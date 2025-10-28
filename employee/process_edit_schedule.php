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
        // Handle quantity sold changes for Installation service type
        if ($old_service_type === 'Installation' || $service_type === 'Installation') {
            $old_product_id = (int)$old_products_to_install;
            $new_product_id = (int)$products_to_install;
            
            // If old service was Installation, restore the old quantity sold
            if ($old_service_type === 'Installation') {
                // Find the most recent sale record to add back to
                $find_old_sale_query = "SELECT sale_id, quantity_sold FROM aircon_sales WHERE product_id = $old_product_id ORDER BY sale_id DESC LIMIT 1";
                $find_old_result = mysqli_query($conn, $find_old_sale_query);
                
                if ($find_old_result && mysqli_num_rows($find_old_result) > 0) {
                    $old_sale_record = mysqli_fetch_assoc($find_old_result);
                    $old_sale_id = $old_sale_record['sale_id'];
                    $old_current_sold = $old_sale_record['quantity_sold'];
                    
                    // Add back the old quantity sold
                    $restore_quantity_sold = $old_current_sold + $old_quantity_to_install;
                    $restore_sale_query = "UPDATE aircon_sales SET quantity_sold = $restore_quantity_sold WHERE sale_id = $old_sale_id";
                    
                    if (!mysqli_query($conn, $restore_sale_query)) {
                        mysqli_rollback($conn);
                        mysqli_autocommit($conn, TRUE);
                        echo json_encode(['success' => false, 'message' => 'Error restoring old quantity sold: ' . mysqli_error($conn)]);
                        exit();
                    }
                }
            }
            
            // If new service is Installation, subtract the new quantity sold
            if ($service_type === 'Installation') {
                // Check if product has enough quantity sold to subtract from
                $check_sales_query = "SELECT SUM(quantity_sold) as total_sold FROM aircon_sales WHERE product_id = $new_product_id";
                $sales_result = mysqli_query($conn, $check_sales_query);
                
                if ($sales_result && mysqli_num_rows($sales_result) > 0) {
                    $sales_data = mysqli_fetch_assoc($sales_result);
                    $total_sold = $sales_data['total_sold'] ?? 0;
                    
                    if ($total_sold >= $quantity_to_install) {
                        // Find the most recent sale record to subtract from
                        $find_sale_query = "SELECT sale_id, quantity_sold FROM aircon_sales WHERE product_id = $new_product_id ORDER BY sale_id DESC LIMIT 1";
                        $find_result = mysqli_query($conn, $find_sale_query);
                        
                        if ($find_result && mysqli_num_rows($find_result) > 0) {
                            $sale_record = mysqli_fetch_assoc($find_result);
                            $sale_id = $sale_record['sale_id'];
                            $current_sold = $sale_record['quantity_sold'];
                            
                            if ($current_sold >= $quantity_to_install) {
                                // Subtract from the most recent sale record
                                $new_quantity_sold = $current_sold - $quantity_to_install;
                                $update_sale_query = "UPDATE aircon_sales SET quantity_sold = $new_quantity_sold WHERE sale_id = $sale_id";
                                
                                if (!mysqli_query($conn, $update_sale_query)) {
                                    mysqli_rollback($conn);
                                    mysqli_autocommit($conn, TRUE);
                                    echo json_encode(['success' => false, 'message' => 'Error updating quantity sold: ' . mysqli_error($conn)]);
                                    exit();
                                }
                            } else {
                                mysqli_rollback($conn);
                                mysqli_autocommit($conn, TRUE);
                                echo json_encode(['success' => false, 'message' => 'Insufficient quantity sold to subtract from. Available: ' . $total_sold]);
                                exit();
                            }
                        } else {
                            mysqli_rollback($conn);
                            mysqli_autocommit($conn, TRUE);
                            echo json_encode(['success' => false, 'message' => 'No sales records found for this product.']);
                            exit();
                        }
                    } else {
                        mysqli_rollback($conn);
                        mysqli_autocommit($conn, TRUE);
                        echo json_encode(['success' => false, 'message' => 'Insufficient quantity sold to subtract from. Available: ' . $total_sold]);
                        exit();
                    }
                } else {
                    mysqli_rollback($conn);
                    mysqli_autocommit($conn, TRUE);
                    echo json_encode(['success' => false, 'message' => 'No sales records found for this product.']);
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

