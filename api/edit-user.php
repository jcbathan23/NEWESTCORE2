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
    $required_fields = ['id', 'username', 'email', 'role', 'is_active'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing field: $field"]);
            exit;
        }
    }
    
    $id = (int)$input['id'];
    $username = sanitizeInput($input['username']);
    $email = sanitizeInput($input['email']);
    $role = sanitizeInput($input['role']);
    $is_active = (int)$input['is_active'];
    
    // Validate role
    $valid_roles = ['admin', 'user', 'provider'];
    if (!in_array($role, $valid_roles)) {
        echo json_encode(['success' => false, 'message' => 'Invalid role specified']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    // Check if user exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Check if username is already taken by another user
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit;
    }
    
    // Check if email is already taken by another user
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    
    // Update user
    $stmt = $mysqli->prepare("UPDATE users SET username = ?, email = ?, role = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("sssii", $username, $email, $role, $is_active, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'User updated successfully',
            'data' => [
                'id' => $id,
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'is_active' => (bool)$is_active
            ]
        ]);
    } else {
        throw new Exception('Failed to update user: ' . $mysqli->error);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update user: ' . $e->getMessage()
    ]);
}
?>
