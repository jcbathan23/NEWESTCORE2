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
            // Get specific vehicle
            $stmt = $mysqli->prepare('SELECT v.*, u.username as provider_name FROM vehicles v LEFT JOIN users u ON v.provider_id = u.id WHERE v.id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) {
                send_json($row);
            } else {
                send_json(['error' => 'Vehicle not found'], 404);
            }
        } elseif ($provider_id > 0) {
            // Get vehicles for specific provider
            $stmt = $mysqli->prepare('SELECT v.*, u.username as provider_name FROM vehicles v LEFT JOIN users u ON v.provider_id = u.id WHERE v.provider_id = ? ORDER BY v.created_at DESC');
            $stmt->bind_param('i', $provider_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            send_json($data);
        } else {
            // Get all vehicles
            $res = $mysqli->query('SELECT v.*, u.username as provider_name FROM vehicles v LEFT JOIN users u ON v.provider_id = u.id ORDER BY v.created_at DESC');
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
        
        $errors = validate_vehicle($body);
        if ($errors) {
            send_json(['errors' => $errors], 422);
            exit;
        }
        
        // Generate unique vehicle code
        $vehicle_code = 'VHC-' . strtoupper(substr(uniqid(), -6));
        
        $stmt = $mysqli->prepare('INSERT INTO vehicles (vehicle_code, provider_id, vehicle_type, brand, model, year, license_plate, vin_number, engine_number, fuel_type, capacity_weight, capacity_volume, capacity_passengers, color, insurance_number, insurance_expiry, registration_expiry, gps_device_id, odometer_reading, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        
        if (!$stmt) {
            send_json(['error' => 'Failed to prepare statement', 'details' => $mysqli->error], 500);
            exit;
        }
        
        $stmt->bind_param(
            'sisssississddisissssds',
            $vehicle_code,
            (int)$body['provider_id'],
            $body['vehicle_type'],
            $body['brand'],
            $body['model'],
            (int)$body['year'],
            $body['license_plate'],
            $body['vin_number'] ?? null,
            $body['engine_number'] ?? null,
            $body['fuel_type'],
            isset($body['capacity_weight']) ? (float)$body['capacity_weight'] : null,
            isset($body['capacity_volume']) ? (float)$body['capacity_volume'] : null,
            isset($body['capacity_passengers']) ? (int)$body['capacity_passengers'] : null,
            $body['color'] ?? null,
            $body['insurance_number'] ?? null,
            $body['insurance_expiry'] ?? null,
            $body['registration_expiry'] ?? null,
            $body['gps_device_id'] ?? null,
            isset($body['odometer_reading']) ? (float)$body['odometer_reading'] : null,
            $body['notes'] ?? null
        );
        
        if (!$stmt->execute()) {
            send_json(['error' => 'Insert failed', 'details' => $stmt->error], 500);
            exit;
        }
        
        $vehicle_id = $stmt->insert_id;
        get_vehicle_after_write($vehicle_id);
        break;

    case 'PUT':
        if ($id <= 0) {
            send_json(['error' => 'Missing vehicle id'], 400);
        }
        $body = read_json_body();
        $errors = validate_vehicle($body, $id);
        if ($errors) {
            send_json(['errors' => $errors], 422);
        }
        
        // Get current vehicle
        $current = $mysqli->query("SELECT * FROM vehicles WHERE id = $id")->fetch_assoc();
        if (!$current) {
            send_json(['error' => 'Vehicle not found'], 404);
        }
        
        $stmt = $mysqli->prepare('UPDATE vehicles SET vehicle_type=?, brand=?, model=?, year=?, license_plate=?, vin_number=?, engine_number=?, fuel_type=?, capacity_weight=?, capacity_volume=?, capacity_passengers=?, color=?, insurance_number=?, insurance_expiry=?, registration_expiry=?, last_maintenance=?, next_maintenance=?, gps_device_id=?, status=?, odometer_reading=?, notes=? WHERE id=?');
        $stmt->bind_param(
            'sssississddisissssssdssi',
            $body['vehicle_type'] ?? $current['vehicle_type'],
            $body['brand'] ?? $current['brand'],
            $body['model'] ?? $current['model'],
            isset($body['year']) ? (int)$body['year'] : $current['year'],
            $body['license_plate'] ?? $current['license_plate'],
            $body['vin_number'] ?? $current['vin_number'],
            $body['engine_number'] ?? $current['engine_number'],
            $body['fuel_type'] ?? $current['fuel_type'],
            isset($body['capacity_weight']) ? (float)$body['capacity_weight'] : $current['capacity_weight'],
            isset($body['capacity_volume']) ? (float)$body['capacity_volume'] : $current['capacity_volume'],
            isset($body['capacity_passengers']) ? (int)$body['capacity_passengers'] : $current['capacity_passengers'],
            $body['color'] ?? $current['color'],
            $body['insurance_number'] ?? $current['insurance_number'],
            $body['insurance_expiry'] ?? $current['insurance_expiry'],
            $body['registration_expiry'] ?? $current['registration_expiry'],
            $body['last_maintenance'] ?? $current['last_maintenance'],
            $body['next_maintenance'] ?? $current['next_maintenance'],
            $body['gps_device_id'] ?? $current['gps_device_id'],
            $body['status'] ?? $current['status'],
            isset($body['odometer_reading']) ? (float)$body['odometer_reading'] : $current['odometer_reading'],
            $body['notes'] ?? $current['notes'],
            $id
        );
        
        if (!$stmt->execute()) {
            send_json(['error' => 'Update failed', 'details' => $stmt->error], 500);
        }
        
        get_vehicle_after_write($id);
        break;

    case 'DELETE':
        if ($id <= 0) {
            send_json(['error' => 'Missing vehicle id'], 400);
        }
        
        // Check if vehicle is assigned to any active services
        $active_services = $mysqli->query("SELECT COUNT(*) as count FROM services WHERE vehicle_id = $id AND status IN ('active', 'pending', 'en_route', 'loading', 'unloading')")->fetch_assoc();
        if ($active_services['count'] > 0) {
            send_json(['error' => 'Cannot delete vehicle assigned to active services'], 400);
        }
        
        $stmt = $mysqli->prepare('DELETE FROM vehicles WHERE id = ?');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            send_json(['error' => 'Delete failed', 'details' => $stmt->error], 500);
        }
        
        send_json(['success' => true, 'message' => 'Vehicle deleted successfully']);
        break;

    default:
        send_json(['error' => 'Method not allowed'], 405);
        break;
}

function validate_vehicle(array $body, int $id = 0): array {
    $errors = [];
    
    // Required fields for new vehicles
    if ($id === 0) {
        $required = ['provider_id', 'vehicle_type', 'brand', 'model', 'year', 'license_plate', 'fuel_type'];
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
    
    // Validate vehicle type
    if (isset($body['vehicle_type'])) {
        $valid_types = ['truck', 'motor', 'boat', 'ship', 'bus', 'van', 'motorcycle'];
        if (!in_array($body['vehicle_type'], $valid_types)) {
            $errors['vehicle_type'] = 'Invalid vehicle type';
        }
    }
    
    // Validate fuel type
    if (isset($body['fuel_type'])) {
        $valid_fuel_types = ['petrol', 'diesel', 'electric', 'hybrid', 'cng', 'lpg'];
        if (!in_array($body['fuel_type'], $valid_fuel_types)) {
            $errors['fuel_type'] = 'Invalid fuel type';
        }
    }
    
    // Validate status
    if (isset($body['status'])) {
        $valid_statuses = ['active', 'inactive', 'maintenance', 'retired'];
        if (!in_array($body['status'], $valid_statuses)) {
            $errors['status'] = 'Invalid status value';
        }
    }
    
    // Validate year
    if (isset($body['year'])) {
        $current_year = date('Y');
        if (!is_numeric($body['year']) || $body['year'] < 1900 || $body['year'] > $current_year + 1) {
            $errors['year'] = 'Invalid year';
        }
    }
    
    // Validate capacity values
    if (isset($body['capacity_weight']) && (!is_numeric($body['capacity_weight']) || $body['capacity_weight'] < 0)) {
        $errors['capacity_weight'] = 'Weight capacity must be a valid positive number';
    }
    
    if (isset($body['capacity_volume']) && (!is_numeric($body['capacity_volume']) || $body['capacity_volume'] < 0)) {
        $errors['capacity_volume'] = 'Volume capacity must be a valid positive number';
    }
    
    if (isset($body['capacity_passengers']) && (!is_numeric($body['capacity_passengers']) || $body['capacity_passengers'] < 0)) {
        $errors['capacity_passengers'] = 'Passenger capacity must be a valid positive number';
    }
    
    // Validate unique license plate (for new vehicles or when changing license plate)
    if (isset($body['license_plate'])) {
        global $mysqli;
        if ($id === 0) {
            $stmt = $mysqli->prepare('SELECT id FROM vehicles WHERE license_plate = ?');
            $stmt->bind_param('s', $body['license_plate']);
        } else {
            $stmt = $mysqli->prepare('SELECT id FROM vehicles WHERE license_plate = ? AND id != ?');
            $stmt->bind_param('si', $body['license_plate'], $id);
        }
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errors['license_plate'] = 'License plate already exists';
        }
    }
    
    return $errors;
}

function get_vehicle_after_write(int $id): void {
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT v.*, u.username as provider_name FROM vehicles v LEFT JOIN users u ON v.provider_id = u.id WHERE v.id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    send_json($row, 201);
}

?>
