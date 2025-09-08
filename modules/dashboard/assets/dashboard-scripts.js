/**
 * Dashboard Module Scripts
 * CORE II - Admin Dashboard Module
 */

// Global Variables
let analyticsChart = null;
let dashboardData = null;
let currentChartView = 'week';

// Sidebar toggle with overlay
const hamburger = document.getElementById('hamburger');
const sidebar = document.getElementById('sidebar');
const content = document.getElementById('mainContent');

// Create overlay element
const overlay = document.createElement('div');
overlay.className = 'sidebar-overlay';
overlay.style.cssText = `
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  z-index: 999;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
`;
document.body.appendChild(overlay);

function toggleSidebar() {
  if (sidebar) {
    sidebar.classList.toggle('show');
    content.classList.toggle('expanded');
    
    if (sidebar.classList.contains('show')) {
      overlay.style.opacity = '1';
      overlay.style.visibility = 'visible';
      document.body.style.overflow = 'hidden';
    } else {
      overlay.style.opacity = '0';
      overlay.style.visibility = 'hidden';
      document.body.style.overflow = 'auto';
    }
  }
}

if (hamburger) {
  hamburger.addEventListener('click', toggleSidebar);
}

if (overlay) {
  overlay.addEventListener('click', toggleSidebar);
}

// Close sidebar when clicking on content on mobile
if (window.innerWidth <= 992) {
  content.addEventListener('click', function(e) {
    if (sidebar && sidebar.classList.contains('show') && !sidebar.contains(e.target)) {
      toggleSidebar();
    }
  });
}

// Theme toggle functionality with enhanced state management
const themeToggle = document.getElementById('themeToggle');
const body = document.body;

// Initialize theme system
function initializeTheme() {
  // Check for saved theme preference or system preference
  const savedTheme = localStorage.getItem('theme');
  const systemDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const defaultTheme = savedTheme || (systemDarkMode ? 'dark' : 'light');
  
  if (defaultTheme === 'dark') {
    body.classList.add('dark-mode');
    if (themeToggle) themeToggle.checked = true;
  }
  
  // Update CSS variables for theme
  updateThemeVariables(defaultTheme);
}

// Update CSS variables based on theme
function updateThemeVariables(theme) {
  const root = document.documentElement;
  
  if (theme === 'dark') {
    root.style.setProperty('--bg-primary', '#1a1a2e');
    root.style.setProperty('--bg-secondary', '#16213e');
    root.style.setProperty('--text-primary', '#f8f9fa');
    root.style.setProperty('--text-secondary', '#adb5bd');
  } else {
    root.style.setProperty('--bg-primary', '#ffffff');
    root.style.setProperty('--bg-secondary', '#f8f9fc');
    root.style.setProperty('--text-primary', '#212529');
    root.style.setProperty('--text-secondary', '#6c757d');
  }
}

// Theme toggle event listener with animation
if (themeToggle) {
  themeToggle.addEventListener('change', function() {
    const newTheme = this.checked ? 'dark' : 'light';
    
    // Add transition class for smooth theme change
    body.classList.add('theme-transition');
    
    if (this.checked) {
      body.classList.add('dark-mode');
      localStorage.setItem('theme', 'dark');
    } else {
      body.classList.remove('dark-mode');
      localStorage.setItem('theme', 'light');
    }
    
    updateThemeVariables(newTheme);
    
    // Theme changed silently
    
    // Remove transition class after animation
    setTimeout(() => {
      body.classList.remove('theme-transition');
    }, 300);
  });
}

// Listen for system theme changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
  if (!localStorage.getItem('theme')) {
    // Only auto-switch if user hasn't manually set a preference
    const newTheme = e.matches ? 'dark' : 'light';
    
    body.classList.toggle('dark-mode', e.matches);
    if (themeToggle) themeToggle.checked = e.matches;
    updateThemeVariables(newTheme);
    
    // Auto-theme changed silently
  }
});

// Initialize theme on page load
initializeTheme();

// Add theme transition CSS
const themeTransitionStyle = document.createElement('style');
themeTransitionStyle.textContent = `
  .theme-transition,
  .theme-transition *,
  .theme-transition *:before,
  .theme-transition *:after {
    transition: all 0.3s ease !important;
    transition-delay: 0 !important;
  }
`;
document.head.appendChild(themeTransitionStyle);

// Load users function
async function loadUsers() {
  try {
    const response = await fetch('../../api/users.php');
    const users = await response.json();
    
    const tbody = document.getElementById('usersTableBody');
    if (tbody) {
      tbody.innerHTML = '';
      
      users.forEach(user => {
        const row = document.createElement('tr');
        const roleValue = (user.role || '').toString().toLowerCase();
        const roleClass = roleValue === 'admin' ? 'bg-danger' : roleValue === 'provider' ? 'bg-info' : 'bg-secondary';
        row.innerHTML = `
          <td>${user.id}</td>
          <td>${user.username}</td>
          <td>${user.email}</td>
          <td><span class="badge ${roleClass}">${user.role}</span></td>
          <td><span class="badge bg-${user.is_active ? 'success' : 'warning'}">${user.is_active ? 'Active' : 'Inactive'}</span></td>
          <td>${user.last_login || 'Never'}</td>
          <td>
            <button class="btn btn-sm btn-outline-primary me-1" onclick="editUser(${user.id})">Edit</button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id}, '${user.username}')">Delete</button>
          </td>
        `;
        tbody.appendChild(row);
      });
    }
  } catch (error) {
    console.error('Error loading users:', error);
  }
}

// Change password function
const changePasswordForm = document.getElementById('changePasswordForm');
if (changePasswordForm) {
  changePasswordForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword !== confirmPassword) {
      Swal.fire('Error', 'New passwords do not match!', 'error');
      return;
    }
    
    if (newPassword.length < 6) {
      Swal.fire('Error', 'Password must be at least 6 characters long!', 'error');
      return;
    }
    
    try {
      const response = await fetch('../../api/change-password.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          currentPassword: currentPassword,
          newPassword: newPassword
        })
      });
      
      const result = await response.json();
      
      if (result.success) {
        Swal.fire('Success!', 'Password changed successfully!', 'success');
        document.getElementById('changePasswordForm').reset();
      } else {
        Swal.fire('Error', result.message || 'Failed to change password', 'error');
      }
    } catch (error) {
      Swal.fire('Error', 'Error changing password', 'error');
    }
  });
}

// Session monitoring and management
let sessionCheckInterval;
let sessionWarningShown = false;

// Check session status every 30 seconds
sessionCheckInterval = setInterval(checkSessionStatus, 30000);

async function checkSessionStatus() {
  try {
    const response = await fetch('../../auth.php?check_session=1');
    const result = await response.json();
    
    if (!result.success || !result.logged_in) {
      // Session expired or invalid
      clearInterval(sessionCheckInterval);
      
      Swal.fire({
        title: 'Session Expired',
        text: 'Your session has expired. Please log in again.',
        icon: 'warning',
        confirmButtonText: 'Go to Login',
        allowOutsideClick: false,
        allowEscapeKey: false
      }).then(() => {
        window.location.href = '../../login.php?session_expired=1';
      });
    } else if (result.time_remaining && result.time_remaining < 300000 && !sessionWarningShown) {
      // Less than 5 minutes remaining
      sessionWarningShown = true;
      showSessionWarning(Math.floor(result.time_remaining / 60000));
    }
  } catch (error) {
    console.warn('Session check failed:', error);
  }
}

// Show session warning dialog
function showSessionWarning(minutesLeft) {
  Swal.fire({
    title: 'Session Expiring Soon',
    html: `Your session will expire in <b>${minutesLeft}</b> minutes.<br>Would you like to extend your session?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#f59e0b',
    cancelButtonColor: '#dc2626',
    confirmButtonText: 'Extend Session',
    cancelButtonText: 'Logout Now',
    allowOutsideClick: false
  }).then((result) => {
    if (result.isConfirmed) {
      extendSession();
    } else if (result.isDismissed) {
      confirmLogout();
    }
  });
}

// Extend session function
async function extendSession() {
  try {
    const response = await fetch('../../auth.php?extend_session=1');
    const result = await response.json();
    
    if (result.success) {
      sessionWarningShown = false;
      Swal.fire({
        title: 'Session Extended!',
        text: 'Your session has been extended successfully.',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      });
    } else {
      throw new Error('Failed to extend session');
    }
  } catch (error) {
    console.error('Session extension failed:', error);
    Swal.fire({
      title: 'Extension Failed',
      text: 'Unable to extend session. Please log in again.',
      icon: 'error'
    }).then(() => {
      window.location.href = '../../login.php';
    });
  }
}

// Logout confirmation with SweetAlert2
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
      window.location.href = '../../auth.php?logout=1';
    }
  });
}

// Edit user function
function editUser(userId) {
  fetch(`../../api/users.php?id=${userId}`)
    .then(res => res.json())
    .then(user => {
      document.getElementById('editUserId').value = user.id;
      document.getElementById('editUsername').value = user.username;
      document.getElementById('editEmail').value = user.email;
      document.getElementById('editRole').value = user.role;
      document.getElementById('editStatus').value = user.is_active ? '1' : '0';
      new bootstrap.Modal(document.getElementById('editUserModal')).show();
    })
    .catch(() => Swal.fire('Error', 'Failed to load user details', 'error'));
}

// Handle edit user form submit
const editUserForm = document.getElementById('editUserForm');
if (editUserForm) {
  editUserForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Show loading state
    const submitBtn = document.getElementById('editUserSubmitBtn');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Saving...';
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    
    // Get form data
    const id = document.getElementById('editUserId').value;
    const username = document.getElementById('editUsername').value.trim();
    const email = document.getElementById('editEmail').value.trim();
    const role = document.getElementById('editRole').value;
    const is_active = document.getElementById('editStatus').value;
    
    // Client-side validation
    const errors = [];
    if (!id) errors.push('User ID is missing');
    if (!username) errors.push('Username is required');
    if (!email) errors.push('Email is required');
    if (!role) errors.push('Role selection is required');
    if (!email.includes('@')) errors.push('Please enter a valid email address');
    
    if (errors.length > 0) {
      // Reset button state
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
      submitBtn.classList.remove('loading');
      
      Swal.fire({
        title: 'Validation Error',
        html: errors.join('<br>'),
        icon: 'error'
      });
      return;
    }

    try {
      const response = await fetch('../../api/edit-user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, username, email, role, is_active })
      });
      
      const result = await response.json();
      
      // Reset button state
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
      submitBtn.classList.remove('loading');
      
      if (result.success) {
        const Toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
          }
        });
        
        Toast.fire({
          icon: 'success',
          title: 'User updated successfully!'
        });
        
        bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
        loadUsers(); // Reload users table
      } else {
        Swal.fire({
          title: 'Error',
          text: result.message || 'Failed to update user',
          icon: 'error'
        });
      }
    } catch (error) {
      // Reset button state
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
      submitBtn.classList.remove('loading');
      
      console.error('Edit user error:', error);
      Swal.fire({
        title: 'Connection Error',
        text: 'Failed to update user. Please check your connection and try again.',
        icon: 'error'
      });
    }
  });
}

// Show add user modal
function showAddUserModal() {
  const addUserForm = document.getElementById('addUserForm');
  if (addUserForm) {
    addUserForm.reset();
    new bootstrap.Modal(document.getElementById('addUserModal')).show();
  }
}

// Handle add user form submit
const addUserForm = document.getElementById('addUserForm');
if (addUserForm) {
  addUserForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Show loading state
    const submitBtn = document.getElementById('addUserSubmitBtn');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Creating...';
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    
    // Get form data
    const username = document.getElementById('addUsername').value.trim();
    const email = document.getElementById('addEmail').value.trim();
    const password = document.getElementById('addPassword').value;
    const role = document.getElementById('addRole').value;
    const is_active = document.getElementById('addStatus').value;
    
    // Client-side validation
    const errors = [];
    if (!username) errors.push('Username is required');
    if (!email) errors.push('Email is required');
    if (!password) errors.push('Password is required');
    if (password.length < 6) errors.push('Password must be at least 6 characters');
    if (!role) errors.push('Role selection is required');
    if (!email.includes('@')) errors.push('Please enter a valid email address');
    
    if (errors.length > 0) {
      // Reset button state
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
      submitBtn.classList.remove('loading');
      
      Swal.fire({
        title: 'Validation Error',
        html: errors.join('<br>'),
        icon: 'error'
      });
      return;
    }

    try {
      const response = await fetch('../../api/add-user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, email, password, role, is_active })
      });
      
      const result = await response.json();
      
      // Reset button state
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
      submitBtn.classList.remove('loading');
      
      if (result.success) {
        const Toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
          }
        });
        
        Toast.fire({
          icon: 'success',
          title: 'User added successfully!'
        });
        
        bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
        this.reset(); // Reset form
        loadUsers(); // Reload users table
      } else {
        Swal.fire({
          title: 'Error',
          text: result.message || 'Failed to add user',
          icon: 'error'
        });
      }
    } catch (error) {
      // Reset button state
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
      submitBtn.classList.remove('loading');
      
      console.error('Add user error:', error);
      Swal.fire({
        title: 'Connection Error',
        text: 'Failed to add user. Please check your connection and try again.',
        icon: 'error'
      });
    }
  });
}

// Delete user function
function deleteUser(userId, username) {
  Swal.fire({
    title: 'Delete User',
    text: `Are you sure you want to delete user "${username}"? This action cannot be undone.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, Delete',
    cancelButtonText: 'Cancel'
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        const response = await fetch('../../api/delete-user.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: userId })
        });
        
        const deleteResult = await response.json();
        
        if (deleteResult.success) {
          Swal.fire('Deleted!', 'User has been deleted successfully.', 'success');
          loadUsers(); // Reload the users table
        } else {
          Swal.fire('Error', deleteResult.message || 'Failed to delete user', 'error');
        }
      } catch {
        Swal.fire('Error', 'Failed to delete user', 'error');
      }
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
  if (overlay) overlay.classList.add('show');
}

function hideLoading() {
  const overlay = document.getElementById('loadingOverlay');
  if (overlay) overlay.classList.remove('show');
}

// Modern Modal Functions
function togglePasswordVisibility(passwordFieldId) {
  const passwordField = document.getElementById(passwordFieldId);
  if (passwordField) {
    const toggleButton = passwordField.parentElement.querySelector('.password-toggle i');
    
    if (passwordField.type === 'password') {
      passwordField.type = 'text';
      if (toggleButton) toggleButton.className = 'bi bi-eye-slash';
    } else {
      passwordField.type = 'password';
      if (toggleButton) toggleButton.className = 'bi bi-eye';
    }
  }
}

// Password strength checker
function checkPasswordStrength(password) {
  let strength = 0;
  let feedback = 'Enter password';
  
  if (password.length >= 6) strength += 1;
  if (password.length >= 10) strength += 1;
  if (/[a-z]/.test(password)) strength += 1;
  if (/[A-Z]/.test(password)) strength += 1;
  if (/[0-9]/.test(password)) strength += 1;
  if (/[^A-Za-z0-9]/.test(password)) strength += 1;
  
  const strengthTexts = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
  feedback = password ? strengthTexts[Math.min(strength, 5)] : 'Enter password';
  
  return { strength: (strength / 6) * 100, feedback };
}

// Success message function
function showSuccessMessage(message) {
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Success!',
      text: message,
      icon: 'success',
      timer: 2000,
      showConfirmButton: false
    });
  } else {
    console.log(message);
  }
}

// Dashboard Data Loading Functions
async function loadDashboardData() {
  try {
    // Show loading state
    showLoadingState();
    
    const response = await fetch('api/dashboard-summary.php', {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });
    
    if (!response.ok) {
      // If authentication required, show appropriate message
      if (response.status === 401) {
        console.warn('Dashboard requires authentication - using fallback data');
        loadFallbackData();
        return;
      }
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (data.status === 'success' && data.summary) {
      dashboardData = data;
      
      // Update components with data validation
      updateSystemOverview(data.summary);
      updateSummaryCards(data.summary, data.growth || {});
      
      if (data.timeSeries && Array.isArray(data.timeSeries) && data.timeSeries.length > 0) {
        updateAnalyticsChart(data.timeSeries);
      } else {
        console.warn('No valid time series data - generating sample data');
        updateAnalyticsChart(generateSampleTimeSeriesData());
      }
      
      hideLoadingStates();
      // Dashboard loaded successfully
    } else if (data.status === 'error' && data.error === 'Unauthorized access') {
      console.warn('Unauthorized access - using fallback data');
      loadFallbackData();
    } else {
      throw new Error(data.error || 'Invalid data format received');
    }
  } catch (error) {
    console.error('Error loading dashboard data:', error);
    
    // Try fallback data instead of showing error
    console.log('Attempting to load fallback data...');
    try {
      loadFallbackData();
    } catch (fallbackError) {
      console.error('Fallback data loading failed:', fallbackError);
      showErrorState('Unable to load dashboard data. Please refresh the page.');
    }
  }
}

function showLoadingState() {
  const loadingElement = document.getElementById('summaryCardsLoading');
  const containerElement = document.getElementById('summaryCardsContainer');
  
  if (loadingElement) {
    loadingElement.style.display = 'block';
    loadingElement.innerHTML = `
      <div class="col-12 text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Loading dashboard data...</p>
      </div>
    `;
  }
  if (containerElement) {
    containerElement.style.display = 'none';
  }
}

// Hide loading states and show content
function hideLoadingStates() {
  const loadingElement = document.getElementById('summaryCardsLoading');
  const containerElement = document.getElementById('summaryCardsContainer');
  
  if (loadingElement) loadingElement.style.display = 'none';
  if (containerElement) containerElement.style.display = 'flex';
}

// Show Error State
function showErrorState(message) {
  const container = document.getElementById('summaryCardsContainer');
  if (container) {
    container.innerHTML = `
      <div class="col-12 text-center py-4">
        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
        <p class="mt-2 text-muted">${message || 'Failed to load dashboard data'}</p>
        <button class="btn btn-outline-primary btn-sm" onclick="loadDashboardData()">
          <i class="bi bi-arrow-clockwise"></i> Retry
        </button>
      </div>
    `;
    document.getElementById('summaryCardsLoading').style.display = 'none';
    document.getElementById('summaryCardsContainer').style.display = 'flex';
  }
}

// Load fallback dashboard data
function loadFallbackData() {
  const fallbackData = {
    status: 'success',
    summary: {
      providers: { total: 25, active: 23, recent: 3, percentage: 92, page: '../../provider-dashboard.php' },
      routes: { total: 48, active: 45, recent: 5, percentage: 94, page: '../../schedules.php' },
      schedules: { total: 156, active: 148, recent: 12, percentage: 95, page: '../../schedules.php' },
      service_points: { total: 89, active: 85, recent: 7, percentage: 96, page: '../../service-network.php' },
      sops: { total: 34, active: 32, recent: 2, percentage: 94, page: '../../sop-manager.php' },
      tariffs: { total: 18, active: 17, recent: 1, percentage: 94, page: '../../rate-tariff.php' }
    },
    growth: {
      providers: 12.5,
      routes: 8.3,
      schedules: 15.2,
      service_points: 10.7,
      sops: 5.9,
      tariffs: 6.2
    },
    timeSeries: generateSampleTimeSeriesData()
  };
  
  updateSystemOverview(fallbackData.summary);
  updateSummaryCards(fallbackData.summary, fallbackData.growth);
  updateAnalyticsChart(fallbackData.timeSeries);
  hideLoadingStates();
}

// Generate sample time series data
function generateSampleTimeSeriesData(period = 'week') {
  const timeSeries = [];
  const days = (period === 'month') ? 30 : 7;
  
  for (let i = days - 1; i >= 0; i--) {
    const date = new Date();
    date.setDate(date.getDate() - i);
    const dateLabel = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    
    // Generate realistic sample data
    const baseCount = 50 + (days - i) * 2;
    
    const dayData = {
      date: date.toISOString().split('T')[0],
      label: dateLabel,
      providers: baseCount + Math.floor(Math.random() * 20) - 5,
      routes: baseCount + Math.floor(Math.random() * 30) - 10,
      schedules: baseCount + Math.floor(Math.random() * 26) - 8,
      service_points: baseCount + Math.floor(Math.random() * 37) - 12,
      sops: baseCount + Math.floor(Math.random() * 17) - 5,
      tariffs: baseCount + Math.floor(Math.random() * 11) - 3,
      total: 0
    };
    
    // Calculate total
    dayData.total = dayData.providers + dayData.routes + dayData.schedules + 
                   dayData.service_points + dayData.sops + dayData.tariffs;
    
    timeSeries.push(dayData);
  }
  
  return timeSeries;
}

// Update System Overview (small card)
function updateSystemOverview(summary) {
  const container = document.getElementById('systemOverview');
  if (!container) return;
  
  const totalModules = Object.keys(summary).length;
  const totalItems = summary.providers.total + summary.routes.total + summary.schedules.total + 
                    summary.service_points.total + summary.sops.total + summary.tariffs.total;
  
  container.innerHTML = `
    <div class="text-center">
      <h2 class="text-primary mb-2">${totalItems}</h2>
      <p class="mb-1">Total Items Managed</p>
      <small class="text-muted">Across ${totalModules} modules</small>
      <div class="mt-3">
        <div class="progress" style="height: 6px;">
          <div class="progress-bar bg-success" role="progressbar" style="width: 75%"></div>
        </div>
        <small class="text-muted mt-1 d-block">System Health: 75%</small>
      </div>
    </div>
  `;
}

// Update Summary Cards
function updateSummaryCards(summary, growth) {
  const container = document.getElementById('summaryCardsContainer');
  if (!container) return;
  
  container.innerHTML = '';
  
  const moduleOrder = ['providers', 'routes', 'schedules', 'service_points', 'sops', 'tariffs'];
  
  moduleOrder.forEach((module, index) => {
    if (summary[module]) {
      const moduleData = summary[module];
      const growthData = growth[module] || 0;
      const moduleConfig = MODULES_CONFIG[module] || { name: module, icon: 'bi-circle', color: 'primary' };
      
      const colClass = moduleOrder.length === 6 ? 'col-xl-2 col-lg-4 col-md-6 col-sm-6' : 'col-lg-3 col-md-6';
      
      const card = document.createElement('div');
      card.className = colClass;
      
      // Create clickable card with proper navigation
      const pageUrl = moduleData.page || '#';
      const isClickable = pageUrl !== '#';
      
      card.innerHTML = `
        <div class="card summary-card h-100 ${isClickable ? 'clickable-card' : ''}" ${isClickable ? `onclick="navigateToModule('${pageUrl}', '${moduleConfig.name}')"` : ''} ${isClickable ? 'style="cursor: pointer;"' : ''}>
          <div class="card-body text-center">
            <div class="mb-3">
              <i class="${moduleData.icon || moduleConfig.icon} text-${moduleConfig.color}" style="font-size: 2.5rem;"></i>
            </div>
            <h3 class="text-${moduleConfig.color} mb-2">${moduleData.total}</h3>
            <h6 class="text-muted mb-3">${moduleConfig.name}</h6>
            <div class="row small">
              <div class="col-6">
                <div class="text-success fw-bold">${moduleData.active}</div>
                <div class="text-muted">Active</div>
              </div>
              <div class="col-6">
                <div class="text-info fw-bold">${moduleData.recent}</div>
                <div class="text-muted">Recent</div>
              </div>
            </div>
            <div class="mt-3">
              <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-${moduleConfig.color}" role="progressbar" style="width: ${moduleData.percentage}%"></div>
              </div>
              <small class="text-muted mt-1">
                ${growthData > 0 ? '+' : ''}${growthData}% vs last month
              </small>
            </div>
            ${isClickable ? '<div class="card-overlay"><i class="bi bi-arrow-right-circle"></i></div>' : ''}
          </div>
        </div>
      `;
      container.appendChild(card);
    }
  });
}

// Update Analytics Chart
function updateAnalyticsChart(timeSeries) {
  const ctx = document.getElementById('analyticsChart');
  if (!ctx) return;
  
  // Destroy existing chart if it exists
  if (analyticsChart) {
    analyticsChart.destroy();
  }
  
  const labels = timeSeries.map(item => item.label);
  const colors = DASHBOARD_CONFIG.chart_settings.colors;
  
  const datasets = [
    {
      label: 'Providers',
      data: timeSeries.map(item => item.providers),
      borderColor: colors.primary,
      backgroundColor: colors.primary + '20',
      fill: false
    },
    {
      label: 'Routes',
      data: timeSeries.map(item => item.routes),
      borderColor: colors.info,
      backgroundColor: colors.info + '20',
      fill: false
    },
    {
      label: 'Schedules',
      data: timeSeries.map(item => item.schedules),
      borderColor: colors.warning,
      backgroundColor: colors.warning + '20',
      fill: false
    },
    {
      label: 'Service Points',
      data: timeSeries.map(item => item.service_points),
      borderColor: colors.success,
      backgroundColor: colors.success + '20',
      fill: false
    },
    {
      label: 'SOPs',
      data: timeSeries.map(item => item.sops),
      borderColor: colors.secondary,
      backgroundColor: colors.secondary + '20',
      fill: false
    },
    {
      label: 'Tariffs',
      data: timeSeries.map(item => item.tariffs),
      borderColor: colors.danger,
      backgroundColor: colors.danger + '20',
      fill: false
    }
  ];
  
  analyticsChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: datasets
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: `System Analytics - Last ${currentChartView === 'week' ? '7 Days' : '30 Days'}`
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0,0,0,0.1)'
          }
        },
        x: {
          grid: {
            color: 'rgba(0,0,0,0.1)'
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

// Switch chart view
function switchChartView(period) {
  currentChartView = period;
  
  // Update button states
  document.querySelectorAll('#weekView, #monthView').forEach(btn => {
    btn.classList.remove('active');
  });
  document.getElementById(period + 'View').classList.add('active');
  
  // Regenerate chart with new period
  const newTimeSeries = generateSampleTimeSeriesData(period);
  updateAnalyticsChart(newTimeSeries);
}

// Refresh dashboard data
function refreshDashboardData() {
  loadDashboardData();
}

// Navigate to module page
function navigateToModule(pageUrl, moduleName) {
  // Show loading state
  showLoading(`Loading ${moduleName}...`, 'Redirecting to module page');
  
  // Add a small delay for better UX
  setTimeout(() => {
    window.location.href = pageUrl;
  }, 500);
}

// Real-time form validation
function setupFormValidation() {
  const forms = document.querySelectorAll('.modern-modal form');
  
  forms.forEach(form => {
    const inputs = form.querySelectorAll('.modern-form-control');
    
    inputs.forEach(input => {
      input.addEventListener('input', function() {
        validateField(this);
        
        // Special handling for password field
        if (this.type === 'password' && this.id === 'addPassword') {
          const passwordStrength = checkPasswordStrength(this.value);
          const strengthBar = this.parentElement.parentElement.querySelector('.strength-bar');
          const strengthText = this.parentElement.parentElement.querySelector('.strength-text');
          
          if (strengthBar) {
            strengthBar.style.setProperty('--width', passwordStrength.strength + '%');
            strengthBar.style.background = getStrengthColor(passwordStrength.strength);
          }
          if (strengthText) {
            strengthText.textContent = passwordStrength.feedback;
          }
        }
      });
      
      input.addEventListener('blur', function() {
        validateField(this);
      });
    });
  });
}

function validateField(field) {
  const feedback = field.parentElement.querySelector('.validation-feedback');
  let isValid = field.checkValidity();
  let message = '';
  
  // Custom validation rules
  if (field.type === 'email' && field.value && !field.value.includes('@')) {
    isValid = false;
    message = 'Please enter a valid email address';
  }
  
  if (field.id === 'addPassword' && field.value && field.value.length < 6) {
    isValid = false;
    message = 'Password must be at least 6 characters long';
  }
  
  if (field.required && !field.value.trim()) {
    isValid = false;
    message = 'This field is required';
  }
  
  // Update UI based on validation
  if (feedback) {
    if (isValid && field.value.trim()) {
      feedback.className = 'validation-feedback valid';
      feedback.textContent = '✓ Looks good!';
      field.classList.remove('is-invalid');
      field.classList.add('is-valid');
    } else if (!isValid && field.value.trim()) {
      feedback.className = 'validation-feedback invalid';
      feedback.textContent = message || field.validationMessage;
      field.classList.remove('is-valid');
      field.classList.add('is-invalid');
    } else {
      feedback.className = 'validation-feedback';
      feedback.textContent = '';
      field.classList.remove('is-valid', 'is-invalid');
    }
  }
  
  return isValid;
}

function getStrengthColor(strength) {
  if (strength < 30) return '#dc3545';
  if (strength < 60) return '#ffc107';
  return '#28a745';
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  // Show initial loading
  showLoading('Loading Dashboard...', 'Preparing admin dashboard');
  
  // Setup form validation
  setupFormValidation();
  
  // Load initial data
  setTimeout(() => {
    loadUsers();
    loadDashboardData();
    hideLoading();
  }, 1200);
  
  // Auto-refresh dashboard data every 5 minutes
  setInterval(loadDashboardData, 300000);
  
  // Add password strength styling
  const style = document.createElement('style');
  style.textContent = `
    .strength-bar::before {
      width: var(--width, 0%);
    }
  `;
  document.head.appendChild(style);
});
