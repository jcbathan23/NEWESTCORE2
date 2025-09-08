<?php
// Universal Dark Mode CSS Styles
// Include this file in all modules to ensure consistent dark mode styling
?>
<style>
/* Universal Dark Mode Root Variables */
:root {
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

/* Universal Dark Mode Body Styles */
body.dark-mode {
  background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%) !important;
  color: var(--text-light) !important;
  min-height: 100vh;
}

/* Universal Dark Mode Text Visibility */
.dark-mode h1, 
.dark-mode h2, 
.dark-mode h3, 
.dark-mode h4, 
.dark-mode h5, 
.dark-mode h6 {
  color: var(--text-light) !important;
}

.dark-mode p,
.dark-mode span,
.dark-mode div,
.dark-mode li,
.dark-mode td,
.dark-mode th,
.dark-mode label,
.dark-mode a:not(.btn):not(.nav-link) {
  color: var(--text-light) !important;
}

.dark-mode .text-muted {
  color: #adb5bd !important;
}

.dark-mode .text-primary {
  color: #667eea !important;
}

.dark-mode .text-success {
  color: var(--success-color) !important;
}

.dark-mode .text-info {
  color: var(--info-color) !important;
}

.dark-mode .text-warning {
  color: var(--warning-color) !important;
}

.dark-mode .text-danger {
  color: var(--danger-color) !important;
}

.dark-mode .text-secondary {
  color: #adb5bd !important;
}

.dark-mode .text-dark {
  color: var(--text-light) !important;
}

/* Universal Dark Mode Form Elements */
.dark-mode .form-control,
.dark-mode .form-select,
.dark-mode .form-check-input,
.dark-mode input[type="text"],
.dark-mode input[type="email"],
.dark-mode input[type="password"],
.dark-mode input[type="number"],
.dark-mode input[type="tel"],
.dark-mode input[type="url"],
.dark-mode textarea,
.dark-mode select {
  background-color: rgba(44, 62, 80, 0.8) !important;
  border-color: rgba(255, 255, 255, 0.2) !important;
  color: var(--text-light) !important;
}

.dark-mode .form-control:focus,
.dark-mode .form-select:focus,
.dark-mode input:focus,
.dark-mode textarea:focus,
.dark-mode select:focus {
  background-color: rgba(44, 62, 80, 0.9) !important;
  border-color: var(--primary-color) !important;
  box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25) !important;
  color: var(--text-light) !important;
}

.dark-mode .form-control::placeholder,
.dark-mode input::placeholder,
.dark-mode textarea::placeholder {
  color: #adb5bd !important;
}

.dark-mode .form-label {
  color: var(--text-light) !important;
  font-weight: 500;
}

.dark-mode .form-text {
  color: #adb5bd !important;
}

/* Universal Dark Mode Card Styles */
.dark-mode .card {
  background: rgba(44, 62, 80, 0.9) !important;
  color: var(--text-light) !important;
  border: 1px solid rgba(255,255,255,0.1) !important;
}

.dark-mode .card-header {
  background-color: rgba(44, 62, 80, 0.6) !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
  color: var(--text-light) !important;
}

.dark-mode .card-body {
  color: var(--text-light) !important;
}

.dark-mode .card-footer {
  background-color: rgba(44, 62, 80, 0.6) !important;
  border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
  color: var(--text-light) !important;
}

.dark-mode .card-title,
.dark-mode .card-subtitle {
  color: var(--text-light) !important;
}

/* Universal Dark Mode Table Styles */
.dark-mode .table {
  color: var(--text-light) !important;
  background-color: transparent !important;
}

.dark-mode .table th {
  background-color: rgba(44, 62, 80, 0.7) !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
  color: var(--text-light) !important;
}

.dark-mode .table td {
  border-color: rgba(255, 255, 255, 0.1) !important;
  color: var(--text-light) !important;
  background-color: transparent !important;
}

.dark-mode .table tbody tr {
  background-color: transparent !important;
}

.dark-mode .table tbody tr td {
  color: var(--text-light) !important;
}

.dark-mode .table-hover tbody tr:hover {
  background-color: rgba(255, 255, 255, 0.05) !important;
}

.dark-mode .table-hover tbody tr:hover td {
  color: var(--text-light) !important;
}

.dark-mode .table-striped tbody tr:nth-of-type(odd) {
  background-color: rgba(255, 255, 255, 0.03) !important;
}

.dark-mode .table-striped tbody tr:nth-of-type(odd) td {
  color: var(--text-light) !important;
}

/* Enhanced table content visibility */
.dark-mode table {
  color: var(--text-light) !important;
}

.dark-mode table th,
.dark-mode table td {
  color: var(--text-light) !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

.dark-mode table thead th {
  background-color: rgba(44, 62, 80, 0.8) !important;
  color: var(--text-light) !important;
}

.dark-mode table tbody td {
  background-color: transparent !important;
  color: var(--text-light) !important;
}

/* Table responsive wrapper */
.dark-mode .table-responsive {
  color: var(--text-light) !important;
}

.dark-mode .table-responsive table {
  color: var(--text-light) !important;
}

.dark-mode .table-responsive th,
.dark-mode .table-responsive td {
  color: var(--text-light) !important;
}

/* Table cell content styling */
.dark-mode .table td *,
.dark-mode .table th *,
.dark-mode table td *,
.dark-mode table th * {
  color: inherit !important;
}

/* Ensure table badges maintain their colors but with proper contrast */
.dark-mode .table .badge,
.dark-mode table .badge {
  color: white !important;
}

/* Table buttons in dark mode */
.dark-mode .table .btn,
.dark-mode table .btn {
  border: 1px solid rgba(255, 255, 255, 0.2) !important;
}

.dark-mode .table .btn-outline-primary,
.dark-mode table .btn-outline-primary {
  color: var(--primary-color) !important;
  border-color: var(--primary-color) !important;
}

.dark-mode .table .btn-outline-danger,
.dark-mode table .btn-outline-danger {
  color: var(--danger-color) !important;
  border-color: var(--danger-color) !important;
}

/* Specific table data content */
.dark-mode .table tbody tr td,
.dark-mode table tbody tr td {
  color: var(--text-light) !important;
}

.dark-mode .table tbody tr td span,
.dark-mode .table tbody tr td div,
.dark-mode .table tbody tr td p,
.dark-mode table tbody tr td span,
.dark-mode table tbody tr td div,
.dark-mode table tbody tr td p {
  color: var(--text-light) !important;
}

/* Table section background */
.dark-mode .table-section {
  background: rgba(44, 62, 80, 0.9) !important;
  color: var(--text-light) !important;
  border: 1px solid rgba(255,255,255,0.1) !important;
}

/* Stronger rules for dynamically loaded table content */
.dark-mode #usersTableBody,
.dark-mode #usersTableBody tr,
.dark-mode #usersTableBody td {
  color: var(--text-light) !important;
  background-color: transparent !important;
}

.dark-mode #usersTableBody td * {
  color: var(--text-light) !important;
}

/* Force visibility for all table body elements */
.dark-mode tbody#usersTableBody td,
.dark-mode tbody#usersTableBody tr td,
.dark-mode table tbody#usersTableBody td {
  color: var(--text-light) !important;
  background: transparent !important;
}

/* Override any inline styles that might be added by JavaScript */
.dark-mode .table tbody tr[style] td,
.dark-mode .table tbody td[style],
.dark-mode table tbody tr[style] td,
.dark-mode table tbody td[style] {
  color: var(--text-light) !important;
}

/* Admin table specific styling */
.dark-mode .table.table-hover tbody tr:hover td {
  color: var(--text-light) !important;
  background-color: rgba(255, 255, 255, 0.05) !important;
}

/* Force text visibility in all possible table contexts */
.dark-mode .card .table tbody tr td,
.dark-mode .card .table-responsive .table tbody tr td,
.dark-mode .card .table-responsive table tbody tr td {
  color: var(--text-light) !important;
}

/* Ensure badges in tables maintain visibility */
.dark-mode .table tbody tr td .badge {
  color: white !important;
  opacity: 1 !important;
}

/* Button styling in tables */
.dark-mode .table tbody tr td .btn {
  opacity: 1 !important;
}

/* Universal Dark Mode Modal Styles */
.dark-mode .modal-content {
  background-color: rgba(44, 62, 80, 0.95) !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
  color: var(--text-light) !important;
}

.dark-mode .modal-header {
  border-bottom-color: rgba(255, 255, 255, 0.1) !important;
  color: var(--text-light) !important;
}

.dark-mode .modal-body {
  color: var(--text-light) !important;
}

.dark-mode .modal-footer {
  border-top-color: rgba(255, 255, 255, 0.1) !important;
  color: var(--text-light) !important;
}

.dark-mode .modal-title {
  color: var(--text-light) !important;
}

.dark-mode .modal-header h1,
.dark-mode .modal-header h2,
.dark-mode .modal-header h3,
.dark-mode .modal-header h4,
.dark-mode .modal-header h5,
.dark-mode .modal-header h6 {
  color: var(--text-light) !important;
}

.dark-mode .modal-body h1,
.dark-mode .modal-body h2,
.dark-mode .modal-body h3,
.dark-mode .modal-body h4,
.dark-mode .modal-body h5,
.dark-mode .modal-body h6,
.dark-mode .modal-body p,
.dark-mode .modal-body span,
.dark-mode .modal-body div,
.dark-mode .modal-body label,
.dark-mode .modal-body li,
.dark-mode .modal-body strong,
.dark-mode .modal-body small {
  color: var(--text-light) !important;
}

.dark-mode .modal-footer h1,
.dark-mode .modal-footer h2,
.dark-mode .modal-footer h3,
.dark-mode .modal-footer h4,
.dark-mode .modal-footer h5,
.dark-mode .modal-footer h6,
.dark-mode .modal-footer p,
.dark-mode .modal-footer span,
.dark-mode .modal-footer div,
.dark-mode .modal-footer label,
.dark-mode .modal-footer li,
.dark-mode .modal-footer strong,
.dark-mode .modal-footer small {
  color: var(--text-light) !important;
}

.dark-mode .btn-close {
  filter: invert(1) grayscale(100%) brightness(200%);
}

/* Modal Form Elements in Dark Mode */
.dark-mode .modal-body .form-control,
.dark-mode .modal-body .form-select,
.dark-mode .modal-body input,
.dark-mode .modal-body textarea,
.dark-mode .modal-body select {
  background-color: rgba(44, 62, 80, 0.8) !important;
  border-color: rgba(255, 255, 255, 0.2) !important;
  color: var(--text-light) !important;
}

.dark-mode .modal-body .form-control:focus,
.dark-mode .modal-body .form-select:focus,
.dark-mode .modal-body input:focus,
.dark-mode .modal-body textarea:focus,
.dark-mode .modal-body select:focus {
  background-color: rgba(44, 62, 80, 0.9) !important;
  border-color: var(--primary-color) !important;
  box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25) !important;
  color: var(--text-light) !important;
}

.dark-mode .modal-body .form-control::placeholder,
.dark-mode .modal-body input::placeholder,
.dark-mode .modal-body textarea::placeholder {
  color: #adb5bd !important;
}

.dark-mode .modal-body .form-label {
  color: var(--text-light) !important;
  font-weight: 500;
}

.dark-mode .modal-body .form-text,
.dark-mode .modal-body .text-muted {
  color: #adb5bd !important;
}

/* Modal Backdrop Enhancement */
.dark-mode .modal-backdrop {
  background-color: rgba(0, 0, 0, 0.7) !important;
}

/* Universal Dark Mode Button Styles */
.dark-mode .btn-secondary {
  background-color: rgba(108, 117, 125, 0.8) !important;
  border-color: rgba(108, 117, 125, 0.8) !important;
  color: var(--text-light) !important;
}

.dark-mode .btn-secondary:hover {
  background-color: rgba(108, 117, 125, 1) !important;
  border-color: rgba(108, 117, 125, 1) !important;
}

.dark-mode .btn-outline-secondary {
  color: var(--text-light) !important;
  border-color: rgba(255, 255, 255, 0.3) !important;
}

.dark-mode .btn-outline-secondary:hover {
  background-color: rgba(255, 255, 255, 0.1) !important;
  color: var(--text-light) !important;
}

/* Universal Dark Mode List Styles */
.dark-mode .list-group-item {
  background-color: rgba(44, 62, 80, 0.8) !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
  color: var(--text-light) !important;
}

.dark-mode .list-unstyled li,
.dark-mode .list-group-item {
  color: var(--text-light) !important;
}

.dark-mode .list-unstyled li strong,
.dark-mode strong {
  color: #667eea !important;
}

/* Universal Dark Mode Navigation Styles */
.dark-mode .navbar {
  background-color: rgba(44, 62, 80, 0.9) !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.dark-mode .navbar-nav .nav-link {
  color: var(--text-light) !important;
}

.dark-mode .navbar-brand {
  color: var(--text-light) !important;
}

/* Universal Dark Mode Alert Styles */
.dark-mode .alert {
  border-color: rgba(255, 255, 255, 0.1) !important;
}

.dark-mode .alert-primary {
  background-color: rgba(78, 115, 223, 0.2) !important;
  color: #667eea !important;
}

.dark-mode .alert-success {
  background-color: rgba(28, 200, 138, 0.2) !important;
  color: var(--success-color) !important;
}

.dark-mode .alert-warning {
  background-color: rgba(246, 194, 62, 0.2) !important;
  color: var(--warning-color) !important;
}

.dark-mode .alert-danger {
  background-color: rgba(231, 74, 59, 0.2) !important;
  color: var(--danger-color) !important;
}

.dark-mode .alert-info {
  background-color: rgba(54, 185, 204, 0.2) !important;
  color: var(--info-color) !important;
}

/* Universal Dark Mode Dropdown Styles */
.dark-mode .dropdown-menu {
  background-color: rgba(44, 62, 80, 0.95) !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.dark-mode .dropdown-item {
  color: var(--text-light) !important;
}

.dark-mode .dropdown-item:hover {
  background-color: rgba(255, 255, 255, 0.1) !important;
}

/* Universal Dark Mode Pagination Styles */
.dark-mode .page-link {
  background-color: rgba(44, 62, 80, 0.8) !important;
  border-color: rgba(255, 255, 255, 0.2) !important;
  color: var(--text-light) !important;
}

.dark-mode .page-link:hover {
  background-color: rgba(255, 255, 255, 0.1) !important;
  color: var(--text-light) !important;
}

.dark-mode .page-item.active .page-link {
  background-color: var(--primary-color) !important;
  border-color: var(--primary-color) !important;
}

/* Universal Dark Mode Badge Styles */
.dark-mode .badge {
  color: var(--text-light) !important;
}

/* Universal Dark Mode Progress Styles */
.dark-mode .progress {
  background-color: rgba(255, 255, 255, 0.1) !important;
}

/* Universal Dark Mode Breadcrumb Styles */
.dark-mode .breadcrumb {
  background-color: rgba(44, 62, 80, 0.5) !important;
}

.dark-mode .breadcrumb-item,
.dark-mode .breadcrumb-item a {
  color: var(--text-light) !important;
}

.dark-mode .breadcrumb-item.active {
  color: #adb5bd !important;
}

/* Universal Dark Mode Header Styles */
.dark-mode .header {
  background: rgba(44, 62, 80, 0.9) !important;
  color: var(--text-light) !important;
  border: 1px solid rgba(255,255,255,0.1) !important;
}

/* Universal Dark Mode Sidebar Styles */
.dark-mode .sidebar {
  background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%) !important;
  color: white !important;
}

/* Modern Theme Toggle Styles */
.theme-toggle-container {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem;
  border-radius: 2rem;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dark-mode .theme-toggle-container {
  background: rgba(44, 62, 80, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.theme-toggle-container:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.3);
  transform: translateY(-1px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.dark-mode .theme-toggle-container:hover {
  background: rgba(44, 62, 80, 0.4);
  border-color: rgba(255, 255, 255, 0.25);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.theme-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-dark);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  user-select: none;
  transition: all 0.3s ease;
}

.dark-mode .theme-label {
  color: var(--text-light);
}

.theme-switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 30px;
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
  background: linear-gradient(135deg, #ddd 0%, #f0f0f0 100%);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 30px;
  box-shadow: 
    inset 0 2px 4px rgba(0, 0, 0, 0.1),
    0 2px 8px rgba(0, 0, 0, 0.1);
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.slider:hover {
  transform: scale(1.05);
  box-shadow: 
    inset 0 2px 4px rgba(0, 0, 0, 0.15),
    0 4px 12px rgba(0, 0, 0, 0.15);
}

.slider:before {
  position: absolute;
  content: "☀️";
  height: 22px;
  width: 22px;
  left: 4px;
  bottom: 2px;
  background: linear-gradient(135deg, #fff 0%, #f8f8f8 100%);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  box-shadow: 
    0 2px 6px rgba(0, 0, 0, 0.2),
    0 0 0 1px rgba(255, 255, 255, 0.3);
  border: 1px solid rgba(0, 0, 0, 0.1);
}

input:checked + .slider {
  background: linear-gradient(135deg, var(--primary-color) 0%, #5a67d8 100%);
  box-shadow: 
    inset 0 2px 4px rgba(0, 0, 0, 0.2),
    0 0 15px rgba(78, 115, 223, 0.4);
}

input:checked + .slider:before {
  content: "🌙";
  transform: translateX(30px);
  background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
  color: #ffd700;
  box-shadow: 
    0 2px 6px rgba(0, 0, 0, 0.3),
    0 0 0 1px rgba(255, 255, 255, 0.2),
    0 0 10px rgba(255, 215, 0, 0.3);
}

.slider:active:before {
  transform: scale(0.95);
}

input:checked + .slider:active:before {
  transform: translateX(30px) scale(0.95);
}

/* Dark Mode Toggle JavaScript Helper */
.dark-mode-transition {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

<script>
// Universal Dark Mode Toggle Script
function initializeDarkModeToggle() {
  const themeToggle = document.getElementById('themeToggle');
  
  if (!themeToggle) return;

  // Load saved theme preference or default to light mode
  const savedTheme = localStorage.getItem('theme') || 'light';
  
  // Apply saved theme
  if (savedTheme === 'dark') {
    document.body.classList.add('dark-mode');
    themeToggle.checked = true;
  }

  // Theme toggle event listener
  themeToggle.addEventListener('change', function() {
    document.body.classList.add('dark-mode-transition');
    
    if (this.checked) {
      document.body.classList.add('dark-mode');
      localStorage.setItem('theme', 'dark');
    } else {
      document.body.classList.remove('dark-mode');
      localStorage.setItem('theme', 'light');
    }

    // Remove transition class after animation
    setTimeout(() => {
      document.body.classList.remove('dark-mode-transition');
    }, 300);
  });
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', initializeDarkModeToggle);

// Also initialize if script is loaded after DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeDarkModeToggle);
} else {
  initializeDarkModeToggle();
}
</script>
