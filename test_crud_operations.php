<?php
// Test script to verify admin CRUD operations
session_start();

echo "<h2>Admin CRUD Operations Test</h2>";

// Simulate admin login for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['login_time'] = time();
    echo "<p style='color: orange;'>⚠ Simulated admin login for testing</p>";
}

echo "<div style='font-family: Arial, sans-serif;'>";

// Test API endpoints
$tests = [
    'Get All Users' => 'api/users.php',
    'Get Single User (ID=1)' => 'api/users.php?id=1',
    'Change Password API' => 'api/change-password.php',
    'Add User API' => 'api/add-user.php',
    'Edit User API' => 'api/edit-user.php',
    'Delete User API' => 'api/delete-user.php'
];

echo "<h3>API Endpoint Status</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Test</th><th>Endpoint</th><th>Status</th><th>Action</th></tr>";

foreach ($tests as $testName => $endpoint) {
    $fileExists = file_exists($endpoint);
    $status = $fileExists ? "✓ File Exists" : "✗ File Missing";
    $statusColor = $fileExists ? "green" : "red";
    
    echo "<tr>";
    echo "<td><strong>{$testName}</strong></td>";
    echo "<td>{$endpoint}</td>";
    echo "<td style='color: {$statusColor};'>{$status}</td>";
    
    if ($fileExists) {
        if (strpos($endpoint, '?') !== false) {
            echo "<td><a href='{$endpoint}' target='_blank'>Test GET</a></td>";
        } else {
            echo "<td>POST only - Use form below</td>";
        }
    } else {
        echo "<td>-</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

// Test forms for POST operations
echo "<h3>Test Forms (POST Operations)</h3>";

// Add User Form
echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
echo "<h4>Add User Test</h4>";
echo "<form id='testAddUser' onsubmit='return testAddUser(event)'>";
echo "<table>";
echo "<tr><td>Username:</td><td><input type='text' name='username' value='testuser' required></td></tr>";
echo "<tr><td>Email:</td><td><input type='email' name='email' value='test@example.com' required></td></tr>";
echo "<tr><td>Password:</td><td><input type='password' name='password' value='password123' required></td></tr>";
echo "<tr><td>Role:</td><td><select name='role'><option value='user'>User</option><option value='admin'>Admin</option><option value='provider'>Provider</option></select></td></tr>";
echo "<tr><td>Active:</td><td><select name='is_active'><option value='1'>Active</option><option value='0'>Inactive</option></select></td></tr>";
echo "<tr><td colspan='2'><button type='submit'>Add User</button></td></tr>";
echo "</table>";
echo "</form>";
echo "<div id='addUserResult'></div>";
echo "</div>";

// Change Password Form
echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
echo "<h4>Change Password Test</h4>";
echo "<form id='testChangePassword' onsubmit='return testChangePassword(event)'>";
echo "<table>";
echo "<tr><td>Current Password:</td><td><input type='password' name='currentPassword' value='admin123' required></td></tr>";
echo "<tr><td>New Password:</td><td><input type='password' name='newPassword' value='newpassword123' required></td></tr>";
echo "<tr><td colspan='2'><button type='submit'>Change Password</button></td></tr>";
echo "</table>";
echo "</form>";
echo "<div id='changePasswordResult'></div>";
echo "</div>";

echo "</div>";

?>

<script>
async function testAddUser(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    try {
        const response = await fetch('api/add-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        document.getElementById('addUserResult').innerHTML = 
            `<div style="margin-top: 10px; padding: 10px; border-radius: 5px; background: ${result.success ? '#d4edda' : '#f8d7da'}; color: ${result.success ? '#155724' : '#721c24'};">
                <strong>${result.success ? 'Success' : 'Error'}:</strong> ${result.message}
                ${result.data ? '<br>User ID: ' + result.data.id : ''}
            </div>`;
            
    } catch (error) {
        document.getElementById('addUserResult').innerHTML = 
            `<div style="margin-top: 10px; padding: 10px; border-radius: 5px; background: #f8d7da; color: #721c24;">
                <strong>Error:</strong> ${error.message}
            </div>`;
    }
    
    return false;
}

async function testChangePassword(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    try {
        const response = await fetch('api/change-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        document.getElementById('changePasswordResult').innerHTML = 
            `<div style="margin-top: 10px; padding: 10px; border-radius: 5px; background: ${result.success ? '#d4edda' : '#f8d7da'}; color: ${result.success ? '#155724' : '#721c24'};">
                <strong>${result.success ? 'Success' : 'Error'}:</strong> ${result.message}
            </div>`;
            
    } catch (error) {
        document.getElementById('changePasswordResult').innerHTML = 
            `<div style="margin-top: 10px; padding: 10px; border-radius: 5px; background: #f8d7da; color: #721c24;">
                <strong>Error:</strong> ${error.message}
            </div>`;
    }
    
    return false;
}
</script>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 10px 0; }
    th { background: #f0f0f0; padding: 10px; }
    td { padding: 8px; }
    input, select { padding: 5px; margin: 2px; }
    button { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
    button:hover { background: #0056b3; }
</style>
