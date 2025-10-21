# User Installer System

This document describes the new user installer system added to the AUS Inventory System.

## Overview

The user installer system provides multiple ways to add new users to the system with different levels of functionality and features.

## Files Created

### 1. `installer_index.php`
- **Purpose**: Main entry point for the installer system
- **Features**: 
  - Provides access to both basic and enhanced installers
  - Shows database migration status
  - Responsive design with modern UI

### 2. `user_installer.php`
- **Purpose**: Basic user installer with essential fields only
- **Features**:
  - Username, Full Name, Role, Password fields
  - Form validation and error handling
  - Password confirmation
  - Works with existing database schema
  - Modern, responsive design

### 3. `user_installer_enhanced.php`
- **Purpose**: Enhanced user installer with additional fields
- **Features**:
  - All basic installer features
  - Email and Phone number fields (if database migration is applied)
  - Automatic detection of database schema
  - Graceful fallback to basic fields if migration not applied

### 4. `database_migration.sql`
- **Purpose**: Database migration script to add extended fields
- **Features**:
  - Adds email and phone columns to users table
  - Creates indexes for better performance
  - Safe to run multiple times

## Usage

### Basic Installation
1. Navigate to `installer_index.php`
2. Click on "Basic Installer"
3. Fill in the required fields:
   - Username (unique)
   - Full Name
   - Role (Admin/Employee)
   - Password (minimum 6 characters)
4. Click "Install User"

### Enhanced Installation
1. First, run the database migration:
   ```sql
   -- Execute the contents of database_migration.sql
   ```
2. Navigate to `installer_index.php`
3. Click on "Enhanced Installer"
4. Fill in all fields including optional email and phone
5. Click "Install User"

## Database Schema

### Current Schema (Basic)
```sql
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employee') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
);
```

### Extended Schema (After Migration)
```sql
-- Additional fields added:
`email` varchar(255) DEFAULT NULL,
`phone` varchar(20) DEFAULT NULL
```

## Features

### Security
- Password hashing using PHP's `password_hash()` with BCRYPT
- Input validation and sanitization
- SQL injection prevention with prepared statements
- Username uniqueness checking

### User Experience
- Modern, responsive design
- Real-time form validation
- Clear error messages
- Success notifications
- Mobile-friendly interface

### Technical Features
- Automatic database schema detection
- Graceful fallback for missing fields
- Comprehensive error handling
- Clean, maintainable code structure

## Integration

The installer system integrates seamlessly with the existing user management system:

- Uses the same database connection (`config/conn.php`)
- Follows the same user table structure
- Compatible with existing user management pages
- Maintains data consistency

## Access Points

- **Main Index**: `installer_index.php`
- **Basic Installer**: `user_installer.php`
- **Enhanced Installer**: `user_installer_enhanced.php`
- **Database Migration**: `database_migration.sql`

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile responsive design
- JavaScript for enhanced UX (graceful degradation)

## Future Enhancements

Potential future improvements:
- Bulk user import functionality
- User role templates
- Advanced validation rules
- Integration with LDAP/Active Directory
- User activation workflows

