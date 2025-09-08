<?php
session_start();
require_once '../auth.php';
require_once '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Only handle GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Get user profile data
    $stmt = $db->prepare("SELECT username, email, name, profile_picture, phone, service_area, provider_type, experience, description, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Check if profile picture file actually exists
    if ($user['profile_picture']) {
        if (!file_exists('../' . $user['profile_picture'])) {
            // If file doesn't exist, remove the reference from database
            $updateStmt = $db->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
            $updateStmt->bind_param("i", $userId);
            $updateStmt->execute();
            $user['profile_picture'] = null;
        }
    }
    
    // Return profile data
    echo json_encode([
        'success' => true,
        'profile' => [
            'username' => $user['username'],
            'email' => $user['email'],
            'name' => $user['name'] ?? '',
            'profile_picture' => $user['profile_picture'],
            'phone' => $user['phone'] ?? '',
            'service_area' => $user['service_area'] ?? '',
            'provider_type' => $user['provider_type'] ?? '',
            'experience' => $user['experience'] ?? '',
            'description' => $user['description'] ?? '',
            'role' => $user['role']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load profile: ' . $e->getMessage()
    ]);
}
?>
