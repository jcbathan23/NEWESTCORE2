<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    send_json(['error' => 'Unauthorized'], 401);
    exit;
}

// Check admin privileges for write operations
$isAdmin = ($_SESSION['role'] === 'admin');
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE']) && !$isAdmin) {
    send_json(['error' => 'Admin privileges required'], 403);
    exit;
}

// Basic routing for tariffs resource
$method = $_SERVER['REQUEST_METHOD'];

// Allow id via query string
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

switch ($method) {
	case 'GET':
		if ($id > 0) {
			$get = $mysqli->prepare('SELECT * FROM tariffs WHERE id = ?');
			$get->bind_param('i', $id);
			$get->execute();
			$result = $get->get_result()->fetch_assoc();
			if (!$result) {
				return send_json(['error' => 'Not found'], 404);
			}
			return send_json($result);
		}

		// Filter tariffs based on user role
		if ($isAdmin) {
			// Admins see all tariffs
			$res = $mysqli->query('SELECT * FROM tariffs ORDER BY id ASC');
		} else {
			// Regular users only see admin-created tariffs, not user submissions
			$res = $mysqli->query("SELECT * FROM tariffs WHERE source = 'admin' OR source IS NULL ORDER BY id ASC");
		}
		
		$data = [];
		while ($row = $res->fetch_assoc()) {
			$data[] = $row;
		}
		return send_json($data);

	case 'POST':
		$body = read_json_body();
		$errors = validate_tariff($body);
		if ($errors) {
			return send_json(['errors' => $errors], 422);
		}

		$stmt = $mysqli->prepare('INSERT INTO tariffs (name, category, base_rate, per_km_rate, per_hour_rate, priority_multiplier, status, effective_date, expiry_date, notes, source) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
		$stmt->bind_param(
			'ssddddsssss',
			$body['name'],
			$body['category'],
			$body['baseRate'],
			$body['perKmRate'],
			$body['perHourRate'],
			$body['priorityMultiplier'],
			$body['status'],
			$body['effectiveDate'],
			$body['expiryDate'],
			$body['notes'],
			'admin'
		);
		if (!$stmt->execute()) {
			return send_json(['error' => 'Insert failed', 'details' => $stmt->error], 500);
		}

		$newId = $stmt->insert_id;
		return after_write_response($newId);

	case 'PUT':
		if ($id <= 0) {
			return send_json(['error' => 'Missing id'], 400);
		}
		$body = read_json_body();
		$errors = validate_tariff($body);
		if ($errors) {
			return send_json(['errors' => $errors], 422);
		}

		$stmt = $mysqli->prepare('UPDATE tariffs SET name=?, category=?, base_rate=?, per_km_rate=?, per_hour_rate=?, priority_multiplier=?, status=?, effective_date=?, expiry_date=?, notes=? WHERE id=?');
		$stmt->bind_param(
			'ssddddssssi',
			$body['name'],
			$body['category'],
			$body['baseRate'],
			$body['perKmRate'],
			$body['perHourRate'],
			$body['priorityMultiplier'],
			$body['status'],
			$body['effectiveDate'],
			$body['expiryDate'],
			$body['notes'],
			$id
		);
		if (!$stmt->execute()) {
			return send_json(['error' => 'Update failed', 'details' => $stmt->error], 500);
		}

		return after_write_response($id);

	case 'DELETE':
		if ($id <= 0) {
			return send_json(['error' => 'Missing id'], 400);
		}
		$stmt = $mysqli->prepare('DELETE FROM tariffs WHERE id = ?');
		$stmt->bind_param('i', $id);
		if (!$stmt->execute()) {
			return send_json(['error' => 'Delete failed', 'details' => $stmt->error], 500);
		}
		return send_json(['success' => true]);

	default:
		return send_json(['error' => 'Method not allowed'], 405);
}

function validate_tariff(array $body): array {
	$errors = [];
	$requiredStrings = ['name','category','status','effectiveDate','expiryDate'];
	foreach ($requiredStrings as $field) {
		if (!isset($body[$field]) || trim((string)$body[$field]) === '') {
			$errors[$field] = 'Required';
		}
	}
	$requiredNumbers = ['baseRate','perKmRate','perHourRate','priorityMultiplier'];
	foreach ($requiredNumbers as $field) {
		if (!isset($body[$field]) || !is_numeric($body[$field])) {
			$errors[$field] = 'Numeric value required';
		}
	}
	return $errors;
}

function after_write_response(int $id) {
	global $mysqli;
	$get = $mysqli->prepare('SELECT * FROM tariffs WHERE id = ?');
	$get->bind_param('i', $id);
	$get->execute();
	$row = $get->get_result()->fetch_assoc();
	send_json($row, 201);
}

?>


