# Installment Payment Database Setup

## Overview
This guide explains how to add installment payment functionality to your sales system database.

## Database Changes Required

### 1. Run the SQL Script
Execute the SQL script `database_update_installment.sql` in your database:

```sql
-- Add new columns to aircon_sales table
ALTER TABLE `aircon_sales` 
ADD COLUMN `payment_method` ENUM('cash', 'installment') DEFAULT 'cash' AFTER `cashier`,
ADD COLUMN `installment_period` INT(11) DEFAULT NULL AFTER `payment_method`,
ADD COLUMN `interest_rate` DECIMAL(5,2) DEFAULT NULL AFTER `installment_period`,
ADD COLUMN `interest_amount` DECIMAL(10,2) DEFAULT NULL AFTER `interest_rate`,
ADD COLUMN `monthly_payment` DECIMAL(10,2) DEFAULT NULL AFTER `interest_amount`,
ADD COLUMN `original_price` DECIMAL(10,2) DEFAULT NULL AFTER `monthly_payment`;
```

### 2. New Database Columns

| Column Name | Type | Description |
|-------------|------|-------------|
| `payment_method` | ENUM('cash', 'installment') | Payment method used |
| `installment_period` | INT(11) | Installment period in months (6, 12, or 24) |
| `interest_rate` | DECIMAL(5,2) | Interest rate percentage (3, 5, or 7) |
| `interest_amount` | DECIMAL(10,2) | Total interest amount calculated |
| `monthly_payment` | DECIMAL(10,2) | Monthly payment amount for installment |
| `original_price` | DECIMAL(10,2) | Original price before interest or discount |

### 3. Installment Interest Rates

- **6 months**: 3% interest
- **12 months**: 5% interest  
- **24 months**: 7% interest

## Features Added

### 1. Sales Form
- Payment method selection (Cash/Installment)
- Installment period selection (6, 12, 24 months)
- Real-time interest calculation
- Monthly payment calculation

### 2. Sales Display
- Payment method column in sales table
- Visual badges for cash vs installment payments
- Installment period and interest rate display

### 3. Database Storage
- All installment data is stored in the database
- Backward compatibility with existing cash sales
- Proper indexing for performance

## How to Use

### 1. Database Setup
1. Run the SQL script in your database
2. Verify the new columns were added successfully

### 2. Testing
1. Go to the Sales page
2. Click "New Sale"
3. Select a product and quantity
4. Choose "Installment" payment method
5. Select installment period (6, 12, or 24 months)
6. Complete the sale

### 3. Verification
1. Check the sales table to see the new columns populated
2. Verify installment sales show the correct payment method
3. Confirm interest calculations are accurate

## Example Data

### Cash Sale
```sql
INSERT INTO aircon_sales (aircon_model, quantity_sold, selling_price, total_amount, date_of_sale, cashier, payment_method, original_price) 
VALUES ('LG 1.5HP Inverter', 1, 30000.00, 27000.00, NOW(), 'Admin', 'cash', 30000.00);
```

### Installment Sale
```sql
INSERT INTO aircon_sales (aircon_model, quantity_sold, selling_price, total_amount, date_of_sale, cashier, payment_method, installment_period, interest_rate, interest_amount, monthly_payment, original_price) 
VALUES ('LG 1.5HP Inverter', 1, 30000.00, 31500.00, NOW(), 'Admin', 'installment', 12, 5.00, 1500.00, 2625.00, 30000.00);
```

## Troubleshooting

### Common Issues
1. **Columns not added**: Make sure you have proper database permissions
2. **Data not saving**: Check that the PHP code is using the correct column names
3. **Display issues**: Clear browser cache and check for JavaScript errors

### Verification Queries
```sql
-- Check if columns exist
DESCRIBE aircon_sales;

-- Check recent sales with installment data
SELECT sale_id, aircon_model, payment_method, installment_period, interest_rate, total_amount 
FROM aircon_sales 
ORDER BY sale_id DESC 
LIMIT 10;
```

## Support
If you encounter any issues, check the browser console for JavaScript errors and verify the database structure matches the expected schema.
