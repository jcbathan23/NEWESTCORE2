<?php
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Enable error reporting for debugging
if (isset($_GET['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

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
            // Get specific service
            $stmt = $mysqli->prepare('SELECT s.*, u.username as provider_name FROM services s LEFT JOIN users u ON s.provider_id = u.id WHERE s.id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) {
                send_json($row);
            } else {
                send_json(['error' => 'Service not found'], 404);
            }
        } elseif ($provider_id > 0) {
            // Get services for specific provider
            $stmt = $mysqli->prepare('SELECT s.*, u.username as provider_name FROM services s LEFT JOIN users u ON s.provider_id = u.id WHERE s.provider_id = ? ORDER BY s.created_at DESC');
            $stmt->bind_param('i', $provider_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            send_json($data);
        } else {
            // Get all services
            $res = $mysqli->query('SELECT s.*, u.username as provider_name FROM services s LEFT JOIN users u ON s.provider_id = u.id ORDER BY s.created_at DESC');
            $data = [];
            while ($r = $res->fetch_assoc()) {
                $data[] = $r;
            }
            send_json($data);
        }
        break;

    case 'POST':
        $body = read_json_body();
        
        // Check if body was parsed successfully
        if (empty($body)) {
            send_json(['error' => 'Invalid JSON data or empty request body'], 400);
            exit;
        }
        
        $errors = validate_service($body);
        if ($errors) {
            send_json(['errors' => $errors], 422);
            exit;
        }
        
        // Generate unique service code
        $service_code = 'SRV-' . strtoupper(substr(uniqid(), -6));
        
        // Check if table exists
        $table_check = $mysqli->query("SHOW TABLES LIKE 'services'");
        if ($table_check->num_rows === 0) {
            send_json(['error' => 'Services table does not exist. Please run database setup.'], 500);
            exit;
        }
        
        $stmt = $mysqli->prepare('INSERT INTO services (service_code, provider_id, service_type, transport_mode, vehicle_id, driver_id, route, origin, origin_lat, origin_lng, destination, destination_lat, destination_lng, distance_km, capacity_weight, capacity_volume, capacity_passengers, base_fare, per_km_rate, per_weight_rate, fuel_surcharge, insurance_required, special_requirements, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        
        if (!$stmt) {
            send_json(['error' => 'Failed to prepare statement', 'details' => $mysqli->error], 500);
            exit;
        }
        
        // Calculate total fare if pricing components are provided
        $base_fare = (float)($body['base_fare'] ?? 0.00);
        $per_km_rate = (float)($body['per_km_rate'] ?? 0.00);
        $per_weight_rate = (float)($body['per_weight_rate'] ?? 0.00);
        $fuel_surcharge = (float)($body['fuel_surcharge'] ?? 0.00);
        $distance_km = (float)($body['distance_km'] ?? 0.00);
        $capacity_weight = (float)($body['capacity_weight'] ?? 0.00);
        
        $total_fare = $base_fare + ($per_km_rate * $distance_km) + ($per_weight_rate * $capacity_weight) + $fuel_surcharge;
        
        $stmt->bind_param(
            'siisiiisddsdddddddddbss',
            $service_code,
            (int)$body['provider_id'],
            $body['service_type'],
            $body['transport_mode'],
            isset($body['vehicle_id']) && $body['vehicle_id'] > 0 ? (int)$body['vehicle_id'] : null,
            isset($body['driver_id']) && $body['driver_id'] > 0 ? (int)$body['driver_id'] : null,
            $body['route'],
            $body['origin'],
            isset($body['origin_lat']) ? (float)$body['origin_lat'] : null,
            isset($body['origin_lng']) ? (float)$body['origin_lng'] : null,
            $body['destination'],
            isset($body['destination_lat']) ? (float)$body['destination_lat'] : null,
            isset($body['destination_lng']) ? (float)$body['destination_lng'] : null,
            $distance_km,
            $capacity_weight,
            (float)($body['capacity_volume'] ?? 0.00),
            isset($body['capacity_passengers']) ? (int)$body['capacity_passengers'] : null,
            $base_fare,
            $per_km_rate,
            $per_weight_rate,
            $fuel_surcharge,
            isset($body['insurance_required']) ? (bool)$body['insurance_required'] : false,
            $body['special_requirements'] ?? null,
            $body['notes'] ?? ''
        );
        
        // Update total_fare in the database after insert
        if ($stmt->execute()) {
            $service_id = $stmt->insert_id;
            
            // Update total_fare
            $update_fare_stmt = $mysqli->prepare('UPDATE services SET total_fare = ? WHERE id = ?');
            $update_fare_stmt->bind_param('di', $total_fare, $service_id);
            $update_fare_stmt->execute();
            
        } else {
            send_json(['error' => 'Insert failed', 'details' => $stmt->error, 'sql_error' => $mysqli->error], 500);
            exit;
        }
        
        $service_id = $stmt->insert_id;
        
        // Log service creation (only if history table exists)
        $history_check = $mysqli->query("SHOW TABLES LIKE 'service_history'");
        if ($history_check->num_rows > 0) {
            try {
                log_service_action($service_id, 'created', null, 'pending', null, null, 'Service created', $body['provider_id']);
            } catch (Exception $e) {
                // Don't fail service creation if logging fails
                error_log('Failed to log service creation: ' . $e->getMessage());
            }
        }
        
        get_service_after_write($service_id);
        break;

    case 'PUT':
        if ($id <= 0) {
            send_json(['error' => 'Missing service id'], 400);
        }
        $body = read_json_body();
        $errors = validate_service($body, $id);
        if ($errors) {
            send_json(['errors' => $errors], 422);
        }
        
        // Get current service for logging
        $current = $mysqli->query("SELECT * FROM services WHERE id = $id")->fetch_assoc();
        if (!$current) {
            send_json(['error' => 'Service not found'], 404);
        }
        
        $stmt = $mysqli->prepare('UPDATE services SET service_type=?, transport_mode=?, vehicle_id=?, driver_id=?, route=?, origin=?, origin_lat=?, origin_lng=?, destination=?, destination_lat=?, destination_lng=?, distance_km=?, capacity_weight=?, capacity_volume=?, capacity_passengers=?, current_load_weight=?, current_passengers=?, base_fare=?, per_km_rate=?, per_weight_rate=?, fuel_surcharge=?, total_fare=?, insurance_required=?, special_requirements=?, notes=?, status=? WHERE id=?');
        
        // Calculate new total fare
        $base_fare = (float)($body['base_fare'] ?? $current['base_fare']);
        $per_km_rate = (float)($body['per_km_rate'] ?? $current['per_km_rate']);
        $per_weight_rate = (float)($body['per_weight_rate'] ?? $current['per_weight_rate']);
        $fuel_surcharge = (float)($body['fuel_surcharge'] ?? $current['fuel_surcharge']);
        $distance_km = (float)($body['distance_km'] ?? $current['distance_km']);
        $capacity_weight = (float)($body['capacity_weight'] ?? $current['capacity_weight']);
        
        $total_fare = $base_fare + ($per_km_rate * $distance_km) + ($per_weight_rate * $capacity_weight) + $fuel_surcharge;
        
        $stmt->bind_param(
            'ssiissddsdddddddddddbsssi',
            $body['service_type'] ?? $current['service_type'],
            $body['transport_mode'] ?? $current['transport_mode'],
            isset($body['vehicle_id']) && $body['vehicle_id'] > 0 ? (int)$body['vehicle_id'] : $current['vehicle_id'],
            isset($body['driver_id']) && $body['driver_id'] > 0 ? (int)$body['driver_id'] : $current['driver_id'],
            $body['route'] ?? $current['route'],
            $body['origin'] ?? $current['origin'],
            isset($body['origin_lat']) ? (float)$body['origin_lat'] : $current['origin_lat'],
            isset($body['origin_lng']) ? (float)$body['origin_lng'] : $current['origin_lng'],
            $body['destination'] ?? $current['destination'],
            isset($body['destination_lat']) ? (float)$body['destination_lat'] : $current['destination_lat'],
            isset($body['destination_lng']) ? (float)$body['destination_lng'] : $current['destination_lng'],
            $distance_km,
            $capacity_weight,
            (float)($body['capacity_volume'] ?? $current['capacity_volume']),
            isset($body['capacity_passengers']) ? (int)$body['capacity_passengers'] : $current['capacity_passengers'],
            (float)($body['current_load_weight'] ?? $current['current_load_weight']),
            (int)($body['current_passengers'] ?? $current['current_passengers']),
            $base_fare,
            $per_km_rate,
            $per_weight_rate,
            $fuel_surcharge,
            $total_fare,
            isset($body['insurance_required']) ? (bool)$body['insurance_required'] : $current['insurance_required'],
            $body['special_requirements'] ?? $current['special_requirements'],
            $body['notes'] ?? $current['notes'],
            $body['status'] ?? $current['status'],
            $id
        );
        
        if (!$stmt->execute()) {
            send_json(['error' => 'Update failed', 'details' => $stmt->error], 500);
        }
        
        // Log service update if status changed
        if (isset($body['status']) && $body['status'] !== $current['status']) {
            log_service_action($id, 'status_changed', $current['status'], $body['status'], $body['current_passengers'] ?? null, null, 'Service status updated', $current['provider_id']);
        }
        
        get_service_after_write($id);
        break;

    case 'DELETE':
        if ($id <= 0) {
            send_json(['error' => 'Missing service id'], 400);
        }
        
        // Get service info for logging
        $service = $mysqli->query("SELECT * FROM services WHERE id = $id")->fetch_assoc();
        if (!$service) {
            send_json(['error' => 'Service not found'], 404);
        }
        
        $stmt = $mysqli->prepare('DELETE FROM services WHERE id = ?');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            send_json(['error' => 'Delete failed', 'details' => $stmt->error], 500);
        }
        
        // Log service deletion
        log_service_action($id, 'deleted', $service['status'], 'deleted', null, null, 'Service deleted', $service['provider_id']);
        
        send_json(['success' => true, 'message' => 'Service deleted successfully']);
        break;

    default:
        send_json(['error' => 'Method not allowed'], 405);
        break;
}

function validate_service(array $body, int $id = 0): array {
    $errors = [];
    
    // Required fields for new services
    if ($id === 0) {
        $required = ['provider_id', 'service_type', 'transport_mode', 'route', 'origin', 'destination'];
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
    
    // Validate service type
    if (isset($body['service_type'])) {
        $valid_service_types = ['freight_transport', 'passenger_transport', 'cargo_delivery', 'moving_service', 'logistics_service'];
        if (!in_array($body['service_type'], $valid_service_types)) {
            $errors['service_type'] = 'Invalid service type';
        }
    }
    
    // Validate transport mode
    if (isset($body['transport_mode'])) {
        $valid_transport_modes = ['truck', 'motor', 'boat', 'ship', 'bus', 'van', 'motorcycle'];
        if (!in_array($body['transport_mode'], $valid_transport_modes)) {
            $errors['transport_mode'] = 'Invalid transport mode';
        }
    }
    
    // Validate vehicle assignment
    if (isset($body['vehicle_id']) && $body['vehicle_id'] > 0) {
        global $mysqli;
        $stmt = $mysqli->prepare('SELECT id, status FROM vehicles WHERE id = ? AND provider_id = ?');
        $stmt->bind_param('ii', $body['vehicle_id'], $body['provider_id']);
        $stmt->execute();
        $vehicle = $stmt->get_result()->fetch_assoc();
        if (!$vehicle) {
            $errors['vehicle_id'] = 'Invalid vehicle ID or vehicle not owned by provider';
        } elseif ($vehicle['status'] !== 'active') {
            $errors['vehicle_id'] = 'Vehicle is not available (status: ' . $vehicle['status'] . ')';
        }
    }
    
    // Validate driver assignment
    if (isset($body['driver_id']) && $body['driver_id'] > 0) {
        global $mysqli;
        $stmt = $mysqli->prepare('SELECT id, status, license_expiry FROM drivers WHERE id = ? AND provider_id = ?');
        $stmt->bind_param('ii', $body['driver_id'], $body['provider_id']);
        $stmt->execute();
        $driver = $stmt->get_result()->fetch_assoc();
        if (!$driver) {
            $errors['driver_id'] = 'Invalid driver ID or driver not employed by provider';
        } elseif ($driver['status'] !== 'active') {
            $errors['driver_id'] = 'Driver is not available (status: ' . $driver['status'] . ')';
        } elseif ($driver['license_expiry'] && strtotime($driver['license_expiry']) < time()) {
            $errors['driver_id'] = 'Driver license has expired';
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
    
    // Validate current load doesn't exceed capacity
    if (isset($body['current_load_weight']) && isset($body['capacity_weight'])) {
        if ($body['current_load_weight'] > $body['capacity_weight']) {
            $errors['current_load_weight'] = 'Current load weight cannot exceed capacity';
        }
    }
    
    if (isset($body['current_passengers']) && isset($body['capacity_passengers'])) {
        if ($body['current_passengers'] > $body['capacity_passengers']) {
            $errors['current_passengers'] = 'Current passengers cannot exceed capacity';
        }
    }
    
    // Validate status
    if (isset($body['status'])) {
        $valid_statuses = ['active', 'inactive', 'pending', 'maintenance', 'completed', 'cancelled', 'en_route', 'loading', 'unloading'];
        if (!in_array($body['status'], $valid_statuses)) {
            $errors['status'] = 'Invalid status value';
        }
    }
    
    // Validate pricing
    $pricing_fields = ['base_fare', 'per_km_rate', 'per_weight_rate', 'fuel_surcharge', 'total_fare'];
    foreach ($pricing_fields as $field) {
        if (isset($body[$field]) && (!is_numeric($body[$field]) || $body[$field] < 0)) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid positive number';
        }
    }
    
    // Validate coordinates
    if (isset($body['origin_lat']) && (!is_numeric($body['origin_lat']) || abs($body['origin_lat']) > 90)) {
        $errors['origin_lat'] = 'Origin latitude must be between -90 and 90';
    }
    
    if (isset($body['origin_lng']) && (!is_numeric($body['origin_lng']) || abs($body['origin_lng']) > 180)) {
        $errors['origin_lng'] = 'Origin longitude must be between -180 and 180';
    }
    
    if (isset($body['destination_lat']) && (!is_numeric($body['destination_lat']) || abs($body['destination_lat']) > 90)) {
        $errors['destination_lat'] = 'Destination latitude must be between -90 and 90';
    }
    
    if (isset($body['destination_lng']) && (!is_numeric($body['destination_lng']) || abs($body['destination_lng']) > 180)) {
        $errors['destination_lng'] = 'Destination longitude must be between -180 and 180';
    }
    
    return $errors;
}

function get_service_after_write(int $id): void {
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT s.*, u.username as provider_name FROM services s LEFT JOIN users u ON s.provider_id = u.id WHERE s.id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    send_json($row, 201);
}

function log_service_action(int $service_id, string $action, ?string $previous_status, string $new_status, ?int $passenger_count, ?float $revenue_amount, ?string $notes, ?int $action_by_user_id): void {
    global $mysqli;
    $stmt = $mysqli->prepare('INSERT INTO service_history (service_id, action, previous_status, new_status, passenger_count, revenue_amount, notes, action_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssidsi', $service_id, $action, $previous_status, $new_status, $passenger_count, $revenue_amount, $notes, $action_by_user_id);
    $stmt->execute();
}

// Additional endpoints for service management
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $service_id = isset($_GET['service_id']) ? (int) $_GET['service_id'] : 0;
    
    switch ($action) {
        case 'start_service':
            if ($service_id <= 0) {
                send_json(['error' => 'Missing service ID'], 400);
                break;
            }
            start_service($service_id);
            break;
            
        case 'complete_service':
            if ($service_id <= 0) {
                send_json(['error' => 'Missing service ID'], 400);
                break;
            }
            $data = read_json_body();
            complete_service($service_id, $data);
            break;
            
        case 'update_passengers':
            if ($service_id <= 0) {
                send_json(['error' => 'Missing service ID'], 400);
                break;
            }
            $data = read_json_body();
            update_passenger_count($service_id, $data['passenger_count'] ?? 0);
            break;
            
        case 'get_history':
            if ($service_id <= 0) {
                send_json(['error' => 'Missing service ID'], 400);
                break;
            }
            get_service_history($service_id);
            break;
            
        case 'get_stats':
            if ($provider_id <= 0) {
                send_json(['error' => 'Missing provider ID'], 400);
                break;
            }
            get_provider_service_stats($provider_id);
            break;
            
        case 'assign_vehicle':
            if ($service_id <= 0) {
                send_json(['error' => 'Missing service ID'], 400);
                break;
            }
            $data = read_json_body();
            assign_vehicle_to_service($service_id, $data['vehicle_id'] ?? 0);
            break;
            
        case 'assign_driver':
            if ($service_id <= 0) {
                send_json(['error' => 'Missing service ID'], 400);
                break;
            }
            $data = read_json_body();
            assign_driver_to_service($service_id, $data['driver_id'] ?? 0);
            break;
            
        case 'update_location':
            if ($service_id <= 0) {
                send_json(['error' => 'Missing service ID'], 400);
                break;
            }
            $data = read_json_body();
            update_service_location($service_id, $data);
            break;
            
        case 'add_cargo':
            if ($service_id <= 0) {
                send_json(['error' => 'Missing service ID'], 400);
                break;
            }
            $data = read_json_body();
            add_cargo_to_service($service_id, $data);
            break;
            
        case 'load_cargo':
            if ($service_id <= 0) {
                send_json(['error' => 'Missing service ID'], 400);
                break;
            }
            $data = read_json_body();
            load_cargo($service_id, $data['cargo_ids'] ?? []);
            break;
            
        case 'unload_cargo':
            if ($service_id <= 0) {
                send_json(['error' => 'Missing service ID'], 400);
                break;
            }
            $data = read_json_body();
            unload_cargo($service_id, $data['cargo_ids'] ?? []);
            break;
            
        case 'get_cargo':
            if ($service_id <= 0) {
                send_json(['error' => 'Missing service ID'], 400);
                break;
            }
            get_service_cargo($service_id);
            break;
            
        case 'calculate_fare':
            if ($service_id <= 0) {
                send_json(['error' => 'Missing service ID'], 400);
                break;
            }
            calculate_service_fare($service_id);
            break;
    }
}

function start_service(int $service_id): void {
    global $mysqli;
    
    // Get current service
    $service = $mysqli->query("SELECT * FROM services WHERE id = $service_id")->fetch_assoc();
    if (!$service) {
        send_json(['error' => 'Service not found'], 404);
        return;
    }
    
    // Update service status and start time
    $stmt = $mysqli->prepare('UPDATE services SET status = "active", actual_start = NOW() WHERE id = ?');
    $stmt->bind_param('i', $service_id);
    
    if ($stmt->execute()) {
        log_service_action($service_id, 'started', $service['status'], 'active', null, null, 'Service started', $service['provider_id']);
        send_json(['success' => true, 'message' => 'Service started successfully']);
    } else {
        send_json(['error' => 'Failed to start service'], 500);
    }
}

function complete_service(int $service_id, array $data): void {
    global $mysqli;
    
    // Get current service
    $service = $mysqli->query("SELECT * FROM services WHERE id = $service_id")->fetch_assoc();
    if (!$service) {
        send_json(['error' => 'Service not found'], 404);
        return;
    }
    
    $revenue = $data['revenue'] ?? 0;
    $rating = $data['rating'] ?? null;
    $passenger_count = $data['passenger_count'] ?? $service['current_passengers'];
    
    // Update service
    $stmt = $mysqli->prepare('UPDATE services SET status = "completed", actual_end = NOW(), revenue = ?, current_passengers = ?, rating = ? WHERE id = ?');
    $stmt->bind_param('didi', $revenue, $passenger_count, $rating, $service_id);
    
    if ($stmt->execute()) {
        log_service_action($service_id, 'completed', $service['status'], 'completed', $passenger_count, $revenue, 'Service completed', $service['provider_id']);
        send_json(['success' => true, 'message' => 'Service completed successfully']);
    } else {
        send_json(['error' => 'Failed to complete service'], 500);
    }
}

function update_passenger_count(int $service_id, int $passenger_count): void {
    global $mysqli;
    
    // Get current service
    $service = $mysqli->query("SELECT * FROM services WHERE id = $service_id")->fetch_assoc();
    if (!$service) {
        send_json(['error' => 'Service not found'], 404);
        return;
    }
    
    // Validate passenger count
    if ($passenger_count > $service['capacity']) {
        send_json(['error' => 'Passenger count exceeds capacity'], 400);
        return;
    }
    
    $stmt = $mysqli->prepare('UPDATE services SET current_passengers = ? WHERE id = ?');
    $stmt->bind_param('ii', $passenger_count, $service_id);
    
    if ($stmt->execute()) {
        log_service_action($service_id, 'passenger_update', null, $service['status'], $passenger_count, null, 'Passenger count updated', $service['provider_id']);
        send_json(['success' => true, 'message' => 'Passenger count updated']);
    } else {
        send_json(['error' => 'Failed to update passenger count'], 500);
    }
}

function get_service_history(int $service_id): void {
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT sh.*, u.username as action_by_username FROM service_history sh LEFT JOIN users u ON sh.action_by_user_id = u.id WHERE sh.service_id = ? ORDER BY sh.created_at DESC');
    $stmt->bind_param('i', $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    send_json($data);
}

function get_provider_service_stats(int $provider_id): void {
    global $mysqli;
    
    // Get comprehensive stats
    $stats = [];
    
    // Active services count
    $result = $mysqli->query("SELECT COUNT(*) as count FROM services WHERE provider_id = $provider_id AND status = 'active'");
    $stats['active_services'] = $result->fetch_assoc()['count'];
    
    // Completed today
    $result = $mysqli->query("SELECT COUNT(*) as count FROM services WHERE provider_id = $provider_id AND status = 'completed' AND DATE(actual_end) = CURDATE()");
    $stats['completed_today'] = $result->fetch_assoc()['count'];
    
    // Monthly revenue
    $result = $mysqli->query("SELECT COALESCE(SUM(revenue), 0) as revenue FROM services WHERE provider_id = $provider_id AND status = 'completed' AND MONTH(actual_end) = MONTH(NOW()) AND YEAR(actual_end) = YEAR(NOW())");
    $stats['monthly_revenue'] = $result->fetch_assoc()['revenue'];
    
    // Average rating
    $result = $mysqli->query("SELECT AVG(rating) as rating FROM services WHERE provider_id = $provider_id AND rating IS NOT NULL");
    $rating = $result->fetch_assoc()['rating'];
    $stats['average_rating'] = $rating ? round($rating, 1) : 0;
    
    // Weekly performance data
    $result = $mysqli->query("SELECT DATE(actual_end) as date, COUNT(*) as services, SUM(revenue) as revenue FROM services WHERE provider_id = $provider_id AND status = 'completed' AND actual_end >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(actual_end) ORDER BY date");
    $weekly_data = [];
    while ($row = $result->fetch_assoc()) {
        $weekly_data[] = $row;
    }
    $stats['weekly_performance'] = $weekly_data;
    
    send_json($stats);
}

// Transport-specific functions

function assign_vehicle_to_service(int $service_id, int $vehicle_id): void {
    global $mysqli;
    
    // Validate service exists
    $service = $mysqli->query("SELECT * FROM services WHERE id = $service_id")->fetch_assoc();
    if (!$service) {
        send_json(['error' => 'Service not found'], 404);
        return;
    }
    
    // Validate vehicle exists and is available
    $vehicle = $mysqli->query("SELECT * FROM vehicles WHERE id = $vehicle_id AND provider_id = {$service['provider_id']}")->fetch_assoc();
    if (!$vehicle) {
        send_json(['error' => 'Vehicle not found or not owned by provider'], 404);
        return;
    }
    
    if ($vehicle['status'] !== 'active') {
        send_json(['error' => 'Vehicle is not available (status: ' . $vehicle['status'] . ')'], 400);
        return;
    }
    
    // Check if vehicle is already assigned to another active service
    $existing = $mysqli->query("SELECT id FROM services WHERE vehicle_id = $vehicle_id AND status IN ('active', 'pending', 'en_route', 'loading', 'unloading')")->fetch_assoc();
    if ($existing && $existing['id'] != $service_id) {
        send_json(['error' => 'Vehicle is already assigned to another active service'], 400);
        return;
    }
    
    // Assign vehicle to service
    $stmt = $mysqli->prepare('UPDATE services SET vehicle_id = ?, transport_mode = ?, capacity_weight = ?, capacity_volume = ?, capacity_passengers = ? WHERE id = ?');
    $stmt->bind_param('isddii', $vehicle_id, $vehicle['vehicle_type'], $vehicle['capacity_weight'], $vehicle['capacity_volume'], $vehicle['capacity_passengers'], $service_id);
    
    if ($stmt->execute()) {
        log_service_action($service_id, 'vehicle_assigned', $service['status'], $service['status'], null, null, "Vehicle {$vehicle['license_plate']} assigned", $service['provider_id']);
        send_json(['success' => true, 'message' => 'Vehicle assigned successfully', 'vehicle' => $vehicle]);
    } else {
        send_json(['error' => 'Failed to assign vehicle'], 500);
    }
}

function assign_driver_to_service(int $service_id, int $driver_id): void {
    global $mysqli;
    
    // Validate service exists
    $service = $mysqli->query("SELECT * FROM services WHERE id = $service_id")->fetch_assoc();
    if (!$service) {
        send_json(['error' => 'Service not found'], 404);
        return;
    }
    
    // Validate driver exists and is available
    $driver = $mysqli->query("SELECT * FROM drivers WHERE id = $driver_id AND provider_id = {$service['provider_id']}")->fetch_assoc();
    if (!$driver) {
        send_json(['error' => 'Driver not found or not employed by provider'], 404);
        return;
    }
    
    if ($driver['status'] !== 'active') {
        send_json(['error' => 'Driver is not available (status: ' . $driver['status'] . ')'], 400);
        return;
    }
    
    // Check license validity
    if (strtotime($driver['license_expiry']) < time()) {
        send_json(['error' => 'Driver license has expired'], 400);
        return;
    }
    
    // Check if driver is already assigned to another active service
    $existing = $mysqli->query("SELECT id FROM services WHERE driver_id = $driver_id AND status IN ('active', 'pending', 'en_route', 'loading', 'unloading')")->fetch_assoc();
    if ($existing && $existing['id'] != $service_id) {
        send_json(['error' => 'Driver is already assigned to another active service'], 400);
        return;
    }
    
    // Assign driver to service
    $stmt = $mysqli->prepare('UPDATE services SET driver_id = ? WHERE id = ?');
    $stmt->bind_param('ii', $driver_id, $service_id);
    
    if ($stmt->execute()) {
        // Update driver's assigned vehicle if service has one
        if ($service['vehicle_id']) {
            $mysqli->query("UPDATE drivers SET assigned_vehicle_id = {$service['vehicle_id']} WHERE id = $driver_id");
        }
        
        log_service_action($service_id, 'driver_assigned', $service['status'], $service['status'], null, null, "Driver {$driver['first_name']} {$driver['last_name']} assigned", $service['provider_id']);
        send_json(['success' => true, 'message' => 'Driver assigned successfully', 'driver' => $driver]);
    } else {
        send_json(['error' => 'Failed to assign driver'], 500);
    }
}

function update_service_location(int $service_id, array $data): void {
    global $mysqli;
    
    // Validate service exists
    $service = $mysqli->query("SELECT * FROM services WHERE id = $service_id")->fetch_assoc();
    if (!$service) {
        send_json(['error' => 'Service not found'], 404);
        return;
    }
    
    // Validate coordinates
    if (!isset($data['lat']) || !isset($data['lng'])) {
        send_json(['error' => 'Latitude and longitude are required'], 400);
        return;
    }
    
    $lat = (float)$data['lat'];
    $lng = (float)$data['lng'];
    
    if (abs($lat) > 90 || abs($lng) > 180) {
        send_json(['error' => 'Invalid coordinates'], 400);
        return;
    }
    
    // Update service location
    $stmt = $mysqli->prepare('UPDATE services SET current_location_lat = ?, current_location_lng = ?, last_location_update = NOW() WHERE id = ?');
    $stmt->bind_param('ddi', $lat, $lng, $service_id);
    
    // Also update vehicle location if assigned
    if ($service['vehicle_id']) {
        $vehicle_stmt = $mysqli->prepare('UPDATE vehicles SET current_location_lat = ?, current_location_lng = ?, last_location_update = NOW() WHERE id = ?');
        $vehicle_stmt->bind_param('ddi', $lat, $lng, $service['vehicle_id']);
        $vehicle_stmt->execute();
    }
    
    if ($stmt->execute()) {
        log_service_action($service_id, 'location_updated', null, $service['status'], null, null, "Location updated to {$lat}, {$lng}", $service['provider_id']);
        send_json(['success' => true, 'message' => 'Location updated successfully', 'lat' => $lat, 'lng' => $lng]);
    } else {
        send_json(['error' => 'Failed to update location'], 500);
    }
}

function add_cargo_to_service(int $service_id, array $data): void {
    global $mysqli;
    
    // Validate service exists
    $service = $mysqli->query("SELECT * FROM services WHERE id = $service_id")->fetch_assoc();
    if (!$service) {
        send_json(['error' => 'Service not found'], 404);
        return;
    }
    
    // Validate required cargo fields
    $required = ['item_name', 'item_type', 'quantity', 'unit', 'loading_point', 'unloading_point'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            send_json(['error' => "Field '$field' is required"], 400);
            return;
        }
    }
    
    // Generate cargo code
    $cargo_code = 'CGO-' . strtoupper(substr(uniqid(), -8));
    
    // Insert cargo
    $stmt = $mysqli->prepare('INSERT INTO cargo (cargo_code, service_id, item_name, item_type, quantity, unit, weight, volume, value, dangerous_goods, special_instructions, loading_point, unloading_point, recipient_name, recipient_phone, sender_name, sender_phone, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    
    $stmt->bind_param(
        'sissdsddbsssssssss',
        $cargo_code,
        $service_id,
        $data['item_name'],
        $data['item_type'],
        (float)$data['quantity'],
        $data['unit'],
        isset($data['weight']) ? (float)$data['weight'] : null,
        isset($data['volume']) ? (float)$data['volume'] : null,
        isset($data['value']) ? (float)$data['value'] : null,
        isset($data['dangerous_goods']) ? (bool)$data['dangerous_goods'] : false,
        $data['special_instructions'] ?? null,
        $data['loading_point'],
        $data['unloading_point'],
        $data['recipient_name'] ?? null,
        $data['recipient_phone'] ?? null,
        $data['sender_name'] ?? null,
        $data['sender_phone'] ?? null,
        $data['notes'] ?? null
    );
    
    if ($stmt->execute()) {
        $cargo_id = $stmt->insert_id;
        $cargo = $mysqli->query("SELECT * FROM cargo WHERE id = $cargo_id")->fetch_assoc();
        
        log_service_action($service_id, 'cargo_added', $service['status'], $service['status'], null, null, "Cargo {$data['item_name']} added", $service['provider_id']);
        send_json(['success' => true, 'message' => 'Cargo added successfully', 'cargo' => $cargo], 201);
    } else {
        send_json(['error' => 'Failed to add cargo', 'details' => $stmt->error], 500);
    }
}

function load_cargo(int $service_id, array $cargo_ids): void {
    global $mysqli;
    
    if (empty($cargo_ids)) {
        send_json(['error' => 'No cargo IDs provided'], 400);
        return;
    }
    
    $service = $mysqli->query("SELECT * FROM services WHERE id = $service_id")->fetch_assoc();
    if (!$service) {
        send_json(['error' => 'Service not found'], 404);
        return;
    }
    
    $loaded_cargo = [];
    $total_weight = 0;
    $total_volume = 0;
    
    foreach ($cargo_ids as $cargo_id) {
        $cargo = $mysqli->query("SELECT * FROM cargo WHERE id = $cargo_id AND service_id = $service_id")->fetch_assoc();
        if ($cargo && $cargo['status'] === 'pending') {
            // Update cargo status
            $mysqli->query("UPDATE cargo SET status = 'loaded', loaded_at = NOW() WHERE id = $cargo_id");
            $loaded_cargo[] = $cargo;
            $total_weight += $cargo['weight'] ?? 0;
            $total_volume += $cargo['volume'] ?? 0;
        }
    }
    
    // Update service current load
    $new_weight = $service['current_load_weight'] + $total_weight;
    $mysqli->query("UPDATE services SET current_load_weight = $new_weight, status = 'loading' WHERE id = $service_id");
    
    log_service_action($service_id, 'cargo_loaded', $service['status'], 'loading', null, null, count($loaded_cargo) . " cargo items loaded", $service['provider_id']);
    send_json(['success' => true, 'message' => 'Cargo loaded successfully', 'loaded_count' => count($loaded_cargo), 'total_weight' => $total_weight]);
}

function unload_cargo(int $service_id, array $cargo_ids): void {
    global $mysqli;
    
    if (empty($cargo_ids)) {
        send_json(['error' => 'No cargo IDs provided'], 400);
        return;
    }
    
    $service = $mysqli->query("SELECT * FROM services WHERE id = $service_id")->fetch_assoc();
    if (!$service) {
        send_json(['error' => 'Service not found'], 404);
        return;
    }
    
    $unloaded_cargo = [];
    $total_weight = 0;
    $total_volume = 0;
    
    foreach ($cargo_ids as $cargo_id) {
        $cargo = $mysqli->query("SELECT * FROM cargo WHERE id = $cargo_id AND service_id = $service_id")->fetch_assoc();
        if ($cargo && in_array($cargo['status'], ['loaded', 'in_transit'])) {
            // Update cargo status
            $mysqli->query("UPDATE cargo SET status = 'delivered', unloaded_at = NOW() WHERE id = $cargo_id");
            $unloaded_cargo[] = $cargo;
            $total_weight += $cargo['weight'] ?? 0;
            $total_volume += $cargo['volume'] ?? 0;
        }
    }
    
    // Update service current load
    $new_weight = max(0, $service['current_load_weight'] - $total_weight);
    $mysqli->query("UPDATE services SET current_load_weight = $new_weight, status = 'unloading' WHERE id = $service_id");
    
    log_service_action($service_id, 'cargo_unloaded', $service['status'], 'unloading', null, null, count($unloaded_cargo) . " cargo items unloaded", $service['provider_id']);
    send_json(['success' => true, 'message' => 'Cargo unloaded successfully', 'unloaded_count' => count($unloaded_cargo), 'total_weight' => $total_weight]);
}

function get_service_cargo(int $service_id): void {
    global $mysqli;
    
    $stmt = $mysqli->prepare('SELECT * FROM cargo WHERE service_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cargo_list = [];
    while ($row = $result->fetch_assoc()) {
        $cargo_list[] = $row;
    }
    
    send_json($cargo_list);
}

function calculate_service_fare(int $service_id): void {
    global $mysqli;
    
    $service = $mysqli->query("SELECT * FROM services WHERE id = $service_id")->fetch_assoc();
    if (!$service) {
        send_json(['error' => 'Service not found'], 404);
        return;
    }
    
    // Get total cargo weight and value
    $cargo_result = $mysqli->query("SELECT SUM(weight) as total_weight, SUM(value) as total_value, COUNT(*) as cargo_count FROM cargo WHERE service_id = $service_id");
    $cargo_data = $cargo_result->fetch_assoc();
    
    $base_fare = $service['base_fare'];
    $distance_km = $service['distance_km'] ?? 0;
    $per_km_rate = $service['per_km_rate'];
    $cargo_weight = $cargo_data['total_weight'] ?? $service['current_load_weight'];
    $per_weight_rate = $service['per_weight_rate'];
    $fuel_surcharge = $service['fuel_surcharge'];
    
    // Calculate total fare
    $distance_charge = $distance_km * $per_km_rate;
    $weight_charge = $cargo_weight * $per_weight_rate;
    $total_fare = $base_fare + $distance_charge + $weight_charge + $fuel_surcharge;
    
    // Apply insurance if required and cargo value exists
    $insurance_fee = 0;
    if ($service['insurance_required'] && $cargo_data['total_value']) {
        $insurance_fee = $cargo_data['total_value'] * 0.02; // 2% insurance fee
        $total_fare += $insurance_fee;
    }
    
    // Update service fare
    $stmt = $mysqli->prepare('UPDATE services SET total_fare = ? WHERE id = ?');
    $stmt->bind_param('di', $total_fare, $service_id);
    $stmt->execute();
    
    $fare_breakdown = [
        'base_fare' => $base_fare,
        'distance_km' => $distance_km,
        'distance_charge' => $distance_charge,
        'cargo_weight' => $cargo_weight,
        'weight_charge' => $weight_charge,
        'fuel_surcharge' => $fuel_surcharge,
        'insurance_fee' => $insurance_fee,
        'total_fare' => $total_fare,
        'cargo_count' => $cargo_data['cargo_count'] ?? 0,
        'cargo_value' => $cargo_data['total_value'] ?? 0
    ];
    
    send_json(['success' => true, 'fare_breakdown' => $fare_breakdown]);
}

?>
