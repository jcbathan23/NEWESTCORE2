<?php
session_start();
require_once 'auth.php';
require_once 'db.php';

// Allow only authenticated admin users
if (!isset($_SESSION['user_id'])) {
    send_json(['error' => 'Unauthorized access'], 401);
}

$userRole = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$isAdmin = ($userRole === 'admin');

// Only admin can manage providers
if (!$isAdmin) {
    send_json(['error' => 'Insufficient permissions'], 403);
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            handleListProviders();
            break;
        case 'get':
            handleGetProvider();
            break;
        case 'create':
            handleCreateProvider();
            break;
        case 'update':
            handleUpdateProvider();
            break;
        case 'delete':
            handleDeleteProvider();
            break;
        case 'stats':
            handleGetStats();
            break;
        default:
            send_json(['error' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    error_log('Providers API Error: ' . $e->getMessage());
    send_json(['error' => 'Internal server error'], 500);
}

function handleListProviders() {
    global $mysqli;
    
    $sql = "
        SELECT 
            id,
            name,
            type,
            contact_person,
            contact_email,
            contact_phone,
            service_area,
            monthly_rate,
            status,
            contract_start,
            contract_end,
            notes,
            created_at,
            updated_at
        FROM providers
        ORDER BY created_at DESC
    ";
    
    $result = $mysqli->query($sql);
    
    if (!$result) {
        send_json(['error' => 'Database query failed'], 500);
    }
    
    $providers = [];
    while ($row = $result->fetch_assoc()) {
        $providers[] = $row;
    }
    
    send_json(['providers' => $providers]);
}

function handleGetProvider() {
    global $mysqli;
    
    $id = $_GET['id'] ?? '';
    if (!$id || !is_numeric($id)) {
        send_json(['error' => 'Invalid provider ID'], 400);
    }
    
    $sql = "
        SELECT 
            *
        FROM providers
        WHERE id = ?
    ";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        send_json(['error' => 'Provider not found'], 404);
    }
    
    $provider = $result->fetch_assoc();
    send_json(['provider' => $provider]);
}

function handleCreateProvider() {
    global $mysqli;
    
    $data = read_json_body();
    
    // Validate required fields
    $required = ['name', 'type', 'contact_person', 'contact_email', 'contact_phone', 'service_area'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            send_json(['error' => "Field '$field' is required"], 400);
        }
    }
    
    // Validate email
    if (!filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
        send_json(['error' => 'Invalid email address'], 400);
    }
    
    // Set defaults
    $status = $data['status'] ?? 'Active';
    $monthlyRate = $data['monthly_rate'] ?? 0.00;
    $contractStart = !empty($data['contract_start']) ? $data['contract_start'] : date('Y-m-d');
    $contractEnd = !empty($data['contract_end']) ? $data['contract_end'] : date('Y-m-d', strtotime('+1 year'));
    $notes = $data['notes'] ?? null;
    
    $sql = "INSERT INTO providers (
        name, type, contact_person, contact_email, contact_phone,
        service_area, monthly_rate, status, contract_start, contract_end, notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        send_json(['error' => 'Database prepare failed'], 500);
    }
    
    $stmt->bind_param('ssssssdssss', 
        $data['name'],
        $data['type'],
        $data['contact_person'],
        $data['contact_email'],
        $data['contact_phone'],
        $data['service_area'],
        $monthlyRate,
        $status,
        $contractStart,
        $contractEnd,
        $notes
    );
    
    if ($stmt->execute()) {
        $newId = $mysqli->insert_id;
        send_json([
            'success' => true,
            'message' => 'Provider created successfully',
            'provider_id' => $newId
        ]);
    } else {
        send_json(['error' => 'Failed to create provider'], 500);
    }
}

function handleUpdateProvider() {
    global $mysqli;
    
    $id = $_GET['id'] ?? '';
    if (!$id || !is_numeric($id)) {
        send_json(['error' => 'Invalid provider ID'], 400);
    }
    
    $data = read_json_body();
    
    // Check if provider exists
    $checkSql = "SELECT * FROM providers WHERE id = ?";
    $stmt = $mysqli->prepare($checkSql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        send_json(['error' => 'Provider not found'], 404);
    }
    
    // Validate email if provided
    if (!empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
        send_json(['error' => 'Invalid email address'], 400);
    }
    
    // Build update query dynamically
    $updateFields = [];
    $params = [];
    $types = '';
    
    $updatableFields = [
        'name' => 's',
        'type' => 's',
        'contact_person' => 's',
        'contact_email' => 's',
        'contact_phone' => 's',
        'service_area' => 's',
        'monthly_rate' => 'd',
        'status' => 's',
        'contract_start' => 's',
        'contract_end' => 's',
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
    
    $sql = "UPDATE providers SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $params[] = $id;
    $types .= 'i';
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        send_json(['error' => 'Database prepare failed'], 500);
    }
    
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        send_json([
            'success' => true,
            'message' => 'Provider updated successfully'
        ]);
    } else {
        send_json(['error' => 'Failed to update provider'], 500);
    }
}

function handleDeleteProvider() {
    global $mysqli;
    
    $id = $_GET['id'] ?? '';
    if (!$id || !is_numeric($id)) {
        send_json(['error' => 'Invalid provider ID'], 400);
    }
    
    // Check if provider exists
    $checkSql = "SELECT * FROM providers WHERE id = ?";
    $stmt = $mysqli->prepare($checkSql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        send_json(['error' => 'Provider not found'], 404);
    }
    
    $sql = "DELETE FROM providers WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        send_json([
            'success' => true,
            'message' => 'Provider deleted successfully'
        ]);
    } else {
        send_json(['error' => 'Failed to delete provider'], 500);
    }
}

function handleGetStats() {
    global $mysqli;
    
    // Get total providers
    $totalSql = "SELECT COUNT(*) as total FROM providers";
    $totalResult = $mysqli->query($totalSql);
    $totalProviders = $totalResult ? $totalResult->fetch_assoc()['total'] : 0;
    
    // Get active providers
    $activeSql = "SELECT COUNT(*) as active FROM providers WHERE status = 'Active'";
    $activeResult = $mysqli->query($activeSql);
    $activeProviders = $activeResult ? $activeResult->fetch_assoc()['active'] : 0;
    
    // Get unique service areas
    $areasSql = "SELECT COUNT(DISTINCT service_area) as areas FROM providers";
    $areasResult = $mysqli->query($areasSql);
    $serviceAreas = $areasResult ? $areasResult->fetch_assoc()['areas'] : 0;
    
    // Get total monthly revenue
    $revenueSql = "SELECT SUM(monthly_rate) as revenue FROM providers WHERE status = 'Active'";
    $revenueResult = $mysqli->query($revenueSql);
    $monthlyRevenue = $revenueResult ? $revenueResult->fetch_assoc()['revenue'] : 0;
    
    send_json([
        'total_providers' => $totalProviders,
        'active_providers' => $activeProviders,
        'service_areas' => $serviceAreas,
        'monthly_revenue' => $monthlyRevenue ?: 0
    ]);
}
?>
