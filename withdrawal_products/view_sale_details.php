<?php
// Start output buffering to prevent header issues
ob_start();

// Start session FIRST before any output
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
    // Query to fetch sale details with all installment information
    $query = "SELECT 
        sale_id,
        aircon_model,
        quantity_sold,
        selling_price,
        total_amount,
        date_of_sale,
        cashier,
        payment_method,
        installment_period,
        interest_rate,
        interest_amount,
        monthly_payment,
        original_price
    FROM aircon_sales 
    WHERE sale_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $sale_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $sale = $result->fetch_assoc();
        
        // Ensure all numeric values are properly formatted
        $sale['sale_id'] = (int)$sale['sale_id'];
        $sale['quantity_sold'] = (int)$sale['quantity_sold'];
        $sale['selling_price'] = (float)$sale['selling_price'];
        $sale['total_amount'] = (float)$sale['total_amount'];
        $sale['installment_period'] = $sale['installment_period'] ? (int)$sale['installment_period'] : null;
        $sale['interest_rate'] = $sale['interest_rate'] ? (float)$sale['interest_rate'] : null;
        $sale['interest_amount'] = $sale['interest_amount'] ? (float)$sale['interest_amount'] : null;
        $sale['monthly_payment'] = $sale['monthly_payment'] ? (float)$sale['monthly_payment'] : null;
        $sale['original_price'] = $sale['original_price'] ? (float)$sale['original_price'] : (float)$sale['selling_price'];
        
        // Set default payment method if not set (for backward compatibility)
        if (empty($sale['payment_method'])) {
            $sale['payment_method'] = 'cash';
        }
        
        echo json_encode([
            'success' => true,
            'sale' => $sale
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Sale not found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

// Close database connection
$conn->close();

// End output buffering and flush content
ob_end_flush();
?>
