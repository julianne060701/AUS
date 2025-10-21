<?php
// Start output buffering to prevent header issues
ob_start();

// Start session FIRST before any output
session_start();

// Include database connection
include '../config/conn.php';

// Check if sale_id is provided
if (!isset($_GET['sale_id']) || empty($_GET['sale_id'])) {
    die('Sale ID is required');
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
    
    if ($result->num_rows === 0) {
        die('Sale not found');
    }
    
    $sale = $result->fetch_assoc();
    
    // Set default payment method if not set (for backward compatibility)
    if (empty($sale['payment_method'])) {
        $sale['payment_method'] = 'cash';
    }
    
} catch (Exception $e) {
    die('Database error: ' . $e->getMessage());
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Sale #<?php echo $sale['sale_id']; ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            background: white;
        }
        .receipt {
            max-width: 300px;
            margin: 0 auto;
            border: 1px solid #000;
            padding: 15px;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .receipt-title {
            font-size: 14px;
            margin-bottom: 5px;
        }
        .receipt-info {
            font-size: 10px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .item-label {
            font-weight: bold;
        }
        .item-value {
            text-align: right;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .total-row {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #000;
            padding-top: 5px;
            margin-top: 10px;
        }
        .installment-details {
            background: #f8f9fa;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #dee2e6;
        }
        .installment-title {
            font-weight: bold;
            text-align: center;
            margin-bottom: 8px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
        }
        @media print {
            body { margin: 0; padding: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div class="company-name">BIGASAN STORE</div>
            <div class="receipt-title">SALES RECEIPT</div>
            <div class="receipt-info">
                Transaction #<?php echo str_pad($sale['sale_id'], 3, '0', STR_PAD_LEFT); ?><br>
                Date: <?php echo date('M d, Y g:i A', strtotime($sale['date_of_sale'])); ?><br>
                Cashier: <?php echo htmlspecialchars($sale['cashier']); ?>
            </div>
        </div>

        <div class="item-row">
            <span class="item-label">Product:</span>
            <span class="item-value"><?php echo htmlspecialchars($sale['aircon_model']); ?></span>
        </div>
        
        <div class="item-row">
            <span class="item-label">Quantity:</span>
            <span class="item-value"><?php echo $sale['quantity_sold']; ?> unit(s)</span>
        </div>
        
        <div class="item-row">
            <span class="item-label">Unit Price:</span>
            <span class="item-value">₱<?php echo number_format($sale['selling_price'], 2); ?></span>
        </div>

        <div class="divider"></div>

        <?php if ($sale['payment_method'] === 'installment'): ?>
            <div class="installment-details">
                <div class="installment-title">INSTALLMENT PAYMENT</div>
                
                <div class="item-row">
                    <span class="item-label">Original Price:</span>
                    <span class="item-value">₱<?php echo number_format($sale['original_price'], 2); ?></span>
                </div>
                
                <div class="item-row">
                    <span class="item-label">Installment Period:</span>
                    <span class="item-value"><?php echo $sale['installment_period']; ?> months</span>
                </div>
                
                <div class="item-row">
                    <span class="item-label">Interest Rate:</span>
                    <span class="item-value"><?php echo $sale['interest_rate']; ?>%</span>
                </div>
                
                <div class="item-row">
                    <span class="item-label">Interest Amount:</span>
                    <span class="item-value">₱<?php echo number_format($sale['interest_amount'], 2); ?></span>
                </div>
                
                <div class="item-row">
                    <span class="item-label">Monthly Payment:</span>
                    <span class="item-value">₱<?php echo number_format($sale['monthly_payment'], 2); ?></span>
                </div>
            </div>
        <?php else: ?>
            <div class="item-row">
                <span class="item-label">Payment Method:</span>
                <span class="item-value">Cash (10% Discount)</span>
            </div>
            
            <div class="item-row">
                <span class="item-label">Original Price:</span>
                <span class="item-value">₱<?php echo number_format($sale['original_price'], 2); ?></span>
            </div>
            
            <div class="item-row">
                <span class="item-label">Discount (10%):</span>
                <span class="item-value">-₱<?php echo number_format($sale['original_price'] - $sale['total_amount'], 2); ?></span>
            </div>
        <?php endif; ?>

        <div class="divider"></div>

        <div class="item-row total-row">
            <span class="item-label">TOTAL AMOUNT:</span>
            <span class="item-value">₱<?php echo number_format($sale['total_amount'], 2); ?></span>
        </div>

        <div class="footer">
            <div>Thank you for your purchase!</div>
            <div>Please keep this receipt for your records.</div>
            <div class="no-print" style="margin-top: 20px;">
                <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px;">
                    Print Receipt
                </button>
                <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; cursor: pointer; border-radius: 4px; margin-left: 10px;">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>

<?php
// End output buffering and flush content
ob_end_flush();
?>
