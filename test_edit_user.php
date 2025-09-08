<?php
// Comprehensive test for admin edit user functionality
session_start();

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';  
$_SESSION['login_time'] = time();

require_once 'db.php';
require_once 'auth.php';

echo "<h2>Admin Edit User Functionality Test</h2>";

// Test 1: Get a user to edit
echo "<h3>Test 1: Get User Data</h3>";
$testUserId = 1; // Admin user for testing

if ($mysqli && !$mysqli->connect_error) {
    $stmt = $mysqli->prepare("SELECT id, username, email, role, is_active, last_login, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $testUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p style='color: green;'>✓ Successfully retrieved user data:</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>{$user['id']}</td></tr>";
        echo "<tr><td>Username</td><td>{$user['username']}</td></tr>";
        echo "<tr><td>Email</td><td>{$user['email']}</td></tr>";
        echo "<tr><td>Role</td><td>{$user['role']}</td></tr>";
        echo "<tr><td>Status</td><td>" . ($user['is_active'] ? 'Active' : 'Inactive') . "</td></tr>";
        echo "</table>";
        
        // Test the API endpoint directly
        echo "<h3>Test 2: API GET User Endpoint</h3>";
        echo "<p>Testing: <code>api/users.php?id={$testUserId}</code></p>";
        
        // Reset and test API call
        unset($_GET['id']);
        $_GET['id'] = $testUserId;
        
        ob_start();
        include 'api/users.php';
        $apiOutput = ob_get_clean();
        
        if ($apiOutput) {
            $apiUser = json_decode($apiOutput, true);
            if ($apiUser && isset($apiUser['id'])) {
                echo "<p style='color: green;'>✓ API returned valid user data</p>";
                echo "<pre>" . htmlspecialchars(json_encode($apiUser, JSON_PRETTY_PRINT)) . "</pre>";
            } else {
                echo "<p style='color: red;'>✗ API returned invalid data: " . htmlspecialchars($apiOutput) . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ API returned no output</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ No user found with ID {$testUserId}</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
}

// Test 3: Edit user simulation
echo "<h3>Test 3: Edit User Simulation</h3>";
$testEditData = [
    'id' => $testUserId,
    'username' => 'admin',
    'email' => 'admin@slate.com',
    'role' => 'admin',
    'is_active' => 1
];

echo "<p>Simulating edit user API call with data:</p>";
echo "<pre>" . htmlspecialchars(json_encode($testEditData, JSON_PRETTY_PRINT)) . "</pre>";

// Create a test for the edit API
echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
echo "<h4>Live Edit User Test</h4>";
echo "<p>Select a user to edit:</p>";

// Show available users
if ($mysqli && !$mysqli->connect_error) {
    $result = $mysqli->query("SELECT id, username, email, role, is_active FROM users ORDER BY id");
    echo "<select id='testUserId' onchange='loadUserForEdit()'>";
    echo "<option value=''>Select a user...</option>";
    while ($row = $result->fetch_assoc()) {
        $statusText = $row['is_active'] ? 'Active' : 'Inactive';
        echo "<option value='{$row['id']}'>{$row['id']} - {$row['username']} ({$row['role']}) - {$statusText}</option>";
    }
    echo "</select>";
}

echo "<div id='userEditForm' style='display: none; margin-top: 15px;'>";
echo "<table>";
echo "<tr><td>ID:</td><td><input type='hidden' id='editId'><span id='displayId'></span></td></tr>";
echo "<tr><td>Username:</td><td><input type='text' id='editUsername' style='padding: 5px; width: 200px;'></td></tr>";
echo "<tr><td>Email:</td><td><input type='email' id='editEmail' style='padding: 5px; width: 200px;'></td></tr>";
echo "<tr><td>Role:</td><td>";
echo "<select id='editRole' style='padding: 5px;'>";
echo "<option value='admin'>Admin</option>";
echo "<option value='user'>User</option>";
echo "<option value='provider'>Provider</option>";
echo "</select>";
echo "</td></tr>";
echo "<tr><td>Status:</td><td>";
echo "<select id='editStatus' style='padding: 5px;'>";
echo "<option value='1'>Active</option>";
echo "<option value='0'>Inactive</option>";
echo "</select>";
echo "</td></tr>";
echo "<tr><td colspan='2'><button onclick='testEditUser()' style='padding: 8px 15px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer;'>Update User</button></td></tr>";
echo "</table>";
echo "</div>";

echo "<div id='editResult'></div>";
echo "</div>";

echo "<h3>Test 4: Frontend Integration</h3>";
echo "<p>Testing modal and JavaScript integration:</p>";
echo "<div>";
echo "<button onclick='testModalOpen()' style='padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; margin: 5px;'>Test Edit Modal</button>";
echo "<button onclick='testApiCall()' style='padding: 8px 15px; background: #17a2b8; color: white; border: none; border-radius: 3px; cursor: pointer; margin: 5px;'>Test API Call</button>";
echo "</div>";
echo "<div id='testResults' style='margin-top: 15px;'></div>";
?>

<script>
let currentUser = null;

async function loadUserForEdit() {
    const userId = document.getElementById('testUserId').value;
    if (!userId) {
        document.getElementById('userEditForm').style.display = 'none';
        return;
    }
    
    try {
        const response = await fetch(`api/users.php?id=${userId}`);
        const user = await response.json();
        
        if (user && user.id) {
            currentUser = user;
            document.getElementById('editId').value = user.id;
            document.getElementById('displayId').textContent = user.id;
            document.getElementById('editUsername').value = user.username;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editRole').value = user.role;
            document.getElementById('editStatus').value = user.is_active ? '1' : '0';
            document.getElementById('userEditForm').style.display = 'block';
        } else {
            alert('Error loading user: ' + JSON.stringify(user));
        }
    } catch (error) {
        alert('Error loading user: ' + error.message);
    }
}

async function testEditUser() {
    const data = {
        id: document.getElementById('editId').value,
        username: document.getElementById('editUsername').value,
        email: document.getElementById('editEmail').value,
        role: document.getElementById('editRole').value,
        is_active: document.getElementById('editStatus').value
    };
    
    try {
        const response = await fetch('api/edit-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        const resultDiv = document.getElementById('editResult');
        resultDiv.innerHTML = `
            <div style="margin-top: 10px; padding: 10px; border-radius: 5px; background: ${result.success ? '#d4edda' : '#f8d7da'}; color: ${result.success ? '#155724' : '#721c24'};">
                <strong>${result.success ? 'Success' : 'Error'}:</strong> ${result.message}
                ${result.data ? '<br>Updated: ' + JSON.stringify(result.data) : ''}
            </div>
        `;
        
    } catch (error) {
        document.getElementById('editResult').innerHTML = `
            <div style="margin-top: 10px; padding: 10px; border-radius: 5px; background: #f8d7da; color: #721c24;">
                <strong>Error:</strong> ${error.message}
            </div>
        `;
    }
}

function testModalOpen() {
    document.getElementById('testResults').innerHTML = `
        <div style="padding: 10px; background: #e7f3ff; border-radius: 5px; margin: 10px 0;">
            <strong>Modal Test:</strong> Check browser console for any JavaScript errors when opening edit modal.
            <br><small>This tests if the modal HTML and JavaScript are properly loaded.</small>
        </div>
    `;
    
    // Check if modal elements exist
    const modal = document.getElementById('editUserModal');
    const form = document.getElementById('editUserForm');
    
    if (modal && form) {
        document.getElementById('testResults').innerHTML += `
            <div style="padding: 10px; background: #d4edda; color: #155724; border-radius: 5px; margin: 5px 0;">
                ✓ Edit User Modal and Form elements found in DOM
            </div>
        `;
    } else {
        document.getElementById('testResults').innerHTML += `
            <div style="padding: 10px; background: #f8d7da; color: #721c24; border-radius: 5px; margin: 5px 0;">
                ✗ Edit User Modal or Form elements NOT found in DOM
                <br>Modal: ${modal ? 'Found' : 'Not Found'}
                <br>Form: ${form ? 'Found' : 'Not Found'}
            </div>
        `;
    }
}

async function testApiCall() {
    try {
        const response = await fetch('api/users.php?id=1');
        const result = await response.json();
        
        document.getElementById('testResults').innerHTML = `
            <div style="padding: 10px; background: #d4edda; color: #155724; border-radius: 5px; margin: 10px 0;">
                <strong>API Test Success:</strong> Loaded user data
                <br><pre>${JSON.stringify(result, null, 2)}</pre>
            </div>
        `;
    } catch (error) {
        document.getElementById('testResults').innerHTML = `
            <div style="padding: 10px; background: #f8d7da; color: #721c24; border-radius: 5px; margin: 10px 0;">
                <strong>API Test Failed:</strong> ${error.message}
            </div>
        `;
    }
}
</script>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 10px 0; }
    th { background: #f0f0f0; padding: 8px; }
    td { padding: 6px; border: 1px solid #ddd; }
    h3 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
    h4 { color: #666; margin-top: 20px; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    select, input { margin: 2px; }
</style>
