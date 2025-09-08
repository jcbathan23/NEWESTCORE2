<?php
// Simple DB connector for CORE II
// Adjust credentials as needed for your WAMP/MySQL setup

$host = 'localhost';
$user = 'root'; // or your MySQL username
$pass = '';     // or your MySQL password
$dbname = 'core2';

// Primary connection
$db = new mysqli($host, $user, $pass, $dbname);
if ($db->connect_error) {
	die('Database connection failed: ' . $db->connect_error);
}

// Secondary handle (procedural-style usage convenience)
$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_errno) {
	error_log('Database connection failed: ' . $mysqli->connect_error);
	$mysqli = null;
}

// Minimal helpers used by API endpoints
if (!function_exists('send_json')) {
	function send_json($data, int $status = 200): void {
		header('Content-Type: application/json');
		http_response_code($status);
		echo json_encode($data);
		exit;
	}
}

if (!function_exists('read_json_body')) {
	function read_json_body(): array {
		$raw = file_get_contents('php://input');
		if ($raw === false || $raw === '') {
			return [];
		}
		$decoded = json_decode($raw, true);
		return is_array($decoded) ? $decoded : [];
	}
}

?>

