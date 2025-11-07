-- Migration: Add total_quantity_to_install column to existing product_quantity_sold_summary table
-- This migration adds the column if the table already exists
-- Created: 2025-01-XX

-- Add the column if it doesn't exist
-- Note: If the column already exists, this will produce an error which can be safely ignored
SET @dbname = DATABASE();
SET @tablename = 'product_quantity_sold_summary';
SET @columnname = 'total_quantity_to_install';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' int(11) NOT NULL DEFAULT 0 AFTER total_quantity_sold')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

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

