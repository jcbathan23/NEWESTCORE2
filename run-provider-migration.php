<?php
/**
 * Run Provider Dashboard Migration
 * This script will execute the migration to add provider-specific data
 */

require_once 'db.php';

echo "<h2>Provider Dashboard Migration</h2>\n";
echo "<pre>\n";

try {
    // Read the migration SQL file
    $migrationSQL = file_get_contents('provider-dashboard-migration.sql');
    
    if ($migrationSQL === false) {
        throw new Exception("Could not read migration file");
    }
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $migrationSQL)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    echo "Executing " . count($statements) . " migration statements...\n\n";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $i => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        echo "Statement " . ($i + 1) . ": ";
        
        try {
            $result = $mysqli->query($statement);
            if ($result) {
                echo "SUCCESS\n";
                $successCount++;
            } else {
                echo "ERROR: " . $mysqli->error . "\n";
                $errorCount++;
            }
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Migration Summary:\n";
    echo "- Successful statements: $successCount\n";
    echo "- Failed statements: $errorCount\n";
    
    if ($errorCount === 0) {
        echo "\n✅ Migration completed successfully!\n";
        echo "\nYou can now log in as:\n";
        echo "Username: testprovider\n";
        echo "Password: password\n";
        echo "Email: provider@test.com\n";
        echo "Role: provider\n";
    } else {
        echo "\n⚠️  Migration completed with some errors.\n";
    }
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Provider Dashboard Migration</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background-color: #f5f5f5; 
        }
        pre { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        h2 { 
            color: #333; 
        }
    </style>
</head>
<body>
    <p><strong>After running this migration:</strong></p>
    <ol>
        <li>Visit the provider dashboard: <a href="provider-dashboard.php">provider-dashboard.php</a></li>
        <li>Log in with the test provider credentials shown above</li>
        <li>Verify that the dashboard shows real data from the modules</li>
    </ol>
</body>
</html>
