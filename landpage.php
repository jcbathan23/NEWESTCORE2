<?php
require_once 'auth.php';
requireLogin(); // Any logged in user can access this page

// Redirect users to their appropriate dashboards
if ($_SESSION['role'] === 'admin') {
    header('Location: admin.php');
    exit();
} elseif ($_SESSION['role'] === 'provider') {
    header('Location: provider-dashboard.php');
    exit();
} else {
    header('Location: user-dashboard.php');
    exit();
}
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
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    /* Page Transition Loading */
    .page-transition-loading {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }

    .page-transition-loading.show {
      opacity: 1;
      visibility: visible;
    }

    .transition-spinner {
      width: 50px;
      height: 50px;
      border: 4px solid rgba(255,255,255,0.3);
      border-top: 4px solid white;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    /* Card Loading Animation */
    .card.loading {
      position: relative;
      overflow: hidden;
    }

    .card.loading::after {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
      animation: cardShimmer 1.5s infinite;
    }

    @keyframes cardShimmer {
      0% { left: -100%; }
      100% { left: 100%; }
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
      font-size: 1.2rem;
      width: 24px;
      text-align: center;
    }
    .sidebar-nav .nav-link .peso-icon { display: inline-block; margin-right: 0.75rem; font-size: 1.2rem; width: 24px; text-align: center; font-weight: 700; }

    .admin-feature {
      background: rgba(0,0,0,0.1);
      border-left: 4px solid rgba(255,255,255,0.4);
    }

    .admin-feature:hover {
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
    .card:nth-child(5)::before { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .card:nth-child(6)::before { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .card:nth-child(7)::before { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .card:nth-child(8)::before { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }

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

    /* Loading animation */
    .loading {
      opacity: 0.6;
      pointer-events: none;
    }

    .loading::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 20px;
      height: 20px;
      margin: -10px 0 0 -10px;
      border: 2px solid #f3f3f3;
      border-top: 2px solid var(--primary-color);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
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
      <div class="loading-subtext" id="loadingSubtext">Please wait while we prepare your dashboard</div>
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

  <!-- Page Transition Loading -->
  <div class="page-transition-loading" id="pageTransitionLoading">
    <div class="transition-spinner"></div>
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
        <a href="service-provider.php" class="nav-link">
          <i class="bi bi-people"></i>
          Service Provider
        </a>
      </div>
      <div class="nav-item">
        <a href="service-network.php" class="nav-link">
          <i class="bi bi-diagram-3"></i>
          Service Network & Route Planner
        </a>
      </div>
      <div class="nav-item">
        <a href="rate-tariff.php" class="nav-link admin-feature">
          <span class="peso-icon">₱</span>
          Rate & Tariff
        </a>
      </div>
      <div class="nav-item">
        <a href="sop-manager.php" class="nav-link admin-feature">
          <i class="bi bi-journal-text"></i>
          SOP Manager
        </a>
      </div>
      <div class="nav-item">
        <a href="schedules.php" class="nav-link">
          <i class="bi bi-calendar-week"></i>
          Schedules & Transit Timetable
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
        <h1>Admin Dashboard <span class="system-title">| CORE II</span></h1>
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
        <h3>Total Providers</h3>
        <div class="stat-value" id="cardTotalProviders">0</div>
        <div class="stat-label">All service providers</div>
      </div>

      <div class="card">
        <h3>Active Providers</h3>
        <div class="stat-value" id="cardActiveProviders">0</div>
        <div class="stat-label">Currently active</div>
      </div>

      <div class="card">
        <h3>Total Routes</h3>
        <div class="stat-value" id="cardTotalRoutes">0</div>
        <div class="stat-label">Routes in system</div>
      </div>

      <div class="card">
        <h3>Service Points</h3>
        <div class="stat-value" id="cardServicePoints">0</div>
        <div class="stat-label">Network nodes</div>
    </div>

      <div class="card">
        <h3>Active Tariffs</h3>
        <div class="stat-value" id="cardActiveTariffs">0</div>
        <div class="stat-label">Current rates</div>
    </div>

      <div class="card">
        <h3>Active SOPs</h3>
        <div class="stat-value" id="cardActiveSOPs">0</div>
        <div class="stat-label">In use</div>
  </div>

      <div class="card">
        <h3>Active Schedules</h3>
        <div class="stat-value" id="cardActiveSchedules">0</div>
        <div class="stat-label">Current timetables</div>
  </div>

      <div class="card">
        <h3>Monthly Provider Spend</h3>
        <div class="stat-value" id="cardMonthlySpend">₱0</div>
        <div class="stat-label">Sum of active contracts</div>
    </div>

      <div class="card">
        <h3>Active Shipments</h3>
        <div class="stat-value" id="cardActiveShipments">0</div>
        <div class="stat-label">In transit</div>
      </div>

      <div class="card">
        <h3>Avg Performance</h3>
        <div class="stat-value" id="cardAvgPerformance">0%</div>
        <div class="stat-label">Provider quality</div>
      </div>

      <div class="card">
        <h3>Compliance Rate</h3>
        <div class="stat-value" id="cardComplianceRate">0%</div>
        <div class="stat-label">Document compliance</div>
      </div>

      <div class="card">
        <h3>On-Time Delivery</h3>
        <div class="stat-value" id="cardOnTimeDelivery">0%</div>
        <div class="stat-label">Schedule adherence</div>
      </div>
  </div>

    <div class="chart-section">
      <div class="chart-header">
        <h2 class="chart-title">System Overview</h2>
        <button class="btn btn-outline-primary btn-sm" onclick="refreshDashboard()">
          <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
        </div>
      <div class="chart-container">
        <canvas id="dashboardChart"></canvas>
      </div>
    </div>
  </div>



  <!-- Bootstrap JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <script>
    const API_PROVIDERS = 'api/providers.php';
    const API_ROUTES = 'api/routes.php';
    const API_POINTS = 'api/service-points.php';
    const API_TARIFFS = 'api/tariffs.php';
    const API_SOPS = 'api/sops.php';
    const API_SCHEDULES = 'api/schedules.php';

    let dashboardChart;

    // Initialize the application
    document.addEventListener('DOMContentLoaded', function() {
      showLoading('Initializing Dashboard...', 'Setting up your workspace');
      
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

      // Active link management with loading
      const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
      navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          // Don't show loading for current page
          if (this.classList.contains('active')) return;
          
          const href = this.getAttribute('href');
          if (href && href !== '#') {
            e.preventDefault();
            showPageTransition('Navigating...', href);
          }
          
          navLinks.forEach(l => l.classList.remove('active'));
          this.classList.add('active');
        });
      });

      // Logout confirmation with SweetAlert
      const logoutLinks = document.querySelectorAll('.sidebar-footer a[href="auth.php?logout=1"]');
      logoutLinks.forEach(l => {
        l.addEventListener('click', function(e) {
          e.preventDefault();
          confirmLogout();
        });
      });
    }

    function initializeChart() {
      const ctx = document.getElementById('dashboardChart').getContext('2d');
      
      dashboardChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: ['Providers', 'Routes', 'Service Points', 'Tariffs', 'SOPs', 'Schedules'],
          datasets: [{
            label: 'Total Items',
            data: [0, 0, 0, 0, 0, 0],
            backgroundColor: [
              'rgba(78, 115, 223, 0.8)',
              'rgba(28, 200, 138, 0.8)',
              'rgba(54, 185, 204, 0.8)',
              'rgba(246, 194, 62, 0.8)',
              'rgba(231, 74, 59, 0.8)',
              'rgba(102, 126, 234, 0.8)'
            ],
            borderColor: [
              'rgba(78, 115, 223, 1)',
              'rgba(28, 200, 138, 1)',
              'rgba(54, 185, 204, 1)',
              'rgba(246, 194, 62, 1)',
              'rgba(231, 74, 59, 1)',
              'rgba(102, 126, 234, 1)'
            ],
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              titleColor: 'white',
              bodyColor: 'white',
              borderColor: 'rgba(255, 255, 255, 0.2)',
              borderWidth: 1,
              cornerRadius: 8,
              displayColors: false,
              callbacks: {
                label: function(context) {
                  return `${context.label}: ${context.parsed.y} items`;
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(0, 0, 0, 0.1)',
                drawBorder: false
              },
              ticks: {
                color: '#6c757d',
                font: {
                  size: 12
                }
              }
            },
            x: {
              grid: {
                display: false
              },
              ticks: {
                color: '#6c757d',
                font: {
                  size: 12
                }
              }
            }
          },
          animation: {
            duration: 1000,
            easing: 'easeInOutQuart'
          }
        }
      });
    }

    function updateChartTheme() {
      const isDark = document.body.classList.contains('dark-mode');
      const textColor = isDark ? '#f8f9fa' : '#6c757d';
      const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

      if (dashboardChart) {
        dashboardChart.options.scales.y.ticks.color = textColor;
        dashboardChart.options.scales.x.ticks.color = textColor;
        dashboardChart.options.scales.y.grid.color = gridColor;
        dashboardChart.update();
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
      const cards = document.querySelectorAll('.card');
      cards.forEach(card => card.classList.add('loading'));

      try {
        const [providersRes, routesRes, pointsRes, tariffsRes, sopsRes, schedulesRes] = await Promise.all([
          fetch(API_PROVIDERS),
          fetch(API_ROUTES),
          fetch(API_POINTS),
          fetch(API_TARIFFS),
          fetch(API_SOPS),
          fetch(API_SCHEDULES)
        ]);

        const [providers, routes, points, tariffs, sops, schedules] = await Promise.all([
          providersRes.json(),
          routesRes.json(),
          pointsRes.json(),
          tariffsRes.json(),
          sopsRes.json(),
          schedulesRes.json()
        ]);

        const totalProviders = Array.isArray(providers) ? providers.length : 0;
        const activeProviders = Array.isArray(providers) ? providers.filter(p => p.status === 'Active').length : 0;
        const monthlySpend = Array.isArray(providers) ? providers
          .filter(p => p.status === 'Active')
          .reduce((sum, p) => sum + (parseFloat(p.monthly_rate) || 0), 0) : 0;

        const totalRoutes = Array.isArray(routes) ? routes.length : 0;
        const totalPoints = Array.isArray(points) ? points.length : 0;
        const activeTariffs = Array.isArray(tariffs) ? tariffs.filter(t => t.status === 'Active').length : 0;
        const activeSOPs = Array.isArray(sops) ? sops.filter(s => s.status === 'Active').length : 0;
        const activeSchedules = Array.isArray(schedules) ? schedules.filter(s => s.status === 'Active').length : 0;

        // Update cards with animation
        animateValue('cardTotalProviders', totalProviders);
        animateValue('cardActiveProviders', activeProviders);
        animateValue('cardTotalRoutes', totalRoutes);
        animateValue('cardServicePoints', totalPoints);
        animateValue('cardActiveTariffs', activeTariffs);
        animateValue('cardActiveSOPs', activeSOPs);
        animateValue('cardActiveSchedules', activeSchedules);
        animateValue('cardMonthlySpend', '₱' + monthlySpend.toLocaleString());

        // Update chart
        if (dashboardChart) {
          dashboardChart.data.datasets[0].data = [
            totalProviders,
            totalRoutes,
            totalPoints,
            activeTariffs,
            activeSOPs,
            activeSchedules
          ];
          dashboardChart.update('active');
        }

        showNotification('Dashboard updated successfully!', 'success');
      } catch (e) {
        console.error('Error loading dashboard data:', e);
        showNotification('Failed to load dashboard data', 'danger');
      } finally {
        cards.forEach(card => card.classList.remove('loading'));
      }
    }

    function animateValue(elementId, targetValue) {
      const element = document.getElementById(elementId);
      const startValue = parseInt(element.textContent.replace(/[^0-9]/g, '')) || 0;
      const isCurrency = elementId === 'cardMonthlySpend';
      const target = isCurrency ? parseInt(targetValue.replace(/[^0-9]/g, '')) : targetValue;
      
      const duration = 1000;
      const startTime = performance.now();
      
      function updateValue(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const current = Math.floor(startValue + (target - startValue) * progress);
        
        if (isCurrency) {
          element.textContent = '₱' + current.toLocaleString();
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
      const transition = document.getElementById('pageTransitionLoading');
      transition.classList.add('show');
      
      // Add text to transition if needed
      if (text) {
        const textElement = document.createElement('div');
        textElement.style.cssText = 'color: white; margin-top: 1rem; font-size: 1rem; font-weight: 500;';
        textElement.textContent = text;
        transition.appendChild(textElement);
      }
      
      // Navigate after a short delay for smooth transition
      setTimeout(() => {
        if (url) {
          window.location.href = url;
        }
      }, 800);
    }

    // Enhanced refresh dashboard with loading
    async function refreshDashboard() {
      showLoading('Refreshing Dashboard...', 'Updating system statistics');
      
      const cards = document.querySelectorAll('.card');
      cards.forEach(card => card.classList.add('loading'));

      try {
        const [providersRes, routesRes, pointsRes, tariffsRes, sopsRes, schedulesRes] = await Promise.all([
          fetch(API_PROVIDERS),
          fetch(API_ROUTES),
          fetch(API_POINTS),
          fetch(API_TARIFFS),
          fetch(API_SOPS),
          fetch(API_SCHEDULES)
        ]);

        const [providers, routes, points, tariffs, sops, schedules] = await Promise.all([
          providersRes.json(),
          routesRes.json(),
          pointsRes.json(),
          tariffsRes.json(),
          sopsRes.json(),
          schedulesRes.json()
        ]);

        const totalProviders = Array.isArray(providers) ? providers.length : 0;
        const activeProviders = Array.isArray(providers) ? providers.filter(p => p.status === 'Active').length : 0;
        const monthlySpend = Array.isArray(providers) ? providers
          .filter(p => p.status === 'Active')
          .reduce((sum, p) => sum + (parseFloat(p.monthly_rate) || 0), 0) : 0;

        const totalRoutes = Array.isArray(routes) ? routes.length : 0;
        const totalPoints = Array.isArray(points) ? points.length : 0;
        const activeTariffs = Array.isArray(tariffs) ? tariffs.filter(t => t.status === 'Active').length : 0;
        const activeSOPs = Array.isArray(sops) ? sops.filter(s => s.status === 'Active').length : 0;
        const activeSchedules = Array.isArray(schedules) ? schedules.filter(s => s.status === 'Active').length : 0;

        // Update cards with animation
        animateValue('cardTotalProviders', totalProviders);
        animateValue('cardActiveProviders', activeProviders);
        animateValue('cardTotalRoutes', totalRoutes);
        animateValue('cardServicePoints', totalPoints);
        animateValue('cardActiveTariffs', activeTariffs);
        animateValue('cardActiveSOPs', activeSOPs);
        animateValue('cardActiveSchedules', activeSchedules);
        animateValue('cardMonthlySpend', '₱' + monthlySpend.toLocaleString());

        // Update chart
        if (dashboardChart) {
          dashboardChart.data.datasets[0].data = [
            totalProviders,
            totalRoutes,
            totalPoints,
            activeTariffs,
            activeSOPs,
            activeSchedules
          ];
          dashboardChart.update('active');
        }

        showNotification('Dashboard updated successfully!', 'success');
      } catch (e) {
        console.error('Error loading dashboard data:', e);
        showNotification('Failed to load dashboard data', 'danger');
      } finally {
        cards.forEach(card => card.classList.remove('loading'));
        hideLoading();
      }
    }
  </script>
</body>
</html>