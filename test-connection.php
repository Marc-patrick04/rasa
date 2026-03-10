<?php
/**
 * Database Connection Test Script
 * 
 * This script tests the database connection for both local and Neon PostgreSQL
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        .status-box {
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        .success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        .details {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .code {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 10px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Database Connection Test</h1>
            <p>Testing connection to your configured database</p>
        </div>";

// Test environment detection
echo "<div class='status-box info'>";
echo "<h3>Environment Detection</h3>";
$is_production = getenv('ENVIRONMENT') === 'production' || getenv('DATABASE_URL') !== false;
$env_type = $is_production ? 'Production (Neon PostgreSQL)' : 'Local Development';
echo "<p><strong>Detected Environment:</strong> $env_type</p>";

if ($is_production && getenv('DATABASE_URL')) {
    echo "<p><strong>DATABASE_URL:</strong> " . substr(getenv('DATABASE_URL'), 0, 50) . "...</p>";
} else {
    echo "<p><strong>Local Database:</strong> " . DB_HOST . " (port " . DB_PORT . ")</p>";
}
echo "</div>";

// Test database connection
echo "<div class='status-box'>";
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test connection
    $result = $db->query("SELECT version()");
    $version = $result->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='success'>";
    echo "<h3>✅ Connection Successful!</h3>";
    echo "<p><strong>Database Version:</strong> " . $version['version'] . "</p>";
    echo "<p><strong>Connection Type:</strong> " . ($is_production ? 'Neon PostgreSQL' : 'Local PostgreSQL') . "</p>";
    echo "<p><strong>SSL Mode:</strong> " . DB_SSLMODE . "</p>";
    echo "</div>";
    
    // Test table existence
    echo "<div class='details'>";
    echo "<h4>Table Status:</h4>";
    $tables = ['users', 'positions', 'candidates', 'previous_leaders'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table");
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>✓ $table: {$count['count']} records</p>";
        } catch (PDOException $e) {
            echo "<p>✗ $table: Table not found or error</p>";
        }
    }
    echo "</div>";

} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Connection Failed!</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
    
    // Provide troubleshooting info
    echo "<div class='details'>";
    echo "<h4>Troubleshooting:</h4>";
    if ($is_production) {
        echo "<p><strong>For Neon PostgreSQL:</strong></p>";
        echo "<ul>";
        echo "<li>Check that your DATABASE_URL is correct</li>";
        echo "<li>Verify your Neon PostgreSQL project is active</li>";
        echo "<li>Ensure the database exists and has the correct schema</li>";
        echo "<li>Check your Neon dashboard for connection status</li>";
        echo "</ul>";
    } else {
        echo "<p><strong>For Local PostgreSQL:</strong></p>";
        echo "<ul>";
        echo "<li>Ensure PostgreSQL server is running</li>";
        echo "<li>Check database credentials in config.php</li>";
        echo "<li>Verify the database 'rasa_db' exists</li>";
        echo "<li>Import the schema from dd.sql file</li>";
        echo "</ul>";
    }
    echo "</div>";
    echo "</div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='index.php' class='btn'>Return to Application</a>";
echo "<a href='setup-env.php' class='btn' style='background-color: #28a745; margin-left: 10px;'>Setup Environment</a>";
echo "</div>";

echo "</div>
</body>
</html>";
?>