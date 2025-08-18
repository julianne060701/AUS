<?php include '../config/db.php'; ?>

<form method="POST">
    Supplier Name: <input type="text" name="name"><br>
    Contact Info: <textarea name="contact"></textarea><br>
    <button type="submit">Add Supplier</button>
</form>

<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_info) VALUES (?, ?)");
    $stmt->bind_param("ss", $_POST['name'], $_POST['contact']);
    $stmt->execute();
    echo "Supplier added successfully!";
}
?>
