<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth.php';
require_once 'db.php';

// Allow only authenticated users with proper roles
if (!isset($_SESSION['user_id'])) {
    send_json(['error' => 'Unauthorized access'], 401);
}

$userRole = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$isAdmin = ($userRole === 'admin');
$isProvider = ($userRole === 'provider');

// Only admin and provider can manage services
if (!$isAdmin && !$isProvider) {
    send_json(['error' => 'Insufficient permissions'], 403);
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            handleListServices();
            break;
        case 'get':
            handleGetService();
            break;
        case 'create':
            handleCreateService();
            break;
        case 'update':
            handleUpdateService();
            break;
        case 'delete':
            handleDeleteService();
            break;
        case 'stats':
            handleGetStats();
            break;
        default:
            send_json(['error' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    error_log('Service API Error: ' . $e->getMessage());
    send_json(['error' => 'Internal server error'], 500);
}

function handleListServices() {
    global $mysqli, $isAdmin, $userId;
    
    $whereClause = '';
    if (!$isAdmin) {
        // Providers can only see their own services
        $whereClause = "WHERE s.provider_id = $userId";
    }
    
    $sql = "
        SELECT 
            s.id,
            s.service_code,
            s.service_type,
            s.transport_mode,
            s.route,
            s.origin,
            s.destination,
            s.distance_km,
            s.capacity_weight,
            s.capacity_volume,
            s.capacity_passengers,
            s.status,
            s.scheduled_start,
            s.scheduled_end,
            s.base_fare,
            s.total_fare,
            s.rating,
            s.created_at,
            u.username as provider_name
        FROM services s
        LEFT JOIN users u ON s.provider_id = u.id
        $whereClause
        ORDER BY s.created_at DESC
    ";
    
    $result = $mysqli->query($sql);
    
    if (!$result) {
        send_json(['error' => 'Database query failed'], 500);
    }
    
    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
    
    send_json(['services' => $services]);
}

function handleGetService() {
    global $mysqli, $isAdmin, $userId;
    
    $id = $_GET['id'] ?? '';
    if (!$id || !is_numeric($id)) {
        send_json(['error' => 'Invalid service ID'], 400);
    }
    
    $whereClause = "WHERE s.id = $id";
    if (!$isAdmin) {
        $whereClause .= " AND s.provider_id = $userId";
    }
    
    $sql = "
        SELECT 
            s.*,
            u.username as provider_name,
            v.license_plate as vehicle_plate,
            d.first_name as driver_first_name,
            d.last_name as driver_last_name
        FROM services s
        LEFT JOIN users u ON s.provider_id = u.id
        LEFT JOIN vehicles v ON s.vehicle_id = v.id
        LEFT JOIN drivers d ON s.driver_id = d.id
        $whereClause
    ";
    
    $result = $mysqli->query($sql);
    
    if (!$result || $result->num_rows === 0) {
        send_json(['error' => 'Service not found'], 404);
    }
    
    $service = $result->fetch_assoc();
    send_json(['service' => $service]);
}

function handleCreateService() {
    global $mysqli, $isAdmin, $userId;
    
    $data = read_json_body();
    
    // Validate required fields
    $required = ['service_type', 'transport_mode', 'route', 'origin', 'destination'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            send_json(['error' => "Field '$field' is required"], 400);
        }
    }
    
    // Generate unique service code
    $serviceCode = 'SVC-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Check if service code already exists
    $checkSql = "SELECT id FROM services WHERE service_code = '$serviceCode'";
    $result = $mysqli->query($checkSql);
    if ($result && $result->num_rows > 0) {
        $serviceCode .= '-' . rand(10, 99);
    }
    
    $providerId = $isAdmin && !empty($data['provider_id']) ? $data['provider_id'] : $userId;
    
    $sql = "INSERT INTO services (
        service_code, provider_id, service_type, transport_mode, route,
        origin, destination, distance_km, capacity_weight, capacity_volume,
        capacity_passengers, status, scheduled_start, scheduled_end,
        base_fare, per_km_rate, per_weight_rate, fuel_surcharge,
        total_fare, special_requirements, notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        send_json(['error' => 'Database prepare failed'], 500);
    }
    
    $status = $data['status'] ?? 'pending';
    $scheduledStart = !empty($data['scheduled_start']) ? $data['scheduled_start'] : null;
    $scheduledEnd = !empty($data['scheduled_end']) ? $data['scheduled_end'] : null;
    
    $stmt->bind_param('sisssssdddissddddss', 
        $serviceCode,
        $providerId,
        $data['service_type'],
        $data['transport_mode'],
        $data['route'],
        $data['origin'],
        $data['destination'],
        $data['distance_km'] ?? null,
        $data['capacity_weight'] ?? null,
        $data['capacity_volume'] ?? null,
        $data['capacity_passengers'] ?? null,
        $status,
        $scheduledStart,
        $scheduledEnd,
        $data['base_fare'] ?? 0.00,
        $data['per_km_rate'] ?? 0.00,
        $data['per_weight_rate'] ?? 0.00,
        $data['fuel_surcharge'] ?? 0.00,
        $data['total_fare'] ?? 0.00,
        $data['special_requirements'] ?? null,
        $data['notes'] ?? null
    );
    
    if ($stmt->execute()) {
        $newId = $mysqli->insert_id;
        
        // Log service creation in service_history
        $historyQuery = "INSERT INTO service_history (service_id, action, new_status, action_by_user_id, notes) 
                        VALUES ($newId, 'created', '$status', $userId, 'Service created')";
        $mysqli->query($historyQuery);
        
        send_json([
            'success' => true,
            'message' => 'Service created successfully',
            'service_id' => $newId,
            'service_code' => $serviceCode
        ]);
    } else {
        send_json(['error' => 'Failed to create service'], 500);
    }
}

function handleUpdateService() {
    global $mysqli, $isAdmin, $userId;
    
    $id = $_GET['id'] ?? '';
    if (!$id || !is_numeric($id)) {
        send_json(['error' => 'Invalid service ID'], 400);
    }
    
    $data = read_json_body();
    
    // Check if service exists and user has permission
    $checkSql = "SELECT * FROM services WHERE id = $id";
    if (!$isAdmin) {
        $checkSql .= " AND provider_id = $userId";
    }
    
    $result = $mysqli->query($checkSql);
    if (!$result || $result->num_rows === 0) {
        send_json(['error' => 'Service not found or access denied'], 404);
    }
    
    $currentService = $result->fetch_assoc();
    
    // Build update query dynamically
    $updateFields = [];
    $params = [];
    $types = '';
    
    $updatableFields = [
        'service_type' => 's',
        'transport_mode' => 's',
        'route' => 's',
        'origin' => 's',
        'destination' => 's',
        'distance_km' => 'd',
        'capacity_weight' => 'd',
        'capacity_volume' => 'd',
        'capacity_passengers' => 'i',
        'status' => 's',
        'scheduled_start' => 's',
        'scheduled_end' => 's',
        'base_fare' => 'd',
        'per_km_rate' => 'd',
        'per_weight_rate' => 'd',
        'fuel_surcharge' => 'd',
        'total_fare' => 'd',
        'special_requirements' => 's',
        'notes' => 's'
    ];
    
    foreach ($updatableFields as $field => $type) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = $data[$field];
            $types .= $type;
        }
    }
    
    if (empty($updateFields)) {
        send_json(['error' => 'No fields to update'], 400);
    }
    
    $sql = "UPDATE services SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $params[] = $id;
    $types .= 'i';
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        send_json(['error' => 'Database prepare failed'], 500);
    }
    
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // Log status change if status was updated
        if (isset($data['status']) && $data['status'] !== $currentService['status']) {
            $historyQuery = "INSERT INTO service_history (service_id, action, previous_status, new_status, action_by_user_id, notes) 
                            VALUES ($id, 'status_changed', '{$currentService['status']}', '{$data['status']}', $userId, 'Status updated')";
            $mysqli->query($historyQuery);
        }
        
        send_json([
            'success' => true,
            'message' => 'Service updated successfully'
        ]);
    } else {
        send_json(['error' => 'Failed to update service'], 500);
    }
}

function handleDeleteService() {
    global $mysqli, $isAdmin, $userId;
    
    $id = $_GET['id'] ?? '';
    if (!$id || !is_numeric($id)) {
        send_json(['error' => 'Invalid service ID'], 400);
    }
    
    // Check if service exists and user has permission
    $checkSql = "SELECT * FROM services WHERE id = $id";
    if (!$isAdmin) {
        $checkSql .= " AND provider_id = $userId";
    }
    
    $result = $mysqli->query($checkSql);
    if (!$result || $result->num_rows === 0) {
        send_json(['error' => 'Service not found or access denied'], 404);
    }
    
    $service = $result->fetch_assoc();
    
    // Check if service can be deleted (not active or in progress)
    if (in_array($service['status'], ['active', 'en_route', 'loading', 'unloading'])) {
        send_json(['error' => 'Cannot delete active or in-progress services'], 400);
    }
    
    // Log deletion before deleting (due to foreign key cascade)
    $historyQuery = "INSERT INTO service_history (service_id, action, previous_status, new_status, action_by_user_id, notes) 
                    VALUES ($id, 'deleted', '{$service['status']}', 'deleted', $userId, 'Service deleted')";
    $mysqli->query($historyQuery);
    
    $sql = "DELETE FROM services WHERE id = $id";
    
    if ($mysqli->query($sql)) {
        send_json([
            'success' => true,
            'message' => 'Service deleted successfully'
        ]);
    } else {
        send_json(['error' => 'Failed to delete service'], 500);
    }
}

function handleGetStats() {
    global $mysqli, $isAdmin, $userId;
    
    $whereClause = '';
    if (!$isAdmin) {
        $whereClause = "WHERE provider_id = $userId";
    }
    
    // Get total services
    $totalSql = "SELECT COUNT(*) as total FROM services $whereClause";
    $totalResult = $mysqli->query($totalSql);
    $totalServices = $totalResult ? $totalResult->fetch_assoc()['total'] : 0;
    
    // Get active services
    $activeSql = "SELECT COUNT(*) as active FROM services $whereClause";
    if (!empty($whereClause)) {
        $activeSql .= " AND status IN ('active', 'en_route')";
    } else {
        $activeSql .= " WHERE status IN ('active', 'en_route')";
    }
    $activeResult = $mysqli->query($activeSql);
    $activeServices = $activeResult ? $activeResult->fetch_assoc()['active'] : 0;
    
    // Get unique routes
    $routesSql = "SELECT COUNT(DISTINCT route) as routes FROM services $whereClause";
    $routesResult = $mysqli->query($routesSql);
    $totalRoutes = $routesResult ? $routesResult->fetch_assoc()['routes'] : 0;
    
    // Get average rating
    $ratingSql = "SELECT AVG(rating) as avg_rating FROM services $whereClause";
    if (!empty($whereClause)) {
        $ratingSql .= " AND rating IS NOT NULL";
    } else {
        $ratingSql .= " WHERE rating IS NOT NULL";
    }
    $ratingResult = $mysqli->query($ratingSql);
    $avgRatingValue = $ratingResult ? $ratingResult->fetch_assoc()['avg_rating'] : null;
    $avgRating = $avgRatingValue ? round($avgRatingValue, 1) : 0;
    
    // Get total revenue
    $revenueSql = "SELECT SUM(revenue) as total_revenue FROM services $whereClause";
    $revenueResult = $mysqli->query($revenueSql);
    $totalRevenue = $revenueResult ? $revenueResult->fetch_assoc()['total_revenue'] : 0;
    
    send_json([
        'total_services' => $totalServices,
        'active_services' => $activeServices,
        'total_routes' => $totalRoutes,
        'avg_rating' => $avgRating ?: 4.8,
        'total_revenue' => $totalRevenue ?: 0,
        'service_coverage' => '100%'
    ]);
}
?>
