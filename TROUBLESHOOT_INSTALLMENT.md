# Troubleshooting Installment Payment Database Issue

## Problem
When adding a sale with installment payment method, the installment data (payment_method, installment_period, interest_rate, etc.) appears blank in the database.

## Step-by-Step Solution

### Step 1: Check Database Structure
Run this in your browser: `http://localhost/AUS/check_database.php`

This will show you:
- Current table structure
- Which installment columns exist
- Which columns are missing

### Step 2: Add Missing Columns
If columns are missing, run: `http://localhost/AUS/add_installment_columns.php`

This will:
- Add all required installment columns
- Add proper indexes
- Update existing records

### Step 3: Test the Insertion
Run: `http://localhost/AUS/test_installment.php`

This will:
- Test if installment data can be inserted
- Verify the data is stored correctly
- Clean up test data

### Step 4: Check Error Logs
Look at your PHP error log (usually in `C:\xampp\php\logs\php_error_log`) for any error messages.

### Step 5: Test Real Sale
1. Go to your sales page
2. Create a new sale
3. Select "Installment" payment method
4. Choose installment period (6, 12, or 24 months)
5. Complete the sale
6. Check the database to see if data was inserted

## Common Issues and Solutions

### Issue 1: Columns Don't Exist
**Error**: `Unknown column 'payment_method' in 'field list'`
**Solution**: Run `add_installment_columns.php`

### Issue 2: Data Type Mismatch
**Error**: `Incorrect decimal value`
**Solution**: Check that interest_rate is between 0-100, not 0-1

### Issue 3: NULL Values
**Problem**: Data shows as NULL in database
**Solution**: Check that installment_period is being passed correctly from the form

### Issue 4: Form Data Not Reaching PHP
**Problem**: Payment method shows as 'cash' even when 'installment' is selected
**Solution**: Check that the form field names match the PHP $_POST keys

## Verification Queries

Run these in phpMyAdmin to verify your data:

```sql
-- Check recent sales with installment data
SELECT sale_id, aircon_model, payment_method, installment_period, interest_rate, total_amount 
FROM aircon_sales 
ORDER BY sale_id DESC 
LIMIT 10;

-- Check if any installment sales exist
SELECT COUNT(*) as installment_count 
FROM aircon_sales 
WHERE payment_method = 'installment';

-- Check table structure
DESCRIBE aircon_sales;
```

## Expected Results

After fixing, you should see:
- `payment_method` column with 'installment' values
- `installment_period` column with 6, 12, or 24
- `interest_rate` column with 3, 5, or 7
- `interest_amount` column with calculated interest
- `monthly_payment` column with calculated monthly amount
- `original_price` column with the base price

## Still Having Issues?

1. Check browser console for JavaScript errors
2. Check PHP error logs
3. Verify form data is being submitted correctly
4. Test with a simple cash sale first
5. Make sure database user has ALTER and INSERT permissions
