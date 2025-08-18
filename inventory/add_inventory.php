<?php
include '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rice_type = $_POST['rice_type'];
    $price_per_kg = $_POST['price_per_kg'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $category_id = $_POST['category'];
    $alert_threshold = $_POST['alert_threshold'];
    $sack_weight_kg = $_POST['sack_weight'];

    // Default sack weight
    $sack_weight_kg = 50; // you may allow user input later if needed

    // Compute based on unit
    if ($unit === 'sack') {
        $quantity_sacks = $quantity;
        $quantity_kg = $quantity * $sack_weight_kg;
    } else {
        $quantity_sacks = 0;
        $quantity_kg = $quantity;
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO rice_inventory (rice_type, price_per_kg, quantity_sacks, quantity_kg, unit, category_id, alert_threshold, sack_weight_kg) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdidsiii", $rice_type, $price_per_kg, $quantity_sacks, $quantity_kg, $unit, $category_id, $alert_threshold, $sack_weight_kg);

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
