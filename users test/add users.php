<?php
include 'config/db.php';

if ($_POST) {
    $sql = "INSERT INTO users (full_name, email, department, role, status)
            VALUES (:name, :email, :dept, :role, 'ACTIVE')";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':name', $_POST['name']);
    oci_bind_by_name($stmt, ':email', $_POST['email']);
    oci_bind_by_name($stmt, ':dept', $_POST['department']);
    oci_bind_by_name($stmt, ':role', $_POST['role']);

    oci_execute($stmt);
    header("Location: users.php");
}
?>

<form method="POST">
    <h2>Add User</h2>
    <input name="name" placeholder="Full Name" required><br><br>
    <input name="email" placeholder="Email" required><br><br>
    <input name="department" placeholder="Department"><br><br>
    <input name="role" placeholder="Role"><br><br>
    <button type="submit">Save</button>
</form>