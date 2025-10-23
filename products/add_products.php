<?php
include '../config/conn.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form data
    $product_name = trim($_POST['product_name']);
    $serial_number = !empty($_POST['serial_number']) ? trim($_POST['serial_number']) : null;
    $brand_id = !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null;
    $capacity = trim($_POST['capacity']);
    $buying_price = floatval($_POST['buying_price']);
    $selling_price = floatval($_POST['selling_price']);
    $quantity = intval($_POST['quantity']);
    $category_id = intval($_POST['category_id']);

    // Validate required fields
    if (empty($product_name) || empty($capacity) || empty($category_id)) {
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

    // Check if serial number already exists (if provided)
    if (!empty($serial_number)) {
        $checkStmt = $conn->prepare("SELECT id FROM products WHERE serial_number = ?");
        $checkStmt->bind_param("s", $serial_number);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $_SESSION['error'] = "Serial number '$serial_number' already exists! Please use a unique serial number.";
            $checkStmt->close();
            $conn->close();
            header("Location: inventory.php");
            exit();
        }
        $checkStmt->close();
    }

    // Prepare and execute insert query
    $stmt = $conn->prepare("INSERT INTO products 
                           (product_name, serial_number, brand_id, capacity, buying_price, selling_price, quantity, category_id, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->bind_param("ssissdii", 
        $product_name, 
        $serial_number, 
        $brand_id, 
        $capacity, 
        $buying_price, 
        $selling_price, 
        $quantity, 
        $category_id
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Product '$product_name' has been added successfully!";
    } else {
        $_SESSION['error'] = "Error adding product: " . $conn->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: product.php");
    exit();
} else {
    // If not POST request, redirect to inventory page
    header("Location: invenproducttory.php");
    exit();
}
?>