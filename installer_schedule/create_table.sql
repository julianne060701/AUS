-- Create installer_schedules table
CREATE TABLE IF NOT EXISTS `installer_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `installer_name` varchar(255) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `schedule_date` date NOT NULL,
  `schedule_time` time NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `products_to_install` text,
  `image_path` varchar(500),
  `notes` text,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data (optional)
INSERT INTO `installer_schedules` (`installer_name`, `customer_name`, `contact_number`, `address`, `schedule_date`, `schedule_time`, `service_type`, `notes`, `status`) VALUES
('John Smith', 'Maria Garcia', '09123456789', '123 Main Street, Quezon City', '2024-01-15', '09:00:00', 'Installation', 'Install water pump system', 'Scheduled'),
('Mike Johnson', 'Carlos Rodriguez', '09987654321', '456 Oak Avenue, Makati City', '2024-01-16', '14:30:00', 'Repair', 'Fix leaking pipes', 'Scheduled'),
('Sarah Wilson', 'Ana Santos', '09111222333', '789 Pine Street, Manila', '2024-01-17', '10:00:00', 'Maintenance', 'Regular maintenance check', 'Scheduled');
