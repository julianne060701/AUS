<?php include '../config/db.php'; ?>

<form method="POST">
    Rice ID: <input type="number" name="rice_id"><br>
    Quantity Sold: <input type="number" name="qty_sold" step="0.01"><br>
    <button type="submit">Record Sale</button>
</form>

<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get rice price
    $rice_id = $_POST['rice_id'];
    $qty_sold = $_POST['qty_sold'];
    $get = $conn->query("SELECT price_per_kg, quantity FROM rice_inventory WHERE id = $rice_id");
    $rice = $get->fetch_assoc();

    $total = $rice['price_per_kg'] * $qty_sold;
    $new_qty = $rice['quantity'] - $qty_sold;

    // Record sale
    $stmt = $conn->prepare("INSERT INTO sales (rice_id, quantity_sold, total_price) VALUES (?, ?, ?)");
    $stmt->bind_param("idd", $rice_id, $qty_sold, $total);
    $stmt->execute();

    // Update stock
    $conn->query("UPDATE rice_inventory SET quantity = $new_qty WHERE id = $rice_id");

    echo "Sale recorded. Stock updated.";
}
?>
