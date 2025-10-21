<?php
include 'config/conn.php';

echo "Testing installment payment insertion...\n\n";

// Test data
$test_data = [
    'product_name' => 'Test Product',
    'quantity' => 1,
    'selling_price' => 10000.00,
    'payment_method' => 'installment',
    'installment_period' => 12,
    'interest_rate' => 5,
    'interest_amount' => 500.00,
    'total_amount' => 10500.00,
    'monthly_payment' => 875.00,
    'original_price' => 10000.00,
    'cashier' => 'Test User',
    'date_of_sale' => date('Y-m-d H:i:s')
];

echo "Test data:\n";
print_r($test_data);
echo "\n";

try {
    // Check if columns exist
    $result = $conn->query("SHOW COLUMNS FROM aircon_sales LIKE 'payment_method'");
    if (!$result || $result->num_rows == 0) {
        echo "ERROR: Installment columns do not exist in the database!\n";
        echo "Please run add_installment_columns.php first.\n";
        exit;
    }
    
    // Test insertion
    $sql = "INSERT INTO aircon_sales (aircon_model, quantity_sold, selling_price, total_amount, date_of_sale, cashier, payment_method, installment_period, interest_rate, interest_amount, monthly_payment, original_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "ERROR: Failed to prepare statement: " . $conn->error . "\n";
        exit;
    }
    
    $stmt->bind_param("sidissisiddd", 
        $test_data['product_name'],
        $test_data['quantity'],
        $test_data['selling_price'],
        $test_data['total_amount'],
        $test_data['date_of_sale'],
        $test_data['cashier'],
        $test_data['payment_method'],
        $test_data['installment_period'],
        $test_data['interest_rate'],
        $test_data['interest_amount'],
        $test_data['monthly_payment'],
        $test_data['original_price']
    );
    
    if ($stmt->execute()) {
        $sale_id = $conn->insert_id;
        echo "✓ Test sale inserted successfully with ID: $sale_id\n";
        
        // Verify the data was inserted correctly
        $verify_sql = "SELECT * FROM aircon_sales WHERE sale_id = $sale_id";
        $verify_result = $conn->query($verify_sql);
        if ($verify_result && $verify_result->num_rows > 0) {
            $sale = $verify_result->fetch_assoc();
            echo "\nVerification - Inserted data:\n";
            echo "Payment Method: " . ($sale['payment_method'] ?? 'NULL') . "\n";
            echo "Installment Period: " . ($sale['installment_period'] ?? 'NULL') . "\n";
            echo "Interest Rate: " . ($sale['interest_rate'] ?? 'NULL') . "\n";
            echo "Interest Amount: " . ($sale['interest_amount'] ?? 'NULL') . "\n";
            echo "Monthly Payment: " . ($sale['monthly_payment'] ?? 'NULL') . "\n";
            echo "Original Price: " . ($sale['original_price'] ?? 'NULL') . "\n";
        }
        
        // Clean up test data
        $conn->query("DELETE FROM aircon_sales WHERE sale_id = $sale_id");
        echo "\n✓ Test data cleaned up\n";
        
    } else {
        echo "ERROR: Failed to execute statement: " . $stmt->error . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

$conn->close();
?>
