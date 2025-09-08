<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');
require_once '../auth.php';
require_once '../db.php';

// Log all incoming requests for debugging
error_log('API Request - Method: ' . $_SERVER['REQUEST_METHOD'] . ', URI: ' . $_SERVER['REQUEST_URI']);
error_log('POST data: ' . json_encode($_POST));
error_log('Input data: ' . file_get_contents('php://input'));

// Ensure only logged-in users can access
if (!isLoggedIn()) {
    error_log('User requests API accessed without login. Session: ' . json_encode($_SESSION));
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Authentication required. Please log in to access this feature.',
        'error_code' => 'AUTH_REQUIRED',
        'session_id' => session_id()
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'GET':
            handleGetRequests();
            break;
        case 'POST':
            handleCreateRequest($input);
            break;
        case 'PUT':
            handleUpdateRequest($input);
            break;
        case 'DELETE':
            handleDeleteRequest($input);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function handleGetRequests() {
    global $mysqli, $db;
    
    // Use $db if $mysqli is not available
    if (!$mysqli && $db) {
        $mysqli = $db;
    }
    
    $userId = $_SESSION['user_id'];
    $status = $_GET['status'] ?? '';
    $serviceType = $_GET['service_type'] ?? '';
    $dateRange = $_GET['date_range'] ?? '';
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 10);
    $offset = ($page - 1) * $limit;
    
    // Build query conditions
    $conditions = ["user_id = ?"];
    $params = [$userId];
    $types = "i";
    
    if (!empty($status)) {
        $conditions[] = "status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if (!empty($serviceType)) {
        $conditions[] = "service_type = ?";
        $params[] = $serviceType;
        $types .= "s";
    }
    
    if (!empty($dateRange)) {
        switch ($dateRange) {
            case 'today':
                $conditions[] = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
        }
    }
    
    $whereClause = "WHERE " . implode(" AND ", $conditions);
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM user_requests $whereClause";
    $countStmt = $mysqli->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalResult = $countStmt->get_result()->fetch_assoc();
    $total = $totalResult['total'];
    
    // Get requests with pagination
    $sql = "
        SELECT 
            ur.*,
            p.username as provider_name,
            p.name as provider_company
        FROM user_requests ur
        LEFT JOIN users p ON ur.provider_id = p.id
        $whereClause
        ORDER BY ur.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $mysqli->prepare($sql);
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $requests = [];
    
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    // Get statistics
    $statsSQL = "
        SELECT 
            status,
            COUNT(*) as count
        FROM user_requests 
        WHERE user_id = ?
        GROUP BY status
    ";
    $statsStmt = $mysqli->prepare($statsSQL);
    $statsStmt->bind_param("i", $userId);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    
    $stats = [
        'pending' => 0,
        'processing' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];
    
    while ($row = $statsResult->fetch_assoc()) {
        $stats[$row['status']] = (int)$row['count'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'requests' => $requests,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_items' => $total,
                'items_per_page' => $limit
            ],
            'stats' => $stats
        ]
    ]);
}

function handleCreateRequest($input) {
    global $mysqli, $db;
    
    // Use $db if $mysqli is not available
    if (!$mysqli && $db) {
        $mysqli = $db;
    }
    
    // Log input for debugging
    error_log('Create request input: ' . json_encode($input));
    
    // Check database connection
    if (!$mysqli) {
        error_log('Database connection not available');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection error']);
        return;
    }
    
    // Validate required fields
    $requiredFields = ['service_type', 'title', 'description', 'origin', 'destination'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            error_log('Missing required field: ' . $field);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }
    
    // Generate unique request ID
    $requestId = 'REQ-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Check if request ID already exists
    $checkStmt = $mysqli->prepare("SELECT id FROM user_requests WHERE request_id = ?");
    $checkStmt->bind_param("s", $requestId);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        // Generate new ID if collision occurs
        $requestId = 'REQ-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }
    
    $userId = $_SESSION['user_id'];
    
    // Insert new request
    $sql = "
        INSERT INTO user_requests (
            request_id, user_id, service_type, title, description, 
            origin, destination, cargo_type, weight, volume,
            pickup_address, delivery_address, special_instructions,
            contact_person, contact_phone, priority
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param(
        "sissssssddssssss",
        $requestId,
        $userId,
        $input['service_type'],
        $input['title'],
        $input['description'],
        $input['origin'],
        $input['destination'],
        $input['cargo_type'] ?? null,
        $input['weight'] ?? null,
        $input['volume'] ?? null,
        $input['pickup_address'] ?? null,
        $input['delivery_address'] ?? null,
        $input['special_instructions'] ?? null,
        $input['contact_person'] ?? null,
        $input['contact_phone'] ?? null,
        $input['priority'] ?? 'normal'
    );
    
    if ($stmt->execute()) {
        $newRequestId = $mysqli->insert_id;
        error_log('Request created successfully with ID: ' . $newRequestId);
        
        // Log status history
        logStatusChange($newRequestId, null, 'pending', $userId, 'user', 'Request created');
        
        echo json_encode([
            'success' => true,
            'message' => 'Request created successfully',
            'data' => [
                'id' => $newRequestId,
                'request_id' => $requestId
            ]
        ]);
    } else {
        $error = $stmt->error;
        error_log('Failed to create request - SQL Error: ' . $error);
        error_log('MySQL Error: ' . $mysqli->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create request: ' . $error]);
    }
}

function handleUpdateRequest($input) {
    global $mysqli, $db;
    
    // Use $db if $mysqli is not available
    if (!$mysqli && $db) {
        $mysqli = $db;
    }
    
    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Request ID is required']);
        return;
    }
    
    $requestId = $input['id'];
    $userId = $_SESSION['user_id'];
    
    // Check if request belongs to user
    $checkStmt = $mysqli->prepare("SELECT id, status FROM user_requests WHERE id = ? AND user_id = ?");
    $checkStmt->bind_param("ii", $requestId, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        return;
    }
    
    $request = $result->fetch_assoc();
    
    // Check if request can be updated (only pending requests can be updated by users)
    if ($request['status'] !== 'pending') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Only pending requests can be updated']);
        return;
    }
    
    // Build update query dynamically
    $updateFields = [];
    $params = [];
    $types = "";
    
    $allowedFields = [
        'service_type' => 's', 'title' => 's', 'description' => 's',
        'origin' => 's', 'destination' => 's', 'cargo_type' => 's',
        'weight' => 'd', 'volume' => 'd', 'pickup_address' => 's',
        'delivery_address' => 's', 'special_instructions' => 's',
        'contact_person' => 's', 'contact_phone' => 's', 'priority' => 's'
    ];
    
    foreach ($allowedFields as $field => $type) {
        if (isset($input[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = $input[$field];
            $types .= $type;
        }
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
        return;
    }
    
    $updateFields[] = "updated_at = NOW()";
    $params[] = $requestId;
    $types .= "i";
    
    $sql = "UPDATE user_requests SET " . implode(", ", $updateFields) . " WHERE id = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Request updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update request']);
    }
}

function handleDeleteRequest($input) {
    global $mysqli, $db;
    
    // Use $db if $mysqli is not available
    if (!$mysqli && $db) {
        $mysqli = $db;
    }
    
    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Request ID is required']);
        return;
    }
    
    $requestId = $input['id'];
    $userId = $_SESSION['user_id'];
    
    // Check if request belongs to user and can be cancelled
    $checkStmt = $mysqli->prepare("SELECT id, status FROM user_requests WHERE id = ? AND user_id = ?");
    $checkStmt->bind_param("ii", $requestId, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        return;
    }
    
    $request = $result->fetch_assoc();
    
    // Check if request can be cancelled
    if (in_array($request['status'], ['completed', 'cancelled'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot cancel completed or already cancelled requests']);
        return;
    }
    
    $cancelReason = $input['cancel_reason'] ?? 'Cancelled by user';
    
    // Update request status to cancelled
    $stmt = $mysqli->prepare("
        UPDATE user_requests 
        SET status = 'cancelled', cancelled_at = NOW(), cancel_reason = ? 
        WHERE id = ?
    ");
    $stmt->bind_param("si", $cancelReason, $requestId);
    
    if ($stmt->execute()) {
        // Log status change
        logStatusChange($requestId, $request['status'], 'cancelled', $userId, 'user', $cancelReason);
        
        echo json_encode(['success' => true, 'message' => 'Request cancelled successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to cancel request']);
    }
}

function logStatusChange($requestId, $oldStatus, $newStatus, $changedBy, $changedByRole, $comments = null) {
    global $mysqli, $db;
    
    if (!$mysqli && $db) {
        $mysqli = $db;
    }
    
    $stmt = $mysqli->prepare("
        INSERT INTO request_status_history 
        (request_id, previous_status, new_status, changed_by_user_id, changed_by_role, comments) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssss", $requestId, $oldStatus, $newStatus, $changedBy, $changedByRole, $comments);
    $stmt->execute();
}

// Handle specific actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'get_request':
            if (isset($_GET['id'])) {
                getSingleRequest($_GET['id']);
            }
            break;
        case 'add_review':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                addRequestReview($input);
            }
            break;
        case 'get_tracking':
            if (isset($_GET['id'])) {
                getRequestTracking($_GET['id']);
            }
            break;
    }
}

function getSingleRequest($requestId) {
    global $mysqli, $db;
    
    if (!$mysqli && $db) {
        $mysqli = $db;
    }
    $userId = $_SESSION['user_id'];
    
    $sql = "
        SELECT 
            ur.*,
            p.username as provider_name,
            p.name as provider_company,
            p.phone as provider_phone
        FROM user_requests ur
        LEFT JOIN users p ON ur.provider_id = p.id
        WHERE ur.id = ? AND ur.user_id = ?
    ";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $requestId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Request not found']);
    }
}

function addRequestReview($input) {
    global $mysqli, $db;
    
    if (!$mysqli && $db) {
        $mysqli = $db;
    }
    
    if (empty($input['request_id']) || empty($input['rating'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Request ID and rating are required']);
        return;
    }
    
    $requestId = $input['request_id'];
    $userId = $_SESSION['user_id'];
    $rating = (float)$input['rating'];
    $reviewText = $input['review_text'] ?? '';
    
    // Validate rating range
    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
        return;
    }
    
    // Check if request belongs to user and is completed
    $checkStmt = $mysqli->prepare("SELECT id, status FROM user_requests WHERE id = ? AND user_id = ?");
    $checkStmt->bind_param("ii", $requestId, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        return;
    }
    
    $request = $result->fetch_assoc();
    
    if ($request['status'] !== 'completed') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Can only review completed requests']);
        return;
    }
    
    // Update request with review
    $stmt = $mysqli->prepare("
        UPDATE user_requests 
        SET rating = ?, review_text = ?, review_date = NOW() 
        WHERE id = ?
    ");
    $stmt->bind_param("dsi", $rating, $reviewText, $requestId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
    }
}

function getRequestTracking($requestId) {
    global $mysqli, $db;
    
    if (!$mysqli && $db) {
        $mysqli = $db;
    }
    $userId = $_SESSION['user_id'];
    
    // Get request details
    $requestSql = "
        SELECT ur.*, p.username as provider_name 
        FROM user_requests ur
        LEFT JOIN users p ON ur.provider_id = p.id
        WHERE ur.id = ? AND ur.user_id = ?
    ";
    
    $requestStmt = $mysqli->prepare($requestSql);
    $requestStmt->bind_param("ii", $requestId, $userId);
    $requestStmt->execute();
    $requestResult = $requestStmt->get_result();
    
    if ($requestResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        return;
    }
    
    $request = $requestResult->fetch_assoc();
    
    // Get status history
    $historySql = "
        SELECT 
            rsh.*,
            u.username as changed_by_username
        FROM request_status_history rsh
        LEFT JOIN users u ON rsh.changed_by_user_id = u.id
        WHERE rsh.request_id = ?
        ORDER BY rsh.created_at ASC
    ";
    
    $historyStmt = $mysqli->prepare($historySql);
    $historyStmt->bind_param("i", $requestId);
    $historyStmt->execute();
    $historyResult = $historyStmt->get_result();
    
    $history = [];
    while ($row = $historyResult->fetch_assoc()) {
        $history[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'request' => $request,
            'history' => $history
        ]
    ]);
}
?>
