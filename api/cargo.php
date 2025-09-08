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
$service_id = isset($_GET['service_id']) ? (int) $_GET['service_id'] : 0;

switch ($method) {
    case 'GET':
        if ($id > 0) {
            // Get specific cargo
            $stmt = $mysqli->prepare('SELECT c.*, s.service_code, s.origin, s.destination FROM cargo c LEFT JOIN services s ON c.service_id = s.id WHERE c.id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) {
                send_json($row);
            } else {
                send_json(['error' => 'Cargo not found'], 404);
            }
        } elseif ($service_id > 0) {
            // Get cargo for specific service
            $stmt = $mysqli->prepare('SELECT c.*, s.service_code, s.origin, s.destination FROM cargo c LEFT JOIN services s ON c.service_id = s.id WHERE c.service_id = ? ORDER BY c.created_at DESC');
            $stmt->bind_param('i', $service_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            send_json($data);
        } else {
            // Get all cargo with optional filters
            $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
            $tracking_number = isset($_GET['tracking_number']) ? $_GET['tracking_number'] : '';
            
            $query = 'SELECT c.*, s.service_code, s.origin, s.destination FROM cargo c LEFT JOIN services s ON c.service_id = s.id WHERE 1=1';
            $params = [];
            $types = '';
            
            if ($status_filter) {
                $query .= ' AND c.status = ?';
                $params[] = $status_filter;
                $types .= 's';
            }
            
            if ($tracking_number) {
                $query .= ' AND c.tracking_number = ?';
                $params[] = $tracking_number;
                $types .= 's';
            }
            
            $query .= ' ORDER BY c.created_at DESC';
            
            if ($params) {
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $mysqli->query($query);
            }
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
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
        
        $errors = validate_cargo($body);
        if ($errors) {
            send_json(['errors' => $errors], 422);
            exit;
        }
        
        // Generate unique cargo code and tracking number
        $cargo_code = 'CGO-' . strtoupper(substr(uniqid(), -8));
        $tracking_number = 'TRK-' . strtoupper(substr(uniqid(), -10));
        
        $stmt = $mysqli->prepare('INSERT INTO cargo (cargo_code, service_id, item_name, item_type, quantity, unit, weight, volume, value, dangerous_goods, special_instructions, loading_point, unloading_point, recipient_name, recipient_phone, sender_name, sender_phone, tracking_number, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        
        if (!$stmt) {
            send_json(['error' => 'Failed to prepare statement', 'details' => $mysqli->error], 500);
            exit;
        }
        
        $stmt->bind_param(
            'sissdsddbsssssssss',
            $cargo_code,
            (int)$body['service_id'],
            $body['item_name'],
            $body['item_type'],
            (float)$body['quantity'],
            $body['unit'],
            isset($body['weight']) ? (float)$body['weight'] : null,
            isset($body['volume']) ? (float)$body['volume'] : null,
            isset($body['value']) ? (float)$body['value'] : null,
            isset($body['dangerous_goods']) ? (bool)$body['dangerous_goods'] : false,
            $body['special_instructions'] ?? null,
            $body['loading_point'],
            $body['unloading_point'],
            $body['recipient_name'] ?? null,
            $body['recipient_phone'] ?? null,
            $body['sender_name'] ?? null,
            $body['sender_phone'] ?? null,
            $tracking_number,
            $body['notes'] ?? null
        );
        
        if (!$stmt->execute()) {
            send_json(['error' => 'Insert failed', 'details' => $stmt->error], 500);
            exit;
        }
        
        $cargo_id = $stmt->insert_id;
        get_cargo_after_write($cargo_id);
        break;

    case 'PUT':
        if ($id <= 0) {
            send_json(['error' => 'Missing cargo id'], 400);
        }
        $body = read_json_body();
        $errors = validate_cargo($body, $id);
        if ($errors) {
            send_json(['errors' => $errors], 422);
        }
        
        // Get current cargo
        $current = $mysqli->query("SELECT * FROM cargo WHERE id = $id")->fetch_assoc();
        if (!$current) {
            send_json(['error' => 'Cargo not found'], 404);
        }
        
        $stmt = $mysqli->prepare('UPDATE cargo SET item_name=?, item_type=?, quantity=?, unit=?, weight=?, volume=?, value=?, dangerous_goods=?, special_instructions=?, loading_point=?, unloading_point=?, recipient_name=?, recipient_phone=?, sender_name=?, sender_phone=?, status=?, notes=? WHERE id=?');
        $stmt->bind_param(
            'ssssdddrssssssssi',
            $body['item_name'] ?? $current['item_name'],
            $body['item_type'] ?? $current['item_type'],
            isset($body['quantity']) ? (float)$body['quantity'] : $current['quantity'],
            $body['unit'] ?? $current['unit'],
            isset($body['weight']) ? (float)$body['weight'] : $current['weight'],
            isset($body['volume']) ? (float)$body['volume'] : $current['volume'],
            isset($body['value']) ? (float)$body['value'] : $current['value'],
            isset($body['dangerous_goods']) ? (bool)$body['dangerous_goods'] : $current['dangerous_goods'],
            $body['special_instructions'] ?? $current['special_instructions'],
            $body['loading_point'] ?? $current['loading_point'],
            $body['unloading_point'] ?? $current['unloading_point'],
            $body['recipient_name'] ?? $current['recipient_name'],
            $body['recipient_phone'] ?? $current['recipient_phone'],
            $body['sender_name'] ?? $current['sender_name'],
            $body['sender_phone'] ?? $current['sender_phone'],
            $body['status'] ?? $current['status'],
            $body['notes'] ?? $current['notes'],
            $id
        );
        
        if (!$stmt->execute()) {
            send_json(['error' => 'Update failed', 'details' => $stmt->error], 500);
        }
        
        get_cargo_after_write($id);
        break;

    case 'DELETE':
        if ($id <= 0) {
            send_json(['error' => 'Missing cargo id'], 400);
        }
        
        // Check if cargo is in transit or delivered
        $cargo = $mysqli->query("SELECT status FROM cargo WHERE id = $id")->fetch_assoc();
        if (!$cargo) {
            send_json(['error' => 'Cargo not found'], 404);
        }
        
        if (in_array($cargo['status'], ['loaded', 'in_transit', 'delivered'])) {
            send_json(['error' => 'Cannot delete cargo that is loaded, in transit, or delivered'], 400);
        }
        
        $stmt = $mysqli->prepare('DELETE FROM cargo WHERE id = ?');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            send_json(['error' => 'Delete failed', 'details' => $stmt->error], 500);
        }
        
        send_json(['success' => true, 'message' => 'Cargo deleted successfully']);
        break;

    default:
        send_json(['error' => 'Method not allowed'], 405);
        break;
}

// Additional endpoints for cargo management
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $cargo_id = isset($_GET['cargo_id']) ? (int) $_GET['cargo_id'] : 0;
    
    switch ($action) {
        case 'track':
            if (isset($_GET['tracking_number'])) {
                track_cargo_by_number($_GET['tracking_number']);
            } elseif ($cargo_id > 0) {
                track_cargo_by_id($cargo_id);
            } else {
                send_json(['error' => 'Missing tracking number or cargo ID'], 400);
            }
            break;
            
        case 'update_status':
            if ($cargo_id <= 0) {
                send_json(['error' => 'Missing cargo ID'], 400);
                break;
            }
            $data = read_json_body();
            update_cargo_status($cargo_id, $data);
            break;
            
        case 'manifest':
            if ($service_id <= 0) {
                send_json(['error' => 'Missing service ID'], 400);
                break;
            }
            generate_cargo_manifest($service_id);
            break;
            
        case 'summary':
            get_cargo_summary();
            break;
    }
}

function validate_cargo(array $body, int $id = 0): array {
    $errors = [];
    
    // Required fields for new cargo
    if ($id === 0) {
        $required = ['service_id', 'item_name', 'item_type', 'quantity', 'unit', 'loading_point', 'unloading_point'];
        foreach ($required as $field) {
            if (!isset($body[$field]) || $body[$field] === '') {
                $errors[$field] = 'This field is required';
            }
        }
    }
    
    // Validate service exists
    if (isset($body['service_id']) && $body['service_id'] > 0) {
        global $mysqli;
        $stmt = $mysqli->prepare('SELECT id FROM services WHERE id = ?');
        $stmt->bind_param('i', $body['service_id']);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            $errors['service_id'] = 'Invalid service ID';
        }
    }
    
    // Validate status
    if (isset($body['status'])) {
        $valid_statuses = ['pending', 'loaded', 'in_transit', 'delivered', 'damaged', 'lost'];
        if (!in_array($body['status'], $valid_statuses)) {
            $errors['status'] = 'Invalid status value';
        }
    }
    
    // Validate numeric fields
    $numeric_fields = ['quantity', 'weight', 'volume', 'value'];
    foreach ($numeric_fields as $field) {
        if (isset($body[$field]) && (!is_numeric($body[$field]) || $body[$field] < 0)) {
            $errors[$field] = ucfirst($field) . ' must be a valid positive number';
        }
    }
    
    return $errors;
}

function get_cargo_after_write(int $id): void {
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT c.*, s.service_code, s.origin, s.destination FROM cargo c LEFT JOIN services s ON c.service_id = s.id WHERE c.id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    send_json($row, 201);
}

function track_cargo_by_number(string $tracking_number): void {
    global $mysqli;
    
    $stmt = $mysqli->prepare('SELECT c.*, s.service_code, s.status as service_status, s.origin, s.destination, s.current_location_lat, s.current_location_lng, s.last_location_update FROM cargo c LEFT JOIN services s ON c.service_id = s.id WHERE c.tracking_number = ?');
    $stmt->bind_param('s', $tracking_number);
    $stmt->execute();
    $cargo = $stmt->get_result()->fetch_assoc();
    
    if (!$cargo) {
        send_json(['error' => 'Cargo not found with tracking number'], 404);
        return;
    }
    
    // Get cargo history from service history
    $history_stmt = $mysqli->prepare('SELECT * FROM service_history WHERE service_id = ? AND notes LIKE ? ORDER BY created_at ASC');
    $search_pattern = "%{$cargo['item_name']}%";
    $history_stmt->bind_param('is', $cargo['service_id'], $search_pattern);
    $history_stmt->execute();
    $history_result = $history_stmt->get_result();
    $history = [];
    while ($row = $history_result->fetch_assoc()) {
        $history[] = $row;
    }
    
    $tracking_info = [
        'cargo' => $cargo,
        'current_location' => [
            'lat' => $cargo['current_location_lat'],
            'lng' => $cargo['current_location_lng'],
            'last_update' => $cargo['last_location_update']
        ],
        'history' => $history,
        'estimated_delivery' => null // Could be calculated based on service schedule
    ];
    
    send_json($tracking_info);
}

function track_cargo_by_id(int $cargo_id): void {
    global $mysqli;
    
    $cargo = $mysqli->query("SELECT tracking_number FROM cargo WHERE id = $cargo_id")->fetch_assoc();
    if (!$cargo) {
        send_json(['error' => 'Cargo not found'], 404);
        return;
    }
    
    track_cargo_by_number($cargo['tracking_number']);
}

function update_cargo_status(int $cargo_id, array $data): void {
    global $mysqli;
    
    $cargo = $mysqli->query("SELECT * FROM cargo WHERE id = $cargo_id")->fetch_assoc();
    if (!$cargo) {
        send_json(['error' => 'Cargo not found'], 404);
        return;
    }
    
    if (!isset($data['status'])) {
        send_json(['error' => 'Status is required'], 400);
        return;
    }
    
    $valid_statuses = ['pending', 'loaded', 'in_transit', 'delivered', 'damaged', 'lost'];
    if (!in_array($data['status'], $valid_statuses)) {
        send_json(['error' => 'Invalid status'], 400);
        return;
    }
    
    // Update timestamps based on status
    $timestamp_field = null;
    switch ($data['status']) {
        case 'loaded':
            $timestamp_field = 'loaded_at';
            break;
        case 'delivered':
            $timestamp_field = 'unloaded_at';
            break;
    }
    
    if ($timestamp_field) {
        $stmt = $mysqli->prepare("UPDATE cargo SET status = ?, $timestamp_field = NOW() WHERE id = ?");
        $stmt->bind_param('si', $data['status'], $cargo_id);
    } else {
        $stmt = $mysqli->prepare('UPDATE cargo SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $data['status'], $cargo_id);
    }
    
    if ($stmt->execute()) {
        // Log the status change in service history
        $notes = "Cargo {$cargo['item_name']} status changed to {$data['status']}";
        $history_stmt = $mysqli->prepare('INSERT INTO service_history (service_id, action, previous_status, new_status, notes) VALUES (?, ?, ?, ?, ?)');
        $history_stmt->bind_param('issss', $cargo['service_id'], 'cargo_status_update', $cargo['status'], $data['status'], $notes);
        $history_stmt->execute();
        
        send_json(['success' => true, 'message' => 'Cargo status updated successfully']);
    } else {
        send_json(['error' => 'Failed to update cargo status'], 500);
    }
}

function generate_cargo_manifest(int $service_id): void {
    global $mysqli;
    
    // Get service details
    $service = $mysqli->query("SELECT * FROM services WHERE id = $service_id")->fetch_assoc();
    if (!$service) {
        send_json(['error' => 'Service not found'], 404);
        return;
    }
    
    // Get all cargo for this service
    $cargo_result = $mysqli->query("SELECT * FROM cargo WHERE service_id = $service_id ORDER BY created_at ASC");
    $cargo_list = [];
    $total_weight = 0;
    $total_volume = 0;
    $total_value = 0;
    $dangerous_items = 0;
    
    while ($row = $cargo_result->fetch_assoc()) {
        $cargo_list[] = $row;
        $total_weight += $row['weight'] ?? 0;
        $total_volume += $row['volume'] ?? 0;
        $total_value += $row['value'] ?? 0;
        if ($row['dangerous_goods']) {
            $dangerous_items++;
        }
    }
    
    $manifest = [
        'service' => $service,
        'manifest_date' => date('Y-m-d H:i:s'),
        'cargo_count' => count($cargo_list),
        'total_weight' => $total_weight,
        'total_volume' => $total_volume,
        'total_value' => $total_value,
        'dangerous_items_count' => $dangerous_items,
        'cargo_list' => $cargo_list
    ];
    
    send_json($manifest);
}

function get_cargo_summary(): void {
    global $mysqli;
    
    $summary = [];
    
    // Status breakdown
    $status_result = $mysqli->query("SELECT status, COUNT(*) as count FROM cargo GROUP BY status");
    $status_breakdown = [];
    while ($row = $status_result->fetch_assoc()) {
        $status_breakdown[$row['status']] = $row['count'];
    }
    $summary['status_breakdown'] = $status_breakdown;
    
    // Today's cargo
    $summary['today_added'] = $mysqli->query("SELECT COUNT(*) as count FROM cargo WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
    $summary['today_delivered'] = $mysqli->query("SELECT COUNT(*) as count FROM cargo WHERE DATE(unloaded_at) = CURDATE()")->fetch_assoc()['count'];
    
    // Value statistics
    $value_stats = $mysqli->query("SELECT SUM(value) as total_value, AVG(value) as avg_value FROM cargo WHERE value IS NOT NULL")->fetch_assoc();
    $summary['total_cargo_value'] = $value_stats['total_value'] ?? 0;
    $summary['average_cargo_value'] = $value_stats['avg_value'] ?? 0;
    
    // Weight statistics
    $weight_stats = $mysqli->query("SELECT SUM(weight) as total_weight, AVG(weight) as avg_weight FROM cargo WHERE weight IS NOT NULL")->fetch_assoc();
    $summary['total_weight'] = $weight_stats['total_weight'] ?? 0;
    $summary['average_weight'] = $weight_stats['avg_weight'] ?? 0;
    
    // Dangerous goods count
    $summary['dangerous_goods_count'] = $mysqli->query("SELECT COUNT(*) as count FROM cargo WHERE dangerous_goods = 1")->fetch_assoc()['count'];
    
    send_json($summary);
}

?>
