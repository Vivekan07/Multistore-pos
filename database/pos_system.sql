-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 19, 2025 at 06:55 AM
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
-- Database: `pos_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `store_id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'category 1', 'category 1aaaa', 'active', '2025-12-18 16:01:57', '2025-12-18 16:49:20'),
(2, 1, 'category 2', 'category 2', 'active', '2025-12-18 16:42:51', '2025-12-18 16:42:51');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_logs`
--

CREATE TABLE `inventory_logs` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_type` enum('sale','purchase','adjustment','return') NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_logs`
--

INSERT INTO `inventory_logs` (`id`, `store_id`, `product_id`, `user_id`, `transaction_type`, `quantity_change`, `previous_quantity`, `new_quantity`, `reference_id`, `notes`, `created_at`) VALUES
(1, 1, 1, 4, 'sale', -1, 100, 99, 1, 'Sale #SALE-20251218-7456', '2025-12-18 16:16:47'),
(2, 1, 1, 4, 'sale', -5, 99, 94, 2, 'Sale #SALE-20251218-7299', '2025-12-18 16:17:34'),
(3, 1, 1, 4, 'sale', -1, 94, 93, 3, 'Sale #SALE-20251218-0610', '2025-12-18 16:22:25'),
(4, 1, 1, 4, 'sale', -3, 93, 90, 4, 'Sale #SALE-20251218-3319', '2025-12-18 16:23:08'),
(5, 1, 1, 4, 'sale', -2, 90, 88, 5, 'Sale #SALE-20251218-1209', '2025-12-18 16:23:30'),
(6, 1, 1, 4, 'sale', -1, 88, 87, 6, 'Sale #SALE-20251218-6387', '2025-12-18 16:30:10'),
(7, 1, 3, 2, 'purchase', 100, 0, 100, NULL, 'Initial stock entry', '2025-12-18 16:41:51'),
(8, 1, 4, 2, 'purchase', 100, 0, 100, NULL, 'Initial stock entry', '2025-12-18 16:42:19'),
(9, 1, 5, 2, 'purchase', 100, 0, 100, NULL, 'Initial stock entry', '2025-12-18 16:43:21'),
(10, 1, 6, 2, 'purchase', 100, 0, 100, NULL, 'Initial stock entry', '2025-12-18 16:43:51'),
(11, 1, 7, 2, 'purchase', 100, 0, 100, NULL, 'Initial stock entry', '2025-12-18 16:55:16'),
(12, 1, 1, 4, 'sale', -7, 87, 80, 7, 'Sale #SALE-20251218-5577', '2025-12-18 17:01:56'),
(13, 1, 2, 4, 'sale', -1, 100, 99, 7, 'Sale #SALE-20251218-5577', '2025-12-18 17:01:56'),
(14, 1, 3, 4, 'sale', -1, 100, 99, 7, 'Sale #SALE-20251218-5577', '2025-12-18 17:01:56'),
(15, 1, 4, 4, 'sale', -1, 100, 99, 7, 'Sale #SALE-20251218-5577', '2025-12-18 17:01:56'),
(16, 1, 7, 4, 'sale', -1, 100, 99, 7, 'Sale #SALE-20251218-5577', '2025-12-18 17:01:56'),
(17, 1, 6, 4, 'sale', -2, 100, 98, 7, 'Sale #SALE-20251218-5577', '2025-12-18 17:01:56'),
(18, 1, 5, 4, 'sale', -2, 100, 98, 7, 'Sale #SALE-20251218-5577', '2025-12-18 17:01:56'),
(19, 1, 1, 4, 'sale', -10, 80, 70, 8, 'Sale #SALE-20251218-4098', '2025-12-18 17:03:12'),
(20, 1, 1, 4, 'sale', -5, 70, 65, 9, 'Sale #SALE-20251218-9433', '2025-12-18 17:04:07'),
(21, 1, 1, 4, 'sale', -4, 65, 61, 10, 'Sale #SALE-20251218-5584', '2025-12-18 17:05:07'),
(22, 1, 1, 4, 'sale', -1, 61, 60, 11, 'Sale #SALE-20251218-0444', '2025-12-18 17:06:37'),
(23, 1, 1, 4, 'sale', -2, 60, 58, 12, 'Sale #SALE-20251218-3593', '2025-12-18 17:06:48'),
(24, 1, 1, 4, 'sale', -8, 58, 50, 13, 'Sale #SALE-20251218-5526', '2025-12-18 17:07:07');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `payment_method` enum('cash','card','mixed') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `cash_amount` decimal(10,2) DEFAULT 0.00,
  `card_amount` decimal(10,2) DEFAULT 0.00,
  `change_amount` decimal(10,2) DEFAULT 0.00,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `sale_id`, `payment_method`, `amount`, `cash_amount`, `card_amount`, `change_amount`, `payment_date`) VALUES
(1, 1, 'cash', 150.00, 200.00, 0.00, 50.00, '2025-12-18 16:16:47'),
(2, 2, 'cash', 750.00, 1000.00, 0.00, 250.00, '2025-12-18 16:17:34'),
(3, 3, 'cash', 150.00, 200.00, 0.00, 50.00, '2025-12-18 16:22:25'),
(4, 4, 'cash', 450.00, 500.00, 0.00, 50.00, '2025-12-18 16:23:08'),
(5, 5, 'cash', 300.00, 500.00, 0.00, 200.00, '2025-12-18 16:23:30'),
(6, 6, 'cash', 150.00, 200.00, 0.00, 50.00, '2025-12-18 16:30:10'),
(7, 7, 'card', 2025.00, 0.00, 2025.00, 0.00, '2025-12-18 17:01:56'),
(8, 8, 'mixed', 1500.00, 1500.00, 0.00, 0.00, '2025-12-18 17:03:12'),
(9, 9, 'cash', 750.00, 750.00, 0.00, 0.00, '2025-12-18 17:04:07'),
(10, 10, 'card', 600.00, 0.00, 600.00, 0.00, '2025-12-18 17:05:07'),
(11, 11, 'card', 150.00, 0.00, 150.00, 0.00, '2025-12-18 17:06:37'),
(12, 12, 'card', 300.00, 0.00, 300.00, 0.00, '2025-12-18 17:06:48'),
(13, 13, 'card', 1200.00, 0.00, 1200.00, 0.00, '2025-12-18 17:07:07');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `stock_quantity` int(11) DEFAULT 0,
  `low_stock_threshold` int(11) DEFAULT 10,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `store_id`, `category_id`, `name`, `sku`, `barcode`, `description`, `product_image`, `cost_price`, `selling_price`, `tax_rate`, `stock_quantity`, `low_stock_threshold`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'product 1', 'size / 25L', '1212121212121212121211', 'gfdgdfgdfgfdgaaasdf', '1_1766076865.png', 100.00, 150.00, 0.00, 50, 10, 'active', '2025-12-18 16:02:42', '2025-12-18 17:07:07'),
(2, 1, 1, 'product 2', 'size / 100L', '', '', '2_1766075980.jpg', 100.00, 111.00, 0.00, 99, 10, 'active', '2025-12-18 16:39:40', '2025-12-18 17:01:56'),
(3, 1, 1, 'product 3', 'Default variant', '', '', '3_1766076111.jpg', 100.00, 111.00, 0.00, 99, 10, 'active', '2025-12-18 16:41:51', '2025-12-18 17:01:56'),
(4, 1, 1, 'product 4', 'SERVO FUTURA SYNTH 5W-50 / 100L', '', '', '4_1766076139.jpg', 100.00, 111.00, 0.00, 99, 10, 'active', '2025-12-18 16:42:19', '2025-12-18 17:01:56'),
(5, 1, 2, 'product 5', 'SERVO FUTURA SYNTH 5W-50', '', 'sdfsdfsdf', '5_1766076201.jpg', 100.00, 150.00, 0.00, 98, 10, 'active', '2025-12-18 16:43:21', '2025-12-18 17:01:56'),
(6, 1, 2, 'product 8', 'SERVO FUTURA SYNTH 5W-50 /', '', '', '6_1766076231.jpg', 100.00, 111.00, 0.00, 98, 10, 'active', '2025-12-18 16:43:51', '2025-12-18 17:01:56'),
(7, 1, 2, 'JS Distributor', 'SERVO FUTURA SYNTHaa', '', '', '7_1766076916.jpg', 100.00, 120.00, 0.00, 99, 10, 'active', '2025-12-18 16:55:16', '2025-12-18 17:21:43');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'super_admin', 'Super Administrator - Manages all stores', '2025-12-18 15:27:29'),
(2, 'admin', 'Store Administrator - Manages one store', '2025-12-18 15:27:29'),
(3, 'cashier', 'Cashier - Performs sales for one store', '2025-12-18 15:27:29');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `sale_number` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('completed','cancelled','refunded') DEFAULT 'completed',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `store_id`, `cashier_id`, `sale_number`, `total_amount`, `tax_amount`, `discount_amount`, `final_amount`, `sale_date`, `status`, `notes`) VALUES
(1, 1, 4, 'SALE-20251218-7456', 150.00, 0.00, 0.00, 150.00, '2025-12-18 16:16:47', 'completed', NULL),
(2, 1, 4, 'SALE-20251218-7299', 750.00, 0.00, 0.00, 750.00, '2025-12-18 16:17:34', 'completed', NULL),
(3, 1, 4, 'SALE-20251218-0610', 150.00, 0.00, 0.00, 150.00, '2025-12-18 16:22:25', 'completed', NULL),
(4, 1, 4, 'SALE-20251218-3319', 450.00, 0.00, 0.00, 450.00, '2025-12-18 16:23:08', 'completed', NULL),
(5, 1, 4, 'SALE-20251218-1209', 300.00, 0.00, 0.00, 300.00, '2025-12-18 16:23:30', 'completed', NULL),
(6, 1, 4, 'SALE-20251218-6387', 150.00, 0.00, 0.00, 150.00, '2025-12-18 16:30:10', 'completed', NULL),
(7, 1, 4, 'SALE-20251218-5577', 2025.00, 0.00, 0.00, 2025.00, '2025-12-18 17:01:56', 'completed', NULL),
(8, 1, 4, 'SALE-20251218-4098', 1500.00, 0.00, 0.00, 1500.00, '2025-12-18 17:03:12', 'completed', NULL),
(9, 1, 4, 'SALE-20251218-9433', 750.00, 0.00, 0.00, 750.00, '2025-12-18 17:04:07', 'completed', NULL),
(10, 1, 4, 'SALE-20251218-5584', 600.00, 0.00, 0.00, 600.00, '2025-12-18 17:05:07', 'completed', NULL),
(11, 1, 4, 'SALE-20251218-0444', 150.00, 0.00, 0.00, 150.00, '2025-12-18 17:06:37', 'completed', NULL),
(12, 1, 4, 'SALE-20251218-3593', 300.00, 0.00, 0.00, 300.00, '2025-12-18 17:06:48', 'completed', NULL),
(13, 1, 4, 'SALE-20251218-5526', 1200.00, 0.00, 0.00, 1200.00, '2025-12-18 17:07:07', 'completed', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `tax_rate`, `tax_amount`, `discount_percent`, `discount_amount`, `subtotal`) VALUES
(1, 1, 1, 'product 1', 1, 150.00, 0.00, 0.00, 0.00, 0.00, 150.00),
(2, 2, 1, 'product 1', 5, 150.00, 0.00, 0.00, 0.00, 0.00, 750.00),
(3, 3, 1, 'product 1', 1, 150.00, 0.00, 0.00, 0.00, 0.00, 150.00),
(4, 4, 1, 'product 1', 3, 150.00, 0.00, 0.00, 0.00, 0.00, 450.00),
(5, 5, 1, 'product 1', 2, 150.00, 0.00, 0.00, 0.00, 0.00, 300.00),
(6, 6, 1, 'product 1', 1, 150.00, 0.00, 0.00, 0.00, 0.00, 150.00),
(7, 7, 1, 'product 1', 7, 150.00, 0.00, 0.00, 0.00, 0.00, 1050.00),
(8, 7, 2, 'product 2', 1, 111.00, 0.00, 0.00, 0.00, 0.00, 111.00),
(9, 7, 3, 'product 3', 1, 111.00, 0.00, 0.00, 0.00, 0.00, 111.00),
(10, 7, 4, 'product 4', 1, 111.00, 0.00, 0.00, 0.00, 0.00, 111.00),
(11, 7, 7, 'JS Distributor', 1, 120.00, 0.00, 0.00, 0.00, 0.00, 120.00),
(12, 7, 6, 'product 8', 2, 111.00, 0.00, 0.00, 0.00, 0.00, 222.00),
(13, 7, 5, 'product 5', 2, 150.00, 0.00, 0.00, 0.00, 0.00, 300.00),
(14, 8, 1, 'product 1', 10, 150.00, 0.00, 0.00, 0.00, 0.00, 1500.00),
(15, 9, 1, 'product 1', 5, 150.00, 0.00, 0.00, 0.00, 0.00, 750.00),
(16, 10, 1, 'product 1', 4, 150.00, 0.00, 0.00, 0.00, 0.00, 600.00),
(17, 11, 1, 'product 1', 1, 150.00, 0.00, 0.00, 0.00, 0.00, 150.00),
(18, 12, 1, 'product 1', 2, 150.00, 0.00, 0.00, 0.00, 0.00, 300.00),
(19, 13, 1, 'product 1', 8, 150.00, 0.00, 0.00, 0.00, 0.00, 1200.00);

-- --------------------------------------------------------

--
-- Table structure for table `stores`
--

CREATE TABLE `stores` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stores`
--

INSERT INTO `stores` (`id`, `name`, `address`, `phone`, `email`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Store 1', 'aaaa', '0756393032', 'vivekanv93@gmail.com', 'active', '2025-12-18 15:49:38', '2025-12-18 15:49:38'),
(2, 'Store 2', 'chunnakamaa', '8677133164', 'admin@example.com', 'active', '2025-12-18 16:06:32', '2025-12-18 17:20:58');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `store_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `role_id`, `store_id`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'vivekan', '$2y$10$Fh8zX1tgiDIutG3ym.P5LerBUruzUVDfoJYcNjVBJ/QvZs8ECBt3.', 'Super Administrator', NULL, NULL, 1, NULL, 'active', '2025-12-18 17:20:51', '2025-12-18 15:27:29', '2025-12-18 17:20:51'),
(2, 'admin@rishvigems.com', '$2y$10$Kt0xGLj.ODiNvCwJfvLGze0QoplG.HTCyVMjDDt5/XUxtbhGTj/6S', 'Vivekan Vivek', 'vivekanv93@gmail.com', '0756393032', 2, 1, 'active', '2025-12-18 17:21:36', '2025-12-18 15:50:17', '2025-12-18 17:21:36'),
(3, 'clerk2@gmail.com', '$2y$10$QGcSZzIfZ44nAaOxCveJ9eChdvaWbw.xbhvVgSTQW.DfE.7hiJiEW', 'Indian engine oil.', 'admin@example.com', '0475639303', 2, 2, 'active', '2025-12-18 16:07:30', '2025-12-18 16:07:24', '2025-12-18 16:07:30'),
(4, 'cashierstore1', '$2y$10$guPl2/xArpOol27pQ5e7.ede2zV5L7RqOMwAkgAtK5fxTBXhB2tXK', 'cashierstore1', 'cashierstore1@gmail.com', '0471515151', 3, 1, 'active', '2025-12-19 05:35:40', '2025-12-18 16:08:51', '2025-12-19 05:35:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_store` (`store_id`);

--
-- Indexes for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_store_product` (`store_id`,`product_id`),
  ADD KEY `idx_product_date` (`product_id`,`created_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sale` (`sale_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_store_sku` (`store_id`,`sku`),
  ADD KEY `idx_store` (`store_id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_barcode` (`barcode`),
  ADD KEY `idx_sku` (`sku`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sale_number` (`sale_number`),
  ADD KEY `idx_store_date` (`store_id`,`sale_date`),
  ADD KEY `idx_cashier` (`cashier_id`),
  ADD KEY `idx_sale_number` (`sale_number`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sale` (`sale_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `stores`
--
ALTER TABLE `stores`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_store_role` (`store_id`,`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `stores`
--
ALTER TABLE `stores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_logs_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_logs_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
