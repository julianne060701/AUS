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
        // If service type is Installation, subtract from quantity sold
        if ($service_type === 'Installation') {
            // Get product ID from products_to_install (assuming it contains product ID)
            $product_id = (int)$products_to_install;
            
            // Check if product exists and has enough quantity sold to subtract from
            $check_sales_query = "SELECT SUM(quantity_sold) as total_sold FROM aircon_sales WHERE product_id = $product_id";
            $sales_result = mysqli_query($conn, $check_sales_query);
            
            if ($sales_result && mysqli_num_rows($sales_result) > 0) {
                $sales_data = mysqli_fetch_assoc($sales_result);
                $total_sold = $sales_data['total_sold'] ?? 0;
                
                if ($total_sold >= $quantity_to_install) {
                    // Find sale records with quantity sold > 0, ordered by sale_id DESC
                    $find_sale_query = "SELECT sale_id, quantity_sold FROM aircon_sales WHERE product_id = $product_id AND quantity_sold > 0 ORDER BY sale_id DESC";
                    $find_result = mysqli_query($conn, $find_sale_query);
                    
                    if ($find_result && mysqli_num_rows($find_result) > 0) {
                        $remaining_to_subtract = $quantity_to_install;
                        $success = true;
                        
                        // Loop through sale records and subtract quantities
                        while ($remaining_to_subtract > 0 && $sale_record = mysqli_fetch_assoc($find_result)) {
                            $sale_id = $sale_record['sale_id'];
                            $current_sold = $sale_record['quantity_sold'];
                            
                            if ($current_sold >= $remaining_to_subtract) {
                                // This record has enough quantity
                                $new_quantity_sold = $current_sold - $remaining_to_subtract;
                                $update_sale_query = "UPDATE aircon_sales SET quantity_sold = $new_quantity_sold WHERE sale_id = $sale_id";
                                
                                if (!mysqli_query($conn, $update_sale_query)) {
                                    $success = false;
                                    break;
                                }
                                $remaining_to_subtract = 0;
                            } else {
                                // This record doesn't have enough, subtract what it has
                                $update_sale_query = "UPDATE aircon_sales SET quantity_sold = 0 WHERE sale_id = $sale_id";
                                
                                if (!mysqli_query($conn, $update_sale_query)) {
                                    $success = false;
                                    break;
                                }
                                $remaining_to_subtract -= $current_sold;
                            }
                        }
                        
                        if ($success && $remaining_to_subtract == 0) {
                            // Commit transaction
                            mysqli_commit($conn);
                            echo json_encode(['success' => true, 'message' => 'Schedule assigned and quantity sold updated successfully!']);
                        } else {
                            // Rollback transaction
                            mysqli_rollback($conn);
                            echo json_encode(['success' => false, 'message' => 'Error updating quantity sold: ' . mysqli_error($conn)]);
                        }
                    } else {
                        // Rollback transaction
                        mysqli_rollback($conn);
                        echo json_encode(['success' => false, 'message' => 'No sale records with quantity found for this product.']);
                    }
                } else {
                    // Rollback transaction
                    mysqli_rollback($conn);
                    echo json_encode(['success' => false, 'message' => 'Insufficient total quantity sold. Available: ' . $total_sold . ', Requested: ' . $quantity_to_install]);
                }
            } else {
                // Rollback transaction
                mysqli_rollback($conn);
                echo json_encode(['success' => false, 'message' => 'No sales records found for this product.']);
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

