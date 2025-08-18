<?php
include '../config/conn.php'; // database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $brand_id = intval($_POST['brand_id']);
    $brand_name = trim($_POST['brand_name']);

    if (!empty($brand_name) && $brand_id > 0) {
        // Check if the brand exists
        $check_stmt = $conn->prepare("SELECT brand_id FROM brands WHERE brand_id = ?");
        $check_stmt->bind_param("i", $brand_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Brand doesn't exist
            $check_stmt->close();
            header("Location: brand.php?error=notfound");
            exit;
        }
        $check_stmt->close();

        // Check if brand name already exists (excluding current brand)
        $duplicate_stmt = $conn->prepare("SELECT brand_id FROM brands WHERE brand_name = ? AND brand_id != ?");
        $duplicate_stmt->bind_param("si", $brand_name, $brand_id);
        $duplicate_stmt->execute();
        $duplicate_result = $duplicate_stmt->get_result();
        
        if ($duplicate_result->num_rows > 0) {
            // Brand name already exists
            $duplicate_stmt->close();
            header("Location: brand.php?error=duplicate");
            exit;
        }
        $duplicate_stmt->close();

        // Update the brand
        $stmt = $conn->prepare("UPDATE brands SET brand_name = ? WHERE brand_id = ?");
        $stmt->bind_param("si", $brand_name, $brand_id);

        if ($stmt->execute()) {
            // Check if any rows were actually affected
            if ($stmt->affected_rows > 0) {
                // Success → redirect back to brands page
                $stmt->close();
                header("Location: brand.php?updated=1");
                exit;
            } else {
                // No changes made (same data)
                $stmt->close();
                header("Location: brand.php?error=nochange");
                exit;
            }
        } else {
            // Error updating
            $stmt->close();
            header("Location: brand.php?error=update");
            exit;
        }
    } else {
        // Invalid input
        if (empty($brand_name)) {
            header("Location: brand.php?error=empty");
        } else {
            header("Location: brand.php?error=invalid");
        }
        exit;
    }
} else {
    // Not a POST request - redirect to brands page
    header("Location: brand.php");
    exit;
}

$conn->close();
?>