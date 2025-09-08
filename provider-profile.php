<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is a provider
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header('Location: login.php');
    exit();
}

$provider_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get provider information
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ? AND role = 'provider'");
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$provider = $result->fetch_assoc();

if (!$provider) {
    header('Location: login.php');
    exit();
}

// Get provider statistics
$stats = [];

// Total services
$result = $mysqli->query("SELECT COUNT(*) as count FROM services WHERE provider_id = $provider_id");
$stats['total_services'] = $result ? $result->fetch_assoc()['count'] : 0;

// Active services
$result = $mysqli->query("SELECT COUNT(*) as count FROM services WHERE provider_id = $provider_id AND status = 'active'");
$stats['active_services'] = $result ? $result->fetch_assoc()['count'] : 0;

// Completed services
$result = $mysqli->query("SELECT COUNT(*) as count FROM services WHERE provider_id = $provider_id AND status = 'completed'");
$stats['completed_services'] = $result ? $result->fetch_assoc()['count'] : 0;

// Monthly revenue
$result = $mysqli->query("SELECT COALESCE(SUM(revenue), 0) as revenue FROM services WHERE provider_id = $provider_id AND status = 'completed' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
$stats['total_revenue'] = $result ? $result->fetch_assoc()['revenue'] : 0;

// Vehicle and driver counts (with table existence check)
$vehicle_check = $mysqli->query("SHOW TABLES LIKE 'vehicles'");
if ($vehicle_check && $vehicle_check->num_rows > 0) {
    $result = $mysqli->query("SELECT COUNT(*) as count FROM vehicles WHERE provider_id = $provider_id");
    $stats['vehicle_count'] = $result ? $result->fetch_assoc()['count'] : 0;
} else {
    $stats['vehicle_count'] = 0;
}

$driver_check = $mysqli->query("SHOW TABLES LIKE 'drivers'");
if ($driver_check && $driver_check->num_rows > 0) {
    $result = $mysqli->query("SELECT COUNT(*) as count FROM drivers WHERE provider_id = $provider_id");
    $stats['driver_count'] = $result ? $result->fetch_assoc()['count'] : 0;
} else {
    $stats['driver_count'] = 0;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $service_area = $_POST['service_area'] ?? '';
        $description = $_POST['description'] ?? '';
        $provider_type = $_POST['provider_type'] ?? '';
        $experience = $_POST['experience'] ?? '';

        $stmt = $mysqli->prepare("UPDATE users SET name = ?, email = ?, phone = ?, service_area = ?, description = ?, provider_type = ?, experience = ? WHERE id = ?");
        $stmt->bind_param("sssssssi", $name, $email, $phone, $service_area, $description, $provider_type, $experience, $provider_id);
        
        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            // Refresh provider data
            $stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ? AND role = 'provider'");
            $stmt->bind_param("i", $provider_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $provider = $result->fetch_assoc();
        } else {
            $error_message = "Failed to update profile. Please try again.";
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } elseif (password_verify($current_password, $provider['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $provider_id);
            
            if ($stmt->execute()) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Failed to change password. Please try again.";
            }
        } else {
            $error_message = "Current password is incorrect.";
        }
    }
}

// Set role variables for sidebar
$isProvider = true;
$isAdmin = false;
$isUser = false;
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
  <title>My Profile | CORE II</title>
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
    
    .system-subtitle {
      font-size: 0.8rem;
      color: rgba(255,255,255,0.7);
      margin: 0;
      font-weight: 500;
    }

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

    .sidebar-footer .nav-link i {
      margin-right: 0;
    }

    /* Enhanced transitions */
    .sidebar.transitioning {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Main Content */
    .content { 
      margin-left: var(--sidebar-width); 
      padding: 2rem; 
      transition: all 0.3s ease; 
      min-height: 100vh;
      width: calc(100% - var(--sidebar-width));
      max-width: none;
    }

    .content.expanded {
      margin-left: 0;
      width: 100%;
    }

    /* Header */
    .header { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px); padding: 1.5rem 2rem; border-radius: var(--border-radius); box-shadow: 0 8px 32px rgba(0,0,0,0.1); margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(255,255,255,0.2); }

    .dark-mode .header { background: rgba(44, 62, 80, 0.9); color: var(--text-light); border: 1px solid rgba(255,255,255,0.1); }

    .hamburger { font-size: 1.5rem; cursor: pointer; padding: 0.75rem; border-radius: 0.5rem; transition: all 0.3s; background: rgba(0,0,0,0.05); }
    .hamburger:hover { background: rgba(0,0,0,0.1); }

    .system-title { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-size: 2.2rem; font-weight: 800; }

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

    /* Cards */
    .card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px) saturate(180%);
      border: 1px solid rgba(0, 0, 0, 0.05);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      transition: all 0.3s ease;
      overflow: hidden;
      position: relative;
      margin-bottom: 2rem;
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    }

    .card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    body.dark-mode .card {
      background: rgba(22, 33, 62, 0.95);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: var(--text-light);
    }

    body.dark-mode .card::before {
      background: linear-gradient(90deg, #74b9ff 0%, #0984e3 100%);
    }

    .card-header {
      background: rgba(102, 126, 234, 0.1);
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      padding: 1.5rem 2rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    body.dark-mode .card-header {
      background: rgba(116, 185, 255, 0.1);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      color: var(--text-light);
    }

    .card-header h5 {
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-weight: 600;
      font-size: 1.25rem;
    }

    .card-body {
      padding: 2rem;
    }

    /* Profile Header */
    .profile-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: var(--border-radius);
      padding: 3rem 2rem;
      margin-bottom: 2rem;
      color: white;
      position: relative;
      overflow: hidden;
    }

    .profile-header::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
      animation: float 6s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-20px) rotate(180deg); }
    }

    .profile-avatar {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 4px solid rgba(255, 255, 255, 0.3);
      object-fit: cover;
      transition: all 0.3s ease;
      position: relative;
      z-index: 2;
    }

    .profile-avatar:hover {
      transform: scale(1.05);
      border-color: rgba(255, 255, 255, 0.5);
    }

    /* Statistics Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px) saturate(180%);
      border: 1px solid rgba(0, 0, 0, 0.05);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: hidden;
      position: relative;
      min-height: 180px;
      display: flex;
      flex-direction: column;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: var(--primary-color);
      transition: all 0.3s ease;
    }

    .stat-card-success::before { background: var(--success-color); }
    .stat-card-info::before { background: var(--info-color); }
    .stat-card-warning::before { background: var(--warning-color); }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .stat-card:hover::before { height: 4px; }

    body.dark-mode .stat-card {
      background: rgba(22, 33, 62, 0.95);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: var(--text-light);
    }

    .stat-card .card-body {
      padding: 2.5rem 2rem;
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      text-align: center;
      gap: 1rem;
    }

    .stat-icon {
      font-size: 3rem;
      margin-bottom: 0.5rem;
      opacity: 0.8;
    }

    .stat-label {
      font-size: 0.875rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 0.75rem;
      color: #6c757d;
    }

    .stat-value {
      font-size: 3rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      line-height: 1;
    }

    .stat-subtitle {
      font-size: 1rem;
      color: #adb5bd;
      margin: 0;
    }

    body.dark-mode .stat-label {
      color: #adb5bd;
    }

    body.dark-mode .stat-subtitle {
      color: #6c757d;
    }

    /* Navigation Pills */
    .nav-pills {
      margin-bottom: 2rem;
    }

    .nav-pills .nav-link {
      background: transparent;
      border: 1px solid rgba(102, 126, 234, 0.3);
      color: #667eea;
      border-radius: 12px;
      margin-right: 0.5rem;
      margin-bottom: 0.5rem;
      padding: 1rem 1.5rem;
      font-weight: 500;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 1.05rem;
    }

    .nav-pills .nav-link:hover {
      background: rgba(102, 126, 234, 0.1);
      transform: translateY(-1px);
    }

    .nav-pills .nav-link.active {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-color: transparent;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    body.dark-mode .nav-pills .nav-link {
      border-color: rgba(116, 185, 255, 0.3);
      color: #74b9ff;
    }

    body.dark-mode .nav-pills .nav-link:hover {
      background: rgba(116, 185, 255, 0.1);
    }

    body.dark-mode .nav-pills .nav-link.active {
      background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
    }

    /* Form Styles */
    .form-floating {
      margin-bottom: 2rem;
    }

    .form-floating .form-control {
      border: 1px solid rgba(0, 0, 0, 0.1);
      border-radius: 12px;
      padding: 1.25rem 1rem;
      background: rgba(255, 255, 255, 0.8);
      transition: all 0.3s ease;
      font-size: 1rem;
    }

    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
      background: white;
    }

    body.dark-mode .form-control {
      background: rgba(22, 33, 62, 0.8);
      border-color: rgba(255, 255, 255, 0.2);
      color: var(--text-light);
    }

    body.dark-mode .form-control:focus {
      border-color: #74b9ff;
      box-shadow: 0 0 0 0.25rem rgba(116, 185, 255, 0.15);
      background: rgba(22, 33, 62, 0.9);
    }

    .form-floating > label {
      padding-left: 1rem;
      color: #6c757d;
    }

    body.dark-mode .form-floating > label {
      color: #adb5bd;
    }

    /* Button Styles */
    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      border-radius: 12px;
      padding: 0.75rem 1.5rem;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
      transform: translateY(-1px);
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-success {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      border: none;
      border-radius: 12px;
      padding: 0.75rem 1.5rem;
    }

    .btn-info {
      background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
      border: none;
      border-radius: 12px;
      padding: 0.75rem 1.5rem;
    }

    .btn-warning {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
      border: none;
      border-radius: 12px;
      padding: 0.75rem 1.5rem;
    }

    .btn-lg {
      padding: 1rem 2rem;
      font-size: 1.1rem;
    }

    /* Alert Styles */
    .alert {
      border: none;
      border-radius: 12px;
      padding: 1.25rem 1.5rem;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .alert-success {
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);
      border-left: 4px solid #10b981;
      color: #059669;
    }

    .alert-danger {
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
      border-left: 4px solid #ef4444;
      color: #dc2626;
    }

    /* Enhanced Layout Improvements */
    .tab-content .card {
      margin-bottom: 2rem;
    }

    .row {
      margin-bottom: 1rem;
    }

    /* Mobile Responsiveness */
    @media (max-width: 991.98px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.show {
        transform: translateX(0);
      }

      .content {
        margin-left: 0;
        width: 100%;
        padding: 1rem;
      }

      .header {
        padding: 1rem;
      }

      .system-title {
        font-size: 1.5rem;
      }

      .profile-header {
        padding: 2rem 1.5rem;
        text-align: center;
      }

      .profile-avatar {
        width: 100px;
        height: 100px;
      }

      .stat-value {
        font-size: 2.5rem;
      }

      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
      }

      .card-body {
        padding: 1.5rem;
      }
    }

    @media (max-width: 600px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
        width: 240px;
      }
      
      .content {
        margin-left: 0;
        padding: 1rem;
      }
      
      .sidebar.show {
        transform: translateX(0);
      }
    }
  </style>
</head>
<body>
  <!-- Loading Overlay -->
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
        <h1>My Profile <span class="system-title">| CORE II </span></h1>
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

    <!-- Profile Header -->
    <div class="profile-header">
      <div class="row align-items-center">
        <div class="col-auto">
          <img src="<?php echo $provider['profile_picture'] ? htmlspecialchars($provider['profile_picture']) : 'https://via.placeholder.com/120'; ?>" 
               alt="Profile Picture" class="profile-avatar" id="profileImage">
        </div>
        <div class="col">
          <h2 class="mb-1"><?php echo htmlspecialchars($provider['name'] ?: $provider['username']); ?></h2>
          <p class="mb-1">Transport Provider</p>
          <p class="mb-1"><?php echo htmlspecialchars($provider['provider_type'] ?: 'General Transport'); ?></p>
          <small class="opacity-75">
            <i class="bi bi-calendar-event"></i> Member since <?php echo date('M Y', strtotime($provider['created_at'])); ?>
            <?php if ($provider['last_login']): ?>
            | <i class="bi bi-clock"></i> Last login: <?php echo date('M j, Y g:i A', strtotime($provider['last_login'])); ?>
            <?php endif; ?>
          </small>
        </div>
        <div class="col-auto">
          <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#profilePictureModal">
            <i class="bi bi-camera"></i> Change Photo
          </button>
        </div>
      </div>
    </div>

    <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="bi bi-exclamation-circle"></i> <?php echo $error_message; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Profile Statistics -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="card-body">
          <div class="stat-icon text-primary">
            <i class="bi bi-briefcase"></i>
          </div>
          <h3 class="stat-label">TOTAL SERVICES</h3>
          <div class="stat-value text-primary"><?php echo $stats['total_services']; ?></div>
          <div class="stat-subtitle">All time services</div>
        </div>
      </div>
      
      <div class="stat-card stat-card-success">
        <div class="card-body">
          <div class="stat-icon text-success">
            <i class="bi bi-check-circle"></i>
          </div>
          <h3 class="stat-label">COMPLETED SERVICES</h3>
          <div class="stat-value text-success"><?php echo $stats['completed_services']; ?></div>
          <div class="stat-subtitle">Successfully completed</div>
        </div>
      </div>
      
      <div class="stat-card stat-card-info">
        <div class="card-body">
          <div class="stat-icon text-info">
            <i class="bi bi-currency-dollar"></i>
          </div>
          <h3 class="stat-label">MONTHLY REVENUE</h3>
          <div class="stat-value text-info">₱<?php echo number_format($stats['total_revenue'], 0); ?></div>
          <div class="stat-subtitle">This month's earnings</div>
        </div>
      </div>
      
      <div class="stat-card stat-card-warning">
        <div class="card-body">
          <div class="stat-icon text-warning">
            <i class="bi bi-clock"></i>
          </div>
          <h3 class="stat-label">ACTIVE SERVICES</h3>
          <div class="stat-value text-warning"><?php echo $stats['active_services']; ?></div>
          <div class="stat-subtitle">Currently active</div>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills" id="profileTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" type="button">
          <i class="bi bi-person"></i> Profile Info
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button">
          <i class="bi bi-shield-lock"></i> Security
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="business-tab" data-bs-toggle="pill" data-bs-target="#business" type="button">
          <i class="bi bi-building"></i> Business Info
        </button>
      </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="profileTabContent">
      <!-- Profile Info Tab -->
      <div class="tab-pane fade show active" id="profile" role="tabpanel">
        <div class="card">
          <div class="card-header">
            <h5><i class="bi bi-pencil"></i> Edit Profile Information</h5>
          </div>
          <div class="card-body">
            <form method="POST">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-floating">
                    <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($provider['username']); ?>" readonly>
                    <label for="username">Username</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-floating">
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($provider['name'] ?: ''); ?>">
                    <label for="name">Full Name</label>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($provider['email']); ?>" required>
                    <label for="email">Email Address</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-floating">
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($provider['phone'] ?: ''); ?>">
                    <label for="phone">Phone Number</label>
                  </div>
                </div>
              </div>

              <div class="form-floating">
                <input type="text" class="form-control" id="service_area" name="service_area" value="<?php echo htmlspecialchars($provider['service_area'] ?: ''); ?>">
                <label for="service_area">Service Area</label>
              </div>

              <div class="form-floating">
                <textarea class="form-control" id="description" name="description" style="height: 120px"><?php echo htmlspecialchars($provider['description'] ?: ''); ?></textarea>
                <label for="description">Business Description</label>
              </div>

              <button type="submit" name="update_profile" class="btn btn-primary btn-lg">
                <i class="bi bi-check-circle"></i> Update Profile
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Security Tab -->
      <div class="tab-pane fade" id="security" role="tabpanel">
        <div class="card">
          <div class="card-header">
            <h5><i class="bi bi-key"></i> Change Password</h5>
          </div>
          <div class="card-body">
            <form method="POST">
              <div class="form-floating">
                <input type="password" class="form-control" id="current_password" name="current_password" required>
                <label for="current_password">Current Password</label>
              </div>

              <div class="form-floating">
                <input type="password" class="form-control" id="new_password" name="new_password" required>
                <label for="new_password">New Password</label>
              </div>

              <div class="form-floating">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                <label for="confirm_password">Confirm New Password</label>
              </div>

              <button type="submit" name="change_password" class="btn btn-warning btn-lg">
                <i class="bi bi-shield-check"></i> Change Password
              </button>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h5><i class="bi bi-shield"></i> Account Security</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="d-flex align-items-center mb-3">
                  <i class="bi bi-check-circle-fill text-success" style="font-size: 2rem;"></i>
                  <div class="ms-3">
                    <h6 class="mb-0">Account Status</h6>
                    <small class="text-muted">Active and verified</small>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="d-flex align-items-center mb-3">
                  <i class="bi bi-clock text-info" style="font-size: 2rem;"></i>
                  <div class="ms-3">
                    <h6 class="mb-0">Last Password Change</h6>
                    <small class="text-muted">Recent security update</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Business Info Tab -->
      <div class="tab-pane fade" id="business" role="tabpanel">
        <div class="card">
          <div class="card-header">
            <h5><i class="bi bi-building"></i> Business Information</h5>
          </div>
          <div class="card-body">
            <form method="POST">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-floating">
                    <select class="form-select" id="provider_type" name="provider_type">
                      <option value="">Select Provider Type</option>
                      <option value="Freight Transport" <?php echo $provider['provider_type'] === 'Freight Transport' ? 'selected' : ''; ?>>Freight Transport</option>
                      <option value="Passenger Transport" <?php echo $provider['provider_type'] === 'Passenger Transport' ? 'selected' : ''; ?>>Passenger Transport</option>
                      <option value="Logistics Service" <?php echo $provider['provider_type'] === 'Logistics Service' ? 'selected' : ''; ?>>Logistics Service</option>
                      <option value="Moving Service" <?php echo $provider['provider_type'] === 'Moving Service' ? 'selected' : ''; ?>>Moving Service</option>
                      <option value="Multi-Modal Transport" <?php echo $provider['provider_type'] === 'Multi-Modal Transport' ? 'selected' : ''; ?>>Multi-Modal Transport</option>
                    </select>
                    <label for="provider_type">Provider Type</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-floating">
                    <select class="form-select" id="experience" name="experience">
                      <option value="">Select Experience Level</option>
                      <option value="Less than 1 year" <?php echo $provider['experience'] === 'Less than 1 year' ? 'selected' : ''; ?>>Less than 1 year</option>
                      <option value="1-3 years" <?php echo $provider['experience'] === '1-3 years' ? 'selected' : ''; ?>>1-3 years</option>
                      <option value="3-5 years" <?php echo $provider['experience'] === '3-5 years' ? 'selected' : ''; ?>>3-5 years</option>
                      <option value="5-10 years" <?php echo $provider['experience'] === '5-10 years' ? 'selected' : ''; ?>>5-10 years</option>
                      <option value="More than 10 years" <?php echo $provider['experience'] === 'More than 10 years' ? 'selected' : ''; ?>>More than 10 years</option>
                    </select>
                    <label for="experience">Years of Experience</label>
                  </div>
                </div>
              </div>

              <button type="submit" name="update_profile" class="btn btn-primary btn-lg">
                <i class="bi bi-check-circle"></i> Update Business Info
              </button>
            </form>
          </div>
        </div>

        <!-- Business Statistics -->
        <div class="card">
          <div class="card-header">
            <h5><i class="bi bi-graph-up"></i> Business Performance</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <div class="text-center p-3 bg-light rounded">
                  <h4 class="text-primary"><?php echo $stats['vehicle_count']; ?></h4>
                  <small>Total Vehicles</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="text-center p-3 bg-light rounded">
                  <h4 class="text-success"><?php echo $stats['driver_count']; ?></h4>
                  <small>Total Drivers</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="text-center p-3 bg-light rounded">
                  <h4 class="text-info"><?php echo $stats['completed_services'] > 0 ? number_format(($stats['completed_services'] / $stats['total_services']) * 100, 1) : '0'; ?>%</h4>
                  <small>Completion Rate</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Profile Picture Modal -->
  <div class="modal fade" id="profilePictureModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Change Profile Picture</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <p>Profile picture upload functionality would be implemented here.</p>
          <p class="text-muted">This would typically include file upload, image cropping, and storage functionality.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary">Upload Picture</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Universal Logout SweetAlert -->
  <script src="includes/logout-sweetalert.js"></script>
  
  <script>
    // Initialize the application
    document.addEventListener('DOMContentLoaded', function() {
      initializeEventListeners();
      applyStoredTheme();
      showLoadingScreen();
    });

    function showLoadingScreen() {
      const loadingOverlay = document.getElementById('loadingOverlay');
      loadingOverlay.classList.add('show');
      
      // Hide loading overlay after initialization
      setTimeout(() => {
        loadingOverlay.classList.remove('show');
      }, 1000);
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

      // Form validation
      const forms = document.querySelectorAll('form');
      forms.forEach(form => {
        form.addEventListener('submit', function(e) {
          if (this.querySelector('input[name="change_password"]')) {
            const newPassword = this.querySelector('input[name="new_password"]').value;
            const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
            
            if (newPassword !== confirmPassword) {
              e.preventDefault();
              Swal.fire({
                icon: 'error',
                title: 'Password Mismatch',
                text: 'New passwords do not match!',
                confirmButtonColor: '#667eea'
              });
            }
          }
        });
      });

      // Enhanced card hover effects
      const cards = document.querySelectorAll('.card, .stat-card');
      cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
          this.style.transform = 'translateY(-4px)';
        });
        
        card.addEventListener('mouseleave', function() {
          this.style.transform = 'translateY(0)';
        });
      });

      // Profile picture hover effect
      const profileAvatar = document.getElementById('profileImage');
      if (profileAvatar) {
        profileAvatar.addEventListener('mouseenter', function() {
          this.style.transform = 'scale(1.1)';
        });
        
        profileAvatar.addEventListener('mouseleave', function() {
          this.style.transform = 'scale(1)';
        });
      }
    }

    function applyStoredTheme() {
      const stored = localStorage.getItem('theme');
      const isDark = stored === 'dark';
      document.body.classList.toggle('dark-mode', isDark);
      const toggle = document.getElementById('themeToggle');
      if (toggle) toggle.checked = isDark;
    }

    // Password strength indicator
    document.getElementById('new_password')?.addEventListener('input', function() {
      const password = this.value;
      let strength = 0;
      
      if (password.length >= 8) strength++;
      if (password.match(/[a-z]/)) strength++;
      if (password.match(/[A-Z]/)) strength++;
      if (password.match(/[0-9]/)) strength++;
      if (password.match(/[^a-zA-Z0-9]/)) strength++;
      
      // Visual strength indicator could be added here
      console.log('Password strength:', strength);
    });

    // Success/Error message auto-dismiss
    setTimeout(() => {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        if (alert.classList.contains('show')) {
          alert.classList.remove('show');
          alert.classList.add('fade');
        }
      });
    }, 5000);
  </script>
</body>
</html>
