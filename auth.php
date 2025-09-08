<?php
// Prevent any HTML output before JSON responses
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

session_start();

// Check if this is a JSON API request
$isApiRequest = (isset($_POST['action']) && ($_POST['action'] === 'register' || $_POST['action'] === 'login')) ||
                 isset($_GET['test_db']) ||
                 isset($_GET['logout']);

if ($isApiRequest) {
    // For API requests, ensure clean JSON output
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
}

try {
    require_once 'db.php';
    if ($isApiRequest && !isset($mysqli)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
} catch (Exception $e) {
    if ($isApiRequest) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'System configuration error: ' . $e->getMessage()]);
        exit();
    }
}

try {
    require_once 'security.php';
} catch (Exception $e) {
    if ($isApiRequest) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Security configuration error']);
        exit();
    }
}

// Authentication functions
function isLoggedIn() {
    // Check if session exists and has required data
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['role'])) {
        return false;
    }
    
    // Check session timeout (8 hours)
    $timeout = 8 * 60 * 60; // 8 hours in seconds
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
        // Session expired
        session_unset();
        session_destroy();
        return false;
    }
    
    // Update login time on each check
    $_SESSION['login_time'] = time();
    
    return true;
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function isProvider() {
    return isLoggedIn() && $_SESSION['role'] === 'provider';
}

function isUser() {
    return isLoggedIn() && $_SESSION['role'] === 'user';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: login.php?error=access_denied');
        exit();
    }
}

function requireProvider() {
    if (!isProvider()) {
        header('Location: login.php?error=access_denied');
        exit();
    }
}

function requireUser() {
    if (!isUser()) {
        header('Location: login.php?error=access_denied');
        exit();
    }
}

function login($username, $password) {
    global $mysqli;
    
    // Check rate limiting
    if (!checkLoginRateLimit($username)) {
        return ['success' => false, 'message' => 'Too many login attempts. Please try again in 15 minutes.'];
    }
    
    // Sanitize input
    $username = sanitizeInput($username);
    $username = $mysqli->real_escape_string($username);
    
    // Get user from database
    $stmt = $mysqli->prepare("SELECT id, username, password_hash, role, is_active FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if user is active
        if (!$user['is_active']) {
            recordLoginAttempt($username, false);
            return ['success' => false, 'message' => 'Account is deactivated'];
        }
        
        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            // Start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            $_SESSION['csrf_token'] = generateCSRFToken();
            
            // Update last login
            $updateStmt = $mysqli->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();
            
            // Record successful login
            recordLoginAttempt($username, true);
            
            return ['success' => true, 'role' => $user['role']];
        }
    }
    
    // Record failed login attempt
    recordLoginAttempt($username, false);
    
    return ['success' => false, 'message' => 'Invalid username or password'];
}

function ensureTablesExist() {
    global $mysqli;
    
    // Create providers table if it doesn't exist
    $createProvidersTable = "CREATE TABLE IF NOT EXISTS providers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        type VARCHAR(100) NOT NULL,
        contact_person VARCHAR(255) NOT NULL,
        contact_email VARCHAR(255) NOT NULL,
        contact_phone VARCHAR(50) NOT NULL,
        service_area VARCHAR(255) NOT NULL,
        monthly_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status VARCHAR(50) NOT NULL,
        contract_start DATE NOT NULL,
        contract_end DATE NOT NULL,
        notes TEXT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $mysqli->query($createProvidersTable);
    
    // Create user_profiles table if it doesn't exist
    $createUserProfilesTable = "CREATE TABLE IF NOT EXISTS user_profiles (
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
    
    $mysqli->query($createUserProfilesTable);
}

function sendJsonResponse($data) {
    // Clean any output buffer completely
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Ensure proper JSON headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    
    // Send JSON response
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function registerUser($userData) {
    global $mysqli;
    
    try {
        // Check database connection
        if ($mysqli->connect_error) {
            error_log('Database connection error: ' . $mysqli->connect_error);
            return ['success' => false, 'message' => 'Database connection failed: ' . $mysqli->connect_error];
        }
        
        // Ensure required tables exist
        ensureTablesExist();
        
        // Ensure users table exists
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
        
        if (!$mysqli->query($createUsersTable)) {
            error_log('Failed to create users table: ' . $mysqli->error);
            return ['success' => false, 'message' => 'Failed to create users table: ' . $mysqli->error];
        }
        
        // Start transaction
        $mysqli->begin_transaction();
        
        // Check if username already exists
        $checkUsername = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
        $checkUsername->bind_param("s", $userData['username']);
        $checkUsername->execute();
        if ($checkUsername->get_result()->num_rows > 0) {
            $mysqli->rollback();
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Check if email already exists
        $checkEmail = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->bind_param("s", $userData['email']);
        $checkEmail->execute();
        if ($checkEmail->get_result()->num_rows > 0) {
            $mysqli->rollback();
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Insert user
        $insertUser = $mysqli->prepare("INSERT INTO users (username, password_hash, email, role, is_active) VALUES (?, ?, ?, ?, 1)");
        $insertUser->bind_param("ssss", $userData['username'], $passwordHash, $userData['email'], $userData['role']);
        
        if (!$insertUser->execute()) {
            $mysqli->rollback();
            return ['success' => false, 'message' => 'Failed to create user account'];
        }
        
        $userId = $mysqli->insert_id;
        
        // If it's a service provider, create provider record
        if ($userData['role'] === 'provider') {
            // Ensure providers table exists with correct structure
            $createProvidersTable = "CREATE TABLE IF NOT EXISTS providers (
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
            
            $mysqli->query($createProvidersTable);
            
            // Validate required provider fields
            $requiredProviderFields = ['company_name', 'service_type', 'contact_person', 'contact_phone', 'service_area', 'monthly_rate'];
            foreach ($requiredProviderFields as $field) {
                if (empty($userData[$field])) {
                    $mysqli->rollback();
                    return ['success' => false, 'message' => 'Missing required field: ' . $field];
                }
            }
            
            $insertProvider = $mysqli->prepare("INSERT INTO providers (name, type, contact_person, contact_email, contact_phone, service_area, monthly_rate, status, contract_start, contract_end, notes) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), ?)");
            
            if (!$insertProvider) {
                $mysqli->rollback();
                return ['success' => false, 'message' => 'Failed to prepare provider insert statement: ' . $mysqli->error];
            }
            
            $insertProvider->bind_param("sssssss", 
                $userData['company_name'],
                $userData['service_type'],
                $userData['contact_person'],
                $userData['email'],
                $userData['contact_phone'],
                $userData['service_area'],
                $userData['monthly_rate'],
                $userData['notes'] ?? ''
            );
            
            if (!$insertProvider->execute()) {
                $mysqli->rollback();
                return ['success' => false, 'message' => 'Failed to create provider profile: ' . $insertProvider->error];
            }
        }
        
        // If it's a regular user, create user profile
        if ($userData['role'] === 'user') {
            // Create user_profiles table if it doesn't exist
            $createUserProfilesTable = "CREATE TABLE IF NOT EXISTS user_profiles (
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
            
            $mysqli->query($createUserProfilesTable);
            
            $insertProfile = $mysqli->prepare("INSERT INTO user_profiles (user_id, first_name, last_name, phone, company) VALUES (?, ?, ?, ?, ?)");
            $insertProfile->bind_param("issss", 
                $userId,
                $userData['first_name'],
                $userData['last_name'],
                $userData['phone'],
                $userData['company'] ?? null
            );
            
            if (!$insertProfile->execute()) {
                $mysqli->rollback();
                return ['success' => false, 'message' => 'Failed to create user profile'];
            }
        }
        
        // Commit transaction
        $mysqli->commit();
        
        return ['success' => true, 'message' => 'Successful registration!'];
        
    } catch (Exception $e) {
        $mysqli->rollback();
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

function logout() {
    // Destroy session
    session_unset();
    session_destroy();
    
    // Redirect to login
    header('Location: login.php');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    try {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $response = ['success' => false, 'message' => 'Please enter both username and password'];
        } else {
            $response = login($username, $password);
        }
        
        sendJsonResponse($response);
    } catch (Exception $e) {
        error_log('Login error: ' . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
    }
}

    // Handle registration form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
        try {
            $role = $_POST['role'] ?? '';
            
            // Debug: Log all POST data
            error_log('Registration POST data: ' . json_encode($_POST));
            
            if ($role === 'user') {
                $userData = [
                    'username' => $_POST['username'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'password' => $_POST['password'] ?? '',
                    'role' => 'user',
                    'first_name' => $_POST['first_name'] ?? '',
                    'last_name' => $_POST['last_name'] ?? '',
                    'phone' => $_POST['phone'] ?? '',
                    'company' => $_POST['company'] ?? ''
                ];
            } elseif ($role === 'provider') {
                $userData = [
                    'username' => $_POST['username'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'password' => $_POST['password'] ?? '',
                    'role' => 'provider',
                    'company_name' => $_POST['company_name'] ?? '',
                    'contact_person' => $_POST['contact_person'] ?? '',
                    'contact_phone' => $_POST['contact_phone'] ?? '',
                    'service_area' => $_POST['service_area'] ?? '',
                    'service_type' => $_POST['service_type'] ?? '',
                    'monthly_rate' => $_POST['monthly_rate'] ?? '0.00',
                    'notes' => $_POST['notes'] ?? ''
                ];
                
                // Debug: Log provider data
                error_log('Provider registration data: ' . json_encode($userData));
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Invalid role specified']);
            }
            
            // Validate required fields
            $requiredFields = ['username', 'email', 'password'];
            foreach ($requiredFields as $field) {
                if (empty($userData[$field])) {
                    sendJsonResponse(['success' => false, 'message' => ucfirst($field) . ' is required']);
                }
            }
            
            // Validate password length
            if (strlen($userData['password']) < 6) {
                sendJsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters long']);
            }
            
            // Validate email format
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                sendJsonResponse(['success' => false, 'message' => 'Invalid email format']);
            }
            
            $response = registerUser($userData);
            
            // Log the response for debugging
            error_log('Registration response: ' . json_encode($response));
            
            sendJsonResponse($response);
            
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            sendJsonResponse(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
        }
    }

// Handle logout
if (isset($_GET['logout'])) {
    logout();
}

// Test database connection endpoint
if (isset($_GET['test_db'])) {
    try {
        global $mysqli;
        if ($mysqli->ping()) {
            sendJsonResponse(['success' => true, 'message' => 'Database connection successful']);
        } else {
            sendJsonResponse(['success' => false, 'message' => 'Database connection failed']);
        }
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Check session status endpoint
if (isset($_GET['check_session'])) {
    try {
        $response = [
            'success' => true,
            'logged_in' => isLoggedIn(),
            'is_admin' => isAdmin(),
            'username' => $_SESSION['username'] ?? null,
            'role' => $_SESSION['role'] ?? null
        ];
        
        if (isLoggedIn() && isset($_SESSION['login_time'])) {
            $timeElapsed = time() - $_SESSION['login_time'];
            $sessionTimeout = 8 * 60 * 60; // 8 hours
            $timeRemaining = max(0, $sessionTimeout - $timeElapsed);
            
            $response['time_remaining'] = $timeRemaining * 1000; // Convert to milliseconds
            $response['session_expires_at'] = $_SESSION['login_time'] + $sessionTimeout;
        }
        
        sendJsonResponse($response);
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Session check error: ' . $e->getMessage()]);
    }
}

// Extend session endpoint
if (isset($_GET['extend_session'])) {
    try {
        if (isLoggedIn()) {
            // Update login time to extend session
            $_SESSION['login_time'] = time();
            
            sendJsonResponse([
                'success' => true, 
                'message' => 'Session extended successfully',
                'new_expiry' => time() + (8 * 60 * 60)
            ]);
        } else {
            sendJsonResponse([
                'success' => false, 
                'message' => 'No active session to extend'
            ]);
        }
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Session extension error: ' . $e->getMessage()]);
    }
}

// Clean up any remaining output buffer for non-API requests
if (!$isApiRequest && ob_get_level()) {
    ob_end_clean();
}
?>
