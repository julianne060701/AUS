<?php
// Test the sales form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<h2>Form Submission Test Results</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>POST Data Received:</h4>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    echo "</div>";
    
    $payment_method = $_POST['payment_method'] ?? 'not_set';
    $installment_period = isset($_POST['installment_period']) ? (int)$_POST['installment_period'] : 0;
    
    echo "<div style='background: #e9ecef; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Processed Values:</h4>";
    echo "Payment Method: <strong>" . $payment_method . "</strong><br>";
    echo "Installment Period: <strong>" . $installment_period . "</strong><br>";
    
    if ($payment_method === 'installment') {
        if ($installment_period > 0) {
            $interest_rate = 0;
            switch($installment_period) {
                case 6: $interest_rate = 3; break;
                case 12: $interest_rate = 5; break;
                case 24: $interest_rate = 7; break;
            }
            echo "Interest Rate: <strong>" . $interest_rate . "%</strong><br>";
            echo "<span style='color: green;'>✓ Installment data is being captured correctly!</span>";
        } else {
            echo "<span style='color: red;'>✗ Installment period is 0 - this is the problem!</span>";
        }
    } else {
        echo "<span style='color: blue;'>Cash payment selected</span>";
    }
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Sales Form</title>
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
        <h1>Test Sales Form - Installment Payment</h1>
        <p>This form tests if installment payment data is being submitted correctly.</p>
        
        <form method="POST">
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
                                    <small class="text-muted d-block">Lowest Rate</small>
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
                                    <small class="text-muted d-block">Most Popular</small>
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
                                    <small class="text-muted d-block">Longest Term</small>
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
                }
            });
        });

        // Installment period change
        document.querySelectorAll('input[name="installment_period"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Update visual selection
                document.querySelectorAll('.installment-option').forEach(option => {
                    option.classList.remove('border-primary', 'bg-light');
                });
                
                const selectedCard = document.querySelector(`.installment-option[data-period="${this.value}"]`);
                selectedCard.classList.add('border-primary', 'bg-light');
            });
        });
    </script>
</body>
</html>
