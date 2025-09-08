<?php
require_once 'db.php';

echo "<h1>Database Schema Check</h1>";

// Check users table structure
$result = $mysqli->query('DESCRIBE users');
echo "<h2>Users Table Structure:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>" . ($row['Key'] ?? '') . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . ($row['Extra'] ?? '') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check current users data
$result = $mysqli->query('SELECT id, username, role, is_active FROM users ORDER BY id');
echo "<h2>Current Users Data:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Username</th><th>Role (Raw)</th><th>Role Length</th><th>Active</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['username']}</td>";
    echo "<td>'{$row['role']}'</td>";
    echo "<td>" . strlen($row['role']) . "</td>";
    echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Try to fix the testprovider role
echo "<h2>Fix Test Provider Role</h2>";
$stmt = $mysqli->prepare("UPDATE users SET role = 'provider' WHERE username = 'testprovider'");
if ($stmt->execute()) {
    echo "<p>✅ Updated testprovider role to 'provider'</p>";
} else {
    echo "<p>❌ Failed to update role: " . $mysqli->error . "</p>";
}

// Check after update
$stmt = $mysqli->prepare("SELECT id, username, role FROM users WHERE username = 'testprovider'");
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo "<p><strong>After update:</strong> Username: {$row['username']}, Role: '{$row['role']}'</p>";
}

?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}
table {
    width: 100%;
    background: white;
    border-collapse: collapse;
    margin: 10px 0;
}
th {
    background: #007bff;
    color: white;
    padding: 8px;
    text-align: left;
}
td {
    padding: 6px;
    border: 1px solid #ddd;
}
tr:nth-child(even) {
    background: #f8f9fa;
}
</style>
