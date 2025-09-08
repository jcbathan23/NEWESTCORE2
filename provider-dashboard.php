<?php
session_start();

// Define constants
define('PROVIDER_DASHBOARD', true);

require_once 'db.php';
require_once 'includes/provider-dashboard-functions.php';

// Check if user is logged in and is a provider
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header('Location: login.php');
    exit();
}

$provider_id = $_SESSION['user_id'];
$provider_name = $_SESSION['username'];

// Get provider statistics using the new module-based approach
$stats = getProviderDashboardStats($provider_id);

// Get recent activity for the provider
$recent_activity = getProviderRecentActivity($provider_id, 10);

// Get upcoming schedules
$upcoming_schedules = getProviderUpcomingSchedules($provider_id, 5);

// Get performance data for charts
$performance_data = getProviderPerformanceData($provider_id, 'month');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Provider Dashboard | CORE II</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- Universal Dark Mode Styles -->
  <?php include 'includes/dark-mode-styles.php'; ?>
  <style>
    :root {
      --sidebar-width: 280px;
      --primary-color: #4e73df;
      --secondary-color: #f8f9fc;
      --dark-bg: #1a1a2e;
      --dark-card: #16213e;
      --text-light: #f8f9fa;
      --text-dark: #212529;
      --success-color: #1cc88a;
      --info-color: #36b9cc;
      --warning-color: #f6c23e;
      --border-radius: 0.75rem;
      --shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
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
      opacity: 1;
      visibility: visible;
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .loading-overlay.hide {
      opacity: 0;
      visibility: hidden;
    }

    .dark-mode .loading-overlay {
      background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(22, 33, 62, 0.98) 100%);
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
      font-size: 1.5rem;
      font-weight: 700;
      color: #667eea;
      margin-bottom: 0.5rem;
      opacity: 0;
      animation: textFadeIn 0.5s ease-out 0.3s forwards;
    }

    .loading-subtext {
      font-size: 1rem;
      color: #6c757d;
      opacity: 0;
      animation: textFadeIn 0.5s ease-out 0.6s forwards;
    }

    .dark-mode .loading-text {
      color: #667eea;
    }

    .dark-mode .loading-subtext {
      color: #adb5bd;
    }

    /* Loading Animations */
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    @keyframes logoFloat {
      0%, 100% { transform: translateY(0px) scale(1); }
      50% { transform: translateY(-10px) scale(1.05); }
    }

    @keyframes textFadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
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

    /* SLATE Sidebar */
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
    
    .system-subtitle {
      font-size: 0.8rem;
      color: rgba(255,255,255,0.7);
      margin: 0;
      font-weight: 500;
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
    
    .system-subtitle {
      font-size: 0.8rem;
      color: rgba(255,255,255,0.7);
      margin: 0;
      font-weight: 500;
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
    
    .provider-feature {
      background: rgba(0,0,0,0.1);
      border-left: 4px solid rgba(255,255,255,0.4);
    }

    .provider-feature:hover {
      background: rgba(0,0,0,0.2);
      border-left-color: rgba(255,255,255,0.8);
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

    .sidebar-footer .nav-link i {
      margin-right: 0;
    }

    /* Main Content */
    .content { 
      margin-left: var(--sidebar-width); 
      padding: 2rem; 
      transition: all 0.3s ease; 
      min-height: 100vh;
      width: calc(100% - var(--sidebar-width));
      max-width: none;
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

    /* Dashboard Cards Grid - Consistent Layout */
    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.5rem;
      margin-bottom: 2rem;
      max-width: 100%;
    }

    @media (max-width: 1200px) {
      .dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
      }
    }

    @media (max-width: 768px) {
      .dashboard-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
      }
    }

    /* Stat Cards - Consistent Design */
    .stat-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px) saturate(180%);
      border: 1px solid rgba(0, 0, 0, 0.05);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: hidden;
      position: relative;
      height: 240px;
      display: flex;
      flex-direction: column;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--primary-color);
      transition: all 0.3s ease;
    }

    .stat-card-success::before {
      background: var(--success-color);
    }

    .stat-card-info::before {
      background: var(--info-color);
    }

    .stat-card-warning::before {
      background: var(--warning-color);
    }

    .stat-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
    }

    .stat-card:hover::before {
      height: 5px;
    }

    body.dark-mode .stat-card {
      background: rgba(22, 33, 62, 0.95);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: var(--text-light);
    }

    .stat-card .card-body {
      padding: 2rem 1.5rem;
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      gap: 0.75rem;
    }

    .stat-icon {
      font-size: 2.5rem;
      margin-bottom: 0.5rem;
      opacity: 0.9;
      line-height: 1;
    }

    .stat-label {
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 0.5rem;
      color: #6c757d;
      line-height: 1.2;
    }

    .stat-value {
      font-size: 2.8rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
      line-height: 1;
      min-height: 3.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .stat-subtitle {
      font-size: 0.875rem;
      color: #adb5bd;
      margin: 0;
      line-height: 1.3;
      font-weight: 500;
    }

    body.dark-mode .stat-label {
      color: #adb5bd;
    }

    body.dark-mode .stat-subtitle {
      color: #6c757d;
    }

    /* Card-specific value colors */
    .stat-card .stat-value {
      color: var(--primary-color);
    }
    
    .stat-card-success .stat-value {
      color: var(--success-color);
    }
    
    .stat-card-info .stat-value {
      color: var(--info-color);
    }
    
    .stat-card-warning .stat-value {
      color: var(--warning-color);
    }

    /* Animation states */
    .stat-card {
      opacity: 0;
      transform: translateY(20px);
      transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .stat-card.loaded {
      opacity: 1;
      transform: translateY(0);
    }

    /* Ensure consistent icon sizes */
    .stat-icon i {
      display: block;
      width: 100%;
      text-align: center;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .stat-card {
        height: 200px;
      }
      
      .stat-card .card-body {
        padding: 1.5rem 1rem;
        gap: 0.5rem;
      }
      
      .stat-icon {
        font-size: 2rem;
      }
      
      .stat-value {
        font-size: 2.2rem;
        min-height: 2.8rem;
      }
      
      .stat-label {
        font-size: 0.75rem;
      }
      
      .stat-subtitle {
        font-size: 0.8rem;
      }
    }

    /* Ensure equal height for all cards */
    .dashboard-grid .stat-card {
      align-self: stretch;
    }


    /* Header Controls */
    .header-controls {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    /* Theme Toggle */
    .theme-toggle-container {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .theme-switch {
      position: relative;
      display: inline-block;
      width: 60px;
      height: 34px;
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
      background-color: #ccc;
      transition: .4s;
      border-radius: 34px;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 26px;
      width: 26px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }

    input:checked + .slider {
      background-color: var(--primary-color);
    }

    input:checked + .slider:before {
      transform: translateX(26px);
    }

    /* Modern Cards */
    .card {
      border: 1px solid #e3ebf6;
      border-radius: var(--border-radius);
      box-shadow: 0 2px 4px rgba(15, 34, 58, 0.06);
      background: white;
      transition: all 0.3s ease;
      overflow: hidden;
    }

    body.dark-mode .card {
      background: var(--dark-card);
      border-color: #393f4f;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .card:hover {
      box-shadow: 0 4px 12px rgba(15, 34, 58, 0.12);
    }

    /* Info Cards (Weather & Calendar) */
    .info-card {
      min-height: 280px;
      display: flex;
      flex-direction: column;
    }

    .info-card .card-body {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    /* Activity Cards (Recent Activity & Upcoming Schedules) */
    .activity-card {
      min-height: 400px;
      display: flex;
      flex-direction: column;
    }

    .activity-card .card-header {
      flex-shrink: 0;
    }

    .activity-card .card-body {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .activity-list {
      flex: 1;
    }

    .activity-item {
      padding: 1rem 0;
      border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    .activity-item:last-child {
      border-bottom: none;
    }

    .activity-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(78, 115, 223, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
    }

    .activity-title {
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--text-dark);
      margin: 0;
    }

    .activity-meta {
      font-size: 0.8rem;
      line-height: 1.3;
      margin: 0;
    }

    .activity-date {
      font-size: 0.75rem;
      opacity: 0.8;
    }

    .empty-state {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .empty-state i {
      font-size: 3rem !important;
    }

    /* Dark mode adjustments */
    body.dark-mode .activity-item {
      border-bottom-color: rgba(255,255,255,0.1);
    }

    body.dark-mode .activity-title {
      color: var(--text-light);
    }

    body.dark-mode .activity-icon {
      background: rgba(255, 255, 255, 0.1);
    }

    /* Responsive adjustments for info and activity cards */
    @media (max-width: 768px) {
      .info-card {
        min-height: 250px;
      }
      
      .activity-card {
        min-height: 350px;
      }
      
      .activity-item {
        padding: 0.75rem 0;
      }
      
      .activity-icon {
        width: 35px;
        height: 35px;
        font-size: 1rem;
      }
    }

    /* Ensure equal heights within rows */
    .row {
      display: flex;
      flex-wrap: wrap;
    }

    .row > [class*="col-"] {
      display: flex;
      flex-direction: column;
    }

    .row > [class*="col-"] > .card {
      flex: 1;
    }

    /* Modern Tables */
    .table {
      border-radius: var(--border-radius);
      overflow: hidden;
      box-shadow: var(--shadow);
    }

    .table thead th {
      background: var(--gradient-primary);
      color: white;
      border: none;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.8rem;
      letter-spacing: 0.5px;
    }

    .table-hover tbody tr:hover {
      background-color: rgba(102, 126, 234, 0.05);
      transform: scale(1.01);
      transition: all 0.2s;
    }

    /* Status Badges */
    .status-badge {
      font-size: 0.75rem;
      padding: 0.35rem 0.8rem;
      border-radius: 1rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Modern Buttons */
    .btn {
      border-radius: 0.5rem;
      font-weight: 600;
      padding: 0.75rem 1.5rem;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .btn::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      background: rgba(255,255,255,0.3);
      border-radius: 50%;
      transition: all 0.3s ease;
      transform: translate(-50%, -50%);
    }

    .btn:hover::before {
      width: 300px;
      height: 300px;
    }

    /* Chart Container */
    .chart-container {
      position: relative;
      height: 300px;
      border-radius: var(--border-radius);
      padding: 1rem;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
        width: 240px;
      }
      
      .content {
        margin-left: 0;
        padding: 1rem;
      }
      
      .sidebar.show {
        transform: translateX(0);
      }
    }
  </style>
</head>
<body>
  <!-- Loading Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
    <div class="loading-container">
      <img src="slatelogo.png" alt="SLATE Logo" class="loading-logo">
      <div class="loading-spinner"></div>
      <div class="loading-text" id="loadingText">Loading...</div>
      <div class="loading-subtext" id="loadingSubtext">Please wait while we prepare service network data</div>
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

  <!-- Mobile Sidebar Toggle -->
  <button class="sidebar-toggle d-md-none" id="sidebarToggle">
    <i class="bi bi-list"></i>
  </button>

  <div class="d-flex">
    <!-- SLATE Sidebar -->
    <nav class="sidebar" id="sidebar">
      <div class="logo">
        <img src="slatelogo.png" alt="SLATE Logo">
      </div>
      <div class="system-name">CORE II
      </div>
      
      <div class="sidebar-nav">
        <!-- EXACT ORDER FROM IMAGE -->
        <!-- 1. Dashboard -->
        <div class="nav-item">
          <a class="nav-link active" href="#" data-module="dashboard">
            <i class="bi bi-speedometer2"></i>
            Dashboard
          </a>
        </div>
        
        <!-- 3. Service Network -->
        <div class="nav-item">
          <a class="nav-link" href="service-network.php">
            <i class="bi bi-diagram-3"></i>
            Service Network
          </a>
        </div>
        
        <!-- 4. My Schedule -->
        <div class="nav-item">
          <a class="nav-link" href="schedules.php">
            <i class="bi bi-calendar-week"></i>
            My Schedule
          </a>
        </div>
        
        <!-- 5. Profile Settings -->
        <div class="nav-item">
          <a class="nav-link" href="provider-profile.php">
            <i class="bi bi-person-circle"></i>
            Profile Settings
          </a>
        </div>
      </div>
      
      <div class="sidebar-footer">
        <a href="#" class="nav-link" onclick="confirmLogout(); return false;" title="Logout">
          <i class="bi bi-box-arrow-right"></i>
          Logout
        </a>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="content" id="mainContent">
      <div class="header">
        <div class="hamburger" id="hamburger">☰</div>
        <div>
          <h1>Provider Dashboard <span class="system-title">| CORE II </span></h1>
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

      <!-- Dashboard Module -->
      <div id="module-dashboard" class="module-content">

        <!-- Statistics Cards -->
        <div class="dashboard-grid">
          <div class="stat-card">
            <div class="card-body">
              <div class="stat-icon text-primary">
                <i class="bi bi-map"></i>
              </div>
              <h3 class="stat-label">ACTIVE ROUTES</h3>
              <div class="stat-value"><?php echo $stats['active_routes']; ?></div>
              <div class="stat-subtitle">Currently active routes</div>
            </div>
          </div>
          
          <div class="stat-card stat-card-success">
            <div class="card-body">
              <div class="stat-icon text-success">
                <i class="bi bi-calendar-check"></i>
              </div>
              <h3 class="stat-label">ACTIVE SCHEDULES</h3>
              <div class="stat-value"><?php echo $stats['active_schedules']; ?></div>
              <div class="stat-subtitle">Active scheduled services</div>
            </div>
          </div>
          
          <div class="stat-card stat-card-info">
            <div class="card-body">
              <div class="stat-icon text-info">
                <i class="bi bi-currency-dollar"></i>
              </div>
              <h3 class="stat-label">MONTHLY REVENUE</h3>
              <div class="stat-value">₱<?php echo number_format($stats['monthly_revenue'], 0); ?></div>
              <div class="stat-subtitle">Current contract value</div>
            </div>
          </div>
          
          <div class="stat-card stat-card-warning">
            <div class="card-body">
              <div class="stat-icon text-warning">
                <i class="bi bi-geo-alt"></i>
              </div>
              <h3 class="stat-label">SERVICE POINTS</h3>
              <div class="stat-value"><?php echo $stats['total_service_points']; ?></div>
              <div class="stat-subtitle">Service network points</div>
            </div>
          </div>
        </div>

        <!-- Weather and Calendar Row -->
        <div class="row mb-4">
          <div class="col-md-8 mb-3">
            <div class="card info-card">
              <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                  <i class="bi bi-cloud-sun me-2 text-primary"></i>
                  <h5 class="card-title mb-0">Weather Forecast</h5>
                </div>
                <div class="row align-items-center">
                  <div class="col-md-6">
                    <div class="d-flex align-items-center">
                      <div class="me-4">
                        <h2 class="display-4 mb-0 fw-bold text-primary">28°C</h2>
                        <p class="mb-0 text-muted">Manila, Philippines</p>
                        <small class="text-muted">Partly Cloudy - Feels like 30°C</small>
                      </div>
                      <div class="text-primary" style="font-size: 4rem;">
                        <i class="bi bi-cloud-sun"></i>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="row text-center">
                      <div class="col-4">
                        <small class="text-muted d-block">Humidity</small>
                        <strong>75%</strong>
                      </div>
                      <div class="col-4">
                        <small class="text-muted d-block">Wind</small>
                        <strong>12 km/h</strong>
                      </div>
                      <div class="col-4">
                        <small class="text-muted d-block">Visibility</small>
                        <strong>10 km</strong>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="card info-card">
              <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-calendar3 me-2 text-primary"></i>
                    <h5 class="card-title mb-0">Calendar</h5>
                  </div>
                  <span class="badge bg-primary">Sep 2025</span>
                </div>
                <div class="text-center">
                  <p class="mb-2 text-muted">Saturday, September 6, 2025</p>
                  <h1 class="display-1 text-primary fw-bold mb-3">6</h1>
                  <div class="d-flex align-items-center justify-content-center text-success">
                    <i class="bi bi-check-circle me-2"></i>
                    <span>No events today</span>
                  </div>
                  <small class="text-muted">Your schedule is clear</small>
                </div>
              </div>
            </div>
          </div>
        </div>


        <!-- Recent Activity and Upcoming Schedules -->
        <div class="row mt-4">
          <div class="col-md-6 mb-3">
            <div class="card activity-card">
              <div class="card-header bg-transparent border-0 py-3">
                <div class="d-flex align-items-center">
                  <i class="bi bi-activity me-2 text-primary"></i>
                  <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
              </div>
              <div class="card-body py-2">
                <?php if (!empty($recent_activity)): ?>
                  <div class="activity-list">
                    <?php foreach (array_slice($recent_activity, 0, 4) as $activity): ?>
                      <div class="activity-item">
                        <div class="d-flex align-items-start">
                          <div class="activity-icon me-3">
                            <i class="bi bi-<?php echo $activity['type'] === 'route' ? 'map' : 'calendar'; ?> text-primary"></i>
                          </div>
                          <div class="flex-grow-1">
                            <h6 class="mb-1 activity-title"><?php echo htmlspecialchars($activity['name']); ?></h6>
                            <p class="text-muted mb-1 small activity-meta"><?php echo ucfirst($activity['type']); ?> - Status: <?php echo $activity['status']; ?></p>
                            <small class="text-muted activity-date"><?php echo date('M j, Y g:i A', strtotime($activity['updated_at'])); ?></small>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="text-center py-4 empty-state">
                    <i class="bi bi-inbox display-6 text-muted mb-3"></i>
                    <p class="text-muted mb-0">No recent activity</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          
          <div class="col-md-6 mb-3">
            <div class="card activity-card">
              <div class="card-header bg-transparent border-0 py-3">
                <div class="d-flex align-items-center">
                  <i class="bi bi-calendar-event me-2 text-success"></i>
                  <h5 class="card-title mb-0">Upcoming Schedules</h5>
                </div>
              </div>
              <div class="card-body py-2">
                <?php if (!empty($upcoming_schedules)): ?>
                  <div class="activity-list">
                    <?php foreach (array_slice($upcoming_schedules, 0, 4) as $schedule): ?>
                      <div class="activity-item">
                        <div class="d-flex align-items-start">
                          <div class="activity-icon me-3">
                            <i class="bi bi-clock text-success"></i>
                          </div>
                          <div class="flex-grow-1">
                            <h6 class="mb-1 activity-title"><?php echo htmlspecialchars($schedule['name']); ?></h6>
                            <p class="text-muted mb-1 small activity-meta"><?php echo htmlspecialchars($schedule['route']); ?> - <?php echo $schedule['vehicle_type']; ?></p>
                            <div class="d-flex align-items-center activity-date">
                              <small class="text-success me-3">
                                <i class="bi bi-calendar me-1"></i>
                                <?php echo date('M j, Y', strtotime($schedule['start_date'])); ?>
                              </small>
                              <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>
                                <?php echo date('g:i A', strtotime($schedule['departure'])); ?>
                              </small>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="text-center py-4 empty-state">
                    <i class="bi bi-calendar-x display-6 text-muted mb-3"></i>
                    <p class="text-muted mb-0">No upcoming schedules</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Provider Performance Analytics -->
        <div class="row mt-4">
          <div class="col-12">
            <div class="card">
              <div class="card-header bg-transparent border-0">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-bar-chart me-2 text-primary"></i>
                    <h5 class="card-title mb-0">Provider Performance Analytics</h5>
                  </div>
                  <button class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i>
                    Refresh
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="row mb-3">
                  <div class="col-md-4">
                    <div class="d-flex align-items-center">
                      <div class="me-3" style="width: 12px; height: 12px; background-color: var(--primary-color); border-radius: 50%;"></div>
                      <span class="small text-muted">Routes</span>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="d-flex align-items-center">
                      <div class="me-3" style="width: 12px; height: 12px; background-color: var(--success-color); border-radius: 50%;"></div>
                      <span class="small text-muted">Schedules</span>
                    </div>
                  </div>
                  <div class="col-md-4 text-end">
                    <small class="text-muted">Monthly Overview</small>
                  </div>
                </div>
                  <div class="chart-container" style="height: 300px;">
                  <canvas id="providerPerformanceChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Hidden module placeholder for future use -->
      <div id="module-placeholder" class="module-content" style="display:none;">
        <div class="text-center py-5">
          <i class="bi bi-gear display-1 text-muted mb-3"></i>
          <h4>Module Not Available</h4>
          <p class="text-muted">This module is currently under development.</p>
        </div>
      </div>
    </main>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Universal Logout SweetAlert -->
  <script src="includes/logout-sweetalert.js"></script>
  
  <script>
    // Initialize the application with loading screen
    document.addEventListener('DOMContentLoaded', function() {
      // Show loading overlay initially
      showLoadingScreen();
      
      // Initialize app after a delay to show loading animation
      setTimeout(() => {
        initializeApp();
        hideLoadingScreen();
      }, 1500);
    });

    function showLoadingScreen() {
      const loadingOverlay = document.getElementById('loadingOverlay');
      if (loadingOverlay) {
        loadingOverlay.style.opacity = '1';
        loadingOverlay.style.visibility = 'visible';
      }
    }

    function hideLoadingScreen() {
      const loadingOverlay = document.getElementById('loadingOverlay');
      if (loadingOverlay) {
        loadingOverlay.classList.add('hide');
        setTimeout(() => {
          loadingOverlay.style.display = 'none';
        }, 500);
      }
    }

    function initializeApp() {
      initializeEventListeners();
      applyStoredTheme();
      initializeCharts();
      animateStatCards();
    }

    function initializeEventListeners() {
      // Theme toggle
      const themeToggle = document.getElementById('themeToggle');
      if (themeToggle) {
        themeToggle.addEventListener('change', function() {
          document.body.classList.toggle('dark-mode', this.checked);
          localStorage.setItem('theme', this.checked ? 'dark' : 'light');
        });
      }

      // Enhanced sidebar toggle with smooth animations
      const hamburger = document.getElementById('hamburger');
      if (hamburger) {
        hamburger.addEventListener('click', function() {
          const sidebar = document.getElementById('sidebar');
          const mainContent = document.getElementById('mainContent');
          
          sidebar.classList.toggle('collapsed');
          mainContent.classList.toggle('expanded');
          
          // Add smooth transition class
          sidebar.classList.add('transitioning');
          setTimeout(() => {
            sidebar.classList.remove('transitioning');
          }, 300);
        });
      }

      // Close sidebar when clicking outside on mobile
      document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const hamburger = document.getElementById('hamburger');
        
        if (window.innerWidth <= 992 && 
            sidebar && hamburger &&
            !sidebar.contains(e.target) && 
            !hamburger.contains(e.target) &&
            !sidebar.classList.contains('collapsed')) {
          sidebar.classList.add('collapsed');
        }
      });

      // Active link management
      const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
      navLinks.forEach(link => {
        link.addEventListener('click', function() {
          navLinks.forEach(l => l.classList.remove('active'));
          this.classList.add('active');
        });
      });

      // Card hover animations
      const statCards = document.querySelectorAll('.stat-card');
      statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
          this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
          this.style.transform = 'translateY(0) scale(1)';
        });
      });
    }

    function animateStatCards() {
      const statCards = document.querySelectorAll('.stat-card');
      statCards.forEach((card, index) => {
        setTimeout(() => {
          card.classList.add('loaded');
        }, index * 150);
      });
    }

    function initializeCharts() {
      // Initialize provider performance chart with real data from modules
      const providerPerformanceCtx = document.getElementById('providerPerformanceChart');
      if (providerPerformanceCtx) {
        // Get data from PHP
        const performanceData = <?php echo json_encode($performance_data); ?>;
        
        // Extract labels and datasets
        const labels = performanceData.map(item => item.label);
        
        const chartData = {
          labels: labels,
          datasets: [
            {
              label: 'Routes',
              data: performanceData.map(item => item.routes),
              borderColor: 'var(--primary-color)',
              backgroundColor: 'rgba(102, 126, 234, 0.1)',
              borderWidth: 2,
              tension: 0.4,
              fill: true,
              pointBackgroundColor: 'var(--primary-color)',
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              pointRadius: 3,
              pointHoverRadius: 5
            },
            {
              label: 'Schedules',
              data: performanceData.map(item => item.schedules),
              borderColor: 'var(--success-color)',
              backgroundColor: 'rgba(28, 200, 138, 0.1)',
              borderWidth: 2,
              tension: 0.4,
              fill: true,
              pointBackgroundColor: 'var(--success-color)',
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              pointRadius: 3,
              pointHoverRadius: 5
            }
          ]
        };
        
        new Chart(providerPerformanceCtx, {
          type: 'line',
          data: chartData,
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                backgroundColor: '#fff',
                titleColor: '#333',
                bodyColor: '#666',
                borderColor: '#ddd',
                borderWidth: 1,
                cornerRadius: 8,
                displayColors: false
              }
            },
            scales: {
              x: {
                grid: {
                  display: false
                },
                border: {
                  display: false
                }
              },
              y: {
                grid: {
                  color: 'rgba(0,0,0,0.05)',
                  drawBorder: false
                },
                border: {
                  display: false
                },
                ticks: {
                  callback: function(value) {
                    return '₱' + value.toLocaleString();
                  }
                }
              }
            },
            interaction: {
              intersect: false,
              mode: 'index'
            }
          }
        });
      }
    }

    function applyStoredTheme() {
      const stored = localStorage.getItem('theme');
      const isDark = stored === 'dark';
      document.body.classList.toggle('dark-mode', isDark);
      const toggle = document.getElementById('themeToggle');
      if (toggle) toggle.checked = isDark;
    }

    function loadModuleContent(module) {
      // For now, just show that modules are under development
      // Since we're focusing only on the dashboard view
      console.log('Module clicked:', module);
    }
  </script>
</body>
</html>
