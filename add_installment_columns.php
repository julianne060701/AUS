<?php
include 'config/conn.php';

echo "Adding installment columns to aircon_sales table...\n\n";

try {
    // Check if columns already exist
    $result = $conn->query("SHOW COLUMNS FROM aircon_sales LIKE 'payment_method'");
    if ($result && $result->num_rows > 0) {
        echo "Installment columns already exist!\n";
    } else {
        echo "Adding installment columns...\n";
        
        // Add the columns one by one
        $columns = [
            "ADD COLUMN `payment_method` ENUM('cash', 'installment') DEFAULT 'cash' AFTER `cashier`",
            "ADD COLUMN `installment_period` INT(11) DEFAULT NULL AFTER `payment_method`",
            "ADD COLUMN `interest_rate` DECIMAL(5,2) DEFAULT NULL AFTER `installment_period`",
            "ADD COLUMN `interest_amount` DECIMAL(10,2) DEFAULT NULL AFTER `interest_rate`",
            "ADD COLUMN `monthly_payment` DECIMAL(10,2) DEFAULT NULL AFTER `interest_amount`",
            "ADD COLUMN `original_price` DECIMAL(10,2) DEFAULT NULL AFTER `monthly_payment`"
        ];
        
        foreach ($columns as $column) {
            $sql = "ALTER TABLE `aircon_sales` " . $column;
            echo "Executing: $sql\n";
            
            if ($conn->query($sql)) {
                echo "✓ Success\n";
            } else {
                echo "✗ Error: " . $conn->error . "\n";
            }
        }
        
        // Add indexes
        echo "\nAdding indexes...\n";
        $indexes = [
            "ADD INDEX `idx_payment_method` (`payment_method`)",
            "ADD INDEX `idx_installment_period` (`installment_period`)"
        ];
        
        foreach ($indexes as $index) {
            $sql = "ALTER TABLE `aircon_sales` " . $index;
            echo "Executing: $sql\n";
            
            if ($conn->query($sql)) {
                echo "✓ Success\n";
            } else {
                echo "✗ Error: " . $conn->error . "\n";
            }
        }
        
        // Update existing records to have 'cash' as default payment method
        echo "\nUpdating existing records...\n";
        $update_sql = "UPDATE `aircon_sales` SET `payment_method` = 'cash' WHERE `payment_method` IS NULL";
        if ($conn->query($update_sql)) {
            echo "✓ Updated existing records\n";
        } else {
            echo "✗ Error updating records: " . $conn->error . "\n";
        }
    }
    
    echo "\nFinal table structure:\n";
    $result = $conn->query('DESCRIBE aircon_sales');
    if ($result) {
        echo "Field Name | Type | Null | Key | Default\n";
        echo "-----------|------|------|-----|--------\n";
        while ($row = $result->fetch_assoc()) {
            echo sprintf("%-15s | %-20s | %-4s | %-3s | %s\n", 
                $row['Field'], 
                $row['Type'], 
                $row['Null'], 
                $row['Key'], 
                $row['Default'] ?? 'NULL'
            );
        }
    }
    
    echo "\n✓ Database update completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
