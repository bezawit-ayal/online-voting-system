<?php
/**
 * Database Setup and Initialization Script
 * Run this file once to initialize the database
 */

require_once 'config/db.php';

$sql = file_get_contents('database/schema.sql');

// Split SQL statements and execute
$statements = array_filter(array_map('trim', explode(';', $sql)));

echo "<h1>Database Initialization</h1>";

try {
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            if ($conn->query($statement) === true) {
                echo "<p style='color: green;'>✓ " . substr($statement, 0, 50) . "...</p>";
            } else {
                echo "<p style='color: orange;'>⚠ " . substr($statement, 0, 50) . "... (May already exist)</p>";
            }
        }
    }
    
    echo "<h2 style='color: green; margin-top: 2rem;'>✓ Database initialized successfully!</h2>";
    echo "<p><strong>Default Admin Credentials:</strong></p>";
    echo "<ul>";
    echo "<li>Username: <code>admin</code></li>";
    echo "<li>Password: <code>admin123</code></li>";
    echo "<li>Email: <code>admin@voting.com</code></li>";
    echo "</ul>";
    echo "<p><a href='login.php' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 1rem;'>Go to Login Page</a></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>
