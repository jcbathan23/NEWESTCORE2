<?php
/**
 * Service Provider Module
 * CORE II - Service Provider Management (Dashboard Module)
 */

// Start session and include authentication
session_start();
require_once '../../auth.php';
requireLogin(); // Allow logged-in users to access this page
require_once '../../security.php';

// Define module constant
define('SERVICE_PROVIDER_MODULE', true);

// Include database connection
require_once '../../db.php';

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
  <title>Service Provider Management | CORE II</title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- Universal Logout SweetAlert -->
  <script src="../../includes/logout-sweetalert.js"></script>
  
  <!-- Universal Dark Mode Styles -->
  <?php include '../../includes/dark-mode-styles.php'; ?>
  
  <style>
    :root {
      --sidebar-width: 280px;
      --primary-color: #667eea;
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

    /* Cards */
    .card {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      border-radius: var(--border-radius);
      padding: 1.5rem;
      box-shadow: var(--shadow);
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s ease;
      margin-bottom: 2rem;
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

    /* Stats Cards */
    .stats-card {
      text-align: center;
      padding: 2rem;
      border-radius: 1rem;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
      border: 1px solid rgba(102, 126, 234, 0.2);
    }

    .stats-number {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
      color: var(--primary-color);
    }

    .stats-label {
      font-size: 1rem;
      color: #666;
      margin-bottom: 1rem;
    }

    .dark-mode .stats-label {
      color: #adb5bd;
    }

    /* Service Provider Table */
    .provider-table {
      overflow-x: auto;
      border-radius: 1rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .table {
      margin-bottom: 0;
    }

    .table th {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      font-weight: 600;
      border: none;
      padding: 1rem;
    }

    .table td {
      padding: 1rem;
      vertical-align: middle;
      border-bottom: 1px solid rgba(0,0,0,0.1);
    }

    .dark-mode .table td {
      border-bottom-color: rgba(255,255,255,0.1);
      color: var(--text-light);
    }

    .badge {
      font-size: 0.75rem;
      padding: 0.5rem 0.75rem;
      border-radius: 0.5rem;
    }

    .btn {
      border-radius: 0.5rem;
      padding: 0.5rem 1rem;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn:hover {
      transform: translateY(-2px);
    }

    /* Responsive Design */
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
    }
  </style>
</head>
<body>
  <?php 
    // Set role variables for sidebar
    include '../../includes/sidebar.php'; 
  ?>

  <div class="content" id="mainContent">
    <div class="header">
      <div class="hamburger" id="hamburger">☰</div>
      <div>
        <h1>Service Provider Management<span class="system-title"> | CORE II</span></h1>
      </div>
      <div class="header-controls">
        <!-- Theme toggle can be added here if needed -->
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card stats-card">
          <div class="stats-number" id="totalProviders">0</div>
          <div class="stats-label">Total Providers</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stats-card">
          <div class="stats-number" id="activeProviders">0</div>
          <div class="stats-label">Active Providers</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stats-card">
          <div class="stats-number" id="pendingProviders">0</div>
          <div class="stats-label">Pending Approval</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stats-card">
          <div class="stats-number" id="recentProviders">0</div>
          <div class="stats-label">This Month</div>
        </div>
      </div>
    </div>

    <!-- Service Providers Table -->
    <div class="card">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-building"></i> Service Providers</h3>
        <?php if ($isAdmin): ?>
        <button class="btn btn-primary" onclick="showAddProviderModal()">
          <i class="bi bi-plus-circle"></i> Add Provider
        </button>
        <?php endif; ?>
      </div>

      <div class="provider-table">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Provider Name</th>
              <th>Type</th>
              <th>Contact Person</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Service Area</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="providersTableBody">
            <!-- Providers will be loaded here -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // User role variables
    const isAdmin = <?php echo json_encode($isAdmin); ?>;
    const isProvider = <?php echo json_encode($isProvider); ?>;
    const isUser = <?php echo json_encode($isUser); ?>;

    // Sidebar toggle
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('mainContent');

    if (hamburger && sidebar) {
      hamburger.addEventListener('click', function() {
        sidebar.classList.toggle('show');
        content.classList.toggle('expanded');
      });
    }

    // Load providers data
    async function loadProviders() {
      try {
        const response = await fetch('../../api/providers.php');
        const providers = await response.json();
        
        const tbody = document.getElementById('providersTableBody');
        tbody.innerHTML = '';
        
        let totalProviders = 0;
        let activeProviders = 0;
        let pendingProviders = 0;
        let recentProviders = 0;
        
        const oneMonthAgo = new Date();
        oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);
        
        providers.forEach(provider => {
          totalProviders++;
          
          if (provider.status && (provider.status.toLowerCase() === 'active' || provider.status === '1')) {
            activeProviders++;
          } else if (provider.status && provider.status.toLowerCase() === 'pending') {
            pendingProviders++;
          }
          
          // Check if provider was created in the last month
          if (provider.created_at && new Date(provider.created_at) > oneMonthAgo) {
            recentProviders++;
          }
          
          const statusClass = provider.status === 'Active' || provider.status === '1' ? 'success' : 
                             provider.status === 'Pending' ? 'warning' : 'secondary';
          
          const statusText = provider.status === '1' ? 'Active' : 
                            provider.status === '0' ? 'Inactive' : provider.status || 'Unknown';
          
          const row = document.createElement('tr');
          row.innerHTML = `
            <td>${provider.id}</td>
            <td><strong>${provider.name || 'N/A'}</strong></td>
            <td>${provider.type || 'N/A'}</td>
            <td>${provider.contact_person || 'N/A'}</td>
            <td>${provider.contact_email || provider.email || 'N/A'}</td>
            <td>${provider.contact_phone || provider.phone || 'N/A'}</td>
            <td>${provider.service_area || 'N/A'}</td>
            <td><span class="badge bg-${statusClass}">${statusText}</span></td>
            <td>
              ${isAdmin ? `
                <button class="btn btn-sm btn-outline-primary me-1" onclick="editProvider(${provider.id})">
                  <i class="bi bi-pencil"></i> Edit
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteProvider(${provider.id}, '${provider.name}')">
                  <i class="bi bi-trash"></i> Delete
                </button>
              ` : `
                <button class="btn btn-sm btn-outline-info" onclick="viewProvider(${provider.id})">
                  <i class="bi bi-eye"></i> View
                </button>
              `}
            </td>
          `;
          tbody.appendChild(row);
        });
        
        // Update stats
        document.getElementById('totalProviders').textContent = totalProviders;
        document.getElementById('activeProviders').textContent = activeProviders;
        document.getElementById('pendingProviders').textContent = pendingProviders;
        document.getElementById('recentProviders').textContent = recentProviders;
        
      } catch (error) {
        console.error('Error loading providers:', error);
        Swal.fire('Error', 'Failed to load service providers', 'error');
      }
    }

    // Edit provider function (admin only)
    function editProvider(providerId) {
      if (!isAdmin) {
        Swal.fire('Access Denied', 'You do not have permission to edit providers', 'warning');
        return;
      }
      
      Swal.fire('Info', 'Edit provider functionality will be implemented soon', 'info');
    }

    // Delete provider function (admin only)
    function deleteProvider(providerId, providerName) {
      if (!isAdmin) {
        Swal.fire('Access Denied', 'You do not have permission to delete providers', 'warning');
        return;
      }
      
      Swal.fire({
        title: 'Delete Provider',
        text: `Are you sure you want to delete provider "${providerName}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire('Info', 'Delete provider functionality will be implemented soon', 'info');
        }
      });
    }

    // View provider function
    function viewProvider(providerId) {
      Swal.fire('Info', 'View provider details functionality will be implemented soon', 'info');
    }

    // Show add provider modal (admin only)
    function showAddProviderModal() {
      if (!isAdmin) {
        Swal.fire('Access Denied', 'You do not have permission to add providers', 'warning');
        return;
      }
      
      Swal.fire('Info', 'Add provider functionality will be implemented soon', 'info');
    }

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
      loadProviders();
    });

    // Auto-refresh every 5 minutes
    setInterval(loadProviders, 300000);
  </script>
</body>
</html>
