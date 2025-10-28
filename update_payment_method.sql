-- SQL script to add 'stock_out' to payment_method ENUM in aircon_sales table
-- This allows proper tracking of stock out transactions

-- Update the payment_method column to include 'stock_out'
ALTER TABLE `aircon_sales` 
MODIFY COLUMN `payment_method` ENUM('cash','installment','stock_out') DEFAULT 'cash' 
COMMENT 'Payment method: cash, installment, or stock_out';

-- Optional: Update existing stock out records (if any exist with selling_price = 0)
-- This will help identify any existing stock out records
UPDATE `aircon_sales` 
SET `payment_method` = 'stock_out' 
WHERE `selling_price` = 0 AND `total_amount` = 0 AND `payment_method` != 'stock_out';

-- Add comment to document the change
ALTER TABLE `aircon_sales` 
MODIFY COLUMN `payment_method` ENUM('cash','installment','stock_out') DEFAULT 'cash' 
COMMENT 'Payment method: cash, installment, or stock_out for inventory withdrawals';
