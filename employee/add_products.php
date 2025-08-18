<?php
include '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve values from the form
    $product_name   = $_POST['product_name'];
    $capacity       = $_POST['capacity']; // NEW FIELD
    $buying_price   = $_POST['buying_price'];
    $selling_price  = $_POST['selling_price'];
    $quantity       = $_POST['quantity'];
    $category_id    = $_POST['category_id'];

    // Prepare the INSERT statement
    $stmt = $conn->prepare("INSERT INTO products (product_name, capacity, buying_price, selling_price, quantity, category_id) 
                            VALUES (?, ?, ?, ?, ?, ?)");

    // Bind parameters: 
    // s = string, d = double, i = integer
    $stmt->bind_param("ssddii", 
        $product_name, 
        $capacity, 
        $buying_price, 
        $selling_price, 
        $quantity, 
        $category_id
    );

    if ($stmt->execute()) {
        header("Location: inventory.php?success=1");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
