<?php
// Simple test to see what's being submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<h2>Form Submission Test</h2>";
    echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px 0;'>";
    echo "<h3>POST Data:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    echo "</div>";
    
    $payment_method = $_POST['payment_method'] ?? 'NOT SET';
    $installment_period = $_POST['installment_period'] ?? 'NOT SET';
    
    echo "<div style='background: #e0e0e0; padding: 20px; margin: 20px 0;'>";
    echo "<h3>Key Values:</h3>";
    echo "Payment Method: <strong>" . $payment_method . "</strong><br>";
    echo "Installment Period: <strong>" . $installment_period . "</strong><br>";
    echo "</div>";
    
    if ($payment_method === 'installment' && $installment_period !== 'NOT SET') {
        echo "<div style='background: #d4edda; padding: 20px; margin: 20px 0; border: 1px solid #c3e6cb;'>";
        echo "<h3 style='color: #155724;'>✓ SUCCESS: Installment data is being captured!</h3>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; margin: 20px 0; border: 1px solid #f5c6cb;'>";
        echo "<h3 style='color: #721c24;'>✗ PROBLEM: Installment data is NOT being captured!</h3>";
        echo "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Form Test</title>
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
        .installment-option { 
            border: 2px solid #ddd; 
            padding: 15px; 
            margin: 10px; 
            cursor: pointer; 
            display: inline-block;
            width: 150px;
            text-align: center;
        }
        .installment-option:hover { background-color: #f0f0f0; }
        .installment-option.selected { border-color: #28a745; background-color: #d4edda; }
        input[type="radio"] { margin-right: 10px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Simple Installment Form Test</h1>
    
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
        
        <div class="form-group" id="installmentOptions" style="display: none;">
            <h3>Installment Period:</h3>
            <div class="installment-option" onclick="selectPeriod(6)">
                <input type="radio" name="installment_period" value="6" id="period_6">
                <label for="period_6">6 Months (3%)</label>
            </div>
            <div class="installment-option" onclick="selectPeriod(12)">
                <input type="radio" name="installment_period" value="12" id="period_12">
                <label for="period_12">12 Months (5%)</label>
            </div>
            <div class="installment-option" onclick="selectPeriod(24)">
                <input type="radio" name="installment_period" value="24" id="period_24">
                <label for="period_24">24 Months (7%)</label>
            </div>
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
            
            // Show/hide installment options
            const installmentOptions = document.getElementById('installmentOptions');
            if (method === 'installment') {
                installmentOptions.style.display = 'block';
            } else {
                installmentOptions.style.display = 'none';
                // Clear installment selections
                document.querySelectorAll('input[name="installment_period"]').forEach(radio => {
                    radio.checked = false;
                });
                document.querySelectorAll('.installment-option').forEach(option => {
                    option.classList.remove('selected');
                });
            }
        }
        
        function selectPeriod(period) {
            // Clear all selections
            document.querySelectorAll('.installment-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Select the clicked option
            event.currentTarget.classList.add('selected');
            document.getElementById('period_' + period).checked = true;
        }
    </script>
</body>
</html>
