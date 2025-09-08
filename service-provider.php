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
  
  <!-- Universal Logout SweetAlert -->
  <script src="includes/logout-sweetalert.js"></script>
  
  <!-- Universal Dark Mode Styles -->
  <?php include 'includes/dark-mode-styles.php'; ?>
  <title>Service Management | CORE II</title>
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

    .provider-feature {
      background: rgba(0,0,0,0.1);
      border-left: 4px solid rgba(255,255,255,0.4);
    }

    .provider-feature:hover {
      background: rgba(0,0,0,0.2);
      border-left-color: rgba(255,255,255,0.8);
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
    .status-active { background-color: var(--success-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; }
    .status-pending { background-color: var(--warning-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; }
    .status-completed { background-color: var(--info-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; }
    .status-cancelled { background-color: var(--danger-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; }

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
    }

    @media (max-width: 576px) {
      .sidebar {
        width: 100%;
        max-width: 320px;
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
      <div class="loading-subtext" id="loadingSubtext">Please wait while we prepare service data</div>
    </div>
  </div>

  <?php include 'includes/sidebar.php'; ?>

  <div class="content" id="mainContent">
    <div class="header">
      <div class="hamburger" id="hamburger">☰</div>
      <div>
        <h1>Service Management <span class="system-title">| CORE II </span></h1>
      </div>
      <div class="header-controls">
        <a href="provider-dashboard.php" class="btn btn-outline-primary btn-sm me-2">
          <i class="bi bi-speedometer2"></i>
          Dashboard
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
        <div class="stat-value" id="totalProviders">0</div>
        <div class="stat-label">Registered providers</div>
      </div>

      <div class="card">
        <h3>Active Providers</h3>
        <div class="stat-value" id="activeProviders">0</div>
        <div class="stat-label">Currently operational</div>
      </div>

      <div class="card">
        <h3>Service Areas</h3>
        <div class="stat-value" id="serviceAreas">0</div>
        <div class="stat-label">Coverage areas</div>
      </div>

      <div class="card">
        <h3>Monthly Revenue</h3>
        <div class="stat-value" id="monthlyRevenue">₱0</div>
        <div class="stat-label">Current month</div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>Provider Management</h3>
      <div class="d-flex gap-2">
        <button class="btn btn-info" onclick="showProviderInfoMessage()">
          <i class="bi bi-info-circle"></i> How to Add Providers
        </button>
        <button class="btn btn-success d-none" data-bs-toggle="modal" data-bs-target="#providerModal" onclick="openAddModal()">
          <i class="bi bi-plus-circle"></i> Add New Provider
        </button>
      </div>
    </div>

    <div class="table-section">
      <div class="table-responsive">
        <table id="providersTable" class="table table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Username</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Service Area</th>
              <th>Status</th>
              <th>Last Login</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="providersTableBody">
            <!-- Provider data will be loaded here -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Provider Management Modal -->
  <div class="modal fade" id="providerModal" tabindex="-1" aria-labelledby="providerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="providerModalLabel">Add New Provider</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="providerForm">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="providerName" class="form-label">Provider Name *</label>
                <input type="text" class="form-control" id="providerName" name="name" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="providerType" class="form-label">Provider Type *</label>
                <select class="form-select" id="providerType" name="type" required>
                  <option value="">Select type...</option>
                  <option value="Individual">Individual</option>
                  <option value="Company">Company</option>
                  <option value="Cooperative">Cooperative</option>
                  <option value="Government Agency">Government Agency</option>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="contactPerson" class="form-label">Contact Person *</label>
                <input type="text" class="form-control" id="contactPerson" name="contact_person" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="contactEmail" class="form-label">Contact Email *</label>
                <input type="email" class="form-control" id="contactEmail" name="contact_email" required>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="contactPhone" class="form-label">Contact Phone *</label>
                <input type="tel" class="form-control" id="contactPhone" name="contact_phone" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="serviceArea" class="form-label">Service Area *</label>
                <input type="text" class="form-control" id="serviceArea" name="service_area" placeholder="e.g., Metro Manila" required>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="monthlyRate" class="form-label">Monthly Rate (₱)</label>
                <input type="number" step="0.01" class="form-control" id="monthlyRate" name="monthly_rate" placeholder="0.00">
              </div>
              <div class="col-md-6 mb-3">
                <label for="providerStatus" class="form-label">Status</label>
                <select class="form-select" id="providerStatus" name="status">
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                  <option value="Pending">Pending</option>
                  <option value="Suspended">Suspended</option>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="contractStart" class="form-label">Contract Start Date</label>
                <input type="date" class="form-control" id="contractStart" name="contract_start">
              </div>
              <div class="col-md-6 mb-3">
                <label for="contractEnd" class="form-label">Contract End Date</label>
                <input type="date" class="form-control" id="contractEnd" name="contract_end">
              </div>
            </div>
            <div class="row">
              <div class="col-md-12 mb-3">
                <label for="providerNotes" class="form-label">Notes</label>
                <textarea class="form-control" id="providerNotes" name="notes" rows="3" placeholder="Additional notes or comments..."></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-success" id="saveProviderBtn">
              <i class="bi bi-check-circle"></i> Save Provider
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Provider Details Modal -->
  <div class="modal fade" id="providerDetailsModal" tabindex="-1" aria-labelledby="providerDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="providerDetailsModalLabel">Provider Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="providerDetailsBody">
          <!-- Provider details will be loaded here -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Global variables
    let currentEditingProviderId = null;
    let isEditMode = false;
    let providerModal = null;
    let providerDetailsModal = null;

    // Initialize the application
    document.addEventListener('DOMContentLoaded', function() {
      showLoading('Initializing Provider Management...', 'Loading provider data and management tools');
      
      setTimeout(() => {
        initializeEventListeners();
        initializeModals();
        applyStoredTheme();
        loadProviders();
        loadStats();
        
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

      // Active link management
      const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
      navLinks.forEach(link => {
        link.addEventListener('click', function() {
          navLinks.forEach(l => l.classList.remove('active'));
          this.classList.add('active');
        });
      });

      // Provider form submission
      document.getElementById('providerForm').addEventListener('submit', handleProviderFormSubmit);
    }

    function initializeModals() {
      providerModal = new bootstrap.Modal(document.getElementById('providerModal'));
      providerDetailsModal = new bootstrap.Modal(document.getElementById('providerDetailsModal'));
    }

    function applyStoredTheme() {
      const stored = localStorage.getItem('theme');
      const isDark = stored === 'dark';
      document.body.classList.toggle('dark-mode', isDark);
      const toggle = document.getElementById('themeToggle');
      if (toggle) toggle.checked = isDark;
    }

    // API Functions
    async function apiRequest(url, options = {}) {
      try {
        const response = await fetch(url, {
          headers: {
            'Content-Type': 'application/json',
            ...options.headers
          },
          ...options
        });

        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.error || 'API request failed');
        }

        return data;
      } catch (error) {
        console.error('API Error:', error);
        throw error;
      }
    }

    async function loadProviders() {
      try {
        updateLoadingText('Loading provider accounts...', 'Fetching latest provider data');
        const data = await apiRequest('provider-users-api.php?action=list');
        renderProvidersTable(data.providers);
      } catch (error) {
        console.error('Load providers error:', error);
        showAlert('Failed to load providers: ' + error.message, 'error');
        // Show empty table on error
        const tbody = document.getElementById('providersTableBody');
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Error loading provider data. Please refresh the page.</td></tr>';
      }
    }

    async function loadStats() {
      try {
        const data = await apiRequest('provider-users-api.php?action=stats');
        updateDashboardStats(data);
      } catch (error) {
        console.error('Failed to load stats:', error);
      }
    }

    function renderProvidersTable(providers) {
      const tbody = document.getElementById('providersTableBody');
      tbody.innerHTML = '';

      if (!providers || providers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No provider accounts found</td></tr>';
        return;
      }

      providers.forEach(provider => {
        const row = document.createElement('tr');
        const status = provider.is_active ? 'Active' : 'Inactive';
        const lastLogin = provider.last_login ? formatDateTime(provider.last_login) : 'Never';
        
        row.innerHTML = `
          <td>${provider.id}</td>
          <td><strong>${provider.username}</strong></td>
          <td>${provider.name || 'N/A'}</td>
          <td><a href="mailto:${provider.email}">${provider.email}</a></td>
          <td>${provider.phone ? '<a href="tel:' + provider.phone + '">' + provider.phone + '</a>' : 'N/A'}</td>
          <td>${provider.service_area || 'N/A'}</td>
          <td><span class="badge ${getStatusBadgeClass(status)}">${status}</span></td>
          <td>${lastLogin}</td>
          <td>
            <div class="action-buttons">
              <button class="btn btn-sm btn-info" onclick="viewProvider(${provider.id})" title="View Details">
                <i class="bi bi-eye"></i>
              </button>
              <button class="btn btn-sm btn-primary" onclick="editProvider(${provider.id})" title="Edit Provider">
                <i class="bi bi-pencil"></i>
              </button>
              ${provider.is_active ? 
                '<button class="btn btn-sm btn-warning" onclick="toggleProviderStatus(' + provider.id + ', false)" title="Deactivate"><i class="bi bi-pause"></i></button>' :
                '<button class="btn btn-sm btn-success" onclick="toggleProviderStatus(' + provider.id + ', true)" title="Activate"><i class="bi bi-play"></i></button>'
              }
              <button class="btn btn-sm btn-danger" onclick="deleteProvider(${provider.id})" title="Delete Account">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </td>
        `;
        tbody.appendChild(row);
      });
    }

    function updateDashboardStats(stats) {
      document.getElementById('totalProviders').textContent = stats.total_providers || 0;
      document.getElementById('activeProviders').textContent = stats.active_providers || 0;
      document.getElementById('serviceAreas').textContent = stats.service_areas || 0;
      document.getElementById('monthlyRevenue').textContent = 'N/A';
    }

    // CRUD Operations
    function openAddModal() {
      isEditMode = false;
      currentEditingProviderId = null;
      document.getElementById('providerModalLabel').textContent = 'Add New Provider';
      document.getElementById('saveProviderBtn').innerHTML = '<i class="bi bi-check-circle"></i> Save Provider';
      document.getElementById('providerForm').reset();
      
      // Set default dates
      const today = new Date().toISOString().split('T')[0];
      const nextYear = new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0];
      document.getElementById('contractStart').value = today;
      document.getElementById('contractEnd').value = nextYear;
      document.getElementById('providerStatus').value = 'Active';
      
      providerModal.show();
    }

    async function editProvider(id) {
      try {
        if (!id || !Number.isInteger(Number(id))) {
          showAlert('Invalid provider ID', 'error');
          return;
        }
        
        showLoading('Loading provider details...', 'Preparing edit form');
        const data = await apiRequest(`provider-users-api.php?action=get&id=${id}`);
        
        if (!data.provider) {
          throw new Error('Provider data not found in response');
        }
        
        const provider = data.provider;
        
        isEditMode = true;
        currentEditingProviderId = id;
        document.getElementById('providerModalLabel').textContent = `Edit Provider - ${provider.username}`;
        document.getElementById('saveProviderBtn').innerHTML = '<i class="bi bi-check-circle"></i> Update Provider';
        
        // Populate form fields with user data
        document.getElementById('providerName').value = provider.name || '';
        document.getElementById('providerType').value = provider.provider_type || '';
        document.getElementById('contactPerson').value = provider.name || '';
        document.getElementById('contactEmail').value = provider.email || '';
        document.getElementById('contactPhone').value = provider.phone || '';
        document.getElementById('serviceArea').value = provider.service_area || '';
        document.getElementById('monthlyRate').value = '0.00'; // Not applicable for user accounts
        document.getElementById('providerStatus').value = provider.is_active ? 'Active' : 'Inactive';
        document.getElementById('contractStart').value = '';
        document.getElementById('contractEnd').value = '';
        document.getElementById('providerNotes').value = provider.description || '';
        
        hideLoading();
        providerModal.show();
      } catch (error) {
        hideLoading();
        console.error('Edit provider error:', error);
        showAlert('Failed to load provider details: ' + error.message, 'error');
      }
    }

    async function viewProvider(id) {
      try {
        if (!id || !Number.isInteger(Number(id))) {
          showAlert('Invalid provider ID', 'error');
          return;
        }
        
        showLoading('Loading provider details...', 'Fetching account information');
        const data = await apiRequest(`provider-users-api.php?action=get&id=${id}`);
        
        if (!data.provider) {
          throw new Error('Provider data not found in response');
        }
        
        const provider = data.provider;
        
        const detailsHtml = `
          <div class="row">
            <div class="col-md-6">
              <h6><strong>Username:</strong></h6>
              <p><strong>${provider.username}</strong></p>
            </div>
            <div class="col-md-6">
              <h6><strong>Name:</strong></h6>
              <p>${provider.name || 'N/A'}</p>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <h6><strong>Email:</strong></h6>
              <p><a href="mailto:${provider.email}">${provider.email}</a></p>
            </div>
            <div class="col-md-6">
              <h6><strong>Phone:</strong></h6>
              <p>${provider.phone ? '<a href="tel:' + provider.phone + '">' + provider.phone + '</a>' : 'N/A'}</p>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <h6><strong>Provider Type:</strong></h6>
              <p><span class="badge bg-info">${provider.provider_type || 'N/A'}</span></p>
            </div>
            <div class="col-md-6">
              <h6><strong>Service Area:</strong></h6>
              <p>${provider.service_area || 'N/A'}</p>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <h6><strong>Account Status:</strong></h6>
              <p><span class="badge ${getStatusBadgeClass(provider.is_active ? 'Active' : 'Inactive')}">${provider.is_active ? 'Active' : 'Inactive'}</span></p>
            </div>
            <div class="col-md-6">
              <h6><strong>Experience:</strong></h6>
              <p>${provider.experience || 'N/A'}</p>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <h6><strong>Last Login:</strong></h6>
              <p>${provider.last_login ? formatDateTime(provider.last_login) : 'Never'}</p>
            </div>
            <div class="col-md-6">
              <h6><strong>Account Created:</strong></h6>
              <p>${formatDateTime(provider.created_at)}</p>
            </div>
          </div>
          ${provider.description ? `<div class="row"><div class="col-md-12"><h6><strong>Description:</strong></h6><p>${provider.description}</p></div></div>` : ''}
        `;
        
        document.getElementById('providerDetailsBody').innerHTML = detailsHtml;
        hideLoading();
        providerDetailsModal.show();
      } catch (error) {
        hideLoading();
        console.error('View provider error:', error);
        showAlert('Failed to load provider details: ' + error.message, 'error');
      }
    }

    async function toggleProviderStatus(id, activate) {
      const action = activate ? 'activate' : 'deactivate';
      const result = await Swal.fire({
        title: 'Are you sure?',
        text: `This will ${action} the provider account.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: activate ? '#28a745' : '#ffc107',
        cancelButtonColor: '#3085d6',
        confirmButtonText: `Yes, ${action} it!`
      });
      
      if (result.isConfirmed) {
        try {
          await apiRequest(`provider-users-api.php?action=toggle-status&id=${id}`, {
            method: 'POST',
            body: JSON.stringify({ is_active: activate })
          });
          
          showAlert(`Provider ${activate ? 'activated' : 'deactivated'} successfully!`, 'success');
          loadProviders();
          loadStats();
        } catch (error) {
          showAlert('Failed to update provider status: ' + error.message, 'error');
        }
      }
    }

    async function deleteProvider(id) {
      const result = await Swal.fire({
        title: 'Are you sure?',
        text: "This provider account will be permanently deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
      });
      
      if (result.isConfirmed) {
        try {
          await apiRequest(`provider-users-api.php?action=delete&id=${id}`, {
            method: 'DELETE'
          });
          
          showAlert('Provider account deleted successfully!', 'success');
          loadProviders();
          loadStats();
        } catch (error) {
          showAlert('Failed to delete provider: ' + error.message, 'error');
        }
      }
    }

    async function handleProviderFormSubmit(e) {
      e.preventDefault();
      
      const formData = new FormData(e.target);
      const data = {};
      
      for (let [key, value] of formData.entries()) {
        if (value.trim() !== '') {
          data[key] = value;
        }
      }
      
      // Validate required fields for edit mode
      if (isEditMode) {
        const requiredFields = ['name', 'contact_email'];
        for (let field of requiredFields) {
          if (!data[field] || data[field].trim() === '') {
            showAlert(`${field.replace('_', ' ')} is required`, 'error');
            return;
          }
        }
        
        // Validate email format
        if (data.contact_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.contact_email)) {
          showAlert('Please enter a valid email address', 'error');
          return;
        }
      }
      
      try {
        if (isEditMode && currentEditingProviderId) {
          // Update existing provider account
          await apiRequest(`provider-users-api.php?action=update&id=${currentEditingProviderId}`, {
            method: 'POST',
            body: JSON.stringify(data)
          });
          showAlert('Provider account updated successfully!', 'success');
        } else {
          // Creating new providers not supported through this interface
          showAlert('New provider accounts must be created through the registration system.', 'info');
          return;
        }
        
        providerModal.hide();
        loadProviders();
        loadStats();
      } catch (error) {
        showAlert('Failed to save provider: ' + error.message, 'error');
      }
    }

    // Utility Functions
    function getStatusBadgeClass(status) {
      switch(status) {
        case 'Active': return 'bg-success';
        case 'Pending': return 'bg-warning';
        case 'Inactive': return 'bg-secondary';
        case 'Suspended': return 'bg-danger';
        default: return 'bg-secondary';
      }
    }

    function formatDate(dateStr) {
      if (!dateStr) return 'N/A';
      return new Date(dateStr).toLocaleDateString();
    }

    function formatDateTime(dateStr) {
      if (!dateStr) return 'N/A';
      return new Date(dateStr).toLocaleString();
    }

    function showAlert(message, type = 'info') {
      const icon = type === 'error' ? 'error' : type === 'success' ? 'success' : 'info';
      Swal.fire({
        title: type.charAt(0).toUpperCase() + type.slice(1),
        text: message,
        icon: icon,
        timer: 3000,
        timerProgressBar: true
      });
    }

    function showNotification(message, type = 'info') {
      showAlert(message, type);
    }

    function showProviderInfoMessage() {
      Swal.fire({
        title: 'Adding New Providers',
        html: `
          <div class="text-start">
            <p><strong>New provider accounts must be created through the registration system.</strong></p>
            <p>Here's how to add new providers:</p>
            <ol>
              <li>Direct new providers to the registration page</li>
              <li>They should select "Service Provider" during registration</li>
              <li>Fill in all required provider information</li>
              <li>Once registered, their account will appear in this list</li>
              <li>You can then edit, activate/deactivate, or manage their account</li>
            </ol>
            <p class="text-muted mt-3"><small>This ensures proper validation and security for all provider accounts.</small></p>
          </div>
        `,
        icon: 'info',
        confirmButtonText: 'Got it',
        confirmButtonColor: '#17a2b8'
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

    function updateLoadingText(text = 'Loading...', subtext = 'Please wait') {
      const loadingText = document.getElementById('loadingText');
      const loadingSubtext = document.getElementById('loadingSubtext');
      
      if (loadingText) loadingText.textContent = text;
      if (loadingSubtext) loadingSubtext.textContent = subtext;
    }
  </script>
</body>
</html>
