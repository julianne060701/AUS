-- Migration: Create product_quantity_sold_summary table
-- This table tracks the total quantity sold for each product
-- Created: 2025-01-XX

CREATE TABLE IF NOT EXISTS `product_quantity_sold_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `total_quantity_sold` int(11) NOT NULL DEFAULT 0,
  `total_quantity_to_install` int(11) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`),
  CONSTRAINT `fk_product_quantity_sold_product` FOREIGN KEY (`product_id`) 
    REFERENCES `products` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Initialize the table with existing products (if any)
-- This will set total_quantity_sold and total_quantity_to_install to 0 for all existing products
INSERT IGNORE INTO `product_quantity_sold_summary` (`product_id`, `total_quantity_sold`, `total_quantity_to_install`)
SELECT `id`, 0, 0
FROM `products`
WHERE `id` NOT IN (SELECT `product_id` FROM `product_quantity_sold_summary`);

-- If there are existing sales, update the summary with actual totals
UPDATE `product_quantity_sold_summary` pqs
INNER JOIN (
    SELECT 
        product_id,
        COALESCE(SUM(quantity_sold), 0) as total_sold
    FROM `aircon_sales`
    WHERE product_id IS NOT NULL
    GROUP BY product_id
) sales ON pqs.product_id = sales.product_id
SET pqs.total_quantity_sold = sales.total_sold;

-- Update total_quantity_to_install from installer_schedules
-- Only count Installation service types where products_to_install is numeric (product_id)
UPDATE `product_quantity_sold_summary` pqs
INNER JOIN (
    SELECT 
        CAST(products_to_install AS UNSIGNED) as product_id,
        COALESCE(SUM(quantity_to_install), 0) as total_to_install
    FROM `installer_schedules`
    WHERE service_type = 'Installation' 
      AND products_to_install REGEXP '^[0-9]+$'  -- Only numeric values (product IDs)
      AND CAST(products_to_install AS UNSIGNED) > 0
    GROUP BY CAST(products_to_install AS UNSIGNED)
) installs ON pqs.product_id = installs.product_id
SET pqs.total_quantity_to_install = installs.total_to_install;

