<?php
include '../config/conn.php'; // database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $brand_name = trim($_POST['brand_name']);

    if (!empty($brand_name)) {
     
        $stmt = $conn->prepare("INSERT INTO brands (brand_name) VALUES (?)");
        $stmt->bind_param("s", $brand_name);

        if ($stmt->execute()) {
          
            header("Location: brand.php?success=1");
            exit;
        } else {
            // error inserting
            header("Location: brand.php?error=1");
            exit;
        }

        $stmt->close();
    } else {
        
        header("Location: brand.php?error=empty");
        exit;
    }
}

$conn->close();
?>
