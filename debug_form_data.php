<?php
// Debug form data to see what's being submitted
echo "<h2>Form Data Debug</h2>";
echo "<pre>";
echo "POST Data:\n";
print_r($_POST);
echo "\n\nGET Data:\n";
print_r($_GET);
echo "</pre>";

// Test the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<h3>Processing Form Submission:</h3>";
    
    $payment_method = $_POST['payment_method'] ?? 'not_set';
    $installment_period = isset($_POST['installment_period']) ? (int)$_POST['installment_period'] : 0;
    
    echo "Payment Method: " . $payment_method . "<br>";
    echo "Installment Period: " . $installment_period . "<br>";
    
    if ($payment_method === 'installment') {
        echo "Installment payment detected!<br>";
        if ($installment_period > 0) {
            echo "Installment period: " . $installment_period . " months<br>";
            
            // Calculate interest rate
            $interest_rate = 0;
            switch($installment_period) {
                case 6:
                    $interest_rate = 3;
                    break;
                case 12:
                    $interest_rate = 5;
                    break;
                case 24:
                    $interest_rate = 7;
                    break;
            }
            echo "Interest Rate: " . $interest_rate . "%<br>";
        } else {
            echo "ERROR: Installment period is 0 or not set!<br>";
        }
    } else {
        echo "Cash payment detected<br>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Form Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Test Installment Form</h1>
        <form method="POST">
            <div class="form-group">
                <label>Payment Method:</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="payment_method" value="cash" id="cash">
                    <label class="form-check-label" for="cash">Cash</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="payment_method" value="installment" id="installment">
                    <label class="form-check-label" for="installment">Installment</label>
                </div>
            </div>
            
            <div class="form-group" id="installmentOptions" style="display: none;">
                <label>Installment Period:</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="installment_period" value="6" id="installment_6">
                    <label class="form-check-label" for="installment_6">6 Months (3%)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="installment_period" value="12" id="installment_12">
                    <label class="form-check-label" for="installment_12">12 Months (5%)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="installment_period" value="24" id="installment_24">
                    <label class="form-check-label" for="installment_24">24 Months (7%)</label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Test Submit</button>
        </form>
    </div>

    <script>
        // Show/hide installment options
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const installmentOptions = document.getElementById('installmentOptions');
                if (this.value === 'installment') {
                    installmentOptions.style.display = 'block';
                } else {
                    installmentOptions.style.display = 'none';
                    // Clear installment selections
                    document.querySelectorAll('input[name="installment_period"]').forEach(radio => {
                        radio.checked = false;
                    });
                }
            });
        });
    </script>
</body>
</html>
