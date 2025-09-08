<?php
require_once 'auth.php';
require_once 'db.php';
requireUser(); // Only regular users can access this page

// Handle form submission for creating requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_request') {
    // Debug: Log the POST data
    error_log('Create request POST data: ' . json_encode($_POST));
    error_log('User ID from session: ' . ($_SESSION['user_id'] ?? 'NOT SET'));
    
    try {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }
        
        // Get current user ID
        $userId = $_SESSION['user_id'];
        
        // Validate required fields
        $requiredFields = ['service_type', 'title', 'description', 'origin', 'destination'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Generate unique request ID
        $requestId = 'REQ-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Use $db connection (the first one that's guaranteed to work)
        $connection = $db;
        
        // Debug: Check database connection
        if (!$connection) {
            throw new Exception('Database connection not available');
        }
        
        error_log('Using request ID: ' . $requestId);
        
        // Check if request ID already exists
        $checkStmt = $connection->prepare("SELECT id FROM user_requests WHERE request_id = ?");
        if (!$checkStmt) {
            throw new Exception('Failed to prepare check statement: ' . $connection->error);
        }
        $checkStmt->bind_param("s", $requestId);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            // Generate new ID if collision occurs
            $requestId = 'REQ-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        }
        
        // Insert new request
        $sql = "
            INSERT INTO user_requests (
                request_id, user_id, service_type, title, description, 
                origin, destination, cargo_type, weight, 
                contact_person, contact_phone, priority, special_instructions
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $connection->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database prepare error: ' . $connection->error);
        }
        
        // Prepare values
        $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
        $cargo_type = !empty($_POST['cargo_type']) ? $_POST['cargo_type'] : null;
        $contact_person = !empty($_POST['contact_person']) ? $_POST['contact_person'] : null;
        $contact_phone = !empty($_POST['contact_phone']) ? $_POST['contact_phone'] : null;
        $priority = !empty($_POST['priority']) ? $_POST['priority'] : 'normal';
        $special_instructions = !empty($_POST['special_instructions']) ? $_POST['special_instructions'] : null;
        
        $stmt->bind_param(
            "sissssssdssss",
            $requestId,
            $userId,
            $_POST['service_type'],
            $_POST['title'],
            $_POST['description'],
            $_POST['origin'],
            $_POST['destination'],
            $cargo_type,
            $weight,
            $contact_person,
            $contact_phone,
            $priority,
            $special_instructions
        );
        
        error_log('About to execute insert statement...');
        
        if ($stmt->execute()) {
            $newRequestId = $connection->insert_id;
            error_log('Request inserted successfully with ID: ' . $newRequestId);
            
            // Log status history
            $historyStmt = $connection->prepare("
                INSERT INTO request_status_history 
                (request_id, previous_status, new_status, changed_by_user_id, changed_by_role, comments) 
                VALUES (?, NULL, 'pending', ?, 'user', 'Request created')
            ");
            if ($historyStmt) {
                $historyStmt->bind_param("ii", $newRequestId, $userId);
                if (!$historyStmt->execute()) {
                    error_log('Failed to insert status history: ' . $historyStmt->error);
                }
            }
            
            // Success - redirect with success message
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Request Created!',
                        text: 'Your service request has been submitted successfully. Request ID: $requestId',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'user-requests.php';
                    });
                });
            </script>";
        } else {
            error_log('Failed to execute insert statement: ' . $stmt->error);
            throw new Exception('Failed to create request: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        // Error - show error message
        $errorMessage = htmlspecialchars($e->getMessage());
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to create request: $errorMessage',
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.history.back();
                });
            });
        </script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Requests | CORE II</title>
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

    /* Cards */
    .card {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: var(--border-radius);
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
      transition: all 0.3s;
    }

    .dark-mode .card {
      background: rgba(44, 62, 80, 0.9);
      color: var(--text-light);
      border: 1px solid rgba(255,255,255,0.1);
    }

    .card:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    }

    /* Request Cards */
    .request-card {
      border-left: 4px solid var(--primary-color);
      transition: all 0.3s ease;
    }

    .request-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    }

    .request-card.pending {
      border-left-color: var(--warning-color);
    }

    .request-card.processing {
      border-left-color: var(--info-color);
    }

    .request-card.completed {
      border-left-color: var(--success-color);
    }

    .request-card.cancelled {
      border-left-color: var(--danger-color);
    }

    .status-badge {
      font-size: 0.8rem;
      padding: 0.3rem 0.8rem;
      border-radius: 20px;
      font-weight: 500;
    }

    .filters-section {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      padding: 1.5rem;
      border-radius: var(--border-radius);
      box-shadow: 0 4px 16px rgba(0,0,0,0.05);
      margin-bottom: 2rem;
      border: 1px solid rgba(255,255,255,0.2);
    }

    .dark-mode .filters-section {
      background: rgba(44, 62, 80, 0.9);
      border: 1px solid rgba(255,255,255,0.1);
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      text-align: center;
      padding: 1.5rem;
      border-radius: var(--border-radius);
      background: rgba(255,255,255,0.8);
      border: 1px solid rgba(255,255,255,0.3);
    }

    .dark-mode .stat-card {
      background: rgba(255,255,255,0.1);
      border: 1px solid rgba(255,255,255,0.2);
    }

    .stat-number {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .stat-label {
      font-size: 0.9rem;
      opacity: 0.8;
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
      <div class="loading-subtext" id="loadingSubtext">Please wait while we prepare your requests</div>
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
        <a href="#" class="nav-link user-feature active">
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
      <div>
        <h1>My Requests <span class="system-title">| CORE II</span></h1>
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

    <!-- Statistics Cards -->
    <div class="stats-grid" id="statsGrid">
      <div class="stat-card">
        <div class="stat-number text-warning" id="pendingCount">0</div>
        <div class="stat-label">Pending Requests</div>
      </div>
      <div class="stat-card">
        <div class="stat-number text-info" id="processingCount">0</div>
        <div class="stat-label">Processing</div>
      </div>
      <div class="stat-card">
        <div class="stat-number text-success" id="completedCount">0</div>
        <div class="stat-label">Completed</div>
      </div>
      <div class="stat-card">
        <div class="stat-number text-danger" id="cancelledCount">0</div>
        <div class="stat-label">Cancelled</div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
      <div class="row align-items-center">
        <div class="col-md-3">
          <label for="statusFilter" class="form-label">Filter by Status:</label>
          <select class="form-select" id="statusFilter">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="serviceFilter" class="form-label">Filter by Service:</label>
          <select class="form-select" id="serviceFilter">
            <option value="">All Services</option>
            <option value="freight">Freight Services</option>
            <option value="express">Express Delivery</option>
            <option value="warehouse">Warehouse</option>
            <option value="logistics">Logistics</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="dateFilter" class="form-label">Date Range:</label>
          <select class="form-select" id="dateFilter">
            <option value="">All Time</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
          </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button class="btn btn-primary me-2" onclick="applyFilters()">
            <i class="bi bi-funnel"></i> Apply Filters
          </button>
          <button class="btn btn-outline-secondary" onclick="clearFilters()">
            <i class="bi bi-x-circle"></i> Clear
          </button>
        </div>
      </div>
    </div>

    <!-- Create New Request Button -->
    <div class="mb-4">
      <button class="btn btn-primary btn-lg" onclick="showNewRequestModal()">
        <i class="bi bi-plus-circle"></i> Create New Request
      </button>
    </div>

    <!-- Requests List -->
    <div id="requestsList">
      <?php
      // Load user requests from database
      $userId = $_SESSION['user_id'];
      
      $sql = "
          SELECT ur.*, 
                 p.username as provider_name,
                 p.name as provider_company
          FROM user_requests ur
          LEFT JOIN users p ON ur.provider_id = p.id
          WHERE ur.user_id = ?
          ORDER BY ur.created_at DESC
          LIMIT 20
      ";
      
      $stmt = $db->prepare($sql);
      $stmt->bind_param("i", $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $requests = $result->fetch_all(MYSQLI_ASSOC);
      
      if (empty($requests)): ?>
        <!-- Empty state -->
        <div class="text-center py-5" id="emptyState">
          <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
          <h5 class="mt-3 text-muted">No Requests Found</h5>
          <p class="text-muted">You haven't made any service requests yet.</p>
          <button class="btn btn-primary" onclick="showNewRequestModal()">
            <i class="bi bi-plus-circle"></i> Create Your First Request
          </button>
        </div>
      <?php else: ?>
        <?php foreach ($requests as $request): 
          $statusClass = strtolower($request['status']);
          $statusBadgeClass = [
            'pending' => 'bg-warning',
            'processing' => 'bg-info',
            'completed' => 'bg-success',
            'cancelled' => 'bg-danger'
          ][$request['status']] ?? 'bg-secondary';
          
          $serviceTypeNames = [
            'freight' => 'Freight Services',
            'express' => 'Express Delivery',
            'warehouse' => 'Warehouse Services',
            'logistics' => 'Logistics Services'
          ];
          $serviceTypeName = $serviceTypeNames[$request['service_type']] ?? $request['service_type'];
        ?>
        <div class="card request-card <?php echo $statusClass; ?> mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div class="flex-grow-1">
                <h5 class="card-title"><?php echo htmlspecialchars($request['title']); ?></h5>
                <p class="card-text">
                  <strong>Request ID:</strong> <?php echo htmlspecialchars($request['request_id']); ?><br>
                  <strong>Service Type:</strong> <?php echo htmlspecialchars($serviceTypeName); ?><br>
                  <strong>Origin:</strong> <?php echo htmlspecialchars($request['origin']); ?><br>
                  <strong>Destination:</strong> <?php echo htmlspecialchars($request['destination']); ?><br>
                  <?php if ($request['cargo_type']): ?>
                    <strong>Cargo Type:</strong> <?php echo htmlspecialchars($request['cargo_type']); ?><br>
                  <?php endif; ?>
                  <?php if ($request['weight']): ?>
                    <strong>Weight:</strong> <?php echo number_format($request['weight'], 1); ?> kg<br>
                  <?php endif; ?>
                </p>
                <div class="row">
                  <div class="col-md-6">
                    <small class="text-muted">
                      <i class="bi bi-calendar"></i> Created: <?php echo date('M j, Y', strtotime($request['created_at'])); ?><br>
                      <i class="bi bi-currency-dollar"></i> 
                      <?php if ($request['final_cost']): ?>
                        Final Cost: ₱<?php echo number_format($request['final_cost'], 2); ?>
                      <?php elseif ($request['estimated_cost']): ?>
                        Estimated: ₱<?php echo number_format($request['estimated_cost'], 2); ?>
                      <?php else: ?>
                        Cost: Pending
                      <?php endif; ?>
                    </small>
                  </div>
                </div>
              </div>
              <div class="text-end">
                <span class="badge <?php echo $statusBadgeClass; ?> status-badge"><?php echo ucfirst($request['status']); ?></span>
                <div class="mt-2">
                  <button class="btn btn-sm btn-outline-primary" onclick="viewRequest(<?php echo $request['id']; ?>)">
                    <i class="bi bi-eye"></i> View
                  </button>
                  <?php 
                  switch ($request['status']) {
                    case 'pending':
                      echo '<button class="btn btn-sm btn-outline-danger" onclick="cancelRequest(' . $request['id'] . ')">
                              <i class="bi bi-x-circle"></i> Cancel
                            </button>';
                      break;
                    case 'processing':
                      echo '<button class="btn btn-sm btn-outline-info" onclick="trackRequest(' . $request['id'] . ')">
                              <i class="bi bi-geo-alt"></i> Track
                            </button>';
                      break;
                    case 'completed':
                      if ($request['rating']) {
                        echo '<button class="btn btn-sm btn-outline-secondary" disabled>
                                <i class="bi bi-star-fill"></i> Reviewed
                              </button>';
                      } else {
                        echo '<button class="btn btn-sm btn-outline-success" onclick="reviewService(' . $request['id'] . ')">
                                <i class="bi bi-star"></i> Review
                              </button>';
                      }
                      break;
                  }
                  ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
      
      <!-- Loading message (hidden by default) -->
      <div class="text-center py-5 d-none" id="loadingMessage">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-2">Loading requests...</div>
      </div>
    </div>

    <!-- Pagination -->
    <nav aria-label="Requests pagination" class="mt-4 d-none" id="paginationNav">
      <ul class="pagination justify-content-center" id="paginationList">
        <!-- Pagination will be generated dynamically -->
      </ul>
    </nav>
  </div>

  <!-- Bootstrap JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Global variables
    let currentPage = 1;
    let currentFilters = {};
    let requestsData = [];
    
    // Initialize the application
    document.addEventListener('DOMContentLoaded', function() {
      showLoading('Loading Requests...', 'Please wait while we prepare your service requests');
      
      // Initialize everything
      initializeEventListeners();
      applyStoredTheme();
      
      // Check authentication first
      checkAuthentication().then(isAuthenticated => {
        if (isAuthenticated) {
          // Load requests after a short delay for smooth UX
          setTimeout(() => {
            loadRequests();
            setTimeout(() => {
              hideLoading();
            }, 500);
          }, 1000);
        } else {
          hideLoading();
          Swal.fire({
            title: 'Authentication Required',
            text: 'Please log in to view your requests.',
            icon: 'warning',
            confirmButtonText: 'Login'
          }).then(() => {
            window.location.href = 'login.php';
          });
        }
      });
    });

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
        
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
      });
    }

    function applyStoredTheme() {
      const stored = localStorage.getItem('theme');
      const isDark = stored === 'dark';
      document.body.classList.toggle('dark-mode', isDark);
      const toggle = document.getElementById('themeToggle');
      if (toggle) toggle.checked = isDark;
    }

    // Check if user is authenticated
    async function checkAuthentication() {
      try {
        const response = await fetch('check-session.php');
        const data = await response.json();
        return data.logged_in || false;
      } catch (error) {
        console.error('Error checking authentication:', error);
        return false;
      }
    }

    // Load requests - simplified version
    function loadRequests(page = 1) {
      // Hide loading, show content
      document.getElementById('loadingMessage').classList.add('d-none');
      
      // For now, show empty state - this will be populated with PHP data
      const requestsContainer = document.getElementById('requestsList');
      const hasRequests = requestsContainer.querySelector('.request-card:not(#loadingMessage):not(#emptyState)');
      
      if (!hasRequests) {
        document.getElementById('emptyState').classList.remove('d-none');
        document.getElementById('paginationNav').classList.add('d-none');
      } else {
        document.getElementById('emptyState').classList.add('d-none');
        document.getElementById('paginationNav').classList.remove('d-none');
      }
      
      // Update stats - these will be populated by PHP
      updateStatisticsFromPage();
    }
    
    function updateStatisticsFromPage() {
      // Count requests by status from the page
      const pendingCount = document.querySelectorAll('.request-card.pending').length;
      const processingCount = document.querySelectorAll('.request-card.processing').length;
      const completedCount = document.querySelectorAll('.request-card.completed').length;
      const cancelledCount = document.querySelectorAll('.request-card.cancelled').length;
      
      document.getElementById('pendingCount').textContent = pendingCount;
      document.getElementById('processingCount').textContent = processingCount;
      document.getElementById('completedCount').textContent = completedCount;
      document.getElementById('cancelledCount').textContent = cancelledCount;
    }
    
    function updateStatistics(stats) {
      document.getElementById('pendingCount').textContent = stats.pending || 0;
      document.getElementById('processingCount').textContent = stats.processing || 0;
      document.getElementById('completedCount').textContent = stats.completed || 0;
      document.getElementById('cancelledCount').textContent = stats.cancelled || 0;
    }
    
    function renderRequests(requests) {
      const container = document.getElementById('requestsList');
      const loadingMessage = document.getElementById('loadingMessage');
      const emptyState = document.getElementById('emptyState');
      
      // Clear existing content except loading/empty states
      const elementsToKeep = [loadingMessage, emptyState];
      Array.from(container.children).forEach(child => {
        if (!elementsToKeep.includes(child)) {
          child.remove();
        }
      });
      
      requests.forEach(request => {
        const requestCard = createRequestCard(request);
        container.appendChild(requestCard);
      });
    }
    
    function createRequestCard(request) {
      const card = document.createElement('div');
      card.className = `card request-card ${request.status} mb-3`;
      
      const statusBadgeClass = {
        'pending': 'bg-warning',
        'processing': 'bg-info', 
        'completed': 'bg-success',
        'cancelled': 'bg-danger'
      }[request.status] || 'bg-secondary';
      
      const formatCurrency = (amount) => {
        return amount ? `₱${parseFloat(amount).toLocaleString()}` : 'N/A';
      };
      
      const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
          month: 'short',
          day: 'numeric', 
          year: 'numeric'
        });
      };
      
      const getServiceTypeName = (type) => {
        const typeNames = {
          'freight': 'Freight Services',
          'express': 'Express Delivery',
          'warehouse': 'Warehouse Services',
          'logistics': 'Logistics Services'
        };
        return typeNames[type] || type;
      };
      
      card.innerHTML = `
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
              <h5 class="card-title">${request.title}</h5>
              <p class="card-text">
                <strong>Request ID:</strong> ${request.request_id}<br>
                <strong>Service Type:</strong> ${getServiceTypeName(request.service_type)}<br>
                <strong>Origin:</strong> ${request.origin}<br>
                <strong>Destination:</strong> ${request.destination}<br>
                ${request.cargo_type ? `<strong>Cargo Type:</strong> ${request.cargo_type}<br>` : ''}
                ${request.weight ? `<strong>Weight:</strong> ${request.weight} kg<br>` : ''}
              </p>
              <div class="row">
                <div class="col-md-6">
                  <small class="text-muted">
                    <i class="bi bi-calendar"></i> Created: ${formatDate(request.created_at)}<br>
                    <i class="bi bi-currency-dollar"></i> ${request.final_cost ? 'Final Cost: ' + formatCurrency(request.final_cost) : (request.estimated_cost ? 'Estimated: ' + formatCurrency(request.estimated_cost) : 'Cost: Pending')}
                  </small>
                </div>
              </div>
            </div>
            <div class="text-end">
              <span class="badge ${statusBadgeClass} status-badge">${request.status.charAt(0).toUpperCase() + request.status.slice(1)}</span>
              <div class="mt-2">
                <button class="btn btn-sm btn-outline-primary" onclick="viewRequest(${request.id})">
                  <i class="bi bi-eye"></i> View
                </button>
                ${getActionButton(request)}
              </div>
            </div>
          </div>
        </div>
      `;
      
      return card;
    }
    
    function getActionButton(request) {
      switch (request.status) {
        case 'pending':
          return `<button class="btn btn-sm btn-outline-danger" onclick="cancelRequest(${request.id})">
                    <i class="bi bi-x-circle"></i> Cancel
                  </button>`;
        case 'processing':
          return `<button class="btn btn-sm btn-outline-info" onclick="trackRequest(${request.id})">
                    <i class="bi bi-geo-alt"></i> Track
                  </button>`;
        case 'completed':
          return request.rating ? 
            `<button class="btn btn-sm btn-outline-secondary" disabled>
               <i class="bi bi-star-fill"></i> Reviewed
             </button>` :
            `<button class="btn btn-sm btn-outline-success" onclick="reviewService(${request.id})">
               <i class="bi bi-star"></i> Review
             </button>`;
        default:
          return '';
      }
    }
    
    function updatePagination(pagination) {
      const paginationList = document.getElementById('paginationList');
      paginationList.innerHTML = '';
      
      if (pagination.total_pages <= 1) {
        document.getElementById('paginationNav').classList.add('d-none');
        return;
      }
      
      document.getElementById('paginationNav').classList.remove('d-none');
      
      // Previous button
      const prevBtn = document.createElement('li');
      prevBtn.className = `page-item ${pagination.current_page === 1 ? 'disabled' : ''}`;
      prevBtn.innerHTML = `<a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1})">Previous</a>`;
      paginationList.appendChild(prevBtn);
      
      // Page numbers
      const startPage = Math.max(1, pagination.current_page - 2);
      const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
      
      for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('li');
        pageBtn.className = `page-item ${i === pagination.current_page ? 'active' : ''}`;
        pageBtn.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i})">${i}</a>`;
        paginationList.appendChild(pageBtn);
      }
      
      // Next button
      const nextBtn = document.createElement('li');
      nextBtn.className = `page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}`;
      nextBtn.innerHTML = `<a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1})">Next</a>`;
      paginationList.appendChild(nextBtn);
    }
    
    function changePage(page) {
      if (page < 1) return;
      currentPage = page;
      loadRequests(page);
    }

    function applyFilters() {
      const status = document.getElementById('statusFilter').value;
      const serviceType = document.getElementById('serviceFilter').value;
      const dateRange = document.getElementById('dateFilter').value;
      
      currentFilters = {};
      if (status) currentFilters.status = status;
      if (serviceType) currentFilters.service_type = serviceType;
      if (dateRange) currentFilters.date_range = dateRange;
      
      currentPage = 1;
      loadRequests(1);
      
      Swal.fire({
        title: 'Filters Applied!',
        text: `Filtering by Status: ${status || 'All'}, Service: ${serviceType || 'All'}, Date: ${dateRange || 'All Time'}`,
        icon: 'success',
        timer: 2000
      });
    }

    function clearFilters() {
      document.getElementById('statusFilter').value = '';
      document.getElementById('serviceFilter').value = '';
      document.getElementById('dateFilter').value = '';
      
      currentFilters = {};
      currentPage = 1;
      loadRequests(1);
      
      Swal.fire({
        title: 'Filters Cleared!',
        text: 'All filters have been reset.',
        icon: 'info',
        timer: 1500
      });
    }

    function showNewRequestModal() {
      Swal.fire({
        title: 'Create New Request',
        html: `
          <div class="text-start">
            <div class="mb-3">
              <label class="form-label">Service Type *</label>
              <select class="form-select" id="newServiceType" required>
                <option value="">Select Service Type</option>
                <option value="freight">Freight Services</option>
                <option value="express">Express Delivery</option>
                <option value="warehouse">Warehouse Services</option>
                <option value="logistics">Logistics Services</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Title *</label>
              <input type="text" class="form-control" id="newTitle" placeholder="Brief title for your request" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Description *</label>
              <textarea class="form-control" id="newDescription" rows="3" placeholder="Describe your service request in detail..." required></textarea>
            </div>
            <div class="row">
              <div class="col-md-6">
                <label class="form-label">Origin *</label>
                <input type="text" class="form-control" id="newOrigin" placeholder="Pickup location" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Destination *</label>
                <input type="text" class="form-control" id="newDestination" placeholder="Delivery location" required>
              </div>
            </div>
            <div class="row mt-3">
              <div class="col-md-6">
                <label class="form-label">Cargo Type</label>
                <input type="text" class="form-control" id="newCargoType" placeholder="e.g., Electronics, Documents">
              </div>
              <div class="col-md-6">
                <label class="form-label">Weight (kg)</label>
                <input type="number" class="form-control" id="newWeight" placeholder="Weight in kg" min="0" step="0.1">
              </div>
            </div>
            <div class="row mt-3">
              <div class="col-md-6">
                <label class="form-label">Contact Person</label>
                <input type="text" class="form-control" id="newContactPerson" placeholder="Contact person name">
              </div>
              <div class="col-md-6">
                <label class="form-label">Contact Phone</label>
                <input type="tel" class="form-control" id="newContactPhone" placeholder="Phone number">
              </div>
            </div>
            <div class="mt-3">
              <label class="form-label">Priority</label>
              <select class="form-select" id="newPriority">
                <option value="normal">Normal</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <div class="mt-3">
              <label class="form-label">Special Instructions</label>
              <textarea class="form-control" id="newInstructions" rows="2" placeholder="Any special handling instructions..."></textarea>
            </div>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Create Request',
        cancelButtonText: 'Cancel',
        width: 800,
        preConfirm: () => {
          const serviceType = document.getElementById('newServiceType').value;
          const title = document.getElementById('newTitle').value.trim();
          const description = document.getElementById('newDescription').value.trim();
          const origin = document.getElementById('newOrigin').value.trim();
          const destination = document.getElementById('newDestination').value.trim();
          
          if (!serviceType || !title || !description || !origin || !destination) {
            Swal.showValidationMessage('Please fill in all required fields (marked with *)');
            return false;
          }
          
          return {
            service_type: serviceType,
            title: title,
            description: description,
            origin: origin,
            destination: destination,
            cargo_type: document.getElementById('newCargoType').value.trim() || '',
            weight: document.getElementById('newWeight').value || '',
            contact_person: document.getElementById('newContactPerson').value.trim() || '',
            contact_phone: document.getElementById('newContactPhone').value.trim() || '',
            priority: document.getElementById('newPriority').value || 'normal',
            special_instructions: document.getElementById('newInstructions').value.trim() || ''
          };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          createRequestWithForm(result.value);
        }
      });
    }
    
    // Function to create request using form submission
    function createRequestWithForm(formData) {
      // Create a hidden form and submit it
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'user-requests.php';
      form.style.display = 'none';
      
      // Add form fields
      const fields = {
        'action': 'create_request',
        'service_type': formData.service_type,
        'title': formData.title,
        'description': formData.description,
        'origin': formData.origin,
        'destination': formData.destination,
        'cargo_type': formData.cargo_type,
        'weight': formData.weight,
        'contact_person': formData.contact_person,
        'contact_phone': formData.contact_phone,
        'priority': formData.priority,
        'special_instructions': formData.special_instructions
      };
      
      Object.keys(fields).forEach(key => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = fields[key];
        form.appendChild(input);
      });
      
      document.body.appendChild(form);
      
      // Show loading
      Swal.fire({
        title: 'Creating Request...',
        text: 'Please wait while we process your request.',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
      
      // Submit form
      setTimeout(() => {
        form.submit();
      }, 1000);
    }

    function viewRequest(requestId) {
      Swal.fire({
        title: `Request Details`,
        html: `
          <div class="text-start">
            <p>Loading request details...</p>
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        `,
        width: 600,
        confirmButtonText: 'Close',
        didOpen: () => {
          // In a real implementation, this would load data via AJAX
          // For now, just show a basic view
          setTimeout(() => {
            Swal.update({
              html: `
                <div class="text-start">
                  <p><strong>Request ID:</strong> View functionality is available</p>
                  <p><strong>Status:</strong> This feature displays detailed request information</p>
                  <hr>
                  <h6>Available Operations:</h6>
                  <ul>
                    <li>View detailed request information</li>
                    <li>Check request status and updates</li>
                    <li>See provider assignments</li>
                    <li>Review cost estimates</li>
                  </ul>
                  <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Full functionality will load request details from the database.
                  </div>
                </div>
              `
            });
          }, 1000);
        }
      });
    }
    
    function getServiceTypeName(type) {
      const typeNames = {
        'freight': 'Freight Services',
        'express': 'Express Delivery',
        'warehouse': 'Warehouse Services',
        'logistics': 'Logistics Services'
      };
      return typeNames[type] || type;
    }

    function cancelRequest(requestId) {
      Swal.fire({
        title: 'Cancel Request?',
        html: `
          <p>Are you sure you want to cancel this request?</p>
          <div class="mt-3">
            <label class="form-label text-start d-block">Reason for cancellation (optional):</label>
            <textarea class="form-control" id="cancelReason" rows="3" placeholder="Please provide a reason for cancelling this request..."></textarea>
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Cancel Request',
        cancelButtonText: 'Keep Request',
        preConfirm: () => {
          return document.getElementById('cancelReason').value.trim() || 'Cancelled by user';
        }
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Request Cancelled',
            text: 'Your request has been cancelled successfully. Page will refresh.',
            icon: 'success',
            timer: 2000
          }).then(() => {
            // Refresh page to update the request list
            window.location.reload();
          });
        }
      });
    }

    function trackRequest(requestId) {
      Swal.fire({
        title: `Track Request`,
        html: `
          <div class="text-start">
            <div class="mb-3">
              <div class="d-flex align-items-center mb-2">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <span>Request Submitted</span>
                <small class="text-muted ms-auto">Today</small>
              </div>
              <div class="d-flex align-items-center mb-2">
                <i class="bi bi-clock-fill text-warning me-2"></i>
                <span>Under Review</span>
                <small class="text-muted ms-auto">Current</small>
              </div>
              <div class="d-flex align-items-center mb-2">
                <i class="bi bi-circle text-muted me-2"></i>
                <span class="text-muted">Provider Assignment</span>
                <small class="text-muted ms-auto">Pending</small>
              </div>
              <div class="d-flex align-items-center">
                <i class="bi bi-circle text-muted me-2"></i>
                <span class="text-muted">In Progress</span>
                <small class="text-muted ms-auto">Pending</small>
              </div>
            </div>
            <div class="alert alert-info">
              <i class="bi bi-info-circle"></i>
              Your request is being reviewed by our logistics team. You will receive updates as the status changes.
            </div>
          </div>
        `,
        width: 600,
        confirmButtonText: 'Close'
      });
    }

    function reviewService(requestId) {
      Swal.fire({
        title: 'Review Service',
        html: `
          <div class="text-start">
            <div class="mb-3">
              <label class="form-label">Rating *</label>
              <div class="rating-stars text-center mb-2" style="font-size: 1.5rem; cursor: pointer;">
                <i class="bi bi-star rating-star" data-rating="1" style="margin: 0 2px;"></i>
                <i class="bi bi-star rating-star" data-rating="2" style="margin: 0 2px;"></i>
                <i class="bi bi-star rating-star" data-rating="3" style="margin: 0 2px;"></i>
                <i class="bi bi-star rating-star" data-rating="4" style="margin: 0 2px;"></i>
                <i class="bi bi-star rating-star" data-rating="5" style="margin: 0 2px;"></i>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Review</label>
              <textarea class="form-control" id="reviewText" rows="4" placeholder="Share your experience with this service..."></textarea>
            </div>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Submit Review',
        cancelButtonText: 'Cancel',
        didOpen: () => {
          let selectedRating = 0;
          
          // Add click handlers for rating stars
          document.querySelectorAll('.rating-star').forEach(star => {
            star.addEventListener('click', function() {
              selectedRating = parseInt(this.dataset.rating);
              document.querySelectorAll('.rating-star').forEach((s, index) => {
                if (index < selectedRating) {
                  s.classList.remove('bi-star');
                  s.classList.add('bi-star-fill', 'text-warning');
                } else {
                  s.classList.remove('bi-star-fill', 'text-warning');
                  s.classList.add('bi-star');
                }
              });
            });
          });
          
          window.selectedRating = selectedRating;
          
          document.addEventListener('click', function(e) {
            if (e.target.classList.contains('rating-star')) {
              window.selectedRating = parseInt(e.target.dataset.rating);
            }
          });
        },
        preConfirm: () => {
          const reviewText = document.getElementById('reviewText').value.trim();
          const rating = window.selectedRating;
          
          if (!rating || rating < 1 || rating > 5) {
            Swal.showValidationMessage('Please select a rating');
            return false;
          }
          
          return { rating, reviewText };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Review Submitted!',
            text: 'Thank you for your feedback! The page will refresh.',
            icon: 'success',
            timer: 2000
          }).then(() => {
            window.location.reload();
          });
        }
      });
    }
    
    // Utility function to show error messages
    function showError(message) {
      Swal.fire({
        title: 'Error',
        text: message,
        icon: 'error',
        confirmButtonText: 'OK'
      });
    }

    // Cleanup function called when page unloads
    window.addEventListener('beforeunload', function() {
      // Clean up any global variables
      if (window.selectedRating) {
        delete window.selectedRating;
      }
    });

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
