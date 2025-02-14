-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 14, 2025 at 09:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lokpixpc`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','superadmin') NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `email`, `password`, `role`) VALUES
(2, 'lokmen15.messabhia@gmail.com', '$2y$10$wxT9zfwaiIDn3sa1S7neGO2.YhxWt84naPkxmx9AEAGhYdyeiSN4S', 'superadmin'),
(10, 'lokmen14.messabhia@gmail.com', '$2y$10$O9wjcDEADVQKI4EitPTxreatk5G55lKJKS69oxQa8VLFG35skXUUu', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `buildyourpc_orders`
--

CREATE TABLE `buildyourpc_orders` (
  `id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `wilaya_id` int(11) DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` varchar(255) DEFAULT 'pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buildyourpc_orders`
--

INSERT INTO `buildyourpc_orders` (`id`, `user_email`, `phone`, `address`, `wilaya_id`, `total_price`, `status`, `order_date`) VALUES
(4, 'lokmen16.messabhia@gmail.com', '0794159854', '28 logement madjerda', 17, 2790.00, 'pending', '2024-12-21 15:59:50'),
(5, 'lokmen16.messabhia@gmail.com', '0794159854', '28 logement madjerda', 12, 2790.00, 'pending', '2024-12-21 16:13:07'),
(6, 'lokmen16.messabhia@gmail.com', '0794159854', '28 logement madjerda', 15, 31692.00, 'pending', '2024-12-21 19:20:58'),
(7, 'lokmen16.messabhia@gmail.com', '0794159854', '28 logement madjerda', 7, 23192.00, 'pending', '2024-12-21 19:56:26'),
(8, 'lokmen16.messabhia@gmail.com', '0794159854', '28 logement madjerda', 2, 11290.00, 'pending', '2024-12-21 20:45:02'),
(9, 'hammoudiwajdi@gmail.com', '0798626781', 'besbessa', 23, 517600.00, 'pending', '2024-12-30 14:57:58'),
(11, 'lokmen16.messabhia@gmail.com', '0794159854', '28 logement madjerda', 32, 41000.00, 'pending', '2025-01-05 08:54:21'),
(12, 'lokmen16.messabhia@gmail.com', '0794159854', '28 logement madjerda', 10, 41000.00, 'pending', '2025-01-05 08:54:51'),
(14, 'lokmen16.messabhia@gmail.com', '0794159854', '28 logement madjerda', 18, 39900.00, 'pending', '2025-01-29 17:21:34'),
(15, 'lokmen16.messabhia@gmail.com', '0794159854', '28 logement madjerda', 18, 11800.00, 'pending', '2025-01-29 17:26:23'),
(16, 'lokmen16.messabhia@gmail.com', '0794159854', '28 logement madjerda', 20, 41000.00, 'pending', '2025-01-29 17:27:28');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'PC Components'),
(2, 'Peripherals'),
(3, 'Networking'),
(4, 'Prebuilt Systems'),
(5, 'Laptops'),
(6, 'Accessories'),
(7, 'Software'),
(8, 'Gaming'),
(9, 'Special Deals'),
(10, 'tools');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `product_id`, `user_id`, `comment`, `created_at`) VALUES
(3, 38, 2, 'Gg', '2025-02-10 10:30:36');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `features`
--

CREATE TABLE `features` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `features`
--

INSERT INTO `features` (`id`, `title`, `description`, `photo`, `created_at`) VALUES
(5, 'rtx 4060', 'dasd', '22.jpeg', '2025-02-14 16:32:36'),
(6, '2024 Video Game Release Date Calendar', 'dasdad', 'X.png', '2025-02-14 16:32:44');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `admin_id`, `message`, `is_read`, `created_at`) VALUES
(2, 2, 'New order placed by user ID: 63 with total price: 20402', 1, '2024-12-22 13:24:40'),
(3, 2, 'New order placed by user ID: 63 with total price: 20402', 1, '2024-12-22 13:34:50'),
(4, 2, 'New order placed by user ID: 63 with total price: 20402', 1, '2024-12-22 13:59:42'),
(5, 2, 'New order placed by user ID: 63 with total price: 40804', 1, '2024-12-22 17:16:28'),
(6, 2, 'New order placed by user ID: 63 with total price: 20402', 1, '2024-12-22 18:28:47'),
(8, 2, 'New order placed by user ID: 1 with total price: 20402', 1, '2024-12-23 14:35:11'),
(9, 10, 'New order placed by user ID: 108 with total price: 2790', 1, '2024-12-30 13:05:52'),
(10, 2, 'New order placed by user ID: 108 with total price: 2790', 1, '2024-12-30 13:05:52'),
(11, 10, 'New order placed by user ID: 108 with total price: 2790', 1, '2024-12-30 13:09:42'),
(12, 2, 'New order placed by user ID: 108 with total price: 2790', 1, '2024-12-30 13:09:42'),
(13, 10, 'New order placed by user ID: 1 with total price: 152000', 1, '2024-12-30 15:03:47'),
(14, 2, 'New order placed by user ID: 1 with total price: 152000', 1, '2024-12-30 15:03:47'),
(15, 10, 'New order placed by user ID: 2 with total price: 230000', 1, '2025-01-29 17:05:26'),
(16, 2, 'New order placed by user ID: 2 with total price: 230000', 1, '2025-01-29 17:05:26'),
(17, 10, 'New order placed by user ID: 2 with total price: 115000', 1, '2025-02-10 10:19:02'),
(18, 2, 'New order placed by user ID: 2 with total price: 115000', 1, '2025-02-10 10:19:02'),
(19, 10, 'New order placed by user ID: 2 with total price: 60000', 1, '2025-02-10 10:32:29'),
(20, 2, 'New order placed by user ID: 2 with total price: 60000', 1, '2025-02-10 10:32:29'),
(21, 10, 'New order placed by user ID: 1 with total price: 152000', 1, '2025-02-11 14:37:34'),
(22, 2, 'New order placed by user ID: 1 with total price: 152000', 1, '2025-02-11 14:37:34'),
(23, 10, 'New order placed by user ID: 1 with total price: 60000', 1, '2025-02-11 14:40:30'),
(24, 2, 'New order placed by user ID: 1 with total price: 60000', 1, '2025-02-11 14:40:30');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `delivery_type` varchar(50) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) NOT NULL,
  `qrtoken` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `wilaya_id` int(11) NOT NULL,
  `tracking_number` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `email`, `phone`, `address`, `delivery_type`, `total_price`, `order_date`, `status`, `qrtoken`, `user_id`, `wilaya_id`, `tracking_number`) VALUES
(40, 'lokmen15.messabhia@gmail.com', '0657415715', 'besbessa', 'Express', 152000.00, '2024-12-30 15:03:47', 'validated', '98127ef5e0323993bb6e08f7f0214a61', 1, 1, 'Yfhh'),
(41, 'lokmen16.messabhia@gmail.com', '0657415715', '28 logement madjerda', 'Express', 230000.00, '2025-01-29 17:05:26', '', 'ad45d4a959b99d6e7226203d376824da', 2, 17, NULL),
(42, 'lokmen16.messabhia@gmail.com', '0657415715', '7XM4+RCM, Ex Rue Victor Hugo, Souk-Ahras', 'Standard', 115000.00, '2025-02-10 10:19:02', '', '2e5c45b926f465a939bc4e94aeb96810', 2, 5, NULL),
(43, 'lokmen16.messabhia@gmail.com', '0657415715', '7XM4+RCM, Ex Rue Victor Hugo, Souk-Ahras', 'Standard', 60000.00, '2025-02-10 10:32:29', '', '0663b6298c49d821078b6b085798f9b7', 2, 5, NULL),
(44, 'lokmen15.messabhia@gmail.com', '0657415715', '7XM4+RCM, Ex Rue Victor Hugo, Souk-Ahras', 'Express', 152000.00, '2025-02-11 14:37:33', '', '103d4dfeb67e046cc7e844ad33bdfd81', 1, 41, NULL),
(45, 'lokmen15.messabhia@gmail.com', '0657415715', '7XM4+RCM, Ex Rue Victor Hugo, Souk-Ahras', 'Express', 60000.00, '2025-02-11 14:40:28', '', '8ca4d2864bd7da3115f92f0972a65fe0', 1, 17, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `quantity`) VALUES
(24, 40, 15, 1),
(25, 41, 11, 2),
(26, 42, 11, 1),
(27, 43, 10, 1),
(28, 44, 15, 1),
(29, 45, 10, 1);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `buying_price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `subcategory_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `buying_price`, `stock`, `category_id`, `created_at`, `updated_at`, `subcategory_id`) VALUES
(10, 'AMD Ryzen 7 5800X', 'A high-performance 8-core, 16-thread unlocked desktop processor designed for gaming and content creation.', 60000.00, 50000.00, 23, 1, '2024-12-30 13:40:48', '2025-02-11 14:40:29', 2),
(11, 'Intel Core i9-13900K', 'A 13th Gen Intel Core desktop processor featuring 24 cores (8 Performance-cores and 16 Efficient-cores) and 32 threads, optimized for gaming and productivity.', 115000.00, 92000.00, 7, 1, '2024-12-30 13:46:42', '2025-02-10 10:19:02', 2),
(12, 'AMD Ryzen 5 5600G', 'A 6-core, 12-thread processor with integrated Radeon graphics, suitable for gaming and general computing.', 45000.00, 37000.00, 12, 1, '2024-12-30 13:48:56', '2024-12-30 13:48:56', 2),
(14, 'NVIDIA GeForce RTX 4070 Ti', 'A high-end graphics card offering advanced ray tracing and AI capabilities for gaming and creative applications.', 114000.00, 99000.00, 8, 1, '2024-12-30 13:52:04', '2024-12-30 13:52:04', 3),
(15, 'AMD Radeon RX 7900 XTX', 'A flagship GPU delivering exceptional performance for gaming and professional workloads.', 152000.00, 130000.00, 3, 1, '2024-12-30 13:54:27', '2025-02-11 14:37:34', 3),
(16, 'ASUS ROG Strix RTX 3080', 'A premium graphics card from ASUS\'s Republic of Gamers lineup, featuring advanced cooling and overclocking capabilities.', 108000.00, 80000.00, 4, 1, '2024-12-30 13:59:29', '2024-12-31 21:24:07', 3),
(17, 'ASUS TUF Gaming X570-PLUS (Wi-Fi)', 'A durable motherboard designed for gaming, supporting AMD Ryzen processors and featuring Wi-Fi connectivity.', 41000.00, 37000.00, 6, 1, '2024-12-30 14:05:21', '2024-12-30 14:05:21', 1),
(18, 'MSI MPG Z790 Edge', 'A high-performance motherboard designed for Intel processors, offering advanced features for gaming and overclocking.', 49000.00, 40000.00, 10, 1, '2024-12-30 14:08:06', '2024-12-30 14:08:06', 1),
(19, 'ASRock B550M Pro4', 'A micro ATX motherboard supporting AMD Ryzen processors, suitable for compact and efficient builds.', 22500.00, 17000.00, 12, 1, '2024-12-30 14:11:32', '2024-12-30 14:11:32', 1),
(20, 'Corsair Vengeance RGB Pro 16GB DDR4 3200MHz', 'High-performance memory modules with dynamic RGB lighting, optimized for gaming and overclocking.', 11800.00, 9900.00, 10, 1, '2024-12-30 14:12:53', '2024-12-30 14:12:53', 4),
(22, 'Kingston Fury Beast 32GB DDR5 6000MHz', 'Next-generation DDR5 memory offering high speeds and capacities for demanding applications.', 32900.00, 18000.00, 8, 1, '2024-12-30 14:15:44', '2024-12-30 14:15:44', 4),
(23, 'G.SKILL Trident Z5 64GB DDR5 5600MHz', 'Premium DDR5 memory kit designed for high-performance computing and gaming.', 60000.00, 48000.00, 8, 1, '2024-12-30 14:17:50', '2024-12-30 14:17:50', 4),
(24, 'Samsung 980 Pro 2TB NVMe SSD', 'A high-speed solid-state drive offering exceptional read and write speeds for demanding applications.', 39900.00, 29900.00, 5, 1, '2024-12-30 14:19:42', '2024-12-30 14:19:42', 5),
(25, 'Seagate BarraCuda 4TB HDD', 'A reliable and high-capacity hard drive suitable for desktop storage needs.', 20000.00, 12000.00, 10, 1, '2024-12-30 14:21:14', '2024-12-30 14:21:14', 5),
(26, 'Western Digital Black SN850X 1TB NVMe SSD', 'A high-performance NVMe SSD designed for gaming and demanding workloads.', 25000.00, 14000.00, 15, 1, '2024-12-30 14:23:14', '2024-12-30 14:23:14', 5),
(27, 'Corsair RM850x 850W 80+ Gold', 'A fully modular power supply unit offering high efficiency and reliable power delivery.', 19900.00, 11900.00, 10, 1, '2024-12-30 14:24:53', '2024-12-30 14:24:53', 6),
(28, 'EVGA 600W 80+ Bronze', 'A budget-friendly power supply unit providing reliable performance for entry-level systems.', 11900.00, 7900.00, 14, 1, '2024-12-30 14:26:35', '2024-12-30 14:26:35', 6),
(30, 'Seasonic Prime TX-1000 1000W Titanium', 'A top-tier power supply unit offering exceptional efficiency and performance for high-end systems.', 34900.00, 27900.00, 10, 1, '2024-12-30 14:29:45', '2024-12-30 14:29:45', 6),
(31, 'NZXT H7 Flow Mid-Tower Case', 'A mid-tower case designed for optimal airflow and ease of building, suitable for gaming PCs.', 14900.00, 8900.00, 4, 1, '2024-12-30 14:31:00', '2024-12-30 14:31:00', 7),
(32, 'Corsair iCUE 4000X RGB', 'A stylish mid-tower ATX case featuring tempered glass and customizable RGB lighting.', 11900.00, 7400.00, 5, 1, '2024-12-30 14:32:36', '2024-12-30 14:32:36', 7),
(33, 'Noctua NH-D15 CPU Cooler', 'A premium dual-tower CPU cooler known for its exceptional cooling performance and quiet operation.', 9200.00, 6500.00, 20, 1, '2024-12-30 14:34:48', '2024-12-30 14:34:48', 8),
(34, 'Lian Li Lancool II Mesh', 'A high-airflow mid-tower case designed for optimal cooling and hardware compatibility.', 13500.00, 8600.00, 5, 1, '2024-12-30 14:36:11', '2024-12-30 14:36:11', 7),
(35, 'NZXT Kraken Z73 RGB Liquid Cooler', 'A high-performance all-in-one liquid cooler featuring customizable RGB lighting and a digital display.', 24900.00, 16600.00, 8, 1, '2024-12-30 14:38:06', '2024-12-30 14:38:06', 8),
(36, 'Arctic MX-6 Thermal Paste', 'A high-quality thermal compound designed to improve heat transfer between the CPU and cooler.', 1200.00, 600.00, 30, 10, '2024-12-30 14:42:00', '2024-12-30 14:42:00', 39),
(37, 'LG UltraGear 27GP850 27\" QHD 165Hz', 'The LG UltraGear 27GP850-B is a 27-inch QHD gaming monitor with a Nano IPS panel for vibrant colors and a 165Hz refresh rate for smooth gameplay. It features a 1ms response time, HDR10 support, and is G-SYNC and FreeSync compatible to eliminate screen tearing. With an ergonomic design and multiple connectivity options, it’s perfect for immersive gaming and productivity.', 67500.00, 42000.00, 3, 2, '2024-12-30 14:47:40', '2024-12-30 14:47:40', 13),
(38, 'Dell Alienware AW2521H 25\" FHD 360Hz Monitor', '25-inch FHD gaming monitor with a 360Hz refresh rate and 1ms response time for ultra-smooth gameplay. It features an IPS panel, NVIDIA G-SYNC, and customizable RGB lighting, along with an ergonomic stand and multiple connectivity options. Ideal for competitive gaming.', 54900.00, 39000.00, 3, 2, '2024-12-30 14:51:21', '2024-12-30 14:51:21', 13);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `is_primary`, `display_order`, `created_at`) VALUES
(17, 10, '6772a2e04b13d_th.jpeg', 1, 1, '2024-12-30 13:40:48'),
(18, 11, '6772a442e1362_09.jpg', 1, 1, '2024-12-30 13:46:42'),
(19, 12, '6772a4c86f4f0_LD0005868714_1.jpg', 1, 1, '2024-12-30 13:48:56'),
(20, 14, '6772a584d78f1_TUF_Gaming_GeForce_RTX_4070_Ti_5.jpg', 1, 1, '2024-12-30 13:52:04'),
(21, 15, '6772a613415ef_35113700-8301-11ed-bfff-e0bcea1dcf95.cf.jpg', 1, 1, '2024-12-30 13:54:27'),
(22, 16, '6772a74196b3e_14-126-483-V18.jpg', 1, 1, '2024-12-30 13:59:29'),
(23, 17, '6772a8a1c1569_6356983cv13d.avif', 1, 1, '2024-12-30 14:05:21'),
(24, 17, '6772a8a1c1947_bt7d7txjhkd4wfs0_setting_xxx_0_90_end_800.png', 0, 2, '2024-12-30 14:05:21'),
(25, 18, '6772a9467c0f7_13-144-564-V02.jpg', 1, 1, '2024-12-30 14:08:06'),
(26, 19, '6772aa1415aa6_B550M_Pro4-1.png', 1, 1, '2024-12-30 14:11:32'),
(27, 20, '6772aa658b1b9_6256216_sd.avif', 1, 1, '2024-12-30 14:12:53'),
(28, 22, '6772ab1008ebb_20220525121918_kingston_fury_beast_rgb_32gb_ddr5_ram_me_2_modules_2x16gb_kai_sychnotita_6000mhz_gia_desktop_kf560c40bbak2_32.jpeg', 1, 1, '2024-12-30 14:15:44'),
(29, 23, '6772ab8e7fd8b_gskill-64gb-ddr5-5600mhz-kit2x32gb-trident-z5-rgb-black.jpg', 1, 1, '2024-12-30 14:17:50'),
(30, 24, '6772abfe8d108_PRO2TB1-1200x1200.jpg', 1, 1, '2024-12-30 14:19:42'),
(31, 25, '6772ac5a2e923_6164933_sd.avif', 1, 1, '2024-12-30 14:21:14'),
(32, 26, '6772acd29bb49_ssdwd.jpeg', 1, 1, '2024-12-30 14:23:14'),
(33, 27, '6772ad35dd5c5_CP-9020180-UK-RM850x_PSU_01-1200x1200.png', 1, 1, '2024-12-30 14:24:53'),
(34, 28, '6772ad9bd578a_100-B1-0600-K2_XL_1.png', 1, 1, '2024-12-30 14:26:35'),
(36, 30, '6772ae594d86b_6414262cv15d.avif', 1, 1, '2024-12-30 14:29:45'),
(37, 31, '6772aea4e3904_1654199873-case_h7_flow_b_hero_with-system_with-frontlight_pl_png.avif', 1, 1, '2024-12-30 14:31:00'),
(38, 32, '6772af049dcf1_base-4000x-rgb-config-Gallery-4000X-BLACK-42.jpg', 1, 1, '2024-12-30 14:32:36'),
(39, 33, '6772af880a6da_4H8d9JYpYSaoYWBjR3GAfZ.jpg', 1, 1, '2024-12-30 14:34:48'),
(40, 33, '6772af880ac28_NOCTUA-NH-D15_Render_official_1.jpg', 0, 2, '2024-12-30 14:34:48'),
(41, 34, '6772afdb6e676_lian_li_lan2mpx_50_lancool_ii_mesh_performance_1674610.jpg', 1, 1, '2024-12-30 14:36:11'),
(42, 35, '6772b04e3e103_20220912023646.jpg', 1, 1, '2024-12-30 14:38:06'),
(43, 36, '6772b13868e75_MX-6_4g_G00.png', 1, 1, '2024-12-30 14:42:00'),
(44, 37, '6772b28ce60d2_7311-lg-ultragear-27gp850-b-27-led-nanoips-qhd-165hz-g-sync-compatible-8c8bfa12-6f86-4764-a40b-3e7bfae9d278.webp', 1, 1, '2024-12-30 14:47:40'),
(45, 37, '6772b28ce702b_my-11134207-7r98y-ll4vmuxzsrp00e.jpeg', 0, 2, '2024-12-30 14:47:40'),
(46, 38, '6772b36990ad3_20201217110844_37a1b847.jpeg', 1, 1, '2024-12-30 14:51:21');

-- --------------------------------------------------------

--
-- Table structure for table `recycle_requests`
--

CREATE TABLE `recycle_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `category_id` int(11) NOT NULL,
  `subcategory_id` int(11) NOT NULL,
  `component_condition` enum('Working','Not working','Damaged') NOT NULL,
  `photo` varchar(255) NOT NULL,
  `pickup_option` enum('dropoff','pickup') NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','validated') NOT NULL DEFAULT 'pending',
  `part_name` varchar(255) NOT NULL,
  `buying_year` year(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recycle_requests`
--

INSERT INTO `recycle_requests` (`id`, `user_id`, `email`, `phone`, `category_id`, `subcategory_id`, `component_condition`, `photo`, `pickup_option`, `submitted_at`, `status`, `part_name`, `buying_year`) VALUES
(85, 1, 'lokmen15.messabhia@gmail.com', '0657415715', 3, 18, 'Working', 'uploads/1739457745_9845b8c1-c4ce-40d0-a7cd-303337a58992.jfif', 'dropoff', '2025-02-13 14:42:25', 'pending', '', '0000'),
(86, 1, 'lokmen15.messabhia@gmail.com', '0657415715', 1, 8, 'Working', 'uploads/1739462956_1000000569.jpg', 'dropoff', '2025-02-13 16:09:16', 'validated', '', '0000'),
(87, 1, 'lokmen15.messabhia@gmail.com', '0657415715', 1, 7, 'Damaged', 'uploads/1739467975_61dgFvASgSL._AC_SL1500.webp', 'dropoff', '2025-02-13 17:32:55', 'pending', '', '0000'),
(88, 1, 'lokmen15.messabhia@gmail.com', '0657415715', 1, 3, 'Working', 'uploads/1739468273_461194355_904405968237725_2056302997289649851_n.png', 'dropoff', '2025-02-13 17:37:53', 'pending', '', '0000'),
(89, 1, 'lokmen15.messabhia@gmail.com', '0657415715', 1, 3, 'Damaged', 'uploads/1739468466_458614250_508180308646981_3060937892217739441_n.jpg', 'dropoff', '2025-02-13 17:41:06', 'pending', 'rx580', '2023'),
(90, 1, 'lokmen15.messabhia@gmail.com', '0657415715', 1, 3, 'Working', 'uploads/1739469087_1000000568.jpg', 'dropoff', '2025-02-13 17:51:27', 'validated', 'Rtx3070', '2023');

-- --------------------------------------------------------

--
-- Table structure for table `slider_photos`
--

CREATE TABLE `slider_photos` (
  `id` int(11) NOT NULL,
  `photo_url` varchar(255) NOT NULL,
  `caption` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `slider_photos`
--

INSERT INTO `slider_photos` (`id`, `photo_url`, `caption`, `created_at`) VALUES
(6, 'uploads/banner-s.jpg', 'you made it', '2024-12-23 20:17:25'),
(7, 'uploads/les-7-grandes-idees-esthetiques-pour-votre-deco-gaming-742923.jpg', '', '2024-12-23 20:33:10'),
(8, 'uploads/gaming6.jpg', '', '2024-12-23 20:33:23');

-- --------------------------------------------------------

--
-- Table structure for table `subcategories`
--

CREATE TABLE `subcategories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subcategories`
--

INSERT INTO `subcategories` (`id`, `name`, `category_id`) VALUES
(1, 'Motherboards', 1),
(2, 'Processors (CPUs)', 1),
(3, 'Graphics Cards (GPUs)', 1),
(4, 'Memory (RAM)', 1),
(5, 'Storage Drives (HDDs & SSDs)', 1),
(6, 'Power Supplies (PSUs)', 1),
(7, 'Computer Cases', 1),
(8, 'Cooling Solutions (Fans & Heatsinks)', 1),
(9, 'Sound Cards', 1),
(10, 'Expansion Cards', 1),
(11, 'Keyboards', 2),
(12, 'Mice', 2),
(13, 'Monitors', 2),
(14, 'Headsets', 2),
(15, 'Webcams', 2),
(16, 'Routers', 3),
(17, 'Network Switches', 3),
(18, 'Modems', 3),
(19, 'Network Cables', 3),
(20, 'WiFi Adapters', 3),
(21, 'Gaming PCs', 4),
(22, 'Office PCs', 4),
(23, 'Compact PCs', 4),
(24, 'Gaming Laptops', 5),
(25, 'Ultrabooks', 5),
(26, '2-in-1 Laptops', 5),
(27, 'Mouse Pads', 6),
(28, 'Cables & Adapters', 6),
(29, 'Docking Stations', 6),
(30, 'Operating Systems', 7),
(31, 'Antivirus & Security', 7),
(32, 'Office Suites', 7),
(33, 'Gaming Chairs', 8),
(34, 'VR Accessories', 8),
(35, 'Controllers', 8),
(36, 'Clearance Items', 9),
(37, 'Refurbished Products', 9),
(38, 'Bundles & Combos', 9),
(39, 'Thermal Paste\r\n', 10);

-- --------------------------------------------------------

--
-- Table structure for table `tracking_info`
--

CREATE TABLE `tracking_info` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `tracking_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `last_updated` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `additional_info` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tracking_info`
--

INSERT INTO `tracking_info` (`id`, `order_id`, `tracking_number`, `status`, `last_updated`, `location`, `additional_info`) VALUES
(5, 40, 'Yfhh', 'Delivered', '2025-02-10 11:37:13', 'Souk ahras', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `verification_token` varchar(6) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `phone`, `password`, `verification_token`, `email_verified`, `created_at`, `profile_picture`, `reset_token`, `reset_token_expiry`, `username`) VALUES
(1, 'lokmen15.messabhia@gmail.com', '0657415715', '$2y$10$4PArXtWrKRMqI7gHbSaTRuEGBS4R6T4DKPt9fj8x3P.Mt2Lq4oJdm', 'f1473b', 1, '2024-12-22 22:18:21', 'chill_guy_png_by_elenatheobsessionfan_dilje4b-375w-2x.jpg', NULL, NULL, NULL),
(2, 'lokmen16.messabhia@gmail.com', '0657415715', '$2y$10$TnYAV6cWlaGVeX3Yxr/Xl.O9Fm9g5jL8VPOSPpdchIiO24wNaLTAi', NULL, 1, '2024-12-19 20:14:58', '459177720_508171588647853_6761848684807922403_n.jpg', '5d14799dba15b25333c259941b9ee1544b825e6e9d8c32b923b9a451ce215732', '2025-02-15 15:44:12', NULL),
(107, 'lokmen14.messabhia@gmail.com', '', '$2y$10$O9wjcDEADVQKI4EitPTxreatk5G55lKJKS69oxQa8VLFG35skXUUu', NULL, 1, '2024-12-23 17:35:21', NULL, NULL, NULL, NULL),
(108, 'hammoudiwajdi@gmail.com', '0657415715', '$2y$10$nTcM6dXOBmur38Ric1PNAOTzN.eIY.iNu5ZrhvA4xcKPrA4xUMH3y', NULL, 1, '2024-12-30 10:08:30', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wilayas`
--

CREATE TABLE `wilayas` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wilayas`
--

INSERT INTO `wilayas` (`id`, `name`) VALUES
(1, 'Adrar'),
(2, 'Chlef'),
(3, 'Laghouat'),
(4, 'Oum El Bouaghi'),
(5, 'Batna'),
(6, 'Béjaïa'),
(7, 'Biskra'),
(8, 'Béchar'),
(9, 'Blida'),
(10, 'Bouira'),
(11, 'Tamanrasset'),
(12, 'Tébessa'),
(13, 'Tlemcen'),
(14, 'Tiaret'),
(15, 'Tizi Ouzou'),
(16, 'Algiers'),
(17, 'Djelfa'),
(18, 'Jijel'),
(19, 'Sétif'),
(20, 'Saïda'),
(21, 'Skikda'),
(22, 'Sidi Bel Abbès'),
(23, 'Annaba'),
(24, 'Guelma'),
(25, 'Constantine'),
(26, 'Médéa'),
(27, 'Mostaganem'),
(28, 'M’Sila'),
(29, 'Mascara'),
(30, 'Ouargla'),
(31, 'Oran'),
(32, 'El Bayadh'),
(33, 'Illizi'),
(34, 'Bordj Bou Arréridj'),
(35, 'Boumerdès'),
(36, 'El Tarf'),
(37, 'Tindouf'),
(38, 'Tissemsilt'),
(39, 'El Oued'),
(40, 'Khenchela'),
(41, 'Souk Ahras'),
(42, 'Tipaza'),
(43, 'Mila'),
(44, 'Aïn Defla'),
(45, 'Naâma'),
(46, 'Aïn Témouchent'),
(47, 'Ghardaïa'),
(48, 'Relizane'),
(49, 'Timimoun'),
(50, 'Bordj Badji Mokhtar'),
(51, 'Ouled Djellal'),
(52, 'Béni Abbès'),
(53, 'In Salah'),
(54, 'In Guezzam'),
(55, 'Touggourt'),
(56, 'Djanet'),
(57, 'El M’Ghair'),
(58, 'El Meniaa');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `buildyourpc_orders`
--
ALTER TABLE `buildyourpc_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wilaya_id` (`wilaya_id`),
  ADD KEY `user_email` (`user_email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `features`
--
ALTER TABLE `features`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qrtoken` (`qrtoken`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
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
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `recycle_requests`
--
ALTER TABLE `recycle_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `subcategory_id` (`subcategory_id`);

--
-- Indexes for table `slider_photos`
--
ALTER TABLE `slider_photos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `tracking_info`
--
ALTER TABLE `tracking_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wilayas`
--
ALTER TABLE `wilayas`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `buildyourpc_orders`
--
ALTER TABLE `buildyourpc_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `features`
--
ALTER TABLE `features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `recycle_requests`
--
ALTER TABLE `recycle_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `slider_photos`
--
ALTER TABLE `slider_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `tracking_info`
--
ALTER TABLE `tracking_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `wilayas`
--
ALTER TABLE `wilayas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buildyourpc_orders`
--
ALTER TABLE `buildyourpc_orders`
  ADD CONSTRAINT `buildyourpc_orders_ibfk_1` FOREIGN KEY (`wilaya_id`) REFERENCES `wilayas` (`id`),
  ADD CONSTRAINT `buildyourpc_orders_ibfk_2` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recycle_requests`
--
ALTER TABLE `recycle_requests`
  ADD CONSTRAINT `recycle_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `recycle_requests_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `recycle_requests_ibfk_3` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`);

--
-- Constraints for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tracking_info`
--
ALTER TABLE `tracking_info`
  ADD CONSTRAINT `tracking_info_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
