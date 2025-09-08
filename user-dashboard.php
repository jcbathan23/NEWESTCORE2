<?php
require_once 'auth.php';
requireUser(); // Only regular users can access this page
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Dashboard | CORE II</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- Universal Logout SweetAlert -->
  <script src="includes/logout-sweetalert.js"></script>
  
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
      --danger-color: #e74a3b;
      --border-radius: 0.75rem;
      --shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
      --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      --gradient-info: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
      margin-right: 0.875rem;
      font-size: 1.1rem;
      width: 20px;
      text-align: center;
      transition: transform 0.3s ease;
    }

    .sidebar-nav .nav-link .peso-icon {
      display: inline-block;
      margin-right: 0.75rem;
      font-size: 1.1rem;
      width: 20px;
      text-align: center;
      font-weight: 700;
    }
    .user-feature {
      background: rgba(0,0,0,0.1);
      border-left: 4px solid rgba(255,255,255,0.4);
    }

    .user-feature:hover {
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
      border-radius: 0.75rem;
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
      background: var(--gradient-primary);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-size: 2.2rem;
      font-weight: 800;
    }

    /* Dashboard Cards */
    .dashboard-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .card {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: var(--border-radius);
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
      padding: 2rem;
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--gradient-primary);
    }

    .card:nth-child(2)::before { background: var(--gradient-success); }
    .card:nth-child(3)::before { background: var(--gradient-info); }
    .card:nth-child(4)::before { background: var(--gradient-warning); }

    .dark-mode .card {
      background: rgba(44, 62, 80, 0.9);
      color: var(--text-light);
      border: 1px solid rgba(255,255,255,0.1);
    }

    .card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .card h3 {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 1rem;
      color: #6c757d;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .stat-value {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
      background: var(--gradient-primary);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .card:nth-child(2) .stat-value {
      background: var(--gradient-success);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .card:nth-child(3) .stat-value {
      background: var(--gradient-info);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .card:nth-child(4) .stat-value {
      background: var(--gradient-warning);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .stat-label {
      font-size: 0.9rem;
      color: #6c757d;
      font-weight: 500;
    }

    /* Chart Section */
    .chart-section {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: var(--border-radius);
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .dark-mode .chart-section {
      background: rgba(44, 62, 80, 0.9);
      color: var(--text-light);
      border: 1px solid rgba(255,255,255,0.1);
    }

    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .chart-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-dark);
    }

    .dark-mode .chart-title {
      color: var(--text-light);
    }

    .chart-container {
      position: relative;
      height: 400px;
      width: 100%;
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
      gap: 0.75rem;
    }

    .theme-label {
      font-weight: 500;
      font-size: 0.9rem;
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
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    input:checked + .slider {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    input:checked + .slider:before {
      transform: translateX(26px);
    }

    /* Responsive */
    @media (max-width: 992px) {
      .sidebar {
        transform: translateX(-100%);
        box-shadow: 4px 0 20px rgba(0,0,0,0.3);
      }
      
      .sidebar.show {
        transform: translateX(0);
      }
      
      .content {
        margin-left: 0;
        padding: 1rem;
      }

      .dashboard-cards {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
      }

      .chart-container {
        height: 300px;
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
        flex-direction: column;
        gap: 1rem;
        text-align: center;
      }

      .chart-container {
        height: 250px;
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
      <div class="loading-subtext" id="loadingSubtext">Please wait while we prepare your user dashboard</div>
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
        <a href="#" class="nav-link active">
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
        <a href="user-schedules.php" class="nav-link">
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
      <a href="auth.php?logout=1" class="nav-link">
        <i class="bi bi-box-arrow-right"></i>
        Logout
      </a>
    </div>
  </div>

  <div class="content" id="mainContent">
    <div class="header">
      <div class="hamburger" id="hamburger">☰</div>
      <div>
        <h1>User Dashboard <span class="system-title">| CORE II</span></h1>
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
        <h3>Active Requests</h3>
        <div class="stat-value" id="cardActiveRequests">0</div>
        <div class="stat-label">Currently pending</div>
      </div>

      <div class="card">
        <h3>Completed Services</h3>
        <div class="stat-value" id="cardCompletedServices">0</div>
        <div class="stat-label">Total completed</div>
      </div>

      <div class="card">
        <h3>Total Spent</h3>
        <div class="stat-value" id="cardTotalSpent">₱0</div>
        <div class="stat-label">All time spending</div>
      </div>

      <div class="card">
        <h3>Satisfaction Rate</h3>
        <div class="stat-value" id="cardSatisfactionRate">0%</div>
        <div class="stat-label">Service quality</div>
      </div>
    </div>

    <!-- Quick Info Cards -->
    <div class="row mb-4">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h5><i class="bi bi-cloud-sun"></i> Weather Forecast</h5>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="text-primary">28°C</h3>
                <p class="mb-0">Manila, Philippines</p>
                <small class="text-muted">Partly Cloudy - Feels like 30°C</small>
              </div>
              <div class="text-end">
                <i class="bi bi-cloud-sun display-4 text-info"></i>
              </div>
            </div>
            <hr>
            <div class="row text-center">
              <div class="col-4">
                <small>Humidity</small>
                <div><strong>75%</strong></div>
              </div>
              <div class="col-4">
                <small>Wind</small>
                <div><strong>12 km/h</strong></div>
              </div>
              <div class="col-4">
                <small>Visibility</small>
                <div><strong>10 km</strong></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-calendar3"></i> Calendar</h5>
            <span class="badge bg-primary"><?php echo date('M Y'); ?></span>
          </div>
          <div class="card-body">
            <div class="text-center mb-3">
              <h6><?php echo date('l, F j, Y'); ?></h6>
            </div>
            <div class="d-flex justify-content-center mb-3">
              <div class="calendar-today">
                <div class="display-1 text-primary fw-bold"><?php echo date('j'); ?></div>
              </div>
            </div>
            <div class="text-center">
              <p class="mb-1"><i class="bi bi-calendar-check text-success"></i> No events today</p>
              <small class="text-muted">Your schedule is clear</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="chart-section">
      <div class="chart-header">
        <h2 class="chart-title">📊 My Service Usage Analytics</h2>
        <button class="btn btn-outline-primary btn-sm" onclick="refreshDashboard()">
          <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
      </div>
      <div class="chart-container">
        <canvas id="userChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Bootstrap JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Common Logout Functionality -->
  <script src="js/common-logout.js"></script>

  <script>
    let userChart;

    // Initialize the application
    document.addEventListener('DOMContentLoaded', function() {
      showLoading('Initializing User Dashboard...', 'Setting up your workspace');
      
      // Simulate loading time for better UX
      setTimeout(() => {
        initializeEventListeners();
        applyStoredTheme();
        refreshDashboard();
        initializeChart();
        
        // Hide loading after everything is ready
        setTimeout(() => {
          hideLoading();
        }, 500);
      }, 1500);
    });

    function initializeEventListeners() {
      // Theme toggle
      document.getElementById('themeToggle').addEventListener('change', function() {
        document.body.classList.toggle('dark-mode', this.checked);
        localStorage.setItem('theme', this.checked ? 'dark' : 'light');
        updateChartTheme();
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
        link.addEventListener('click', function(e) {
          // Don't show loading for current page
          if (this.classList.contains('active')) return;
          
          navLinks.forEach(l => l.classList.remove('active'));
          this.classList.add('active');
        });
      });

      // Note: Logout confirmation is handled by common-logout.js
    }

    function initializeChart() {
      const ctx = document.getElementById('userChart').getContext('2d');
      
      userChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: ['Fri', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu'],
          datasets: [{
            label: 'Daily Spending (₱)',
            data: [1200, 850, 1450, 920, 1680, 750, 1340],
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#4e73df',
            pointBorderColor: '#4e73df',
            pointRadius: 6,
            pointHoverRadius: 8,
            pointBorderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: {
            intersect: false,
            mode: 'index'
          },
          plugins: {
            legend: {
              display: true,
              position: 'top',
              align: 'start',
              labels: {
                usePointStyle: true,
                padding: 20,
                font: {
                  size: 12,
                  weight: '500'
                }
              }
            },
            tooltip: {
              backgroundColor: 'rgba(255, 255, 255, 0.95)',
              titleColor: '#2c3e50',
              bodyColor: '#2c3e50',
              borderColor: 'rgba(78, 115, 223, 0.3)',
              borderWidth: 1,
              cornerRadius: 8,
              displayColors: true,
              callbacks: {
                title: function(context) {
                  return context[0].label;
                },
                label: function(context) {
                  return 'Spending: ₱' + context.parsed.y.toLocaleString();
                }
              }
            }
          },
          scales: {
            x: {
              display: true,
              grid: {
                display: false
              },
              ticks: {
                color: '#6c757d',
                font: {
                  size: 11
                }
              }
            },
            y: {
              display: true,
              grid: {
                color: 'rgba(0, 0, 0, 0.1)',
                drawBorder: false
              },
              ticks: {
                color: '#6c757d',
                font: {
                  size: 11
                },
                callback: function(value) {
                  if (value >= 1000) {
                    return '₱' + (value / 1000).toFixed(1) + 'k';
                  }
                  return '₱' + value;
                }
              }
            }
          },
          elements: {
            point: {
              hoverBackgroundColor: '#4e73df'
            }
          },
          animation: {
            duration: 1500,
            easing: 'easeInOutQuart'
          }
        }
      });
    }

    function updateChartTheme() {
      const isDark = document.body.classList.contains('dark-mode');
      const textColor = isDark ? '#f8f9fa' : '#6c757d';
      const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
      const tooltipBg = isDark ? 'rgba(44, 62, 80, 0.95)' : 'rgba(255, 255, 255, 0.95)';
      const tooltipText = isDark ? '#f8f9fa' : '#2c3e50';

      if (userChart) {
        userChart.options.plugins.legend.labels.color = textColor;
        userChart.options.plugins.tooltip.backgroundColor = tooltipBg;
        userChart.options.plugins.tooltip.titleColor = tooltipText;
        userChart.options.plugins.tooltip.bodyColor = tooltipText;
        userChart.options.scales.x.ticks.color = textColor;
        userChart.options.scales.y.ticks.color = textColor;
        userChart.options.scales.y.grid.color = gridColor;
        userChart.update();
      }
    }

    function applyStoredTheme() {
      const stored = localStorage.getItem('theme');
      const isDark = stored === 'dark';
      document.body.classList.toggle('dark-mode', isDark);
      const toggle = document.getElementById('themeToggle');
      if (toggle) toggle.checked = isDark;
      updateChartTheme();
    }

    async function refreshDashboard() {
      try {
        const response = await fetch('api/user-dashboard-stats.php', {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.status === 'success') {
          const data = result.data;
          
          // Update cards with animation
          animateValue('cardActiveRequests', data.stats.activeRequests);
          animateValue('cardCompletedServices', data.stats.completedServices);
          animateValue('cardTotalSpent', '₱' + data.stats.totalSpent.toLocaleString());
          animateValue('cardSatisfactionRate', data.stats.satisfactionRate + '%');

          // Update chart with real data
          if (userChart && data.spendingData) {
            userChart.data.labels = data.spendingData.labels || ['Fri', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu'];
            userChart.data.datasets[0].data = data.spendingData.data || [1200, 850, 1450, 920, 1680, 750, 1340];
            userChart.update('active');
          }

          showNotification('Dashboard updated successfully!', 'success');
        } else {
          throw new Error(result.message || 'Failed to load dashboard data');
        }
      } catch (e) {
        console.error('Error loading dashboard data:', e);
        showNotification('Failed to load dashboard data', 'danger');
        
        // Fallback to mock data if API fails
        const mockData = {
          activeRequests: 2,
          completedServices: 15,
          totalSpent: 12500,
          satisfactionRate: 95
        };
        
        animateValue('cardActiveRequests', mockData.activeRequests);
        animateValue('cardCompletedServices', mockData.completedServices);
        animateValue('cardTotalSpent', '₱' + mockData.totalSpent.toLocaleString());
        animateValue('cardSatisfactionRate', mockData.satisfactionRate + '%');
        
        if (userChart) {
          const spendingData = [1200, 850, 1450, 920, 1680, 750, 1340];
          userChart.data.datasets[0].data = spendingData;
          userChart.update('active');
        }
      }
    }

    function animateValue(elementId, targetValue) {
      const element = document.getElementById(elementId);
      const startValue = parseInt(element.textContent.replace(/[^0-9]/g, '')) || 0;
      const isCurrency = elementId === 'cardTotalSpent';
      const target = isCurrency ? parseInt(targetValue.replace(/[^0-9]/g, '')) : targetValue;
      
      const duration = 1000;
      const startTime = performance.now();
      
      function updateValue(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const current = Math.floor(startValue + (target - startValue) * progress);
        
        if (isCurrency) {
          element.textContent = '₱' + current.toLocaleString();
        } else if (elementId === 'cardSatisfactionRate') {
          element.textContent = current + '%';
        } else {
          element.textContent = current;
        }
        
        if (progress < 1) {
          requestAnimationFrame(updateValue);
        }
      }
      
      requestAnimationFrame(updateValue);
    }

    function showNotification(message, type = 'info') {
      // Create notification element
      const notification = document.createElement('div');
      notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
      notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.1);';
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
  </script>
</body>
</html>
