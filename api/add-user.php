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
    $required_fields = ['username', 'email', 'password', 'role'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            echo json_encode(['success' => false, 'message' => "Missing or empty field: $field"]);
            exit;
        }
    }
    
    $username = sanitizeInput($input['username']);
    $email = sanitizeInput($input['email']);
    $password = $input['password'];
    $role = sanitizeInput($input['role']);
    $is_active = isset($input['is_active']) ? (int)$input['is_active'] : 1;
    
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
    
    // Validate password length
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
        exit;
    }
    
    // Check if username already exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit;
    }
    
    // Check if email already exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $mysqli->prepare("INSERT INTO users (username, password_hash, email, role, is_active) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $username, $password_hash, $email, $role, $is_active);
    
    if ($stmt->execute()) {
        $new_user_id = $mysqli->insert_id;
        echo json_encode([
            'success' => true, 
            'message' => 'User created successfully',
            'data' => [
                'id' => $new_user_id,
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'is_active' => (bool)$is_active
            ]
        ]);
    } else {
        throw new Exception('Failed to create user: ' . $mysqli->error);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to create user: ' . $e->getMessage()
    ]);
}
?>
