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

// Only admin can manage provider accounts
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
        case 'update':
            handleUpdateProvider();
            break;
        case 'toggle-status':
            handleToggleStatus();
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
    error_log('Provider Users API Error: ' . $e->getMessage());
    send_json(['error' => 'Internal server error'], 500);
}

function handleListProviders() {
    global $mysqli;
    
    $sql = "
        SELECT 
            id,
            username,
            email,
            name,
            phone,
            service_area,
            provider_type,
            experience,
            description,
            is_active,
            last_login,
            created_at,
            updated_at
        FROM users
        WHERE role = 'provider'
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
        FROM users
        WHERE id = ? AND role = 'provider'
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

function handleUpdateProvider() {
    global $mysqli;
    
    // Accept both POST and PUT methods
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
        send_json(['error' => 'Method not allowed'], 405);
    }
    
    $id = $_GET['id'] ?? '';
    if (!$id || !is_numeric($id)) {
        send_json(['error' => 'Invalid provider ID'], 400);
    }
    
    $data = read_json_body();
    
    // If no JSON body, try to get data from POST
    if (empty($data) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = $_POST;
    }
    
    // Check if provider exists
    $checkSql = "SELECT * FROM users WHERE id = ? AND role = 'provider'";
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
    
    // Map form fields to database fields
    $fieldMapping = [
        'name' => 'name',
        'contact_email' => 'email',
        'contact_phone' => 'phone',
        'service_area' => 'service_area',
        'type' => 'provider_type',
        'notes' => 'description'
    ];
    
    foreach ($fieldMapping as $formField => $dbField) {
        if (isset($data[$formField])) {
            $updateFields[] = "$dbField = ?";
            $params[] = $data[$formField];
            $types .= 's';
        }
    }
    
    // Handle status
    if (isset($data['status'])) {
        $updateFields[] = "is_active = ?";
        $params[] = ($data['status'] === 'Active') ? 1 : 0;
        $types .= 'i';
    }
    
    if (empty($updateFields)) {
        send_json(['error' => 'No fields to update'], 400);
    }
    
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
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
            'message' => 'Provider account updated successfully'
        ]);
    } else {
        send_json(['error' => 'Failed to update provider account'], 500);
    }
}

function handleToggleStatus() {
    global $mysqli;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json(['error' => 'Method not allowed'], 405);
    }
    
    $id = $_GET['id'] ?? '';
    if (!$id || !is_numeric($id)) {
        send_json(['error' => 'Invalid provider ID'], 400);
    }
    
    $data = read_json_body();
    if (empty($data) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = $_POST;
    }
    
    $isActive = $data['is_active'] ? 1 : 0;
    
    // Check if provider exists
    $checkSql = "SELECT * FROM users WHERE id = ? AND role = 'provider'";
    $stmt = $mysqli->prepare($checkSql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        send_json(['error' => 'Provider not found'], 404);
    }
    
    $sql = "UPDATE users SET is_active = ? WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $isActive, $id);
    
    if ($stmt->execute()) {
        send_json([
            'success' => true,
            'message' => 'Provider status updated successfully'
        ]);
    } else {
        send_json(['error' => 'Failed to update provider status'], 500);
    }
}

function handleDeleteProvider() {
    global $mysqli;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json(['error' => 'Method not allowed'], 405);
    }
    
    $id = $_GET['id'] ?? '';
    if (!$id || !is_numeric($id)) {
        send_json(['error' => 'Invalid provider ID'], 400);
    }
    
    // Check if provider exists
    $checkSql = "SELECT * FROM users WHERE id = ? AND role = 'provider'";
    $stmt = $mysqli->prepare($checkSql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        send_json(['error' => 'Provider not found'], 404);
    }
    
    $sql = "DELETE FROM users WHERE id = ? AND role = 'provider'";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        send_json([
            'success' => true,
            'message' => 'Provider account deleted successfully'
        ]);
    } else {
        send_json(['error' => 'Failed to delete provider account'], 500);
    }
}

function handleGetStats() {
    global $mysqli;
    
    // Get total provider accounts
    $totalSql = "SELECT COUNT(*) as total FROM users WHERE role = 'provider'";
    $totalResult = $mysqli->query($totalSql);
    $totalProviders = $totalResult ? $totalResult->fetch_assoc()['total'] : 0;
    
    // Get active provider accounts
    $activeSql = "SELECT COUNT(*) as active FROM users WHERE role = 'provider' AND is_active = 1";
    $activeResult = $mysqli->query($activeSql);
    $activeProviders = $activeResult ? $activeResult->fetch_assoc()['active'] : 0;
    
    // Get unique service areas
    $areasSql = "SELECT COUNT(DISTINCT service_area) as areas FROM users WHERE role = 'provider' AND service_area IS NOT NULL AND service_area != ''";
    $areasResult = $mysqli->query($areasSql);
    $serviceAreas = $areasResult ? $areasResult->fetch_assoc()['areas'] : 0;
    
    send_json([
        'total_providers' => $totalProviders,
        'active_providers' => $activeProviders,
        'service_areas' => $serviceAreas
    ]);
}
?>
