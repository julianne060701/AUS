<?php
// Check if serial_number column exists in products table
include 'config/conn.php';

echo "<h2>Database Column Check: Products Table</h2>\n";
echo "<pre>\n";

try {
    // Check current table structure
    echo "Current products table structure:\n";
    $result = $conn->query("DESCRIBE products");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['Field']} ({$row['Type']}) - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
        }
    }
    
    echo "\n";
    
    // Check if serial_number column exists
    $result = $conn->query("SHOW COLUMNS FROM products LIKE 'serial_number'");
    if ($result && $result->num_rows > 0) {
        echo "✓ serial_number column EXISTS in products table\n";
    } else {
        echo "❌ serial_number column MISSING from products table\n";
        echo "\nThis explains why the stock out query isn't working properly!\n";
        echo "The PHP code expects a serial_number field but it doesn't exist in the database.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
?>
