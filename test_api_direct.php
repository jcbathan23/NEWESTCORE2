<?php
// Direct API test script
session_start();

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['login_time'] = time();

echo "<h2>Direct API Test</h2>";

echo "<h3>Test 1: Get All Users</h3>";
echo "<pre>";
ob_start();
include 'api/users.php';
$output = ob_get_clean();
echo htmlspecialchars($output);
echo "</pre>";

echo "<h3>Test 2: Get Single User</h3>";
echo "<pre>";
$_GET['id'] = 1;
ob_start();
include 'api/users.php';
$output = ob_get_clean();
echo htmlspecialchars($output);
echo "</pre>";
?>
