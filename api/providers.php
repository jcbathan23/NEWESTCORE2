<?php
require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

switch ($method) {
	case 'GET':
		if ($id > 0) {
			$stmt = $mysqli->prepare('SELECT * FROM providers WHERE id = ?');
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$row = $stmt->get_result()->fetch_assoc();
			return $row ? send_json($row) : send_json(['error' => 'Not found'], 404);
		}
		$res = $mysqli->query('SELECT * FROM providers ORDER BY id ASC');
		$data = [];
		while ($r = $res->fetch_assoc()) $data[] = $r;
		return send_json($data);

	case 'POST':
		$body = read_json_body();
		$errors = validate_provider($body);
		if ($errors) return send_json(['errors' => $errors], 422);
		$stmt = $mysqli->prepare('INSERT INTO providers (name,type,contact_person,contact_email,contact_phone,service_area,monthly_rate,status,contract_start,contract_end,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
		$stmt->bind_param(
			'ssssssdssss',
			$body['name'],$body['type'],$body['contactPerson'],$body['contactEmail'],$body['contactPhone'],$body['serviceArea'],$body['monthlyRate'],$body['status'],$body['contractStart'],$body['contractEnd'],$body['notes']
		);
		if (!$stmt->execute()) return send_json(['error' => 'Insert failed', 'details' => $stmt->error], 500);
		return after_write($stmt->insert_id);

	case 'PUT':
		if ($id <= 0) return send_json(['error' => 'Missing id'], 400);
		$body = read_json_body();
		$errors = validate_provider($body);
		if ($errors) return send_json(['errors' => $errors], 422);
		$stmt = $mysqli->prepare('UPDATE providers SET name=?,type=?,contact_person=?,contact_email=?,contact_phone=?,service_area=?,monthly_rate=?,status=?,contract_start=?,contract_end=?,notes=? WHERE id=?');
		$stmt->bind_param(
			'ssssssdssssi',
			$body['name'],$body['type'],$body['contactPerson'],$body['contactEmail'],$body['contactPhone'],$body['serviceArea'],$body['monthlyRate'],$body['status'],$body['contractStart'],$body['contractEnd'],$body['notes'],$id
		);
		if (!$stmt->execute()) return send_json(['error' => 'Update failed', 'details' => $stmt->error], 500);
		return after_write($id);

	case 'DELETE':
		if ($id <= 0) return send_json(['error' => 'Missing id'], 400);
		$stmt = $mysqli->prepare('DELETE FROM providers WHERE id = ?');
		$stmt->bind_param('i', $id);
		if (!$stmt->execute()) return send_json(['error' => 'Delete failed', 'details' => $stmt->error], 500);
		return send_json(['success' => true]);

	default:
		return send_json(['error' => 'Method not allowed'], 405);
}

function validate_provider(array $b): array {
	$e = [];
	$req = ['name','type','contactPerson','contactEmail','contactPhone','serviceArea','monthlyRate','status','contractStart','contractEnd'];
	foreach ($req as $f) if (!isset($b[$f]) || $b[$f] === '') $e[$f] = 'Required';
	if (isset($b['monthlyRate']) && !is_numeric($b['monthlyRate'])) $e['monthlyRate'] = 'Numeric value required';
	return $e;
}

function after_write(int $id): void {
	global $mysqli;
	$stmt = $mysqli->prepare('SELECT * FROM providers WHERE id = ?');
	$stmt->bind_param('i', $id);
	$stmt->execute();
	$row = $stmt->get_result()->fetch_assoc();
	send_json($row, 201);
}

?>


