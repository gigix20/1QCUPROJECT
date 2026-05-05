<?php
date_default_timezone_set('Asia/Manila');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$dsn = 'odbc:OracleXE';      
$username = 'rico';       // Replace these with your current user/schema on your ORACLE db 
$password = '1234';        

try {
    // Create PDO connection
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Test function
function testConnection(PDO $conn) {
    echo "Database Connection Successful!<br>";

    $stmt = $conn->query("SELECT * FROM USERS WHERE ROWNUM <= 1");

    if ($stmt) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo "Row fetched:<br>";
            print_r($row);
        } else {
            echo "No rows returned from USERS table.";
        }
    } else {
        echo "Query returned false.";
    }
}