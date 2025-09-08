<?php
require_once '../auth.php';
requireAdmin(); // Only admin users can access this API
require_once '../security.php';
require_once '../db.php';

header('Content-Type: application/json');

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }
    
    // Validate required fields
    if (!isset($input['id']) || empty($input['id'])) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }
    
    $id = (int)$input['id'];
    
    // Check if user exists
    $stmt = $mysqli->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Prevent admin from deleting themselves
    if ($id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
        exit;
    }
    
    // Prevent deletion of the last admin user
    if ($user['role'] === 'admin') {
        $stmt = $mysqli->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin' AND is_active = 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['admin_count'] <= 1) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete the last admin user']);
            exit;
        }
    }
    
    // Delete user (hard delete)
    $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'User deleted successfully',
                'data' => [
                    'id' => $id,
                    'username' => $user['username'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found or already deleted']);
        }
    } else {
        throw new Exception('Failed to delete user: ' . $mysqli->error);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to delete user: ' . $e->getMessage()
    ]);
}
?>
