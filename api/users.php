<?php
require_once '../auth.php';
requireAdmin(); // Only admin users can access this API
require_once '../security.php';
require_once '../db.php';

header('Content-Type: application/json');

try {
    global $mysqli;
    
    // Check if we're looking for a specific user
    if (isset($_GET['id'])) {
        $user_id = (int)$_GET['id'];
        
        $stmt = $mysqli->prepare("SELECT id, username, email, role, is_active, last_login, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user = [
                'id' => $row['id'],
                'username' => htmlspecialchars($row['username']),
                'email' => htmlspecialchars($row['email']),
                'role' => $row['role'],
                'is_active' => (bool)$row['is_active'],
                'last_login' => $row['last_login'] ? date('Y-m-d H:i:s', strtotime($row['last_login'])) : null,
                'created_at' => date('Y-m-d H:i:s', strtotime($row['created_at']))
            ];
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
    } else {
        // Get all users
        $stmt = $mysqli->prepare("SELECT id, username, email, role, is_active, last_login, created_at FROM users ORDER BY id");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'id' => $row['id'],
                'username' => htmlspecialchars($row['username']),
                'email' => htmlspecialchars($row['email']),
                'role' => $row['role'],
                'is_active' => (bool)$row['is_active'],
                'last_login' => $row['last_login'] ? date('Y-m-d H:i:s', strtotime($row['last_login'])) : null,
                'created_at' => date('Y-m-d H:i:s', strtotime($row['created_at']))
            ];
        }
        
        echo json_encode($users);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load users']);
}
?>
