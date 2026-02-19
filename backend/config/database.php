
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$dsn = 'odbc:OracleXE';      
$username = 'rico';        // Replace with your created user in sql developer
$password = '1234';        // Password for that new user you created

try {
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connection successful!<br>";

    // Test query (should show users data if there are any on the DB)
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

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
