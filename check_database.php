<?php
include 'config/conn.php';

echo "Checking aircon_sales table structure...\n\n";

$result = $conn->query('DESCRIBE aircon_sales');
if ($result) {
    echo "Current aircon_sales table structure:\n";
    echo "Field Name | Type | Null | Key | Default | Extra\n";
    echo "-----------|------|------|-----|---------|-------\n";
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-10s | %-20s | %-4s | %-3s | %-7s | %s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key'], 
            $row['Default'] ?? 'NULL', 
            $row['Extra']
        );
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n\nChecking if installment columns exist...\n";
$installment_columns = ['payment_method', 'installment_period', 'interest_rate', 'interest_amount', 'monthly_payment', 'original_price'];

foreach ($installment_columns as $column) {
    $result = $conn->query("SHOW COLUMNS FROM aircon_sales LIKE '$column'");
    if ($result && $result->num_rows > 0) {
        echo "✓ Column '$column' exists\n";
    } else {
        echo "✗ Column '$column' does NOT exist\n";
    }
}

$conn->close();
?>
