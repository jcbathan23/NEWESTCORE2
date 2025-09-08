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

// Check if file was uploaded
if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['profile_picture'];
$userId = $_SESSION['user_id'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed']);
    exit;
}

// Validate file size (max 5MB)
$maxSize = 5 * 1024 * 1024; // 5MB in bytes
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Maximum size is 5MB']);
    exit;
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
$uploadPath = '../uploads/profiles/' . $filename;
$relativePath = 'uploads/profiles/' . $filename;

// Create upload directory if it doesn't exist
$uploadDir = dirname($uploadPath);
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create upload directory']);
        exit;
    }
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save uploaded file']);
    exit;
}

// Get current profile picture to delete old one
$stmt = $db->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Delete old profile picture if it exists
if ($user && $user['profile_picture']) {
    $oldImagePath = '../' . $user['profile_picture'];
    if (file_exists($oldImagePath)) {
        unlink($oldImagePath);
    }
}

// Update database with new profile picture path
$stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
$stmt->bind_param("si", $relativePath, $userId);

if (!$stmt->execute()) {
    // If database update fails, remove the uploaded file
    unlink($uploadPath);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update profile picture in database']);
    exit;
}

// Resize image for better performance (optional)
try {
    resizeImage($uploadPath, $uploadPath, 300, 300);
} catch (Exception $e) {
    // Continue even if resize fails
    error_log('Failed to resize profile picture: ' . $e->getMessage());
}

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Profile picture updated successfully',
    'profile_picture' => $relativePath
]);

/**
 * Resize image to specified dimensions
 */
function resizeImage($source, $destination, $maxWidth, $maxHeight) {
    $imageInfo = getimagesize($source);
    if (!$imageInfo) {
        throw new Exception('Invalid image file');
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // Calculate new dimensions while maintaining aspect ratio
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = intval($width * $ratio);
    $newHeight = intval($height * $ratio);
    
    // Create image resource based on type
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($source);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($source);
            break;
        default:
            throw new Exception('Unsupported image type');
    }
    
    if (!$sourceImage) {
        throw new Exception('Failed to create image resource');
    }
    
    // Create new image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save resized image
    switch ($mimeType) {
        case 'image/jpeg':
            imagejpeg($newImage, $destination, 85);
            break;
        case 'image/png':
            imagepng($newImage, $destination);
            break;
        case 'image/gif':
            imagegif($newImage, $destination);
            break;
        case 'image/webp':
            imagewebp($newImage, $destination, 85);
            break;
    }
    
    // Clean up
    imagedestroy($sourceImage);
    imagedestroy($newImage);
}
?>
