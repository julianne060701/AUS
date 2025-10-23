<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../config/conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $schedule_id = (int)$_POST['schedule_id'];
    $installer_name = mysqli_real_escape_string($conn, $_POST['installer_name']);
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $schedule_date = mysqli_real_escape_string($conn, $_POST['schedule_date']);
    $schedule_time = mysqli_real_escape_string($conn, $_POST['schedule_time']);
    $service_type = mysqli_real_escape_string($conn, $_POST['service_type']);
    $products_to_install = mysqli_real_escape_string($conn, $_POST['products_to_install']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Validate required fields
    if (empty($installer_name) || empty($customer_name) || empty($contact_number) || 
        empty($address) || empty($schedule_date) || empty($schedule_time) || 
        empty($service_type) || empty($products_to_install)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
        exit();
    }

    if ($schedule_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid schedule ID.']);
        exit();
    }

    // Check if there's already a schedule for this installer at this time (excluding current schedule)
    $check_query = "SELECT id FROM installer_schedules WHERE installer_name = '$installer_name' AND schedule_date = '$schedule_date' AND schedule_time = '$schedule_time' AND id != $schedule_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'This installer already has a schedule at this time.']);
        exit();
    }

    // Update the schedule
    $query = "UPDATE installer_schedules SET 
              installer_name = '$installer_name',
              customer_name = '$customer_name',
              contact_number = '$contact_number',
              address = '$address',
              schedule_date = '$schedule_date',
              schedule_time = '$schedule_time',
              service_type = '$service_type',
              products_to_install = '$products_to_install',
              notes = '$notes',
              status = '$status'
              WHERE id = $schedule_id";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Schedule updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>

