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
$input = json_decode(file_get_contents('php://input'), true);

// If it's form data instead of JSON
if (empty($input)) {
    $input = $_POST;
}

try {
    // Prepare the update fields
    $updateFields = [];
    $values = [];
    $types = '';
    
    // Map of allowed fields to update
    $allowedFields = [
        'fullName' => 'name',
        'email' => 'email',
        'phone' => 'phone',
        'serviceArea' => 'service_area',
        'providerType' => 'provider_type',
        'experience' => 'experience',
        'description' => 'description'
    ];
    
    foreach ($allowedFields as $inputField => $dbField) {
        if (isset($input[$inputField])) {
            $updateFields[] = "$dbField = ?";
            $values[] = $input[$inputField];
            $types .= 's';
        }
    }
    
    if (empty($updateFields)) {
        throw new Exception('No valid fields to update');
    }
    
    // Add user ID to parameters
    $values[] = $userId;
    $types .= 'i';
    
    // Build and execute the query
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update profile');
    }
    
    // Update session data if name was changed
    if (isset($input['fullName'])) {
        $_SESSION['name'] = $input['fullName'];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to save profile: ' . $e->getMessage()
    ]);
}
?>
