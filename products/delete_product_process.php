<?php
session_start();
include '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    
    // Prepare and execute delete query
    $sql = "DELETE FROM products WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Product deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting product: " . $conn->error;
    }
    
    $stmt->close();
}

// Redirect back to the inventory page
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>