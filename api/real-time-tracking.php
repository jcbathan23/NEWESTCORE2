<?php
require_once '../auth.php';
require_once '../db.php';
requireAdmin();

// Start session for maintaining vehicle simulation state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDB();
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['type'])) {
                $type = $_GET['type'];
                
                switch ($type) {
                    case 'vehicles':
                        // Get all vehicles with their real-time positions
                        $stmt = $pdo->prepare("
                            SELECT v.*, vt.latitude, vt.longitude, vt.status as tracking_status, 
                                   vt.speed, vt.heading, vt.updated_at as last_update,
                                   r.name as route_name, r.start_point, r.end_point
                            FROM vehicles v
                            LEFT JOIN vehicle_tracking vt ON v.id = vt.vehicle_id
                            LEFT JOIN routes r ON v.route_id = r.id
                            WHERE v.status = 'Active'
                            ORDER BY vt.updated_at DESC
                        ");
                        $stmt->execute();
                        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Add simulated data if no real vehicles exist
                        if (empty($vehicles)) {
                            $vehicles = generateSimulatedVehicles();
                        }
                        
                        echo json_encode($vehicles);
                        break;
                        
                    case 'routes':
                        // Get all routes with coordinates
                        $stmt = $pdo->prepare("
                            SELECT r.*, rc.coordinates
                            FROM routes r
                            LEFT JOIN route_coordinates rc ON r.id = rc.route_id
                            WHERE r.status = 'Active'
                        ");
                        $stmt->execute();
                        $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Add sample route coordinates if none exist
                        foreach ($routes as &$route) {
                            if (!$route['coordinates']) {
                                $route['coordinates'] = generateRouteCoordinates($route['start_point'], $route['end_point']);
                            }
                        }
                        
                        echo json_encode($routes);
                        break;
                        
                    case 'service-points':
                        // Get all service points with coordinates
                        $stmt = $pdo->prepare("
                            SELECT sp.*, spc.latitude, spc.longitude
                            FROM service_points sp
                            LEFT JOIN service_point_coordinates spc ON sp.id = spc.service_point_id
                            WHERE sp.status = 'Active'
                        ");
                        $stmt->execute();
                        $servicePoints = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Add sample coordinates if none exist
                        foreach ($servicePoints as &$point) {
                            if (!$point['latitude'] || !$point['longitude']) {
                                $coords = generateServicePointCoordinates($point['location']);
                                $point['latitude'] = $coords['lat'];
                                $point['longitude'] = $coords['lng'];
                            }
                        }
                        
                        echo json_encode($servicePoints);
                        break;
                        
                    case 'alerts':
                        // Get active alerts and incidents
                        $alerts = [
                            [
                                'id' => 1,
                                'type' => 'traffic',
                                'severity' => 'medium',
                                'message' => 'Heavy traffic on Route Manila-Quezon',
                                'location' => [14.6042, 121.0117],
                                'timestamp' => date('Y-m-d H:i:s')
                            ],
                            [
                                'id' => 2,
                                'type' => 'maintenance',
                                'severity' => 'high',
                                'message' => 'Vehicle V-003 requires immediate maintenance',
                                'location' => [14.5995, 120.9842],
                                'timestamp' => date('Y-m-d H:i:s', strtotime('-15 minutes'))
                            ]
                        ];
                        echo json_encode($alerts);
                        break;
                        
                    default:
                        echo json_encode(['error' => 'Invalid type parameter']);
                }
            } else {
                // Return all real-time data
                $data = [
                    'vehicles' => generateSimulatedVehicles(),
                    'routes' => [],
                    'servicePoints' => [],
                    'alerts' => [],
                    'lastUpdate' => date('Y-m-d H:i:s')
                ];
                echo json_encode($data);
            }
            break;
            
        case 'POST':
            // Update vehicle position
            if (isset($input['vehicleId']) && isset($input['latitude']) && isset($input['longitude'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO vehicle_tracking (vehicle_id, latitude, longitude, status, speed, heading, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                    latitude = VALUES(latitude),
                    longitude = VALUES(longitude),
                    status = VALUES(status),
                    speed = VALUES(speed),
                    heading = VALUES(heading),
                    updated_at = VALUES(updated_at)
                ");
                $stmt->execute([
                    $input['vehicleId'],
                    $input['latitude'],
                    $input['longitude'],
                    $input['status'] ?? 'Active',
                    $input['speed'] ?? 0,
                    $input['heading'] ?? 0
                ]);
                
                echo json_encode(['success' => true, 'message' => 'Position updated successfully']);
            } else {
                echo json_encode(['error' => 'Missing required parameters']);
            }
            break;
            
        default:
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

function generateSimulatedVehicles() {
    // Use session to maintain vehicle positions for realistic movement
    if (!isset($_SESSION['vehicle_positions'])) {
        $_SESSION['vehicle_positions'] = [];
        $_SESSION['last_update'] = time();
    }
    
    $now = time();
    $timeDiff = $now - ($_SESSION['last_update'] ?? $now);
    $_SESSION['last_update'] = $now;
    
    $vehicles = [
        [
            'id' => 'V-001',
            'name' => 'Bus Alpha',
            'type' => 'Bus',
            'tracking_status' => 'Active',
            'route_name' => 'Manila-Quezon Route',
            'start_point' => 'Manila Central',
            'end_point' => 'Quezon Terminal',
            'base_lat' => 14.5995,
            'base_lng' => 120.9842,
            'max_speed' => 45,
            'capacity' => 50
        ],
        [
            'id' => 'V-002',
            'name' => 'Bus Beta',
            'type' => 'Bus',
            'tracking_status' => 'Active',
            'route_name' => 'Makati-BGC Express',
            'start_point' => 'Makati CBD',
            'end_point' => 'BGC Terminal',
            'base_lat' => 14.6042,
            'base_lng' => 121.0117,
            'max_speed' => 40,
            'capacity' => 45
        ],
        [
            'id' => 'V-003',
            'name' => 'Jeepney Gamma',
            'type' => 'Jeepney',
            'tracking_status' => 'Maintenance',
            'route_name' => 'Pasig Local Route',
            'start_point' => 'Pasig Station',
            'end_point' => 'Ortigas Center',
            'base_lat' => 14.5764,
            'base_lng' => 121.0851,
            'max_speed' => 0,
            'capacity' => 20
        ],
        [
            'id' => 'V-004',
            'name' => 'Van Delta',
            'type' => 'Van',
            'tracking_status' => 'Active',
            'route_name' => 'Quezon City Loop',
            'start_point' => 'QC Circle',
            'end_point' => 'SM North EDSA',
            'base_lat' => 14.6507,
            'base_lng' => 121.0762,
            'max_speed' => 55,
            'capacity' => 15
        ],
        [
            'id' => 'V-005',
            'name' => 'Bus Epsilon',
            'type' => 'Bus',
            'tracking_status' => 'Active',
            'route_name' => 'Paranaque-Alabang',
            'start_point' => 'Paranaque Terminal',
            'end_point' => 'Alabang Station',
            'base_lat' => 14.5378,
            'base_lng' => 120.9821,
            'max_speed' => 50,
            'capacity' => 52
        ],
        [
            'id' => 'V-006',
            'name' => 'Express Zeta',
            'type' => 'Bus',
            'tracking_status' => 'Active',
            'route_name' => 'EDSA Carousel',
            'start_point' => 'Monumento',
            'end_point' => 'MRT Taft',
            'base_lat' => 14.6536,
            'base_lng' => 121.0488,
            'max_speed' => 60,
            'capacity' => 60
        ]
    ];
    
    // Simulate realistic movement and generate current positions
    foreach ($vehicles as &$vehicle) {
        $vehicleId = $vehicle['id'];
        
        // Initialize position if not exists
        if (!isset($_SESSION['vehicle_positions'][$vehicleId])) {
            $_SESSION['vehicle_positions'][$vehicleId] = [
                'lat' => $vehicle['base_lat'],
                'lng' => $vehicle['base_lng'],
                'heading' => rand(0, 360),
                'speed' => 0,
                'last_move' => $now
            ];
        }
        
        $pos = &$_SESSION['vehicle_positions'][$vehicleId];
        
        // Simulate movement for active vehicles
        if ($vehicle['tracking_status'] === 'Active' && $timeDiff > 0) {
            $speed = rand(15, $vehicle['max_speed']); // km/h
            $speedMs = $speed * 1000 / 3600; // m/s
            $distance = $speedMs * $timeDiff; // meters moved
            
            // Convert distance to lat/lng (rough approximation)
            $latChange = ($distance / 111320) * cos(deg2rad($pos['heading']));
            $lngChange = ($distance / (111320 * cos(deg2rad($pos['lat'])))) * sin(deg2rad($pos['heading']));
            
            $pos['lat'] += $latChange;
            $pos['lng'] += $lngChange;
            $pos['speed'] = $speed;
            
            // Occasionally change direction (simulate turns)
            if (rand(1, 100) <= 5) { // 5% chance to turn
                $pos['heading'] = ($pos['heading'] + rand(-45, 45) + 360) % 360;
            }
            
            // Keep within reasonable bounds of base position (simulate route)
            $maxDistance = 0.02; // ~2km radius
            if (abs($pos['lat'] - $vehicle['base_lat']) > $maxDistance) {
                $pos['lat'] = $vehicle['base_lat'] + (rand(-100, 100) / 10000);
                $pos['heading'] = ($pos['heading'] + 180) % 360; // Turn around
            }
            if (abs($pos['lng'] - $vehicle['base_lng']) > $maxDistance) {
                $pos['lng'] = $vehicle['base_lng'] + (rand(-100, 100) / 10000);
                $pos['heading'] = ($pos['heading'] + 180) % 360; // Turn around
            }
        }
        
        // Set final vehicle data
        $vehicle['latitude'] = $pos['lat'];
        $vehicle['longitude'] = $pos['lng'];
        $vehicle['speed'] = $pos['speed'];
        $vehicle['heading'] = $pos['heading'];
        $vehicle['last_update'] = date('Y-m-d H:i:s');
        
        // Add realistic operational data
        $vehicle['passengers'] = $vehicle['tracking_status'] === 'Active' ? 
            rand(max(1, intval($vehicle['capacity'] * 0.1)), intval($vehicle['capacity'] * 0.9)) : 0;
        $vehicle['fuel_level'] = rand(15, 100);
        
        // Add some variety in status
        if ($vehicleId === 'V-003') {
            $vehicle['tracking_status'] = rand(1, 10) <= 7 ? 'Maintenance' : 'Active';
        } else if (rand(1, 100) <= 2) { // 2% chance of temporary issues
            $vehicle['tracking_status'] = 'Warning';
        }
        
        // Remove temporary fields
        unset($vehicle['base_lat'], $vehicle['base_lng'], $vehicle['max_speed']);
    }
    
    return $vehicles;
}

function generateRouteCoordinates($startPoint, $endPoint) {
    // Generate sample route coordinates based on start and end points
    // This is a simplified version - in real implementation, you'd use routing services
    $routes = [
        'Manila-Quezon' => [
            [14.5995, 120.9842],
            [14.6042, 120.9917],
            [14.6158, 121.0117],
            [14.6378, 121.0342]
        ],
        'Makati-BGC' => [
            [14.5547, 121.0244],
            [14.5578, 121.0317],
            [14.5515, 121.0453],
            [14.5465, 121.0511]
        ]
    ];
    
    // Return a default route if specific route not found
    return $routes['Manila-Quezon'];
}

function generateServicePointCoordinates($location) {
    // Generate coordinates based on location name
    $locations = [
        'Manila Central' => ['lat' => 14.5995, 'lng' => 120.9842],
        'Quezon Terminal' => ['lat' => 14.6378, 'lng' => 121.0342],
        'Makati CBD' => ['lat' => 14.5547, 'lng' => 121.0244],
        'BGC Terminal' => ['lat' => 14.5465, 'lng' => 121.0511],
        'Pasig Station' => ['lat' => 14.5764, 'lng' => 121.0851],
        'Ortigas Center' => ['lat' => 14.5866, 'lng' => 121.0576]
    ];
    
    return $locations[$location] ?? ['lat' => 14.5995, 'lng' => 120.9842];
}
?>
