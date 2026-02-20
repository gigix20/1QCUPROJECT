<?php
require_once __DIR__ . '/../backend/auth.php';

// If user is not logged in, redirect user to login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: /1QCUPROJECT/views/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page</title>
</head>

<style>
    h1 {
        display: flex;
        justify-content: center;
    }

    .button-container {
        display: flex;
        justify-content: center;
    }

    .logout-btn {
        padding: 10px 20px;
        background-color: #f04;
        color: white;
        text-decoration: none;
        border-radius: 5px;
    }  

    .logout-btn:hover {
        background-color: #d03;
    }
</style> 
<!-- DELETE THIS STYLE PART AND MOVE IT TO styles/ folder  -->
<body>
    
    <h1>THIS THE LANDING PAGE O_O</h1>

    <!-- Logout button/link -->
    <div class="button-container">
        <a href="/1QCUPROJECT/backend/controllers/LogoutController.php" class="logout-btn">
            Logout
        </a>
    </div>

</body>
</html>