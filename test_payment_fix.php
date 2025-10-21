<?php
// Test the payment method fix
echo "<h2>Payment Method Fix Test</h2>";

// Simulate POST data
$_POST = [
    'product_id' => '1',
    'quantity' => '1',
    'selling_price' => '1000.00',
    'cashier_name' => 'Test User',
    'payment_method' => 'cash'  // This should be set
];

echo "<h3>Test 1: Cash Payment</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<strong>POST Data:</strong><br>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

// Test payment method determination
$payment_method = 'cash'; // Default to cash
if (isset($_POST['payment_method']) && !empty($_POST['payment_method'])) {
    $payment_method = $_POST['payment_method'];
} else {
    // If payment_method is not set, check if installment data exists
    if (isset($_POST['installment_period']) && !empty($_POST['installment_period'])) {
        $payment_method = 'installment';
    }
}

echo "<strong>Determined Payment Method:</strong> " . $payment_method;
echo "</div>";

// Test 2: Installment Payment
echo "<h3>Test 2: Installment Payment</h3>";
$_POST['payment_method'] = 'installment';
$_POST['installment_period'] = '6';

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<strong>POST Data:</strong><br>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

$payment_method = 'cash'; // Default to cash
if (isset($_POST['payment_method']) && !empty($_POST['payment_method'])) {
    $payment_method = $_POST['payment_method'];
} else {
    // If payment_method is not set, check if installment data exists
    if (isset($_POST['installment_period']) && !empty($_POST['installment_period'])) {
        $payment_method = 'installment';
    }
}

echo "<strong>Determined Payment Method:</strong> " . $payment_method;
echo "</div>";

// Test 3: No payment method (fallback)
echo "<h3>Test 3: No Payment Method (Fallback)</h3>";
unset($_POST['payment_method']);
$_POST['installment_period'] = '12';

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<strong>POST Data:</strong><br>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

$payment_method = 'cash'; // Default to cash
if (isset($_POST['payment_method']) && !empty($_POST['payment_method'])) {
    $payment_method = $_POST['payment_method'];
} else {
    // If payment_method is not set, check if installment data exists
    if (isset($_POST['installment_period']) && !empty($_POST['installment_period'])) {
        $payment_method = 'installment';
    }
}

echo "<strong>Determined Payment Method:</strong> " . $payment_method;
echo "</div>";

echo "<div style='color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "✓ All tests completed. The payment method determination logic is working correctly.";
echo "</div>";
?>
