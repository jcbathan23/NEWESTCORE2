<?php
// Clean registration API endpoint
// Prevent any output before JSON
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Set JSON headers immediately
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Function to send clean JSON response
function sendResponse($data) {
    // Clear any output that might have been generated
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Send response and exit
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['success' => false, 'message' => 'Method not allowed']);
}

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'core2';

try {
    $mysqli = new mysqli($host, $user, $pass);
    if ($mysqli->connect_error) {
        sendResponse(['success' => false, 'message' => 'Database connection failed']);
    }
    
    // Create database if not exists
    $mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $mysqli->select_db($dbname);
    
    if ($mysqli->error) {
        sendResponse(['success' => false, 'message' => 'Database selection failed']);
    }
    
} catch (Exception $e) {
    sendResponse(['success' => false, 'message' => 'Database error']);
}

// Get and validate input
$role = $_POST['role'] ?? '';
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Basic validation
if (empty($username) || empty($email) || empty($password) || empty($role)) {
    sendResponse(['success' => false, 'message' => 'All required fields must be filled']);
}

if (strlen($password) < 6) {
    sendResponse(['success' => false, 'message' => 'Password must be at least 6 characters long']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(['success' => false, 'message' => 'Invalid email format']);
}

if (!in_array($role, ['user', 'provider'])) {
    sendResponse(['success' => false, 'message' => 'Invalid role specified']);
}

try {
    // Start transaction
    $mysqli->autocommit(false);
    
    // Create users table
    $createUsersTable = "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        role ENUM('admin', 'user', 'provider') NOT NULL DEFAULT 'user',
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $mysqli->query($createUsersTable);
    
    // Check if username exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    if (!$stmt) {
        $mysqli->rollback();
        sendResponse(['success' => false, 'message' => 'Database prepare error']);
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $mysqli->rollback();
        sendResponse(['success' => false, 'message' => 'Username already exists']);
    }
    
    // Check if email exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $mysqli->rollback();
        sendResponse(['success' => false, 'message' => 'Email already exists']);
    }
    
    // Insert user
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        $mysqli->rollback();
        sendResponse(['success' => false, 'message' => 'Failed to prepare user insert']);
    }
    
    $stmt->bind_param("ssss", $username, $passwordHash, $email, $role);
    
    if (!$stmt->execute()) {
        $mysqli->rollback();
        sendResponse(['success' => false, 'message' => 'Failed to create user account']);
    }
    
    $userId = $mysqli->insert_id;
    
    // Handle role-specific data
    if ($role === 'user') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $company = trim($_POST['company'] ?? '');
        
        if (empty($firstName) || empty($lastName) || empty($phone)) {
            $mysqli->rollback();
            sendResponse(['success' => false, 'message' => 'First name, last name, and phone are required']);
        }
        
        // Create user_profiles table
        $createProfileTable = "CREATE TABLE IF NOT EXISTS user_profiles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            company VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $mysqli->query($createProfileTable);
        
        $stmt = $mysqli->prepare("INSERT INTO user_profiles (user_id, first_name, last_name, phone, company) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            $mysqli->rollback();
            sendResponse(['success' => false, 'message' => 'Failed to prepare profile insert']);
        }
        
        $stmt->bind_param("issss", $userId, $firstName, $lastName, $phone, $company);
        
        if (!$stmt->execute()) {
            $mysqli->rollback();
            sendResponse(['success' => false, 'message' => 'Failed to create user profile']);
        }
        
    } elseif ($role === 'provider') {
        $companyName = trim($_POST['company_name'] ?? '');
        $contactPerson = trim($_POST['contact_person'] ?? '');
        $contactPhone = trim($_POST['contact_phone'] ?? '');
        $serviceArea = trim($_POST['service_area'] ?? '');
        $serviceType = trim($_POST['service_type'] ?? '');
        $monthlyRate = floatval($_POST['monthly_rate'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($companyName) || empty($contactPerson) || empty($contactPhone) || empty($serviceArea) || empty($serviceType)) {
            $mysqli->rollback();
            sendResponse(['success' => false, 'message' => 'All provider fields are required']);
        }
        
        // Create providers table
        $createProviderTable = "CREATE TABLE IF NOT EXISTS providers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(100) NOT NULL,
            contact_person VARCHAR(255) NOT NULL,
            contact_email VARCHAR(255) NOT NULL,
            contact_phone VARCHAR(50) NOT NULL,
            service_area VARCHAR(255) NOT NULL,
            monthly_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(50) NOT NULL DEFAULT 'Pending',
            contract_start DATE NOT NULL,
            contract_end DATE NOT NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $mysqli->query($createProviderTable);
        
        $stmt = $mysqli->prepare("INSERT INTO providers (name, type, contact_person, contact_email, contact_phone, service_area, monthly_rate, status, contract_start, contract_end, notes) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), ?)");
        if (!$stmt) {
            $mysqli->rollback();
            sendResponse(['success' => false, 'message' => 'Failed to prepare provider insert']);
        }
        
        $stmt->bind_param("ssssssds", $companyName, $serviceType, $contactPerson, $email, $contactPhone, $serviceArea, $monthlyRate, $notes);
        
        if (!$stmt->execute()) {
            $mysqli->rollback();
            sendResponse(['success' => false, 'message' => 'Failed to create provider profile']);
        }
    }
    
    // Commit transaction
    $mysqli->commit();
    
    // Success response
    sendResponse(['success' => true, 'message' => 'Successful registration!']);
    
} catch (Exception $e) {
    $mysqli->rollback();
    sendResponse(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
}
?>
