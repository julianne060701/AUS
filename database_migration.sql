-- Database Migration Script
-- Add email and phone fields to users table
-- Run this script to extend the users table with additional fields

-- Add email field to users table
ALTER TABLE `users` 
ADD COLUMN `email` varchar(255) DEFAULT NULL AFTER `role`;

-- Add phone field to users table  
ALTER TABLE `users` 
ADD COLUMN `phone` varchar(20) DEFAULT NULL AFTER `email`;

-- Add index for email field for better performance
ALTER TABLE `users` 
ADD INDEX `idx_email` (`email`);

-- Add index for phone field for better performance
ALTER TABLE `users` 
ADD INDEX `idx_phone` (`phone`);

