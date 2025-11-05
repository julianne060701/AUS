<?php
// Set PHP timezone to Philippine time
date_default_timezone_set('Asia/Manila');

$host = "localhost";
$db = "bigasandb";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set MySQL timezone to Philippine time
$conn->query("SET time_zone = '+08:00'");

// Optional: Set session timezone for consistency
$conn->query("SET SESSION time_zone = '+08:00'");
?>