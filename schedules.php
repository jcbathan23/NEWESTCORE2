<?php
session_start();
require_once 'auth.php';

// Allow admin, provider, and user access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userRole = $_SESSION['role'];
$isAdmin = ($userRole === 'admin');
$isProvider = ($userRole === 'provider');
$isUser = ($userRole === 'user');
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
  
  <!-- Universal Dark Mode Styles -->
  <?php include 'includes/dark-mode-styles.php'; ?>
  <title>Schedules & Transit Timetable | CORE II</title>
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
      50% { width: 70%; }
      100% { width: 100%; }
    }

    @keyframes dotPulse {
      0%, 80%, 100% { 
        transform: scale(0.8);
        opacity: 0.5;
      }
      40% { 
        transform: scale(1);
        opacity: 1;
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

    .hamburger { font-size: 1.5rem; cursor: pointer; padding: 0.75rem; border-radius: 0.5rem; transition: all 0.3s; background: rgba(0,0,0,0.05); }
    .hamburger:hover { background: rgba(0,0,0,0.1); }

    .system-title { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-size: 2.2rem; font-weight: 800; }

    /* Dashboard Cards */
    .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }

    .card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.2); border-radius: var(--border-radius); box-shadow: 0 8px 32px rgba(0,0,0,0.1); padding: 2rem; transition: all 0.3s; position: relative; overflow: hidden; }
    .card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .card:nth-child(2)::before { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .card:nth-child(3)::before { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .card:nth-child(4)::before { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }

    .dark-mode .card { background: rgba(44, 62, 80, 0.9); color: var(--text-light); border: 1px solid rgba(255,255,255,0.1); }

    .card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }

    .stat-value { font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .card:nth-child(2) .stat-value { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .card:nth-child(3) .stat-value { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .card:nth-child(4) .stat-value { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

    /* Form Section */
    .form-section { background: rgba(255,255,255,0.9); border: 1px solid rgba(255,255,255,0.2); border-radius: var(--border-radius); box-shadow: 0 8px 32px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 1.5rem; display: none; }
    .dark-mode .form-section { background: rgba(44, 62, 80, 0.9); color: var(--text-light); border: 1px solid rgba(255,255,255,0.1); }

    .dark-mode .form-section {
      background-color: var(--dark-card);
      color: var(--text-light);
    }

    .form-group {
      margin-bottom: 1rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
    }

    .dark-mode .form-group input,
    .dark-mode .form-group select,
    .dark-mode .form-group textarea {
      background-color: #2a3a5a;
      border-color: #3a4b6e;
      color: var(--text-light);
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 0.5rem;
      margin-top: 1rem;
    }

    /* Buttons */
    .btn {
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 4px;
      font-size: 1rem;
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

    .btn-secondary {
      background-color: #6c757d;
      color: white;
    }

    .btn-secondary:hover {
      background-color: #5a6268;
    }

    .btn-success {
      background-color: var(--success-color);
      color: white;
    }

    .btn-info {
      background-color: var(--info-color);
      color: white;
    }

    .btn-warning {
      background-color: var(--warning-color);
      color: white;
    }

    .btn-danger {
      background-color: var(--danger-color);
      color: white;
    }

    .toggle-form-btn {
      background-color: var(--primary-color);
      color: white;
      margin-bottom: 1.5rem;
    }

    .toggle-form-btn:hover {
      background-color: #3a5bc7;
    }

    /* Table Section */
    .table-section { background: rgba(255,255,255,0.9); border: 1px solid rgba(255,255,255,0.2); border-radius: var(--border-radius); box-shadow: 0 8px 32px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 1.5rem; }
    .dark-mode .table-section { background: rgba(44, 62, 80, 0.9); color: var(--text-light); border: 1px solid rgba(255,255,255,0.1); }

    .dark-mode .table-section {
      background-color: var(--dark-card);
      color: var(--text-light);
    }

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

    .action-buttons {
      display: flex;
      gap: 0.5rem;
    }

    /* Status badges */
    .status-active { background-color: var(--success-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; }
    .status-inactive { background-color: #6c757d; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; }
    .status-maintenance { background-color: var(--warning-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; }
    .status-delayed { background-color: var(--danger-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; }

    /* Calendar Section */
    .calendar-section {
      background-color: white;
      padding: 1.5rem;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      margin-bottom: 1.5rem;
    }

    .dark-mode .calendar-section {
      background-color: var(--dark-card);
      color: var(--text-light);
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }

    .calendar-nav {
      display: flex;
      gap: 0.5rem;
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
      <div class="loading-subtext" id="loadingSubtext">Please wait while we prepare schedule data and calendar</div>
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

  <?php include 'includes/sidebar.php'; ?>

  <div class="content" id="mainContent">
    <div class="header">
      <div class="hamburger" id="hamburger">☰</div>
      <div>
        <h1>Schedules & Transit Timetable <span class="system-title">| CORE II </span></h1>
      </div>
      <div class="header-controls">
        <a href="admin.php" class="btn btn-outline-primary btn-sm me-2">
          <i class="bi bi-shield-lock"></i>
          Admin
        </a>
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
        <h3>Active Schedules</h3>
        <div class="stat-value" id="activeSchedules">0</div>
        <div class="stat-label">Current timetables</div>
      </div>

      <div class="card">
        <h3>Total Routes</h3>
        <div class="stat-value" id="totalRoutes">0</div>
        <div class="stat-label">Scheduled routes</div>
      </div>

      <div class="card">
        <h3>On-Time Performance</h3>
        <div class="stat-value" id="onTimePerformance">0%</div>
        <div class="stat-label">This week</div>
      </div>

      <div class="card">
        <h3>Delayed Services</h3>
        <div class="stat-value" id="delayedServices">0</div>
        <div class="stat-label">Currently delayed</div>
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

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>Schedule Management</h3>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#scheduleModal" onclick="openAddModal()">
        <i class="bi bi-plus-circle"></i> Add New Schedule
      </button>
    </div>

    <div class="table-section">
      <div class="table-responsive">
        <table id="schedulesTable" class="table table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Schedule Name</th>
              <th>Route</th>
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



  <!-- Schedule Modal -->
  <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="scheduleModalLabel">Add New Schedule</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="scheduleForm">
            <input type="hidden" id="scheduleId">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="scheduleName" class="form-label">Schedule Name *</label>
                  <input type="text" class="form-control" id="scheduleName" name="scheduleName" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="routeName" class="form-label">Route Name *</label>
                  <select class="form-select" id="routeName" name="routeName" required>
                    <option value="">Select Route</option>
                    <option value="North-South Express">North-South Express</option>
                    <option value="East-West Connector">East-West Connector</option>
                    <option value="Airport Shuttle">Airport Shuttle</option>
                    <option value="City Loop">City Loop</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="vehicleType" class="form-label">Vehicle Type *</label>
                  <select class="form-select" id="vehicleType" name="vehicleType" required>
                    <option value="">Select Vehicle Type</option>
                    <option value="Bus">Bus</option>
                    <option value="Train">Train</option>
                    <option value="Shuttle">Shuttle</option>
                    <option value="Metro">Metro</option>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="frequency" class="form-label">Frequency *</label>
                  <select class="form-select" id="frequency" name="frequency" required>
                    <option value="">Select Frequency</option>
                    <option value="Daily">Daily</option>
                    <option value="Weekdays">Weekdays Only</option>
                    <option value="Weekends">Weekends Only</option>
                    <option value="Custom">Custom Schedule</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="departureTime" class="form-label">Departure Time *</label>
                  <input type="time" class="form-control" id="departureTime" name="departureTime" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="arrivalTime" class="form-label">Arrival Time *</label>
                  <input type="time" class="form-control" id="arrivalTime" name="arrivalTime" required>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="startDate" class="form-label">Start Date *</label>
                  <input type="date" class="form-control" id="startDate" name="startDate" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="endDate" class="form-label">End Date *</label>
                  <input type="date" class="form-control" id="endDate" name="endDate" required>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="capacity" class="form-label">Vehicle Capacity *</label>
                  <input type="number" class="form-control" id="capacity" name="capacity" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="scheduleStatus" class="form-label">Status *</label>
                  <select class="form-select" id="scheduleStatus" name="scheduleStatus" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Delayed">Delayed</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <div class="mb-3">
                  <label for="scheduleNotes" class="form-label">Notes</label>
                  <textarea class="form-control" id="scheduleNotes" name="scheduleNotes" rows="3"></textarea>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveSchedule()">Save Schedule</button>
        </div>
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
              <p><strong>ID:</strong> <span id="viewScheduleId"></span></p>
              <p><strong>Name:</strong> <span id="viewScheduleName"></span></p>
              <p><strong>Route:</strong> <span id="viewScheduleRoute"></span></p>
              <p><strong>Vehicle Type:</strong> <span id="viewScheduleVehicleType"></span></p>
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
              <p><strong>Notes:</strong></p>
              <p id="viewScheduleNotes"></p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete schedule <strong id="deleteScheduleName"></strong>?</p>
          <p class="text-danger">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete Schedule</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    const API_BASE = 'api/schedules.php';
    let schedules = [];
    let currentScheduleId = null;
    let isEditMode = false;
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

    async function fetchSchedules() {
      try {
        const res = await fetch(API_BASE);
        const data = await res.json();
        schedules = Array.isArray(data) ? data.map(dbToUi) : [];
        loadSchedules();
        generateCalendar();
        updateDashboardStats();
      } catch (e) {
        showNotification('Failed to load schedules', 'danger');
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

    function uiToDb(s) {
      return {
        name: s.name,
        route: s.route,
        vehicleType: s.vehicleType,
        departure: s.departure,
        arrival: s.arrival,
        frequency: s.frequency,
        status: s.status,
        startDate: s.startDate,
        endDate: s.endDate,
        capacity: s.capacity,
        notes: s.notes || ''
      };
    }

    function initializeEventListeners() {
      // Theme toggle
      document.getElementById('themeToggle').addEventListener('change', function() {
        document.body.classList.toggle('dark-mode', this.checked);
        localStorage.setItem('theme', this.checked ? 'dark' : 'light');
      });

      // Enhanced sidebar toggle with smooth animations
      document.getElementById('hamburger').addEventListener('click', function() {
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

      // Close sidebar when clicking outside on mobile
      document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const hamburger = document.getElementById('hamburger');
        
        if (window.innerWidth <= 992 && 
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
      const stored = localStorage.getItem('theme');
      const isDark = stored === 'dark';
      document.body.classList.toggle('dark-mode', isDark);
      const toggle = document.getElementById('themeToggle');
      if (toggle) toggle.checked = isDark;
    }

    function loadSchedules() {
      const tbody = document.getElementById('schedulesTableBody');
      tbody.innerHTML = '';

      schedules.forEach(schedule => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${schedule.id}</td>
          <td>${schedule.name}</td>
          <td>${schedule.route}</td>
          <td>${schedule.vehicleType}</td>
          <td>${schedule.departure}</td>
          <td>${schedule.arrival}</td>
          <td>${schedule.frequency}</td>
          <td><span class="badge ${getStatusBadgeClass(schedule.status)}">${schedule.status}</span></td>
          <td>
            <div class="action-buttons">
              <button class="btn btn-sm btn-info" onclick="viewSchedule(${schedule.id})" title="View Details">
                <i class="bi bi-eye"></i>
              </button>
              <button class="btn btn-sm btn-primary" onclick="editSchedule(${schedule.id})" title="Edit Schedule">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-sm btn-warning" onclick="delaySchedule(${schedule.id})" title="Mark as Delayed">
                <i class="bi bi-clock"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteSchedule(${schedule.id})" title="Delete Schedule">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </td>
        `;
        tbody.appendChild(row);
      });
    }

    function getStatusBadgeClass(status) {
      switch(status) {
        case 'Active': return 'bg-success';
        case 'Inactive': return 'bg-secondary';
        case 'Maintenance': return 'bg-warning text-dark';
        case 'Delayed': return 'bg-danger';
        default: return 'bg-secondary';
      }
    }

    function updateDashboardStats() {
      const activeSchedules = schedules.filter(s => s.status === 'Active').length;
      const totalRoutes = new Set(schedules.map(s => s.route)).size;
      const delayedServices = schedules.filter(s => s.status === 'Delayed').length;
      // Basic heuristic for on-time performance: percent not delayed
      const onTimePerformance = schedules.length > 0 ? Math.round(((schedules.length - delayedServices) / schedules.length) * 100) + '%' : '0%';

      document.getElementById('activeSchedules').textContent = activeSchedules;
      document.getElementById('totalRoutes').textContent = totalRoutes;
      document.getElementById('onTimePerformance').textContent = onTimePerformance;
      document.getElementById('delayedServices').textContent = delayedServices;
    }

    function openAddModal() {
      isEditMode = false;
      currentScheduleId = null;
      document.getElementById('scheduleModalLabel').textContent = 'Add New Schedule';
      document.getElementById('scheduleForm').reset();
      document.getElementById('scheduleId').value = '';
    }

    function viewSchedule(id) {
      const schedule = schedules.find(s => s.id === id);
      if (!schedule) return;

      document.getElementById('viewScheduleId').textContent = schedule.id;
      document.getElementById('viewScheduleName').textContent = schedule.name;
      document.getElementById('viewScheduleRoute').textContent = schedule.route;
      document.getElementById('viewScheduleVehicleType').textContent = schedule.vehicleType;
      document.getElementById('viewScheduleDeparture').textContent = schedule.departure;
      document.getElementById('viewScheduleArrival').textContent = schedule.arrival;
      document.getElementById('viewScheduleFrequency').textContent = schedule.frequency;
      document.getElementById('viewScheduleStatus').textContent = schedule.status;
      document.getElementById('viewScheduleNotes').textContent = schedule.notes || 'No notes available';

      const viewModal = new bootstrap.Modal(document.getElementById('viewScheduleModal'));
      viewModal.show();
    }

    function editSchedule(id) {
      const schedule = schedules.find(s => s.id === id);
      if (!schedule) return;

      isEditMode = true;
      currentScheduleId = id;
      document.getElementById('scheduleModalLabel').textContent = 'Edit Schedule';
      
      // Populate form fields
      document.getElementById('scheduleId').value = schedule.id;
      document.getElementById('scheduleName').value = schedule.name;
      document.getElementById('routeName').value = schedule.route;
      document.getElementById('vehicleType').value = schedule.vehicleType;
      document.getElementById('departureTime').value = schedule.departure;
      document.getElementById('arrivalTime').value = schedule.arrival;
      document.getElementById('frequency').value = schedule.frequency;
      document.getElementById('startDate').value = schedule.startDate;
      document.getElementById('endDate').value = schedule.endDate;
      document.getElementById('capacity').value = schedule.capacity;
      document.getElementById('scheduleStatus').value = schedule.status;
      document.getElementById('scheduleNotes').value = schedule.notes || '';

      const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
      modal.show();
    }

    function delaySchedule(id) {
      const schedule = schedules.find(s => s.id === id);
      if (!schedule) return;

      schedule.status = 'Delayed';
      loadSchedules();
      updateDashboardStats();
      showNotification(`Schedule "${schedule.name}" marked as delayed!`, 'warning');
    }

    function deleteSchedule(id) {
      const schedule = schedules.find(s => s.id === id);
      if (!schedule) return;

      document.getElementById('deleteScheduleName').textContent = schedule.name;
      currentScheduleId = id;

      const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
      deleteModal.show();
    }

    async function confirmDelete() {
      if (!currentScheduleId) return;
      try {
        const res = await fetch(`${API_BASE}?id=${currentScheduleId}`, { method: 'DELETE' });
        if (!res.ok) throw new Error();
        await fetchSchedules();
        const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
        deleteModal.hide();
        showNotification('Schedule deleted successfully!', 'success');
      } catch (e) {
        showNotification('Failed to delete schedule', 'danger');
      }
    }

    async function saveSchedule() {
      const form = document.getElementById('scheduleForm');
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const formData = new FormData(form);
      const scheduleData = {
        name: formData.get('scheduleName'),
        route: formData.get('routeName'),
        vehicleType: formData.get('vehicleType'),
        departure: formData.get('departureTime'),
        arrival: formData.get('arrivalTime'),
        frequency: formData.get('frequency'),
        status: formData.get('scheduleStatus'),
        startDate: formData.get('startDate'),
        endDate: formData.get('endDate'),
        capacity: parseInt(formData.get('capacity')),
        notes: formData.get('scheduleNotes')
      };

      try {
        if (isEditMode && currentScheduleId) {
          const res = await fetch(`${API_BASE}?id=${currentScheduleId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(uiToDb(scheduleData))
          });
          if (!res.ok) throw new Error();
          showNotification('Schedule updated successfully!', 'success');
        } else {
          const res = await fetch(API_BASE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(uiToDb(scheduleData))
          });
          if (!res.ok) throw new Error();
          showNotification('Schedule added successfully!', 'success');
        }
        await fetchSchedules();
        const modal = bootstrap.Modal.getInstance(document.getElementById('scheduleModal'));
        modal.hide();
      } catch (e) {
        showNotification('Failed to save schedule', 'danger');
      }
    }

    function generateCalendar() {
      const calendarGrid = document.getElementById('calendarGrid');
      const currentMonthElement = document.getElementById('currentMonth');
      
      const firstDay = new Date(currentYear, currentMonth, 1);
      const lastDay = new Date(currentYear, currentMonth + 1, 0);
      const startDate = new Date(firstDay);
      startDate.setDate(startDate.getDate() - firstDay.getDay());
      
      currentMonthElement.textContent = new Date(currentYear, currentMonth).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
      
      calendarGrid.innerHTML = '';
      
      // Add day headers
      const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
      daysOfWeek.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.className = 'calendar-day';
        dayHeader.style.fontWeight = 'bold';
        dayHeader.style.backgroundColor = '#f8f9fa';
        dayHeader.textContent = day;
        calendarGrid.appendChild(dayHeader);
      });
      
      // Generate calendar days
      for (let i = 0; i < 42; i++) {
        const date = new Date(startDate);
        date.setDate(startDate.getDate() + i);
        
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        
        if (date.getMonth() !== currentMonth) {
          dayElement.classList.add('other-month');
        }
        
        if (date.toDateString() === new Date().toDateString()) {
          dayElement.classList.add('today');
        }
        
        // Check if date has schedules
        const hasSchedule = schedules.some(schedule => {
          const scheduleDate = new Date(schedule.startDate);
          return scheduleDate.toDateString() === date.toDateString();
        });
        
        if (hasSchedule) {
          dayElement.classList.add('has-schedule');
        }
        
        dayElement.innerHTML = `
          ${date.getDate()}
          ${hasSchedule ? '<div class="schedule-indicator">📅</div>' : ''}
        `;
        
        calendarGrid.appendChild(dayElement);
      }
    }

    function showNotification(message, type = 'info') {
      // Create notification element
      const notification = document.createElement('div');
      notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
      notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
      notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;
      
      document.body.appendChild(notification);
      
      // Auto remove after 3 seconds
      setTimeout(() => {
        if (notification.parentNode) {
          notification.remove();
        }
      }, 3000);
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
          showPageTransition('Logging out...', 'auth.php?logout=1');
        }
      });
    }

    // Loading Utility Functions
    function showLoading(text = 'Loading...', subtext = 'Please wait') {
      const overlay = document.getElementById('loadingOverlay');
      const loadingText = document.getElementById('loadingText');
      const loadingSubtext = document.getElementById('loadingSubtext');
      
      if (loadingText) loadingText.textContent = text;
      if (loadingSubtext) loadingSubtext.textContent = subtext;
      
      overlay.classList.add('show');
    }

    function hideLoading() {
      const overlay = document.getElementById('loadingOverlay');
      overlay.classList.remove('show');
    }

    function showPageTransition(text = 'Loading...', url = null) {
      showLoading(text, 'Preparing to navigate...');
      
      // Navigate after a short delay for smooth transition
      setTimeout(() => {
        if (url) {
          window.location.href = url;
        }
      }, 800);
    }
  </script>
</body>
</html>
