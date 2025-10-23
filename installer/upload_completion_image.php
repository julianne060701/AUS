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
$employee_list = isset($_POST['employee_list']) ? trim($_POST['employee_list']) : '';

// Validate input
if ($schedule_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid schedule ID']);
    exit();
}

if (empty($employee_list)) {
    echo json_encode(['success' => false, 'message' => 'Please provide the list of installation team members']);
    exit();
}

try {
    // Check if the schedule belongs to this installer and is in progress
    $check_query = "SELECT id, status FROM installer_schedules WHERE id = ? AND installer_name = ? AND status = 'In Progress'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("is", $schedule_id, $installer_name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Schedule not found, not assigned to you, or not in progress']);
        exit();
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['completion_image']) || $_FILES['completion_image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Please select an image to upload']);
        exit();
    }
    
    $file = $_FILES['completion_image'];
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a JPEG, PNG, or GIF image.']);
        exit();
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
        exit();
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = '../uploads/completion_images/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'completion_' . $schedule_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Update the schedule with completion image, employee list, and status
        $update_query = "UPDATE installer_schedules SET status = 'Completed', completion_image = ?, employee_list = ?, completed_at = NOW() WHERE id = ? AND installer_name = ?";
        $update_stmt = $conn->prepare($update_query);
        $relative_path = 'uploads/completion_images/' . $new_filename;
        $update_stmt->bind_param("ssis", $relative_path, $employee_list, $schedule_id, $installer_name);
        
        if ($update_stmt->execute()) {
            if ($update_stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Installation completed successfully with image uploaded!',
                    'image_path' => $relative_path
                ]);
            } else {
                // Remove uploaded file if database update failed
                unlink($upload_path);
                echo json_encode(['success' => false, 'message' => 'Failed to update schedule status']);
            }
        } else {
            // Remove uploaded file if database update failed
            unlink($upload_path);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
