<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../config/conn.php';

// Get schedule ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: installer_schedule.php");
    exit();
}

// Delete the schedule
$query = "DELETE FROM installer_schedules WHERE id = $id";
if (mysqli_query($conn, $query)) {
    header("Location: installer_schedule.php?message=Schedule deleted successfully&type=success");
} else {
    header("Location: installer_schedule.php?message=Error deleting schedule&type=danger");
}
exit();
?>

