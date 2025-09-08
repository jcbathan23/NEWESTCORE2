<?php
// Simple test script to verify provider-users-api.php CRUD operations
session_start();

// Set up a test admin session (you would normally login properly)
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "<h1>Testing Provider Users API CRUD Operations</h1>\n";
echo "<pre>\n";

// Test 1: List all providers
echo "TEST 1: List all providers\n";
echo "==========================\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/NEWCOREll/provider-users-api.php?action=list');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n\n";

$data = json_decode($response, true);
if ($data && isset($data['providers']) && !empty($data['providers'])) {
    $testProviderId = $data['providers'][0]['id'];
    echo "Found test provider ID: $testProviderId\n\n";
    
    // Test 2: Get specific provider
    echo "TEST 2: Get specific provider (ID: $testProviderId)\n";
    echo "=============================================\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/NEWCOREll/provider-users-api.php?action=get&id=$testProviderId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status: $httpCode\n";
    echo "Response: $response\n\n";
    
    // Test 3: Update provider
    echo "TEST 3: Update provider (ID: $testProviderId)\n";
    echo "========================================\n";
    $updateData = json_encode([
        'name' => 'Updated Provider Name',
        'contact_email' => 'updated@example.com',
        'service_area' => 'Updated Service Area',
        'notes' => 'Updated via API test'
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/NEWCOREll/provider-users-api.php?action=update&id=$testProviderId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $updateData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status: $httpCode\n";
    echo "Update Data: $updateData\n";
    echo "Response: $response\n\n";
    
    // Test 4: Toggle status
    echo "TEST 4: Toggle provider status (ID: $testProviderId)\n";
    echo "===============================================\n";
    $statusData = json_encode(['is_active' => false]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/NEWCOREll/provider-users-api.php?action=toggle-status&id=$testProviderId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $statusData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status: $httpCode\n";
    echo "Status Data: $statusData\n";
    echo "Response: $response\n\n";
    
} else {
    echo "No providers found for testing. Please create a provider account first.\n\n";
}

// Test 5: Get statistics
echo "TEST 5: Get statistics\n";
echo "===================\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/NEWCOREll/provider-users-api.php?action=stats');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n\n";

echo "All tests completed!\n";
echo "</pre>\n";

echo "<p><strong>Note:</strong> This is a basic test. For complete testing:</p>";
echo "<ul>";
echo "<li>Create a provider account through the registration system</li>";
echo "<li>Test all operations through the actual web interface</li>";
echo "<li>Verify database changes are persistent</li>";
echo "<li>Test error handling with invalid data</li>";
echo "</ul>";
?>
