<?php
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

// resource can be: providers, routes, points, tariffs, schedules, summary
$resource = isset($_GET['resource']) ? $_GET['resource'] : 'summary';

function fetch_all_assoc(mysqli $mysqli, string $sql): array {
	$data = [];
	$res = $mysqli->query($sql);
	if ($res) {
		while ($row = $res->fetch_assoc()) $data[] = $row;
	}
	return $data;
}

switch ($resource) {
	case 'providers':
		echo json_encode(fetch_all_assoc($mysqli, 'SELECT * FROM v_core1_providers_active'));
		break;
	case 'routes':
		echo json_encode(fetch_all_assoc($mysqli, 'SELECT * FROM v_core1_routes_active'));
		break;
	case 'points':
		echo json_encode(fetch_all_assoc($mysqli, 'SELECT * FROM v_core1_service_points_active'));
		break;
	case 'tariffs':
		echo json_encode(fetch_all_assoc($mysqli, 'SELECT * FROM v_core1_tariffs_current'));
		break;
	case 'schedules':
		echo json_encode(fetch_all_assoc($mysqli, 'SELECT * FROM v_core1_schedules_current'));
		break;
	default:
		// compact snapshot for Core1
		echo json_encode([
			'providers' => fetch_all_assoc($mysqli, 'SELECT * FROM v_core1_providers_active'),
			'routes' => fetch_all_assoc($mysqli, 'SELECT * FROM v_core1_routes_active'),
			'points' => fetch_all_assoc($mysqli, 'SELECT * FROM v_core1_service_points_active'),
			'tariffs' => fetch_all_assoc($mysqli, 'SELECT * FROM v_core1_tariffs_current'),
			'schedules' => fetch_all_assoc($mysqli, 'SELECT * FROM v_core1_schedules_current'),
		]);
}

?>


