<?php
/**
 * Dashboard Summary API
 * CORE II - Admin Dashboard Module
 */

// Enable CORS and set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Start session and check authentication
session_start();

// Define module constant
define('DASHBOARD_MODULE', true);

// Include required files
require_once '../../../auth.php';
require_once '../../../db.php';
require_once '../dashboard-config.php';
require_once '../dashboard-functions.php';

// Check authentication
if (!isAdmin()) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'error' => 'Unauthorized access',
        'message' => 'Admin authentication required'
    ]);
    exit();
}

try {
    // Get dashboard configuration
    $config = include '../dashboard-config.php';
    
    // Validate configuration
    validateDashboardConfig($config);
    
    // Get request parameters
    $period = $_GET['period'] ?? 'week';
    $includeTimeSeries = isset($_GET['timeSeries']) ? filter_var($_GET['timeSeries'], FILTER_VALIDATE_BOOLEAN) : true;
    
    // Get dashboard summary data
    $summaryData = getDashboardSummary();
    
    if ($summaryData['status'] === 'error') {
        // Log the error but provide fallback data
        error_log("Dashboard API Error: " . $summaryData['error']);
        $summaryData = getFallbackDashboardData();
    }
    
    // Get time series data if requested
    if ($includeTimeSeries) {
        $timeSeries = getTimeSeriesData($period);
        if (empty($timeSeries)) {
            $timeSeries = generateSampleTimeSeriesData($period);
        }
        $summaryData['timeSeries'] = $timeSeries;
    }
    
    // Get system health
    $systemHealth = getSystemHealth();
    $summaryData['systemHealth'] = $systemHealth;
    
    // Format data for frontend
    $formattedData = formatDashboardData($summaryData);
    
    // Add metadata
    $formattedData['meta'] = [
        'version' => DASHBOARD_VERSION,
        'generatedAt' => date('c'),
        'period' => $period,
        'includeTimeSeries' => $includeTimeSeries,
        'dataSource' => $summaryData['status'] === 'success' ? 'database' : 'fallback'
    ];
    
    // Add configuration info
    $formattedData['config'] = [
        'modules' => $config['modules'],
        'chartColors' => $config['dashboard']['chart_settings']['colors'],
        'features' => $config['dashboard']['features']
    ];
    
    // Log successful request
    logDashboardActivity('dashboard_summary_requested', ['period' => $period]);
    
    // Return successful response
    echo json_encode($formattedData, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Dashboard Summary API Exception: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Internal server error',
        'message' => 'Failed to generate dashboard summary',
        'fallbackData' => getFallbackDashboardData()
    ]);
}
?>
