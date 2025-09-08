<?php
/**
 * Dashboard Module Configuration
 * CORE II - Admin Dashboard Module
 */

// Prevent direct access
if (!defined('DASHBOARD_MODULE') && !isset($_SESSION['role'])) {
    die('Unauthorized access');
}

// Dashboard Module Configuration
define('DASHBOARD_VERSION', '2.0.0');
define('DASHBOARD_MODULE_PATH', __DIR__);
define('DASHBOARD_API_PATH', DASHBOARD_MODULE_PATH . '/api/');
define('DASHBOARD_ASSETS_PATH', DASHBOARD_MODULE_PATH . '/assets/');

// Dashboard Settings
$dashboardConfig = [
    'module_name' => 'Admin Dashboard',
    'version' => DASHBOARD_VERSION,
    'refresh_interval' => 300000, // 5 minutes in milliseconds
    'session_check_interval' => 30000, // 30 seconds
    'session_warning_time' => 300000, // 5 minutes before expiry
    'max_login_attempts' => 5,
    'lockout_duration' => 900, // 15 minutes
    'features' => [
        'user_management' => true,
        'analytics_chart' => true,
        'system_monitoring' => true,
        'weather_widget' => true,
        'security_info' => true,
        'real_time_updates' => true,
        'dark_mode' => true,
        'responsive_design' => true
    ],
    'chart_settings' => [
        'default_view' => 'week',
        'available_views' => ['week', 'month'],
        'colors' => [
            'primary' => '#667eea',
            'secondary' => '#764ba2',
            'success' => '#1cc88a',
            'info' => '#36b9cc',
            'warning' => '#f6c23e',
            'danger' => '#e74a3b'
        ]
    ],
    'security' => [
        'require_strong_passwords' => true,
        'min_password_length' => 6,
        'session_timeout' => 28800, // 8 hours
        'csrf_protection' => true,
        'xss_protection' => true
    ]
];

// Weather API Configuration (if using external weather API)
$weatherConfig = [
    'enabled' => true,
    'default_location' => 'Manila, Philippines',
    'api_endpoint' => '', // Add your weather API endpoint if needed
    'cache_duration' => 1800 // 30 minutes
];

// Dashboard Modules Summary Configuration
$modulesConfig = [
    'providers' => [
        'name' => 'Service Providers',
        'icon' => 'bi-building',
        'color' => 'primary',
        'description' => 'Transportation service providers'
    ],
    'routes' => [
        'name' => 'Routes',
        'icon' => 'bi-diagram-3',
        'color' => 'info',
        'description' => 'Transportation routes'
    ],
    'schedules' => [
        'name' => 'Schedules',
        'icon' => 'bi-calendar-event',
        'color' => 'warning',
        'description' => 'Service schedules'
    ],
    'service_points' => [
        'name' => 'Service Points',
        'icon' => 'bi-geo-alt',
        'color' => 'success',
        'description' => 'Service locations'
    ],
    'sops' => [
        'name' => 'SOPs',
        'icon' => 'bi-list-check',
        'color' => 'secondary',
        'description' => 'Standard Operating Procedures'
    ],
    'tariffs' => [
        'name' => 'Tariffs',
        'icon' => 'peso-icon',
        'color' => 'danger',
        'description' => 'Pricing and tariffs'
    ]
];

// Export configurations for use in other files
return [
    'dashboard' => $dashboardConfig,
    'weather' => $weatherConfig,
    'modules' => $modulesConfig
];
?>
