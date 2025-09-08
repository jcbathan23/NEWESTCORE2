<?php
require_once '../db.php'; // Your DB connection

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
  case 'GET':
    $result = $db->query("SELECT * FROM routes");
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    break;

  case 'POST':
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare("INSERT INTO routes (name, type, start_point, end_point, distance, frequency, status, estimated_time, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssdsiss", $data['name'], $data['type'], $data['startPoint'], $data['endPoint'], $data['distance'], $data['frequency'], $data['status'], $data['estimatedTime'], $data['notes']);
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;

  case 'PUT':
    parse_str($_SERVER['QUERY_STRING'], $params);
    $id = $params['id'] ?? 0;
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare("UPDATE routes SET name=?, type=?, start_point=?, end_point=?, distance=?, frequency=?, status=?, estimated_time=?, notes=? WHERE id=?");
    $stmt->bind_param("ssssdsissi", $data['name'], $data['type'], $data['startPoint'], $data['endPoint'], $data['distance'], $data['frequency'], $data['status'], $data['estimatedTime'], $data['notes'], $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;

  case 'DELETE':
    parse_str($_SERVER['QUERY_STRING'], $params);
    $id = $params['id'] ?? 0;
    $stmt = $db->prepare("DELETE FROM routes WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;
}


