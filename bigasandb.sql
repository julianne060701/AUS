-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 17, 2025 at 02:43 PM
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
-- Database: `bigasandb`
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
  `aircon_model` varchar(255) DEFAULT NULL,
  `quantity_sold` int(11) DEFAULT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `date_of_sale` datetime DEFAULT NULL,
  `cashier` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Premium', '2025-08-06 03:57:08'),
(2, 'Regular', '2025-08-06 03:57:08'),
(3, 'Broken', '2025-08-06 03:57:08'),
(4, 'Standard', '2025-08-06 04:19:54'),
(5, 'Standard', '2025-08-06 04:20:53'),
(6, 'Window Type', '2025-08-16 13:22:11'),
(7, 'Split type wall-mounted', '2025-08-16 13:22:31');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `capacity` varchar(20) DEFAULT NULL,
  `buying_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `capacity`, `buying_price`, `selling_price`, `quantity`, `category_id`, `created_at`, `updated_at`) VALUES
(2, 'Carrier Split Type Inverter', '1.5HP', 25000.00, 28999.00, 10, 1, '2025-08-16 13:28:08', '2025-08-16 13:28:08'),
(3, 'Panasonic Split Type', '2.0HP', 30000.00, 33999.00, 7, 1, '2025-08-16 13:28:08', '2025-08-16 13:28:08'),
(4, 'LG Double Door Ref', '8 cu.ft', 18000.00, 19999.00, 5, 2, '2025-08-16 13:28:08', '2025-08-16 13:28:08'),
(5, 'Samsung Top Load', '10kg', 15000.00, 16999.00, 4, 3, '2025-08-16 13:28:08', '2025-08-16 13:28:08');

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
  `role` enum('admin','employee') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `full_name`, `password`, `role`, `created_at`) VALUES
(1, 'Admin', 'Admin 1', '$2y$10$cNntUpjwh.gr7wFHUi/mSOYl8QQsVH0ASu.9ASI/zbDxzhatW/4n6', 'admin', '2025-08-07 09:25:02'),
(2, 'staff', 'Staff 1', '$2y$10$oDFh1kvK88Ci69V8WFCSR.bv6xxWjt5BUANCreYgwymWDSD9JI0re', 'employee', '2025-08-07 09:47:31');

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
  ADD PRIMARY KEY (`sale_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

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
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`);

--
-- Constraints for table `rice_inventory`
--
ALTER TABLE `rice_inventory`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
