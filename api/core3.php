<?php
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

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
	case 'contracts':
		echo json_encode(fetch_all_assoc($mysqli, 'SELECT * FROM v_core3_contracts'));
		break;
	case 'sop_compliance':
		echo json_encode(fetch_all_assoc($mysqli, 'SELECT * FROM v_core3_sop_compliance'));
		break;
	case 'operational_summary':
		echo json_encode(fetch_all_assoc($mysqli, 'SELECT * FROM v_core3_operational_summary'));
		break;
	default:
		echo json_encode([
			'contracts' => fetch_all_assoc($mysqli, 'SELECT * FROM v_core3_contracts'),
			'sop_compliance' => fetch_all_assoc($mysqli, 'SELECT * FROM v_core3_sop_compliance'),
			'operational_summary' => fetch_all_assoc($mysqli, 'SELECT * FROM v_core3_operational_summary'),
		]);
}

?>


