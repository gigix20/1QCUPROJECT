<?php
require_once 'backend/config/database.php'; 

try {
    // ADMIN CREDENTIALS
    $fullName    = 'System Administrator';
    $email       = 'admin@1qcumgmt.com';
    $department  = 'IT';
    $employeeId  = 'ADM-001';
    $rawPassword = 'passwords'; 
    $isVerified  = 1;
    $role        = 'Admin';

    $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);

    $sql = "INSERT INTO USERS (
                FULL_NAME, 
                EMAIL, 
                DEPARTMENT, 
                EMPLOYEE_ID, 
                PASSWORD, 
                IS_VERIFIED, 
                ROLE
            ) VALUES (
                :full_name, 
                :email, 
                :department, 
                :employee_id, 
                :password, 
                :is_verified, 
                :role
            )";

    $stmt = $conn->prepare($sql);

    $stmt->bindParam(':full_name', $fullName);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':department', $department);
    $stmt->bindParam(':employee_id', $employeeId);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':is_verified', $isVerified);
    $stmt->bindParam(':role', $role);

    $stmt->execute();

    echo "<h2>Admin Account Created Successfully!</h2>";
    echo "<b>Email:</b> " . htmlspecialchars($email) . "<br>";
    echo "<b>Password:</b> " . htmlspecialchars($rawPassword) . "<br>";
    echo "<p>You can now close this page.</p>";

} catch (PDOException $e) {
    // Check if the error is due to a duplicate Email or Employee ID
    if ($e->getCode() == '23000' || strpos($e->getMessage(), 'unique constraint') !== false) {
        echo "Error: An account with that Email or Employee ID already exists.";
    } else {
        echo "Database Error: " . $e->getMessage();
    }
}
?>