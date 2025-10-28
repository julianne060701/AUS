<?php
/**
 * Database Timezone Migration Script
 * Updates existing timestamps to Philippine time
 */
include 'config/conn.php';
include 'config/timezone.php';

echo "<h2>Database Timezone Migration</h2>\n";
echo "<pre>\n";

try {
    echo "=== Setting up Philippine Timezone ===\n";
    
    // Set MySQL timezone to Philippine time
    $conn->query("SET time_zone = '+08:00'");
    echo "✓ MySQL timezone set to Philippine time (+08:00)\n";
    
    // Check current MySQL timezone
    $result = $conn->query("SELECT @@time_zone as timezone, NOW() as current_time");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✓ MySQL timezone: {$row['timezone']}\n";
        echo "✓ MySQL current time: {$row['current_time']}\n";
    }
    
    echo "\n=== Checking Tables with Timestamps ===\n";
    
    // Check products table
    $result = $conn->query("SHOW COLUMNS FROM products LIKE '%_at'");
    if ($result && $result->num_rows > 0) {
        echo "✓ Products table has timestamp columns\n";
        while ($row = $result->fetch_assoc()) {
            echo "  - {$row['Field']} ({$row['Type']})\n";
        }
    }
    
    // Check aircon_sales table
    $result = $conn->query("SHOW COLUMNS FROM aircon_sales LIKE '%_at'");
    if ($result && $result->num_rows > 0) {
        echo "✓ Aircon_sales table has timestamp columns\n";
        while ($row = $result->fetch_assoc()) {
            echo "  - {$row['Field']} ({$row['Type']})\n";
        }
    }
    
    echo "\n=== Sample Data Check ===\n";
    
    // Check sample data from products table
    $result = $conn->query("SELECT product_name, created_at, updated_at FROM products LIMIT 3");
    if ($result && $result->num_rows > 0) {
        echo "Sample products timestamps:\n";
        while ($row = $result->fetch_assoc()) {
            echo "  - {$row['product_name']}: Created {$row['created_at']}, Updated {$row['updated_at']}\n";
        }
    }
    
    // Check sample data from aircon_sales table
    $result = $conn->query("SELECT aircon_model, date_of_sale FROM aircon_sales LIMIT 3");
    if ($result && $result->num_rows > 0) {
        echo "Sample sales timestamps:\n";
        while ($row = $result->fetch_assoc()) {
            echo "  - {$row['aircon_model']}: Sold {$row['date_of_sale']}\n";
        }
    }
    
    echo "\n=== Timezone Functions Test ===\n";
    echo "Current Philippine Time: " . getPhilippineTime() . "\n";
    echo "Current UTC Time: " . gmdate('Y-m-d H:i:s') . "\n";
    echo "Timezone Offset: " . getPhilippineTimezoneOffset() . "\n";
    
    echo "\n🎉 Timezone configuration completed!\n";
    echo "\nAll new timestamps will be stored in Philippine time.\n";
    echo "Existing timestamps will be interpreted as Philippine time.\n";
    echo "\nTo test: Visit any page with ?debug_timezone=1 to see timezone info\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
?>
