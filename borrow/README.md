# Borrower Module

This module allows employees to borrow equipment/items and admins to manage (approve/decline/return) borrow requests.

## Features

- **Employee Features:**
  - View available products/equipment
  - Submit borrow requests with quantity and expected return date
  - View their own borrow request history and status
  - See admin notes and action dates

- **Admin Features:**
  - View all borrow requests organized by status (Pending, Approved, Declined, Returned)
  - Approve or decline pending requests
  - Mark approved items as returned
  - Add admin notes for each action
  - View complete borrow history

## Database Setup

1. Run the SQL migration file to create the `borrow_requests` table:

```sql
-- Execute the SQL file
source borrow/create_borrow_table.sql;
```

Or manually run the SQL commands from `create_borrow_table.sql`.

## File Structure

```
borrow/
├── create_borrow_table.sql          # Database migration file
├── employee_borrow.php              # Employee interface for borrowing
├── admin_borrow_management.php      # Admin interface for managing requests
├── process_borrow_request.php       # Backend API for submitting requests
├── process_borrow_action.php        # Backend API for admin actions
└── README.md                        # This file
```

## Access

- **Employee Access:** Navigate to "Borrow Equipment/Items" in the employee sidebar
- **Admin Access:** Navigate to "Borrow Management" in the admin sidebar

## Workflow

1. **Employee submits request:**
   - Employee views available products
   - Clicks "Borrow" button
   - Fills in quantity, expected return date (optional), and notes (optional)
   - Submits request (status: pending)

2. **Admin reviews request:**
   - Admin sees request in "Pending" tab
   - Admin can approve or decline
   - Admin can add notes when taking action

3. **Item is returned:**
   - Admin marks approved item as "Returned"
   - System records return date
   - Item becomes available for borrowing again

## Status Flow

```
pending → approved → returned
pending → declined
```

## Database Schema

The `borrow_requests` table includes:
- `id` - Primary key
- `employee_id` - Foreign key to users table
- `product_id` - Foreign key to products table
- `quantity` - Number of items to borrow
- `borrow_date` - When request was made
- `expected_return_date` - Expected return date (optional)
- `status` - pending, approved, declined, returned
- `admin_id` - Admin who processed the request
- `action_date` - When admin took action
- `return_date` - When item was returned
- `notes` - Employee notes
- `admin_notes` - Admin notes

## Notes

- Available stock is calculated by subtracting currently borrowed items (approved + pending) from total product quantity
- Only items with available stock > 0 are shown to employees
- Admins can see all requests regardless of status
- Foreign key constraints ensure data integrity

