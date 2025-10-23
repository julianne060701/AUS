-- Database Migration Script
-- Add employee_list field to installer_schedules table
-- Run this script to add the employee list field for tracking installation team members

-- Add employee_list field to installer_schedules table
ALTER TABLE `installer_schedules` 
ADD COLUMN `employee_list` text DEFAULT NULL AFTER `completion_image`;

-- Add completed_at field if it doesn't exist (for tracking completion time)
ALTER TABLE `installer_schedules` 
ADD COLUMN `completed_at` timestamp NULL DEFAULT NULL AFTER `employee_list`;

-- Add index for completed_at field for better performance
ALTER TABLE `installer_schedules` 
ADD INDEX `idx_completed_at` (`completed_at`);
