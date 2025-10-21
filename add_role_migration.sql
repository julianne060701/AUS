-- Add Installer Role Migration
-- This script adds an 'installer' role to the users table

-- Update the role enum to include 'installer'
ALTER TABLE `users` 
MODIFY COLUMN `role` enum('admin','employee','installer') NOT NULL;

-- Optional: Add any existing users to have a default role if needed
-- UPDATE `users` SET `role` = 'employee' WHERE `role` IS NULL;
