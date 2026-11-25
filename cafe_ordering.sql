-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 26, 2026 at 12:43 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cafe_ordering`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `nama_kategori`, `gambar`) VALUES
(1, 'Coffee', NULL),
(2, 'Non Coffee', NULL),
(3, 'Foods', NULL),
(4, 'Snacks', NULL),
(5, 'Dessert', NULL),
(6, 'items', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nomor_pesanan` varchar(50) NOT NULL,
  `total_harga` decimal(12,2) NOT NULL,
  `catatan` text DEFAULT NULL,
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `status` enum('pending','diproses','siap','selesai','dibatalkan') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `nomor_pesanan`, `total_harga`, `catatan`, `metode_pembayaran`, `status`, `created_at`) VALUES
(1, 1, 'KLK-20261125-8312', 25000.00, 'Take Away', 'QRIS', 'dibatalkan', '2026-11-25 21:05:49'),
(2, 1, 'KLK-20261125-9580', 75000.00, 'Take Away', 'Cash', 'selesai', '2026-11-25 21:06:44'),
(3, 1, 'KLK-20261125-8985', 215000.00, 'Dine In (Meja: 87) | Catatan: less sugar', 'QRIS', 'selesai', '2026-11-25 21:07:57'),
(6, 1, 'KLK-20261125-3103', 18000.00, 'Dine In (Meja: 02) | Catatan: less sugar', 'QRIS', 'selesai', '2026-11-25 22:25:11'),
(7, 1, 'KLK-20261125-6892', 15000.00, 'Dine In (Meja: 02) | Catatan: es banyakin', 'QRIS', 'selesai', '2026-11-25 22:25:32'),
(8, 1, 'KLK-20261125-7707', 25000.00, 'Take Away', 'QRIS', 'selesai', '2026-11-25 22:25:50'),
(9, 1, 'KLK-20261125-6504', 18000.00, 'Dine In (Meja: 02)', 'QRIS', 'selesai', '2026-11-25 22:34:01'),
(10, 3, 'KLK-20261125-8361', 25000.00, 'Dine In (Meja: 03)', 'QRIS', 'selesai', '2026-11-25 22:59:33'),
(11, 2, 'KLK-20261126-7470', 100000.00, 'Dine In (Meja: 02) | Catatan: kjhg', 'QRIS', 'selesai', '2026-11-25 23:33:54'),
(12, 2, 'KLK-20261126-4950', 25000.00, 'Dine In (Meja: 87)', 'Cash', 'pending', '2026-11-25 23:41:40');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `jumlah`, `harga`, `subtotal`) VALUES
(1, 1, 1, 1, 25000.00, 25000.00),
(2, 2, 1, 3, 25000.00, 75000.00),
(3, 3, 1, 3, 25000.00, 75000.00),
(4, 3, 4, 1, 20000.00, 20000.00),
(5, 3, 5, 3, 30000.00, 90000.00),
(6, 3, 6, 2, 15000.00, 30000.00),
(7, 6, 7, 1, 18000.00, 18000.00),
(8, 7, 6, 1, 15000.00, 15000.00),
(9, 8, 3, 1, 25000.00, 25000.00),
(10, 9, 7, 1, 18000.00, 18000.00),
(11, 10, 1, 1, 25000.00, 25000.00),
(12, 11, 8, 1, 100000.00, 100000.00),
(13, 12, 1, 1, 25000.00, 25000.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(12,2) NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` enum('tersedia','habis') DEFAULT 'tersedia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `nama_produk`, `deskripsi`, `harga`, `gambar`, `status`, `created_at`) VALUES
(1, 1, 'Cafe Latte', 'Espresso dengan susu creamy', 25000.00, 'latte.jpg', 'tersedia', '2026-11-25 19:53:49'),
(2, 1, 'Cappuccino', 'Espresso dengan foam susu', 22000.00, 'cappuccino.jpg', 'tersedia', '2026-11-25 19:53:49'),
(3, 2, 'Matcha Latte', 'Minuman matcha dengan susu', 25000.00, 'matcha.jpg', 'tersedia', '2026-11-25 19:53:49'),
(4, 2, 'Chocolate', 'Minuman cokelat creamy', 20000.00, 'chocolate.jpg', 'tersedia', '2026-11-25 19:53:49'),
(5, 3, 'Chicken Rice', 'Nasi dengan ayam crispy', 30000.00, 'chicken.jpg', 'tersedia', '2026-11-25 19:53:49'),
(6, 4, 'French Fries', 'Kentang goreng renyah', 15000.00, 'fries.jpg', 'tersedia', '2026-11-25 19:53:49'),
(7, 5, 'Chocolate Cake', 'Kue cokelat lembut', 18000.00, 'cake.jpg', 'tersedia', '2026-11-25 19:53:49'),
(8, 6, 'desain', '', 100000.00, 'menu-1795649219-710.png', 'tersedia', '2026-11-25 23:15:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `no_hp`, `alamat`, `role`, `created_at`) VALUES
(1, 'Dedi Fitrianto', 'dedifitrianto3@gmail.com', '$2y$10$ZpFGQzp3Bj7nSK0LUGVKeOVEpWGHwjMgMDllJGrYw9UIko7PVq1XS', '085841954944', NULL, 'customer', '2026-11-25 19:58:49'),
(2, 'admin', 'admin@gmail.com', '$2y$10$2IzKu0l4XeQ/CcwaRRqoh.xT9C9ZzdLbj/bV9tDi2LtxEvH8ZgH7G', '085841954943', NULL, 'admin', '2026-11-25 21:18:16'),
(3, 'ijan', 'ijan@gmail.com', '$2y$10$EnCmNtpS1dsA7YMeuDU4BeQJGwOwdP.cExrZCerdoxFqlpulz00im', NULL, NULL, 'customer', '2026-11-25 22:42:27'),
(4, 'restu', 'restu@gmail.com', '$2y$10$uvwaS8MkuJIHRhU2iqTFIexicxQy5oFrC2hGLEqwPPQ8ysW5haAwC', NULL, NULL, 'customer', '2026-11-25 22:43:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_pesanan` (`nomor_pesanan`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
