<?php
session_start();
include '../config/conn.php';

header('Content-Type: application/json');

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_SESSION['user_id'];
    $item_name = !empty($_POST['item_name']) ? trim(mysqli_real_escape_string($conn, $_POST['item_name'])) : null;
    $quantity = intval($_POST['quantity']);
    $expected_return_date = !empty($_POST['expected_return_date']) ? $_POST['expected_return_date'] : null;
    $notes = !empty($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : null;

    // Validate item name
    if (empty($item_name)) {
        echo json_encode(['success' => false, 'message' => 'Item name is required']);
        exit();
    }

    // Validate quantity
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0']);
        exit();
    }

    // Try to find product by name (optional - for linking if exists)
    $product_id = null;
    $product_sql = "SELECT id FROM products WHERE product_name = ? LIMIT 1";
    $stmt = $conn->prepare($product_sql);
    $stmt->bind_param("s", $item_name);
    $stmt->execute();
    $product_result = $stmt->get_result();
    if ($product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
        $product_id = $product['id'];
        
        // Check product availability if product exists
        $product_qty_sql = "SELECT quantity FROM products WHERE id = ?";
        $stmt = $conn->prepare($product_qty_sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product_qty_result = $stmt->get_result();
        $product_data = $product_qty_result->fetch_assoc();
        
        // Check how many are currently borrowed
        $borrowed_sql = "SELECT COALESCE(SUM(quantity), 0) as borrowed_qty 
                         FROM borrow_requests 
                         WHERE product_id = ? AND status IN ('approved', 'pending')";
        $stmt = $conn->prepare($borrowed_sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $borrowed_result = $stmt->get_result();
        $borrowed_data = $borrowed_result->fetch_assoc();
        $available = $product_data['quantity'] - $borrowed_data['borrowed_qty'];
        
        if ($quantity > $available) {
            echo json_encode(['success' => false, 'message' => "Only $available items available for this product"]);
            exit();
        }
    }

    // Insert borrow request
    $insert_sql = "INSERT INTO borrow_requests (employee_id, product_id, item_name, quantity, expected_return_date, notes, status) 
                   VALUES (?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("iisiss", $employee_id, $product_id, $item_name, $quantity, $expected_return_date, $notes);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Borrow request submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit request: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

