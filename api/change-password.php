<?php
require_once '../auth.php';
require_once '../security.php';
require_once '../db.php';

// Only logged in users can change their password
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $currentPassword = $input['currentPassword'] ?? '';
    $newPassword = $input['newPassword'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword)) {
        throw new Exception('Current password and new password are required');
    }
    
    if (strlen($newPassword) < 6) {
        throw new Exception('New password must be at least 6 characters long');
    }
    
    global $mysqli;
    
    // Verify current password
    $stmt = $mysqli->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        throw new Exception('User not found');
    }
    
    $user = $result->fetch_assoc();
    
    if (!password_verify($currentPassword, $user['password_hash'])) {
        throw new Exception('Current password is incorrect');
    }
    
    // Hash new password
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password
    $updateStmt = $mysqli->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $updateStmt->bind_param("si", $newPasswordHash, $_SESSION['user_id']);
    
    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update password');
    }
    
    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
