-- SQL script to add product_id foreign key to aircon_sales table
-- This creates a proper relationship between aircon_sales and products tables

-- Step 1: Add product_id column to aircon_sales table
ALTER TABLE `aircon_sales` 
ADD COLUMN `product_id` INT(11) DEFAULT NULL AFTER `sale_id`;

-- Step 2: Add foreign key constraint
ALTER TABLE `aircon_sales` 
ADD CONSTRAINT `fk_aircon_sales_product_id` 
FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Step 3: Add index for better performance
ALTER TABLE `aircon_sales` 
ADD INDEX `idx_product_id` (`product_id`);

-- Step 4: Update existing records to link with products table
-- This matches aircon_model with product_name to populate product_id
UPDATE `aircon_sales` s 
INNER JOIN `products` p ON s.aircon_model = p.product_name 
SET s.product_id = p.id 
WHERE s.product_id IS NULL;

-- Step 5: Add comment to document the new column
ALTER TABLE `aircon_sales` 
MODIFY COLUMN `product_id` INT(11) DEFAULT NULL 
COMMENT 'Foreign key reference to products table';

-- Optional: You can also add 'stock_out' to payment_method ENUM if needed
-- ALTER TABLE `aircon_sales` 
-- MODIFY COLUMN `payment_method` ENUM('cash','installment','stock_out') DEFAULT 'cash' 
-- COMMENT 'Payment method: cash, installment, or stock_out for inventory withdrawals';
