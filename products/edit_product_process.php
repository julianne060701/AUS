<?php
session_start();
include '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $brand_id = !empty($_POST['brand_id']) ? $_POST['brand_id'] : NULL; // NEW FIELD - Brand (optional)
    $capacity = $_POST['capacity'];
    $buying_price = $_POST['buying_price'];
    $selling_price = $_POST['selling_price'];
    $quantity = $_POST['quantity'];
    $category_id = $_POST['category_id'];
    
    // Prepare and execute update query with brand_id
    $sql = "UPDATE products SET 
            product_name = ?, 
            brand_id = ?,
            capacity = ?, 
            buying_price = ?, 
            selling_price = ?, 
            quantity = ?, 
            category_id = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sissddii", $product_name, $brand_id, $capacity, $buying_price, $selling_price, $quantity, $category_id, $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Product updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating product: " . $conn->error;
    }
    
    $stmt->close();
}

// Redirect back to the inventory page
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>