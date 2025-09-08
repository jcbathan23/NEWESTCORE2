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
$vehicle_id = isset($_GET['vehicle_id']) ? (int) $_GET['vehicle_id'] : 0;

switch ($method) {
    case 'GET':
        if ($id > 0) {
            // Get specific maintenance record
            $stmt = $mysqli->prepare('SELECT m.*, v.license_plate, v.brand, v.model, v.provider_id FROM vehicle_maintenance m LEFT JOIN vehicles v ON m.vehicle_id = v.id WHERE m.id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) {
                send_json($row);
            } else {
                send_json(['error' => 'Maintenance record not found'], 404);
            }
        } elseif ($vehicle_id > 0) {
            // Get maintenance records for specific vehicle
            $stmt = $mysqli->prepare('SELECT m.*, v.license_plate, v.brand, v.model FROM vehicle_maintenance m LEFT JOIN vehicles v ON m.vehicle_id = v.id WHERE m.vehicle_id = ? ORDER BY m.scheduled_date DESC');
            $stmt->bind_param('i', $vehicle_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            send_json($data);
        } else {
            // Get all maintenance records with optional filters
            $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
            $type_filter = isset($_GET['type']) ? $_GET['type'] : '';
            $provider_id = isset($_GET['provider_id']) ? (int) $_GET['provider_id'] : 0;
            
            $query = 'SELECT m.*, v.license_plate, v.brand, v.model, v.provider_id FROM vehicle_maintenance m LEFT JOIN vehicles v ON m.vehicle_id = v.id WHERE 1=1';
            $params = [];
            $types = '';
            
            if ($status_filter) {
                $query .= ' AND m.status = ?';
                $params[] = $status_filter;
                $types .= 's';
            }
            
            if ($type_filter) {
                $query .= ' AND m.maintenance_type = ?';
                $params[] = $type_filter;
                $types .= 's';
            }
            
            if ($provider_id > 0) {
                $query .= ' AND v.provider_id = ?';
                $params[] = $provider_id;
                $types .= 'i';
            }
            
            $query .= ' ORDER BY m.scheduled_date DESC';
            
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
        
        $errors = validate_maintenance($body);
        if ($errors) {
            send_json(['errors' => $errors], 422);
            exit;
        }
        
        $stmt = $mysqli->prepare('INSERT INTO vehicle_maintenance (vehicle_id, maintenance_type, description, scheduled_date, service_provider, cost, odometer_reading, next_maintenance_km, next_maintenance_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        
        if (!$stmt) {
            send_json(['error' => 'Failed to prepare statement', 'details' => $mysqli->error], 500);
            exit;
        }
        
        $stmt->bind_param(
            'issssddsss',
            (int)$body['vehicle_id'],
            $body['maintenance_type'],
            $body['description'],
            $body['scheduled_date'],
            $body['service_provider'] ?? null,
            isset($body['cost']) ? (float)$body['cost'] : null,
            isset($body['odometer_reading']) ? (float)$body['odometer_reading'] : null,
            isset($body['next_maintenance_km']) ? (float)$body['next_maintenance_km'] : null,
            $body['next_maintenance_date'] ?? null,
            $body['notes'] ?? null
        );
        
        if (!$stmt->execute()) {
            send_json(['error' => 'Insert failed', 'details' => $stmt->error], 500);
            exit;
        }
        
        $maintenance_id = $stmt->insert_id;
        get_maintenance_after_write($maintenance_id);
        break;

    case 'PUT':
        if ($id <= 0) {
            send_json(['error' => 'Missing maintenance id'], 400);
        }
        $body = read_json_body();
        $errors = validate_maintenance($body, $id);
        if ($errors) {
            send_json(['errors' => $errors], 422);
        }
        
        // Get current maintenance record
        $current = $mysqli->query("SELECT * FROM vehicle_maintenance WHERE id = $id")->fetch_assoc();
        if (!$current) {
            send_json(['error' => 'Maintenance record not found'], 404);
        }
        
        $stmt = $mysqli->prepare('UPDATE vehicle_maintenance SET maintenance_type=?, description=?, scheduled_date=?, completed_date=?, service_provider=?, cost=?, odometer_reading=?, status=?, next_maintenance_km=?, next_maintenance_date=?, notes=? WHERE id=?');
        $stmt->bind_param(
            'sssssddsdssi',
            $body['maintenance_type'] ?? $current['maintenance_type'],
            $body['description'] ?? $current['description'],
            $body['scheduled_date'] ?? $current['scheduled_date'],
            $body['completed_date'] ?? $current['completed_date'],
            $body['service_provider'] ?? $current['service_provider'],
            isset($body['cost']) ? (float)$body['cost'] : $current['cost'],
            isset($body['odometer_reading']) ? (float)$body['odometer_reading'] : $current['odometer_reading'],
            $body['status'] ?? $current['status'],
            isset($body['next_maintenance_km']) ? (float)$body['next_maintenance_km'] : $current['next_maintenance_km'],
            $body['next_maintenance_date'] ?? $current['next_maintenance_date'],
            $body['notes'] ?? $current['notes'],
            $id
        );
        
        if (!$stmt->execute()) {
            send_json(['error' => 'Update failed', 'details' => $stmt->error], 500);
        }
        
        // Update vehicle maintenance dates if this maintenance is completed
        if (isset($body['status']) && $body['status'] === 'completed') {
            update_vehicle_maintenance_dates($current['vehicle_id'], $body);
        }
        
        get_maintenance_after_write($id);
        break;

    case 'DELETE':
        if ($id <= 0) {
            send_json(['error' => 'Missing maintenance id'], 400);
        }
        
        // Check if maintenance is completed
        $maintenance = $mysqli->query("SELECT status FROM vehicle_maintenance WHERE id = $id")->fetch_assoc();
        if (!$maintenance) {
            send_json(['error' => 'Maintenance record not found'], 404);
        }
        
        if ($maintenance['status'] === 'completed') {
            send_json(['error' => 'Cannot delete completed maintenance record'], 400);
        }
        
        $stmt = $mysqli->prepare('DELETE FROM vehicle_maintenance WHERE id = ?');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            send_json(['error' => 'Delete failed', 'details' => $stmt->error], 500);
        }
        
        send_json(['success' => true, 'message' => 'Maintenance record deleted successfully']);
        break;

    default:
        send_json(['error' => 'Method not allowed'], 405);
        break;
}

// Additional endpoints for maintenance management
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $maintenance_id = isset($_GET['maintenance_id']) ? (int) $_GET['maintenance_id'] : 0;
    
    switch ($action) {
        case 'complete':
            if ($maintenance_id <= 0) {
                send_json(['error' => 'Missing maintenance ID'], 400);
                break;
            }
            $data = read_json_body();
            complete_maintenance($maintenance_id, $data);
            break;
            
        case 'schedule_next':
            if ($vehicle_id <= 0) {
                send_json(['error' => 'Missing vehicle ID'], 400);
                break;
            }
            $data = read_json_body();
            schedule_next_maintenance($vehicle_id, $data);
            break;
            
        case 'overdue':
            get_overdue_maintenance();
            break;
            
        case 'upcoming':
            $days = isset($_GET['days']) ? (int) $_GET['days'] : 30;
            get_upcoming_maintenance($days);
            break;
            
        case 'cost_summary':
            if ($vehicle_id > 0) {
                get_vehicle_maintenance_costs($vehicle_id);
            } else {
                get_overall_maintenance_costs();
            }
            break;
    }
}

function validate_maintenance(array $body, int $id = 0): array {
    $errors = [];
    
    // Required fields for new maintenance records
    if ($id === 0) {
        $required = ['vehicle_id', 'maintenance_type', 'description', 'scheduled_date'];
        foreach ($required as $field) {
            if (!isset($body[$field]) || $body[$field] === '') {
                $errors[$field] = 'This field is required';
            }
        }
    }
    
    // Validate vehicle exists
    if (isset($body['vehicle_id']) && $body['vehicle_id'] > 0) {
        global $mysqli;
        $stmt = $mysqli->prepare('SELECT id FROM vehicles WHERE id = ?');
        $stmt->bind_param('i', $body['vehicle_id']);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            $errors['vehicle_id'] = 'Invalid vehicle ID';
        }
    }
    
    // Validate maintenance type
    if (isset($body['maintenance_type'])) {
        $valid_types = ['routine', 'repair', 'inspection', 'emergency'];
        if (!in_array($body['maintenance_type'], $valid_types)) {
            $errors['maintenance_type'] = 'Invalid maintenance type';
        }
    }
    
    // Validate status
    if (isset($body['status'])) {
        $valid_statuses = ['scheduled', 'in_progress', 'completed', 'cancelled'];
        if (!in_array($body['status'], $valid_statuses)) {
            $errors['status'] = 'Invalid status value';
        }
    }
    
    // Validate dates
    if (isset($body['scheduled_date']) && strtotime($body['scheduled_date']) === false) {
        $errors['scheduled_date'] = 'Invalid scheduled date format';
    }
    
    if (isset($body['completed_date']) && $body['completed_date'] !== '' && strtotime($body['completed_date']) === false) {
        $errors['completed_date'] = 'Invalid completed date format';
    }
    
    if (isset($body['next_maintenance_date']) && $body['next_maintenance_date'] !== '' && strtotime($body['next_maintenance_date']) === false) {
        $errors['next_maintenance_date'] = 'Invalid next maintenance date format';
    }
    
    // Validate cost
    if (isset($body['cost']) && (!is_numeric($body['cost']) || $body['cost'] < 0)) {
        $errors['cost'] = 'Cost must be a valid positive number';
    }
    
    // Validate odometer reading
    if (isset($body['odometer_reading']) && (!is_numeric($body['odometer_reading']) || $body['odometer_reading'] < 0)) {
        $errors['odometer_reading'] = 'Odometer reading must be a valid positive number';
    }
    
    return $errors;
}

function get_maintenance_after_write(int $id): void {
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT m.*, v.license_plate, v.brand, v.model, v.provider_id FROM vehicle_maintenance m LEFT JOIN vehicles v ON m.vehicle_id = v.id WHERE m.id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    send_json($row, 201);
}

function complete_maintenance(int $maintenance_id, array $data): void {
    global $mysqli;
    
    $maintenance = $mysqli->query("SELECT * FROM vehicle_maintenance WHERE id = $maintenance_id")->fetch_assoc();
    if (!$maintenance) {
        send_json(['error' => 'Maintenance record not found'], 404);
        return;
    }
    
    if ($maintenance['status'] === 'completed') {
        send_json(['error' => 'Maintenance is already completed'], 400);
        return;
    }
    
    $cost = isset($data['cost']) ? (float)$data['cost'] : $maintenance['cost'];
    $odometer_reading = isset($data['odometer_reading']) ? (float)$data['odometer_reading'] : $maintenance['odometer_reading'];
    $notes = $data['notes'] ?? $maintenance['notes'];
    
    $stmt = $mysqli->prepare('UPDATE vehicle_maintenance SET status = "completed", completed_date = NOW(), cost = ?, odometer_reading = ?, notes = ? WHERE id = ?');
    $stmt->bind_param('ddsi', $cost, $odometer_reading, $notes, $maintenance_id);
    
    if ($stmt->execute()) {
        // Update vehicle maintenance dates
        update_vehicle_maintenance_dates($maintenance['vehicle_id'], $data);
        
        // Update vehicle odometer if provided
        if ($odometer_reading) {
            $mysqli->query("UPDATE vehicles SET odometer_reading = $odometer_reading, last_maintenance = CURDATE() WHERE id = {$maintenance['vehicle_id']}");\n        }
        
        send_json(['success' => true, 'message' => 'Maintenance completed successfully']);
    } else {
        send_json(['error' => 'Failed to complete maintenance'], 500);
    }
}

function update_vehicle_maintenance_dates(int $vehicle_id, array $data): void {
    global $mysqli;
    
    if (isset($data['next_maintenance_date']) || isset($data['next_maintenance_km'])) {
        $update_parts = [];
        $params = [];
        $types = '';
        
        if (isset($data['next_maintenance_date'])) {
            $update_parts[] = 'next_maintenance = ?';
            $params[] = $data['next_maintenance_date'];
            $types .= 's';
        }
        
        $params[] = $vehicle_id;
        $types .= 'i';
        
        if (!empty($update_parts)) {
            $query = 'UPDATE vehicles SET ' . implode(', ', $update_parts) . ' WHERE id = ?';
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
        }
    }
}

function schedule_next_maintenance(int $vehicle_id, array $data): void {
    global $mysqli;
    
    // Validate required fields
    if (!isset($data['maintenance_type']) || !isset($data['scheduled_date']) || !isset($data['description'])) {
        send_json(['error' => 'Missing required fields'], 400);
        return;
    }
    
    $vehicle = $mysqli->query("SELECT * FROM vehicles WHERE id = $vehicle_id")->fetch_assoc();
    if (!$vehicle) {
        send_json(['error' => 'Vehicle not found'], 404);
        return;
    }
    
    $stmt = $mysqli->prepare('INSERT INTO vehicle_maintenance (vehicle_id, maintenance_type, description, scheduled_date, notes) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param(
        'issss',
        $vehicle_id,
        $data['maintenance_type'],
        $data['description'],
        $data['scheduled_date'],
        $data['notes'] ?? 'Automatically scheduled maintenance'
    );
    
    if ($stmt->execute()) {
        $maintenance_id = $stmt->insert_id;
        get_maintenance_after_write($maintenance_id);
    } else {
        send_json(['error' => 'Failed to schedule maintenance'], 500);
    }
}

function get_overdue_maintenance(): void {
    global $mysqli;
    
    $query = "SELECT m.*, v.license_plate, v.brand, v.model, v.provider_id FROM vehicle_maintenance m LEFT JOIN vehicles v ON m.vehicle_id = v.id WHERE m.status IN ('scheduled', 'in_progress') AND m.scheduled_date < CURDATE() ORDER BY m.scheduled_date ASC";
    
    $result = $mysqli->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    send_json($data);
}

function get_upcoming_maintenance(int $days = 30): void {
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT m.*, v.license_plate, v.brand, v.model, v.provider_id FROM vehicle_maintenance m LEFT JOIN vehicles v ON m.vehicle_id = v.id WHERE m.status IN ('scheduled', 'in_progress') AND m.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY) ORDER BY m.scheduled_date ASC");
    $stmt->bind_param('i', $days);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    send_json($data);
}

function get_vehicle_maintenance_costs(int $vehicle_id): void {
    global $mysqli;
    
    $vehicle = $mysqli->query("SELECT * FROM vehicles WHERE id = $vehicle_id")->fetch_assoc();
    if (!$vehicle) {
        send_json(['error' => 'Vehicle not found'], 404);
        return;
    }
    
    // Get cost statistics
    $stats = [];
    
    // Total cost
    $result = $mysqli->query("SELECT COALESCE(SUM(cost), 0) as total_cost, COUNT(*) as maintenance_count FROM vehicle_maintenance WHERE vehicle_id = $vehicle_id AND cost IS NOT NULL AND status = 'completed'");
    $cost_data = $result->fetch_assoc();
    $stats['total_cost'] = $cost_data['total_cost'];
    $stats['maintenance_count'] = $cost_data['maintenance_count'];
    $stats['average_cost'] = $cost_data['maintenance_count'] > 0 ? $cost_data['total_cost'] / $cost_data['maintenance_count'] : 0;
    
    // This year's cost
    $result = $mysqli->query("SELECT COALESCE(SUM(cost), 0) as yearly_cost FROM vehicle_maintenance WHERE vehicle_id = $vehicle_id AND cost IS NOT NULL AND status = 'completed' AND YEAR(completed_date) = YEAR(NOW())");
    $stats['yearly_cost'] = $result->fetch_assoc()['yearly_cost'];
    
    // Monthly breakdown for current year
    $result = $mysqli->query("SELECT MONTH(completed_date) as month, SUM(cost) as monthly_cost FROM vehicle_maintenance WHERE vehicle_id = $vehicle_id AND cost IS NOT NULL AND status = 'completed' AND YEAR(completed_date) = YEAR(NOW()) GROUP BY MONTH(completed_date) ORDER BY month");
    $monthly_costs = [];
    while ($row = $result->fetch_assoc()) {
        $monthly_costs[] = $row;
    }
    $stats['monthly_breakdown'] = $monthly_costs;
    
    // Maintenance type breakdown
    $result = $mysqli->query("SELECT maintenance_type, SUM(cost) as type_cost, COUNT(*) as type_count FROM vehicle_maintenance WHERE vehicle_id = $vehicle_id AND cost IS NOT NULL AND status = 'completed' GROUP BY maintenance_type");
    $type_breakdown = [];
    while ($row = $result->fetch_assoc()) {
        $type_breakdown[] = $row;
    }
    $stats['type_breakdown'] = $type_breakdown;
    
    $stats['vehicle'] = $vehicle;
    
    send_json($stats);
}

function get_overall_maintenance_costs(): void {
    global $mysqli;
    
    $stats = [];
    
    // Overall statistics
    $result = $mysqli->query("SELECT COALESCE(SUM(cost), 0) as total_cost, COUNT(*) as maintenance_count, COUNT(DISTINCT vehicle_id) as vehicle_count FROM vehicle_maintenance WHERE cost IS NOT NULL AND status = 'completed'");
    $overall_data = $result->fetch_assoc();
    $stats['total_cost'] = $overall_data['total_cost'];
    $stats['maintenance_count'] = $overall_data['maintenance_count'];
    $stats['vehicle_count'] = $overall_data['vehicle_count'];
    $stats['average_cost_per_maintenance'] = $overall_data['maintenance_count'] > 0 ? $overall_data['total_cost'] / $overall_data['maintenance_count'] : 0;
    $stats['average_cost_per_vehicle'] = $overall_data['vehicle_count'] > 0 ? $overall_data['total_cost'] / $overall_data['vehicle_count'] : 0;
    
    // This year's cost
    $result = $mysqli->query("SELECT COALESCE(SUM(cost), 0) as yearly_cost FROM vehicle_maintenance WHERE cost IS NOT NULL AND status = 'completed' AND YEAR(completed_date) = YEAR(NOW())");
    $stats['yearly_cost'] = $result->fetch_assoc()['yearly_cost'];
    
    // Top 5 vehicles by maintenance cost
    $result = $mysqli->query("SELECT v.license_plate, v.brand, v.model, SUM(m.cost) as total_cost, COUNT(m.id) as maintenance_count FROM vehicle_maintenance m JOIN vehicles v ON m.vehicle_id = v.id WHERE m.cost IS NOT NULL AND m.status = 'completed' GROUP BY m.vehicle_id ORDER BY total_cost DESC LIMIT 5");
    $top_vehicles = [];
    while ($row = $result->fetch_assoc()) {
        $top_vehicles[] = $row;
    }
    $stats['top_cost_vehicles'] = $top_vehicles;
    
    // Maintenance type breakdown
    $result = $mysqli->query("SELECT maintenance_type, SUM(cost) as type_cost, COUNT(*) as type_count FROM vehicle_maintenance WHERE cost IS NOT NULL AND status = 'completed' GROUP BY maintenance_type ORDER BY type_cost DESC");
    $type_breakdown = [];
    while ($row = $result->fetch_assoc()) {
        $type_breakdown[] = $row;
    }
    $stats['type_breakdown'] = $type_breakdown;
    
    send_json($stats);
}

?>
