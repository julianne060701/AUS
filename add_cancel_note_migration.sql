-- Database Migration Script
-- Add cancel_note field to installer_schedules table
-- Run this script to add the cancel note field for tracking cancellation reasons

-- Add cancel_note field to installer_schedules table
ALTER TABLE `installer_schedules` 
ADD COLUMN `cancel_note` text DEFAULT NULL AFTER `notes`;


