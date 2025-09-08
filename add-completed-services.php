<?php
session_start();
require_once 'db.php';

// Check if user is provider
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'provider';
    echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0; border-radius: 5px;'>Mock provider session created</div>";
}

$provider_id = $_SESSION['user_id'];

echo "<h1>🏁 Add Completed Services for Testing</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
    .success { background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 5px; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px; }
    .info { background: #cce5ff; color: #004085; padding: 10px; margin: 10px 0; border-radius: 5px; }
    .button { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 5px; }
</style>";

echo "<div class='info'>This will create some completed services so you can test the Service History functionality.</div>";

// Check if we already have completed services
$existing_completed = $mysqli->query("SELECT COUNT(*) as count FROM services WHERE provider_id = $provider_id AND status = 'completed'");
$completed_count = $existing_completed ? $existing_completed->fetch_assoc()['count'] : 0;

echo "<p><strong>Current completed services:</strong> $completed_count</p>";

if (isset($_GET['create'])) {
    // Create completed services
    $completed_services = [
        [
            'service_code' => 'SRV-H001',
            'service_type' => 'Transport',
            'route' => 'Manila - Pasig',
            'origin' => 'Manila',
            'destination' => 'Pasig',
            'capacity' => 35,
            'current_passengers' => 28,
            'base_fare' => 50.00,
            'revenue' => 1400.00,
            'rating' => 4.8,
            'notes' => 'Completed transport service with high passenger satisfaction'
        ],
        [
            'service_code' => 'SRV-H002',
            'service_type' => 'Delivery',
            'route' => 'BGC - Ortigas',
            'origin' => 'BGC',
            'destination' => 'Ortigas',
            'capacity' => 50,
            'current_passengers' => 0,
            'base_fare' => 80.00,
            'revenue' => 1200.00,
            'rating' => 4.6,
            'notes' => 'Package delivery service completed successfully'
        ],
        [
            'service_code' => 'SRV-H003',
            'service_type' => 'Transport',
            'route' => 'Quezon City - Manila',
            'origin' => 'Quezon City',
            'destination' => 'Manila',
            'capacity' => 40,
            'current_passengers' => 35,
            'base_fare' => 60.00,
            'revenue' => 2100.00,
            'rating' => 4.9,
            'notes' => 'High-capacity route with excellent ratings'
        ],
        [
            'service_code' => 'SRV-H004',
            'service_type' => 'Emergency',
            'route' => 'Hospital - Residential',
            'origin' => 'Hospital',
            'destination' => 'Residential',
            'capacity' => 1,
            'current_passengers' => 1,
            'base_fare' => 200.00,
            'revenue' => 200.00,
            'rating' => 5.0,
            'notes' => 'Emergency medical transport completed'
        ],
        [
            'service_code' => 'SRV-H005',
            'service_type' => 'Transport',
            'route' => 'Makati - Taguig',
            'origin' => 'Makati',
            'destination' => 'Taguig',
            'capacity' => 30,
            'current_passengers' => 25,
            'base_fare' => 45.00,
            'revenue' => 1125.00,
            'rating' => 4.7,
            'notes' => 'Business district shuttle service'
        ]
    ];
    
    $created_count = 0;
    
    foreach ($completed_services as $service) {
        // Check if service already exists
        $check = $mysqli->prepare("SELECT id FROM services WHERE service_code = ? AND provider_id = ?");
        $check->bind_param('si', $service['service_code'], $provider_id);
        $check->execute();
        
        if ($check->get_result()->fetch_assoc()) {
            echo "<p>⚠️ Service {$service['service_code']} already exists, skipping...</p>";
            continue;
        }
        
        // Insert the service
        $stmt = $mysqli->prepare("
            INSERT INTO services (
                service_code, provider_id, service_type, route, origin, destination, 
                capacity, current_passengers, status, base_fare, revenue, rating, notes,
                actual_start, actual_end
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?, ?, ?, ?, 
                      NOW() - INTERVAL ? HOUR - INTERVAL ? MINUTE,
                      NOW() - INTERVAL ? HOUR
            )
        ");
        
        // Random timing for realistic data
        $start_hours_ago = rand(1, 72); // 1-72 hours ago
        $duration_minutes = rand(30, 180); // 30 minutes to 3 hours
        $end_hours_ago = max(0, $start_hours_ago - ($duration_minutes / 60));
        
        $stmt->bind_param(
            'sisssiiiiddsiii',
            $service['service_code'],
            $provider_id,
            $service['service_type'],
            $service['route'],
            $service['origin'],
            $service['destination'],
            $service['capacity'],
            $service['current_passengers'],
            $service['base_fare'],
            $service['revenue'],
            $service['rating'],
            $service['notes'],
            $start_hours_ago,
            rand(0, 59), // Random minutes
            $end_hours_ago
        );
        
        if ($stmt->execute()) {
            $service_id = $stmt->insert_id;
            
            // Add service history records
            $history_stmt = $mysqli->prepare("
                INSERT INTO service_history (service_id, action, previous_status, new_status, 
                                           passenger_count, revenue_amount, notes, action_by_user_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? HOUR)
            ");
            
            // Service created
            $history_stmt->bind_param('isssidsii', $service_id, 'created', null, 'pending', null, null, 'Service created', $provider_id, $start_hours_ago + 1);
            $history_stmt->execute();
            
            // Service started  
            $history_stmt->bind_param('isssidsii', $service_id, 'started', 'pending', 'active', null, null, 'Service started', $provider_id, $start_hours_ago);
            $history_stmt->execute();
            
            // Service completed
            $history_stmt->bind_param('isssidsii', $service_id, 'completed', 'active', 'completed', $service['current_passengers'], $service['revenue'], 'Service completed successfully', $provider_id, $end_hours_ago);
            $history_stmt->execute();
            
            echo "<div class='success'>✅ Created completed service: {$service['service_code']} (Revenue: ₱{$service['revenue']})</div>";
            $created_count++;
        } else {
            echo "<div class='error'>❌ Error creating service {$service['service_code']}: {$stmt->error}</div>";
        }
    }
    
    echo "<hr>";
    echo "<h2>Summary</h2>";
    echo "<div class='success'>✅ Successfully created $created_count completed services!</div>";
    
    if ($created_count > 0) {
        echo "<p>Now you can test the Service History functionality.</p>";
        echo "<p>";
        echo "<a href='provider-service.php' class='button'>Go to My Services</a>";
        echo "<a href='test-service-history.php' class='button'>Test Service History</a>";
        echo "</p>";
    }
    
} else {
    // Show create button
    echo "<h2>Ready to Create Completed Services?</h2>";
    echo "<p>This will create 5 sample completed services with:</p>";
    echo "<ul>";
    echo "<li>✅ Different service types (Transport, Delivery, Emergency)</li>";
    echo "<li>✅ Various routes and passenger counts</li>";
    echo "<li>✅ Revenue data and ratings</li>";
    echo "<li>✅ Realistic timestamps (completed in last 72 hours)</li>";
    echo "<li>✅ Complete service history records</li>";
    echo "</ul>";
    
    echo "<p>";
    echo "<a href='?create=1' class='button' style='background: #28a745;'>Create Completed Services</a>";
    echo "<a href='provider-service.php' class='button'>Go to My Services</a>";
    echo "</p>";
}
?>

<script>
// Auto-redirect after successful creation
if (window.location.search.includes('create=1') && document.querySelector('.success')) {
    setTimeout(() => {
        if (confirm('Completed services created! Go to My Services page to test?')) {
            window.location.href = 'provider-service.php';
        }
    }, 3000);
}
</script>
