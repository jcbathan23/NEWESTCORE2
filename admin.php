<?php
/**
 * Admin Dashboard - Module Loader
 * CORE II - Admin Dashboard System
 * 
 * This file now loads the modular dashboard system
 * All functionality has been moved to modules/dashboard/
 */

// Start session and include authentication
session_start();
require_once 'auth.php';
requireAdmin(); // Only admin users can access this page
require_once 'security.php';

// Define constants for the dashboard module
define('DASHBOARD_MODULE', true);
define('ADMIN_DASHBOARD_ACCESS', true);

// Redirect to the new dashboard module
header('Location: modules/dashboard/dashboard.php');
exit();
?>
