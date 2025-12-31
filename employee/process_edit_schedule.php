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
    $quantity_to_install = (int)$_POST['quantity_to_install'];
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Validate required fields
    if (empty($installer_name) || empty($customer_name) || empty($contact_number) || 
        empty($address) || empty($schedule_date) || empty($schedule_time) || 
        empty($service_type) || empty($products_to_install) || $quantity_to_install < 1) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled and quantity must be at least 1.']);
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

    // Get the old schedule data to handle inventory changes
    $old_schedule_query = "SELECT service_type, products_to_install, quantity_to_install FROM installer_schedules WHERE id = $schedule_id";
    $old_schedule_result = mysqli_query($conn, $old_schedule_query);
    
    if (!$old_schedule_result || mysqli_num_rows($old_schedule_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Schedule not found.']);
        exit();
    }
    
    $old_schedule = mysqli_fetch_assoc($old_schedule_result);
    $old_service_type = $old_schedule['service_type'];
    $old_products_to_install = $old_schedule['products_to_install'];
    $old_quantity_to_install = $old_schedule['quantity_to_install'];

    // Start transaction for atomic operation
    mysqli_autocommit($conn, FALSE);

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
              quantity_to_install = '$quantity_to_install',
              notes = '$notes',
              status = '$status'
              WHERE id = $schedule_id";
    
    if (mysqli_query($conn, $query)) {
        // Handle quantity changes for Installation service type using summary table
        if ($old_service_type === 'Installation' || $service_type === 'Installation') {
            $old_product_id = (int)$old_products_to_install;
            $new_product_id = (int)$products_to_install;
            
            // If old service was Installation, restore the old quantity in summary table
            if ($old_service_type === 'Installation' && $old_product_id > 0) {
                // Restore: add back to total_quantity_sold, subtract from total_quantity_to_install
                $restore_summary_query = "UPDATE product_quantity_sold_summary 
                                          SET total_quantity_sold = total_quantity_sold + $old_quantity_to_install,
                                              total_quantity_to_install = total_quantity_to_install - $old_quantity_to_install,
                                              last_updated = NOW()
                                          WHERE product_id = $old_product_id";
                
                if (!mysqli_query($conn, $restore_summary_query)) {
                    mysqli_rollback($conn);
                    mysqli_autocommit($conn, TRUE);
                    echo json_encode(['success' => false, 'message' => 'Error restoring old quantity in summary: ' . mysqli_error($conn)]);
                    exit();
                }
            }
            
            // If new service is Installation, update summary table
            if ($service_type === 'Installation' && $new_product_id > 0) {
                // Check if product has enough total_quantity_sold in summary table
                $check_summary_query = "SELECT total_quantity_sold FROM product_quantity_sold_summary WHERE product_id = $new_product_id";
                $summary_result = mysqli_query($conn, $check_summary_query);
                
                if ($summary_result && mysqli_num_rows($summary_result) > 0) {
                    $summary_data = mysqli_fetch_assoc($summary_result);
                    $total_sold = $summary_data['total_quantity_sold'] ?? 0;
                    
                    if ($total_sold >= $quantity_to_install) {
                        // Update: subtract from total_quantity_sold, add to total_quantity_to_install
                        $update_summary_query = "UPDATE product_quantity_sold_summary 
                                                  SET total_quantity_sold = total_quantity_sold - $quantity_to_install,
                                                      total_quantity_to_install = total_quantity_to_install + $quantity_to_install,
                                                      last_updated = NOW()
                                                  WHERE product_id = $new_product_id";
                        
                        if (!mysqli_query($conn, $update_summary_query)) {
                            mysqli_rollback($conn);
                            mysqli_autocommit($conn, TRUE);
                            echo json_encode(['success' => false, 'message' => 'Error updating quantity summary: ' . mysqli_error($conn)]);
                            exit();
                        }
                    } else {
                        mysqli_rollback($conn);
                        mysqli_autocommit($conn, TRUE);
                        echo json_encode(['success' => false, 'message' => 'Insufficient total quantity sold. Available: ' . $total_sold . ', Requested: ' . $quantity_to_install]);
                        exit();
                    }
                } else {
                    mysqli_rollback($conn);
                    mysqli_autocommit($conn, TRUE);
                    echo json_encode(['success' => false, 'message' => 'No quantity sold found for this product in summary table.']);
                    exit();
                }
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        mysqli_autocommit($conn, TRUE);
        echo json_encode(['success' => true, 'message' => 'Schedule updated and quantity sold adjusted successfully!']);
    } else {
        // Rollback transaction
        mysqli_rollback($conn);
        mysqli_autocommit($conn, TRUE);
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>

