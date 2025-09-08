<?php
// Universal Sidebar Component
// This ensures consistent module arrangement across all admin modules

// Compute project base URL dynamically so links work from any subdirectory.
// Assumes this file lives in <docroot>/<project>/includes/sidebar.php
$docRootFs = realpath($_SERVER['DOCUMENT_ROOT']);
$projectRootFs = realpath(__DIR__ . '/..'); // one level up from includes/
$projectPath = '';
if ($docRootFs && $projectRootFs && strpos($projectRootFs, $docRootFs) === 0) {
  $projectPath = str_replace('\\', '/', substr($projectRootFs, strlen($docRootFs)));
}
$base = '/' . ltrim($projectPath, '/');
if ($base !== '/' && substr($base, -1) !== '/') {
  $base .= '/';
}
?>
<div class="sidebar" id="sidebar">
  <div class="logo">
    <img src="<?php echo $base; ?>slatelogo.png" alt="SLATE Logo">
  </div>
  <div class="system-name">CORE II</div>
  <div class="sidebar-nav">
    <!-- Dashboard - Always first -->
    <?php if ($isAdmin ?? false): ?>
      <div class="nav-item">
        <a href="<?php echo $base; ?>admin.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'admin.php') ? 'active' : ''; ?>">
          <i class="bi bi-speedometer2"></i>
          Dashboard
        </a>
      </div>
    <?php elseif ($isUser ?? false): ?>
      <div class="nav-item">
        <a href="<?php echo $base; ?>user-dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'user-dashboard.php') ? 'active' : ''; ?>">
          <i class="bi bi-speedometer2"></i>
          Dashboard
        </a>
      </div>
    <?php elseif ($isProvider ?? false): ?>
      <div class="nav-item">
        <a href="<?php echo $base; ?>provider-dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'provider-dashboard.php') ? 'active' : ''; ?>">
          <i class="bi bi-speedometer2"></i>
          Dashboard
        </a>
      </div>
    <?php else: ?>
      <div class="nav-item">
        <a href="<?php echo $base; ?>landpage.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'landpage.php') ? 'active' : ''; ?>">
          <i class="bi bi-speedometer2"></i>
          Dashboard
        </a>
      </div>
    <?php endif; ?>
    
    <!-- Admin modules -->
    <?php if ($isAdmin ?? false): ?>
      <!-- Service Provider - Always second for admin -->
      <div class="nav-item">
        <a href="<?php echo $base; ?>service-provider.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'service-provider.php') ? 'active' : ''; ?>">
          <i class="bi bi-people"></i>
          Service Provider
        </a>
      </div>
      
      <!-- Service Network & Route Planner - Always third for admin -->
      <div class="nav-item">
        <a href="<?php echo $base; ?>service-network.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'service-network.php') ? 'active' : ''; ?>">
          <i class="bi bi-diagram-3"></i>
          Service Network & Route Planner
        </a>
      </div>
      
      <!-- Schedules & Transit Timetable - Always fourth for admin -->
      <div class="nav-item">
        <a href="<?php echo $base; ?>schedules.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'schedules.php') ? 'active' : ''; ?>">
          <i class="bi bi-calendar-week"></i>
          Schedules & Transit Timetable
        </a>
      </div>
      
      <!-- Rate & Tariff - Admin only -->
      <div class="nav-item">
        <a href="<?php echo $base; ?>rate-tariff.php" class="nav-link admin-feature <?php echo (basename($_SERVER['PHP_SELF']) === 'rate-tariff.php') ? 'active' : ''; ?>">
          <span class="peso-icon">₱</span>
           Rate & Tariff
        </a>
      </div>
      
      <!-- SOP Manager - Admin only -->
      <div class="nav-item">
        <a href="<?php echo $base; ?>sop-manager.php" class="nav-link admin-feature <?php echo (basename($_SERVER['PHP_SELF']) === 'sop-manager.php') ? 'active' : ''; ?>">
          <i class="bi bi-journal-text"></i>
          SOP Manager
        </a>
      </div>
    <?php endif; ?>
    
    <!-- Provider modules - EXACT ORDER FROM IMAGE -->
    <?php if ($isProvider ?? false): ?>
      <!-- Service Network -->
      <div class="nav-item">
        <a href="<?php echo $base; ?>service-network.php" class="nav-link provider-feature <?php echo (basename($_SERVER['PHP_SELF']) === 'service-network.php') ? 'active' : ''; ?>">
          <i class="bi bi-diagram-3"></i>
          Service Network
        </a>
      </div>
      
      <!-- My Schedule -->
      <div class="nav-item">
        <a href="<?php echo $base; ?>schedules.php" class="nav-link provider-feature <?php echo (basename($_SERVER['PHP_SELF']) === 'schedules.php') ? 'active' : ''; ?>">
          <i class="bi bi-calendar-week"></i>
          My Schedule
        </a>
      </div>
      
      <!-- Profile Settings -->
      <div class="nav-item">
        <a href="<?php echo $base; ?>provider-profile.php" class="nav-link provider-feature <?php echo (basename($_SERVER['PHP_SELF']) === 'provider-profile.php') ? 'active' : ''; ?>">
          <i class="bi bi-person-circle"></i>
          Profile Settings
        </a>
      </div>
    <?php endif; ?>
    
    <!-- User modules -->
    <?php if ($isUser ?? false): ?>
      <!-- Rate & Tariff for users -->
      <div class="nav-item">
        <a href="<?php echo $base; ?>rate-tariff.php" class="nav-link user-feature <?php echo (basename($_SERVER['PHP_SELF']) === 'rate-tariff.php') ? 'active' : ''; ?>">
          <span class="peso-icon">₱</span>
          Rate & Tariff
        </a>
      </div>
      
      <!-- Transit Schedule for users -->
      <div class="nav-item">
        <a href="<?php echo $base; ?>user-schedules.php" class="nav-link user-feature <?php echo (basename($_SERVER['PHP_SELF']) === 'user-schedules.php') ? 'active' : ''; ?>">
          <i class="bi bi-calendar-week"></i>
          Transit Schedule
        </a>
      </div>
      
      <!-- Profile for users -->
      <div class="nav-item">
        <a href="<?php echo $base; ?>user-profile.php" class="nav-link user-feature <?php echo (basename($_SERVER['PHP_SELF']) === 'user-profile.php') ? 'active' : ''; ?>">
          <i class="bi bi-person-circle"></i>
          Profile
        </a>
      </div>
      
      <!-- Requests for users -->
      <div class="nav-item">
        <a href="<?php echo $base; ?>user-requests.php" class="nav-link user-feature <?php echo (basename($_SERVER['PHP_SELF']) === 'user-requests.php') ? 'active' : ''; ?>">
          <i class="bi bi-list-check"></i>
          Requests
        </a>
      </div>
    <?php endif; ?>
  </div>
  <div class="sidebar-footer">
    <a href="#" class="nav-link" onclick="confirmLogout(); return false;" title="Logout">
      <i class="bi bi-box-arrow-right"></i>
      Logout
    </a>
  </div>
</div>
