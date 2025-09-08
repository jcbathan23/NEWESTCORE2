<?php
/**
 * Provider Dashboard Functions
 * CORE II - Provider-specific dashboard data retrieval
 */

// Prevent direct access
if (!defined('PROVIDER_DASHBOARD') && !isset($_SESSION['role'])) {
    die('Unauthorized access');
}

/**
 * Get provider dashboard statistics based on available modules
 */
function getProviderDashboardStats($provider_id) {
    global $mysqli;
    
    $stats = [];
    
    try {
        // Check if provider exists in providers table (they might be linked)
        $providerInfo = null;
        $providerCheck = $mysqli->query("SHOW TABLES LIKE 'providers'");
        if ($providerCheck && $providerCheck->num_rows > 0) {
            // Check if we can find this user as a provider
            $result = $mysqli->query("SELECT * FROM providers WHERE contact_email = (SELECT email FROM users WHERE id = $provider_id) LIMIT 1");
            if ($result && $result->num_rows > 0) {
                $providerInfo = $result->fetch_assoc();
            }
        }
        
        // ROUTES assigned to provider
        $routesCheck = $mysqli->query("SHOW TABLES LIKE 'routes'");
        if ($routesCheck && $routesCheck->num_rows > 0) {
            // Check if routes table has provider_id column
            $hasProviderColumn = $mysqli->query("SHOW COLUMNS FROM routes LIKE 'provider_id'");
            if ($hasProviderColumn && $hasProviderColumn->num_rows > 0) {
                $result = $mysqli->query("SELECT COUNT(*) as count FROM routes WHERE provider_id = $provider_id");
                $stats['total_routes'] = ($result && $row = $result->fetch_assoc()) ? $row['count'] : 0;
                
                $result = $mysqli->query("SELECT COUNT(*) as count FROM routes WHERE provider_id = $provider_id AND status IN ('Active', 'active')");
                $stats['active_routes'] = ($result && $row = $result->fetch_assoc()) ? $row['count'] : 0;
            } else {
                // Get total routes in system (provider might manage all or specific ones)
                $result = $mysqli->query("SELECT COUNT(*) as count FROM routes");
                $stats['total_routes'] = ($result && $row = $result->fetch_assoc()) ? $row['count'] : 0;
                
                $result = $mysqli->query("SELECT COUNT(*) as count FROM routes WHERE status IN ('Active', 'active')");
                $stats['active_routes'] = ($result && $row = $result->fetch_assoc()) ? $row['count'] : 0;
            }
        } else {
            $stats['total_routes'] = 0;
            $stats['active_routes'] = 0;
        }
        
        // SCHEDULES managed by provider
        $schedulesCheck = $mysqli->query("SHOW TABLES LIKE 'schedules'");
        if ($schedulesCheck && $schedulesCheck->num_rows > 0) {
            // Check if schedules table has provider_id column
            $hasProviderColumn = $mysqli->query("SHOW COLUMNS FROM schedules LIKE 'provider_id'");
            if ($hasProviderColumn && $hasProviderColumn->num_rows > 0) {
                $result = $mysqli->query("SELECT COUNT(*) as count FROM schedules WHERE provider_id = $provider_id");
                $stats['total_schedules'] = $result ? $result->fetch_assoc()['count'] : 0;
                
                $result = $mysqli->query("SELECT COUNT(*) as count FROM schedules WHERE provider_id = $provider_id AND status IN ('Active', 'active')");
                $stats['active_schedules'] = $result ? $result->fetch_assoc()['count'] : 0;
            } else {
                // Get schedules that might be related to provider's routes or all schedules
                $result = $mysqli->query("SELECT COUNT(*) as count FROM schedules");
                $stats['total_schedules'] = $result ? $result->fetch_assoc()['count'] : 0;
                
                $result = $mysqli->query("SELECT COUNT(*) as count FROM schedules WHERE status IN ('Active', 'active')");
                $stats['active_schedules'] = $result ? $result->fetch_assoc()['count'] : 0;
            }
        } else {
            $stats['total_schedules'] = 0;
            $stats['active_schedules'] = 0;
        }
        
        // SERVICE POINTS in provider's area
        $servicePointsCheck = $mysqli->query("SHOW TABLES LIKE 'service_points'");
        if ($servicePointsCheck && $servicePointsCheck->num_rows > 0) {
            // Check if service_points table has provider_id column
            $hasProviderColumn = $mysqli->query("SHOW COLUMNS FROM service_points LIKE 'provider_id'");
            if ($hasProviderColumn && $hasProviderColumn->num_rows > 0) {
                $result = $mysqli->query("SELECT COUNT(*) as count FROM service_points WHERE provider_id = $provider_id");
                $stats['total_service_points'] = $result ? $result->fetch_assoc()['count'] : 0;
                
                $result = $mysqli->query("SELECT COUNT(*) as count FROM service_points WHERE provider_id = $provider_id AND status IN ('Active', 'active')");
                $stats['active_service_points'] = $result ? $result->fetch_assoc()['count'] : 0;
            } else {
                // If no provider_id, show service points that might be in their service area
                if ($providerInfo && !empty($providerInfo['service_area'])) {
                    $serviceArea = $mysqli->real_escape_string($providerInfo['service_area']);
                    $result = $mysqli->query("SELECT COUNT(*) as count FROM service_points WHERE location LIKE '%$serviceArea%'");
                    $stats['total_service_points'] = $result ? $result->fetch_assoc()['count'] : 0;
                    
                    $result = $mysqli->query("SELECT COUNT(*) as count FROM service_points WHERE location LIKE '%$serviceArea%' AND status IN ('Active', 'active')");
                    $stats['active_service_points'] = $result ? $result->fetch_assoc()['count'] : 0;
                } else {
                    $result = $mysqli->query("SELECT COUNT(*) as count FROM service_points");
                    $stats['total_service_points'] = $result ? $result->fetch_assoc()['count'] : 0;
                    
                    $result = $mysqli->query("SELECT COUNT(*) as count FROM service_points WHERE status IN ('Active', 'active')");
                    $stats['active_service_points'] = $result ? $result->fetch_assoc()['count'] : 0;
                }
            }
        } else {
            $stats['total_service_points'] = 0;
            $stats['active_service_points'] = 0;
        }
        
        // MONTHLY REVENUE - if provider info exists
        if ($providerInfo) {
            $stats['monthly_revenue'] = $providerInfo['monthly_rate'];
            $stats['contract_status'] = $providerInfo['status'];
            $stats['contract_end'] = $providerInfo['contract_end'];
        } else {
            $stats['monthly_revenue'] = 0;
            $stats['contract_status'] = 'Unknown';
            $stats['contract_end'] = null;
        }
        
        // TARIFFS relevant to provider
        $tariffsCheck = $mysqli->query("SHOW TABLES LIKE 'tariffs'");
        if ($tariffsCheck && $tariffsCheck->num_rows > 0) {
            $result = $mysqli->query("SELECT COUNT(*) as count FROM tariffs WHERE status IN ('Active', 'active')");
            $stats['active_tariffs'] = $result ? $result->fetch_assoc()['count'] : 0;
        } else {
            $stats['active_tariffs'] = 0;
        }
        
        // OPERATIONAL STATUS - calculate based on recent activity
        $stats['operational_days'] = calculateOperationalDays($provider_id);
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Provider dashboard stats error: " . $e->getMessage());
        return getDefaultProviderStats();
    }
}

/**
 * Calculate operational days based on schedule activity
 */
function calculateOperationalDays($provider_id) {
    global $mysqli;
    
    try {
        // Check if schedules exist and are active
        $schedulesCheck = $mysqli->query("SHOW TABLES LIKE 'schedules'");
        if ($schedulesCheck && $schedulesCheck->num_rows > 0) {
            $result = $mysqli->query("SELECT COUNT(*) as count FROM schedules WHERE status IN ('Active', 'active') AND start_date <= CURDATE() AND end_date >= CURDATE()");
            if ($result) {
                return $result->fetch_assoc()['count'];
            }
        }
        
        return 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get recent activity for provider
 */
function getProviderRecentActivity($provider_id, $limit = 10) {
    global $mysqli;
    
    $activities = [];
    
    try {
        // Get recent routes added/updated
        $routesCheck = $mysqli->query("SHOW TABLES LIKE 'routes'");
        if ($routesCheck && $routesCheck->num_rows > 0) {
            $hasProviderColumn = $mysqli->query("SHOW COLUMNS FROM routes LIKE 'provider_id'");
            if ($hasProviderColumn && $hasProviderColumn->num_rows > 0) {
                $result = $mysqli->query("SELECT 'route' as type, name, status, created_at, updated_at FROM routes WHERE provider_id = $provider_id ORDER BY updated_at DESC LIMIT 5");
            } else {
                $result = $mysqli->query("SELECT 'route' as type, name, status, created_at, updated_at FROM routes ORDER BY updated_at DESC LIMIT 5");
            }
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $activities[] = $row;
                }
            }
        }
        
        // Get recent schedules added/updated
        $schedulesCheck = $mysqli->query("SHOW TABLES LIKE 'schedules'");
        if ($schedulesCheck && $schedulesCheck->num_rows > 0) {
            $hasProviderColumn = $mysqli->query("SHOW COLUMNS FROM schedules LIKE 'provider_id'");
            if ($hasProviderColumn && $hasProviderColumn->num_rows > 0) {
                $result = $mysqli->query("SELECT 'schedule' as type, name, status, created_at, updated_at FROM schedules WHERE provider_id = $provider_id ORDER BY updated_at DESC LIMIT 5");
            } else {
                $result = $mysqli->query("SELECT 'schedule' as type, name, status, created_at, updated_at FROM schedules ORDER BY updated_at DESC LIMIT 5");
            }
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $activities[] = $row;
                }
            }
        }
        
        // Sort by updated_at and limit
        usort($activities, function($a, $b) {
            return strtotime($b['updated_at']) - strtotime($a['updated_at']);
        });
        
        return array_slice($activities, 0, $limit);
        
    } catch (Exception $e) {
        error_log("Provider recent activity error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get upcoming schedules for provider
 */
function getProviderUpcomingSchedules($provider_id, $limit = 5) {
    global $mysqli;
    
    try {
        $schedulesCheck = $mysqli->query("SHOW TABLES LIKE 'schedules'");
        if ($schedulesCheck && $schedulesCheck->num_rows > 0) {
            $hasProviderColumn = $mysqli->query("SHOW COLUMNS FROM schedules LIKE 'provider_id'");
            if ($hasProviderColumn && $hasProviderColumn->num_rows > 0) {
                $result = $mysqli->query("SELECT * FROM schedules WHERE provider_id = $provider_id AND start_date >= CURDATE() AND status IN ('Active', 'active') ORDER BY start_date ASC, departure ASC LIMIT $limit");
            } else {
                $result = $mysqli->query("SELECT * FROM schedules WHERE start_date >= CURDATE() AND status IN ('Active', 'active') ORDER BY start_date ASC, departure ASC LIMIT $limit");
            }
            
            $schedules = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $schedules[] = $row;
                }
            }
            return $schedules;
        }
        
        return [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get default provider stats when data is unavailable
 */
function getDefaultProviderStats() {
    return [
        'total_routes' => 0,
        'active_routes' => 0,
        'total_schedules' => 0,
        'active_schedules' => 0,
        'total_service_points' => 0,
        'active_service_points' => 0,
        'monthly_revenue' => 0,
        'contract_status' => 'Unknown',
        'contract_end' => null,
        'active_tariffs' => 0,
        'operational_days' => 0
    ];
}

/**
 * Get provider performance data for charts
 */
function getProviderPerformanceData($provider_id, $period = 'month') {
    global $mysqli;
    
    try {
        $performanceData = [];
        $days = ($period === 'month') ? 30 : 7;
        
        // Generate date range
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dateLabel = date('M j', strtotime("-{$i} days"));
            
            $dayData = [
                'date' => $date,
                'label' => $dateLabel,
                'routes' => 0,
                'schedules' => 0,
                'service_points' => 0
            ];
            
            // Get routes activity for this date
            $routesCheck = $mysqli->query("SHOW TABLES LIKE 'routes'");
            if ($routesCheck && $routesCheck->num_rows > 0) {
                $hasProviderColumn = $mysqli->query("SHOW COLUMNS FROM routes LIKE 'provider_id'");
                if ($hasProviderColumn && $hasProviderColumn->num_rows > 0) {
                    $result = $mysqli->query("SELECT COUNT(*) as count FROM routes WHERE provider_id = $provider_id AND DATE(created_at) <= '$date'");
                } else {
                    $result = $mysqli->query("SELECT COUNT(*) as count FROM routes WHERE DATE(created_at) <= '$date'");
                }
                if ($result) {
                    $dayData['routes'] = $result->fetch_assoc()['count'];
                }
            }
            
            // Get schedules activity for this date
            $schedulesCheck = $mysqli->query("SHOW TABLES LIKE 'schedules'");
            if ($schedulesCheck && $schedulesCheck->num_rows > 0) {
                $hasProviderColumn = $mysqli->query("SHOW COLUMNS FROM schedules LIKE 'provider_id'");
                if ($hasProviderColumn && $hasProviderColumn->num_rows > 0) {
                    $result = $mysqli->query("SELECT COUNT(*) as count FROM schedules WHERE provider_id = $provider_id AND DATE(created_at) <= '$date'");
                } else {
                    $result = $mysqli->query("SELECT COUNT(*) as count FROM schedules WHERE DATE(created_at) <= '$date'");
                }
                if ($result) {
                    $dayData['schedules'] = $result->fetch_assoc()['count'];
                }
            }
            
            $performanceData[] = $dayData;
        }
        
        return $performanceData;
        
    } catch (Exception $e) {
        error_log("Provider performance data error: " . $e->getMessage());
        return generateSampleProviderPerformanceData($period);
    }
}

/**
 * Generate sample performance data when database is unavailable
 */
function generateSampleProviderPerformanceData($period = 'month') {
    $performanceData = [];
    $days = ($period === 'month') ? 30 : 7;
    
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $dateLabel = date('M j', strtotime("-{$i} days"));
        
        $baseCount = 10 + ($days - $i);
        
        $performanceData[] = [
            'date' => $date,
            'label' => $dateLabel,
            'routes' => $baseCount + rand(0, 5),
            'schedules' => $baseCount + rand(2, 8),
            'service_points' => $baseCount + rand(1, 6)
        ];
    }
    
    return $performanceData;
}

/**
 * Check if provider has access to specific module
 */
function hasModuleAccess($provider_id, $module) {
    // For now, all providers have access to all modules
    // This can be expanded later with role-based permissions
    return true;
}
?>
