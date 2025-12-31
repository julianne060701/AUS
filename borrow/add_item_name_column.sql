-- Add item_name column to borrow_requests table
-- This allows employees to type in items that may not be in the products table

ALTER TABLE `borrow_requests` 
ADD COLUMN `item_name` varchar(255) DEFAULT NULL COMMENT 'Item name entered by employee' AFTER `product_id`,
MODIFY COLUMN `product_id` int(11) DEFAULT NULL COMMENT 'Product ID if item exists in products table';

