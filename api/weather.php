<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

/**
 * Weather API Endpoint for CORE II
 * Fetches real-time weather data using OpenWeatherMap API
 */

// Configuration
$config = [
    'api_key' => 'YOUR_OPENWEATHERMAP_API_KEY', // Replace with actual API key
    'default_location' => [
        'lat' => 14.5995, // Manila, Philippines
        'lon' => 120.9842
    ],
    'cache_duration' => 600 // 10 minutes cache
];

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Get weather data from cache or API
 */
function getWeatherData($lat, $lon, $config) {
    $cache_file = __DIR__ . "/cache/weather_" . md5($lat . '_' . $lon) . ".json";
    $cache_time = file_exists($cache_file) ? filemtime($cache_file) : 0;
    
    // Check if cache is valid
    if (time() - $cache_time < $config['cache_duration']) {
        $cached_data = file_get_contents($cache_file);
        if ($cached_data) {
            return json_decode($cached_data, true);
        }
    }
    
    // Fetch fresh data from API
    $weather_data = fetchWeatherFromAPI($lat, $lon, $config['api_key']);
    
    if ($weather_data) {
        // Create cache directory if it doesn't exist
        $cache_dir = dirname($cache_file);
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        
        // Save to cache
        file_put_contents($cache_file, json_encode($weather_data));
    }
    
    return $weather_data;
}

/**
 * Fetch weather data from OpenWeatherMap API
 */
function fetchWeatherFromAPI($lat, $lon, $api_key) {
    try {
        // Current weather URL
        $current_url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$api_key}&units=metric";
        
        // Forecast URL
        $forecast_url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$api_key}&units=metric";
        
        // Initialize cURL for current weather
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $current_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $current_response = curl_exec($ch);
        $current_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($current_http_code !== 200 || !$current_response) {
            return getMockWeatherData();
        }
        
        $current_data = json_decode($current_response, true);
        
        // Initialize cURL for forecast
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $forecast_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $forecast_response = curl_exec($ch);
        $forecast_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $forecast_data = [];
        if ($forecast_http_code === 200 && $forecast_response) {
            $forecast_data = json_decode($forecast_response, true);
        }
        
        return formatWeatherData($current_data, $forecast_data);
        
    } catch (Exception $e) {
        error_log("Weather API Error: " . $e->getMessage());
        return getMockWeatherData();
    }
}

/**
 * Format weather data into a standardized format
 */
function formatWeatherData($current, $forecast) {
    $formatted = [
        'success' => true,
        'location' => $current['name'] . ', ' . $current['sys']['country'],
        'coordinates' => [
            'lat' => $current['coord']['lat'],
            'lon' => $current['coord']['lon']
        ],
        'current' => [
            'temperature' => round($current['main']['temp']),
            'feels_like' => round($current['main']['feels_like']),
            'humidity' => $current['main']['humidity'],
            'pressure' => $current['main']['pressure'],
            'visibility' => isset($current['visibility']) ? round($current['visibility'] / 1000, 1) : 10,
            'wind_speed' => round($current['wind']['speed'] * 3.6), // Convert m/s to km/h
            'wind_direction' => isset($current['wind']['deg']) ? $current['wind']['deg'] : 0,
            'description' => $current['weather'][0]['description'],
            'icon' => mapWeatherIcon($current['weather'][0]['icon']),
            'sunrise' => date('H:i', $current['sys']['sunrise']),
            'sunset' => date('H:i', $current['sys']['sunset'])
        ],
        'hourly' => [],
        'last_updated' => time()
    ];
    
    // Add hourly forecast if available
    if (!empty($forecast['list'])) {
        $hourly_count = 0;
        foreach ($forecast['list'] as $hour) {
            if ($hourly_count >= 6) break; // Limit to 6 hours
            
            $formatted['hourly'][] = [
                'time' => date('H:i', $hour['dt']),
                'temperature' => round($hour['main']['temp']),
                'description' => $hour['weather'][0]['description'],
                'icon' => mapWeatherIcon($hour['weather'][0]['icon']),
                'humidity' => $hour['main']['humidity'],
                'wind_speed' => round($hour['wind']['speed'] * 3.6)
            ];
            
            $hourly_count++;
        }
    }
    
    return $formatted;
}

/**
 * Map OpenWeatherMap icons to Bootstrap icons
 */
function mapWeatherIcon($owm_icon) {
    $icon_map = [
        '01d' => 'bi-sun',
        '01n' => 'bi-moon',
        '02d' => 'bi-cloud-sun',
        '02n' => 'bi-cloud-moon',
        '03d' => 'bi-cloud',
        '03n' => 'bi-cloud',
        '04d' => 'bi-clouds',
        '04n' => 'bi-clouds',
        '09d' => 'bi-cloud-rain',
        '09n' => 'bi-cloud-rain',
        '10d' => 'bi-cloud-rain',
        '10n' => 'bi-cloud-rain',
        '11d' => 'bi-cloud-lightning',
        '11n' => 'bi-cloud-lightning',
        '13d' => 'bi-cloud-snow',
        '13n' => 'bi-cloud-snow',
        '50d' => 'bi-cloud-fog',
        '50n' => 'bi-cloud-fog'
    ];
    
    return isset($icon_map[$owm_icon]) ? $icon_map[$owm_icon] : 'bi-cloud-sun';
}

/**
 * Generate mock weather data for demo purposes
 */
function getMockWeatherData() {
    $descriptions = ['Clear sky', 'Partly cloudy', 'Scattered clouds', 'Light rain', 'Sunny'];
    $icons = ['bi-sun', 'bi-cloud-sun', 'bi-cloud', 'bi-cloud-rain', 'bi-clouds'];
    
    $base_temp = rand(22, 32);
    $description_index = rand(0, count($descriptions) - 1);
    
    $mock_data = [
        'success' => true,
        'location' => 'Manila, PH',
        'coordinates' => ['lat' => 14.5995, 'lon' => 120.9842],
        'current' => [
            'temperature' => $base_temp,
            'feels_like' => $base_temp + rand(1, 3),
            'humidity' => rand(60, 90),
            'pressure' => rand(1010, 1020),
            'visibility' => rand(8, 15),
            'wind_speed' => rand(5, 20),
            'wind_direction' => rand(0, 360),
            'description' => $descriptions[$description_index],
            'icon' => $icons[$description_index],
            'sunrise' => '06:00',
            'sunset' => '18:30'
        ],
        'hourly' => [],
        'last_updated' => time()
    ];
    
    // Generate hourly forecast
    for ($i = 1; $i <= 6; $i++) {
        $hour_temp = $base_temp + rand(-3, 3);
        $hour_desc_index = rand(0, count($descriptions) - 1);
        
        $mock_data['hourly'][] = [
            'time' => date('H:i', time() + ($i * 3600)),
            'temperature' => $hour_temp,
            'description' => $descriptions[$hour_desc_index],
            'icon' => $icons[$hour_desc_index],
            'humidity' => rand(60, 90),
            'wind_speed' => rand(5, 20)
        ];
    }
    
    return $mock_data;
}

// Main execution
try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Get coordinates from query parameters
        $lat = isset($_GET['lat']) ? floatval($_GET['lat']) : $config['default_location']['lat'];
        $lon = isset($_GET['lon']) ? floatval($_GET['lon']) : $config['default_location']['lon'];
        
        // Validate coordinates
        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid coordinates'
            ]);
            exit;
        }
        
        $weather_data = getWeatherData($lat, $lon, $config);
        
        if ($weather_data) {
            echo json_encode($weather_data);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Unable to fetch weather data'
            ]);
        }
        
    } else if ($method === 'POST') {
        // Handle POST requests for specific location updates
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['lat']) && isset($input['lon'])) {
            $weather_data = getWeatherData($input['lat'], $input['lon'], $config);
            echo json_encode($weather_data);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Missing latitude or longitude'
            ]);
        }
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
    error_log("Weather API Error: " . $e->getMessage());
}
?>
