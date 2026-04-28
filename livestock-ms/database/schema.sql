-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 22, 2026 at 05:16 PM
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
-- Database: `livestuchub_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `breeds`
--

CREATE TABLE `breeds` (
  `breed_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `breed_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `breeds`
--

INSERT INTO `breeds` (`breed_id`, `category_id`, `breed_name`) VALUES
(1, 1, 'Brahman'),
(2, 1, 'Holstein'),
(3, 1, 'Angus'),
(4, 1, 'Hereford'),
(5, 2, 'Broiler'),
(6, 2, 'Layer'),
(7, 2, 'Native Chicken'),
(8, 2, 'Duck'),
(9, 3, 'Landrace'),
(10, 3, 'Duroc'),
(11, 3, 'Large White'),
(12, 3, 'Berkshire'),
(13, 4, 'Boer'),
(14, 4, 'Anglo-Nubian'),
(15, 4, 'Saanen'),
(16, 4, 'Native Goat'),
(17, 5, 'Merino'),
(18, 5, 'Dorper'),
(19, 5, 'Suffolk');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(30) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category_name`, `description`) VALUES
(1, 'Cattle', 'Large farm animals'),
(2, 'Poultry', 'Chickens, ducks, birds'),
(3, 'Swine', 'Pigs'),
(4, 'Goat', 'Small ruminants'),
(5, 'Sheep', 'Wool-producing animals');

-- --------------------------------------------------------

--
-- Table structure for table `farmers`
--

CREATE TABLE `farmers` (
  `farmer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `farm_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmers`
--

INSERT INTO `farmers` (`farmer_id`, `user_id`, `farm_name`) VALUES
(1, 5, 'Agang\'s Farm'),
(2, 6, 'james');

-- --------------------------------------------------------

--
-- Table structure for table `farmers_contact`
--

CREATE TABLE `farmers_contact` (
  `contact_id` int(11) NOT NULL,
  `farmer_id` int(11) DEFAULT NULL,
  `contact_type` varchar(30) DEFAULT NULL,
  `contact_value` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `livestock`
--

CREATE TABLE `livestock` (
  `livestock_id` int(11) NOT NULL,
  `tag_number` varchar(50) DEFAULT NULL,
  `farmer_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `breed_id` int(11) DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `health_status` varchar(100) DEFAULT NULL,
  `date_of_birth` datetime DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `sale_status` enum('Available','Reserved','Sold') DEFAULT 'Available',
  `price` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `livestock_image` varchar(255) DEFAULT NULL,
  `current_weight` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `livestock`
--

INSERT INTO `livestock` (`livestock_id`, `tag_number`, `farmer_id`, `location_id`, `category_id`, `breed_id`, `gender`, `health_status`, `date_of_birth`, `date_created`, `sale_status`, `price`, `description`, `livestock_image`, `current_weight`) VALUES
(3, '1234', 2, 2, 3, NULL, 'Male', 'healthy', '2026-04-01 00:00:00', '2026-04-22 22:13:46', 'Available', 180.00, 'kasjdhkasjhd', NULL, 50.00);

-- --------------------------------------------------------

--
-- Table structure for table `livestock_weight`
--

CREATE TABLE `livestock_weight` (
  `weight_id` int(11) NOT NULL,
  `livestock_id` int(11) DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `date_recorded` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE `location` (
  `location_id` int(11) NOT NULL,
  `farmer_id` int(11) DEFAULT NULL,
  `location_name` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `location_type` varchar(50) DEFAULT NULL,
  `location_brgy` varchar(50) DEFAULT NULL,
  `location_city_muni` varchar(50) DEFAULT NULL,
  `location_province` varchar(50) DEFAULT NULL,
  `location_latitude` decimal(10,8) DEFAULT NULL,
  `location_longitude` decimal(11,8) DEFAULT NULL,
  `capacity` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `location`
--

INSERT INTO `location` (`location_id`, `farmer_id`, `location_name`, `description`, `location_type`, `location_brgy`, `location_city_muni`, `location_province`, `location_latitude`, `location_longitude`, `capacity`) VALUES
(1, 1, 'bacolod farm', '10 for mother pig\r\n1 for bore\r\nthe rest is for fattener', 'Pen', NULL, NULL, NULL, NULL, NULL, 50),
(2, 2, 'Bacolod Farm Main', 'This farm is located in brgy bacolod, culaba, biliran', 'Pen', NULL, NULL, NULL, NULL, NULL, 60);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `livestock_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `order_type` varchar(10) DEFAULT NULL,
  `status` enum('Pending','Confirmed','Completed','Cancelled') DEFAULT 'Pending',
  `total_price` decimal(10,2) DEFAULT NULL,
  `reservation_expiry` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `transaction_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('Pending','Paid','Failed','Refunded') DEFAULT NULL,
  `transaction_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `username` varchar(25) NOT NULL,
  `user_email` varchar(30) DEFAULT NULL,
  `user_phone_number` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_role` enum('Admin','Farmer','Buyer') DEFAULT 'Buyer',
  `user_last_name` varchar(20) DEFAULT NULL,
  `user_first_name` varchar(20) DEFAULT NULL,
  `user_middle_name` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `user_status` enum('Active','Suspended','Inactive') DEFAULT 'Active',
  `user_pfp` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `username`, `user_email`, `user_phone_number`, `password_hash`, `user_role`, `user_last_name`, `user_first_name`, `user_middle_name`, `created_at`, `user_status`, `user_pfp`) VALUES
(1, '', 'agangaustien@gmail.com', NULL, '$2y$10$56okpC9irwNZA.yBcGb9wObtSe3e6x2pP8lErxWaeN2d4F5gZFBJ.', 'Farmer', 'Agang', 'Austien James', NULL, '2026-04-15 07:11:28', 'Active', NULL),
(5, 'agang', 'agang@gmail.com', NULL, '$2y$10$NZ6pfoaMmxkLpcy9wBN7KOU0LoJtIHIXqvUPP1xp5Rx5Q9R5VWx1e', 'Farmer', 'Agang', 'Austien James', NULL, '2026-04-18 10:44:20', 'Active', NULL),
(6, 'james', 'james@gmail.com', NULL, '$2y$10$xw4sX5lKgiPa9yVXMxGzOuzqsLy.8GsXHXinC.TgP2xj7MDRQzbp.', 'Farmer', 'Agang', 'James', NULL, '2026-04-22 22:11:07', 'Active', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `breeds`
--
ALTER TABLE `breeds`
  ADD PRIMARY KEY (`breed_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `farmers`
--
ALTER TABLE `farmers`
  ADD PRIMARY KEY (`farmer_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `farmers_contact`
--
ALTER TABLE `farmers_contact`
  ADD PRIMARY KEY (`contact_id`),
  ADD KEY `farmer_id` (`farmer_id`);

--
-- Indexes for table `livestock`
--
ALTER TABLE `livestock`
  ADD PRIMARY KEY (`livestock_id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `livestock_ibfk_1` (`category_id`),
  ADD KEY `livestock_ibfk_4` (`breed_id`);

--
-- Indexes for table `livestock_weight`
--
ALTER TABLE `livestock_weight`
  ADD PRIMARY KEY (`weight_id`),
  ADD KEY `livestock_id` (`livestock_id`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`location_id`),
  ADD KEY `farmer_id` (`farmer_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `livestock_id` (`livestock_id`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `transaction_ibfk_1` (`order_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `user_email` (`user_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `breeds`
--
ALTER TABLE `breeds`
  MODIFY `breed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `farmers`
--
ALTER TABLE `farmers`
  MODIFY `farmer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `farmers_contact`
--
ALTER TABLE `farmers_contact`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `livestock`
--
ALTER TABLE `livestock`
  MODIFY `livestock_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `livestock_weight`
--
ALTER TABLE `livestock_weight`
  MODIFY `weight_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `breeds`
--
ALTER TABLE `breeds`
  ADD CONSTRAINT `breeds_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`);

--
-- Constraints for table `farmers`
--
ALTER TABLE `farmers`
  ADD CONSTRAINT `farmers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `farmers_contact`
--
ALTER TABLE `farmers_contact`
  ADD CONSTRAINT `farmers_contact_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`);

--
-- Constraints for table `livestock`
--
ALTER TABLE `livestock`
  ADD CONSTRAINT `livestock_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `livestock_ibfk_2` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`),
  ADD CONSTRAINT `livestock_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `location` (`location_id`),
  ADD CONSTRAINT `livestock_ibfk_4` FOREIGN KEY (`breed_id`) REFERENCES `breeds` (`breed_id`) ON DELETE SET NULL;

--
-- Constraints for table `livestock_weight`
--
ALTER TABLE `livestock_weight`
  ADD CONSTRAINT `livestock_weight_ibfk_1` FOREIGN KEY (`livestock_id`) REFERENCES `livestock` (`livestock_id`) ON DELETE CASCADE;

--
-- Constraints for table `location`
--
ALTER TABLE `location`
  ADD CONSTRAINT `location_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`livestock_id`) REFERENCES `livestock` (`livestock_id`);

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
