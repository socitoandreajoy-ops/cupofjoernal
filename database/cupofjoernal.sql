-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2026 at 04:48 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cupofjoernal`
--
CREATE DATABASE IF NOT EXISTS `cupofjoernal` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `cupofjoernal`;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'Coffee'),
(2, 'Tea'),
(3, 'Pastries'),
(4, 'Others'),
(5, 'Desserts');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `loyalty_points` int(11) NOT NULL DEFAULT 0,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_name`, `loyalty_points`, `email`, `phone`, `photo`) VALUES
(1, 'Hannah Lei', 8, NULL, NULL, NULL),
(2, 'Pearl Girao', 0, NULL, NULL, NULL),
(3, 'Dan Huertas', 0, NULL, NULL, NULL),
(4, 'Hannah Raymundo', 0, NULL, NULL, NULL),
(5, 'Amihan', 20, NULL, NULL, NULL),
(6, 'Pirena', 61, NULL, NULL, NULL),
(7, 'coco martin', 10, NULL, NULL, NULL),
(8, 'lola flora', 49, 'floradelfin@gmail.com', '+639452595872', NULL),
(9, 'Mariah Curry', 21, 'mariacurry@gmail.com', '09123456789', NULL),
(12, 'Juana', 0, NULL, NULL, NULL),
(13, 'Doraemon', 0, 'doraemon@gmail.com', '09876543210', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `role` enum('Barista','Cashier','Manager') NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `employee_name`, `role`, `username`, `password`) VALUES
(1, 'Clowie Geronimo', 'Cashier', 'clowie', '$2y$10$ZAbVybr5xzjR5nl1GzGj4O46g7hTDDSRwAs59XDwGZLFykAVI/g3u'),
(2, 'Cardo Dalisay', 'Cashier', NULL, NULL),
(3, 'Andrea Socito', 'Cashier', 'andrea', '$2y$10$SmeltglSPeTPth87Tr3IY.bliyZ5FfJrDHdJEt8zEC8YxbPQhK7Fy'),
(4, 'Rene Descartes', 'Barista', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `placed_by_name` varchar(100) DEFAULT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `order_type` enum('Take Out','To Go','Deliver') NOT NULL DEFAULT 'Take Out',
  `delivery_address` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Preparing','Completed','Cancelled') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `employee_id`, `placed_by_name`, `order_date`, `total_amount`, `status`) VALUES
(1, 1, 1, NULL, '2026-10-06 22:20:05', 400.00, 'Pending'),
(2, 5, 2, NULL, '2026-10-06 22:33:51', 1020.00, 'Pending'),
(3, 6, 2, NULL, '2026-10-07 13:52:03', 3080.00, 'Pending'),
(4, 7, NULL, NULL, '2026-10-27 22:06:35', 275.00, 'Pending'),
(5, 7, NULL, NULL, '2026-10-27 22:10:12', 275.00, 'Pending'),
(6, 8, NULL, NULL, '2026-10-27 23:07:34', 915.00, 'Pending'),
(7, 8, NULL, NULL, '2026-10-27 23:08:14', 1405.00, 'Pending'),
(8, 8, NULL, NULL, '2026-10-27 23:12:24', 150.00, 'Pending'),
(9, 9, NULL, NULL, '2026-10-28 08:54:42', 1075.00, 'Pending'),
(12, 12, NULL, NULL, '2026-10-28 09:04:10', 255.00, 'Pending'),
(13, 8, NULL, NULL, '2026-10-28 09:04:44', 2890.00, 'Pending'),
(14, 13, NULL, NULL, '2026-10-28 10:28:21', 4965.00, 'Pending'),
(15, 5, NULL, 'Administrator', '2026-10-28 10:36:46', 750.00, 'Pending'),
(16, 3, 3, 'Andrea Socito', '2026-10-28 10:55:21', 970.00, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 2, 90.00),
(2, 1, 5, 2, 110.00),
(3, 2, 1, 4, 90.00),
(4, 2, 7, 3, 145.00),
(5, 2, 14, 3, 75.00),
(6, 3, 1, 3, 90.00),
(7, 3, 10, 12, 135.00),
(8, 3, 12, 5, 90.00),
(9, 3, 14, 3, 75.00),
(10, 3, 18, 3, 90.00),
(11, 3, 19, 7, 35.00),
(12, 4, 1, 1, 90.00),
(13, 4, 16, 1, 150.00),
(14, 4, 19, 1, 35.00),
(15, 5, 1, 1, 90.00),
(16, 5, 16, 1, 150.00),
(17, 5, 19, 1, 35.00),
(18, 6, 16, 2, 150.00),
(19, 6, 19, 2, 35.00),
(20, 6, 10, 3, 135.00),
(21, 6, 3, 1, 140.00),
(22, 7, 16, 4, 150.00),
(23, 7, 19, 23, 35.00),
(24, 8, 16, 1, 150.00),
(25, 9, 2, 1, 130.00),
(26, 9, 5, 1, 110.00),
(27, 9, 3, 1, 140.00),
(28, 9, 6, 2, 135.00),
(29, 9, 17, 5, 85.00),
(30, 12, 9, 3, 85.00),
(31, 13, 4, 3, 120.00),
(32, 13, 2, 4, 130.00),
(33, 13, 8, 4, 70.00),
(34, 13, 15, 4, 80.00),
(35, 13, 18, 3, 90.00),
(36, 13, 11, 4, 95.00),
(37, 13, 9, 4, 85.00),
(38, 13, 20, 4, 105.00),
(39, 14, 1, 5, 90.00),
(40, 14, 4, 3, 120.00),
(41, 14, 8, 3, 70.00),
(42, 14, 16, 5, 150.00),
(43, 14, 25, 5, 200.00),
(44, 14, 18, 3, 90.00),
(45, 14, 15, 3, 80.00),
(46, 14, 13, 5, 85.00),
(47, 14, 12, 7, 90.00),
(48, 14, 19, 3, 35.00),
(49, 14, 20, 5, 105.00),
(50, 15, 1, 4, 90.00),
(51, 15, 2, 3, 130.00),
(52, 16, 1, 4, 90.00),
(53, 16, 5, 3, 110.00),
(54, 16, 8, 4, 70.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `price`, `stock_quantity`, `category_id`) VALUES
(1, 'Americano', 90.00, 13, 1),
(2, 'Caramel Latte', 130.00, 24, 1),
(3, 'Spanish Latte', 140.00, 42, 1),
(4, 'Cappuccino', 120.00, 23, 1),
(5, 'Cold Brew', 110.00, 15, 1),
(6, 'Vanilla Macchiato', 135.00, 26, 1),
(7, 'Mocha Fudge', 145.00, 17, 1),
(8, 'Espresso Shot', 70.00, 38, 1),
(9, 'Green Tea', 85.00, 16, 2),
(10, 'Matcha Latte', 135.00, 25, 2),
(11, 'Earl Grey Tea', 95.00, 27, 2),
(12, 'Peach Iced Tea', 90.00, 16, 2),
(13, 'Chamomile Tea', 85.00, 25, 2),
(14, 'Croissant', 75.00, 14, 3),
(15, 'Chocolate Muffin', 80.00, 11, 3),
(16, 'Blueberry Cheesecake', 150.00, 22, 5),
(17, 'Cinnamon Roll', 85.00, 10, 3),
(18, 'Red Velvet Cupcake', 90.00, 12, 5),
(19, 'Bottled Water', 35.00, 60, 4),
(20, 'Hot Chocolate', 105.00, 21, 4),
(23, 'Tiramisu', 199.00, 50, 5),
(24, 'Baklava', 150.00, 10, 5),
(25, 'Crème Brûlée', 200.00, 10, 5);

-- --------------------------------------------------------

--
-- Table structure for table `product_photos`
--

DROP TABLE IF EXISTS `product_photos`;
CREATE TABLE `product_photos` (
  `photo_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_photos`
--

INSERT INTO `product_photos` (`photo_id`, `product_id`, `filename`) VALUES
(1, 1, 'p1_0363a457f3a1.jpg'),
(2, 2, 'p2_3dde6bf737ae.jpg'),
(3, 3, 'p3_bd1a8803e778.jpg'),
(4, 4, 'p4_6a7234fbb533.jpg'),
(5, 5, 'p5_75445b06b324.jpg'),
(6, 6, 'p6_0f1590d3a5fe.jpg'),
(7, 7, 'p7_8e9dc99bf215.jpg'),
(8, 8, 'p8_cd2c077483ea.jpg'),
(9, 9, 'p9_b3fdf4d49c54.jpg'),
(10, 10, 'p10_26747d4b8bfe.jpg'),
(11, 11, 'p11_a67baedf7460.jpg'),
(12, 12, 'p12_e3c36a89593d.jpg'),
(13, 13, 'p13_8c671b7a45d9.jpg'),
(14, 14, 'p14_cd6a46d18edf.jpg'),
(15, 15, 'p15_221b157be903.jpg'),
(16, 16, 'p16_2b4dcca5b500.jpg'),
(17, 17, 'p17_b4df4f03203d.jpg'),
(18, 18, 'p18_b045c2f15b94.jpg'),
(19, 19, 'p19_af97227d2dcd.jpg'),
(20, 20, 'p20_1e913126f258.jpg'),
(21, 23, 'p23_c690ee9455c9.jpg'),
(22, 24, 'p24_ce5d4d759592.jpg'),
(23, 25, 'p25_4bcbc4f6c3e4.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier','customer') NOT NULL DEFAULT 'customer',
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `full_name`, `email`, `customer_id`, `is_active`, `photo`) VALUES
(1, 'admin', 'Admin@123', 'admin', 'Administrator', 'admin@cupofjoernal.com', NULL, 1, NULL),
(2, 'customer1', 'password123', 'customer', 'Customer One', 'customer1@example.com', NULL, 1, NULL),
(3, 'cocomartin@gmail.com', '$2y$10$zjg5abH4TiV.KStYFoMQOOVZvPvPg9FFmgAQmISUILpl8bsNQL6oe', 'customer', 'coco martin', 'cocomartin@gmail.com', 7, 1, NULL),
(4, 'floradelfin@gmail.com', '$2y$10$djuMSo/mXIN5VKm.RHlZ0.R88a6VuEkBAOKBwF9SXaoKaM6O39mZa', 'customer', 'lola flora', 'floradelfin@gmail.com', 8, 1, NULL),
(5, 'mariacurry@gmail.com', '$2y$10$1InBeUwzA0VxHAYdabK27.Y1uguTsS9LAdmn1Fu192W15s4Aij3sK', 'customer', 'Mariah Curry', 'mariacurry@gmail.com', 9, 1, NULL),
(6, 'clowie', '$2y$10$ZAbVybr5xzjR5nl1GzGj4O46g7hTDDSRwAs59XDwGZLFykAVI/g3u', 'cashier', 'Clowie Geronimo', 'clowie@cupofjoernal.com', NULL, 1, NULL),
(7, 'andrea', '$2y$10$SmeltglSPeTPth87Tr3IY.bliyZ5FfJrDHdJEt8zEC8YxbPQhK7Fy', 'cashier', 'Andrea Socito', 'andrea@cupofjoernal.com', NULL, 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `uq_employee_username` (`username`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_photos`
--
ALTER TABLE `product_photos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `uq_review` (`product_id`,`customer_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `customer_id` (`customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `product_photos`
--
ALTER TABLE `product_photos`
  MODIFY `photo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `product_photos`
--
ALTER TABLE `product_photos`
  ADD CONSTRAINT `product_photos_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);
--
-- View `vw_order_details`
--
DROP VIEW IF EXISTS `vw_order_details`;
CREATE VIEW `vw_order_details` AS
SELECT o.order_id AS order_id, o.order_date AS order_date, o.status AS status,
       c.customer_id AS customer_id, c.customer_name AS customer_name, c.email AS customer_email,
       e.employee_name AS employee_name, p.product_id AS product_id, p.product_name AS product_name,
       oi.quantity AS quantity, oi.unit_price AS unit_price, oi.quantity * oi.unit_price AS subtotal,
       SUM(oi.quantity * oi.unit_price) OVER (PARTITION BY o.order_id) AS grand_total
FROM orders o
JOIN customers c ON c.customer_id = o.customer_id
JOIN order_items oi ON oi.order_id = o.order_id
JOIN products p ON p.product_id = oi.product_id
LEFT JOIN employees e ON e.employee_id = o.employee_id;

-- ---------------------------------------------------------------
-- v2 additions: admin-editable bad words, password resets, "remember me" logins
-- ---------------------------------------------------------------

--
-- Table structure for table `bad_words` (managed by Admin > Bad Words)
--
DROP TABLE IF EXISTS `bad_words`;
CREATE TABLE `bad_words` (
  `word_id` int(11) NOT NULL AUTO_INCREMENT,
  `word` varchar(30) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`word_id`),
  UNIQUE KEY `uq_word` (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `bad_words` (`word`) VALUES
('fuck'),
('shit'),
('bitch'),
('asshole'),
('ass'),
('bastard'),
('damn'),
('crap'),
('dick'),
('piss'),
('gago'),
('tangina'),
('putangina'),
('bobo'),
('tanga'),
('ulol'),
('pakyu'),
('pakshet'),
('leche'),
('lintik'),
('tarantado'),
('buwisit'),
('siraulo');

--
-- Table structure for table `password_resets` (forgot-password links sent by email)
--
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reset_id`),
  UNIQUE KEY `uq_token` (`token_hash`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `remember_tokens` ("Keep me logged in" cookie)
--
DROP TABLE IF EXISTS `remember_tokens`;
CREATE TABLE `remember_tokens` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `selector` char(24) NOT NULL,
  `validator_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `uq_selector` (`selector`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
