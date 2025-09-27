<?php
// Test form submission to see what data is being received
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<h2>Form Submission Test Results</h2>";
    
    // Log all POST data
    error_log("=== FORM SUBMISSION TEST ===");
    error_log("POST Data: " . print_r($_POST, true));
    
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>All POST Data Received:</h3>";
    echo "<pre style='background: white; padding: 15px; border: 1px solid #ddd;'>";
    print_r($_POST);
    echo "</pre>";
    echo "</div>";
    
    // Check if payment_method exists
    if (isset($_POST['payment_method'])) {
        echo "<div style='color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "✓ Payment method field is present: <strong>" . $_POST['payment_method'] . "</strong>";
        echo "</div>";
    } else {
        echo "<div style='color: red; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "✗ Payment method field is MISSING from POST data!";
        echo "</div>";
    }
    
    // Test database insertion
    if (isset($_POST['payment_method'])) {
        include 'config/conn.php';
        
        try {
            $sql = "INSERT INTO aircon_sales (aircon_model, quantity_sold, selling_price, total_amount, date_of_sale, cashier, payment_method, installment_period, interest_rate, interest_amount, monthly_payment, original_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sidissisiddd", 
                $_POST['product_id'] ?? 'Test Product',
                $_POST['quantity'] ?? 1,
                $_POST['selling_price'] ?? 10000.00,
                $_POST['selling_price'] ?? 10000.00,
                date('Y-m-d H:i:s'),
                $_POST['cashier_name'] ?? 'Test User',
                $_POST['payment_method'],
                $_POST['installment_period'] ?? null,
                null, // interest_rate
                null, // interest_amount
                null, // monthly_payment
                $_POST['selling_price'] ?? 10000.00
            );
            
            if ($stmt->execute()) {
                $sale_id = $conn->insert_id;
                echo "<div style='color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
                echo "✓ Successfully inserted into database with ID: $sale_id";
                echo "</div>";
                
                // Verify
                $verify = $conn->query("SELECT payment_method FROM aircon_sales WHERE sale_id = $sale_id");
                if ($verify && $verify->num_rows > 0) {
                    $result = $verify->fetch_assoc();
                    echo "<div style='color: blue; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
                    echo "✓ Verified in database: payment_method = " . $result['payment_method'];
                    echo "</div>";
                }
                
                // Clean up
                $conn->query("DELETE FROM aircon_sales WHERE sale_id = $sale_id");
            } else {
                echo "<div style='color: red; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
                echo "✗ Database insertion failed: " . $stmt->error;
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div style='color: red; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "✗ Database error: " . $e->getMessage();
            echo "</div>";
        }
        
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Form Submission Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .form-group { margin: 20px 0; }
        .payment-option { 
            border: 2px solid #ddd; 
            padding: 20px; 
            margin: 10px; 
            cursor: pointer; 
            display: inline-block;
            width: 200px;
            text-align: center;
        }
        .payment-option:hover { background-color: #f0f0f0; }
        .payment-option.selected { border-color: #007bff; background-color: #e7f3ff; }
        input[type="radio"] { margin-right: 10px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Form Submission Test</h1>
    <p>This form tests if the payment_method field is being submitted and saved to the database.</p>
    
    <form method="POST">
        <div class="form-group">
            <h3>Payment Method:</h3>
            <div class="payment-option" onclick="selectPayment('cash')">
                <input type="radio" name="payment_method" value="cash" id="cash">
                <label for="cash">Cash Payment</label>
            </div>
            <div class="payment-option" onclick="selectPayment('installment')">
                <input type="radio" name="payment_method" value="installment" id="installment">
                <label for="installment">Installment Payment</label>
            </div>
        </div>
        
        <div class="form-group">
            <label>Product ID:</label>
            <input type="text" name="product_id" value="1" required>
        </div>
        
        <div class="form-group">
            <label>Quantity:</label>
            <input type="number" name="quantity" value="1" required>
        </div>
        
        <div class="form-group">
            <label>Selling Price:</label>
            <input type="number" name="selling_price" value="10000" step="0.01" required>
        </div>
        
        <div class="form-group">
            <label>Cashier Name:</label>
            <input type="text" name="cashier_name" value="Test User" required>
        </div>
        
        <button type="submit">Test Submit</button>
    </form>

    <script>
        function selectPayment(method) {
            // Clear all selections
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Select the clicked option
            event.currentTarget.classList.add('selected');
            document.getElementById(method).checked = true;
            
            console.log('Payment method selected:', method);
        }
    </script>
</body>
</html>
