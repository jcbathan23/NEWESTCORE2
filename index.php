<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Redirect to appropriate dashboard based on role
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin.php');
    } elseif ($_SESSION['role'] === 'provider') {
        header('Location: provider-dashboard.php');
    } else {
        header('Location: user-dashboard.php');
    }
    exit();
} else {
    // Redirect to login page
    header('Location: login.php');
    exit();
}
?>
