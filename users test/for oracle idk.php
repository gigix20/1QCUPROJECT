<?php
$conn = oci_connect('SYSTEM', 'oracle_password', 'localhost/XE');

if (!$conn) {
    $e = oci_error();
    die("Database connection failed: " . $e['message']);
}
?>