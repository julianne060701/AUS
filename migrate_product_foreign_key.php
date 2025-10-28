<?php
// Database migration script to add product_id foreign key to aircon_sales table
include 'config/conn.php';

echo "<h2>Database Migration: Adding Product Foreign Key</h2>\n";
echo "<pre>\n";

try {
    // Check if product_id column already exists
    $result = $conn->query("SHOW COLUMNS FROM aircon_sales LIKE 'product_id'");
    if ($result && $result->num_rows > 0) {
        echo "✓ product_id column already exists!\n";
    } else {
        echo "Adding product_id column...\n";
        
        // Add product_id column
        $sql = "ALTER TABLE `aircon_sales` ADD COLUMN `product_id` INT(11) DEFAULT NULL AFTER `sale_id`";
        if ($conn->query($sql)) {
            echo "✓ product_id column added successfully\n";
        } else {
            throw new Exception("Error adding product_id column: " . $conn->error);
        }
    }
    
    // Check if foreign key constraint already exists
    $result = $conn->query("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'aircon_sales' AND COLUMN_NAME = 'product_id' AND CONSTRAINT_NAME LIKE 'fk_%'");
    if ($result && $result->num_rows > 0) {
        echo "✓ Foreign key constraint already exists!\n";
    } else {
        echo "Adding foreign key constraint...\n";
        
        // Add foreign key constraint
        $sql = "ALTER TABLE `aircon_sales` ADD CONSTRAINT `fk_aircon_sales_product_id` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL ON UPDATE CASCADE";
        if ($conn->query($sql)) {
            echo "✓ Foreign key constraint added successfully\n";
        } else {
            echo "⚠ Warning: Could not add foreign key constraint: " . $conn->error . "\n";
            echo "This might be due to existing data conflicts. Continuing...\n";
        }
    }
    
    // Add index for better performance
    echo "Adding index for better performance...\n";
    $sql = "ALTER TABLE `aircon_sales` ADD INDEX `idx_product_id` (`product_id`)";
    if ($conn->query($sql)) {
        echo "✓ Index added successfully\n";
    } else {
        echo "⚠ Warning: Could not add index: " . $conn->error . "\n";
    }
    
    // Update existing records to link with products table
    echo "Updating existing records to link with products...\n";
    $sql = "UPDATE `aircon_sales` s 
            INNER JOIN `products` p ON s.aircon_model = p.product_name 
            SET s.product_id = p.id 
            WHERE s.product_id IS NULL";
    
    $result = $conn->query($sql);
    if ($result) {
        $affected_rows = $conn->affected_rows;
        echo "✓ Updated $affected_rows existing records with product_id\n";
    } else {
        echo "⚠ Warning: Could not update existing records: " . $conn->error . "\n";
    }
    
    // Add comment to document the new column
    echo "Adding column comment...\n";
    $sql = "ALTER TABLE `aircon_sales` MODIFY COLUMN `product_id` INT(11) DEFAULT NULL COMMENT 'Foreign key reference to products table'";
    if ($conn->query($sql)) {
        echo "✓ Column comment added successfully\n";
    } else {
        echo "⚠ Warning: Could not add column comment: " . $conn->error . "\n";
    }
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Test the Stock Out tab in the inventory report\n";
    echo "2. Create some test withdrawals to verify the new functionality\n";
    echo "3. The system will now properly link stock out records with product details\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
?>
