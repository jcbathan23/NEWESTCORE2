<?php
/**
 * Simple WebSocket Server for Real-time Vehicle Tracking
 * This creates a basic WebSocket server for broadcasting vehicle updates
 */

require_once '../db.php';

class RealTimeWebSocketServer {
    private $server;
    private $clients = [];
    private $pdo;
    private $lastBroadcast = 0;
    private $broadcastInterval = 3; // seconds
    
    public function __construct($host = 'localhost', $port = 8080) {
        $this->pdo = getDB();
        $this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->server, $host, $port);
        socket_listen($this->server);
        
        echo "WebSocket server started on {$host}:{$port}\n";
        echo "Clients can connect to: ws://{$host}:{$port}\n";
    }
    
    public function run() {
        while (true) {
            $read = array_merge([$this->server], $this->clients);
            $write = null;
            $except = null;
            
            if (socket_select($read, $write, $except, 0, 100000) > 0) {
                // Handle new connections
                if (in_array($this->server, $read)) {
                    $client = socket_accept($this->server);
                    if ($client !== false) {
                        $this->handleNewConnection($client);
                    }
                    $key = array_search($this->server, $read);
                    unset($read[$key]);
                }
                
                // Handle client messages
                foreach ($read as $client) {
                    $this->handleClientMessage($client);
                }
            }
            
            // Broadcast vehicle updates periodically
            $this->broadcastVehicleUpdates();
            
            usleep(100000); // Sleep for 0.1 seconds
        }
    }
    
    private function handleNewConnection($client) {
        $request = socket_read($client, 1024);
        
        if (preg_match('/Sec-WebSocket-Key: (.*)/', $request, $matches)) {
            $key = trim($matches[1]);
            $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
            
            $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                       "Upgrade: websocket\r\n" .
                       "Connection: Upgrade\r\n" .
                       "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";
            
            socket_write($client, $response);
            $this->clients[] = $client;
            
            echo "New client connected. Total clients: " . count($this->clients) . "\n";
            
            // Send initial data
            $this->sendToClient($client, [
                'type' => 'welcome',
                'message' => 'Connected to real-time tracking server',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    private function handleClientMessage($client) {
        $data = socket_read($client, 1024);
        
        if ($data === false || $data === '') {
            $this->removeClient($client);
            return;
        }
        
        $message = $this->decodeMessage($data);
        if ($message) {
            echo "Received: " . json_encode($message) . "\n";
            
            // Handle subscription requests
            if (isset($message['type']) && $message['type'] === 'subscribe') {
                $this->sendToClient($client, [
                    'type' => 'subscription_confirmed',
                    'channel' => $message['channel'] ?? 'vehicle_tracking'
                ]);
            }
        }
    }
    
    private function removeClient($client) {
        $key = array_search($client, $this->clients);
        if ($key !== false) {
            unset($this->clients[$key]);
            socket_close($client);
            echo "Client disconnected. Total clients: " . count($this->clients) . "\n";
        }
    }
    
    private function broadcastVehicleUpdates() {
        $now = time();
        if ($now - $this->lastBroadcast < $this->broadcastInterval) {
            return;
        }
        
        $this->lastBroadcast = $now;
        
        if (empty($this->clients)) {
            return;
        }
        
        try {
            $vehicles = $this->getUpdatedVehicles();
            
            if (!empty($vehicles)) {
                $message = [
                    'type' => 'vehicles_batch',
                    'vehicles' => $vehicles,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                
                $this->broadcast($message);
            }
            
        } catch (Exception $e) {
            echo "Error broadcasting updates: " . $e->getMessage() . "\n";
        }
    }
    
    private function getUpdatedVehicles() {
        // Get vehicles from database or generate simulated data
        try {
            $stmt = $this->pdo->prepare("
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
            
            // If no real data, generate simulated data
            if (empty($vehicles)) {
                return $this->generateSimulatedVehicles();
            }
            
            return $vehicles;
            
        } catch (Exception $e) {
            echo "Database error, using simulated data: " . $e->getMessage() . "\n";
            return $this->generateSimulatedVehicles();
        }
    }
    
    private function generateSimulatedVehicles() {
        // Manila area coordinates for simulation with slight movement
        static $lastPositions = [];
        
        $vehicles = [
            [
                'id' => 'V-001',
                'name' => 'Bus Alpha',
                'type' => 'Bus',
                'tracking_status' => 'Active',
                'route_name' => 'Manila-Quezon Route',
                'start_point' => 'Manila Central',
                'end_point' => 'Quezon Terminal'
            ],
            [
                'id' => 'V-002', 
                'name' => 'Bus Beta',
                'type' => 'Bus',
                'tracking_status' => 'Active',
                'route_name' => 'Makati-BGC Express',
                'start_point' => 'Makati CBD',
                'end_point' => 'BGC Terminal'
            ],
            [
                'id' => 'V-003',
                'name' => 'Jeepney Gamma',
                'type' => 'Jeepney', 
                'tracking_status' => 'Maintenance',
                'route_name' => 'Pasig Local Route',
                'start_point' => 'Pasig Station',
                'end_point' => 'Ortigas Center'
            ]
        ];
        
        $basePositions = [
            'V-001' => [14.5995, 120.9842],
            'V-002' => [14.6042, 121.0117], 
            'V-003' => [14.5764, 121.0851]
        ];
        
        foreach ($vehicles as &$vehicle) {
            $vehicleId = $vehicle['id'];
            $basePos = $basePositions[$vehicleId];
            
            // Initialize or update position with slight movement
            if (!isset($lastPositions[$vehicleId])) {
                $lastPositions[$vehicleId] = $basePos;
            } else if ($vehicle['tracking_status'] === 'Active') {
                // Simulate movement (small random changes)
                $lastPositions[$vehicleId][0] += (rand(-5, 5) / 100000); // lat
                $lastPositions[$vehicleId][1] += (rand(-5, 5) / 100000); // lng
                
                // Keep within reasonable bounds
                $maxDist = 0.01; // ~1km
                if (abs($lastPositions[$vehicleId][0] - $basePos[0]) > $maxDist) {
                    $lastPositions[$vehicleId][0] = $basePos[0] + (rand(-50, 50) / 10000);
                }
                if (abs($lastPositions[$vehicleId][1] - $basePos[1]) > $maxDist) {
                    $lastPositions[$vehicleId][1] = $basePos[1] + (rand(-50, 50) / 10000);
                }
            }
            
            $vehicle['latitude'] = $lastPositions[$vehicleId][0];
            $vehicle['longitude'] = $lastPositions[$vehicleId][1];
            $vehicle['speed'] = $vehicle['tracking_status'] === 'Active' ? rand(15, 45) : 0;
            $vehicle['heading'] = rand(0, 360);
            $vehicle['last_update'] = date('Y-m-d H:i:s');
        }
        
        return $vehicles;
    }
    
    private function broadcast($message) {
        $data = $this->encodeMessage(json_encode($message));
        
        foreach ($this->clients as $key => $client) {
            if (@socket_write($client, $data) === false) {
                // Remove failed clients
                $this->removeClient($client);
                unset($this->clients[$key]);
            }
        }
    }
    
    private function sendToClient($client, $message) {
        $data = $this->encodeMessage(json_encode($message));
        @socket_write($client, $data);
    }
    
    private function encodeMessage($data) {
        $length = strlen($data);
        $firstByte = 0x81; // Text frame
        
        if ($length <= 125) {
            return pack('CC', $firstByte, $length) . $data;
        } elseif ($length <= 65535) {
            return pack('CCn', $firstByte, 126, $length) . $data;
        } else {
            return pack('CCNN', $firstByte, 127, 0, $length) . $data;
        }
    }
    
    private function decodeMessage($data) {
        if (strlen($data) < 2) return null;
        
        $firstByte = ord($data[0]);
        $secondByte = ord($data[1]);
        
        $masked = $secondByte >> 7;
        $payloadLength = $secondByte & 0x7F;
        
        $offset = 2;
        if ($payloadLength === 126) {
            $payloadLength = unpack('n', substr($data, $offset, 2))[1];
            $offset += 2;
        } elseif ($payloadLength === 127) {
            $payloadLength = unpack('N', substr($data, $offset + 4, 4))[1];
            $offset += 8;
        }
        
        if ($masked) {
            $mask = substr($data, $offset, 4);
            $offset += 4;
            $payload = substr($data, $offset, $payloadLength);
            
            for ($i = 0; $i < $payloadLength; $i++) {
                $payload[$i] = $payload[$i] ^ $mask[$i % 4];
            }
        } else {
            $payload = substr($data, $offset, $payloadLength);
        }
        
        return json_decode($payload, true);
    }
}

// Start the server if run directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $server = new RealTimeWebSocketServer('localhost', 8080);
    $server->run();
}
?>
