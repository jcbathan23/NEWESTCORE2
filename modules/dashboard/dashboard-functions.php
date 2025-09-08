<?php
/**
 * Dashboard Module Functions
 * CORE II - Admin Dashboard Module
 */

// Prevent direct access
if (!defined('DASHBOARD_MODULE') && !isset($_SESSION['role'])) {
    die('Unauthorized access');
}

/**
 * Get dashboard summary data
 */
function getDashboardSummary() {
    global $mysqli;
    
    try {
        $summary = [];
        $growth = [];
        
        // Get modules data with error handling
        $modules = [
            'providers' => [
                'table' => 'providers', 
                'name_field' => 'name',
                'page' => '../../service-provider.php',
                'status_field' => 'status',
                'active_values' => ['Active', 'active']
            ],
            'routes' => [
                'table' => 'routes', 
                'name_field' => 'name',
                'page' => '../../schedules.php',
                'status_field' => 'status',
                'active_values' => ['Active', 'active']
            ],
            'schedules' => [
                'table' => 'schedules', 
                'name_field' => 'name',
                'page' => '../../schedules.php',
                'status_field' => 'status',
                'active_values' => ['Active', 'active']
            ],
            'service_points' => [
                'table' => 'service_points', 
                'name_field' => 'name',
                'page' => '../../service-network.php',
                'status_field' => 'status',
                'active_values' => ['Active', 'active']
            ],
            'sops' => [
                'table' => 'sops', 
                'name_field' => 'title',
                'page' => '../../sop-manager.php',
                'status_field' => 'status',
                'active_values' => ['Active', 'active']
            ],
            'tariffs' => [
                'table' => 'tariffs', 
                'name_field' => 'name',
                'page' => '../../rate-tariff.php',
                'status_field' => 'status',
                'active_values' => ['Active', 'active']
            ]
        ];
        
        foreach ($modules as $module => $config) {
            $tableName = $config['table'];
            $nameField = $config['name_field'];
            
            // Check if table exists
            $checkTableResult = $mysqli->query("SHOW TABLES LIKE '{$tableName}'");
            
            if ($checkTableResult && $checkTableResult->num_rows > 0) {
                // Get total count
                $stmt = $mysqli->query("SELECT COUNT(*) as total FROM `{$tableName}`");
                $row = $stmt->fetch_assoc();
                $total = $row['total'];
                
            // Get active count based on status field
            $activeCount = $total;
            if (isset($config['status_field']) && isset($config['active_values'])) {
                $statusField = $config['status_field'];
                $activeValues = $config['active_values'];
                
                // Check if status column exists
                $checkStatusResult = $mysqli->query("SHOW COLUMNS FROM `{$tableName}` LIKE '{$statusField}'");
                
                if ($checkStatusResult && $checkStatusResult->num_rows > 0) {
                    // Build WHERE clause for active values
                    $activeConditions = [];
                    foreach ($activeValues as $value) {
                        $activeConditions[] = "`{$statusField}` = '{$value}'";
                    }
                    $activeWhereClause = implode(' OR ', $activeConditions);
                    
                    $activeStmt = $mysqli->query("SELECT COUNT(*) as active FROM `{$tableName}` WHERE {$activeWhereClause}");
                    $activeRow = $activeStmt->fetch_assoc();
                    $activeCount = $activeRow['active'];
                }
            }
                
                // Get recent additions (last 7 days)
                $recentCount = 0;
                $checkCreatedResult = $mysqli->query("SHOW COLUMNS FROM `{$tableName}` LIKE 'created_at'");
                
                if ($checkCreatedResult && $checkCreatedResult->num_rows > 0) {
                    $recentStmt = $mysqli->query("SELECT COUNT(*) as recent FROM `{$tableName}` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                    $recentRow = $recentStmt->fetch_assoc();
                    $recentCount = $recentRow['recent'];
                }
                
                $summary[$module] = [
                    'total' => (int)$total,
                    'active' => (int)$activeCount,
                    'recent' => (int)$recentCount,
                    'percentage' => $total > 0 ? round(($activeCount / $total) * 100, 2) : 0,
                    'page' => $config['page']
                ];
                
                // Calculate growth (compare with last month)
                $growth[$module] = calculateGrowth($mysqli, $tableName, $total);
            } else {
                // Table doesn't exist, provide default data
                $summary[$module] = [
                    'total' => 0,
                    'active' => 0,
                    'recent' => 0,
                    'percentage' => 0,
                    'page' => $config['page']
                ];
                $growth[$module] = 0;
            }
        }
        
        return [
            'status' => 'success',
            'summary' => $summary,
            'growth' => $growth,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        error_log("Dashboard summary error: " . $e->getMessage());
        return [
            'status' => 'error',
            'error' => 'Failed to load dashboard data',
            'details' => $e->getMessage()
        ];
    }
}

/**
 * Calculate growth percentage
 */
function calculateGrowth($mysqli, $tableName, $currentTotal) {
    try {
        // Check if created_at column exists
        $checkCreatedResult = $mysqli->query("SHOW COLUMNS FROM `{$tableName}` LIKE 'created_at'");
        
        if ($checkCreatedResult && $checkCreatedResult->num_rows > 0) {
            // Get count from last month
            $stmt = $mysqli->query("SELECT COUNT(*) as last_month FROM `{$tableName}` WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
            $row = $stmt->fetch_assoc();
            $lastMonthTotal = $row['last_month'];
            
            if ($lastMonthTotal > 0) {
                return round((($currentTotal - $lastMonthTotal) / $lastMonthTotal) * 100, 2);
            }
        }
        
        return 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get time series data for analytics chart
 */
function getTimeSeriesData($period = 'week') {
    global $mysqli;
    
    try {
        $timeSeries = [];
        $days = ($period === 'month') ? 30 : 7;
        
        // Get data for each module over the specified period
        $modules = ['providers', 'routes', 'schedules', 'service_points', 'sops', 'tariffs'];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dateLabel = date('M j', strtotime("-{$i} days"));
            
            $dayData = [
                'date' => $date,
                'label' => $dateLabel,
                'total' => 0
            ];
            
            foreach ($modules as $module) {
                $tableName = $module;
                
                // Check if table exists
                $checkTableResult = $mysqli->query("SHOW TABLES LIKE '{$tableName}'");
                
                if ($checkTableResult && $checkTableResult->num_rows > 0) {
                    // Check if created_at column exists
                    $checkCreatedResult = $mysqli->query("SHOW COLUMNS FROM `{$tableName}` LIKE 'created_at'");
                    
                    if ($checkCreatedResult && $checkCreatedResult->num_rows > 0) {
                        $stmt = $mysqli->query("SELECT COUNT(*) as count FROM `{$tableName}` WHERE DATE(created_at) <= '{$date}'");
                        $row = $stmt->fetch_assoc();
                        $count = $row['count'];
                        $dayData[$module] = (int)$count;
                        $dayData['total'] += (int)$count;
                    } else {
                        $dayData[$module] = 0;
                    }
                } else {
                    $dayData[$module] = 0;
                }
            }
            
            $timeSeries[] = $dayData;
        }
        
        return $timeSeries;
        
    } catch (Exception $e) {
        error_log("Time series data error: " . $e->getMessage());
        return generateSampleTimeSeriesData($period);
    }
}

/**
 * Generate sample time series data when database is not available
 */
function generateSampleTimeSeriesData($period = 'week') {
    $timeSeries = [];
    $days = ($period === 'month') ? 30 : 7;
    
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $dateLabel = date('M j', strtotime("-{$i} days"));
        
        // Generate realistic sample data
        $baseCount = 50 + ($days - $i) * 2;
        
        $dayData = [
            'date' => $date,
            'label' => $dateLabel,
            'providers' => $baseCount + rand(-5, 15),
            'routes' => $baseCount + rand(-10, 20),
            'schedules' => $baseCount + rand(-8, 18),
            'service_points' => $baseCount + rand(-12, 25),
            'sops' => $baseCount + rand(-5, 12),
            'tariffs' => $baseCount + rand(-3, 8),
            'total' => 0
        ];
        
        // Calculate total
        $dayData['total'] = $dayData['providers'] + $dayData['routes'] + $dayData['schedules'] + 
                           $dayData['service_points'] + $dayData['sops'] + $dayData['tariffs'];
        
        $timeSeries[] = $dayData;
    }
    
    return $timeSeries;
}

/**
 * Get sample dashboard data when database connection fails
 */
function getFallbackDashboardData() {
    return [
        'status' => 'success',
        'summary' => [
            'providers' => ['total' => 25, 'active' => 23, 'recent' => 3, 'percentage' => 92, 'page' => '../../provider-dashboard.php'],
            'routes' => ['total' => 48, 'active' => 45, 'recent' => 5, 'percentage' => 94, 'page' => '../../schedules.php'],
            'schedules' => ['total' => 156, 'active' => 148, 'recent' => 12, 'percentage' => 95, 'page' => '../../schedules.php'],
            'service_points' => ['total' => 89, 'active' => 85, 'recent' => 7, 'percentage' => 96, 'page' => '../../service-network.php'],
            'sops' => ['total' => 34, 'active' => 32, 'recent' => 2, 'percentage' => 94, 'page' => '../../sop-manager.php'],
            'tariffs' => ['total' => 18, 'active' => 17, 'recent' => 1, 'percentage' => 94, 'page' => '../../rate-tariff.php']
        ],
        'growth' => [
            'providers' => 12.5,
            'routes' => 8.3,
            'schedules' => 15.2,
            'service_points' => 10.7,
            'sops' => 5.9,
            'tariffs' => 6.2
        ],
        'timeSeries' => generateSampleTimeSeriesData(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Format dashboard data for frontend display
 */
function formatDashboardData($data) {
    if ($data['status'] !== 'success' || !isset($data['summary'])) {
        return getFallbackDashboardData();
    }
    
    // Ensure all required modules exist
    $requiredModules = ['providers', 'routes', 'schedules', 'service_points', 'sops', 'tariffs'];
    
    foreach ($requiredModules as $module) {
        if (!isset($data['summary'][$module])) {
            $data['summary'][$module] = ['total' => 0, 'active' => 0, 'recent' => 0, 'percentage' => 0];
        }
        if (!isset($data['growth'][$module])) {
            $data['growth'][$module] = 0;
        }
    }
    
    // Add time series data if not present
    if (!isset($data['timeSeries']) || empty($data['timeSeries'])) {
        $data['timeSeries'] = generateSampleTimeSeriesData();
    }
    
    return $data;
}

/**
 * Validate dashboard configuration
 */
function validateDashboardConfig($config) {
    $required = ['dashboard', 'weather', 'modules'];
    
    foreach ($required as $key) {
        if (!isset($config[$key])) {
            throw new Exception("Missing required configuration: {$key}");
        }
    }
    
    return true;
}

/**
 * Get system health status
 */
function getSystemHealth() {
    global $mysqli;
    
    $health = [
        'database' => 'healthy',
        'memory' => 'healthy',
        'disk' => 'healthy',
        'overall' => 'healthy',
        'percentage' => 100
    ];
    
    try {
        // Test database connection
        if (!$mysqli || $mysqli->connect_error) {
            $health['database'] = 'warning';
            $health['percentage'] -= 30;
        }
        
        // Check memory usage (if available)
        if (function_exists('memory_get_usage')) {
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = ini_get('memory_limit');
            
            if ($memoryLimit) {
                $limitBytes = convertToBytes($memoryLimit);
                $usagePercentage = ($memoryUsage / $limitBytes) * 100;
                
                if ($usagePercentage > 80) {
                    $health['memory'] = 'warning';
                    $health['percentage'] -= 20;
                } elseif ($usagePercentage > 90) {
                    $health['memory'] = 'critical';
                    $health['percentage'] -= 35;
                }
            }
        }
        
        // Check disk space (if available)
        if (function_exists('disk_free_space')) {
            $diskFree = disk_free_space('.');
            $diskTotal = disk_total_space('.');
            
            if ($diskFree && $diskTotal) {
                $freePercentage = ($diskFree / $diskTotal) * 100;
                
                if ($freePercentage < 20) {
                    $health['disk'] = 'warning';
                    $health['percentage'] -= 15;
                } elseif ($freePercentage < 10) {
                    $health['disk'] = 'critical';
                    $health['percentage'] -= 25;
                }
            }
        }
        
        // Determine overall health
        if ($health['percentage'] < 70) {
            $health['overall'] = 'critical';
        } elseif ($health['percentage'] < 85) {
            $health['overall'] = 'warning';
        }
        
    } catch (Exception $e) {
        error_log("System health check error: " . $e->getMessage());
        $health['overall'] = 'warning';
        $health['percentage'] = 75;
    }
    
    return $health;
}

/**
 * Convert memory limit string to bytes
 */
function convertToBytes($size) {
    $unit = strtolower(substr($size, -1));
    $value = (int)$size;
    
    switch ($unit) {
        case 'g':
            $value *= 1024;
        case 'm':
            $value *= 1024;
        case 'k':
            $value *= 1024;
    }
    
    return $value;
}

/**
 * Log dashboard activity
 */
function logDashboardActivity($action, $details = null) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => $_SESSION['username'] ?? 'unknown',
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    error_log("Dashboard Activity: " . json_encode($logEntry));
}

/**
 * Clean old dashboard logs (if implementing file-based logging)
 */
function cleanOldLogs($daysToKeep = 30) {
    // Implementation would depend on your logging strategy
    // This is a placeholder for log cleanup functionality
    return true;
}
?>
