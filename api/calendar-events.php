<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';
require_once '../auth.php';

/**
 * Calendar Events API Endpoint for CORE II
 * Manages calendar events for users, admins, and providers
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get database connection
 */
function getDBConnection() {
    global $host, $username, $password, $database;
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Validate user authentication
 */
function validateAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Authentication required'
        ]);
        exit;
    }
}

/**
 * Create events table if it doesn't exist
 */
function createEventsTable($pdo) {
    $sql = "CREATE TABLE IF NOT EXISTS calendar_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_date DATE NOT NULL,
        start_time TIME,
        end_time TIME,
        event_type ENUM('personal', 'work', 'meeting', 'reminder', 'maintenance') DEFAULT 'personal',
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_date (user_id, event_date),
        INDEX idx_date_range (event_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
}

/**
 * Get events for a specific month and user
 */
function getEvents($pdo, $user_id, $year, $month) {
    $sql = "SELECT * FROM calendar_events 
            WHERE user_id = ? 
            AND YEAR(event_date) = ? 
            AND MONTH(event_date) = ? 
            ORDER BY event_date ASC, start_time ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $year, $month]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get events for today
 */
function getTodayEvents($pdo, $user_id) {
    $today = date('Y-m-d');
    $sql = "SELECT * FROM calendar_events 
            WHERE user_id = ? 
            AND event_date = ? 
            ORDER BY start_time ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $today]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Create a new event
 */
function createEvent($pdo, $user_id, $event_data) {
    $sql = "INSERT INTO calendar_events 
            (user_id, title, description, event_date, start_time, end_time, event_type, priority) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user_id,
        $event_data['title'],
        $event_data['description'] ?? '',
        $event_data['event_date'],
        $event_data['start_time'] ?? null,
        $event_data['end_time'] ?? null,
        $event_data['event_type'] ?? 'personal',
        $event_data['priority'] ?? 'medium'
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * Update an event
 */
function updateEvent($pdo, $event_id, $user_id, $event_data) {
    $sql = "UPDATE calendar_events 
            SET title = ?, description = ?, event_date = ?, start_time = ?, 
                end_time = ?, event_type = ?, priority = ?, status = ?
            WHERE id = ? AND user_id = ?";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $event_data['title'],
        $event_data['description'] ?? '',
        $event_data['event_date'],
        $event_data['start_time'] ?? null,
        $event_data['end_time'] ?? null,
        $event_data['event_type'] ?? 'personal',
        $event_data['priority'] ?? 'medium',
        $event_data['status'] ?? 'scheduled',
        $event_id,
        $user_id
    ]);
}

/**
 * Delete an event
 */
function deleteEvent($pdo, $event_id, $user_id) {
    $sql = "DELETE FROM calendar_events WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$event_id, $user_id]);
}

/**
 * Get calendar statistics
 */
function getCalendarStats($pdo, $user_id) {
    $today = date('Y-m-d');
    $this_month_start = date('Y-m-01');
    $this_month_end = date('Y-m-t');
    
    // Today's events
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM calendar_events WHERE user_id = ? AND event_date = ?");
    $stmt->execute([$user_id, $today]);
    $today_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // This month's events
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM calendar_events WHERE user_id = ? AND event_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $this_month_start, $this_month_end]);
    $month_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Upcoming events (next 7 days)
    $next_week = date('Y-m-d', strtotime('+7 days'));
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM calendar_events WHERE user_id = ? AND event_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $today, $next_week]);
    $upcoming_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    return [
        'today' => $today_count,
        'this_month' => $month_count,
        'upcoming' => $upcoming_count
    ];
}

/**
 * Generate sample events for demonstration
 */
function generateSampleEvents($pdo, $user_id) {
    $events = [
        [
            'title' => 'Team Meeting',
            'description' => 'Weekly team sync meeting',
            'event_date' => date('Y-m-d', strtotime('+1 day')),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'event_type' => 'meeting',
            'priority' => 'high'
        ],
        [
            'title' => 'Project Deadline',
            'description' => 'Complete project deliverables',
            'event_date' => date('Y-m-d', strtotime('+3 days')),
            'start_time' => '17:00:00',
            'end_time' => '18:00:00',
            'event_type' => 'work',
            'priority' => 'high'
        ],
        [
            'title' => 'System Maintenance',
            'description' => 'Scheduled system maintenance window',
            'event_date' => date('Y-m-d', strtotime('+5 days')),
            'start_time' => '02:00:00',
            'end_time' => '04:00:00',
            'event_type' => 'maintenance',
            'priority' => 'medium'
        ]
    ];
    
    foreach ($events as $event) {
        // Check if event already exists to avoid duplicates
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM calendar_events WHERE user_id = ? AND title = ? AND event_date = ?");
        $stmt->execute([$user_id, $event['title'], $event['event_date']]);
        
        if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] == 0) {
            createEvent($pdo, $user_id, $event);
        }
    }
}

// Main execution
try {
    $method = $_SERVER['REQUEST_METHOD'];
    $pdo = getDBConnection();
    
    // Create events table if it doesn't exist
    createEventsTable($pdo);
    
    if ($method === 'GET') {
        // Handle GET requests - fetch events
        
        if (isset($_GET['demo']) && $_GET['demo'] === 'true') {
            // Return demo data without authentication for initial display
            $demo_events = [
                'events' => [
                    [
                        'id' => 1,
                        'title' => 'Team Meeting',
                        'event_date' => date('Y-m-d', strtotime('+1 day')),
                        'start_time' => '09:00:00',
                        'event_type' => 'meeting'
                    ],
                    [
                        'id' => 2,
                        'title' => 'Project Review',
                        'event_date' => date('Y-m-d', strtotime('+2 days')),
                        'start_time' => '14:00:00',
                        'event_type' => 'work'
                    ]
                ],
                'stats' => [
                    'today' => 0,
                    'this_month' => 2,
                    'upcoming' => 2
                ]
            ];
            echo json_encode(['success' => true, 'data' => $demo_events]);
            exit;
        }
        
        validateAuth();
        $user_id = $_SESSION['user_id'];
        
        if (isset($_GET['action']) && $_GET['action'] === 'generate_sample') {
            // Generate sample events
            generateSampleEvents($pdo, $user_id);
            echo json_encode(['success' => true, 'message' => 'Sample events generated']);
            exit;
        }
        
        if (isset($_GET['today']) && $_GET['today'] === 'true') {
            // Get today's events
            $events = getTodayEvents($pdo, $user_id);
            echo json_encode(['success' => true, 'events' => $events]);
            exit;
        }
        
        if (isset($_GET['stats']) && $_GET['stats'] === 'true') {
            // Get calendar statistics
            $stats = getCalendarStats($pdo, $user_id);
            echo json_encode(['success' => true, 'stats' => $stats]);
            exit;
        }
        
        // Get events for specific month/year
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        $month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
        
        $events = getEvents($pdo, $user_id, $year, $month);
        $stats = getCalendarStats($pdo, $user_id);
        
        echo json_encode([
            'success' => true,
            'events' => $events,
            'stats' => $stats
        ]);
        
    } else if ($method === 'POST') {
        // Handle POST requests - create events
        validateAuth();
        $user_id = $_SESSION['user_id'];
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['title']) || !isset($input['event_date'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Title and event date are required'
            ]);
            exit;
        }
        
        $event_id = createEvent($pdo, $user_id, $input);
        
        echo json_encode([
            'success' => true,
            'event_id' => $event_id,
            'message' => 'Event created successfully'
        ]);
        
    } else if ($method === 'PUT') {
        // Handle PUT requests - update events
        validateAuth();
        $user_id = $_SESSION['user_id'];
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Event ID is required'
            ]);
            exit;
        }
        
        $success = updateEvent($pdo, $input['id'], $user_id, $input);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Event updated successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Event not found or access denied'
            ]);
        }
        
    } else if ($method === 'DELETE') {
        // Handle DELETE requests - delete events
        validateAuth();
        $user_id = $_SESSION['user_id'];
        
        $event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (!$event_id) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Event ID is required'
            ]);
            exit;
        }
        
        $success = deleteEvent($pdo, $event_id, $user_id);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Event deleted successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Event not found or access denied'
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
        'error' => 'Internal server error: ' . $e->getMessage()
    ]);
    error_log("Calendar API Error: " . $e->getMessage());
}
?>
