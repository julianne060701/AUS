-- Create borrow_requests table for equipment/item borrowing system
-- This table tracks all borrow requests from employees and admin actions

CREATE TABLE IF NOT EXISTS `borrow_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL COMMENT 'User ID of the employee requesting to borrow',
  `product_id` int(11) DEFAULT NULL COMMENT 'Product/Equipment ID if item exists in products table',
  `item_name` varchar(255) DEFAULT NULL COMMENT 'Item name entered by employee',
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'Quantity to borrow',
  `borrow_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Date when request was made',
  `expected_return_date` date DEFAULT NULL COMMENT 'Expected return date',
  `status` enum('pending','approved','declined','returned') NOT NULL DEFAULT 'pending' COMMENT 'Request status',
  `admin_id` int(11) DEFAULT NULL COMMENT 'Admin who approved/declined/returned',
  `action_date` datetime DEFAULT NULL COMMENT 'Date when admin took action',
  `return_date` datetime DEFAULT NULL COMMENT 'Actual return date',
  `notes` text DEFAULT NULL COMMENT 'Additional notes from employee or admin',
  `admin_notes` text DEFAULT NULL COMMENT 'Admin notes when approving/declining',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_borrow_employee` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_borrow_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_borrow_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

