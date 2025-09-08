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
  
  <!-- Modern Modal Enhancement System -->
  <script src="js/modern-modals.js"></script>
  
  <!-- Map Fallback -->
  <script src="js/map-fallback.js"></script>
  
  <!-- Leaflet CSS - Try local first, then fallback to CDN -->
  <link rel="stylesheet" href="js/leaflet/leaflet.css" onload="console.log('Local Leaflet CSS loaded')" onerror="loadLeafletCSS()">
  
  <!-- Modern Modal Styles -->
  <link rel="stylesheet" href="css/modern-modals.css">
  
  <script>
    function loadLeafletCSS() {
      console.log('Local CSS failed, loading CDN...');
      const link1 = document.createElement('link');
      link1.rel = 'stylesheet';
      link1.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
      link1.onload = () => console.log('CDN Leaflet CSS loaded');
      link1.onerror = () => {
        console.log('Primary CDN failed, trying fallback...');
        const link2 = document.createElement('link');
        link2.rel = 'stylesheet';
        link2.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css';
        link2.onload = () => console.log('Fallback CDN CSS loaded');
        link2.onerror = () => console.error('All Leaflet CSS sources failed!');
        document.head.appendChild(link2);
      };
      document.head.appendChild(link1);
    }
  </script>
  
  <!-- Universal Dark Mode Styles -->
  <?php include 'includes/dark-mode-styles.php'; ?>
  <title>Service Network & Route Planner | CORE II</title>
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
    
    .user-feature {
      background: rgba(0,0,0,0.1);
      border-left: 4px solid rgba(255,255,255,0.4);
    }

    .user-feature:hover {
      background: rgba(0,0,0,0.2);
      border-left-color: rgba(255,255,255,0.8);
    }
    
    .provider-feature {
      background: rgba(0,0,0,0.1);
      border-left: 4px solid rgba(40, 167, 69, 0.4);
    }

    .provider-feature:hover {
      background: rgba(0,0,0,0.2);
      border-left-color: rgba(40, 167, 69, 0.8);
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

    /* Remove bottom border from all table headers */
    th, thead, tr {
        border-bottom: none !important;
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
  

    .dark-mode .route-map-section {
      background-color: var(--dark-card);
      color: var(--text-light);
    }

    .dark-mode .map-container {
      background-color: #2a3a5a;
      border-color: #3a4b6e;
    }

    /* Header Controls */
    .header-controls {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    /* Theme Toggle */
    .theme-toggle-container { display: flex; align-items: center; gap: 0.5rem; }

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

    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); transition: .4s; border-radius: 34px; }

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

    input:checked + .slider { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

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
      
      .content { margin-left: 0; padding: 1rem; }
      .dashboard-cards { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
    }

    @media (max-width: 576px) {
      .sidebar { width: 100%; max-width: 320px; }
      .dashboard-cards { grid-template-columns: 1fr; }
      .header { flex-direction: column; gap: 1rem; text-align: center; }
    }

    /* Modern Loading Screen - Enhanced for Provider Account */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(180deg, rgba(44, 62, 80, 0.95) 0%, rgba(52, 73, 94, 0.98) 100%);
      backdrop-filter: blur(25px);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      opacity: 0;
      visibility: hidden;
      transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
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
      font-size: 1.3rem;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 0.5rem;
      opacity: 0;
      animation: textFadeIn 0.5s ease-out 0.3s forwards;
      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    .loading-subtext {
      font-size: 1rem;
      color: rgba(255, 255, 255, 0.9);
      opacity: 0;
      animation: textFadeIn 0.5s ease-out 0.6s forwards;
      text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }
    .dark-mode .loading-text { color: #667eea; }
    .dark-mode .loading-subtext { color: #adb5bd; }
    .loading-progress {
      width: 220px;
      height: 5px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 3px;
      margin: 1rem auto 0;
      overflow: hidden;
      position: relative;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .loading-progress-bar {
      height: 100%;
      background: linear-gradient(180deg, rgba(44, 62, 80, 0.95) 0%, rgba(52, 73, 94, 0.98) 100%);
      border-radius: 3px;
      width: 0%;
      animation: progressFill 2.5s ease-in-out infinite;
      box-shadow: 0 0 10px rgba(102, 126, 234, 0.2);
    }
    .loading-dots {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      margin-top: 1rem;
    }
    .loading-dot {
      width: 10px;
      height: 10px;
      background: #ffffff;
      border-radius: 50%;
      animation: dotPulse 1.4s ease-in-out infinite both;
      box-shadow: 0 0 6px rgba(255, 255, 255, 0.6);
    }
    .loading-dot:nth-child(2) { animation-delay: 0.2s; }
    .loading-dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    @keyframes logoFloat { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-10px); } }
    @keyframes textFadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes progressFill { 0% { width: 0%; } 50% { width: 70%; } 100% { width: 100%; } }
    @keyframes dotPulse {
      0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
      40% { transform: scale(1); opacity: 1; }
    }
    
    /* Real-time Map Styles */
    .map-stat {
      padding: 0.5rem;
      transition: all 0.3s ease;
    }
    
    .map-stat:hover {
      transform: translateY(-2px);
    }
    
    .map-stat h5 {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 0.25rem;
      color: var(--primary-color);
    }
    
    .dark-mode .map-stat h5 {
      color: #667eea;
    }
    
    #realTimeMap {
      border-radius: var(--border-radius) var(--border-radius) 0 0;
      overflow: hidden;
      z-index: 1;
      min-height: 500px;
      height: 500px !important;
      width: 100% !important;
      background: #e0e0e0;
      position: relative;
      display: block !important;
    }
    
    /* Ensure Leaflet map fills container properly */
    #realTimeMap .leaflet-container {
      height: 500px !important;
      width: 100% !important;
      background: #e0e0e0;
      display: block !important;
      position: relative !important;
    }
    
    /* Fix map loading indicator */
    #mapLoadingIndicator {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(248, 249, 250, 0.95);
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .dark-mode #mapLoadingIndicator {
      background: rgba(44, 62, 80, 0.95);
      color: var(--text-light);
    }
    
    .leaflet-popup-content {
      min-width: 200px;
    }
    
    .vehicle-popup {
      text-align: center;
    }
    
    .vehicle-popup .vehicle-icon {
      font-size: 2rem;
      margin-bottom: 0.5rem;
    }
    
    .vehicle-status {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .vehicle-status.active {
      background: #d4edda;
      color: #155724;
    }
    
    .vehicle-status.maintenance {
      background: #fff3cd;
      color: #856404;
    }
    
    .vehicle-status.inactive {
      background: #f8d7da;
      color: #721c24;
    }
    
    .dark-mode .vehicle-status.active {
      background: rgba(26, 135, 84, 0.3);
      color: #75b798;
    }
    
    .dark-mode .vehicle-status.maintenance {
      background: rgba(255, 193, 7, 0.3);
      color: #ffc107;
    }
    
    .dark-mode .vehicle-status.inactive {
      background: rgba(220, 53, 69, 0.3);
      color: #f5a6a6;
    }
    
    .alert-popup {
      max-width: 250px;
    }
    
    .alert-severity {
      display: inline-block;
      padding: 2px 6px;
      border-radius: 8px;
      font-size: 0.7rem;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .alert-severity.high {
      background: #dc3545;
      color: white;
    }
    
    .alert-severity.medium {
      background: #ffc107;
      color: #212529;
    }
    
    .alert-severity.low {
      background: #28a745;
      color: white;
    }
    
    .btn-group .btn.active {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
      color: white;
    }
    
    .leaflet-control-layers {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
    }
    
    .dark-mode .leaflet-control-layers {
      background: rgba(44, 62, 80, 0.9);
      color: var(--text-light);
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
      <div class="loading-subtext" id="loadingSubtext">Please wait while we prepare service network data</div>
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
        <h1><?php echo $isProvider ? 'Service Network' : 'Service Network & Route Planner'; ?> <span class="system-title">| CORE II</span></h1>
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

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>Network Overview</h3>
      <button class="btn btn-outline-primary" onclick="refreshDashboardStats()" title="Refresh Dashboard Statistics">
        <i class="bi bi-arrow-clockwise"></i> Refresh Data
      </button>
    </div>

    <div class="dashboard-cards">
      <div class="card">
        <h3>Total Routes</h3>
        <div class="stat-value" id="totalRoutes">0</div>
        <div class="stat-label">Active routes</div>
      </div>

      <div class="card">
        <h3>Service Points</h3>
        <div class="stat-value" id="servicePoints">0</div>
        <div class="stat-label">Network nodes</div>
      </div>

      <div class="card">
        <h3>Coverage Area</h3>
        <div class="stat-value" id="coverageArea">0 km²</div>
        <div class="stat-label">Service coverage</div>
      </div>

      <div class="card">
        <h3>Efficiency Score</h3>
        <div class="stat-value" id="efficiencyScore">0%</div>
        <div class="stat-label">Route optimization</div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>Route Management</h3>
      <button class="btn btn-success" onclick="openAddRouteModal()">
        <i class="bi bi-plus-circle"></i> Add New Route
      </button>
    </div>
    

    <!-- Route Modal (popup for add/edit) -->
    <div class="modal fade" id="routeModal" tabindex="-1" aria-labelledby="routeModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="routeModalLabel">Add New Route</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="routeForm">
              <input type="hidden" id="routeId">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="routeName" class="form-label">Route Name *</label>
                    <input type="text" class="form-control" id="routeName" name="routeName" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="routeType" class="form-label">Route Type *</label>
                    <select class="form-select" id="routeType" name="routeType" required>
                      <option value="">Select Type</option>
                      <option value="Primary">Primary Route</option>
                      <option value="Secondary">Secondary Route</option>
                      <option value="Express">Express Route</option>
                      <option value="Local">Local Route</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="startPoint" class="form-label">Start Point *</label>
                    <input type="text" class="form-control" id="startPoint" name="startPoint" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="endPoint" class="form-label">End Point *</label>
                    <input type="text" class="form-control" id="endPoint" name="endPoint" required>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="mb-3">
                    <label for="distance" class="form-label">Distance (km) *</label>
                    <input type="number" class="form-control" id="distance" name="distance" step="0.1" required>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="mb-3">
                    <label for="estimatedTime" class="form-label">Estimated Time (minutes) *</label>
                    <input type="number" class="form-control" id="estimatedTime" name="estimatedTime" required>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="mb-3">
                    <label for="serviceFrequency" class="form-label">Service Frequency *</label>
                    <select class="form-select" id="serviceFrequency" name="serviceFrequency" required>
                      <option value="">Select Frequency</option>
                      <option value="Every 15 min">Every 15 minutes</option>
                      <option value="Every 30 min">Every 30 minutes</option>
                      <option value="Every hour">Every hour</option>
                      <option value="Every 2 hours">Every 2 hours</option>
                      <option value="Daily">Daily</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="routeStatus" class="form-label">Status *</label>
                    <select class="form-select" id="routeStatus" name="routeStatus" required>
                      <option value="Active">Active</option>
                      <option value="Inactive">Inactive</option>
                      <option value="Maintenance">Maintenance</option>
                      <option value="Planned">Planned</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="routeNotes" class="form-label">Route Notes</label>
                    <textarea class="form-control" id="routeNotes" name="routeNotes" rows="3"></textarea>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveRoute()">Save Route</button>
          </div>
        </div>
      </div>
    </div>

    <div class="table-section">
      <div class="table-responsive">
        <table id="routesTable" class="table table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Route Name</th>
              <th>Type</th>
              <th>Start Point</th>
              <th>End Point</th>
              <th>Distance</th>
              <th>Frequency</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="routesTableBody">
            <!-- Route data will be loaded here -->
          </tbody>
        </table>
      </div>
    </div>


    <!-- Map Controls Panel -->
    <div class="card mb-2" id="mapControlsPanel">
      <div class="card-body py-2">
        <div class="row align-items-center">
          <div class="col-md-3">
            <label class="form-label mb-1 small">Filter by Status:</label>
            <select class="form-select form-select-sm" id="statusFilter" onchange="applyFilters()">
              <option value="all">All</option>
              <option value="active">Active Only</option>
              <option value="maintenance">Maintenance</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label mb-1 small">Vehicle Type:</label>
            <select class="form-select form-select-sm" id="typeFilter" onchange="applyFilters()">
              <option value="all">All Types</option>
              <option value="Bus">Bus</option>
              <option value="Jeepney">Jeepney</option>
              <option value="Van">Van</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label mb-1 small">Route:</label>
            <select class="form-select form-select-sm" id="routeFilter" onchange="applyFilters()">
              <option value="all">All Routes</option>
            </select>
          </div>
          <div class="col-md-3">
            <div class="d-flex align-items-end h-100">
              <button class="btn btn-sm btn-outline-secondary me-1" onclick="resetFilters()">
                <i class="bi bi-arrow-clockwise"></i> Reset
              </button>
            <button class="btn btn-sm btn-outline-primary" onclick="exportMapData()">
              <i class="bi bi-download"></i> Export
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="debugMap()" title="Debug Map">
              <i class="bi bi-bug"></i>
            </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card mb-4">
      <div class="card-body p-0">
        <div id="realTimeMap" style="height: 500px; width: 100%;">
          <div id="mapLoadingIndicator" style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f8f9fa; color: #6c757d;">
            <div class="text-center">
              <div class="spinner-border text-primary mb-2" role="status"></div>
              <div>Loading Map...</div>
              <button class="btn btn-sm btn-primary mt-2" onclick="initMapImmediate()" style="display: none;" id="manualMapInit">Click to Load Map</button>
            </div>
          </div>
        </div>
        <div class="p-3">
          <div class="row text-center">
            <div class="col-md-3">
              <div class="map-stat">
                <h5 id="activeVehiclesCount">0</h5>
                <small class="text-muted">Active Vehicles</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="map-stat">
                <h5 id="totalRoutesCount">0</h5>
                <small class="text-muted">Active Routes</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="map-stat">
                <h5 id="servicePointsCount">0</h5>
                <small class="text-muted">Service Points</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="map-stat">
                <h5 id="alertsCount">0</h5>
                <small class="text-muted">Active Alerts</small>
              </div>
            </div>
          </div>
          <div class="mt-3">
            <small class="text-muted">Last Updated: <span id="lastUpdateTime">Never</span></small>
            <div class="float-end">
              <button class="btn btn-sm btn-outline-secondary me-1" onclick="debugMap()" title="Debug Map">
                <i class="bi bi-bug"></i>
              </button>
              <button class="btn btn-sm btn-outline-warning me-1" onclick="forceInitMap()" title="Force Initialize Map">
                <i class="bi bi-map"></i>
              </button>
              <button class="btn btn-sm btn-outline-primary" onclick="refreshMapData()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>Service Points Network</h3>
      <button class="btn btn-success" onclick="openAddServicePointModal()">
        <i class="bi bi-plus-circle"></i> Add New Service Point
      </button>
    </div>
    

    <!-- Service Point Modal (popup for add/edit) -->
    <div class="modal fade" id="servicePointModal" tabindex="-1" aria-labelledby="servicePointModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="servicePointModalLabel">Add New Service Point</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="servicePointForm">
              <input type="hidden" id="servicePointId">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="pointName" class="form-label">Point Name *</label>
                    <input type="text" class="form-control" id="pointName" name="pointName" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="pointType" class="form-label">Point Type *</label>
                    <select class="form-select" id="pointType" name="pointType" required>
                      <option value="">Select Type</option>
                      <option value="Transport Hub">Transport Hub</option>
                      <option value="Terminal">Terminal</option>
                      <option value="Transfer Point">Transfer Point</option>
                      <option value="Station">Station</option>
                      <option value="Depot">Depot</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="pointLocation" class="form-label">Location *</label>
                    <input type="text" class="form-control" id="pointLocation" name="pointLocation" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="pointServices" class="form-label">Services *</label>
                    <input type="text" class="form-control" id="pointServices" name="pointServices" required>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="pointStatus" class="form-label">Status *</label>
                    <select class="form-select" id="pointStatus" name="pointStatus" required>
                      <option value="Active">Active</option>
                      <option value="Inactive">Inactive</option>
                      <option value="Maintenance">Maintenance</option>
                      <option value="Planned">Planned</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="pointNotes" class="form-label">Notes</label>
                    <textarea class="form-control" id="pointNotes" name="pointNotes" rows="3"></textarea>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveServicePoint()">Save Service Point</button>
          </div>
        </div>
      </div>
    </div>

    <div class="table-section">
      <div class="table-responsive">
        <table id="servicePointsTable" class="table table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Point Name</th>
              <th>Type</th>
              <th>Location</th>
              <th>Services</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="servicePointsTableBody">
            <!-- Service points data will be loaded here -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Route Modal -->
  <div class="modal fade" id="routeModal" tabindex="-1" aria-labelledby="routeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="routeModalLabel">Add New Route</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="routeForm">
            <input type="hidden" id="routeId">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="routeName" class="form-label">Route Name *</label>
                  <input type="text" class="form-control" id="routeName" name="routeName" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="routeType" class="form-label">Route Type *</label>
                  <select class="form-select" id="routeType" name="routeType" required>
                    <option value="">Select Type</option>
                    <option value="Primary">Primary Route</option>
                    <option value="Secondary">Secondary Route</option>
                    <option value="Express">Express Route</option>
                    <option value="Local">Local Route</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="startPoint" class="form-label">Start Point *</label>
                  <input type="text" class="form-control" id="startPoint" name="startPoint" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="endPoint" class="form-label">End Point *</label>
                  <input type="text" class="form-control" id="endPoint" name="endPoint" required>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4">
                <div class="mb-3">
                  <label for="distance" class="form-label">Distance (km) *</label>
                  <input type="number" class="form-control" id="distance" name="distance" step="0.1" required>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mb-3">
                  <label for="estimatedTime" class="form-label">Estimated Time (minutes) *</label>
                  <input type="number" class="form-control" id="estimatedTime" name="estimatedTime" required>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mb-3">
                  <label for="serviceFrequency" class="form-label">Service Frequency *</label>
                  <select class="form-select" id="serviceFrequency" name="serviceFrequency" required>
                    <option value="">Select Frequency</option>
                    <option value="Every 15 min">Every 15 minutes</option>
                    <option value="Every 30 min">Every 30 minutes</option>
                    <option value="Every hour">Every hour</option>
                    <option value="Every 2 hours">Every 2 hours</option>
                    <option value="Daily">Daily</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="routeStatus" class="form-label">Status *</label>
                  <select class="form-select" id="routeStatus" name="routeStatus" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Planned">Planned</option>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="routeNotes" class="form-label">Route Notes</label>
                  <textarea class="form-control" id="routeNotes" name="routeNotes" rows="3"></textarea>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveRoute()">Save Route</button>
        </div>
      </div>
    </div>
  </div>


  <!-- View Route Modal -->
  <div class="modal fade" id="viewRouteModal" tabindex="-1" aria-labelledby="viewRouteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewRouteModalLabel">Route Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <p><strong>ID:</strong> <span id="viewRouteId"></span></p>
              <p><strong>Name:</strong> <span id="viewRouteName"></span></p>
              <p><strong>Type:</strong> <span id="viewRouteType"></span></p>
              <p><strong>Start Point:</strong> <span id="viewRouteStartPoint"></span></p>
              <p><strong>End Point:</strong> <span id="viewRouteEndPoint"></span></p>
            </div>
            <div class="col-md-6">
              <p><strong>Distance:</strong> <span id="viewRouteDistance"></span></p>
              <p><strong>Estimated Time:</strong> <span id="viewRouteTime"></span></p>
              <p><strong>Frequency:</strong> <span id="viewRouteFrequency"></span></p>
              <p><strong>Status:</strong> <span id="viewRouteStatus"></span></p>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <p><strong>Notes:</strong></p>
              <p id="viewRouteNotes"></p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- View Service Point Modal -->
  <div class="modal fade" id="viewServicePointModal" tabindex="-1" aria-labelledby="viewServicePointModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewServicePointModalLabel">Service Point Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <p><strong>ID:</strong> <span id="viewPointId"></span></p>
              <p><strong>Name:</strong> <span id="viewPointName"></span></p>
              <p><strong>Type:</strong> <span id="viewPointType"></span></p>
              <p><strong>Location:</strong> <span id="viewPointLocation"></span></p>
            </div>
            <div class="col-md-6">
              <p><strong>Services:</strong> <span id="viewPointServices"></span></p>
              <p><strong>Status:</strong> <span id="viewPointStatus"></span></p>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <p><strong>Notes:</strong></p>
              <p id="viewPointNotes"></p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Provider Modal -->
  <div class="modal fade" id="providerModal" tabindex="-1" aria-labelledby="providerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="providerModalLabel">Add New Provider</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="providerForm">
            <input type="hidden" id="providerId">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="providerName" class="form-label">Provider Name *</label>
                  <input type="text" class="form-control" id="providerName" name="providerName" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="providerType" class="form-label">Provider Type *</label>
                  <select class="form-select" id="providerType" name="providerType" required>
                    <option value="">Select Type</option>
                    <option value="Individual">Individual</option>
                    <option value="Company">Company</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="contactPerson" class="form-label">Contact Person *</label>
                  <input type="text" class="form-control" id="contactPerson" name="contactPerson" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="contactNumber" class="form-label">Contact Number *</label>
                  <input type="text" class="form-control" id="contactNumber" name="contactNumber" required>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="email" class="form-label">Email</label>
                  <input type="email" class="form-control" id="email" name="email">
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="status" class="form-label">Status *</label>
                  <select class="form-select" id="status" name="status" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-12">
                <div class="mb-3">
                  <label for="notes" class="form-label">Notes</label>
                  <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveProvider()">Save Provider</button>
        </div>
      </div>
    </div>
  </div>

  <!-- View Provider Modal -->
  <div class="modal fade" id="viewProviderModal" tabindex="-1" aria-labelledby="viewProviderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewProviderModalLabel">Provider Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <p><strong>ID:</strong> <span id="viewProviderId"></span></p>
              <p><strong>Name:</strong> <span id="viewProviderName"></span></p>
              <p><strong>Type:</strong> <span id="viewProviderType"></span></p>
              <p><strong>Contact Person:</strong> <span id="viewContactPerson"></span></p>
            </div>
            <div class="col-md-6">
              <p><strong>Contact Number:</strong> <span id="viewContactNumber"></span></p>
              <p><strong>Email:</strong> <span id="viewEmail"></span></p>
              <p><strong>Status:</strong> <span id="viewStatus"></span></p>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <p><strong>Notes:</strong></p>
              <p id="viewNotes"></p>
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
          <p>Are you sure you want to delete <strong id="deleteItemName"></strong>?</p>
          <p class="text-danger">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Shipment Tracking Modal (center) -->
  <div class="modal fade" id="trackingModal" tabindex="-1" aria-labelledby="trackingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="trackingModalLabel">Shipment Tracking - All</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle" id="trackingModalTable">
              <thead>
                <tr>
                  <th>Tracking ID</th>
                  <th>Route</th>
                  <th>Status</th>
                  <th>Last Update</th>
                  <th>ETA</th>
                  <th>Current Location</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Shipment Details Modal (center) -->
  <div class="modal fade" id="trackingDetailsModal" tabindex="-1" aria-labelledby="trackingDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="trackingDetailsModalLabel">Shipment Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <p><strong>Tracking ID:</strong> <span id="td_id"></span></p>
              <p><strong>Route:</strong> <span id="td_route"></span></p>
              <p><strong>Status:</strong> <span id="td_status"></span></p>
            </div>
            <div class="col-md-6">
              <p><strong>Last Update:</strong> <span id="td_lastUpdate"></span></p>
              <p><strong>ETA:</strong> <span id="td_eta"></span></p>
              <p><strong>Current Location:</strong> <span id="td_location"></span></p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Map Fallback -->
  <script src="js/map-fallback.js"></script>
  
  <!-- Leaflet JS with fallbacks -->
  <script src="js/leaflet/leaflet.js" onerror="loadLeafletFallback()"></script>
  
  <script>
    // Leaflet fallback loader
    function loadLeafletFallback() {
      console.log('Local Leaflet failed, trying CDN fallbacks...');
      
      // Try primary CDN
      const script1 = document.createElement('script');
      script1.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
      script1.onerror = function() {
        console.log('Primary CDN failed, trying secondary...');
        
        // Try secondary CDN
        const script2 = document.createElement('script');
        script2.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js';
        script2.onerror = function() {
          console.error('All Leaflet sources failed! Using simple fallback map...');
          initSimpleMap();
        };
        script2.onload = function() {
          console.log('Secondary CDN loaded successfully');
          initMapWhenReady();
        };
        document.head.appendChild(script2);
      };
      script1.onload = function() {
        console.log('Primary CDN loaded successfully');
        initMapWhenReady();
      };
      document.head.appendChild(script1);
    }
    
    // Initialize map when Leaflet is ready
    function initMapWhenReady() {
      if (typeof L !== 'undefined') {
        console.log('Leaflet loaded, initializing map...');
        setTimeout(initMapImmediate, 100);
      } else {
        console.log('Waiting for Leaflet...');
        setTimeout(initMapWhenReady, 200);
      }
    }
  </script>
  
  <script>
    const ROUTES_API = 'api/routes.php';
    const POINTS_API = 'api/service-points.php';
    const SHIPMENTS_API = 'api/schedules.php';
    let routes = [];
    let servicePoints = [];
    let currentRouteId = null;
    let currentServicePointId = null;
    let isEditMode = false;
    let deleteType = '';

    // Initialize the application
    document.addEventListener('DOMContentLoaded', function() {
      console.log('\n=== CORE II Real-Time Map System ===');
      console.log('DOM loaded, initializing...');
      console.log('Leaflet available:', typeof L !== 'undefined');
      console.log('Map element exists:', !!document.getElementById('realTimeMap'));
      console.log('\n🗺️ MAP TROUBLESHOOTING:');
      console.log('- If map doesn\'t appear, click the "Force Init" button (🗺️)');
      console.log('- Or use: initMapImmediate() in console');
      console.log('- Debug info: debugMap()');
      console.log('========================================\n');
      
      // Show loading animation immediately
      showLoading('Initializing Service Network...', 'Loading map data and service information');
      
      setTimeout(() => {
        initializeEventListeners();
        applyStoredTheme();
        
        // Update loading message
        updateLoadingText('Setting up interactive map...', 'Preparing real-time tracking features');
        
        setTimeout(() => {
          // Lazy-initialize the map only when visible to avoid size issues and heavy load
          setupMapLazyInit();
          
          updateLoadingText('Loading service data...', 'Fetching routes and service points');
          
          fetchRoutes();
          fetchServicePoints();
          preloadShipments();
          
          // Initialize dashboard stats
          updateDashboardStats();
          
          setTimeout(() => {
            updateLoadingText('Finalizing...', 'Almost ready!');
            
            setTimeout(() => {
              hideLoading();
            }, 800);
          }, 1000);
        }, 1200);
      }, 500);
    });
    
    // Backup initialization attempts
    window.addEventListener('load', function() {
      console.log('Window loaded - backup check');
      if (!leafletMap) {
        console.log('Map not initialized, trying backup...');
        setTimeout(initMapImmediate, 200);
      }
    });
    
    // Show manual button after 3 seconds if map not loaded
    setTimeout(() => {
      if (!leafletMap) {
        console.log('Map not loaded after timeout, using simple fallback...');
        initSimpleMap();
      }
    }, 3000);
    
    // Global functions for easy access
    window.initMapNow = initMapImmediate;
    window.showSimpleMap = initSimpleMap;
    window.debugMapInfo = debugMap;

    // Enhanced Loading Utility Functions
    function showLoading(text = 'Loading...', subtext = 'Please wait') {
      const overlay = document.getElementById('loadingOverlay');
      const loadingText = document.getElementById('loadingText');
      const loadingSubtext = document.getElementById('loadingSubtext');
      
      if (loadingText) loadingText.textContent = text;
      if (loadingSubtext) loadingSubtext.textContent = subtext;
      
      overlay.classList.add('show');
      
      // Add provider-specific styling
      overlay.style.background = 'linear-gradient(135deg, rgba(40, 167, 69, 0.95) 0%, rgba(34, 139, 58, 0.98) 100%)';
    }

    function hideLoading() {
      const overlay = document.getElementById('loadingOverlay');
      overlay.classList.remove('show');
    }

    function updateLoadingText(text, subtext) {
      const loadingText = document.getElementById('loadingText');
      const loadingSubtext = document.getElementById('loadingSubtext');
      
      if (loadingText) {
        loadingText.style.animation = 'none';
        loadingText.offsetHeight; // Trigger reflow
        loadingText.style.animation = null;
        loadingText.textContent = text;
      }
      if (loadingSubtext) {
        loadingSubtext.style.animation = 'none';
        loadingSubtext.offsetHeight; // Trigger reflow
        loadingSubtext.style.animation = null;
        loadingSubtext.textContent = subtext;
      }
    }

    // Provider-specific notification system
    function showProviderNotification(message, type = 'info', duration = 3000) {
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: duration,
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer)
          toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
      });

      const iconMap = {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'info',
        provider: 'success'
      };

      Toast.fire({
        icon: iconMap[type] || 'info',
        title: message,
        background: type === 'provider' ? '#28a745' : undefined,
        color: type === 'provider' ? '#fff' : undefined
      });
    }

    let leafletMap = null;
    let rtLayers = { vehicles: null, routes: null, servicePoints: null, alerts: null };
    let rtVisibility = { vehicles: true, routes: true, servicePoints: true, alerts: true };
    let rtInterval = null;
    let rtInFlight = false; // prevent overlapping refreshes
    
    // Improved map initialization function
    function initMapImmediate() {
      console.log('=== IMMEDIATE MAP INIT START ===');
      
      // Check prerequisites
      if (typeof L === 'undefined') {
        console.error('Leaflet library not loaded! Attempting to load...');
        loadLeafletAndInitMap();
        return;
      }
      
      const mapContainer = document.getElementById('realTimeMap');
      if (!mapContainer) {
        console.error('Map container not found!');
        return;
      }
      
      // Clear the map container content first
      mapContainer.innerHTML = '';
      
      // Ensure container has proper dimensions
      mapContainer.style.height = '500px';
      mapContainer.style.width = '100%';
      mapContainer.style.position = 'relative';
      mapContainer.style.display = 'block';
      mapContainer.style.zIndex = '1';
      
      console.log('Container dimensions:', mapContainer.offsetWidth, 'x', mapContainer.offsetHeight);
      
      // Clean up existing map
      if (leafletMap) {
        console.log('Removing existing map...');
        try {
          leafletMap.remove();
        } catch (e) {
          console.warn('Error removing existing map:', e);
        }
        leafletMap = null;
      }
      
      try {
        console.log('Creating Leaflet map...');
        
        // Create map with Manila center coordinates
        leafletMap = L.map(mapContainer, {
          center: [14.5995, 120.9842],
          zoom: 11,
          zoomControl: true,
          scrollWheelZoom: true,
          doubleClickZoom: true,
          dragging: true,
          touchZoom: true,
          boxZoom: true,
          keyboard: true,
          attributionControl: true,
          preferCanvas: false
        });
        
        console.log('Map object created successfully');
        
        // Add OpenStreetMap tile layer
        const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
          errorTileUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjU2IiBoZWlnaHQ9IjI1NiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjU2IiBoZWlnaHQ9IjI1NiIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5NYXAgTm90IEF2YWlsYWJsZTwvdGV4dD48L3N2Zz4=',
          updateWhenZooming: false,
          updateWhenIdle: true,
          keepBuffer: 2,
          reuseTiles: true
        });
        
        tileLayer.addTo(leafletMap);
        console.log('Tile layer added successfully');
        
        // Hide loading indicator
        const loadingEl = document.getElementById('mapLoadingIndicator');
        if (loadingEl) {
          loadingEl.style.display = 'none';
          console.log('Loading indicator hidden');
        }
        
        // Multiple invalidation attempts to ensure proper sizing
        const invalidateMap = () => {
          if (leafletMap) {
            leafletMap.invalidateSize(true);
            console.log('Map size invalidated');
          }
        };
        
        setTimeout(invalidateMap, 50);
        setTimeout(invalidateMap, 200);
        setTimeout(invalidateMap, 500);
        setTimeout(invalidateMap, 1000);
        
        // Initialize real-time features after map is stable
        setTimeout(() => {
          initRealTimeMapFeatures();
        }, 300);
        
        console.log('=== MAP INITIALIZED SUCCESSFULLY ===');
        
      } catch (error) {
        console.error('Map initialization error:', error);
        showMapError(error.message);
      }
    }
    
    // Function to load Leaflet library and then initialize map
    function loadLeafletAndInitMap() {
      console.log('Loading Leaflet library...');
      
      const script = document.createElement('script');
      script.src = 'js/leaflet/leaflet.js';
      script.onload = () => {
        console.log('Local Leaflet JS loaded, initializing map...');
        setTimeout(initMapImmediate, 100);
      };
      script.onerror = () => {
        console.log('Local Leaflet failed, trying CDN...');
        loadLeafletFallback();
      };
      document.head.appendChild(script);
    }
    
    // Function to show map errors with retry option
    function showMapError(errorMessage) {
      const mapContainer = document.getElementById('realTimeMap');
      if (mapContainer) {
        mapContainer.innerHTML = `
          <div style="display: flex; align-items: center; justify-content: center; height: 500px; background: #f8f9fa; color: #6c757d; flex-direction: column; gap: 1rem;">
            <div class="text-center">
              <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
              <h4 class="mt-2">Map Loading Error</h4>
              <p class="text-muted">${errorMessage}</p>
              <div class="mt-3">
                <button class="btn btn-primary me-2" onclick="initMapImmediate()">Retry Map</button>
                <button class="btn btn-outline-secondary" onclick="initSimpleMap()">Use Simple View</button>
              </div>
            </div>
          </div>
        `;
      }
    }

    // Legacy function - now redirects to new implementation
    function initMap() {
      console.log('Legacy initMap called - redirecting to initMapImmediate');
      initMapImmediate();
    }

    // Lazy init: create the map only when the container is visible in viewport
    function setupMapLazyInit() {
      const target = document.getElementById('realTimeMap');
      if (!target) return;

      // If Leaflet isn't ready yet, wait for it
      if (typeof L === 'undefined') {
        initMapWhenReady();
      }

      let initialized = false;
      const initIfNeeded = () => {
        if (!initialized && target.offsetParent !== null) {
          initialized = true;
          setTimeout(initMapImmediate, 50);
        }
      };

      if ('IntersectionObserver' in window) {
        const obs = new IntersectionObserver((entries) => {
          entries.forEach(e => {
            if (e.isIntersecting) {
              initIfNeeded();
              obs.disconnect();
            }
          });
        }, { root: null, threshold: 0.05 });
        obs.observe(target);
      } else {
        // Fallback: delay init a bit
        setTimeout(initIfNeeded, 800);
      }
    }

    let sseConnection = null;
    // Start with live updates disabled to reduce network load
    let liveUpdatesEnabled = false;

    // Legacy function - now redirects to new implementation
    function initRealTimeMap() {
      console.log('Legacy initRealTimeMap called - redirecting to initRealTimeMapFeatures');
      initRealTimeMapFeatures();
    }
    
    function initRealTimeMapFeatures() {
      console.log('Initializing real-time map features...');
      
      if (!leafletMap) {
        console.error('Base map not initialized yet!');
        setTimeout(initRealTimeMapFeatures, 500); // Retry after delay
        return;
      }
      
      try {
        // Initialize layer groups
        rtLayers.vehicles = L.layerGroup().addTo(leafletMap);
        rtLayers.routes = L.layerGroup().addTo(leafletMap);
        rtLayers.servicePoints = L.layerGroup().addTo(leafletMap);
        rtLayers.alerts = L.layerGroup().addTo(leafletMap);
        
        console.log('Layer groups created successfully');

        // Set initial button states
        const buttons = ['toggleVehicles', 'toggleRoutes', 'toggleServicePoints', 'toggleAlerts'];
        buttons.forEach(btnId => {
          const btn = document.getElementById(btnId);
          if (btn) {
            btn.classList.add('active');
          } else {
            console.warn(`Button ${btnId} not found`);
          }
        });

        // Set live updates button state (disabled by default for lighter load)
        const liveBtn = document.getElementById('toggleLiveUpdates');
        if (liveBtn) liveBtn.classList.toggle('active', liveUpdatesEnabled);

        // Initial load with slight delay
        setTimeout(() => {
          refreshMapData();
          // Start real-time updates if enabled
          if (liveUpdatesEnabled) startRealTimeUpdates();
        }, 300);

        // Lightweight periodic refresh every 60 seconds (reduced traffic)
        if (rtInterval) clearInterval(rtInterval);
        rtInterval = setInterval(refreshMapData, 60000);
        
        console.log('Real-time map initialization complete');
        
      } catch (error) {
        console.error('Error initializing real-time map:', error);
      }
    }

    function startRealTimeUpdates() {
      if (!liveUpdatesEnabled) return;
      
      if (sseConnection) {
        sseConnection.close();
      }

      sseConnection = new EventSource('api/real-time-stream.php');
      
      sseConnection.addEventListener('vehicles', function(e) {
        const data = JSON.parse(e.data);
        if (data.vehicles) {
          renderVehicles(data.vehicles);
          updateVehicleStats(data.vehicles);
        }
      });
      
      sseConnection.addEventListener('alert', function(e) {
        const data = JSON.parse(e.data);
        if (data.alert) {
          showAlertNotification(data.alert);
          // Refresh alerts to include new one
          setTimeout(() => refreshMapData(), 1000);
        }
      });
      
      sseConnection.addEventListener('heartbeat', function(e) {
        const data = JSON.parse(e.data);
        console.log('Real-time connection active:', data.timestamp);
      });
      
      sseConnection.onerror = function(e) {
        console.error('SSE connection error, falling back to polling');
        sseConnection.close();
        // Will use the fallback interval instead
      };
      
      sseConnection.addEventListener('close', function(e) {
        console.log('SSE connection closed');
        // Attempt to reconnect after 5 seconds
        setTimeout(startRealTimeUpdates, 5000);
      });
    }

    function updateVehicleStats(vehicles) {
      const activeCount = vehicles.filter(v => (v.tracking_status||'Active') === 'Active').length;
      document.getElementById('activeVehiclesCount').textContent = activeCount;
      document.getElementById('lastUpdateTime').textContent = new Date().toLocaleString();
    }

    function showAlertNotification(alert) {
      const bg = alert.severity === 'high' ? '#dc3545' : alert.severity === 'medium' ? '#ffc107' : '#28a745';
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true
      });
      Toast.fire({
        icon: alert.severity === 'high' ? 'error' : alert.severity === 'medium' ? 'warning' : 'info',
        title: alert.type.toUpperCase() + ' ALERT',
        text: alert.message,
        background: bg,
        color: '#fff'
      });
    }

    async function refreshMapData() {
      if (!leafletMap || rtInFlight) return;
      rtInFlight = true;
      try {
        const [vehiclesRes, routesRes, pointsRes, alertsRes] = await Promise.all([
          fetch('api/real-time-tracking.php?type=vehicles'),
          fetch('api/real-time-tracking.php?type=routes'),
          fetch('api/real-time-tracking.php?type=service-points'),
          fetch('api/real-time-tracking.php?type=alerts')
        ]);
        const [vehicles, routes, points, alerts] = await Promise.all([
          vehiclesRes.json(), routesRes.json(), pointsRes.json(), alertsRes.json()
        ]);

        renderVehicles(vehicles);
        renderRoutes(routes);
        renderServicePoints(points);
        renderAlerts(alerts);

        // Update small stats
        document.getElementById('activeVehiclesCount').textContent = Array.isArray(vehicles) ? vehicles.filter(v => (v.tracking_status||'Active') === 'Active').length : 0;
        document.getElementById('totalRoutesCount').textContent = Array.isArray(routes) ? routes.length : 0;
        document.getElementById('servicePointsCount').textContent = Array.isArray(points) ? points.length : 0;
        document.getElementById('alertsCount').textContent = Array.isArray(alerts) ? alerts.length : 0;
        document.getElementById('lastUpdateTime').textContent = new Date().toLocaleString();
      } catch (e) {
        console.error('Failed to refresh map data', e);
      } finally {
        rtInFlight = false;
      }
    }

    function vehicleIconByStatus(status) {
      const colors = {
        'Active': '#4CAF50',
        'Maintenance': '#FF9800',
        'Inactive': '#757575',
        'Warning': '#FFC107',
        'Emergency': '#F44336'
      };
      const color = colors[status] || '#757575';
      
      return L.divIcon({
        className: 'custom-vehicle-marker',
        html: `<div style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:${color};color:#fff;border:2px solid #fff;box-shadow:0 3px 8px rgba(0,0,0,0.3);font-size:12px;"><i class="bi bi-truck"></i></div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14]
      });
    }

    function renderVehicles(vehicles) {
      if (!rtLayers.vehicles) return;
      rtLayers.vehicles.clearLayers();
      if (!rtVisibility.vehicles) return;
      
      // Store all vehicles data for filtering
      if (vehicles) {
        allVehiclesData = vehicles;
        populateFilterOptions(vehicles);
      }
      
      // Apply current filters if any are set
      let filteredVehicles = vehicles || [];
      if (currentFilters.status !== 'all' || currentFilters.type !== 'all' || currentFilters.route !== 'all') {
        filteredVehicles = filteredVehicles.filter(vehicle => {
          if (currentFilters.status !== 'all' && 
              (vehicle.tracking_status || 'Active').toLowerCase() !== currentFilters.status) {
            return false;
          }
          if (currentFilters.type !== 'all' && vehicle.type !== currentFilters.type) {
            return false;
          }
          if (currentFilters.route !== 'all' && vehicle.route_name !== currentFilters.route) {
            return false;
          }
          return true;
        });
      }
      
      filteredVehicles.forEach(v => {
        if (!v.latitude || !v.longitude) return;
        const marker = L.marker([parseFloat(v.latitude), parseFloat(v.longitude)], { icon: vehicleIconByStatus(v.tracking_status||'Active')});
        marker.bindPopup(`
          <div class="vehicle-popup">
            <div class="vehicle-icon"><i class="bi bi-truck"></i></div>
            <h6 class="mb-1">${v.name || v.id}</h6>
            <div class="mb-1"><span class="vehicle-status ${(v.tracking_status||'Active').toLowerCase()}">${v.tracking_status||'Active'}</span></div>
            <div class="text-muted small">
              <strong>Route:</strong> ${v.route_name || '-'}<br/>
              <strong>Speed:</strong> ${v.speed||0} km/h | <strong>Heading:</strong> ${v.heading||0}°<br/>
              <strong>Passengers:</strong> ${v.passengers||0}/${v.capacity||0}<br/>
              <strong>Fuel:</strong> ${v.fuel_level||0}%<br/>
              <strong>Updated:</strong> ${v.last_update||'-'}
            </div>
          </div>
        `);
        rtLayers.vehicles.addLayer(marker);
      });
    }

    function renderRoutes(routes) {
      if (!rtLayers.routes) return;
      rtLayers.routes.clearLayers();
      if (!rtVisibility.routes) return;
      (routes||[]).forEach(r => {
        let coords = r.coordinates;
        try { if (typeof coords === 'string') coords = JSON.parse(coords); } catch {}
        if (!Array.isArray(coords)) return;
        const latlngs = coords.map(c => Array.isArray(c) ? [parseFloat(c[0]), parseFloat(c[1])] : [parseFloat(c.lat), parseFloat(c.lng)]);
        const polyline = L.polyline(latlngs, { color: '#4e73df', weight: 4, opacity: 0.8 });
        polyline.bindPopup(`<strong>${r.name || 'Route'}</strong><br/><span class="badge ${getStatusBadgeClass(r.status||'Active')}">${r.status||'Active'}</span>`);
        rtLayers.routes.addLayer(polyline);
      });
    }

    function renderServicePoints(points) {
      if (!rtLayers.servicePoints) return;
      rtLayers.servicePoints.clearLayers();
      if (!rtVisibility.servicePoints) return;
      (points||[]).forEach(p => {
        if (!p.latitude || !p.longitude) return;
        const marker = L.marker([parseFloat(p.latitude), parseFloat(p.longitude)], { icon: L.divIcon({ className: 'custom-sp-marker', html: '<div style="width:24px;height:24px;border-radius:50%;background:#36b9cc;border:2px solid #fff"></div>', iconSize: [24,24], iconAnchor: [12,12] })});
        marker.bindPopup(`
          <div>
            <h6 class="mb-1">${p.name || 'Service Point'}</h6>
            <div class="mb-1"><span class="badge ${getStatusBadgeClass(p.status||'Active')}">${p.status||'Active'}</span></div>
            <div class="text-muted small">${p.type || ''}<br/>${p.services || ''}<br/>${p.location || ''}</div>
          </div>
        `);
        rtLayers.servicePoints.addLayer(marker);
      });
    }

    function renderAlerts(alerts) {
      if (!rtLayers.alerts) return;
      rtLayers.alerts.clearLayers();
      if (!rtVisibility.alerts) return;
      (alerts||[]).forEach(a => {
        const latlng = Array.isArray(a.location) ? a.location : null;
        if (!latlng) return;
        const circle = L.circle(latlng, { radius: 300, color: a.severity === 'high' ? '#dc3545' : a.severity === 'medium' ? '#ffc107' : '#28a745', fillOpacity: 0.2, weight: 2 });
        circle.bindPopup(`
          <div class="alert-popup">
            <strong class="d-block mb-1">${a.type?.toUpperCase() || 'ALERT'}</strong>
            <span class="alert-severity ${a.severity || 'low'}">${a.severity || 'low'}</span>
            <p class="mt-2 mb-0">${a.message || ''}</p>
            <small class="text-muted">${a.timestamp || ''}</small>
          </div>
        `);
        rtLayers.alerts.addLayer(circle);
      });
    }

    let currentFilters = { status: 'all', type: 'all', route: 'all' };
    let allVehiclesData = [];

    function toggleMapLayer(layer) {
      rtVisibility[layer] = !rtVisibility[layer];
      const btnId = layer === 'vehicles' ? 'toggleVehicles' : layer === 'routes' ? 'toggleRoutes' : layer === 'servicePoints' ? 'toggleServicePoints' : 'toggleAlerts';
      const btn = document.getElementById(btnId);
      if (btn) btn.classList.toggle('active', rtVisibility[layer]);
      // Re-render only that layer
      refreshMapData();
    }

    function toggleLiveUpdates() {
      liveUpdatesEnabled = !liveUpdatesEnabled;
      const btn = document.getElementById('toggleLiveUpdates');
      if (btn) {
        btn.classList.toggle('active', liveUpdatesEnabled);
        btn.querySelector('i').className = liveUpdatesEnabled ? 'bi bi-broadcast' : 'bi bi-broadcast-pin';
      }
      
      if (liveUpdatesEnabled) {
        startRealTimeUpdates();
        showNotification('Live updates enabled', 'success');
      } else {
        if (sseConnection) sseConnection.close();
        showNotification('Live updates disabled', 'info');
      }
    }

    function centerMap() {
      if (leafletMap) {
        leafletMap.setView([14.5995, 120.9842], 11);
        showNotification('Map centered', 'info');
      }
    }

    function toggleFullscreen() {
      const mapCard = document.querySelector('.card').parentElement;
      if (!document.fullscreenElement) {
        mapCard.requestFullscreen().catch(err => {
          console.error('Error attempting to enable fullscreen:', err);
        });
      } else {
        document.exitFullscreen();
      }
    }

    function applyFilters() {
      currentFilters.status = document.getElementById('statusFilter').value;
      currentFilters.type = document.getElementById('typeFilter').value;
      currentFilters.route = document.getElementById('routeFilter').value;
      
      // Filter and re-render vehicles
      const filteredVehicles = allVehiclesData.filter(vehicle => {
        if (currentFilters.status !== 'all' && 
            (vehicle.tracking_status || 'Active').toLowerCase() !== currentFilters.status) {
          return false;
        }
        if (currentFilters.type !== 'all' && vehicle.type !== currentFilters.type) {
          return false;
        }
        if (currentFilters.route !== 'all' && vehicle.route_name !== currentFilters.route) {
          return false;
        }
        return true;
      });
      
      renderVehicles(filteredVehicles);
      showNotification(`Filtered to ${filteredVehicles.length} vehicles`, 'info');
    }

    function resetFilters() {
      document.getElementById('statusFilter').value = 'all';
      document.getElementById('typeFilter').value = 'all';
      document.getElementById('routeFilter').value = 'all';
      currentFilters = { status: 'all', type: 'all', route: 'all' };
      renderVehicles(allVehiclesData);
      showNotification('Filters reset', 'info');
    }

    function exportMapData() {
      const data = {
        vehicles: allVehiclesData,
        timestamp: new Date().toISOString(),
        filters: currentFilters,
        visibility: rtVisibility
      };
      
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `network-data-${new Date().getTime()}.json`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
      showNotification('Data exported successfully', 'success');
    }
    
    // Improved simple map fallback
    function initSimpleMap() {
      console.log('Initializing simple map fallback...');
      const mapContainer = document.getElementById('realTimeMap');
      if (!mapContainer) return;
      
      mapContainer.innerHTML = `
        <div style="height: 500px; width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; text-align: center; position: relative; overflow: hidden;">
          <div style="z-index: 2; max-width: 400px; padding: 2rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem; animation: pulse 2s infinite;">🗺️</div>
            <h3 style="margin-bottom: 1rem; font-weight: 700;">Service Network Map</h3>
            <p style="margin-bottom: 1.5rem; opacity: 0.9; line-height: 1.5;">Real-time service network visualization for Manila Metro Area transportation system.</p>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem; font-size: 0.9rem;">
              <div style="background: rgba(255,255,255,0.1); padding: 0.75rem; border-radius: 0.5rem;">
                <span style="color: #4CAF50; font-size: 1.2rem;">●</span><br>
                <strong>6 Active</strong><br>
                <small>Vehicles Online</small>
              </div>
              <div style="background: rgba(255,255,255,0.1); padding: 0.75rem; border-radius: 0.5rem;">
                <span style="color: #FF9800; font-size: 1.2rem;">●</span><br>
                <strong>1 Maintenance</strong><br>
                <small>Under Service</small>
              </div>
              <div style="background: rgba(255,255,255,0.1); padding: 0.75rem; border-radius: 0.5rem;">
                <span style="color: #2196F3; font-size: 1.2rem;">●</span><br>
                <strong>5 Routes</strong><br>
                <small>Active Lines</small>
              </div>
              <div style="background: rgba(255,255,255,0.1); padding: 0.75rem; border-radius: 0.5rem;">
                <span style="color: #9C27B0; font-size: 1.2rem;">●</span><br>
                <strong>12 Points</strong><br>
                <small>Service Hubs</small>
              </div>
            </div>
            <div class="mt-3">
              <button class="btn btn-light btn-sm me-2" onclick="initMapImmediate()" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white;">
                <i class="bi bi-arrow-clockwise"></i> Try Loading Map
              </button>
              <button class="btn btn-outline-light btn-sm" onclick="debugMap()">
                <i class="bi bi-info-circle"></i> Debug Info
              </button>
            </div>
          </div>
          
          <!-- Animated background elements -->
          <div style="position: absolute; top: 15%; left: 15%; width: 12px; height: 12px; background: rgba(255,255,255,0.2); border-radius: 50%; animation: float 4s ease-in-out infinite;"></div>
          <div style="position: absolute; top: 25%; right: 20%; width: 8px; height: 8px; background: rgba(255,255,255,0.15); border-radius: 50%; animation: float 6s ease-in-out infinite 1s;"></div>
          <div style="position: absolute; bottom: 30%; left: 25%; width: 10px; height: 10px; background: rgba(255,255,255,0.1); border-radius: 50%; animation: float 5s ease-in-out infinite 2s;"></div>
          <div style="position: absolute; bottom: 20%; right: 15%; width: 14px; height: 14px; background: rgba(255,255,255,0.08); border-radius: 50%; animation: float 7s ease-in-out infinite 3s;"></div>
        </div>
        
        <style>
          @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
          }
          @keyframes float {
            0%, 100% { transform: translateY(0px) scale(1); opacity: 0.6; }
            50% { transform: translateY(-15px) scale(1.2); opacity: 1; }
          }
        </style>
      `;
      
      // Hide loading indicator
      const loadingEl = document.getElementById('mapLoadingIndicator');
      if (loadingEl) {
        loadingEl.style.display = 'none';
      }
      
      // Update stats with fallback data
      updateMapStats({ vehicles: 6, routes: 5, servicePoints: 12, alerts: 0 });
      
      console.log('Simple map fallback initialized');
    }
    
    // Debug function for troubleshooting
    function debugMap() {
      const info = {
        leafletLoaded: typeof L !== 'undefined',
        mapExists: !!leafletMap,
        mapContainer: !!document.getElementById('realTimeMap'),
        containerDimensions: {
          width: document.getElementById('realTimeMap')?.offsetWidth || 0,
          height: document.getElementById('realTimeMap')?.offsetHeight || 0
        },
        layersInitialized: !!rtLayers.vehicles,
        dataLoaded: {
          vehicles: allVehiclesData?.length || 0,
          routes: routes?.length || 0,
          servicePoints: servicePoints?.length || 0
        },
        currentFilters: currentFilters,
        liveUpdatesEnabled: liveUpdatesEnabled,
        connectionStatus: sseConnection?.readyState || 'Not connected'
      };
      
      console.log('🐛 MAP DEBUG INFO:', info);
      
      // Show debug info in modal
      const debugHtml = `
        <div>
          <h6>Map Debug Information</h6>
          <pre style="background: #f8f9fa; padding: 1rem; border-radius: 0.375rem; font-size: 0.8rem;">${JSON.stringify(info, null, 2)}</pre>
          <div class="mt-3">
            <button class="btn btn-sm btn-primary" onclick="initMapImmediate()">Force Reinitialize</button>
            <button class="btn btn-sm btn-secondary" onclick="refreshMapData()">Refresh Data</button>
            <button class="btn btn-sm btn-warning" onclick="initSimpleMap()">Simple View</button>
          </div>
        </div>
      `;
      
      Swal.fire({
        title: 'Map Debug Information',
        html: debugHtml,
        width: '600px',
        showCloseButton: true,
        showConfirmButton: false
      });
      
      return info;
    }
    
    // Update map statistics
    function updateMapStats(stats) {
      if (stats.vehicles !== undefined) {
        document.getElementById('activeVehiclesCount').textContent = stats.vehicles;
      }
      if (stats.routes !== undefined) {
        document.getElementById('totalRoutesCount').textContent = stats.routes;
      }
      if (stats.servicePoints !== undefined) {
        document.getElementById('servicePointsCount').textContent = stats.servicePoints;
      }
      if (stats.alerts !== undefined) {
        document.getElementById('alertsCount').textContent = stats.alerts;
      }
      document.getElementById('lastUpdateTime').textContent = new Date().toLocaleString();
    }
    
    // Force map initialization
    function forceInitMap() {
      console.log('🔧 Force initializing map...');
      leafletMap = null; // Reset map reference
      rtLayers = { vehicles: null, routes: null, servicePoints: null, alerts: null }; // Reset layers
      initMapImmediate();
    }

    function populateFilterOptions(vehicles) {
      // Populate route filter
      const routeFilter = document.getElementById('routeFilter');
      const routes = [...new Set(vehicles.map(v => v.route_name).filter(r => r))];
      
      // Clear existing options except 'All Routes'
      routeFilter.innerHTML = '<option value="all">All Routes</option>';
      
      routes.forEach(route => {
        const option = document.createElement('option');
        option.value = route;
        option.textContent = route;
        routeFilter.appendChild(option);
      });
    }
    
    // Notification system
    function showNotification(message, type = 'info') {
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer);
          toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
      });
      
      Toast.fire({
        icon: type,
        title: message
      });
    }
    
    // Status badge helper function
    function getStatusBadgeClass(status) {
      const classes = {
        'Active': 'bg-success',
        'Inactive': 'bg-secondary', 
        'Maintenance': 'bg-warning',
        'Planned': 'bg-info',
        'Warning': 'bg-warning',
        'Emergency': 'bg-danger'
      };
      return classes[status] || 'bg-secondary';
    }

    async function fetchRoutes() {
      try {
        const res = await fetchWithLoading(ROUTES_API);
        const data = await res.json();
        routes = Array.isArray(data) ? data.map(dbToUiRoute) : [];
        loadRoutes();
        updateDashboardStats();
      } catch (e) {
        showNotification('Failed to load routes', 'danger');
      }
    }

    async function fetchServicePoints() {
      try {
        const res = await fetchWithLoading(POINTS_API);
        const data = await res.json();
        servicePoints = Array.isArray(data) ? data.map(dbToUiPoint) : [];
        loadServicePoints();
        updateDashboardStats();
      } catch (e) {
        showNotification('Failed to load service points', 'danger');
      }
    }

    function dbToUiRoute(row) {
      return {
        id: parseInt(row.id),
        name: row.name,
        type: row.type,
        startPoint: row.start_point,
        endPoint: row.end_point,
        distance: parseFloat(row.distance),
        frequency: row.frequency,
        status: row.status,
        estimatedTime: parseInt(row.estimated_time),
        notes: row.notes || ''
      };
    }

    function uiToDbRoute(r) {
      return {
        name: r.name,
        type: r.type,
        startPoint: r.startPoint,
        endPoint: r.endPoint,
        distance: r.distance,
        frequency: r.frequency,
        status: r.status,
        estimatedTime: r.estimatedTime,
        notes: r.notes || ''
      };
    }

    function dbToUiPoint(row) {
      return {
        id: parseInt(row.id),
        name: row.name,
        type: row.type,
        location: row.location,
        services: row.services,
        status: row.status,
        notes: row.notes || ''
      };
    }

    function uiToDbPoint(p) {
      return {
        name: p.name,
        type: p.type,
        location: p.location,
        services: p.services,
        status: p.status,
        notes: p.notes || ''
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

      // Service Point form submit
      document.getElementById('servicePointForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveServicePoint();
      });
    }

    function applyStoredTheme() {
      const stored = localStorage.getItem('theme');
      const isDark = stored === 'dark';
      document.body.classList.toggle('dark-mode', isDark);
      const toggle = document.getElementById('themeToggle');
      if (toggle) toggle.checked = isDark;
    }

    function loadRoutes() {
      const tbody = document.getElementById('routesTableBody');
      tbody.innerHTML = '';

      routes.forEach(route => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${route.id}</td>
          <td>${route.name}</td>
          <td>${route.type}</td>
          <td>${route.startPoint}</td>
          <td>${route.endPoint}</td>
          <td>${route.distance} km</td>
          <td>${route.frequency}</td>
          <td><span class="badge ${getStatusBadgeClass(route.status)}">${route.status}</span></td>
          <td>
            <div class="action-buttons">
              <button class="btn btn-sm btn-info" onclick="viewRoute(${route.id})" title="View Details">
                <i class="bi bi-eye"></i>
              </button>
              <button class="btn btn-sm btn-primary" onclick="editRoute(${route.id})" title="Edit Route">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-sm btn-warning" onclick="optimizeRoute(${route.id})" title="Optimize Route">
                <i class="bi bi-gear"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteRoute(${route.id})" title="Delete Route">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </td>
        `;
        tbody.appendChild(row);
      });
    }

    function loadServicePoints() {
      const tbody = document.getElementById('servicePointsTableBody');
      tbody.innerHTML = '';

      servicePoints.forEach(point => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${point.id}</td>
          <td>${point.name}</td>
          <td>${point.type}</td>
          <td>${point.location}</td>
          <td>${point.services}</td>
          <td><span class="badge ${getStatusBadgeClass(point.status)}">${point.status}</span></td>
          <td>
            <div class="action-buttons">
              <button class="btn btn-sm btn-info" onclick="viewServicePoint(${point.id})" title="View Details">
                <i class="bi bi-eye"></i>
              </button>
              <button class="btn btn-sm btn-primary" onclick="editServicePoint(${point.id})" title="Edit Service Point">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteServicePoint(${point.id})" title="Delete Service Point">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </td>
        `;
        tbody.appendChild(row);
      });
    }
    
    // Force map initialization
    function forceInitMap() {
      console.log('Force initializing map...');
      
      // Remove existing map if any
      if (leafletMap) {
        console.log('Removing existing map...');
        leafletMap.remove();
        leafletMap = null;
      }
      
      // Reset variables
      rtLayers = { vehicles: null, routes: null, servicePoints: null, alerts: null };
      
      // Show loading indicator
      const loadingIndicator = document.getElementById('mapLoadingIndicator');
      if (loadingIndicator) {
        loadingIndicator.style.display = 'flex';
        loadingIndicator.innerHTML = `
          <div class="text-center">
            <div class="spinner-border text-primary mb-2" role="status"></div>
            <div>Reinitializing Map...</div>
          </div>
        `;
      }
      
      // Force initialization
      setTimeout(() => {
        initMapImmediate();
        if (leafletMap) {
          showNotification('Map reinitialized successfully', 'success');
        } else {
          showNotification('Failed to reinitialize map', 'error');
        }
      }, 100);
    }
    
    // Debug function
    function debugMap() {
      console.log('=== MAP DEBUG INFO ===');
      console.log('leafletMap:', leafletMap);
      console.log('Map element exists:', !!document.getElementById('realTimeMap'));
      console.log('Leaflet library loaded:', typeof L !== 'undefined');
      console.log('rtLayers:', rtLayers);
      console.log('rtVisibility:', rtVisibility);
      
      const mapEl = document.getElementById('realTimeMap');
      if (mapEl) {
        console.log('Map element dimensions:', {
          width: mapEl.offsetWidth,
          height: mapEl.offsetHeight,
          display: getComputedStyle(mapEl).display,
          visibility: getComputedStyle(mapEl).visibility
        });
      }
      
      // Try to reinitialize if map is missing
      if (!leafletMap && document.getElementById('realTimeMap')) {
        console.log('Attempting to reinitialize map...');
        initMap();
        if (leafletMap) {
          initRealTimeMap();
          showNotification('Map reinitialized successfully', 'success');
        } else {
          showNotification('Failed to reinitialize map', 'error');
        }
      } else if (leafletMap) {
        // Force refresh
        leafletMap.invalidateSize();
        refreshMapData();
        showNotification('Map refreshed', 'info');
      }
    }

    function getStatusBadgeClass(status) {
      switch(status) {
        case 'Active': return 'bg-success';
        case 'Inactive': return 'bg-secondary';
        case 'Maintenance': return 'bg-warning text-dark';
        case 'Planned': return 'bg-info';
        default: return 'bg-secondary';
      }
    }

    async function updateDashboardStats() {
      try {
        // Show loading state
        const elements = ['totalRoutes', 'servicePoints', 'coverageArea', 'efficiencyScore'];
        elements.forEach(id => {
          const element = document.getElementById(id);
          if (element) {
            element.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';
          }
        });

        const response = await fetch('api/dashboard-stats.php');
        if (!response.ok) throw new Error('Failed to fetch dashboard stats');
        
        const stats = await response.json();
        
        if (stats.status === 'error') {
          throw new Error(stats.message || 'API returned error');
        }

        // Update dashboard cards with real data
        document.getElementById('totalRoutes').textContent = stats.totalRoutes;
        document.getElementById('servicePoints').textContent = stats.servicePoints;
        document.getElementById('coverageArea').textContent = stats.coverageArea + ' km²';
        document.getElementById('efficiencyScore').textContent = stats.efficiencyScore + '%';
        
        console.log('Dashboard stats updated:', stats);
        
      } catch (error) {
        console.error('Failed to update dashboard stats:', error);
        
        // Show error state with retry option
        const elements = {
          'totalRoutes': '0',
          'servicePoints': '0', 
          'coverageArea': '0 km²',
          'efficiencyScore': '0%'
        };
        
        Object.entries(elements).forEach(([id, fallback]) => {
          const element = document.getElementById(id);
          if (element) {
            element.innerHTML = `<span class="text-danger" title="${error.message}">${fallback}</span>`;
          }
        });
        
        showNotification('Failed to load dashboard statistics', 'warning');
      }
    }

    // Function to refresh dashboard stats manually
    function refreshDashboardStats() {
      updateDashboardStats();
    }

    // Helper function to show notifications
    function showNotification(message, type = 'info') {
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
      });
      
      let icon = 'info';
      if (type === 'success') icon = 'success';
      else if (type === 'error' || type === 'danger') icon = 'error';
      else if (type === 'warning') icon = 'warning';
      
      Toast.fire({
        icon: icon,
        title: message
      });
    }

    // Helper function for API calls with loading states
    async function fetchWithLoading(url, options = {}) {
      try {
        const response = await fetch(url, options);
        return response;
      } catch (error) {
        console.error('Fetch error:', error);
        throw error;
      }
    }

    // Simple fallback map when Leaflet fails to load
    function initSimpleMap() {
      const mapContainer = document.getElementById('realTimeMap');
      if (!mapContainer) return;
      
      const loadingEl = document.getElementById('mapLoadingIndicator');
      if (loadingEl) loadingEl.style.display = 'none';
      
      mapContainer.innerHTML = `
        <div style="height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center; flex-direction: column;">
          <i class="bi bi-geo-alt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.8;"></i>
          <h4>Interactive Map</h4>
          <p>Real-time vehicle tracking and route visualization</p>
          <small style="opacity: 0.7;">Map service temporarily unavailable</small>
        </div>
      `;
      
      console.log('Simple fallback map initialized');
    }

    // Logout confirmation
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

    // Preload shipments data for tracking functionality
    function preloadShipments() {
      console.log('Preloading shipment data...');
      // This will be implemented when shipment tracking is fully integrated
      // For now, just log that it's called during initialization
    }

    // Route functions
    function openAddRouteModal() {
      isEditMode = false;
      currentRouteId = null;
      const title = document.getElementById('routeModalLabel');
      if (title) title.textContent = 'Add New Route';
      document.getElementById('routeForm').reset();
      document.getElementById('routeId').value = '';
      const modal = new bootstrap.Modal(document.getElementById('routeModal'));
      modal.show();
    }

    function viewRoute(id) {
      const route = routes.find(r => r.id === id);
      if (!route) return;

      document.getElementById('viewRouteId').textContent = route.id;
      document.getElementById('viewRouteName').textContent = route.name;
      document.getElementById('viewRouteType').textContent = route.type;
      document.getElementById('viewRouteStartPoint').textContent = route.startPoint;
      document.getElementById('viewRouteEndPoint').textContent = route.endPoint;
      document.getElementById('viewRouteDistance').textContent = route.distance + ' km';
      document.getElementById('viewRouteFrequency').textContent = route.frequency;
      document.getElementById('viewRouteStatus').textContent = route.status;
      document.getElementById('viewRouteNotes').textContent = route.notes || 'No notes available';

      const viewModal = new bootstrap.Modal(document.getElementById('viewRouteModal'));
      viewModal.show();
    }

    function editRoute(id) {
      const route = routes.find(r => r.id === id);
      if (!route) return;

      isEditMode = true;
      currentRouteId = id;
      const title = document.getElementById('routeModalLabel');
      if (title) title.textContent = 'Edit Route';
      
      // Populate form fields
      document.getElementById('routeId').value = route.id;
      document.getElementById('routeName').value = route.name;
      document.getElementById('routeType').value = route.type;
      document.getElementById('startPoint').value = route.startPoint;
      document.getElementById('endPoint').value = route.endPoint;
      document.getElementById('distance').value = route.distance;
      document.getElementById('estimatedTime').value = route.estimatedTime;
      document.getElementById('serviceFrequency').value = route.frequency;
      document.getElementById('routeStatus').value = route.status;
      document.getElementById('routeNotes').value = route.notes || '';

      const modal = new bootstrap.Modal(document.getElementById('routeModal'));
      modal.show();
    }

    function optimizeRoute(id) {
      const route = routes.find(r => r.id === id);
      if (!route) return;

      showNotification(`Optimizing route: ${route.name}. Analyzing traffic patterns and calculating optimal path...`, 'info');
    }

    function deleteRoute(id) {
      const route = routes.find(r => r.id === id);
      if (!route) return;
      Swal.fire({
        title: `Delete route "${route.name}"?`,
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#dc3545'
      }).then(async (res) => {
        if (res.isConfirmed) {
          try {
            const r = await fetchWithLoading(`${ROUTES_API}?id=${id}`, { method: 'DELETE' });
            if (!r.ok) throw new Error();
            await fetchRoutes();
            updateDashboardStats();
            showNotification('Route deleted successfully!', 'success');
          } catch (e) {
            showNotification('Failed to delete', 'danger');
          }
        }
      });
    }

    async function saveRoute() {
      const form = document.getElementById('routeForm');
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const formData = new FormData(form);
      const routeData = {
        name: formData.get('routeName'),
        type: formData.get('routeType'),
        startPoint: formData.get('startPoint'),
        endPoint: formData.get('endPoint'),
        distance: parseFloat(formData.get('distance')),
        frequency: formData.get('serviceFrequency'),
        status: formData.get('routeStatus'),
        estimatedTime: parseInt(formData.get('estimatedTime')),
        notes: formData.get('routeNotes')
      };

      try {
        if (isEditMode && currentRouteId) {
          const res = await fetchWithLoading(`${ROUTES_API}?id=${currentRouteId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(uiToDbRoute(routeData))
          });
          if (!res.ok) throw new Error();
          showNotification('Route updated successfully!', 'success');
        } else {
          const res = await fetchWithLoading(ROUTES_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(uiToDbRoute(routeData))
          });
          if (!res.ok) throw new Error();
          showNotification('Route added successfully!', 'success');
        }
        await fetchRoutes();
        const modal = bootstrap.Modal.getInstance(document.getElementById('routeModal'));
        if (modal) modal.hide();
      } catch (e) {
        showNotification('Failed to save route', 'danger');
      }
    }

    // Service Point functions
    function openAddServicePointModal() {
      isEditMode = false;
      currentServicePointId = null;
      const title = document.getElementById('servicePointModalLabel');
      if (title) title.textContent = 'Add New Service Point';
      document.getElementById('servicePointForm').reset();
      document.getElementById('servicePointId').value = '';
      const modal = new bootstrap.Modal(document.getElementById('servicePointModal'));
      modal.show();
    }

    function viewServicePoint(id) {
      const point = servicePoints.find(p => p.id === id);
      if (!point) return;

      document.getElementById('viewPointId').textContent = point.id;
      document.getElementById('viewPointName').textContent = point.name;
      document.getElementById('viewPointType').textContent = point.type;
      document.getElementById('viewPointLocation').textContent = point.location;
      document.getElementById('viewPointServices').textContent = point.services;
      document.getElementById('viewPointStatus').textContent = point.status;
      document.getElementById('viewPointNotes').textContent = point.notes || 'No notes available';

      const viewModal = new bootstrap.Modal(document.getElementById('viewServicePointModal'));
      viewModal.show();
    }

    function editServicePoint(id) {
      const point = servicePoints.find(p => p.id === id);
      if (!point) return;

      isEditMode = true;
      currentServicePointId = id;
      const title = document.getElementById('servicePointModalLabel');
      if (title) title.textContent = 'Edit Service Point';
      
      // Populate form fields
      document.getElementById('servicePointId').value = point.id;
      document.getElementById('pointName').value = point.name;
      document.getElementById('pointType').value = point.type;
      document.getElementById('pointLocation').value = point.location;
      document.getElementById('pointServices').value = point.services;
      document.getElementById('pointStatus').value = point.status;
      document.getElementById('pointNotes').value = point.notes || '';

      const modal = new bootstrap.Modal(document.getElementById('servicePointModal'));
      modal.show();
    }

    function deleteServicePoint(id) {
      const point = servicePoints.find(p => p.id === id);
      if (!point) return;
      Swal.fire({
        title: `Delete service point "${point.name}"?`,
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#dc3545'
      }).then(async (res) => {
        if (res.isConfirmed) {
          try {
            const r = await fetchWithLoading(`${POINTS_API}?id=${id}`, { method: 'DELETE' });
            if (!r.ok) throw new Error();
            await fetchServicePoints();
            updateDashboardStats();
            showNotification('Service point deleted successfully!', 'success');
          } catch (e) {
            showNotification('Failed to delete', 'danger');
          }
        }
      });
    }

    async function saveServicePoint() {
      const form = document.getElementById('servicePointForm');
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const formData = new FormData(form);
      const pointData = {
        name: formData.get('pointName'),
        type: formData.get('pointType'),
        location: formData.get('pointLocation'),
        services: formData.get('pointServices'),
        status: formData.get('pointStatus'),
        notes: formData.get('pointNotes')
      };

      try {
        if (isEditMode && currentServicePointId) {
          const res = await fetchWithLoading(`${POINTS_API}?id=${currentServicePointId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(uiToDbPoint(pointData))
          });
          if (!res.ok) throw new Error();
          showNotification('Service point updated successfully!', 'success');
        } else {
          const res = await fetchWithLoading(POINTS_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(uiToDbPoint(pointData))
          });
          if (!res.ok) throw new Error();
          showNotification('Service point added successfully!', 'success');
        }
        await fetchServicePoints();
        const modal = bootstrap.Modal.getInstance(document.getElementById('servicePointModal'));
        if (modal) modal.hide();
      } catch (e) {
        showNotification('Failed to save service point', 'danger');
      }
    }

    // Inline form helpers no longer used (modals handle show/hide)

    // SweetAlert View dialogs
    function viewRoute(id) {
      const route = routes.find(r => r.id === id);
      if (!route) return;
      Swal.fire({
        title: 'Route Details',
        html: `
          <div class="text-start">
            <p><strong>ID:</strong> ${route.id}</p>
            <p><strong>Name:</strong> ${route.name}</p>
            <p><strong>Type:</strong> ${route.type}</p>
            <p><strong>Start Point:</strong> ${route.startPoint}</p>
            <p><strong>End Point:</strong> ${route.endPoint}</p>
            <p><strong>Distance:</strong> ${route.distance} km</p>
            <p><strong>Frequency:</strong> ${route.frequency}</p>
            <p><strong>Status:</strong> ${route.status}</p>
            <p><strong>Notes:</strong> ${route.notes || 'No notes available'}</p>
          </div>
        `,
        icon: 'info'
      });
    }

    function viewServicePoint(id) {
      const point = servicePoints.find(p => p.id === id);
      if (!point) return;
      Swal.fire({
        title: 'Service Point Details',
        html: `
          <div class="text-start">
            <p><strong>ID:</strong> ${point.id}</p>
            <p><strong>Name:</strong> ${point.name}</p>
            <p><strong>Type:</strong> ${point.type}</p>
            <p><strong>Location:</strong> ${point.location}</p>
            <p><strong>Services:</strong> ${point.services}</p>
            <p><strong>Status:</strong> ${point.status}</p>
            <p><strong>Notes:</strong> ${point.notes || 'No notes available'}</p>
          </div>
        `,
        icon: 'info'
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
      // After the overlay hides, force the map to recalc its size
      setTimeout(() => {
        if (leafletMap) {
          leafletMap.invalidateSize(true);
        }
      }, 200);
    }

    async function fetchWithLoading(url, options = {}) {
      try {
        showLoading('Processing...', 'Please wait');
        const res = await fetch(url, options);
        return res;
      } finally {
        hideLoading();
      }
    }

    function showNotification(message, type = 'info') {
      const bg = type === 'success' ? '#1cc88a' : type === 'danger' ? '#e74a3b' : type === 'warning' ? '#f6c23e' : '#36b9cc';
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2200,
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer);
          toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
      });
      Toast.fire({
        icon: type === 'danger' ? 'error' : type,
        title: message,
        background: bg,
        color: '#fff'
      });
    }

    // Show loader on page load, hide after content is ready
    document.addEventListener('DOMContentLoaded', function() {
      showLoading('Loading Service Network...', 'Preparing service network and route planner');
      setTimeout(() => {
        hideLoading();
      }, 1200); // Adjust time as needed or hide after your data loads
    });
    
    // Fallback initialization on window load
    window.addEventListener('load', function() {
      console.log('Window loaded, checking map initialization...');
      if (leafletMap) {
        // Ensure sizing is correct on load
        setTimeout(() => leafletMap.invalidateSize(true), 300);
      }
    });

    // Hide loader when page is fully loaded
    window.addEventListener('load', function() {
      const loader = document.getElementById('loaderOverlay');
      loader.style.opacity = '0';
      setTimeout(() => loader.style.display = 'none', 400);
    });

    // Throttle resize to keep map tiles aligned
    let __resizeTimer;
    window.addEventListener('resize', function() {
      clearTimeout(__resizeTimer);
      __resizeTimer = setTimeout(() => {
        if (leafletMap) leafletMap.invalidateSize(true);
      }, 200);
    });

    // Note: confirmLogout function is defined earlier in the file

    // Shipment tracking
    async function preloadShipments() {
      try {
        const res = await fetch(SHIPMENTS_API);
        const data = await res.json();
        window.__shipmentsCache = normalizeShipments(data);
        renderTrackingTeaser(window.__shipmentsCache);
      } catch (e) {
        window.__shipmentsCache = [];
      }
    }

    function normalizeShipments(rows) {
      if (!Array.isArray(rows)) return [];
      return rows.map((r, idx) => ({
        id: r.tracking_id || r.id || `SHP-${1000 + idx}`,
        route: r.route || r.name || r.route_name || '-',
        status: r.status || 'In Transit',
        lastUpdate: r.updated_at || r.last_update || '-',
        eta: r.eta || r.arrival || r.estimated_arrival || '-',
        location: r.current_location || r.location || '-'
      }));
    }

    function renderTrackingTeaser(shipments) {
      const tbody = document.getElementById('trackingTableBody');
      if (!tbody) return;
      tbody.innerHTML = '';
      const items = shipments.slice(0, 5);
      items.forEach(s => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${s.id}</td>
          <td>${s.route}</td>
          <td><span class="badge ${getStatusBadgeClass(s.status)}">${s.status}</span></td>
          <td>${s.eta}</td>
          <td>
            <button class="btn btn-sm btn-info" onclick="openTrackingDetails('${s.id}')">
              <i class=\"bi bi-eye\"></i>
            </button>
          </td>
        `;
        tbody.appendChild(tr);
      });
      if (items.length === 0) {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td colspan="5" class="text-center text-muted py-4">No shipment records available.</td>`;
        tbody.appendChild(tr);
      }
    }

    function openTrackingModal() {
      const modal = new bootstrap.Modal(document.getElementById('trackingModal'));
      renderTrackingModalTable(window.__shipmentsCache || []);
      modal.show();
    }

    function renderTrackingModalTable(shipments) {
      const tbody = document.querySelector('#trackingModalTable tbody');
      if (!tbody) return;
      tbody.innerHTML = '';
      shipments.forEach(s => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${s.id}</td>
          <td>${s.route}</td>
          <td><span class="badge ${getStatusBadgeClass(s.status)}">${s.status}</span></td>
          <td>${s.lastUpdate}</td>
          <td>${s.eta}</td>
          <td>${s.location}</td>
          <td>
            <button class="btn btn-sm btn-info" onclick="openTrackingDetails('${s.id}')">
              <i class=\"bi bi-eye\"></i>
            </button>
          </td>
        `;
        tbody.appendChild(tr);
      });
      if (shipments.length === 0) {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td colspan="7" class="text-center text-muted py-4">No shipment records available.</td>`;
        tbody.appendChild(tr);
      }
    }

    function openTrackingDetails(id) {
      const s = (window.__shipmentsCache || []).find(x => String(x.id) === String(id));
      if (!s) return;
      const set = (i,v)=>{ const el = document.getElementById(i); if (el) el.textContent = v; };
      set('td_id', s.id);
      set('td_route', s.route);
      set('td_status', s.status);
      set('td_lastUpdate', s.lastUpdate);
      set('td_eta', s.eta);
      set('td_location', s.location);
      const modal = new bootstrap.Modal(document.getElementById('trackingDetailsModal'));
      modal.show();
    }
  </script>
</body>
</html>
