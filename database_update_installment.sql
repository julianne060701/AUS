-- SQL script to add installment columns to aircon_sales table
-- Run this script in your database to add installment functionality

-- Add new columns to aircon_sales table
ALTER TABLE `aircon_sales` 
ADD COLUMN `payment_method` ENUM('cash', 'installment') DEFAULT 'cash' AFTER `cashier`,
ADD COLUMN `installment_period` INT(11) DEFAULT NULL AFTER `payment_method`,
ADD COLUMN `interest_rate` DECIMAL(5,2) DEFAULT NULL AFTER `installment_period`,
ADD COLUMN `interest_amount` DECIMAL(10,2) DEFAULT NULL AFTER `interest_rate`,
ADD COLUMN `monthly_payment` DECIMAL(10,2) DEFAULT NULL AFTER `interest_amount`,
ADD COLUMN `original_price` DECIMAL(10,2) DEFAULT NULL AFTER `monthly_payment`;

-- Add indexes for better performance
ALTER TABLE `aircon_sales` 
ADD INDEX `idx_payment_method` (`payment_method`),
ADD INDEX `idx_installment_period` (`installment_period`);

-- Update existing records to have 'cash' as default payment method
UPDATE `aircon_sales` SET `payment_method` = 'cash' WHERE `payment_method` IS NULL;

-- Optional: Add comments to document the new columns
ALTER TABLE `aircon_sales` 
MODIFY COLUMN `payment_method` ENUM('cash', 'installment') DEFAULT 'cash' COMMENT 'Payment method: cash or installment',
MODIFY COLUMN `installment_period` INT(11) DEFAULT NULL COMMENT 'Installment period in months (6, 12, or 24)',
MODIFY COLUMN `interest_rate` DECIMAL(5,2) DEFAULT NULL COMMENT 'Interest rate percentage (3, 5, or 7)',
MODIFY COLUMN `interest_amount` DECIMAL(10,2) DEFAULT NULL COMMENT 'Total interest amount calculated',
MODIFY COLUMN `monthly_payment` DECIMAL(10,2) DEFAULT NULL COMMENT 'Monthly payment amount for installment',
MODIFY COLUMN `original_price` DECIMAL(10,2) DEFAULT NULL COMMENT 'Original price before interest or discount';
