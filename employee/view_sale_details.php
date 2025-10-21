<?php
// Start session
session_start();

// Include database connection
include '../config/conn.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if sale_id is provided
if (!isset($_GET['sale_id']) || empty($_GET['sale_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Sale ID is required'
    ]);
    exit;
}

$sale_id = (int)$_GET['sale_id'];

try {
    // Fetch sale details from database
    $stmt = $conn->prepare("SELECT * FROM aircon_sales WHERE sale_id = ?");
    $stmt->bind_param("i", $sale_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Sale record not found'
        ]);
        exit;
    }
    
    $sale = $result->fetch_assoc();
    $stmt->close();
    
    // Return success response with sale data
    echo json_encode([
        'success' => true,
        'sale' => $sale
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
