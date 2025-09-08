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

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Get current profile picture to delete the file
    $stmt = $db->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Delete the file if it exists
    if ($user && $user['profile_picture']) {
        $filePath = '../' . $user['profile_picture'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    // Update database to remove profile picture reference
    $stmt = $db->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
    $stmt->bind_param("i", $userId);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update database');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile picture removed successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to remove profile picture: ' . $e->getMessage()
    ]);
}
?>
