-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 27, 2025 at 10:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ausdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `aircon_inventory`
--

CREATE TABLE `aircon_inventory` (
  `id` int(11) NOT NULL,
  `aircon_model` varchar(255) DEFAULT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `buying_price` decimal(10,2) DEFAULT NULL,
  `quantity_stock` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `aircon_sales`
--

CREATE TABLE `aircon_sales` (
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL COMMENT 'Foreign key reference to products table',
  `aircon_model` varchar(255) DEFAULT NULL,
  `quantity_sold` int(11) DEFAULT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `date_of_sale` datetime DEFAULT NULL,
  `cashier` varchar(255) DEFAULT NULL,
  `payment_method` enum('cash','installment') DEFAULT 'cash',
  `installment_period` int(11) DEFAULT NULL,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `interest_amount` decimal(10,2) DEFAULT NULL,
  `monthly_payment` decimal(10,2) DEFAULT NULL,
  `original_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `aircon_sales`
--

INSERT INTO `aircon_sales` (`sale_id`, `product_id`, `aircon_model`, `quantity_sold`, `selling_price`, `total_amount`, `date_of_sale`, `cashier`, `payment_method`, `installment_period`, `interest_rate`, `interest_amount`, `monthly_payment`, `original_price`) VALUES
(1, 5, 'Carrier Aura Inventer', 2, 20500.00, 36900.00, '2025-10-22 07:36:50', 'Admin', '', NULL, NULL, NULL, NULL, 41000.00),
(2, 5, 'Carrier Aura Inventer', 2, 0.00, 0.00, '2025-10-23 06:57:35', 'Admin', '', NULL, NULL, NULL, NULL, NULL),
(3, 6, 'Condura Inventer', 2, 0.00, 0.00, '2025-10-23 07:00:00', 'Admin', '', NULL, NULL, NULL, NULL, NULL),
(4, 7, 'Condura Window Type', 2, 0.00, 0.00, '2025-10-23 08:30:28', 'Admin', '', NULL, NULL, NULL, NULL, NULL),
(5, 7, 'Condura Window Type', 2, 0.00, 0.00, '2025-10-25 08:30:06', 'Admin', '', NULL, NULL, NULL, NULL, NULL),
(6, 6, 'Condura Inventer', 3, 0.00, 0.00, '2025-10-27 07:07:45', 'Admin', 'cash', NULL, NULL, NULL, NULL, NULL),
(7, 7, 'Condura Window Type', 5, 0.00, 0.00, '2025-10-27 14:10:28', 'Admin', 'cash', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `brand_id` int(11) NOT NULL,
  `brand_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`brand_id`, `brand_name`, `created_at`) VALUES
(1, 'Carrier', '2025-10-22 06:50:58'),
(2, 'Condura', '2025-10-23 06:59:08');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category_name`, `created_at`) VALUES
(1, 'Premiums', '2025-08-06 03:57:08'),
(2, 'Regular', '2025-08-06 03:57:08'),
(3, 'Broken', '2025-08-06 03:57:08'),
(4, 'Standard', '2025-08-06 04:19:54'),
(5, 'Standard', '2025-08-06 04:20:53'),
(6, 'Window Type', '2025-08-16 13:22:11'),
(7, 'Split type wall-mounted', '2025-08-16 13:22:31');

-- --------------------------------------------------------

--
-- Table structure for table `installer_schedules`
--

CREATE TABLE `installer_schedules` (
  `id` int(11) NOT NULL,
  `installer_name` varchar(255) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `schedule_date` date NOT NULL,
  `schedule_time` time NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `products_to_install` text DEFAULT NULL,
  `quantity_to_install` int(11) DEFAULT 1,
  `image_path` varchar(500) DEFAULT NULL,
  `completion_image` varchar(255) DEFAULT NULL,
  `employee_list` text DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `installer_schedules`
--

INSERT INTO `installer_schedules` (`id`, `installer_name`, `customer_name`, `contact_number`, `address`, `schedule_date`, `schedule_time`, `service_type`, `products_to_install`, `quantity_to_install`, `image_path`, `completion_image`, `employee_list`, `completed_at`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Installer 1', 'Juan Dela Cruz', '09481128870', 'Gensantos Foundation College Inc. Brgy. West', '2025-10-24', '15:00:00', 'Installation', 'Carrier Aura Inventer', 1, NULL, 'uploads/completion_images/completion_1_1761118945.png', NULL, '2025-10-22 07:42:25', '', 'Completed', '2025-10-22 07:39:07', '2025-10-22 07:42:25'),
(2, 'Installer 1', 'Sade Dennis', '+1 (605) 285-2158', 'Dolorum odio repudia', '2025-10-25', '14:51:00', 'Installation', 'Carrier Aura Inventer (2.5 HP) - Carrier [Split type wall-mounted]', 1, NULL, 'uploads/completion_images/completion_2_1761194215.png', 'red, green, blue', '2025-10-23 04:36:55', 'Delectus rerum accu', 'Completed', '2025-10-23 04:10:52', '2025-10-23 04:36:55'),
(3, 'Installer 1', 'Fritzie Lynn Jadol', '0909090', 'Calumpang', '2025-10-26', '15:00:00', 'Installation', 'Carrier Aura Inventer (2.5 HP) - Carrier [Split type wall-mounted]', 1, NULL, 'uploads/completion_images/completion_3_1761202998.png', 'Juan Dela Cruz\r\nMarvie paradero', '2025-10-23 07:03:18', '', 'Completed', '2025-10-23 07:00:41', '2025-10-23 07:03:18'),
(4, 'Installer 1', 'Juan Dela Cruz', '0808909', 'Calumpang', '2025-10-27', '04:32:00', 'Installation', 'Condura Window Type (2.0 HP) [Window Type]', 1, NULL, 'uploads/completion_images/completion_4_1761381230.png', 'r g b', '2025-10-25 08:33:50', '', 'Completed', '2025-10-25 08:33:19', '2025-10-25 08:33:50'),
(5, 'Installer 1', 'Juan Dela Cruz', '000889', 'Calumpang', '2025-10-28', '15:39:00', 'Maintenance', 'Condura Window Type (2.0 HP) [Window Type]', 1, NULL, NULL, NULL, NULL, '', 'Scheduled', '2025-10-25 08:39:52', '2025-10-25 08:39:52');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `capacity` varchar(20) DEFAULT NULL,
  `buying_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `brand_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `serial_number`, `capacity`, `buying_price`, `selling_price`, `quantity`, `category_id`, `created_at`, `updated_at`, `brand_id`) VALUES
(5, 'Carrier Aura Inventer', 'SNred', '2.5 HP', 18500.00, 20500.00, 0, 7, '2025-10-22 07:32:09', '2025-10-23 08:12:04', 1),
(6, 'Condura Inventer', '1463441', '2.0 HP', 20000.00, 20500.00, 25, 7, '2025-10-23 06:59:44', '2025-10-27 06:07:45', 2),
(7, 'Condura Window Type', 'N10274FB19484', '2.0 HP', 15200.00, 17000.00, 21, 6, '2025-10-23 08:30:04', '2025-10-27 06:10:28', NULL),
(8, 'Fan', 'Red1', '4.3', 450.00, 500.00, 21, 2, '2025-10-27 06:11:07', '2025-10-27 06:11:07', 1);

-- --------------------------------------------------------

--
-- Table structure for table `rice_inventory`
--

CREATE TABLE `rice_inventory` (
  `id` int(11) NOT NULL,
  `rice_type` varchar(100) DEFAULT NULL,
  `price_per_kg` decimal(10,2) DEFAULT NULL,
  `unit` varchar(10) DEFAULT NULL,
  `alert_threshold` decimal(10,2) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `sack_weight_kg` int(11) DEFAULT 50,
  `quantity_sacks` float(10,2) DEFAULT NULL,
  `quantity_kg` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rice_inventory`
--

INSERT INTO `rice_inventory` (`id`, `rice_type`, `price_per_kg`, `unit`, `alert_threshold`, `category_id`, `sack_weight_kg`, `quantity_sacks`, `quantity_kg`) VALUES
(1, 'Banay Banay', 30.00, 'sack', 15.00, 1, 50, 3.00, 195.00),
(2, 'Blue rice', 45.00, 'sack', 1.00, 2, 50, 1.00, 0.00),
(3, 'Reds', 30.00, 'sack', 20.00, 4, 50, 20.00, 1000.00),
(4, 'Jasmin', 45.00, 'sack', 10.00, 1, 50, 4.00, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `rice_type` varchar(100) DEFAULT NULL,
  `quantity_sold` decimal(10,2) DEFAULT NULL,
  `unit` varchar(10) DEFAULT NULL,
  `price_per_kg` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `date_of_sale` datetime DEFAULT current_timestamp(),
  `cashier` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sale_id`, `rice_type`, `quantity_sold`, `unit`, `price_per_kg`, `total_amount`, `date_of_sale`, `cashier`) VALUES
(1, 'Banay Banay', 15.00, 'sack', 30.00, 22500.00, '2025-08-07 08:26:11', 'staff'),
(2, 'Blue rice', 1.00, 'sack', 45.00, 2250.00, '2025-08-07 08:27:00', 'staff'),
(3, 'Banay Banay', 10.00, 'sack', 30.00, 15000.00, '2025-08-07 08:28:40', 'staff'),
(4, 'red', 5.00, 'sack', 30.00, 7500.00, '2025-08-07 08:30:34', 'staff'),
(5, 'red', 10.00, 'sack', 30.00, 15000.00, '2025-08-07 08:30:57', 'staff'),
(6, 'red', 5.00, 'sack', 30.00, 7500.00, '2025-08-07 08:31:19', 'staff'),
(7, 'Reds', 1.00, 'sack', 30.00, 1500.00, '2025-08-07 09:46:42', 'staff'),
(8, 'Reds', 1.00, 'sack', 30.00, 1500.00, '2025-08-07 09:51:56', 'staff'),
(9, 'Banay Banay', 5.00, 'sack', 30.00, 7500.00, '2025-08-07 09:52:38', 'staff'),
(10, 'Banay Banay', 10.00, 'kg', 30.00, 300.00, '2025-08-07 09:55:14', 'staff'),
(11, 'Reds', 15.00, 'sack', 30.00, 22500.00, '2025-08-07 10:07:43', 'staff'),
(12, 'Reds', 50.00, 'kg', 30.00, 1500.00, '2025-08-07 10:09:06', 'staff'),
(13, 'Reds', 50.00, 'kg', 30.00, 1500.00, '2025-08-07 10:16:13', 'staff'),
(14, 'Reds', 25.00, 'kg', 30.00, 750.00, '2025-08-07 10:16:59', 'staff'),
(15, 'Reds', 25.00, 'kg', 30.00, 750.00, '2025-08-07 10:22:15', 'staff'),
(16, 'Reds', 25.00, 'kg', 30.00, 750.00, '2025-08-07 10:23:46', 'staff'),
(17, 'Reds', 25.00, 'kg', 30.00, 750.00, '2025-08-07 10:24:12', 'staff'),
(18, 'Reds', 10.00, 'kg', 30.00, 300.00, '2025-08-07 10:37:43', 'staff'),
(19, 'Banay Banay', 0.00, 'sack', 30.00, 0.00, '2025-08-08 02:51:22', 'staff'),
(20, 'Banay Banay', 5.00, 'sack', 30.00, 7500.00, '2025-08-08 02:53:46', 'staff'),
(21, 'Reds', 40.00, 'kg', 30.00, 1200.00, '2025-08-08 03:04:32', 'staff'),
(22, 'Banay Banay', 240.00, 'kg', 30.00, 7200.00, '2025-08-08 03:38:44', 'staff'),
(23, 'Banay Banay', 0.00, 'kg', 30.00, 0.00, '2025-08-08 04:25:26', 'staff'),
(24, 'Banay Banay', 1.00, 'sack', 30.00, 1500.00, '2025-08-08 04:26:54', 'staff'),
(25, 'Banay Banay', 3.00, 'sack', 30.00, 4500.00, '2025-08-08 05:46:50', 'staff'),
(26, 'Reds', 1.00, 'sack', 30.00, 1500.00, '2025-08-08 05:48:16', 'staff'),
(27, 'Banay Banay', 25.00, 'kg', 30.00, 750.00, '2025-08-08 06:19:00', 'staff'),
(28, 'Banay Banay', 1.00, 'sack', 30.00, 1500.00, '2025-08-09 14:29:58', 'staff'),
(29, 'Banay Banay', 30.00, 'kg', 30.00, 900.00, '2025-08-09 14:45:06', 'Admin'),
(30, 'Jasmin', 16.00, 'sack', 45.00, 36000.00, '2025-08-09 15:26:34', 'Admin'),
(31, 'Jasmin', 10.00, 'sack', 45.00, 22500.00, '2025-08-09 15:27:01', 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `contact_info` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employee','installer') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `full_name`, `password`, `role`, `created_at`) VALUES
(1, 'Admin', 'Admin 1', '$2y$10$cNntUpjwh.gr7wFHUi/mSOYl8QQsVH0ASu.9ASI/zbDxzhatW/4n6', 'admin', '2025-08-07 09:25:02'),
(2, 'staff', 'Staff 1', '$2y$10$oDFh1kvK88Ci69V8WFCSR.bv6xxWjt5BUANCreYgwymWDSD9JI0re', 'employee', '2025-08-07 09:47:31'),
(3, 'installer', 'Installer 1', '$2y$10$zhQNIF9uqwR2UxpsXqCQBecSfNtvg0/Ouqm0CLpSnj091hVGje/Ni', 'installer', '2025-10-21 14:14:31'),
(4, 'installer 2', 'Installer 2', '$2y$10$AlSz10Vm5Cbud003ZA0U7OQXwVD5C/3E0MKOf1RPRaQMWDGl4HhH6', 'installer', '2025-10-22 07:41:19'),
(5, 'Roland', 'Sale', '$2y$10$GAbDm2Di3cTiNFphgDZnieV.DbRmtnU6CNx2pHKuVLGG3SNBX25ra', 'installer', '2025-10-26 16:09:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aircon_inventory`
--
ALTER TABLE `aircon_inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `aircon_sales`
--
ALTER TABLE `aircon_sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `idx_payment_method` (`payment_method`),
  ADD KEY `idx_installment_period` (`installment_period`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`brand_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `installer_schedules`
--
ALTER TABLE `installer_schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Indexes for table `rice_inventory`
--
ALTER TABLE `rice_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `aircon_inventory`
--
ALTER TABLE `aircon_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `aircon_sales`
--
ALTER TABLE `aircon_sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `brand_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `installer_schedules`
--
ALTER TABLE `installer_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `rice_inventory`
--
ALTER TABLE `rice_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `aircon_sales`
--
ALTER TABLE `aircon_sales`
  ADD CONSTRAINT `fk_aircon_sales_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`) ON DELETE SET NULL;

--
-- Constraints for table `rice_inventory`
--
ALTER TABLE `rice_inventory`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
