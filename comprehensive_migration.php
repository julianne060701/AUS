<?php
// Comprehensive database migration script
// 1. Adds missing serial_number column to products table
// 2. Adds product_id foreign key to aircon_sales table
include 'config/conn.php';

echo "<h2>Comprehensive Database Migration</h2>\n";
echo "<pre>\n";

try {
    // Step 1: Check and add serial_number column to products table
    echo "=== STEP 1: Adding serial_number column to products table ===\n";
    
    $result = $conn->query("SHOW COLUMNS FROM products LIKE 'serial_number'");
    if ($result && $result->num_rows > 0) {
        echo "✓ serial_number column already exists in products table\n";
    } else {
        echo "Adding serial_number column...\n";
        
        $sql = "ALTER TABLE `products` ADD COLUMN `serial_number` VARCHAR(100) DEFAULT NULL AFTER `product_name`";
        if ($conn->query($sql)) {
            echo "✓ serial_number column added successfully\n";
        } else {
            throw new Exception("Error adding serial_number column: " . $conn->error);
        }
        
        // Add index
        $sql = "ALTER TABLE `products` ADD INDEX `idx_serial_number` (`serial_number`)";
        if ($conn->query($sql)) {
            echo "✓ Index added for serial_number\n";
        }
    }
    
    echo "\n=== STEP 2: Adding product_id foreign key to aircon_sales table ===\n";
    
    // Step 2: Check and add product_id column to aircon_sales table
    $result = $conn->query("SHOW COLUMNS FROM aircon_sales LIKE 'product_id'");
    if ($result && $result->num_rows > 0) {
        echo "✓ product_id column already exists in aircon_sales table\n";
    } else {
        echo "Adding product_id column...\n";
        
        $sql = "ALTER TABLE `aircon_sales` ADD COLUMN `product_id` INT(11) DEFAULT NULL AFTER `sale_id`";
        if ($conn->query($sql)) {
            echo "✓ product_id column added successfully\n";
        } else {
            throw new Exception("Error adding product_id column: " . $conn->error);
        }
    }
    
    // Step 3: Add foreign key constraint
    $result = $conn->query("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'aircon_sales' AND COLUMN_NAME = 'product_id' AND CONSTRAINT_NAME LIKE 'fk_%'");
    if ($result && $result->num_rows > 0) {
        echo "✓ Foreign key constraint already exists\n";
    } else {
        echo "Adding foreign key constraint...\n";
        
        $sql = "ALTER TABLE `aircon_sales` ADD CONSTRAINT `fk_aircon_sales_product_id` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL ON UPDATE CASCADE";
        if ($conn->query($sql)) {
            echo "✓ Foreign key constraint added successfully\n";
        } else {
            echo "⚠ Warning: Could not add foreign key constraint: " . $conn->error . "\n";
        }
    }
    
    // Step 4: Add index for product_id
    echo "Adding index for product_id...\n";
    $sql = "ALTER TABLE `aircon_sales` ADD INDEX `idx_product_id` (`product_id`)";
    if ($conn->query($sql)) {
        echo "✓ Index added for product_id\n";
    } else {
        echo "⚠ Warning: Could not add index: " . $conn->error . "\n";
    }
    
    // Step 5: Update existing records to link with products table
    echo "\n=== STEP 3: Updating existing records ===\n";
    echo "Linking existing aircon_sales records with products...\n";
    
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
    
    echo "\n=== STEP 4: Final verification ===\n";
    
    // Check products table structure
    echo "Products table structure:\n";
    $result = $conn->query("DESCRIBE products");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['Field']} ({$row['Type']})\n";
        }
    }
    
    echo "\nAircon_sales table structure:\n";
    $result = $conn->query("DESCRIBE aircon_sales");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['Field']} ({$row['Type']})\n";
        }
    }
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "\nNow the Stock Out tab should work properly with:\n";
    echo "- Serial numbers from products.serial_number\n";
    echo "- Proper foreign key relationships\n";
    echo "- Better data integrity\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
?>
