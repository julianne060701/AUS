<?php
// Debug the actual sales form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<h2>Sales Form Debug Results</h2>";
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>All POST Data Received:</h3>";
    echo "<pre style='background: white; padding: 15px; border: 1px solid #ddd;'>";
    print_r($_POST);
    echo "</pre>";
    echo "</div>";
    
    // Check specific values
    $payment_method = $_POST['payment_method'] ?? 'NOT RECEIVED';
    $installment_period = $_POST['installment_period'] ?? 'NOT RECEIVED';
    
    echo "<div style='background: #e9ecef; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Key Values:</h3>";
    echo "Payment Method: <strong style='color: " . ($payment_method !== 'NOT RECEIVED' ? 'green' : 'red') . "'>" . $payment_method . "</strong><br>";
    echo "Installment Period: <strong style='color: " . ($installment_period !== 'NOT RECEIVED' ? 'green' : 'red') . "'>" . $installment_period . "</strong><br>";
    echo "</div>";
    
    if ($payment_method !== 'NOT RECEIVED') {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0; border: 1px solid #c3e6cb;'>";
        echo "<h3 style='color: #155724;'>✓ Payment Method is being received!</h3>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0; border: 1px solid #f5c6cb;'>";
        echo "<h3 style='color: #721c24;'>✗ Payment Method is NOT being received!</h3>";
        echo "<p>This means the form is not submitting the payment_method field properly.</p>";
        echo "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Sales Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .payment-option { cursor: pointer; }
        .payment-option:hover { background-color: #f8f9fa; }
        .installment-option { cursor: pointer; }
        .installment-option:hover { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Debug Sales Form</h1>
        <p>This form replicates the exact structure of your sales form to test if payment_method is being submitted.</p>
        
        <form method="POST" id="debugForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Product:</label>
                        <select class="form-control" name="product_id" required>
                            <option value="1">Test Product - ₱10,000</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Quantity:</label>
                        <input type="number" class="form-control" name="quantity" value="1" min="1" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Unit Price:</label>
                        <input type="number" class="form-control" name="selling_price" value="10000" step="0.01" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Payment Method:</label>
                <div class="row">
                    <div class="col-6">
                        <div class="card border-success payment-option" data-method="cash">
                            <div class="card-body text-center">
                                <input type="radio" name="payment_method" value="cash" id="cash" required>
                                <label for="cash" class="mb-0 d-block cursor-pointer">
                                    <i class="fas fa-money-bill-wave fa-2x text-success mb-2"></i>
                                    <h6 class="text-success">Cash Payment</h6>
                                    <small class="text-success font-weight-bold">10% Discount</small>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card border-warning payment-option" data-method="installment">
                            <div class="card-body text-center">
                                <input type="radio" name="payment_method" value="installment" id="installment" required>
                                <label for="installment" class="mb-0 d-block cursor-pointer">
                                    <i class="fas fa-credit-card fa-2x text-warning mb-2"></i>
                                    <h6 class="text-warning">Installment</h6>
                                    <small class="text-muted">With Interest</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group" id="installmentOptions" style="display: none;">
                <label>Installment Period:</label>
                <div class="row">
                    <div class="col-4">
                        <div class="card border-info installment-option" data-period="6" data-rate="3">
                            <div class="card-body text-center">
                                <input type="radio" name="installment_period" value="6" id="installment_6">
                                <label for="installment_6" class="mb-0 d-block cursor-pointer">
                                    <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                                    <h6 class="text-info">6 Months</h6>
                                    <div class="badge badge-info mb-1">3% Interest</div>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card border-warning installment-option" data-period="12" data-rate="5">
                            <div class="card-body text-center">
                                <input type="radio" name="installment_period" value="12" id="installment_12">
                                <label for="installment_12" class="mb-0 d-block cursor-pointer">
                                    <i class="fas fa-calendar fa-2x text-warning mb-2"></i>
                                    <h6 class="text-warning">12 Months</h6>
                                    <div class="badge badge-warning mb-1">5% Interest</div>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card border-danger installment-option" data-period="24" data-rate="7">
                            <div class="card-body text-center">
                                <input type="radio" name="installment_period" value="24" id="installment_24">
                                <label for="installment_24" class="mb-0 d-block cursor-pointer">
                                    <i class="fas fa-calendar fa-2x text-danger mb-2"></i>
                                    <h6 class="text-danger">24 Months</h6>
                                    <div class="badge badge-danger mb-1">7% Interest</div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Cashier Name:</label>
                <input type="text" class="form-control" name="cashier_name" value="Test User" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Test Submit</button>
        </form>
    </div>

    <script>
        // Payment method change
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                console.log('Payment method changed to:', this.value);
                
                // Update visual selection
                document.querySelectorAll('.payment-option').forEach(option => {
                    option.classList.remove('border-primary', 'bg-light');
                });
                
                const selectedCard = document.querySelector(`.payment-option[data-method="${this.value}"]`);
                selectedCard.classList.add('border-primary', 'bg-light');
                
                // Show/hide installment options
                const installmentOptions = document.getElementById('installmentOptions');
                if (this.value === 'installment') {
                    installmentOptions.style.display = 'block';
                } else {
                    installmentOptions.style.display = 'none';
                    // Clear installment selections
                    document.querySelectorAll('input[name="installment_period"]').forEach(radio => {
                        radio.checked = false;
                    });
                    document.querySelectorAll('.installment-option').forEach(option => {
                        option.classList.remove('border-primary', 'bg-light');
                    });
                }
            });
        });

        // Installment period change
        document.querySelectorAll('input[name="installment_period"]').forEach(radio => {
            radio.addEventListener('change', function() {
                console.log('Installment period changed to:', this.value);
                
                // Update visual selection
                document.querySelectorAll('.installment-option').forEach(option => {
                    option.classList.remove('border-primary', 'bg-light');
                });
                
                const selectedCard = document.querySelector(`.installment-option[data-period="${this.value}"]`);
                selectedCard.classList.add('border-primary', 'bg-light');
            });
        });
        
        // Form submission debug
        document.getElementById('debugForm').addEventListener('submit', function(e) {
            console.log('Form is being submitted...');
            console.log('Payment method selected:', document.querySelector('input[name="payment_method"]:checked') ? document.querySelector('input[name="payment_method"]:checked').value : 'none');
            console.log('Installment period selected:', document.querySelector('input[name="installment_period"]:checked') ? document.querySelector('input[name="installment_period"]:checked').value : 'none');
        });
    </script>
</body>
</html>
