<?php
session_start();

// Check if user is logged in as installer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'installer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include '../config/conn.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get the installer's full name
$user_id = $_SESSION['user_id'];
$user_query = "SELECT full_name FROM users WHERE id = ? AND role = 'installer'";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

if (!$user_data) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid installer']);
    exit();
}

$installer_name = $user_data['full_name'];

// Get POST data
$schedule_id = isset($_POST['schedule_id']) ? (int)$_POST['schedule_id'] : 0;
$new_status = isset($_POST['status']) ? trim($_POST['status']) : '';

// Validate input
if ($schedule_id <= 0 || empty($new_status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid schedule ID or status']);
    exit();
}

// Validate status
$valid_statuses = ['Scheduled', 'In Progress', 'Completed', 'Cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Check if the schedule belongs to this installer
    $check_query = "SELECT id FROM installer_schedules WHERE id = ? AND installer_name = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("is", $schedule_id, $installer_name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Schedule not found or not assigned to you']);
        exit();
    }
    
    // Update the status
    $update_query = "UPDATE installer_schedules SET status = ? WHERE id = ? AND installer_name = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sis", $new_status, $schedule_id, $installer_name);
    
    if ($update_stmt->execute()) {
        if ($update_stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
