<?php
include '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Print all POST data to see what's being sent
    error_log("POST data: " . print_r($_POST, true));
    
    $id = $_POST['id'];
    $rice_type = $_POST['rice_type'];
    $price_per_kg = (float)$_POST['price_per_kg'];
    $sack_weight_kg = (float)$_POST['sack_weight_kg'];
    $quantity_sacks = (int)$_POST['quantity_sacks'];
    $quantity_kg = (float)$_POST['quantity_kg'];
    $unit = $_POST['unit'];
    $category_id = (int)$_POST['category'];
    $alert_threshold = (int)$_POST['alert_threshold'];

    // Debug: Print processed values
    error_log("Processed values - ID: $id, Rice Type: $rice_type, Sacks: $quantity_sacks, KG: $quantity_kg");

    // Prepare the SQL statement with correct column names
    $sql = "UPDATE rice_inventory SET 
                rice_type = ?, 
                price_per_kg = ?, 
                sack_weight_kg = ?, 
                quantity_sacks = ?, 
                quantity_kg = ?, 
                unit = ?, 
                category_id = ?, 
                alert_threshold = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Bind parameters with correct types
        $stmt->bind_param("sddidsiii", 
            $rice_type,      // string
            $price_per_kg,   // double/float
            $sack_weight_kg, // double/float
            $quantity_sacks, // integer
            $quantity_kg,    // double/float
            $unit,           // string
            $category_id,    // integer
            $alert_threshold,// integer
            $id              // integer
        );
        
        if ($stmt->execute()) {
            // Debug: Check if any rows were affected
            $affected_rows = $stmt->affected_rows;
            error_log("Update successful. Affected rows: $affected_rows");
            
            if ($affected_rows > 0) {
                header("Location: inventory.php?success=1&message=Inventory updated successfully");
            } else {
                header("Location: inventory.php?error=1&message=No changes made or record not found");
            }
        } else {
            error_log("Execute failed: " . $stmt->error);
            header("Location: inventory.php?error=1&message=" . urlencode("Database error: " . $stmt->error));
        }
        
        $stmt->close();
    } else {
        error_log("Prepare failed: " . $conn->error);
        header("Location: inventory.php?error=1&message=" . urlencode("Prepare error: " . $conn->error));
    }
    
    $conn->close();
} else {
    header("Location: inventory.php?error=1&message=Invalid request method");
}
?>