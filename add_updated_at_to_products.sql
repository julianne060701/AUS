-- Adds updated_at column to products and auto-updates on modification
ALTER TABLE products
	ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;


