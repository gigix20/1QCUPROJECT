<?php
require_once __DIR__ . '/../../backend/auth.php';

// If user is not logged in, redirect user to login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: /1QCUPROJECT/views/auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ONEQCU | Homepage</title>
  <link rel="stylesheet" href="#">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
</style>

<body>

<h1>THIS THE LANDING PAGE O_O</h1>

    <div class="button-container">
        <a href="/1QCUPROJECT/backend/controllers/LogoutController.php" class="logout-btn">
            Logout
        </a>
    </div>


</body>
</html>
