<?php
require_once '../db.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
  case 'GET':
    $result = $db->query("SELECT * FROM service_points");
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    break;

  case 'POST':
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare("INSERT INTO service_points (name, type, location, services, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $data['name'], $data['type'], $data['location'], $data['services'], $data['status'], $data['notes']);
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;

  case 'PUT':
    parse_str($_SERVER['QUERY_STRING'], $params);
    $id = $params['id'] ?? 0;
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare("UPDATE service_points SET name=?, type=?, location=?, services=?, status=?, notes=? WHERE id=?");
    $stmt->bind_param("ssssssi", $data['name'], $data['type'], $data['location'], $data['services'], $data['status'], $data['notes'], $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;

  case 'DELETE':
    parse_str($_SERVER['QUERY_STRING'], $params);
    $id = $params['id'] ?? 0;
    $stmt = $db->prepare("DELETE FROM service_points WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;
}


