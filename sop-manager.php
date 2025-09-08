<?php
require_once 'auth.php';
requireAdmin(); // Only admin users can access this page
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
  <title>SOP Manager | CORE II</title>
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

    .sidebar-footer {
      position: absolute;
      bottom: 0;
      width: 100%;
      padding: 1rem;
      border-top: 1px solid rgba(255,255,255,0.1);
      background: rgba(0,0,0,0.1);
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
      padding: 20px;
      transition: all 0.3s ease;
    }

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
    .status-draft { background-color: #6c757d; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; }
    .status-review { background-color: var(--warning-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; }
    .status-approved { background-color: var(--success-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; }
    .status-active { background-color: var(--info-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; }
    .status-archived { background-color: var(--danger-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; }

    /* Workflow Section */
    .workflow-section {
      background-color: white;
      padding: 1.5rem;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      margin-bottom: 1.5rem;
    }

    .dark-mode .workflow-section {
      background-color: rgba(44, 62, 80, 0.9);
      color: var(--text-light);
    }

    .workflow-steps {
      display: flex;
      justify-content: space-between;
      margin: 1rem 0;
      position: relative;
    }

    .workflow-step {
      text-align: center;
      flex: 1;
      position: relative;
    }

    .workflow-step::after {
      content: '';
      position: absolute;
      top: 20px;
      left: 50%;
      width: 100%;
      height: 2px;
      background-color: #ddd;
      z-index: 1;
    }

    .workflow-step:last-child::after {
      display: none;
    }

    .workflow-step.active::after {
      background-color: var(--primary-color);
    }

    .step-circle {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: #ddd;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 0.5rem;
      font-weight: bold;
      position: relative;
      z-index: 2;
    }

    .workflow-step.active .step-circle {
      background-color: var(--primary-color);
    }

    .workflow-step.completed .step-circle {
      background-color: var(--success-color);
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
      <div class="loading-subtext" id="loadingSubtext">Please wait while we prepare SOP data and workflow</div>
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
    include 'includes/sidebar.php'; 
  ?>

  <div class="content" id="mainContent">
    <div class="header">
      <div class="hamburger" id="hamburger">☰</div>
      <div>
        <h1>SOP Manager <span class="system-title">| CORE II </span></h1>
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
        <h3>Total SOPs</h3>
        <div class="stat-value" id="totalSOPs">0</div>
        <div class="stat-label">All procedures</div>
      </div>

      <div class="card">
        <h3>Active SOPs</h3>
        <div class="stat-value" id="activeSOPs">0</div>
        <div class="stat-label">Currently in use</div>
      </div>

      <div class="card">
        <h3>Pending Review</h3>
        <div class="stat-value" id="pendingReview">0</div>
        <div class="stat-label">Awaiting approval</div>
      </div>

      <div class="card">
        <h3>Recent Updates</h3>
        <div class="stat-value" id="recentUpdates">0</div>
        <div class="stat-label">This month</div>
      </div>
    </div>

    <div class="workflow-section">
      <h3>Standard Operating Procedure Workflow</h3>
      <div class="workflow-steps">
        <div class="workflow-step completed">
          <div class="step-circle">1</div>
          <div>Draft</div>
        </div>
        <div class="workflow-step completed">
          <div class="step-circle">2</div>
          <div>Review</div>
        </div>
        <div class="workflow-step active">
          <div class="step-circle">3</div>
          <div>Approval</div>
        </div>
        <div class="workflow-step">
          <div class="step-circle">4</div>
          <div>Active</div>
        </div>
        <div class="workflow-step">
          <div class="step-circle">5</div>
          <div>Archive</div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>SOP Management</h3>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#sopModal" onclick="openAddModal()">
        <i class="bi bi-plus-circle"></i> Create New SOP
      </button>
    </div>

    <div class="table-section">
      <div class="table-responsive">
        <table id="sopsTable" class="table table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Title</th>
              <th>Category</th>
              <th>Department</th>
              <th>Version</th>
              <th>Status</th>
              <th>Review Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="sopsTableBody">
            <!-- SOP data will be loaded here -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- SOP Modal -->
  <div class="modal fade" id="sopModal" tabindex="-1" aria-labelledby="sopModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="sopModalLabel">Create New Standard Operating Procedure</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="sopForm">
            <input type="hidden" id="sopId">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="sopTitle" class="form-label">SOP Title *</label>
                  <input type="text" class="form-control" id="sopTitle" name="sopTitle" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="sopCategory" class="form-label">Category *</label>
                  <select class="form-select" id="sopCategory" name="sopCategory" required>
                    <option value="">Select Category</option>
                    <option value="Safety">Safety Procedures</option>
                    <option value="Operations">Operational Procedures</option>
                    <option value="Maintenance">Maintenance Procedures</option>
                    <option value="Quality">Quality Control</option>
                    <option value="Emergency">Emergency Response</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="sopDepartment" class="form-label">Department *</label>
                  <select class="form-select" id="sopDepartment" name="sopDepartment" required>
                    <option value="">Select Department</option>
                    <option value="Operations">Operations</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Safety">Safety</option>
                    <option value="Quality">Quality Assurance</option>
                    <option value="Administration">Administration</option>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="sopVersion" class="form-label">Version *</label>
                  <input type="text" class="form-control" id="sopVersion" name="sopVersion" value="1.0" required>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <div class="mb-3">
                  <label for="sopPurpose" class="form-label">Purpose *</label>
                  <textarea class="form-control" id="sopPurpose" name="sopPurpose" rows="3" required></textarea>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <div class="mb-3">
                  <label for="sopScope" class="form-label">Scope *</label>
                  <textarea class="form-control" id="sopScope" name="sopScope" rows="3" required></textarea>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <div class="mb-3">
                  <label for="sopResponsibilities" class="form-label">Responsibilities *</label>
                  <textarea class="form-control" id="sopResponsibilities" name="sopResponsibilities" rows="3" required></textarea>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <div class="mb-3">
                  <label for="sopProcedures" class="form-label">Procedures *</label>
                  <textarea class="form-control" id="sopProcedures" name="sopProcedures" rows="6" required></textarea>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="sopEquipment" class="form-label">Required Equipment</label>
                  <textarea class="form-control" id="sopEquipment" name="sopEquipment" rows="3"></textarea>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="sopSafetyNotes" class="form-label">Safety Notes</label>
                  <textarea class="form-control" id="sopSafetyNotes" name="sopSafetyNotes" rows="3"></textarea>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="sopReviewDate" class="form-label">Next Review Date *</label>
                  <input type="date" class="form-control" id="sopReviewDate" name="sopReviewDate" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="sopStatus" class="form-label">Status *</label>
                  <select class="form-select" id="sopStatus" name="sopStatus" required>
                    <option value="Draft">Draft</option>
                    <option value="Under Review">Under Review</option>
                    <option value="Pending Approval">Pending Approval</option>
                    <option value="Approved">Approved</option>
                    <option value="Active">Active</option>
                    <option value="Archived">Archived</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <div class="mb-3">
                  <label for="sopNotes" class="form-label">Additional Notes</label>
                  <textarea class="form-control" id="sopNotes" name="sopNotes" rows="3"></textarea>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveSOP()">Save SOP</button>
        </div>
      </div>
    </div>
  </div>

  <!-- View SOP Modal -->
  <div class="modal fade" id="viewSOPModal" tabindex="-1" aria-labelledby="viewSOPModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewSOPModalLabel">SOP Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <p><strong>ID:</strong> <span id="viewSOPId"></span></p>
              <p><strong>Title:</strong> <span id="viewSOPTitle"></span></p>
              <p><strong>Category:</strong> <span id="viewSOPCategory"></span></p>
              <p><strong>Department:</strong> <span id="viewSOPDepartment"></span></p>
            </div>
            <div class="col-md-6">
              <p><strong>Version:</strong> <span id="viewSOPVersion"></span></p>
              <p><strong>Status:</strong> <span id="viewSOPStatus"></span></p>
              <p><strong>Review Date:</strong> <span id="viewSOPReviewDate"></span></p>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <p><strong>Purpose:</strong></p>
              <p id="viewSOPPurpose"></p>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <p><strong>Scope:</strong></p>
              <p id="viewSOPScope"></p>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <p><strong>Responsibilities:</strong></p>
              <p id="viewSOPResponsibilities"></p>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <p><strong>Procedures:</strong></p>
              <p id="viewSOPProcedures"></p>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <p><strong>Required Equipment:</strong></p>
              <p id="viewSOPEquipment"></p>
            </div>
            <div class="col-md-6">
              <p><strong>Safety Notes:</strong></p>
              <p id="viewSOPSafetyNotes"></p>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <p><strong>Additional Notes:</strong></p>
              <p id="viewSOPNotes"></p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" id="viewDownloadBtn" style="display:none;">Download PDF</button>
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
          <p>Are you sure you want to delete SOP <strong id="deleteSOPName"></strong>?</p>
          <p class="text-danger">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete SOP</button>
        </div>
      </div>
    </div>
  </div>



  <!-- Bootstrap JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    const API_BASE = 'api/sops.php';
    let sops = [];
    let currentSOPId = null;
    let isEditMode = false;

    // Initialize the application
    document.addEventListener('DOMContentLoaded', function() {
      showLoading('Initializing SOP Manager...', 'Loading standard operating procedures and workflow components');
      
      // Simulate loading time for better UX
      setTimeout(() => {
        initializeEventListeners();
        applyStoredTheme();
        fetchSOPs();
        
        // Hide loading after everything is ready
        setTimeout(() => {
          hideLoading();
        }, 500);
      }, 1500);
    });

    async function fetchSOPs() {
      try {
        const res = await fetch(API_BASE);
        const data = await res.json();
        sops = Array.isArray(data) ? data.map(dbToUi) : [];
        loadSOPs();
        updateDashboardStats();
      } catch (e) {
        showNotification('Failed to load SOPs', 'danger');
      }
    }

    function dbToUi(row) {
      return {
        id: parseInt(row.id),
        title: row.title,
        category: row.category,
        department: row.department,
        version: row.version,
        status: row.status,
        reviewDate: row.review_date,
        purpose: row.purpose,
        scope: row.scope,
        responsibilities: row.responsibilities,
        procedures: row.procedures,
        equipment: row.equipment || '',
        safetyNotes: row.safety_notes || '',
        notes: row.notes || ''
      };
    }

    function uiToDb(s) {
      return {
        title: s.title,
        category: s.category,
        department: s.department,
        version: s.version,
        status: s.status,
        reviewDate: s.reviewDate,
        purpose: s.purpose,
        scope: s.scope,
        responsibilities: s.responsibilities,
        procedures: s.procedures,
        equipment: s.equipment || '',
        safetyNotes: s.safetyNotes || '',
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


    }

    function applyStoredTheme() {
      const stored = localStorage.getItem('theme');
      const isDark = stored === 'dark';
      document.body.classList.toggle('dark-mode', isDark);
      const toggle = document.getElementById('themeToggle');
      if (toggle) toggle.checked = isDark;
    }

    function loadSOPs() {
      const tbody = document.getElementById('sopsTableBody');
      tbody.innerHTML = '';

      sops.forEach(sop => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${sop.id}</td>
          <td>${sop.title}</td>
          <td>${sop.category}</td>
          <td>${sop.department}</td>
          <td>${sop.version}</td>
          <td><span class="badge ${getStatusBadgeClass(sop.status)}">${sop.status}</span></td>
          <td>${sop.reviewDate}</td>
          <td>
            <div class="action-buttons">
              <button class="btn btn-sm btn-info" onclick="viewSOP(${sop.id})" title="View Details">
                <i class="bi bi-eye"></i>
              </button>
              <button class="btn btn-sm btn-primary" onclick="editSOP(${sop.id})" title="Edit SOP">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-sm btn-success" onclick="approveSOP(${sop.id})" title="Approve SOP">
                <i class="bi bi-check-circle"></i>
              </button>
              <button class="btn btn-sm btn-warning" onclick="reviewSOP(${sop.id})" title="Send for Review">
                <i class="bi bi-send"></i>
              </button>
              ${ (['Approved','Active'].includes(sop.status)) ? `
                <button class="btn btn-sm btn-outline-primary" onclick="downloadSOP(${sop.id})" title="Download PDF">
                  <i class="bi bi-filetype-pdf"></i>
                </button>` : '' }
              <button class="btn btn-sm btn-danger" onclick="deleteSOP(${sop.id})" title="Delete SOP">
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
        case 'Approved': return 'bg-info';
        case 'Under Review': return 'bg-warning text-dark';
        case 'Pending Approval': return 'bg-warning text-dark';
        case 'Draft': return 'bg-secondary';
        case 'Archived': return 'bg-danger';
        default: return 'bg-secondary';
      }
    }

    function updateDashboardStats() {
      const totalSOPs = sops.length;
      const activeSOPs = sops.filter(s => s.status === 'Active').length;
      const pendingReview = sops.filter(s => s.status === 'Under Review' || s.status === 'Pending Approval').length;
      const recentUpdates = 4; // Sample data

      document.getElementById('totalSOPs').textContent = totalSOPs;
      document.getElementById('activeSOPs').textContent = activeSOPs;
      document.getElementById('pendingReview').textContent = pendingReview;
      document.getElementById('recentUpdates').textContent = recentUpdates;
    }

    function openAddModal() {
      isEditMode = false;
      currentSOPId = null;
      document.getElementById('sopModalLabel').textContent = 'Create New Standard Operating Procedure';
      document.getElementById('sopForm').reset();
      document.getElementById('sopId').value = '';
      document.getElementById('sopVersion').value = '1.0';
    }

    function viewSOP(id) {
      const sop = sops.find(s => s.id === id);
      if (!sop) return;

      document.getElementById('viewSOPId').textContent = sop.id;
      document.getElementById('viewSOPTitle').textContent = sop.title;
      document.getElementById('viewSOPCategory').textContent = sop.category;
      document.getElementById('viewSOPDepartment').textContent = sop.department;
      document.getElementById('viewSOPVersion').textContent = sop.version;
      document.getElementById('viewSOPStatus').textContent = sop.status;
      document.getElementById('viewSOPReviewDate').textContent = sop.reviewDate;
      document.getElementById('viewSOPPurpose').textContent = sop.purpose;
      document.getElementById('viewSOPScope').textContent = sop.scope;
      document.getElementById('viewSOPResponsibilities').textContent = sop.responsibilities;
      document.getElementById('viewSOPProcedures').textContent = sop.procedures;
      document.getElementById('viewSOPEquipment').textContent = sop.equipment || 'No equipment specified';
      document.getElementById('viewSOPSafetyNotes').textContent = sop.safetyNotes || 'No safety notes';
      document.getElementById('viewSOPNotes').textContent = sop.notes || 'No additional notes';

      const viewModal = new bootstrap.Modal(document.getElementById('viewSOPModal'));
      const viewDownloadBtn = document.getElementById('viewDownloadBtn');
      if (viewDownloadBtn) {
        const canDownload = sop.status === 'Approved' || sop.status === 'Active';
        viewDownloadBtn.style.display = canDownload ? 'inline-block' : 'none';
        viewDownloadBtn.onclick = () => downloadSOP(sop.id);
      }
      viewModal.show();
    }

    function editSOP(id) {
      const sop = sops.find(s => s.id === id);
      if (!sop) return;

      isEditMode = true;
      currentSOPId = id;
      document.getElementById('sopModalLabel').textContent = 'Edit SOP';
      
      // Populate form fields
      document.getElementById('sopId').value = sop.id;
      document.getElementById('sopTitle').value = sop.title;
      document.getElementById('sopCategory').value = sop.category;
      document.getElementById('sopDepartment').value = sop.department;
      document.getElementById('sopVersion').value = sop.version;
      document.getElementById('sopPurpose').value = sop.purpose;
      document.getElementById('sopScope').value = sop.scope;
      document.getElementById('sopResponsibilities').value = sop.responsibilities;
      document.getElementById('sopProcedures').value = sop.procedures;
      document.getElementById('sopEquipment').value = sop.equipment || '';
      document.getElementById('sopSafetyNotes').value = sop.safetyNotes || '';
      document.getElementById('sopReviewDate').value = sop.reviewDate;
      document.getElementById('sopStatus').value = sop.status;
      document.getElementById('sopNotes').value = sop.notes || '';

      const modal = new bootstrap.Modal(document.getElementById('sopModal'));
      modal.show();
    }

    async function approveSOP(id) {
      const sop = sops.find(s => s.id === id);
      if (!sop) return;
      try {
        const res = await fetch(`${API_BASE}?id=${id}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(uiToDb({ ...sop, status: 'Approved' }))
        });
        if (!res.ok) throw new Error();
        await fetchSOPs();
        showNotification(`SOP "${sop.title}" approved successfully!`, 'success');
      } catch (e) {
        showNotification('Failed to approve SOP', 'danger');
      }
    }

    async function reviewSOP(id) {
      const sop = sops.find(s => s.id === id);
      if (!sop) return;
      try {
        const res = await fetch(`${API_BASE}?id=${id}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(uiToDb({ ...sop, status: 'Under Review' }))
        });
        if (!res.ok) throw new Error();
        await fetchSOPs();
        showNotification(`SOP "${sop.title}" sent for review!`, 'info');
      } catch (e) {
        showNotification('Failed to update SOP', 'danger');
      }
    }

    function deleteSOP(id) {
      const sop = sops.find(s => s.id === id);
      if (!sop) return;

      document.getElementById('deleteSOPName').textContent = sop.title;
      currentSOPId = id;

      const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
      deleteModal.show();
    }

    async function confirmDelete() {
      if (!currentSOPId) return;
      try {
        const res = await fetch(`${API_BASE}?id=${currentSOPId}`, { method: 'DELETE' });
        if (!res.ok) throw new Error();
        await fetchSOPs();
        const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
        deleteModal.hide();
        showNotification('SOP deleted successfully!', 'success');
      } catch (e) {
        showNotification('Failed to delete SOP', 'danger');
      }
    }

    async function saveSOP() {
      const form = document.getElementById('sopForm');
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const formData = new FormData(form);
      const sopData = {
        title: formData.get('sopTitle'),
        category: formData.get('sopCategory'),
        department: formData.get('sopDepartment'),
        version: formData.get('sopVersion'),
        status: formData.get('sopStatus'),
        reviewDate: formData.get('sopReviewDate'),
        purpose: formData.get('sopPurpose'),
        scope: formData.get('sopScope'),
        responsibilities: formData.get('sopResponsibilities'),
        procedures: formData.get('sopProcedures'),
        equipment: formData.get('sopEquipment'),
        safetyNotes: formData.get('sopSafetyNotes'),
        notes: formData.get('sopNotes')
      };

      try {
        if (isEditMode && currentSOPId) {
          const res = await fetch(`${API_BASE}?id=${currentSOPId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(uiToDb(sopData))
          });
          if (!res.ok) throw new Error();
          showNotification('SOP updated successfully!', 'success');
        } else {
          const res = await fetch(API_BASE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(uiToDb(sopData))
          });
          if (!res.ok) throw new Error();
          showNotification('SOP created successfully!', 'success');
        }
        await fetchSOPs();
        const modal = bootstrap.Modal.getInstance(document.getElementById('sopModal'));
        modal.hide();
      } catch (e) {
        showNotification('Failed to save SOP', 'danger');
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

    // Create a printable page so users can Save as PDF
    function downloadSOP(id) {
      const sop = sops.find(s => s.id === id);
      if (!sop) return;
      if (!(sop.status === 'Approved' || sop.status === 'Active')) {
        showNotification('Only Approved or Active SOPs can be downloaded.', 'warning');
        return;
      }

      const printWindow = window.open('', '_blank');
      if (!printWindow) {
        showNotification('Pop-up blocked. Allow pop-ups to download the PDF.', 'warning');
        return;
      }

      const safe = (v) => (v ? String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') : '');

      const html = `<!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <title>SOP_${safe(sop.id)}_${safe(sop.title)}</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
          body { font-family: 'Inter','Segoe UI',system-ui,sans-serif; padding: 32px; }
          .header { display:flex; align-items:center; justify-content:space-between; margin-bottom: 16px; }
          .brand { display:flex; align-items:center; gap:12px; }
          .brand img { height:40px; }
          .title { margin: 0; font-weight: 800; }
          .meta { font-size: 0.9rem; color: #6c757d; }
          .section { margin-top: 16px; }
          .section h5 { margin-bottom: 8px; }
          .divider { height:2px; background:#e9ecef; margin:16px 0; }
          @media print {
            .no-print { display:none !important; }
            .pagebreak { page-break-before: always; }
          }
        </style>
      </head>
      <body>
        <div class="header">
          <div class="brand">
            <img src="slatelogo.png" alt="Logo">
            <div>
              <h2 class="title">Standard Operating Procedure</h2>
              <div class="meta">Generated on ${new Date().toLocaleString()}</div>
            </div>
          </div>
          <div>
            <span class="badge bg-secondary">${safe(sop.status)}</span>
          </div>
        </div>
        <div class="divider"></div>

        <div class="row">
          <div class="col-md-6">
            <p><strong>ID:</strong> ${safe(sop.id)}</p>
            <p><strong>Title:</strong> ${safe(sop.title)}</p>
            <p><strong>Category:</strong> ${safe(sop.category)}</p>
            <p><strong>Department:</strong> ${safe(sop.department)}</p>
          </div>
          <div class="col-md-6">
            <p><strong>Version:</strong> ${safe(sop.version)}</p>
            <p><strong>Status:</strong> ${safe(sop.status)}</p>
            <p><strong>Review Date:</strong> ${safe(sop.reviewDate)}</p>
          </div>
        </div>

        <div class="section">
          <h5>Purpose</h5>
          <div>${safe(sop.purpose)}</div>
        </div>
        <div class="section">
          <h5>Scope</h5>
          <div>${safe(sop.scope)}</div>
        </div>
        <div class="section">
          <h5>Responsibilities</h5>
          <div>${safe(sop.responsibilities)}</div>
        </div>
        <div class="section">
          <h5>Procedures</h5>
          <div style="white-space: pre-wrap;">${safe(sop.procedures)}</div>
        </div>

        <div class="row section">
          <div class="col-md-6">
            <h5>Required Equipment</h5>
            <div>${safe(sop.equipment || 'No equipment specified')}</div>
          </div>
          <div class="col-md-6">
            <h5>Safety Notes</h5>
            <div>${safe(sop.safetyNotes || 'No safety notes')}</div>
          </div>
        </div>

        <div class="section">
          <h5>Additional Notes</h5>
          <div>${safe(sop.notes || 'No additional notes')}</div>
        </div>

        <div class="divider"></div>
        <div class="d-flex justify-content-between">
          <small>Prepared by CORE II</small>
          <small>Signature: ______________________ Date: ____________</small>
        </div>

        <div class="no-print mt-3">
          <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        </div>
      </body>
      </html>`;

      printWindow.document.open();
      printWindow.document.write(html);
      printWindow.document.close();

      setTimeout(() => {
        printWindow.focus();
        printWindow.print();
      }, 300);
    }
  </script>
</body>
</html>
