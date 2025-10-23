<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../config/conn.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $schedule_id = (int)$_GET['id'];
    
    if ($schedule_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid schedule ID.']);
        exit();
    }
    
    $query = "SELECT * FROM installer_schedules WHERE id = $schedule_id";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $schedule = mysqli_fetch_assoc($result);
        echo json_encode(['success' => true, 'data' => $schedule]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Schedule not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Schedule ID is required.']);
}
?>

