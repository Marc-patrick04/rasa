<?php
/**
 * Test Script for Nomination Form
 * 
 * This script tests the nomination form functionality with Neon PostgreSQL
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Nomination Form</title>
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
        .test-form {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #218838;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Test Nomination Form</h1>
            <p>Testing nomination form with Neon PostgreSQL</p>
        </div>";

// Test database connection
echo "<div class='status-box info'>";
echo "<h3>Database Connection Test</h3>";
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test connection
    $result = $db->query("SELECT version()");
    $version = $result->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>✅ Connection Successful!</strong></p>";
    echo "<p><strong>Database Version:</strong> " . $version['version'] . "</p>";
    
} catch (PDOException $e) {
    echo "<p><strong>❌ Connection Failed:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
    exit;
}
echo "</div>";

// Test positions availability
echo "<div class='status-box'>";
echo "<h3>Available Positions</h3>";
try {
    $positions = $db->query("SELECT * FROM positions WHERE is_active = true ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($positions)) {
        echo "<p class='error'>No active positions found. Please add positions first.</p>";
    } else {
        echo "<p class='success'>" . count($positions) . " active positions found:</p>";
        echo "<ul>";
        foreach ($positions as $position) {
            echo "<li><strong>" . htmlspecialchars($position['name']) . "</strong>";
            if (!empty($position['description'])) {
                echo " - " . htmlspecialchars($position['description']);
            }
            echo "</li>";
        }
        echo "</ul>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>Error fetching positions: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_submit'])) {
    echo "<div class='status-box'>";
    echo "<h3>Form Submission Test</h3>";
    
    try {
        $position_id = $_POST['position_id'];
        $full_name = $_POST['full_name'];
        $student_id = $_POST['student_id'];
        $year_of_study = $_POST['year_of_study'];
        $phone_number = $_POST['phone_number'];
        $manifesto = $_POST['manifesto'];
        $nomination_type = 'self';
        $nominated_by = $full_name;
        
        // Test the exact same query as in nominate.php
        $stmt = $db->prepare("INSERT INTO candidates (position_id, full_name, student_id, year_of_study, phone_number, manifesto, nomination_type, nominated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$position_id, $full_name, $student_id, $year_of_study, $phone_number, $manifesto, $nomination_type, $nominated_by])) {
            echo "<p class='success'>✅ Test nomination submitted successfully!</p>";
            echo "<p><strong>Year of Study Value:</strong> " . $year_of_study . " (Type: " . gettype($year_of_study) . ")</p>";
            echo "<p><strong>Position ID:</strong> " . $position_id . "</p>";
            echo "<p><strong>Candidate:</strong> " . htmlspecialchars($full_name) . "</p>";
        } else {
            echo "<p class='error'>❌ Test nomination failed!</p>";
            $errorInfo = $stmt->errorInfo();
            echo "<p><strong>Error:</strong> " . $errorInfo[2] . "</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Test nomination failed with exception:</p>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
    }
    echo "</div>";
}

// Test form
echo "<div class='test-form'>";
echo "<h3>Test Nomination Form</h3>";
echo "<p>This form tests the exact same functionality as the real nomination form.</p>";

// Get positions for the form
$positions = $db->query("SELECT * FROM positions WHERE is_active = true ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

echo "<form method='POST' action=''>
    <input type='hidden' name='test_submit' value='1'>
    
    <div class='form-group'>
        <label for='position_id'>Position:</label>
        <select name='position_id' id='position_id' required>
            <option value=''>-- Select a Position --</option>";
            foreach ($positions as $position) {
                echo "<option value='" . $position['id'] . "'>" . htmlspecialchars($position['name']) . "</option>";
            }
echo "</select>
    </div>
    
    <div class='form-group'>
        <label for='full_name'>Full Name:</label>
        <input type='text' name='full_name' id='full_name' value='Test Candidate' required>
    </div>
    
    <div class='form-group'>
        <label for='student_id'>Student ID/Church:</label>
        <input type='text' name='student_id' id='student_id' value='Test Church' required>
    </div>
    
    <div class='form-group'>
        <label for='year_of_study'>Year of Study:</label>
        <select name='year_of_study' id='year_of_study' required>
            <option value=''>-- Select Year --</option>
            <option value='1A'>Year 1A</option>
            <option value='1B'>Year 1B</option>
            <option value='2'>Year 2</option>
        </select>
    </div>
    
    <div class='form-group'>
        <label for='phone_number'>Phone Number:</label>
        <input type='tel' name='phone_number' id='phone_number' value='0781234567'>
    </div>
    
    <div class='form-group'>
        <label for='manifesto'>Manifesto:</label>
        <textarea name='manifesto' id='manifesto' rows='4' required>Test manifesto for database connection testing.</textarea>
    </div>
    
    <div style='text-align: center; margin-top: 20px;'>
        <button type='submit' class='btn'>Test Nomination</button>
        <a href='nominate.php' class='btn btn-danger' style='margin-left: 10px;'>Go to Real Form</a>
    </div>
</form>";
echo "</div>";

echo "</div>
</body>
</html>";
?>