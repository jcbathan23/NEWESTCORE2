<?php
session_start();
require_once 'auth.php';

// Require user authentication - only users can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- Universal Logout SweetAlert -->
  <script src="includes/logout-sweetalert.js"></script>
  
  <!-- Universal Dark Mode Styles -->
  <?php include 'includes/dark-mode-styles.php'; ?>
  <title>Transit Schedules | CORE II</title>
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
      --danger-color: #e74a3b;
      --border-radius: 0.75rem;
      --shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
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
      background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(22, 33, 62, 0.98) 100%);
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

    .dark-mode .loading-text {
      color: #667eea;
    }

    .dark-mode .loading-subtext {
      color: #adb5bd;
    }

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

    .loading-dot:nth-child(2) {
      animation-delay: 0.2s;
    }

    .loading-dot:nth-child(3) {
      animation-delay: 0.4s;
    }

    /* Loading Animations */
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    @keyframes logoFloat {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }

    @keyframes textFadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes progressFill {
      0% { width: 0%; }
      50% { width: 80%; }
      100% { width: 100%; }
    }

    @keyframes dotPulse {
      0%, 80%, 100% {
        opacity: 0.3;
        transform: scale(0.8);
      }
      40% {
        opacity: 1;
        transform: scale(1.2);
      }
    }

    body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; overflow-x: hidden; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); color: var(--text-dark); transition: all 0.3s; min-height: 100vh; }

    body.dark-mode { background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%); color: var(--text-light); }

    /* Modern Sidebar */
    .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; left: 0; top: 0; background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%); color: white; padding: 0; transition: all 0.3s ease; z-index: 1000; transform: translateX(0); box-shadow: 4px 0 20px rgba(0,0,0,0.1); backdrop-filter: blur(10px); }

    .sidebar.collapsed {
      transform: translateX(-100%);
    }

    .sidebar .logo { padding: 2rem 1.5rem; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); }

    .sidebar .logo img {
      max-width: 100%;
      height: auto;
      filter: brightness(1.1);
    }

    .system-name { padding: 1rem 1.5rem; font-size: 1.1rem; font-weight: 700; color: rgba(255,255,255,0.95); text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 1.5rem; background: rgba(255,255,255,0.03); letter-spacing: 1px; text-transform: uppercase; }

    .sidebar-nav {
      padding: 0 1rem;
    }

    .sidebar-nav .nav-item {
      margin-bottom: 0.5rem;
    }

    .sidebar-nav .nav-link { display: flex; align-items: center; color: rgba(255,255,255,0.8); padding: 1rem 1.25rem; text-decoration: none; border-radius: 0.75rem; transition: all 0.3s ease; font-weight: 500; border: 1px solid transparent; position: relative; overflow: hidden; }
    .sidebar-nav .nav-link::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent); transition: left 0.5s; }
    .sidebar-nav .nav-link:hover::before { left: 100%; }

    .sidebar-nav .nav-link:hover { background: rgba(255,255,255,0.1); color: white; border-color: rgba(255,255,255,0.2); transform: translateX(5px); box-shadow: 0 4px 15px rgba(0,0,0,0.2); }

    .sidebar-nav .nav-link.active { background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05)); color: white; border-color: rgba(255,255,255,0.3); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }

    .sidebar-nav .nav-link i {
      margin-right: 0.75rem;
      font-size: 1.1rem;
      width: 20px;
      text-align: center;
    }
    .sidebar-nav .nav-link .peso-icon { display: inline-block; margin-right: 0.75rem; font-size: 1.1rem; width: 20px; text-align: center; font-weight: 700; }

    .admin-feature {
      background: rgba(0,0,0,0.1);
      border-left: 3px solid rgba(255,255,255,0.3);
    }

    .admin-feature:hover {
      background: rgba(0,0,0,0.2);
      border-left-color: rgba(255,255,255,0.6);
    }

    .provider-feature {
      background: rgba(0,0,0,0.1);
      border-left: 4px solid rgba(255,255,255,0.4);
    }

    .provider-feature:hover {
      background: rgba(0,0,0,0.2);
      border-left-color: rgba(255,255,255,0.8);
    }

    .user-feature {
      background: rgba(0,123,255,0.1);
      border-left: 3px solid rgba(0,123,255,0.5);
    }

    .user-feature:hover {
      background: rgba(0,123,255,0.2);
      border-left-color: rgba(0,123,255,0.8);
    }

    .sidebar-footer { position: absolute; bottom: 0; width: 100%; padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.1); backdrop-filter: blur(10px); }

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

    /* Enhanced transitions */
    .sidebar.transitioning {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Hover effects for better UX */
    .sidebar-nav .nav-link:active {
      transform: scale(0.98);
      transition: transform 0.1s ease;
    }

    /* Main Content */
    .content { margin-left: var(--sidebar-width); padding: 2rem; transition: all 0.3s ease; min-height: 100vh; }

    .content.expanded {
      margin-left: 0;
    }

    /* Header */
    .header { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px); padding: 1.5rem 2rem; border-radius: var(--border-radius); box-shadow: 0 8px 32px rgba(0,0,0,0.1); margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(255,255,255,0.2); }

    .dark-mode .header { background: rgba(44, 62, 80, 0.9); color: var(--text-light); border: 1px solid rgba(255,255,255,0.1); }

    .hamburger { font-size: 1.5rem; cursor: pointer; padding: 0.75rem; border-radius: 0.5rem; transition: all 0.3s; background: rgba(0,0,0,0.05); display: none; }
    .hamburger:hover { background: rgba(0,0,0,0.1); }

    .header-title {
      flex: 1;
      text-align: center;
    }

    .header-title h1 {
      margin: 0;
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--text-dark);
    }

    .dark-mode .header-title h1 {
      color: var(--text-light);
    }

    .system-title { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-size: 1.8rem; font-weight: 800; }

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

    /* Dashboard Cards */
    .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }

    .card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.2); border-radius: var(--border-radius); box-shadow: 0 8px 32px rgba(0,0,0,0.1); padding: 2rem; transition: all 0.3s; position: relative; overflow: hidden; }
    .card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .card:nth-child(2)::before { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .card:nth-child(3)::before { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .card:nth-child(4)::before { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }

    .dark-mode .card { background: rgba(44, 62, 80, 0.9); color: var(--text-light); border: 1px solid rgba(255,255,255,0.1); }

    .card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }

    .card h3 {
      font-size: 1rem;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 1rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .dark-mode .card h3 {
      color: var(--text-light);
    }

    .stat-value { font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .card:nth-child(2) .stat-value { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .card:nth-child(3) .stat-value { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .card:nth-child(4) .stat-value { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

    .stat-label {
      font-size: 0.875rem;
      color: #6c757d;
      font-weight: 500;
    }

    .dark-mode .stat-label {
      color: #adb5bd;
    }

    /* Calendar Section */
    .calendar-section {
      background-color: rgba(255,255,255,0.9);
      padding: 1.5rem;
      border-radius: var(--border-radius);
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
      margin-bottom: 1.5rem;
      border: 1px solid rgba(255,255,255,0.2);
    }

    .dark-mode .calendar-section {
      background-color: rgba(44, 62, 80, 0.9);
      color: var(--text-light);
      border: 1px solid rgba(255,255,255,0.1);
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }

    .calendar-nav {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 1px;
      background-color: #ddd;
      border-radius: var(--border-radius);
      overflow: hidden;
    }

    .calendar-day {
      background-color: white;
      padding: 0.5rem;
      min-height: 80px;
      text-align: center;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .dark-mode .calendar-day {
      background-color: var(--dark-card);
      color: var(--text-light);
    }

    .calendar-day.other-month {
      background-color: #f8f9fa;
      color: #6c757d;
    }

    .dark-mode .calendar-day.other-month {
      background-color: #2a3a5a;
      color: #6c757d;
    }

    .calendar-day.today {
      background-color: var(--primary-color);
      color: white;
      font-weight: bold;
    }

    .calendar-day.has-schedule {
      background-color: var(--success-color);
      color: white;
      font-weight: bold;
    }

    .schedule-indicator {
      font-size: 0.7rem;
      margin-top: 0.25rem;
    }

    /* Search Section - User Specific */
    .search-section { background: rgba(255,255,255,0.9); border: 1px solid rgba(255,255,255,0.2); border-radius: var(--border-radius); box-shadow: 0 8px 32px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 1.5rem; }
    .dark-mode .search-section { background: rgba(44, 62, 80, 0.9); color: var(--text-light); border: 1px solid rgba(255,255,255,0.1); }

    .filter-tabs {
      margin-top: 1rem;
    }

    .filter-tab {
      background: none;
      border: 2px solid var(--primary-color);
      color: var(--primary-color);
      padding: 0.5rem 1rem;
      margin-right: 0.5rem;
      margin-bottom: 0.5rem;
      border-radius: 20px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 0.9rem;
    }

    .filter-tab.active, .filter-tab:hover {
      background: var(--primary-color);
      color: white;
    }

    /* Table Section */
    .table-section { background: rgba(255,255,255,0.9); border: 1px solid rgba(255,255,255,0.2); border-radius: var(--border-radius); box-shadow: 0 8px 32px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 1.5rem; }
    .dark-mode .table-section { background: rgba(44, 62, 80, 0.9); color: var(--text-light); border: 1px solid rgba(255,255,255,0.1); }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      padding: 0.75rem;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    .dark-mode th,
    .dark-mode td {
      border-bottom-color: #3a4b6e;
    }

    thead {
      background-color: var(--primary-color);
      color: white;
    }

    .btn {
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 4px;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-primary {
      background-color: var(--primary-color);
      color: white;
    }

    .btn-primary:hover {
      background-color: #3a5bc7;
    }

    .btn-success {
      background-color: var(--success-color);
      color: white;
    }

    .btn-info {
      background-color: var(--info-color);
      color: white;
    }

    /* Responsive */
    @media (max-width: 992px) {
      .sidebar {
        transform: translateX(-100%);
        box-shadow: 2px 0 20px rgba(0,0,0,0.3);
      }
      
      .sidebar.show {
        transform: translateX(0);
      }
      
      .content {
        margin-left: 0;
        padding: 1rem;
      }

      .hamburger {
        display: block;
      }

      .dashboard-cards {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
      }

      .sidebar-nav {
        padding: 0 0.75rem;
      }

      .sidebar-nav .nav-link {
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
      }

      .sidebar-nav .nav-link i {
        font-size: 1rem;
        margin-right: 0.5rem;
      }
    }

    @media (max-width: 576px) {
      .sidebar {
        width: 100%;
        max-width: 320px;
      }

      .dashboard-cards {
        grid-template-columns: 1fr;
      }

      .header {
        padding: 1rem;
        flex-direction: column;
        gap: 1rem;
        text-align: center;
      }

      .sidebar-nav .nav-link {
        padding: 1rem;
        font-size: 1rem;
      }

      .sidebar-nav .nav-link i {
        font-size: 1.1rem;
        margin-right: 0.75rem;
      }
    }
  </style>
</head>
<body>
  <!-- Modern Loading Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
    <div class="loading-container">
      <img src="slatelogo.png" alt="SLATE Logo" class="loading-logo">
      <div class="loading-spinner"></div>
      <div class="loading-text" id="loadingText">Loading...</div>
      <div class="loading-subtext" id="loadingSubtext">Please wait while we prepare your transit schedules</div>
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

  <div class="sidebar" id="sidebar">
    <div class="logo">
      <img src="slatelogo.png" alt="SLATE Logo">
    </div>
    <div class="system-name">CORE II</div>
    <div class="sidebar-nav">
      <div class="nav-item">
        <a href="user-dashboard.php" class="nav-link">
          <i class="bi bi-speedometer2"></i>
          Dashboard
        </a>
      </div>
      
      <div class="nav-item">
        <a href="rate-tariff.php" class="nav-link">
          <span class="peso-icon">₱</span>
          Rate & Tariff
        </a>
      </div>
      <div class="nav-item">
        <a href="user-schedules.php" class="nav-link active">
          <i class="bi bi-calendar-week"></i>
          Transit Schedules
        </a>
      </div>
      
      <div class="nav-item">
        <a href="user-profile.php" class="nav-link user-feature">
          <i class="bi bi-person-circle"></i>
          My Profile
        </a>
      </div>
      <div class="nav-item">
        <a href="user-requests.php" class="nav-link user-feature">
          <i class="bi bi-list-check"></i>
          My Requests
        </a>
      </div>
    </div>
    <div class="sidebar-footer">
      <a href="#" class="nav-link" onclick="confirmLogout()">
        <i class="bi bi-box-arrow-right"></i>
        Logout
      </a>
    </div>
  </div>

  <div class="content" id="mainContent">
    <div class="header">
      <div class="hamburger" id="hamburger">☰</div>
      <div class="header-title">
        <h1>Transit Schedules <span class="system-title">| CORE II </span></h1>
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

    <div class="dashboard-cards">
      <div class="card">
        <h3>Active Routes</h3>
        <div class="stat-value" id="activeSchedules">0</div>
        <div class="stat-label">Currently operational</div>
      </div>

      <div class="card">
        <h3>Total Schedules</h3>
        <div class="stat-value" id="totalRoutes">0</div>
        <div class="stat-label">Available timetables</div>
      </div>

      <div class="card">
        <h3>On-Time Performance</h3>
        <div class="stat-value" id="onTimePerformance">0%</div>
        <div class="stat-label">This week</div>
      </div>

      <div class="card">
        <h3>Next Departure</h3>
        <div class="stat-value" id="nextDeparture">--:--</div>
        <div class="stat-label">From nearest stop</div>
      </div>
    </div>

    <div class="calendar-section">
      <div class="calendar-header">
        <h3>Schedule Calendar</h3>
        <div class="calendar-nav">
          <button id="prevMonth" class="btn btn-secondary">←</button>
          <span id="currentMonth" style="font-weight: bold; padding: 0.5rem;">December 2024</span>
          <button id="nextMonth" class="btn btn-secondary">→</button>
        </div>
      </div>
      <div id="calendarGrid" class="calendar-grid">
        <!-- Calendar will be generated here -->
      </div>
    </div>

    <!-- Search and Filter Section for Users -->
    <div class="search-section">
      <h3><i class="bi bi-search"></i> Find Transit Schedules</h3>
      <div class="row mt-3">
        <div class="col-md-8">
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" id="searchInput" placeholder="Search routes, destinations, or times...">
          </div>
        </div>
        <div class="col-md-4">
          <select class="form-select" id="routeFilter">
            <option value="">All Routes</option>
            <option value="North-South Express">North-South Express</option>
            <option value="East-West Connector">East-West Connector</option>
            <option value="Airport Shuttle">Airport Shuttle</option>
            <option value="City Loop">City Loop</option>
          </select>
        </div>
      </div>
      
      <div class="filter-tabs">
        <button class="filter-tab active" data-status="">All Services</button>
        <button class="filter-tab" data-status="Active">Active Only</button>
        <button class="filter-tab" data-status="Delayed">Delayed</button>
        <button class="filter-tab" data-vehicle="Bus">Buses</button>
        <button class="filter-tab" data-vehicle="Train">Trains</button>
        <button class="filter-tab" data-vehicle="Shuttle">Shuttles</button>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3" style="padding: 0 2rem;">
      <h3>Available Schedules</h3>
    </div>

    <div class="table-section">
      <div class="table-responsive">
        <table id="schedulesTable" class="table table-hover">
          <thead>
            <tr>
              <th>Route</th>
              <th>Schedule Name</th>
              <th>Vehicle Type</th>
              <th>Departure</th>
              <th>Arrival</th>
              <th>Frequency</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="schedulesTableBody">
            <!-- Schedule data will be loaded here -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- View Schedule Modal -->
  <div class="modal fade" id="viewScheduleModal" tabindex="-1" aria-labelledby="viewScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewScheduleModalLabel">Schedule Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <p><strong>Route:</strong> <span id="viewScheduleRoute"></span></p>
              <p><strong>Schedule Name:</strong> <span id="viewScheduleName"></span></p>
              <p><strong>Vehicle Type:</strong> <span id="viewScheduleVehicleType"></span></p>
              <p><strong>Capacity:</strong> <span id="viewScheduleCapacity"></span> passengers</p>
            </div>
            <div class="col-md-6">
              <p><strong>Departure:</strong> <span id="viewScheduleDeparture"></span></p>
              <p><strong>Arrival:</strong> <span id="viewScheduleArrival"></span></p>
              <p><strong>Frequency:</strong> <span id="viewScheduleFrequency"></span></p>
              <p><strong>Status:</strong> <span id="viewScheduleStatus"></span></p>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <p><strong>Operating Period:</strong> <span id="viewSchedulePeriod"></span></p>
              <p><strong>Notes:</strong></p>
              <p id="viewScheduleNotes"></p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" onclick="planTrip()">
            <i class="bi bi-geo-alt"></i> Plan Trip
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    const API_BASE = 'api/schedules.php';
    let schedules = [];
    let filteredSchedules = [];
    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();

    // Initialize the application
    document.addEventListener('DOMContentLoaded', function() {
      showLoading('Initializing Schedules...', 'Loading timetable data and calendar components');
      
      // Simulate loading time for better UX
      setTimeout(() => {
        initializeEventListeners();
        applyStoredTheme();
        fetchSchedules();
        
        // Hide loading after everything is ready
        setTimeout(() => {
          hideLoading();
        }, 500);
      }, 1500);
    });

    function showLoading(text, subtext) {
      const overlay = document.getElementById('loadingOverlay');
      const loadingText = document.getElementById('loadingText');
      const loadingSubtext = document.getElementById('loadingSubtext');
      
      if (loadingText) loadingText.textContent = text;
      if (loadingSubtext) loadingSubtext.textContent = subtext;
      if (overlay) overlay.classList.add('show');
    }

    function hideLoading() {
      const overlay = document.getElementById('loadingOverlay');
      if (overlay) overlay.classList.remove('show');
    }

    async function fetchSchedules() {
      try {
        const res = await fetch(API_BASE);
        const data = await res.json();
        schedules = Array.isArray(data) ? data.map(dbToUi) : [];
        filteredSchedules = [...schedules];
        loadSchedules();
        generateCalendar();
        updateDashboardStats();
      } catch (e) {
        console.error('Failed to load schedules:', e);
        showNotification('Failed to load schedules', 'error');
      }
    }

    function dbToUi(row) {
      return {
        id: parseInt(row.id),
        name: row.name,
        route: row.route,
        vehicleType: row.vehicle_type,
        departure: row.departure,
        arrival: row.arrival,
        frequency: row.frequency,
        status: row.status,
        startDate: row.start_date,
        endDate: row.end_date,
        capacity: parseInt(row.capacity),
        notes: row.notes || ''
      };
    }

    function initializeEventListeners() {
      // Theme toggle
      document.getElementById('themeToggle').addEventListener('change', function() {
        document.body.classList.toggle('dark-mode', this.checked);
        localStorage.setItem('theme', this.checked ? 'dark' : 'light');
      });

      // Sidebar toggle
      document.getElementById('hamburger').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        
        sidebar.classList.toggle('show');
        mainContent.classList.toggle('expanded');
      });

      // Close sidebar when clicking outside on mobile
      document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const hamburger = document.getElementById('hamburger');
        
        if (window.innerWidth <= 992 && 
            !sidebar.contains(e.target) && 
            !hamburger.contains(e.target) &&
            sidebar.classList.contains('show')) {
          sidebar.classList.remove('show');
        }
      });

      // Search functionality
      document.getElementById('searchInput').addEventListener('input', filterSchedules);
      document.getElementById('routeFilter').addEventListener('change', filterSchedules);

      // Filter tabs
      document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
          document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
          this.classList.add('active');
          filterSchedules();
        });
      });

      // Calendar navigation
      document.getElementById('prevMonth').addEventListener('click', function() {
        currentMonth--;
        if (currentMonth < 0) {
          currentMonth = 11;
          currentYear--;
        }
        generateCalendar();
      });

      document.getElementById('nextMonth').addEventListener('click', function() {
        currentMonth++;
        if (currentMonth > 11) {
          currentMonth = 0;
          currentYear++;
        }
        generateCalendar();
      });
    }

    function applyStoredTheme() {
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        document.getElementById('themeToggle').checked = true;
      }
    }

    function filterSchedules() {
      const searchTerm = document.getElementById('searchInput').value.toLowerCase();
      const routeFilter = document.getElementById('routeFilter').value;
      const activeTab = document.querySelector('.filter-tab.active');
      const statusFilter = activeTab.dataset.status;
      const vehicleFilter = activeTab.dataset.vehicle;

      filteredSchedules = schedules.filter(schedule => {
        const matchesSearch = !searchTerm || 
          schedule.name.toLowerCase().includes(searchTerm) ||
          schedule.route.toLowerCase().includes(searchTerm) ||
          schedule.vehicleType.toLowerCase().includes(searchTerm);

        const matchesRoute = !routeFilter || schedule.route === routeFilter;
        const matchesStatus = !statusFilter || schedule.status === statusFilter;
        const matchesVehicle = !vehicleFilter || schedule.vehicleType === vehicleFilter;

        return matchesSearch && matchesRoute && matchesStatus && matchesVehicle;
      });

      loadSchedules();
    }

    function loadSchedules() {
      const tbody = document.getElementById('schedulesTableBody');
      
      if (filteredSchedules.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No schedules found matching your criteria</td></tr>';
        return;
      }

      tbody.innerHTML = filteredSchedules.map(schedule => `
        <tr>
          <td><strong>${schedule.route}</strong></td>
          <td>${schedule.name}</td>
          <td><span class="badge bg-info">${schedule.vehicleType}</span></td>
          <td><i class="bi bi-clock"></i> ${schedule.departure}</td>
          <td><i class="bi bi-clock"></i> ${schedule.arrival}</td>
          <td>${schedule.frequency}</td>
          <td><span class="badge bg-${getStatusColor(schedule.status)}">${schedule.status}</span></td>
          <td>
            <button class="btn btn-sm btn-primary" onclick="viewSchedule(${schedule.id})" title="View Details">
              <i class="bi bi-eye"></i>
            </button>
          </td>
        </tr>
      `).join('');
    }

    function getStatusColor(status) {
      switch(status.toLowerCase()) {
        case 'active': return 'success';
        case 'delayed': return 'warning';
        case 'maintenance': return 'danger';
        case 'inactive': return 'secondary';
        default: return 'secondary';
      }
    }

    function updateDashboardStats() {
      const activeSchedules = schedules.filter(s => s.status === 'Active');
      const onTimeCount = schedules.filter(s => s.status === 'Active').length;
      const totalCount = schedules.length;
      const onTimePercentage = totalCount > 0 ? Math.round((onTimeCount / totalCount) * 100) : 0;
      
      document.getElementById('activeSchedules').textContent = activeSchedules.length;
      document.getElementById('totalRoutes').textContent = totalCount;
      document.getElementById('onTimePerformance').textContent = onTimePercentage + '%';
      
      // Simulate next departure time
      const now = new Date();
      const nextHour = new Date(now.getTime() + 15 * 60000); // 15 minutes from now
      document.getElementById('nextDeparture').textContent = 
        nextHour.toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit', hour12: false});
    }

    function generateCalendar() {
      const monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"];
      
      document.getElementById('currentMonth').textContent = `${monthNames[currentMonth]} ${currentYear}`;
      
      const firstDay = new Date(currentYear, currentMonth, 1).getDay();
      const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
      const today = new Date();
      
      let html = '';
      
      // Add day headers
      ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(day => {
        html += `<div class="calendar-day" style="background: var(--primary-color); color: white; font-weight: bold;">${day}</div>`;
      });
      
      // Add empty cells for days before month starts
      for (let i = 0; i < firstDay; i++) {
        html += '<div class="calendar-day other-month"></div>';
      }
      
      // Add days of the month
      for (let day = 1; day <= daysInMonth; day++) {
        const isToday = today.getDate() === day && 
                       today.getMonth() === currentMonth && 
                       today.getFullYear() === currentYear;
        
        const classes = ['calendar-day'];
        if (isToday) classes.push('today');
        
        html += `<div class="${classes.join(' ')}" onclick="selectDate(${day})">${day}</div>`;
      }
      
      document.getElementById('calendarGrid').innerHTML = html;
    }

    function selectDate(day) {
      Swal.fire({
        title: 'View Schedules',
        text: `Show schedules for ${currentMonth + 1}/${day}/${currentYear}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, show schedules',
        confirmButtonColor: '#4e73df'
      }).then((result) => {
        if (result.isConfirmed) {
          // Filter schedules for selected date
          filterSchedulesByDate(day);
        }
      });
    }

    function filterSchedulesByDate(day) {
      // This would typically filter by the selected date
      // For demo purposes, we'll just show all active schedules
      document.querySelector('.filter-tab[data-status="Active"]').click();
      
      Swal.fire({
        title: 'Schedules Found',
        text: `Showing active schedules for the selected date`,
        icon: 'success',
        confirmButtonColor: '#4e73df'
      });
    }

    function viewSchedule(scheduleId) {
      const schedule = schedules.find(s => s.id === scheduleId);
      if (!schedule) return;

      document.getElementById('viewScheduleRoute').textContent = schedule.route;
      document.getElementById('viewScheduleName').textContent = schedule.name;
      document.getElementById('viewScheduleVehicleType').textContent = schedule.vehicleType;
      document.getElementById('viewScheduleCapacity').textContent = schedule.capacity;
      document.getElementById('viewScheduleDeparture').textContent = schedule.departure;
      document.getElementById('viewScheduleArrival').textContent = schedule.arrival;
      document.getElementById('viewScheduleFrequency').textContent = schedule.frequency;
      document.getElementById('viewScheduleStatus').textContent = schedule.status;
      document.getElementById('viewSchedulePeriod').textContent = `${schedule.startDate} to ${schedule.endDate}`;
      document.getElementById('viewScheduleNotes').textContent = schedule.notes || 'No additional notes available.';

      new bootstrap.Modal(document.getElementById('viewScheduleModal')).show();
    }

    function planTrip() {
      Swal.fire({
        title: 'Trip Planner',
        html: `
          <div class="text-start">
            <div class="mb-3">
              <label class="form-label">From:</label>
              <input type="text" class="form-control" id="tripFrom" placeholder="Enter starting location">
            </div>
            <div class="mb-3">
              <label class="form-label">To:</label>
              <input type="text" class="form-control" id="tripTo" placeholder="Enter destination">
            </div>
            <div class="mb-3">
              <label class="form-label">When:</label>
              <input type="datetime-local" class="form-control" id="tripWhen">
            </div>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Plan Trip',
        confirmButtonColor: '#4e73df',
        preConfirm: () => {
          const from = document.getElementById('tripFrom').value;
          const to = document.getElementById('tripTo').value;
          
          if (!from || !to) {
            Swal.showValidationMessage('Please enter both locations');
            return false;
          }
          
          return { from, to };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Trip Planned!',
            text: `Your trip from ${result.value.from} to ${result.value.to} has been planned. Check your requests for details.`,
            icon: 'success',
            confirmButtonColor: '#4e73df'
          });
        }
      });
    }

    function showNotification(message, type = 'info') {
      Swal.fire({
        text: message,
        icon: type,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
      });
    }

    function confirmLogout() {
      Swal.fire({
        title: 'Confirm Logout',
        text: 'Are you sure you want to log out?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Logout',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'auth.php?logout=1';
        }
      });
    }
  </script>
</body>
</html>
