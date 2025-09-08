<?php
require_once 'auth.php';
require_once 'db.php';
requireUser(); // Only regular users can access this page
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile | CORE II</title>
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

    .profile-avatar {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      background: var(--gradient-primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 4rem;
      color: white;
      margin: 0 auto 2rem;
      border: 4px solid rgba(255,255,255,0.2);
      overflow: hidden;
    }

    .profile-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
    }

    .profile-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin: 2rem 0;
    }

    .stat-item {
      text-align: center;
      padding: 1rem;
      background: rgba(255,255,255,0.7);
      border-radius: 0.5rem;
      border: 1px solid rgba(255,255,255,0.3);
    }

    .dark-mode .stat-item {
      background: rgba(255,255,255,0.1);
      border: 1px solid rgba(255,255,255,0.2);
    }

    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: var(--primary-color);
    }

    .stat-label {
      font-size: 0.9rem;
      color: #6c757d;
      margin-top: 0.5rem;
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
      <div class="loading-subtext" id="loadingSubtext">Please wait while we prepare your profile</div>
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
        <a href="#" class="nav-link user-feature active">
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
      <div>
        <h1>My Profile <span class="system-title">| CORE II</span></h1>
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

    <div class="row">
      <div class="col-md-4">
        <div class="card">
          <div class="card-body text-center">
            <div class="profile-avatar" id="profileAvatarContainer">
              <?php
              // Get user profile picture from database
              $currentProfilePicture = null;
              $currentUserName = $_SESSION['username'] ?? 'User';
              
              if (isset($_SESSION['user_id'])) {
                $stmt = $db->prepare("SELECT profile_picture, name FROM users WHERE id = ?");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                if ($user) {
                  $currentProfilePicture = $user['profile_picture'];
                  $currentUserName = $user['name'] ?: $_SESSION['username'] ?: 'User';
                  
                  // Update session name if available
                  if ($user['name']) {
                    $_SESSION['name'] = $user['name'];
                  }
                }
              }
              
              if ($currentProfilePicture && file_exists($currentProfilePicture)) {
                echo '<img src="' . htmlspecialchars($currentProfilePicture) . '" alt="Profile Picture" class="profile-image" id="profileImage">';
              } else {
                echo '<i class="bi bi-person-circle" id="defaultAvatar"></i>';
              }
              ?>
            </div>
            <h4 id="userName"><?php echo htmlspecialchars($currentUserName); ?></h4>
            <p class="text-muted"><?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?></p>
            <div class="mt-3">
              <span class="badge bg-success">Active Account</span>
            </div>
          </div>
        </div>

        <div class="card mt-4">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-info-circle"></i> Account Information</h5>
            <ul class="list-unstyled mt-3">
              <li class="mb-2"><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></li>
              <li class="mb-2"><strong>Role:</strong> <?php echo ucfirst($_SESSION['role']); ?></li>
              <li class="mb-2"><strong>Status:</strong> <span class="text-success">Active</span></li>
              <li class="mb-2"><strong>Member Since:</strong> <?php echo date('M Y'); ?></li>
            </ul>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-graph-up"></i> Profile Statistics</h5>
            <div class="profile-stats">
              <div class="stat-item">
                <div class="stat-value">3</div>
                <div class="stat-label">Active Requests</div>
              </div>
              <div class="stat-item">
                <div class="stat-value">24</div>
                <div class="stat-label">Total Requests</div>
              </div>
              <div class="stat-item">
                <div class="stat-value">98%</div>
                <div class="stat-label">Satisfaction Rate</div>
              </div>
              <div class="stat-item">
                <div class="stat-value">₱15,750</div>
                <div class="stat-label">Total Spent</div>
              </div>
            </div>
          </div>
        </div>

        <div class="card mt-4">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-pencil-square"></i> Edit Profile</h5>
            <form id="profileForm">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="fullName" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="fullName" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" value="user@example.com" readonly>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" placeholder="+63 XXX XXX XXXX">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="company" class="form-label">Company</label>
                    <input type="text" class="form-control" id="company" placeholder="Your Company Name">
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control" id="address" rows="2" placeholder="Your complete address"></textarea>
              </div>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Save Changes
              </button>
            </form>
          </div>
        </div>

        <div class="card mt-4">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-shield-lock"></i> Change Password</h5>
            <form id="passwordForm">
              <div class="mb-3">
                <label for="currentPassword" class="form-label">Current Password</label>
                <input type="password" class="form-control" id="currentPassword" required>
              </div>
              <div class="mb-3">
                <label for="newPassword" class="form-label">New Password</label>
                <input type="password" class="form-control" id="newPassword" required minlength="6">
              </div>
              <div class="mb-3">
                <label for="confirmPassword" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirmPassword" required>
              </div>
              <button type="submit" class="btn btn-warning">
                <i class="bi bi-key"></i> Change Password
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Common Logout Functionality -->
  <script src="js/common-logout.js"></script>
  
  <script>
    // Initialize the application
    document.addEventListener('DOMContentLoaded', function() {
      showLoading('Loading Profile...', 'Please wait while we prepare your profile data');
      
      // Simulate loading time for better UX
      setTimeout(() => {
        initializeEventListeners();
        applyStoredTheme();
        loadUserProfile();
        
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
      });

      // Sidebar toggle
      document.getElementById('hamburger').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
      });

      // Profile form submission
      document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveUserProfile();
      });

      // Password form submission
      document.getElementById('passwordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (newPassword !== confirmPassword) {
          Swal.fire({
            title: 'Error!',
            text: 'New passwords do not match.',
            icon: 'error',
            confirmButtonColor: '#dc3545'
          });
          return;
        }
        
        if (newPassword.length < 6) {
          Swal.fire({
            title: 'Error!',
            text: 'Password must be at least 6 characters long.',
            icon: 'error',
            confirmButtonColor: '#dc3545'
          });
          return;
        }
        
        Swal.fire({
          title: 'Password Changed!',
          text: 'Your password has been updated successfully.',
          icon: 'success',
          confirmButtonColor: '#28a745'
        }).then(() => {
          this.reset();
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

    // Profile Picture Upload Functions
    function initializeProfilePictureUpload() {
      const uploadBtn = document.getElementById('uploadBtn');
      const removeBtn = document.getElementById('removeBtn');
      const fileInput = document.getElementById('profilePictureInput');
      const previewContainer = document.getElementById('previewContainer');
      const uploadArea = document.getElementById('uploadArea');

      // Show file picker when upload button is clicked
      uploadBtn?.addEventListener('click', function() {
        fileInput.click();
      });

      // Also allow clicking on preview area to upload
      previewContainer?.addEventListener('click', function() {
        fileInput.click();
      });

      // Handle file selection
      fileInput?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
          validateAndPreviewImage(file);
        }
      });

      // Handle drag and drop
      uploadArea?.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('drag-over');
      });

      uploadArea?.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
      });

      uploadArea?.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
          validateAndPreviewImage(files[0]);
        }
      });

      // Remove picture button
      removeBtn?.addEventListener('click', function() {
        removeProfilePicture();
      });

      // Check if user has profile picture to show remove button
      updateRemoveButtonVisibility();
      
      // Initialize with existing profile picture if available
      initializeExistingProfilePicture();
    }

    function validateAndPreviewImage(file) {
      // Validate file type
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
      const fileType = file.type.toLowerCase();
      
      if (!allowedTypes.includes(fileType)) {
        Swal.fire({
          title: 'Invalid File Type',
          text: 'Please select a JPG, PNG, GIF, or WebP image.',
          icon: 'error',
          confirmButtonColor: '#dc3545'
        });
        return;
      }

      // Validate file size (5MB max)
      const maxSize = 5 * 1024 * 1024; // 5MB in bytes
      if (file.size > maxSize) {
        Swal.fire({
          title: 'File Too Large',
          text: 'Please select an image smaller than 5MB.',
          icon: 'error',
          confirmButtonColor: '#dc3545'
        });
        return;
      }

      // Show preview
      const reader = new FileReader();
      reader.onload = function(e) {
        showImagePreview(e.target.result);
      };
      reader.readAsDataURL(file);

      // Upload immediately
      uploadProfilePicture(file);
    }

    function showImagePreview(imageSrc) {
      const previewContainer = document.getElementById('previewContainer');
      previewContainer.innerHTML = `<img src="${imageSrc}" alt="Preview" class="preview-image" id="previewImage">`;
      updateRemoveButtonVisibility();
    }

    async function uploadProfilePicture(file) {
      const progressContainer = document.getElementById('uploadProgress');
      const progressBar = document.getElementById('progressBar');
      const progressText = document.getElementById('progressText');

      try {
        // Show progress
        progressContainer.style.display = 'block';
        progressText.textContent = 'Uploading...';
        progressBar.style.width = '0%';

        const formData = new FormData();
        formData.append('profile_picture', file);

        const xhr = new XMLHttpRequest();
        
        // Upload progress
        xhr.upload.addEventListener('progress', function(e) {
          if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            progressBar.style.width = percentComplete + '%';
            progressText.textContent = `Uploading... ${Math.round(percentComplete)}%`;
          }
        });

        xhr.onload = function() {
          if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
              progressText.textContent = 'Upload complete!';
              setTimeout(() => {
                progressContainer.style.display = 'none';
              }, 1000);
              
              console.log('Upload successful, updating images:', response.profile_picture);
              
              // Update profile header image
              updateProfileHeaderImage(response.profile_picture);
              
              // Also update the upload preview to match
              showImagePreview(response.profile_picture + '?t=' + new Date().getTime());
              
              Swal.fire({
                title: 'Success!',
                text: 'Profile picture updated successfully!',
                icon: 'success',
                confirmButtonColor: '#28a745'
              });
              updateRemoveButtonVisibility();
              
              console.log('Profile avatar should now show the uploaded image');
            } else {
              throw new Error(response.error || 'Upload failed');
            }
          } else {
            throw new Error('Upload failed');
          }
        };

        xhr.onerror = function() {
          throw new Error('Network error during upload');
        };

        xhr.open('POST', 'api/upload-profile-picture.php');
        xhr.send(formData);

      } catch (error) {
        console.error('Upload error:', error);
        progressContainer.style.display = 'none';
        Swal.fire({
          title: 'Upload Failed',
          text: 'Failed to upload profile picture: ' + error.message,
          icon: 'error',
          confirmButtonColor: '#dc3545'
        });
      }
    }

    async function removeProfilePicture() {
      const result = await Swal.fire({
        title: 'Remove Profile Picture?',
        text: 'Are you sure you want to remove your profile picture?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Remove',
        cancelButtonText: 'Cancel'
      });

      if (!result.isConfirmed) {
        return;
      }

      try {
        const response = await fetch('api/remove-profile-picture.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          }
        });

        const responseData = await response.json();
        
        if (responseData.success) {
          // Reset to default avatar
          const previewContainer = document.getElementById('previewContainer');
          previewContainer.innerHTML = `
            <div class="upload-placeholder" id="uploadPlaceholder">
              <i class="bi bi-person-plus"></i>
              <p>Click to upload profile picture</p>
              <small>JPG, PNG, GIF, or WebP (Max 5MB)</small>
            </div>`;
          
          // Update profile header
          updateProfileHeaderImage(null);
          
          Swal.fire({
            title: 'Success!',
            text: 'Profile picture removed successfully!',
            icon: 'success',
            confirmButtonColor: '#28a745'
          });
          updateRemoveButtonVisibility();
        } else {
          throw new Error(responseData.error || 'Failed to remove profile picture');
        }
      } catch (error) {
        console.error('Remove error:', error);
        Swal.fire({
          title: 'Error',
          text: 'Failed to remove profile picture: ' + error.message,
          icon: 'error',
          confirmButtonColor: '#dc3545'
        });
      }
    }

    function updateProfileHeaderImage(imagePath) {
      const profileAvatarContainer = document.getElementById('profileAvatarContainer');
      if (imagePath) {
        // Add timestamp to prevent caching issues
        const timestamp = new Date().getTime();
        const imageUrl = `${imagePath}?t=${timestamp}`;
        profileAvatarContainer.innerHTML = `<img src="${imageUrl}" alt="Profile Picture" class="profile-image" id="profileImage">`;
        console.log('Updated profile header image:', imageUrl);
      } else {
        profileAvatarContainer.innerHTML = `<i class="bi bi-person-circle" id="defaultAvatar"></i>`;
        console.log('Reset profile header to default avatar');
      }
    }

    function updateProfilePictureDisplay(imagePath) {
      if (imagePath) {
        // Update upload preview section
        const previewContainer = document.getElementById('previewContainer');
        if (previewContainer) {
          previewContainer.innerHTML = `<img src="${imagePath}" alt="Current Profile Picture" class="preview-image" id="previewImage">`;
        }
        
        // Update profile header
        updateProfileHeaderImage(imagePath);
        
        // Show remove button
        updateRemoveButtonVisibility();
      } else {
        // Reset to placeholder
        const previewContainer = document.getElementById('previewContainer');
        if (previewContainer) {
          previewContainer.innerHTML = `
            <div class="upload-placeholder" id="uploadPlaceholder">
              <i class="bi bi-person-plus"></i>
              <p>Click to upload profile picture</p>
              <small>JPG, PNG, GIF, or WebP (Max 5MB)</small>
            </div>`;
        }
        
        // Update profile header to default
        updateProfileHeaderImage(null);
        
        // Hide remove button
        updateRemoveButtonVisibility();
      }
    }

    function initializeExistingProfilePicture() {
      // Check if there's already a profile image in the header
      const headerImage = document.querySelector('#profileAvatarContainer img.profile-image');
      const previewContainer = document.getElementById('previewContainer');
      
      console.log('Initializing existing profile picture...');
      console.log('Header image found:', headerImage);
      console.log('Preview container found:', previewContainer);
      
      if (headerImage && previewContainer) {
        const imageSrc = headerImage.src;
        console.log('Found existing profile image:', imageSrc);
        
        // Update the preview container to match the header
        previewContainer.innerHTML = `<img src="${imageSrc}" alt="Current Profile Picture" class="preview-image" id="previewImage">`;
        updateRemoveButtonVisibility();
        
        console.log('✅ Successfully initialized profile picture display');
      } else {
        console.log('⚪ No existing profile image found - showing placeholder');
      }
    }

    function updateRemoveButtonVisibility() {
      const removeBtn = document.getElementById('removeBtn');
      const previewImage = document.getElementById('previewImage');
      
      if (removeBtn) {
        if (previewImage) {
          removeBtn.style.display = 'inline-flex';
        } else {
          removeBtn.style.display = 'none';
        }
      }
    }

    async function loadUserProfile() {
      try {
        // Load profile data from API
        const response = await fetch('api/load-profile.php');
        const result = await response.json();
        
        if (result.success && result.profile) {
          const profile = result.profile;
          
          // Populate form fields
          const fieldMappings = {
            'fullName': profile.name || profile.username,
            'email': profile.email,
            'phone': profile.phone,
            'company': profile.service_area, // Using service_area as company for users
            'address': profile.description   // Using description as address for users
          };
          
          for (const [fieldId, value] of Object.entries(fieldMappings)) {
            const field = document.getElementById(fieldId);
            if (field && value) {
              field.value = value;
            }
          }
          
          // Handle profile picture display - only if different from current
          const currentHeaderImage = document.querySelector('#profileAvatarContainer img.profile-image');
          if (profile.profile_picture) {
            // Only update if the image is different or missing
            if (!currentHeaderImage || currentHeaderImage.src.indexOf(profile.profile_picture) === -1) {
              console.log('API loaded different profile picture, updating:', profile.profile_picture);
              updateProfilePictureDisplay(profile.profile_picture);
            } else {
              console.log('API profile picture matches current display, keeping existing');
            }
          } else if (currentHeaderImage) {
            console.log('API shows no profile picture but one exists in HTML, keeping existing');
          }
          
          // Update user name in header
          const userName = document.getElementById('userName');
          if (userName) {
            userName.textContent = profile.name || profile.username;
          }
        } else {
          throw new Error(result.error || 'Failed to load profile data');
        }
      } catch (error) {
        console.error('Error loading profile:', error);
        // Don't show error notification on page load to avoid interrupting user experience
      }
    }

    async function saveUserProfile() {
      try {
        const formData = new FormData(document.getElementById('profileForm'));
        const profileData = Object.fromEntries(formData.entries());
        
        // Map user form fields to the expected API fields
        const apiData = {
          fullName: profileData.fullName,
          email: profileData.email,
          phone: profileData.phone,
          serviceArea: profileData.company, // Using company as service_area
          description: profileData.address  // Using address as description
        };

        // Save profile data to API
        const response = await fetch('api/save-profile.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(apiData)
        });
        
        const result = await response.json();
        
        if (result.success) {
          Swal.fire({
            title: 'Profile Updated!',
            text: 'Your profile information has been saved successfully.',
            icon: 'success',
            confirmButtonColor: '#28a745'
          });
          
          // Update the user name in the header
          const userName = document.getElementById('userName');
          if (userName && apiData.fullName) {
            userName.textContent = apiData.fullName;
          }
        } else {
          throw new Error(result.error || 'Failed to save profile');
        }
      } catch (error) {
        console.error('Error saving profile:', error);
        Swal.fire({
          title: 'Error!',
          text: 'Failed to save profile changes: ' + error.message,
          icon: 'error',
          confirmButtonColor: '#dc3545'
        });
      }
    }
  </script>
</body>
</html>
