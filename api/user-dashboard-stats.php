<?php
require_once '../db.php';

// For development, temporarily disable authentication
// require_once '../auth.php';
// requireUser();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Mock user ID - in production this would come from session
    $userId = $_SESSION['user_id'] ?? 1;
    
    // Get user statistics
    $userStats = [
        'activeRequests' => 3,
        'completedServices' => 15,
        'totalSpent' => 12500,
        'satisfactionRate' => 95,
        'pendingRequests' => 2,
        'processingRequests' => 1,
        'cancelledRequests' => 1
    ];
    
    // Generate service usage data for chart (mock data)
    $serviceUsage = [
        'labels' => ['Freight Services', 'Express Delivery', 'Warehouse', 'Logistics'],
        'data' => [8, 4, 2, 1],
        'backgroundColor' => [
            'rgba(102, 126, 234, 0.8)',
            'rgba(28, 200, 138, 0.8)',
            'rgba(246, 194, 62, 0.8)',
            'rgba(231, 74, 59, 0.8)'
        ],
        'borderColor' => [
            'rgb(102, 126, 234)',
            'rgb(28, 200, 138)',
            'rgb(246, 194, 62)',
            'rgb(231, 74, 59)'
        ]
    ];
    
    // Generate time series data for activity chart
    $activityData = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayName = date('D', strtotime("-$i days"));
        
        $activityData[] = [
            'date' => $date,
            'day' => $dayName,
            'requests' => rand(0, 3),
            'spending' => rand(500, 3000),
            'services' => rand(0, 2)
        ];
    }
    
    // Recent activities
    $recentActivities = [
        [
            'id' => 'REQ-2024-001',
            'type' => 'request',
            'title' => 'Freight Transport Request',
            'description' => 'Manila to Cebu freight service',
            'status' => 'pending',
            'date' => date('Y-m-d H:i:s'),
            'amount' => '₱25,000'
        ],
        [
            'id' => 'REQ-2024-002',
            'type' => 'service',
            'title' => 'Express Delivery Completed',
            'description' => 'Document delivery to Davao',
            'status' => 'completed',
            'date' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'amount' => '₱1,250'
        ],
        [
            'id' => 'REQ-2024-003',
            'type' => 'payment',
            'title' => 'Payment Processed',
            'description' => 'Warehouse storage payment',
            'status' => 'completed',
            'date' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'amount' => '₱18,000'
        ]
    ];
    
    // Service providers recommendations
    $recommendations = [
        [
            'id' => 'SP-001',
            'name' => 'Manila Freight Express',
            'rating' => 4.8,
            'services' => ['Freight', 'Express'],
            'price_range' => '₱500-₱50,000',
            'location' => 'Metro Manila'
        ],
        [
            'id' => 'SP-002',
            'name' => 'Cebu Logistics Hub',
            'rating' => 4.6,
            'services' => ['Warehouse', 'Logistics'],
            'price_range' => '₱1,000-₱25,000',
            'location' => 'Cebu City'
        ]
    ];
    
    $response = [
        'status' => 'success',
        'data' => [
            'stats' => $userStats,
            'serviceUsage' => $serviceUsage,
            'activityData' => $activityData,
            'recentActivities' => $recentActivities,
            'recommendations' => $recommendations
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
