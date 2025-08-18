<?php
include '../config/conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_id = intval($_POST["category_id"]);
    $category_name = trim($_POST["category_name"]);

    if (!empty($category_name)) {
        $stmt = $conn->prepare("UPDATE category SET category_name = ? WHERE category_id = ?");
        $stmt->bind_param("si", $category_name, $category_id);

        if ($stmt->execute()) {
            header("Location: categories.php?updated=1");
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>
