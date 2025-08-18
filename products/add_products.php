<?php
include '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve values from the form
    $product_name   = $_POST['product_name'];
    $brand_id       = !empty($_POST['brand_id']) ? $_POST['brand_id'] : NULL; // NEW FIELD - Brand (optional)
    $capacity       = $_POST['capacity'];
    $buying_price   = $_POST['buying_price'];
    $selling_price  = $_POST['selling_price'];
    $quantity       = $_POST['quantity'];
    $category_id    = $_POST['category_id'];

    // Prepare the INSERT statement with brand_id
    $stmt = $conn->prepare("INSERT INTO products (product_name, brand_id, capacity, buying_price, selling_price, quantity, category_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");

    // Bind parameters: 
    // s = string, i = integer, d = double
    $stmt->bind_param("sisddii", 
        $product_name, 
        $brand_id,
        $capacity, 
        $buying_price, 
        $selling_price, 
        $quantity, 
        $category_id
    );

    if ($stmt->execute()) {
        header("Location: product.php?success=1");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>