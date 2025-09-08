<?php
/**
 * Dashboard Module - Main File
 * CORE II - Admin Dashboard Module
 */

// Start session and include required files
session_start();

// Define module constant
define('DASHBOARD_MODULE', true);

// Include authentication and security
require_once '../../auth.php';
requireAdmin(); // Only admin users can access this page
require_once '../../security.php';

// Include database connection
require_once '../../db.php';

// Include dashboard configuration and functions
require_once 'dashboard-config.php';
require_once 'dashboard-functions.php';

// Load dashboard configuration
$config = include 'dashboard-config.php';

// Validate configuration
try {
    validateDashboardConfig($config);
} catch (Exception $e) {
    error_log("Dashboard Configuration Error: " . $e->getMessage());
    // Continue with default configuration
}

// Log dashboard access
logDashboardActivity('dashboard_accessed');

// Extract configuration for easier use
$dashboardConfig = $config['dashboard'];
$weatherConfig = $config['weather'];
$modulesConfig = $config['modules'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | CORE II</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- Universal Logout SweetAlert -->
  <script src="../../includes/logout-sweetalert.js"></script>
  
  <!-- Dashboard Styles -->
  <link rel="stylesheet" href="assets/dashboard-styles.css">
  
  <style>
    :root {
      --sidebar-width: 280px;
      --primary-color: <?php echo $dashboardConfig['chart_settings']['colors']['primary']; ?>;
      --secondary-color: #f8f9fc;
      --dark-bg: #1a1a2e;
      --dark-card: #16213e;
      --text-light: #f8f9fa;
      --text-dark: #212529;
      --success-color: <?php echo $dashboardConfig['chart_settings']['colors']['success']; ?>;
      --info-color: <?php echo $dashboardConfig['chart_settings']['colors']['info']; ?>;
      --warning-color: <?php echo $dashboardConfig['chart_settings']['colors']['warning']; ?>;
      --danger-color: <?php echo $dashboardConfig['chart_settings']['colors']['danger']; ?>;
      --border-radius: 0.75rem;
      --shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
      overflow-x: hidden;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      color: var(--text-dark);
      transition: all 0.3s;
      min-height: 100vh;
    }

    body.dark-mode {
      background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
      color: var(--text-light);
    }

    /* Modern Sidebar */
    .sidebar {
      width: var(--sidebar-width);
      height: 100vh;
      position: fixed;
      left: 0;
      top: 0;
      background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
      color: white;
      padding: 0;
      transition: all 0.3s ease;
      z-index: 1000;
      transform: translateX(0);
      box-shadow: 4px 0 20px rgba(0,0,0,0.1);
      backdrop-filter: blur(10px);
    }

    .sidebar.collapsed {
      transform: translateX(-100%);
    }

    .sidebar .logo {
      padding: 2rem 1.5rem;
      text-align: center;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      background: rgba(255,255,255,0.05);
      backdrop-filter: blur(10px);
    }

    .sidebar .logo img {
      max-width: 100%;
      height: auto;
      filter: brightness(1.1);
    }

    .system-name {
      padding: 1rem 1.5rem;
      font-size: 1.1rem;
      font-weight: 700;
      color: rgba(255,255,255,0.95);
      text-align: center;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      margin-bottom: 1.5rem;
      background: rgba(255,255,255,0.03);
      letter-spacing: 1px;
      text-transform: uppercase;
    }

    .sidebar-nav {
      padding: 0 1rem;
    }

    .sidebar-nav .nav-item {
      margin-bottom: 0.5rem;
    }

    .sidebar-nav .nav-link {
      display: flex;
      align-items: center;
      color: rgba(255,255,255,0.8);
      padding: 1rem 1.25rem;
      text-decoration: none;
      border-radius: 0.75rem;
      transition: all 0.3s ease;
      font-weight: 500;
      border: 1px solid transparent;
      position: relative;
      overflow: hidden;
    }

    .sidebar-nav .nav-link::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
      transition: left 0.5s;
    }

    .sidebar-nav .nav-link:hover::before {
      left: 100%;
    }

    .sidebar-nav .nav-link:hover {
      background: rgba(255,255,255,0.1);
      color: white;
      border-color: rgba(255,255,255,0.2);
      transform: translateX(5px);
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    .sidebar-nav .nav-link.active {
      background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05));
      color: white;
      border-color: rgba(255,255,255,0.3);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .sidebar-nav .nav-link i {
      margin-right: 0.75rem;
      font-size: 1.1rem;
      width: 20px;
      text-align: center;
    }
    
    .sidebar-nav .nav-link .peso-icon {
      display: inline-block;
      margin-right: 0.75rem;
      font-size: 1.1rem;
      width: 20px;
      text-align: center;
      font-weight: 700;
    }

    .admin-feature {
      background: rgba(0,0,0,0.1);
      border-left: 3px solid rgba(255,255,255,0.3);
    }

    .admin-feature:hover {
      background: rgba(0,0,0,0.2);
      border-left-color: rgba(255,255,255,0.6);
    }

    .sidebar-footer {
      position: absolute;
      bottom: 0;
      width: 100%;
      padding: 1rem;
      border-top: 1px solid rgba(255,255,255,0.1);
      background: rgba(0,0,0,0.1);
      backdrop-filter: blur(10px);
    }

    .sidebar-footer .nav-link {
      justify-content: center;
      padding: 0.75rem;
      border-radius: 0.5rem;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
    }

    .sidebar-footer .nav-link:hover {
      background: rgba(255,255,255,0.1);
      border-color: rgba(255,255,255,0.2);
    }

    .content {
      margin-left: var(--sidebar-width);
      padding: 2rem;
      transition: all 0.3s ease;
      min-height: 100vh;
    }

    .content.expanded {
      margin-left: 0;
    }

    /* Header */
    .header {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      padding: 1.5rem 2rem;
      border-radius: var(--border-radius);
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border: 1px solid rgba(255,255,255,0.2);
    }

    .dark-mode .header {
      background: rgba(44, 62, 80, 0.9);
      color: var(--text-light);
      border: 1px solid rgba(255,255,255,0.1);
    }

    .hamburger {
      font-size: 1.5rem;
      cursor: pointer;
      padding: 0.75rem;
      border-radius: 0.5rem;
      transition: all 0.3s;
      background: rgba(0,0,0,0.05);
    }

    .hamburger:hover {
      background: rgba(0,0,0,0.1);
    }

    .system-title { 
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
      -webkit-background-clip: text; 
      -webkit-text-fill-color: transparent; 
      background-clip: text; 
      font-size: 2.2rem; 
      font-weight: 800; 
    }

    .header-controls {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .theme-toggle-container {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.5rem;
      border-radius: 2rem;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .dark-mode .theme-toggle-container {
      background: rgba(44, 62, 80, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .theme-toggle-container:hover {
      background: rgba(255, 255, 255, 0.15);
      border-color: rgba(255, 255, 255, 0.3);
      transform: translateY(-1px);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .dark-mode .theme-toggle-container:hover {
      background: rgba(44, 62, 80, 0.4);
      border-color: rgba(255, 255, 255, 0.25);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .theme-label {
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--text-dark);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      user-select: none;
      transition: all 0.3s ease;
    }

    .dark-mode .theme-label {
      color: var(--text-light);
    }

    .theme-switch {
      position: relative;
      display: inline-block;
      width: 60px;
      height: 30px;
    }

    .theme-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, #ddd 0%, #f0f0f0 100%);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      border-radius: 30px;
      box-shadow: 
        inset 0 2px 4px rgba(0, 0, 0, 0.1),
        0 2px 8px rgba(0, 0, 0, 0.1);
      border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .slider:hover {
      transform: scale(1.05);
      box-shadow: 
        inset 0 2px 4px rgba(0, 0, 0, 0.15),
        0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .slider:before {
      position: absolute;
      content: "☀️";
      height: 22px;
      width: 22px;
      left: 4px;
      bottom: 2px;
      background: linear-gradient(135deg, #fff 0%, #f8f8f8 100%);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      box-shadow: 
        0 2px 6px rgba(0, 0, 0, 0.2),
        0 0 0 1px rgba(255, 255, 255, 0.3);
      border: 1px solid rgba(0, 0, 0, 0.1);
    }

    input:checked + .slider {
      background: linear-gradient(135deg, var(--primary-color) 0%, #5a67d8 100%);
      box-shadow: 
        inset 0 2px 4px rgba(0, 0, 0, 0.2),
        0 0 15px rgba(78, 115, 223, 0.4);
    }

    input:checked + .slider:before {
      content: "🌙";
      transform: translateX(30px);
      background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
      color: #ffd700;
      box-shadow: 
        0 2px 6px rgba(0, 0, 0, 0.3),
        0 0 0 1px rgba(255, 255, 255, 0.2),
        0 0 10px rgba(255, 215, 0, 0.3);
    }

    .slider:active:before {
      transform: scale(0.95);
    }

    input:checked + .slider:active:before {
      transform: translateX(30px) scale(0.95);
    }

    /* Dark Mode Text Visibility - Universal Styles */
    .dark-mode h1, 
    .dark-mode h2, 
    .dark-mode h3, 
    .dark-mode h4, 
    .dark-mode h5, 
    .dark-mode h6 {
      color: var(--text-light) !important;
    }

    .dark-mode p,
    .dark-mode span,
    .dark-mode div,
    .dark-mode li,
    .dark-mode td,
    .dark-mode th,
    .dark-mode label {
      color: var(--text-light) !important;
    }

    .dark-mode .text-muted {
      color: #adb5bd !important;
    }

    .dark-mode .text-primary {
      color: #667eea !important;
    }

    .dark-mode .text-success {
      color: var(--success-color) !important;
    }

    .dark-mode .text-info {
      color: var(--info-color) !important;
    }

    .dark-mode .text-warning {
      color: var(--warning-color) !important;
    }

    .dark-mode .text-danger {
      color: var(--danger-color) !important;
    }

    /* Dark Mode Form Elements */
    .dark-mode .form-control,
    .dark-mode .form-select {
      background-color: rgba(44, 62, 80, 0.8) !important;
      border-color: rgba(255, 255, 255, 0.2) !important;
      color: var(--text-light) !important;
    }

    .dark-mode .form-control:focus,
    .dark-mode .form-select:focus {
      background-color: rgba(44, 62, 80, 0.9) !important;
      border-color: var(--primary-color) !important;
      box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25) !important;
      color: var(--text-light) !important;
    }

    .dark-mode .form-control::placeholder {
      color: #adb5bd !important;
    }

    .dark-mode .form-label {
      color: var(--text-light) !important;
      font-weight: 500;
    }

    /* Dark Mode Table Styles */
    .dark-mode .table {
      color: var(--text-light) !important;
    }

    .dark-mode .table th {
      background-color: rgba(44, 62, 80, 0.7) !important;
      border-color: rgba(255, 255, 255, 0.1) !important;
      color: var(--text-light) !important;
    }

    .dark-mode .table td {
      border-color: rgba(255, 255, 255, 0.1) !important;
      color: var(--text-light) !important;
    }

    .dark-mode .table-hover tbody tr:hover {
      background-color: rgba(255, 255, 255, 0.05) !important;
    }

    /* Dark Mode Modal Styles */
    .dark-mode .modal-content {
      background-color: rgba(44, 62, 80, 0.95) !important;
      border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    .dark-mode .modal-header {
      border-bottom-color: rgba(255, 255, 255, 0.1) !important;
    }

    .dark-mode .modal-footer {
      border-top-color: rgba(255, 255, 255, 0.1) !important;
    }

    .dark-mode .modal-title {
      color: var(--text-light) !important;
    }

    .dark-mode .btn-close {
      filter: invert(1) grayscale(100%) brightness(200%);
    }

    /* Dark Mode Button Adjustments */
    .dark-mode .btn-secondary {
      background-color: rgba(108, 117, 125, 0.8) !important;
      border-color: rgba(108, 117, 125, 0.8) !important;
      color: var(--text-light) !important;
    }

    .dark-mode .btn-secondary:hover {
      background-color: rgba(108, 117, 125, 1) !important;
      border-color: rgba(108, 117, 125, 1) !important;
    }

    /* Dark Mode List Styles */
    .dark-mode .list-unstyled li {
      color: var(--text-light) !important;
    }

    .dark-mode .list-unstyled li strong {
      color: #667eea !important;
    }

    /* Dark Mode Card Headers */
    .dark-mode .card-header {
      background-color: rgba(44, 62, 80, 0.6) !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    .dark-mode .card-header h5 {
      color: var(--text-light) !important;
    }

    /* Admin Table Specific Styling - Force visibility */
    .dark-mode #usersTableBody tr {
      color: var(--text-light) !important;
    }

    .dark-mode #usersTableBody td {
      color: var(--text-light) !important;
      background-color: transparent !important;
    }

    .dark-mode .table-responsive .table tbody tr td {
      color: var(--text-light) !important;
    }

    .dark-mode .table tbody tr td,
    .dark-mode .table tbody tr td * {
      color: var(--text-light) !important;
    }

    /* Override any JavaScript-injected styles */
    body.dark-mode .table tbody tr td {
      color: var(--text-light) !important;
    }

    body.dark-mode #usersTableBody tr td {
      color: var(--text-light) !important;
    }
    
    /* Cards */
    .card {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      border-radius: var(--border-radius);
      padding: 1.5rem;
      box-shadow: var(--shadow);
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s ease;
    }
    
    /* Summary Cards - Horizontal alignment */
    .summary-card {
      display: flex;
      flex-direction: column;
      height: 100%;
      min-height: 200px;
    }
    
    .summary-card .card-body {
      display: flex;
      flex-direction: column;
      flex: 1;
      padding: 1rem;
    }
    
    /* Ensure cards container displays horizontally */
    #summaryCardsContainer {
      display: flex !important;
      flex-wrap: wrap;
      margin: 0 -0.5rem;
    }
    
    #summaryCardsContainer > div {
      padding: 0 0.5rem;
      margin-bottom: 1rem;
    }
    
    /* Responsive card sizing */
    @media (min-width: 1400px) {
      #summaryCardsContainer .col-xl-2 {
        flex: 0 0 16.666667%;
        max-width: 16.666667%;
      }
    }

    .dark-mode .card {
      background: rgba(44, 62, 80, 0.9);
      color: var(--text-light);
      border: 1px solid rgba(255,255,255,0.1);
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 0.5rem 2rem rgba(0,0,0,0.15);
    }

    /* Modern Loading Screen */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(180deg, rgba(44, 62, 80, 0.95) 0%, rgba(52, 73, 94, 0.98) 100%);
      backdrop-filter: blur(20px);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      opacity: 0;
      visibility: hidden;
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .dark-mode .loading-overlay {
      background: linear-gradient(180deg, rgba(44, 62, 80, 0.95) 0%, rgba(52, 73, 94, 0.98) 100%);
    }
    .loading-overlay.show {
      opacity: 1;
      visibility: visible;
    }
    .loading-container {
      text-align: center;
      position: relative;
    }
    .loading-logo {
      width: 80px;
      height: 80px;
      margin-bottom: 2rem;
      animation: logoFloat 3s ease-in-out infinite;
    }
    .loading-spinner {
      width: 60px;
      height: 60px;
      border: 3px solid rgba(102, 126, 234, 0.2);
      border-top: 3px solid #667eea;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto 1.5rem;
      position: relative;
    }
    .loading-spinner::before {
      content: '';
      position: absolute;
      top: -3px;
      left: -3px;
      right: -3px;
      bottom: -3px;
      border: 3px solid transparent;
      border-top: 3px solid rgba(102, 126, 234, 0.4);
      border-radius: 50%;
      animation: spin 1.5s linear infinite reverse;
    }
    .loading-text {
      font-size: 1.2rem;
      font-weight: 600;
      color: #667eea;
      margin-bottom: 0.5rem;
      opacity: 0;
      animation: textFadeIn 0.5s ease-out 0.3s forwards;
    }
    .loading-subtext {
      font-size: 0.9rem;
      color: #6c757d;
      opacity: 0;
      animation: textFadeIn 0.5s ease-out 0.6s forwards;
    }
    .dark-mode .loading-text { color: #667eea; }
    .dark-mode .loading-subtext { color: #adb5bd; }
    .loading-progress {
      width: 200px;
      height: 4px;
      background: rgba(102, 126, 234, 0.2);
      border-radius: 2px;
      margin: 1rem auto 0;
      overflow: hidden;
      position: relative;
    }
    .loading-progress-bar {
      height: 100%;
      background: linear-gradient(90deg, #667eea, #764ba2);
      border-radius: 2px;
      width: 0%;
      animation: progressFill 2s ease-in-out infinite;
    }
    .loading-dots {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      margin-top: 1rem;
    }
    .loading-dot {
      width: 8px;
      height: 8px;
      background: #667eea;
      border-radius: 50%;
      animation: dotPulse 1.4s ease-in-out infinite both;
    }
    .loading-dot:nth-child(2) { animation-delay: 0.2s; }
    .loading-dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    @keyframes logoFloat { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-10px); } }
    @keyframes textFadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes progressFill { 0% { width: 0%; } 50% { width: 70%; } 100% { width: 100%; } }
    @keyframes dotPulse {
      0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
      40% { transform: scale(1); opacity: 1; }
    }

    /* Responsive Design Improvements */
    @media (max-width: 1200px) {
      .content {
        padding: 1.5rem;
      }
      
      #summaryCardsContainer .col-lg-2 {
        flex: 0 0 50%;
        max-width: 50%;
      }
    }
    
    @media (max-width: 992px) {
      .sidebar {
        transform: translateX(-100%);
        z-index: 1050;
      }
      
      .sidebar.show {
        transform: translateX(0);
      }
      
      .content {
        margin-left: 0;
        padding: 1rem;
      }
      
      .header {
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
      }
      
      .system-title {
        font-size: 1.8rem;
      }
      
      #summaryCardsContainer .col-lg-2 {
        flex: 0 0 100%;
        max-width: 100%;
      }
    }
    
    @media (max-width: 768px) {
      .hamburger {
        display: block;
      }
      
      .header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
      }
      
      .header-controls {
        justify-content: center;
      }
      
      .table-responsive {
        font-size: 0.875rem;
      }
      
      .card {
        padding: 1rem;
      }
      
      .btn {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
      }
      
      .modal-dialog {
        margin: 0.5rem;
      }
    }
    
    @media (max-width: 576px) {
      .content {
        padding: 0.5rem;
      }
      
      .header {
        padding: 1rem;
        border-radius: 0.5rem;
      }
      
      .system-title {
        font-size: 1.5rem;
      }
      
      .card {
        padding: 0.75rem;
        margin-bottom: 1rem;
      }
      
      .table th,
      .table td {
        padding: 0.5rem 0.25rem;
        font-size: 0.8rem;
      }
      
      .btn-sm {
        font-size: 0.75rem;
        padding: 0.375rem 0.5rem;
      }
    }
  </style>
</head>
<body>
  <!-- Modern Loading Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
    <div class="loading-container">
      <img src="../../slatelogo.png" alt="SLATE Logo" class="loading-logo">
      <div class="loading-spinner"></div>
      <div class="loading-text" id="loadingText">Loading Dashboard...</div>
      <div class="loading-subtext" id="loadingSubtext">Please wait while we prepare admin data</div>
      <div class="loading-progress">
        <div class="loading-progress-bar"></div>
      </div>
      <div class="loading-dots">
        <div class="loading-dot"></div>
        <div class="loading-dot"></div>
        <div class="loading-dot"></div>
      </div>
    </div>
  </div>

  <?php 
    // Set role variables for sidebar
    $isAdmin = true;
    $isUser = false;
    $isProvider = false;
    include '../../includes/sidebar.php'; 
  ?>

  <div class="content" id="mainContent">
    <div class="header">
      <div class="hamburger" id="hamburger">☰</div>
      <div>
        <h1>Admin Dashboard<span class="system-title"> | CORE II</span></h1>
      </div>
      <div class="header-controls">
        <div class="theme-toggle-container">
          <span class="theme-label">Dark Mode</span>
          <label class="theme-switch">
            <input type="checkbox" id="themeToggle">
            <span class="slider"></span>
          </label>
        </div>
      </div>
    </div>

    <!-- Quick Info Cards - Enhanced -->
    <div class="row mb-4">
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header">
            <h5><i class="bi bi-cloud-sun"></i> Weather & Environment</h5>
          </div>
          <div class="card-body">
            <div class="text-center mb-3">
              <h2 class="text-primary mb-1">28°C</h2>
              <p class="mb-1 fw-bold"><?php echo $weatherConfig['default_location']; ?></p>
              <small class="text-muted">Partly Cloudy</small>
            </div>
            <div class="row text-center small mt-3">
              <div class="col-6">
                <div class="text-info fw-bold">75%</div>
                <div class="text-muted">Humidity</div>
              </div>
              <div class="col-6">
                <div class="text-success fw-bold">15 km/h</div>
                <div class="text-muted">Wind Speed</div>
              </div>
            </div>
            <div class="mt-3">
              <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-info" role="progressbar" style="width: 75%"></div>
              </div>
              <small class="text-muted mt-1 d-block text-center">Air Quality: Good</small>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header">
            <h5><i class="bi bi-calendar3"></i> Today's Dashboard</h5>
          </div>
          <div class="card-body">
            <div class="text-center mb-3">
              <h2 class="text-success mb-1"><?php echo date('j'); ?></h2>
              <p class="mb-1 fw-bold"><?php echo date('l, F Y'); ?></p>
              <small class="text-muted">Administrative Overview</small>
            </div>
            <div class="row text-center small mt-3">
              <div class="col-6">
                <div class="text-primary fw-bold">5</div>
                <div class="text-muted">Active Sessions</div>
              </div>
              <div class="col-6">
                <div class="text-warning fw-bold">2</div>
                <div class="text-muted">Pending Tasks</div>
              </div>
            </div>
            <div class="mt-3">
              <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: 85%"></div>
              </div>
              <small class="text-muted mt-1 d-block text-center">System Status: Operational</small>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header">
            <h5><i class="bi bi-graph-up-arrow"></i> System Overview</h5>
          </div>
          <div class="card-body">
            <div id="systemOverview">
              <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading system data...</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Module Summary Cards -->
    <div class="row mb-4" id="moduleSummaryCards">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h3><i class="bi bi-grid-3x3-gap"></i> Module Summary</h3>
          <button class="btn btn-outline-primary btn-sm" onclick="refreshDashboardData()">
            <i class="bi bi-arrow-clockwise"></i> Refresh Data
          </button>
        </div>
      </div>
      
      <!-- Loading state -->
      <div id="summaryCardsLoading" class="col-12 text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Loading module data...</p>
      </div>
      
      <!-- Cards container -->
      <div id="summaryCardsContainer" class="row" style="display: none;">
        <!-- Cards will be populated here -->
      </div>
    </div>
    
    <!-- Analytics Chart - Enhanced Size -->
    <div class="row mb-5">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <h4 class="mb-0"><i class="bi bi-bar-chart-line text-primary"></i> System Analytics - Last 7 Days</h4>
            <div class="btn-group" role="group">
              <button class="btn btn-outline-secondary btn-sm active" onclick="switchChartView('week')" id="weekView">7 Days</button>
              <button class="btn btn-outline-secondary btn-sm" onclick="switchChartView('month')" id="monthView">30 Days</button>
            </div>
          </div>
          <div class="card-body p-4" style="min-height: 500px;">
            <canvas id="analyticsChart" height="150" style="max-height: 450px;"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-8">
        <div class="card">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Admin Accounts</h3>
            <button class="btn btn-primary" onclick="showAddUserModal()">
              <i class="bi bi-plus-circle"></i> Add User
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Last Login</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="usersTableBody">
                <!-- User data will be loaded here -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="card">
          <h3>Change Password</h3>
          <form id="changePasswordForm">
            <div class="mb-3">
              <label for="currentPassword" class="form-label">Current Password</label>
              <input type="password" class="form-control" id="currentPassword" required>
            </div>
            <div class="mb-3">
              <label for="newPassword" class="form-label">New Password</label>
              <input type="password" class="form-control" id="newPassword" required>
            </div>
            <div class="mb-3">
              <label for="confirmPassword" class="form-label">Confirm New Password</label>
              <input type="password" class="form-control" id="confirmPassword" required>
            </div>
            <button type="submit" class="btn btn-primary">Change Password</button>
          </form>
        </div>
        
        <div class="card mt-3">
          <h3>Security Information</h3>
          <ul class="list-unstyled">
            <li><strong>Session Timeout:</strong> <?php echo $dashboardConfig['security']['session_timeout'] / 3600; ?> hours</li>
            <li><strong>Login Attempts:</strong> <?php echo $dashboardConfig['max_login_attempts']; ?> before lockout</li>
            <li><strong>Lockout Duration:</strong> <?php echo $dashboardConfig['lockout_duration'] / 60; ?> minutes</li>
            <li><strong>Current Admin:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></li>
            <li><strong>Role:</strong> <?php echo htmlspecialchars($_SESSION['role']); ?></li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Include modals from original file -->
  <?php include 'modals/user-modals.php'; ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <!-- Dashboard Configuration -->
  <script>
    // Dashboard Configuration from PHP
    const DASHBOARD_CONFIG = <?php echo json_encode($dashboardConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const MODULES_CONFIG = <?php echo json_encode($modulesConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const API_BASE_URL = './modules/dashboard/api/';
  </script>
  
  <!-- Dashboard JavaScript -->
  <script src="assets/dashboard-scripts.js"></script>
</body>
</html>
