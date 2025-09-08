<?php
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check database connection
if (!isset($mysqli) || $mysqli->connect_errno) {
    send_json(['error' => 'Database connection failed'], 500);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$provider_id = isset($_GET['provider_id']) ? (int) $_GET['provider_id'] : 0;

switch ($method) {
    case 'GET':
        if ($id > 0) {
            // Get specific driver
            $stmt = $mysqli->prepare('SELECT d.*, u.username as provider_name, v.license_plate, v.brand, v.model FROM drivers d LEFT JOIN users u ON d.provider_id = u.id LEFT JOIN vehicles v ON d.assigned_vehicle_id = v.id WHERE d.id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) {
                send_json($row);
            } else {
                send_json(['error' => 'Driver not found'], 404);
            }
        } elseif ($provider_id > 0) {
            // Get drivers for specific provider
            $stmt = $mysqli->prepare('SELECT d.*, u.username as provider_name, v.license_plate, v.brand, v.model FROM drivers d LEFT JOIN users u ON d.provider_id = u.id LEFT JOIN vehicles v ON d.assigned_vehicle_id = v.id WHERE d.provider_id = ? ORDER BY d.created_at DESC');
            $stmt->bind_param('i', $provider_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            send_json($data);
        } else {
            // Get all drivers
            $res = $mysqli->query('SELECT d.*, u.username as provider_name, v.license_plate, v.brand, v.model FROM drivers d LEFT JOIN users u ON d.provider_id = u.id LEFT JOIN vehicles v ON d.assigned_vehicle_id = v.id ORDER BY d.created_at DESC');
            $data = [];
            while ($r = $res->fetch_assoc()) {
                $data[] = $r;
            }
            send_json($data);
        }
        break;

    case 'POST':
        $body = read_json_body();
        
        if (empty($body)) {
            send_json(['error' => 'Invalid JSON data or empty request body'], 400);
            exit;
        }
        
        $errors = validate_driver($body);
        if ($errors) {
            send_json(['errors' => $errors], 422);
            exit;
        }
        
        // Generate unique driver code
        $driver_code = 'DRV-' . strtoupper(substr(uniqid(), -6));
        
        $stmt = $mysqli->prepare('INSERT INTO drivers (driver_code, provider_id, first_name, last_name, phone, email, address, license_number, license_type, license_expiry, medical_certificate, medical_expiry, experience_years, emergency_contact_name, emergency_contact_phone, assigned_vehicle_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        
        if (!$stmt) {
            send_json(['error' => 'Failed to prepare statement', 'details' => $mysqli->error], 500);
            exit;
        }
        
        $stmt->bind_param(
            'sisssssssssssisss',
            $driver_code,
            (int)$body['provider_id'],
            $body['first_name'],
            $body['last_name'],
            $body['phone'],
            $body['email'] ?? null,
            $body['address'] ?? null,
            $body['license_number'],
            $body['license_type'],
            $body['license_expiry'],
            $body['medical_certificate'] ?? null,
            $body['medical_expiry'] ?? null,
            isset($body['experience_years']) ? (int)$body['experience_years'] : null,
            $body['emergency_contact_name'] ?? null,
            $body['emergency_contact_phone'] ?? null,
            isset($body['assigned_vehicle_id']) && $body['assigned_vehicle_id'] > 0 ? (int)$body['assigned_vehicle_id'] : null,
            $body['notes'] ?? null
        );
        
        if (!$stmt->execute()) {
            send_json(['error' => 'Insert failed', 'details' => $stmt->error], 500);
            exit;
        }
        
        $driver_id = $stmt->insert_id;
        get_driver_after_write($driver_id);
        break;

    case 'PUT':
        if ($id <= 0) {
            send_json(['error' => 'Missing driver id'], 400);
        }
        $body = read_json_body();
        $errors = validate_driver($body, $id);
        if ($errors) {
            send_json(['errors' => $errors], 422);
        }
        
        // Get current driver
        $current = $mysqli->query("SELECT * FROM drivers WHERE id = $id")->fetch_assoc();
        if (!$current) {
            send_json(['error' => 'Driver not found'], 404);
        }
        
        $stmt = $mysqli->prepare('UPDATE drivers SET first_name=?, last_name=?, phone=?, email=?, address=?, license_number=?, license_type=?, license_expiry=?, medical_certificate=?, medical_expiry=?, experience_years=?, rating=?, emergency_contact_name=?, emergency_contact_phone=?, status=?, assigned_vehicle_id=?, notes=? WHERE id=?');
        $stmt->bind_param(
            'ssssssssssidsssisi',
            $body['first_name'] ?? $current['first_name'],
            $body['last_name'] ?? $current['last_name'],
            $body['phone'] ?? $current['phone'],
            $body['email'] ?? $current['email'],
            $body['address'] ?? $current['address'],
            $body['license_number'] ?? $current['license_number'],
            $body['license_type'] ?? $current['license_type'],
            $body['license_expiry'] ?? $current['license_expiry'],
            $body['medical_certificate'] ?? $current['medical_certificate'],
            $body['medical_expiry'] ?? $current['medical_expiry'],
            isset($body['experience_years']) ? (int)$body['experience_years'] : $current['experience_years'],
            isset($body['rating']) ? (float)$body['rating'] : $current['rating'],
            $body['emergency_contact_name'] ?? $current['emergency_contact_name'],
            $body['emergency_contact_phone'] ?? $current['emergency_contact_phone'],
            $body['status'] ?? $current['status'],
            isset($body['assigned_vehicle_id']) && $body['assigned_vehicle_id'] > 0 ? (int)$body['assigned_vehicle_id'] : $current['assigned_vehicle_id'],
            $body['notes'] ?? $current['notes'],
            $id
        );
        
        if (!$stmt->execute()) {
            send_json(['error' => 'Update failed', 'details' => $stmt->error], 500);
        }
        
        get_driver_after_write($id);
        break;

    case 'DELETE':
        if ($id <= 0) {
            send_json(['error' => 'Missing driver id'], 400);
        }
        
        // Check if driver is assigned to any active services
        $active_services = $mysqli->query("SELECT COUNT(*) as count FROM services WHERE driver_id = $id AND status IN ('active', 'pending', 'en_route', 'loading', 'unloading')")->fetch_assoc();
        if ($active_services['count'] > 0) {
            send_json(['error' => 'Cannot delete driver assigned to active services'], 400);
        }
        
        $stmt = $mysqli->prepare('DELETE FROM drivers WHERE id = ?');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            send_json(['error' => 'Delete failed', 'details' => $stmt->error], 500);
        }
        
        send_json(['success' => true, 'message' => 'Driver deleted successfully']);
        break;

    default:
        send_json(['error' => 'Method not allowed'], 405);
        break;
}

// Additional endpoints for driver management
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $driver_id = isset($_GET['driver_id']) ? (int) $_GET['driver_id'] : 0;
    
    switch ($action) {
        case 'assign_vehicle':
            if ($driver_id <= 0) {
                send_json(['error' => 'Missing driver ID'], 400);
                break;
            }
            $data = read_json_body();
            assign_vehicle_to_driver($driver_id, $data['vehicle_id'] ?? 0);
            break;
            
        case 'unassign_vehicle':
            if ($driver_id <= 0) {
                send_json(['error' => 'Missing driver ID'], 400);
                break;
            }
            unassign_vehicle_from_driver($driver_id);
            break;
            
        case 'update_rating':
            if ($driver_id <= 0) {
                send_json(['error' => 'Missing driver ID'], 400);
                break;
            }
            $data = read_json_body();
            update_driver_rating($driver_id, $data['rating'] ?? 0);
            break;
            
        case 'get_performance':
            if ($driver_id <= 0) {
                send_json(['error' => 'Missing driver ID'], 400);
                break;
            }
            get_driver_performance($driver_id);
            break;
    }
}

function validate_driver(array $body, int $id = 0): array {
    $errors = [];
    
    // Required fields for new drivers
    if ($id === 0) {
        $required = ['provider_id', 'first_name', 'last_name', 'phone', 'license_number', 'license_type', 'license_expiry'];
        foreach ($required as $field) {
            if (!isset($body[$field]) || $body[$field] === '') {
                $errors[$field] = 'This field is required';
            }
        }
    }
    
    // Validate provider exists
    if (isset($body['provider_id']) && $body['provider_id'] > 0) {
        global $mysqli;
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE id = ? AND role = "provider"');
        $stmt->bind_param('i', $body['provider_id']);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            $errors['provider_id'] = 'Invalid provider ID';
        }
    }
    
    // Validate status
    if (isset($body['status'])) {
        $valid_statuses = ['active', 'inactive', 'suspended', 'on_leave'];
        if (!in_array($body['status'], $valid_statuses)) {
            $errors['status'] = 'Invalid status value';
        }
    }
    
    // Validate email format
    if (isset($body['email']) && $body['email'] !== '' && !filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    // Validate license expiry date
    if (isset($body['license_expiry']) && strtotime($body['license_expiry']) < time()) {
        $errors['license_expiry'] = 'License expiry date cannot be in the past';
    }
    
    // Validate rating
    if (isset($body['rating']) && (!is_numeric($body['rating']) || $body['rating'] < 1 || $body['rating'] > 5)) {
        $errors['rating'] = 'Rating must be between 1 and 5';
    }
    
    // Validate unique license number
    if (isset($body['license_number'])) {
        global $mysqli;
        if ($id === 0) {
            $stmt = $mysqli->prepare('SELECT id FROM drivers WHERE license_number = ?');
            $stmt->bind_param('s', $body['license_number']);
        } else {
            $stmt = $mysqli->prepare('SELECT id FROM drivers WHERE license_number = ? AND id != ?');
            $stmt->bind_param('si', $body['license_number'], $id);
        }
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errors['license_number'] = 'License number already exists';
        }
    }
    
    // Validate vehicle assignment
    if (isset($body['assigned_vehicle_id']) && $body['assigned_vehicle_id'] > 0) {
        global $mysqli;
        $stmt = $mysqli->prepare('SELECT id, status FROM vehicles WHERE id = ? AND provider_id = ?');
        $stmt->bind_param('ii', $body['assigned_vehicle_id'], $body['provider_id']);
        $stmt->execute();
        $vehicle = $stmt->get_result()->fetch_assoc();
        if (!$vehicle) {
            $errors['assigned_vehicle_id'] = 'Invalid vehicle ID or vehicle not owned by provider';
        } elseif ($vehicle['status'] !== 'active') {
            $errors['assigned_vehicle_id'] = 'Vehicle is not available (status: ' . $vehicle['status'] . ')';
        }
    }
    
    return $errors;
}

function get_driver_after_write(int $id): void {
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT d.*, u.username as provider_name, v.license_plate, v.brand, v.model FROM drivers d LEFT JOIN users u ON d.provider_id = u.id LEFT JOIN vehicles v ON d.assigned_vehicle_id = v.id WHERE d.id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    send_json($row, 201);
}

function assign_vehicle_to_driver(int $driver_id, int $vehicle_id): void {
    global $mysqli;
    
    $driver = $mysqli->query("SELECT * FROM drivers WHERE id = $driver_id")->fetch_assoc();
    if (!$driver) {
        send_json(['error' => 'Driver not found'], 404);
        return;
    }
    
    $vehicle = $mysqli->query("SELECT * FROM vehicles WHERE id = $vehicle_id AND provider_id = {$driver['provider_id']}")->fetch_assoc();
    if (!$vehicle) {
        send_json(['error' => 'Vehicle not found or not owned by provider'], 404);
        return;
    }
    
    if ($vehicle['status'] !== 'active') {
        send_json(['error' => 'Vehicle is not available'], 400);
        return;
    }
    
    $stmt = $mysqli->prepare('UPDATE drivers SET assigned_vehicle_id = ? WHERE id = ?');
    $stmt->bind_param('ii', $vehicle_id, $driver_id);
    
    if ($stmt->execute()) {
        send_json(['success' => true, 'message' => 'Vehicle assigned successfully']);
    } else {
        send_json(['error' => 'Failed to assign vehicle'], 500);
    }
}

function unassign_vehicle_from_driver(int $driver_id): void {
    global $mysqli;
    
    $stmt = $mysqli->prepare('UPDATE drivers SET assigned_vehicle_id = NULL WHERE id = ?');
    $stmt->bind_param('i', $driver_id);
    
    if ($stmt->execute()) {
        send_json(['success' => true, 'message' => 'Vehicle unassigned successfully']);
    } else {
        send_json(['error' => 'Failed to unassign vehicle'], 500);
    }
}

function update_driver_rating(int $driver_id, float $rating): void {
    global $mysqli;
    
    if ($rating < 1 || $rating > 5) {
        send_json(['error' => 'Rating must be between 1 and 5'], 400);
        return;
    }
    
    $driver = $mysqli->query("SELECT * FROM drivers WHERE id = $driver_id")->fetch_assoc();
    if (!$driver) {
        send_json(['error' => 'Driver not found'], 404);
        return;
    }
    
    $current_rating = $driver['rating'] ?? 0;
    $total_ratings = $driver['total_ratings'];
    
    // Calculate new average rating
    $new_total = $total_ratings + 1;
    $new_rating = (($current_rating * $total_ratings) + $rating) / $new_total;
    
    $stmt = $mysqli->prepare('UPDATE drivers SET rating = ?, total_ratings = ? WHERE id = ?');
    $stmt->bind_param('dii', $new_rating, $new_total, $driver_id);
    
    if ($stmt->execute()) {
        send_json(['success' => true, 'new_rating' => round($new_rating, 2), 'total_ratings' => $new_total]);
    } else {
        send_json(['error' => 'Failed to update rating'], 500);
    }
}

function get_driver_performance(int $driver_id): void {
    global $mysqli;
    
    $driver = $mysqli->query("SELECT * FROM drivers WHERE id = $driver_id")->fetch_assoc();
    if (!$driver) {
        send_json(['error' => 'Driver not found'], 404);
        return;
    }
    
    // Get performance statistics
    $stats = [];
    
    // Total trips completed
    $result = $mysqli->query("SELECT COUNT(*) as count FROM services WHERE driver_id = $driver_id AND status = 'completed'");
    $stats['total_trips'] = $result->fetch_assoc()['count'];
    
    // This month's trips
    $result = $mysqli->query("SELECT COUNT(*) as count FROM services WHERE driver_id = $driver_id AND status = 'completed' AND MONTH(actual_end) = MONTH(NOW()) AND YEAR(actual_end) = YEAR(NOW())");
    $stats['monthly_trips'] = $result->fetch_assoc()['count'];
    
    // Average rating
    $stats['rating'] = $driver['rating'] ? round($driver['rating'], 1) : 0;
    $stats['total_ratings'] = $driver['total_ratings'];
    
    // Total revenue generated (if available in services table)
    $result = $mysqli->query("SELECT COALESCE(SUM(revenue), 0) as revenue FROM services WHERE driver_id = $driver_id AND status = 'completed'");
    $stats['total_revenue'] = $result->fetch_assoc()['revenue'];
    
    // Recent trip performance
    $result = $mysqli->query("SELECT DATE(actual_end) as date, COUNT(*) as trips, SUM(revenue) as revenue FROM services WHERE driver_id = $driver_id AND status = 'completed' AND actual_end >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(actual_end) ORDER BY date DESC");
    $recent_performance = [];
    while ($row = $result->fetch_assoc()) {
        $recent_performance[] = $row;
    }
    $stats['recent_performance'] = $recent_performance;
    
    send_json($stats);
}

?>
