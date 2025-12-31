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

// Get schedule data before deleting to restore quantities if needed
$schedule_query = "SELECT service_type, products_to_install, quantity_to_install FROM installer_schedules WHERE id = $id";
$schedule_result = mysqli_query($conn, $schedule_query);

if ($schedule_result && mysqli_num_rows($schedule_result) > 0) {
    $schedule = mysqli_fetch_assoc($schedule_result);
    
    // Start transaction
    mysqli_autocommit($conn, FALSE);
    
    // If it's an Installation service, restore quantities in summary table
    if ($schedule['service_type'] === 'Installation') {
        $product_id = (int)$schedule['products_to_install'];
        $quantity_to_install = (int)$schedule['quantity_to_install'];
        
        if ($product_id > 0 && $quantity_to_install > 0) {
            // Restore: add back to total_quantity_sold, subtract from total_quantity_to_install
            $restore_query = "UPDATE product_quantity_sold_summary 
                              SET total_quantity_sold = total_quantity_sold + $quantity_to_install,
                                  total_quantity_to_install = total_quantity_to_install - $quantity_to_install,
                                  last_updated = NOW()
                              WHERE product_id = $product_id";
            
            if (!mysqli_query($conn, $restore_query)) {
                mysqli_rollback($conn);
                mysqli_autocommit($conn, TRUE);
                header("Location: installer_schedule.php?message=Error restoring quantities: " . mysqli_error($conn) . "&type=danger");
                exit();
            }
        }
    }
    
    // Delete the schedule
    $query = "DELETE FROM installer_schedules WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        mysqli_commit($conn);
        mysqli_autocommit($conn, TRUE);
        header("Location: installer_schedule.php?message=Schedule deleted successfully&type=success");
    } else {
        mysqli_rollback($conn);
        mysqli_autocommit($conn, TRUE);
        header("Location: installer_schedule.php?message=Error deleting schedule&type=danger");
    }
} else {
    // Schedule not found, just redirect
    header("Location: installer_schedule.php?message=Schedule not found&type=danger");
}
exit();
?>

