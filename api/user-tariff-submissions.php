<?php
require_once __DIR__ . '/../db.php';

// Basic routing for user tariff submissions
$method = $_SERVER['REQUEST_METHOD'];

// Allow id via query string
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

switch ($method) {
	case 'GET':
		if ($id > 0) {
			$get = $mysqli->prepare('SELECT * FROM user_tariff_submissions WHERE id = ?');
			$get->bind_param('i', $id);
			$get->execute();
			$result = $get->get_result()->fetch_assoc();
			if (!$result) {
				return send_json(['error' => 'Not found'], 404);
			}
			return send_json($result);
		}

		// Get submissions by user (if specified) or all submissions (admin only)
		if ($userId > 0) {
			$res = $mysqli->prepare('SELECT uts.*, u.username as submitted_by_username FROM user_tariff_submissions uts LEFT JOIN users u ON uts.submitted_by_user_id = u.id WHERE uts.submitted_by_user_id = ? ORDER BY uts.created_at DESC');
			$res->bind_param('i', $userId);
			$res->execute();
			$result = $res->get_result();
		} else {
			$result = $mysqli->query('SELECT uts.*, u.username as submitted_by_username FROM user_tariff_submissions uts LEFT JOIN users u ON uts.submitted_by_user_id = u.id ORDER BY uts.created_at DESC');
		}
		$data = [];
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
		return send_json($data);

	case 'POST':
		$body = read_json_body();
		$errors = validate_user_submission($body);
		if ($errors) {
			return send_json(['errors' => $errors], 422);
		}

		$stmt = $mysqli->prepare('INSERT INTO user_tariff_submissions (name, category, base_rate, per_km_rate, per_hour_rate, priority_multiplier, service_area, justification, notes, status, submitted_by_user_id, submitted_by_username) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
		$stmt->bind_param(
			'ssddddssssis',
			$body['name'],
			$body['category'],
			$body['baseRate'],
			$body['perKmRate'],
			$body['perHourRate'],
			$body['priorityMultiplier'],
			$body['serviceArea'],
			$body['justification'],
			$body['notes'],
			$body['status'],
			$body['submittedByUserId'],
			$body['submittedByUsername']
		);
		if (!$stmt->execute()) {
			return send_json(['error' => 'Insert failed', 'details' => $stmt->error], 500);
		}

		$newId = $stmt->insert_id;
		return after_submission_response($newId);

	case 'PUT':
		if ($id <= 0) {
			return send_json(['error' => 'Missing id'], 400);
		}
		$body = read_json_body();
		
		// Check if this is a status-only update (approval/rejection)
		if (isset($body['statusOnly']) && $body['statusOnly'] === true) {
			// Only update status and review fields - require admin privileges
			session_start();
			if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
				return send_json(['error' => 'Admin access required for approval operations'], 403);
			}
			
			if (!isset($body['status']) || empty(trim($body['status']))) {
				return send_json(['error' => 'Status is required'], 400);
			}
			
			// Get current user ID if admin is reviewing
			$reviewedByUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
			$reviewNotes = isset($body['reviewNotes']) ? $body['reviewNotes'] : null;
			
			if ($reviewedByUserId) {
				$stmt = $mysqli->prepare('UPDATE user_tariff_submissions SET status=?, reviewed_by_user_id=?, reviewed_at=NOW(), review_notes=? WHERE id=?');
				$stmt->bind_param('sisi', $body['status'], $reviewedByUserId, $reviewNotes, $id);
			} else {
				$stmt = $mysqli->prepare('UPDATE user_tariff_submissions SET status=?, review_notes=? WHERE id=?');
				$stmt->bind_param('ssi', $body['status'], $reviewNotes, $id);
			}
			
			if (!$stmt->execute()) {
				return send_json(['error' => 'Status update failed', 'details' => $stmt->error], 500);
			}
			
			// If approved, automatically add to official tariffs table
			if ($body['status'] === 'Approved') {
				return auto_approve_to_tariffs($id);
			}
			
			return after_submission_response($id);
		}
		
		// Full update validation for regular submission edits
		$errors = validate_user_submission($body, false);
		if ($errors) {
			return send_json(['errors' => $errors], 422);
		}

		$stmt = $mysqli->prepare('UPDATE user_tariff_submissions SET name=?, category=?, base_rate=?, per_km_rate=?, per_hour_rate=?, priority_multiplier=?, service_area=?, justification=?, notes=?, status=? WHERE id=?');
		$stmt->bind_param(
			'ssddddssssi',
			$body['name'],
			$body['category'],
			$body['baseRate'],
			$body['perKmRate'],
			$body['perHourRate'],
			$body['priorityMultiplier'],
			$body['serviceArea'],
			$body['justification'],
			$body['notes'],
			$body['status'],
			$id
		);
		if (!$stmt->execute()) {
			return send_json(['error' => 'Update failed', 'details' => $stmt->error], 500);
		}

		return after_submission_response($id);

	case 'DELETE':
		if ($id <= 0) {
			return send_json(['error' => 'Missing id'], 400);
		}
		$stmt = $mysqli->prepare('DELETE FROM user_tariff_submissions WHERE id = ?');
		$stmt->bind_param('i', $id);
		if (!$stmt->execute()) {
			return send_json(['error' => 'Delete failed', 'details' => $stmt->error], 500);
		}
		return send_json(['success' => true]);

	default:
		return send_json(['error' => 'Method not allowed'], 405);
}

function validate_user_submission(array $body, bool $requireAll = true): array {
	$errors = [];
	$requiredStrings = ['name', 'category', 'justification'];
	if ($requireAll) {
		$requiredStrings[] = 'submittedByUsername';
	}
	
	foreach ($requiredStrings as $field) {
		if (!isset($body[$field]) || trim((string)$body[$field]) === '') {
			$errors[$field] = 'Required';
		}
	}
	
	$requiredNumbers = ['baseRate'];
	foreach ($requiredNumbers as $field) {
		if (!isset($body[$field]) || !is_numeric($body[$field]) || $body[$field] < 0) {
			$errors[$field] = 'Positive numeric value required';
		}
	}

	$optionalNumbers = ['perKmRate', 'perHourRate', 'priorityMultiplier'];
	foreach ($optionalNumbers as $field) {
		if (isset($body[$field]) && (!is_numeric($body[$field]) || $body[$field] < 0)) {
			$errors[$field] = 'Positive numeric value required';
		}
	}

	return $errors;
}

function auto_approve_to_tariffs(int $submissionId) {
	global $mysqli;
	
	// Get the submission details
	$get = $mysqli->prepare('SELECT * FROM user_tariff_submissions WHERE id = ?');
	$get->bind_param('i', $submissionId);
	$get->execute();
	$submission = $get->get_result()->fetch_assoc();
	
	if (!$submission) {
		send_json(['error' => 'Submission not found'], 404);
		return;
	}
	
	// Create a new official tariff from the approved submission
	$tariffStmt = $mysqli->prepare('
		INSERT INTO tariffs (
			name, category, base_rate, per_km_rate, per_hour_rate, 
			priority_multiplier, status, effective_date, expiry_date, notes, source, source_submission_id
		) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	');
	
	$effectiveDate = date('Y-m-d'); // Start today
	$expiryDate = date('Y-m-d', strtotime('+1 year')); // Expire in 1 year
	$status = 'Active';
	$notes = 'Auto-created from approved user submission by ' . ($submission['submitted_by_username'] ?? 'Unknown');
	$source = 'user_submission';
	
	$tariffStmt->bind_param('ssddddsssssi',
		$submission['name'],
		$submission['category'],
		$submission['base_rate'],
		$submission['per_km_rate'],
		$submission['per_hour_rate'],
		$submission['priority_multiplier'],
		$status,
		$effectiveDate,
		$expiryDate,
		$notes,
		$source,
		$submissionId
	);
	
	if ($tariffStmt->execute()) {
		$newTariffId = $tariffStmt->insert_id;
		$response = [
			'success' => true,
			'message' => 'Submission approved and tariff created successfully',
			'submission_id' => $submissionId,
			'new_tariff_id' => $newTariffId
		];
		send_json($response);
		return;
	} else {
		send_json(['error' => 'Failed to create tariff from approved submission', 'details' => $tariffStmt->error], 500);
		return;
	}
}

function after_submission_response(int $id) {
	global $mysqli;
	$get = $mysqli->prepare('SELECT * FROM user_tariff_submissions WHERE id = ?');
	$get->bind_param('i', $id);
	$get->execute();
	$row = $get->get_result()->fetch_assoc();
	send_json($row, 201);
}

?>
