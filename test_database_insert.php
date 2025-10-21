<?php
include 'config/conn.php';

echo "<h2>Database Insert Test</h2>";

// Test data
$test_data = [
    'aircon_model' => 'Test Product',
    'quantity_sold' => 1,
    'selling_price' => 10000.00,
    'total_amount' => 10000.00,
    'date_of_sale' => date('Y-m-d H:i:s'),
    'cashier' => 'Test User',
    'payment_method' => 'cash',
    'installment_period' => null,
    'interest_rate' => null,
    'interest_amount' => null,
    'monthly_payment' => null,
    'original_price' => 10000.00
];

echo "<h3>Test Data:</h3>";
echo "<pre>";
print_r($test_data);
echo "</pre>";

try {
    // Test insertion
    $sql = "INSERT INTO aircon_sales (aircon_model, quantity_sold, selling_price, total_amount, date_of_sale, cashier, payment_method, installment_period, interest_rate, interest_amount, monthly_payment, original_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "<div style='color: red;'>Error preparing statement: " . $conn->error . "</div>";
        exit;
    }
    
    $stmt->bind_param("sidissisiddd", 
        $test_data['aircon_model'],
        $test_data['quantity_sold'],
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
        echo "<div style='color: green; background: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "✓ Test sale inserted successfully with ID: $sale_id";
        echo "</div>";
        
        // Verify the data was inserted correctly
        $verify_sql = "SELECT * FROM aircon_sales WHERE sale_id = $sale_id";
        $verify_result = $conn->query($verify_sql);
        if ($verify_result && $verify_result->num_rows > 0) {
            $sale = $verify_result->fetch_assoc();
            echo "<h3>Verification - Data in Database:</h3>";
            echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
            echo "<pre>";
            print_r($sale);
            echo "</pre>";
            echo "</div>";
            
            // Check specific fields
            echo "<h3>Key Fields Check:</h3>";
            echo "<div style='background: #e9ecef; padding: 15px; border-radius: 5px;'>";
            echo "Payment Method: <strong>" . ($sale['payment_method'] ?? 'NULL') . "</strong><br>";
            echo "Installment Period: <strong>" . ($sale['installment_period'] ?? 'NULL') . "</strong><br>";
            echo "Interest Rate: <strong>" . ($sale['interest_rate'] ?? 'NULL') . "</strong><br>";
            echo "Original Price: <strong>" . ($sale['original_price'] ?? 'NULL') . "</strong><br>";
            echo "</div>";
            
            if ($sale['payment_method'] === 'cash') {
                echo "<div style='color: green; background: #d4edda; padding: 15px; border-radius: 5px;'>";
                echo "✓ Payment method was saved correctly!";
                echo "</div>";
            } else {
                echo "<div style='color: red; background: #f8d7da; padding: 15px; border-radius: 5px;'>";
                echo "✗ Payment method was NOT saved correctly!";
                echo "</div>";
            }
        }
        
        // Clean up test data
        $conn->query("DELETE FROM aircon_sales WHERE sale_id = $sale_id");
        echo "<div style='color: blue; background: #d1ecf1; padding: 15px; border-radius: 5px;'>";
        echo "✓ Test data cleaned up";
        echo "</div>";
        
    } else {
        echo "<div style='color: red; background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "✗ Error inserting test sale: " . $stmt->error;
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "✗ Exception: " . $e->getMessage();
    echo "</div>";
}

$conn->close();
?>
