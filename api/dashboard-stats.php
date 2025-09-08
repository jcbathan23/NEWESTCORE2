<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

try {
    $stats = [
        'totalRoutes' => 0,
        'servicePoints' => 0, 
        'coverageArea' => 0,
        'efficiencyScore' => 0,
        'status' => 'success'
    ];

    // Get total active routes
    $routesQuery = $pdo->prepare("SELECT COUNT(*) as count FROM routes WHERE status = 'Active'");
    $routesQuery->execute();
    $routesResult = $routesQuery->fetch(PDO::FETCH_ASSOC);
    $stats['totalRoutes'] = (int)$routesResult['count'];

    // Get total active service points
    $pointsQuery = $pdo->prepare("SELECT COUNT(*) as count FROM service_points WHERE status = 'Active'");
    $pointsQuery->execute();
    $pointsResult = $pointsQuery->fetch(PDO::FETCH_ASSOC);
    $stats['servicePoints'] = (int)$pointsResult['count'];

    // Calculate coverage area (estimated based on route distance)
    $areaQuery = $pdo->prepare("SELECT SUM(distance) as total_distance FROM routes WHERE status = 'Active'");
    $areaQuery->execute();
    $areaResult = $areaQuery->fetch(PDO::FETCH_ASSOC);
    $totalDistance = (float)($areaResult['total_distance'] ?? 0);
    
    // Estimate coverage area (rough calculation: distance * average corridor width of 2km)
    $stats['coverageArea'] = round($totalDistance * 2, 1);

    // Calculate efficiency score based on route optimization
    $efficiencyQuery = $pdo->prepare("
        SELECT 
            COUNT(*) as total_routes,
            AVG(distance / estimated_time * 60) as avg_speed,
            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_routes
        FROM routes 
        WHERE estimated_time > 0
    ");
    $efficiencyQuery->execute();
    $efficiencyResult = $efficiencyQuery->fetch(PDO::FETCH_ASSOC);
    
    $totalRoutes = (int)($efficiencyResult['total_routes'] ?? 0);
    $avgSpeed = (float)($efficiencyResult['avg_speed'] ?? 0);
    $activeRoutes = (int)($efficiencyResult['active_routes'] ?? 0);
    
    // Calculate efficiency score (0-100%)
    $operationalRatio = $totalRoutes > 0 ? ($activeRoutes / $totalRoutes) * 100 : 0;
    $speedEfficiency = min($avgSpeed / 30 * 100, 100); // Assuming 30 km/h as optimal speed
    $stats['efficiencyScore'] = round(($operationalRatio * 0.6 + $speedEfficiency * 0.4), 1);

    echo json_encode($stats);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'totalRoutes' => 0,
        'servicePoints' => 0,
        'coverageArea' => 0,
        'efficiencyScore' => 0
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage(),
        'totalRoutes' => 0,
        'servicePoints' => 0,
        'coverageArea' => 0,
        'efficiencyScore' => 0
    ]);
}
?>
