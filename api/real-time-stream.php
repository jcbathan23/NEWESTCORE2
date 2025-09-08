<?php
require_once '../auth.php';
require_once '../db.php';
requireAdmin();

// Set headers for Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Cache-Control');

// Prevent PHP from buffering output
if (ob_get_level()) ob_end_clean();

// Function to send SSE data
function sendSSE($id, $data, $event = 'update') {
    echo "id: $id\n";
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

// Function to generate random movement for vehicles
function updateVehiclePositions($vehicles) {
    foreach ($vehicles as &$vehicle) {
        if ($vehicle['tracking_status'] === 'Active') {
            // Small random movement (simulate real-time tracking)
            $latOffset = (rand(-50, 50) / 100000); // Very small movement
            $lngOffset = (rand(-50, 50) / 100000);
            
            $vehicle['latitude'] += $latOffset;
            $vehicle['longitude'] += $lngOffset;
            
            // Update speed randomly
            $vehicle['speed'] = max(0, min(60, $vehicle['speed'] + rand(-5, 5)));
            
            // Update heading occasionally
            if (rand(1, 10) <= 3) { // 30% chance to change heading
                $vehicle['heading'] = ($vehicle['heading'] + rand(-30, 30) + 360) % 360;
            }
            
            $vehicle['last_update'] = date('Y-m-d H:i:s');
        }
    }
    return $vehicles;
}

// Function to simulate incidents/alerts
function generateRandomAlert() {
    $alertTypes = ['traffic', 'maintenance', 'incident', 'weather'];
    $severities = ['low', 'medium', 'high'];
    $messages = [
        'traffic' => ['Heavy traffic detected', 'Traffic congestion on route', 'Road blocked ahead'],
        'maintenance' => ['Vehicle requires maintenance', 'Scheduled maintenance due', 'Engine check needed'],
        'incident' => ['Minor accident reported', 'Road closure ahead', 'Emergency services on scene'],
        'weather' => ['Heavy rain warning', 'Flooding risk', 'Poor visibility conditions']
    ];
    
    $type = $alertTypes[array_rand($alertTypes)];
    $severity = $severities[array_rand($severities)];
    
    return [
        'id' => 'A-' . time() . '-' . rand(100, 999),
        'type' => $type,
        'severity' => $severity,
        'message' => $messages[$type][array_rand($messages[$type])],
        'location' => [
            14.5995 + (rand(-500, 500) / 10000),
            120.9842 + (rand(-500, 500) / 10000)
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

// Initialize data
$lastUpdateTime = time();
$vehicles = [];
$alerts = [];
$eventId = 1;

// Get initial vehicle data
try {
    $response = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . '/NEWCOREll/api/real-time-tracking.php?type=vehicles');
    $vehicles = json_decode($response, true) ?: [];
} catch (Exception $e) {
    // Use fallback data if API fails
    $vehicles = [];
}

// Send initial data
sendSSE($eventId++, [
    'type' => 'init',
    'vehicles' => $vehicles,
    'alerts' => $alerts,
    'timestamp' => date('Y-m-d H:i:s')
]);

// Keep connection alive and send updates
$maxRunTime = 300; // Run for 5 minutes max
$startTime = time();

while (connection_status() == CONNECTION_NORMAL && (time() - $startTime) < $maxRunTime) {
    $currentTime = time();
    
    // Send vehicle updates every 5 seconds
    if ($currentTime - $lastUpdateTime >= 5) {
        $vehicles = updateVehiclePositions($vehicles);
        
        // Randomly generate alerts (10% chance every update)
        if (rand(1, 100) <= 10) {
            $newAlert = generateRandomAlert();
            $alerts[] = $newAlert;
            
            // Keep only last 5 alerts
            if (count($alerts) > 5) {
                array_shift($alerts);
            }
            
            // Send alert immediately
            sendSSE($eventId++, [
                'type' => 'alert',
                'alert' => $newAlert,
                'timestamp' => date('Y-m-d H:i:s')
            ], 'alert');
        }
        
        // Send vehicle position updates
        sendSSE($eventId++, [
            'type' => 'vehicles',
            'vehicles' => $vehicles,
            'timestamp' => date('Y-m-d H:i:s')
        ], 'vehicles');
        
        $lastUpdateTime = $currentTime;
    }
    
    // Send heartbeat every 30 seconds
    if ($currentTime % 30 == 0) {
        sendSSE($eventId++, [
            'type' => 'heartbeat',
            'timestamp' => date('Y-m-d H:i:s'),
            'active_vehicles' => count(array_filter($vehicles, function($v) {
                return $v['tracking_status'] === 'Active';
            }))
        ], 'heartbeat');
    }
    
    sleep(1); // Wait 1 second before next iteration
}

// Connection closed or timeout reached
sendSSE($eventId++, [
    'type' => 'close',
    'message' => 'Stream ended',
    'timestamp' => date('Y-m-d H:i:s')
], 'close');
?>
