<?php
// Test script to check admin login functionality
session_start();

echo "<h2>Admin Dashboard Access Test</h2>";

// Include database connection
try {
    require_once 'db.php';
    echo "<p>✓ Database connection: SUCCESS</p>";
    
    if ($mysqli && !$mysqli->connect_error) {
        echo "<p>✓ MySQL connection: ACTIVE</p>";
        
        // Check if admin user exists
        $result = $mysqli->query("SELECT username, email, role FROM users WHERE role = 'admin' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            echo "<p>✓ Admin user found: " . htmlspecialchars($admin['username']) . " (" . htmlspecialchars($admin['email']) . ")</p>";
        } else {
            echo "<p>⚠ No admin user found</p>";
        }
        
        // Check required tables
        $tables = ['users', 'providers', 'routes', 'schedules', 'service_points', 'sops', 'tariffs'];
        foreach ($tables as $table) {
            $result = $mysqli->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo "<p>✓ Table '$table': EXISTS</p>";
            } else {
                echo "<p>⚠ Table '$table': MISSING</p>";
            }
        }
        
    } else {
        echo "<p>✗ MySQL connection: FAILED - " . $mysqli->connect_error . "</p>";
    }
} catch (Exception $e) {
    echo "<p>✗ Database connection: FAILED - " . $e->getMessage() . "</p>";
}

// Check authentication files
try {
    require_once 'auth.php';
    echo "<p>✓ Auth system: LOADED</p>";
} catch (Exception $e) {
    echo "<p>✗ Auth system: ERROR - " . $e->getMessage() . "</p>";
}

try {
    require_once 'security.php';
    echo "<p>✓ Security system: LOADED</p>";
} catch (Exception $e) {
    echo "<p>✗ Security system: ERROR - " . $e->getMessage() . "</p>";
}

// Test dashboard files
$dashboardFiles = [
    'modules/dashboard/dashboard.php',
    'modules/dashboard/dashboard-config.php',
    'modules/dashboard/dashboard-functions.php',
    'modules/dashboard/modals/user-modals.php',
    'modules/dashboard/assets/dashboard-styles.css',
    'modules/dashboard/assets/dashboard-scripts.js'
];

foreach ($dashboardFiles as $file) {
    if (file_exists($file)) {
        echo "<p>✓ Dashboard file '$file': EXISTS</p>";
    } else {
        echo "<p>✗ Dashboard file '$file': MISSING</p>";
    }
}

// Test login credentials
echo "<h3>Login Test</h3>";
echo "<p>Default admin credentials:</p>";
echo "<ul>";
echo "<li>Username: admin</li>";
echo "<li>Password: admin123</li>";
echo "</ul>";

echo "<h3>Access URLs</h3>";
echo "<ul>";
echo "<li><a href='login.php'>Login Page</a></li>";
echo "<li><a href='admin.php'>Admin Dashboard</a></li>";
echo "<li><a href='modules/dashboard/dashboard.php'>Direct Dashboard Access</a></li>";
echo "</ul>";

if (isset($_SESSION['username'])) {
    echo "<p><strong>Current session:</strong> " . htmlspecialchars($_SESSION['username']) . " (Role: " . htmlspecialchars($_SESSION['role']) . ")</p>";
    echo "<p><a href='?logout=1'>Logout</a></p>";
    
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: test_admin.php');
        exit;
    }
} else {
    echo "<p><strong>No active session</strong></p>";
}
?>
