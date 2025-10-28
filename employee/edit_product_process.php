<?php
session_start();
include '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form data
    $product_id = intval($_POST['product_id']);
    $product_name = trim($_POST['product_name']);
    $serial_number = !empty($_POST['serial_number']) ? trim($_POST['serial_number']) : null;
    $brand_id = !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null;
    $capacity = trim($_POST['capacity']);
    $buying_price = floatval($_POST['buying_price']);
    $selling_price = floatval($_POST['selling_price']);
    $quantity = intval($_POST['quantity']);
    $category_id = intval($_POST['category_id']);

    // Validate required fields
    if (empty($product_name) || empty($capacity) || empty($category_id) || empty($product_id)) {
        $_SESSION['error'] = "Product name, capacity, and category are required!";
        header("Location: inventory.php");
        exit();
    }

    // Validate numeric values
    if ($buying_price < 0 || $selling_price < 0 || $quantity < 0) {
        $_SESSION['error'] = "Prices and quantity cannot be negative!";
        header("Location: inventory.php");
        exit();
    }

    // Check if serial number already exists for another product (if provided)
    if (!empty($serial_number)) {
        $checkStmt = $conn->prepare("SELECT id FROM products WHERE serial_number = ? AND id != ?");
        $checkStmt->bind_param("si", $serial_number, $product_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $_SESSION['error'] = "Serial number '$serial_number' is already used by another product!";
            $checkStmt->close();
            $conn->close();
            header("Location: inventory.php");
            exit();
        }
        $checkStmt->close();
    }

    // Prepare and execute update query
    $stmt = $conn->prepare("UPDATE products 
                           SET product_name = ?, 
                               serial_number = ?, 
                               brand_id = ?, 
                               capacity = ?, 
                               buying_price = ?, 
                               selling_price = ?, 
                               quantity = ?, 
                               category_id = ? 
                           WHERE id = ?");
    
    $stmt->bind_param("ssissdiii", 
        $product_name, 
        $serial_number, 
        $brand_id, 
        $capacity, 
        $buying_price, 
        $selling_price, 
        $quantity, 
        $category_id, 
        $product_id
    );

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['success'] = "Product '$product_name' has been updated successfully!";
        } else {
            $_SESSION['info'] = "No changes were made to the product.";
        }
    } else {
        $_SESSION['error'] = "Error updating product: " . $conn->error;

    }
}
// Redirect back to the inventory page
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>