<?php
// Test script to verify module data accuracy
session_start();
require_once 'db.php';

echo "<h2>Module Data Verification</h2>";

// Module configurations
$modules = [
    'providers' => [
        'table' => 'providers',
        'page' => 'provider-dashboard.php',
        'status_field' => 'status'
    ],
    'routes' => [
        'table' => 'routes',
        'page' => 'schedules.php', 
        'status_field' => 'status'
    ],
    'schedules' => [
        'table' => 'schedules',
        'page' => 'schedules.php',
        'status_field' => 'status'
    ],
    'service_points' => [
        'table' => 'service_points',
        'page' => 'service-network.php',
        'status_field' => 'status'
    ],
    'sops' => [
        'table' => 'sops',
        'page' => 'sop-manager.php',
        'status_field' => 'status'
    ],
    'tariffs' => [
        'table' => 'tariffs',
        'page' => 'rate-tariff.php',
        'status_field' => 'status'
    ]
];

if ($mysqli && !$mysqli->connect_error) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Module</th><th>Table</th><th>Total Count</th><th>Active Count</th><th>Recent (7 days)</th><th>Page Link</th><th>Status</th></tr>";
    
    foreach ($modules as $module => $config) {
        $tableName = $config['table'];
        $pageLink = $config['page'];
        
        // Check if table exists
        $checkTable = $mysqli->query("SHOW TABLES LIKE '{$tableName}'");
        
        if ($checkTable && $checkTable->num_rows > 0) {
            // Get total count
            $totalResult = $mysqli->query("SELECT COUNT(*) as total FROM `{$tableName}`");
            $total = $totalResult ? $totalResult->fetch_assoc()['total'] : 0;
            
            // Get active count
            $activeResult = $mysqli->query("SELECT COUNT(*) as active FROM `{$tableName}` WHERE status = 'active' OR status = 'Active'");
            $active = $activeResult ? $activeResult->fetch_assoc()['active'] : 0;
            
            // Get recent count (if created_at exists)
            $recentCount = 0;
            $checkCreated = $mysqli->query("SHOW COLUMNS FROM `{$tableName}` LIKE 'created_at'");
            if ($checkCreated && $checkCreated->num_rows > 0) {
                $recentResult = $mysqli->query("SELECT COUNT(*) as recent FROM `{$tableName}` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                $recentCount = $recentResult ? $recentResult->fetch_assoc()['recent'] : 0;
            }
            
            $status = "✓ Exists";
            $fileExists = file_exists($pageLink) ? "✓ Exists" : "✗ Missing";
            
        } else {
            $total = 0;
            $active = 0; 
            $recentCount = 0;
            $status = "✗ Table Missing";
            $fileExists = file_exists($pageLink) ? "✓ Exists" : "✗ Missing";
        }
        
        echo "<tr>";
        echo "<td><strong>{$module}</strong></td>";
        echo "<td>{$tableName}</td>";
        echo "<td>{$total}</td>";
        echo "<td>{$active}</td>";
        echo "<td>{$recentCount}</td>";
        echo "<td><a href='{$pageLink}' target='_blank'>{$pageLink}</a> ({$fileExists})</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Test dashboard API
    echo "<h3>Dashboard API Test</h3>";
    echo "<p><a href='modules/dashboard/api/dashboard-summary.php' target='_blank'>Test Dashboard API</a></p>";
    
} else {
    echo "<p style='color: red;'>Database connection failed: " . ($mysqli->connect_error ?? 'Unknown error') . "</p>";
}

// Show sample insert queries for testing
echo "<h3>Sample Data Insert Queries (for testing)</h3>";
echo "<pre>";
echo "-- Insert sample providers\n";
echo "INSERT INTO providers (name, type, contact_person, contact_email, contact_phone, service_area, monthly_rate, status, contract_start, contract_end) VALUES\n";
echo "('Metro Express', 'Bus Service', 'John Doe', 'john@metroexpress.com', '123-456-7890', 'City Center', 5000.00, 'Active', '2024-01-01', '2025-01-01'),\n";
echo "('Swift Cargo', 'Freight', 'Jane Smith', 'jane@swiftcargo.com', '098-765-4321', 'Industrial Zone', 8000.00, 'Active', '2024-02-01', '2025-02-01');\n\n";

echo "-- Insert sample routes\n";
echo "INSERT INTO routes (name, type, start_point, end_point, distance, frequency, status, estimated_time) VALUES\n";
echo "('Route A1', 'Urban', 'City Center', 'Airport', 25.5, 'Every 30 minutes', 'Active', 45),\n";
echo "('Route B2', 'Express', 'Downtown', 'Suburb', 15.2, 'Every 15 minutes', 'Active', 25);\n\n";

echo "-- Insert sample schedules\n";
echo "INSERT INTO schedules (name, route, vehicle_type, departure, arrival, frequency, status, start_date, end_date, capacity) VALUES\n";
echo "('Morning Rush', 'Route A1', 'Bus', '07:00:00', '07:45:00', 'Daily', 'Active', '2024-01-01', '2024-12-31', 50),\n";
echo "('Evening Service', 'Route B2', 'Van', '17:30:00', '17:55:00', 'Weekdays', 'Active', '2024-01-01', '2024-12-31', 12);\n";
echo "</pre>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 20px 0; }
    th { background: #f0f0f0; padding: 10px; }
    td { padding: 8px; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>
