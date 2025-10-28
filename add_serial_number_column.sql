-- SQL script to add missing serial_number column to products table
-- This column is expected by the PHP code but missing from the database schema

-- Add serial_number column to products table
ALTER TABLE `products` 
ADD COLUMN `serial_number` VARCHAR(100) DEFAULT NULL AFTER `product_name`;

-- Add index for better performance on serial number lookups
ALTER TABLE `products` 
ADD INDEX `idx_serial_number` (`serial_number`);

-- Add comment to document the new column
ALTER TABLE `products` 
MODIFY COLUMN `serial_number` VARCHAR(100) DEFAULT NULL 
COMMENT 'Unique serial number for the product (optional)';

-- Optional: Add unique constraint to ensure serial numbers are unique
-- ALTER TABLE `products` 
-- ADD UNIQUE KEY `unique_serial_number` (`serial_number`);
