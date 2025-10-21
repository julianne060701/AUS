<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../config/conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $installer_name = mysqli_real_escape_string($conn, $_POST['installer_name']);
    $schedule_id = (int)$_POST['schedule_id'];
    
    if (empty($installer_name) || $schedule_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid installer or schedule ID.']);
        exit();
    }
    
    // Check if installer already has a schedule at the same time
    $schedule_query = "SELECT schedule_date, schedule_time FROM installer_schedules WHERE id = $schedule_id";
    $schedule_result = mysqli_query($conn, $schedule_query);
    
    if (mysqli_num_rows($schedule_result) > 0) {
        $schedule = mysqli_fetch_assoc($schedule_result);
        $schedule_date = $schedule['schedule_date'];
        $schedule_time = $schedule['schedule_time'];
        
        // Check for conflicts
        $conflict_query = "SELECT id FROM installer_schedules WHERE installer_name = '$installer_name' AND schedule_date = '$schedule_date' AND schedule_time = '$schedule_time' AND id != $schedule_id";
        $conflict_result = mysqli_query($conn, $conflict_query);
        
        if (mysqli_num_rows($conflict_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'This installer already has a schedule at this time.']);
            exit();
        }
    }
    
    // Update the schedule with the installer
    $query = "UPDATE installer_schedules SET installer_name = '$installer_name' WHERE id = $schedule_id";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Installer assigned successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
