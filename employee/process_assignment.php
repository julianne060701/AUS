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
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $schedule_date = mysqli_real_escape_string($conn, $_POST['selected_date']);
    $schedule_time = mysqli_real_escape_string($conn, $_POST['schedule_time']);
    $service_type = mysqli_real_escape_string($conn, $_POST['service_type']);
    $products_to_install = mysqli_real_escape_string($conn, $_POST['products_to_install']);
    $quantity_to_install = (int)$_POST['quantity_to_install'];
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $status = 'Scheduled';

    // Validate required fields
    if (empty($installer_name) || empty($customer_name) || empty($contact_number) || 
        empty($address) || empty($schedule_date) || empty($schedule_time) || 
        empty($service_type) || empty($products_to_install) || $quantity_to_install < 1) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled and quantity must be at least 1.']);
        exit();
    }

    // Check if there's already a schedule for this installer at this time
    $check_query = "SELECT id FROM installer_schedules WHERE installer_name = '$installer_name' AND schedule_date = '$schedule_date' AND schedule_time = '$schedule_time'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'This installer already has a schedule at this time.']);
        exit();
    }

    // Start transaction for atomic operation
    mysqli_autocommit($conn, FALSE);
    
    // Insert the new schedule
    $query = "INSERT INTO installer_schedules (installer_name, customer_name, contact_number, address, schedule_date, schedule_time, service_type, products_to_install, quantity_to_install, notes, status) 
              VALUES ('$installer_name', '$customer_name', '$contact_number', '$address', '$schedule_date', '$schedule_time', '$service_type', '$products_to_install', '$quantity_to_install', '$notes', '$status')";
    
    if (mysqli_query($conn, $query)) {
        // If service type is Installation, update summary table
        if ($service_type === 'Installation') {
            // Get product ID from products_to_install (assuming it contains product ID)
            $product_id = (int)$products_to_install;
            
            // Check if product has enough total_quantity_sold in summary table
            $check_summary_query = "SELECT total_quantity_sold FROM product_quantity_sold_summary WHERE product_id = $product_id";
            $summary_result = mysqli_query($conn, $check_summary_query);
            
            if ($summary_result && mysqli_num_rows($summary_result) > 0) {
                $summary_data = mysqli_fetch_assoc($summary_result);
                $total_sold = $summary_data['total_quantity_sold'] ?? 0;
                
                if ($total_sold >= $quantity_to_install) {
                    // Update product_quantity_sold_summary table:
                    // 1. Subtract quantity_to_install from total_quantity_sold
                    // 2. Add quantity_to_install to total_quantity_to_install
                    $update_summary_query = "INSERT INTO product_quantity_sold_summary 
                                              (product_id, total_quantity_sold, total_quantity_to_install, last_updated) 
                                              VALUES ($product_id, $total_sold - $quantity_to_install, $quantity_to_install, NOW())
                                              ON DUPLICATE KEY UPDATE 
                                              total_quantity_sold = total_quantity_sold - $quantity_to_install,
                                              total_quantity_to_install = total_quantity_to_install + $quantity_to_install,
                                              last_updated = NOW()";
                    
                    if (mysqli_query($conn, $update_summary_query)) {
                        // Commit transaction
                        mysqli_commit($conn);
                        echo json_encode(['success' => true, 'message' => 'Schedule assigned and quantity updated successfully!']);
                    } else {
                        // Rollback transaction
                        mysqli_rollback($conn);
                        echo json_encode(['success' => false, 'message' => 'Error updating quantity summary: ' . mysqli_error($conn)]);
                    }
                } else {
                    // Rollback transaction
                    mysqli_rollback($conn);
                    echo json_encode(['success' => false, 'message' => 'Insufficient total quantity sold. Available: ' . $total_sold . ', Requested: ' . $quantity_to_install]);
                }
            } else {
                // Rollback transaction
                mysqli_rollback($conn);
                echo json_encode(['success' => false, 'message' => 'No quantity sold found for this product in summary table.']);
            }
        } else {
            // For non-installation services, just commit the schedule
            mysqli_commit($conn);
            echo json_encode(['success' => true, 'message' => 'Schedule assigned successfully!']);
        }
    } else {
        // Rollback transaction
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
    
    // Restore autocommit
    mysqli_autocommit($conn, TRUE);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>

