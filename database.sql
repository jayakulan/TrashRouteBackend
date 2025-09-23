-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2025 at 06:14 PM
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
-- Database: `trashroute`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'Vimalarasa Jayakulan', 'vjayakulan@gmail.com', '$2y$10$tCA0IwD7DbzejeO.UTjpLOljJW5LFjprvenMIkd6/LSjQ9t9Yz4lm', '2025-09-23 10:02:07');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `company_id` int(11) NOT NULL,
  `company_reg_number` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`company_id`, `company_reg_number`) VALUES
(3, 'REG-84521'),
(4, 'REG-29384'),
(5, 'REG-56109'),
(6, 'REG-43725'),
(8, 'REG-72819');

-- --------------------------------------------------------

--
-- Table structure for table `company_feedback`
--

CREATE TABLE `company_feedback` (
  `feedback_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `entered_otp` varchar(10) DEFAULT NULL,
  `pickup_verified` tinyint(1) DEFAULT 0,
  `pickup_completed` tinyint(1) DEFAULT 0,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_feedback`
--

INSERT INTO `company_feedback` (`feedback_id`, `request_id`, `company_id`, `entered_otp`, `pickup_verified`, `pickup_completed`, `rating`, `comment`, `created_at`) VALUES
(1, 11, 3, '990242', 1, 1, 5, 'customer was good', '2025-09-23 13:22:15');

-- --------------------------------------------------------

--
-- Table structure for table `contact_us`
--

CREATE TABLE `contact_us` (
  `contact_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(150) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_us`
--

INSERT INTO `contact_us` (`contact_id`, `admin_id`, `name`, `email`, `subject`, `message`, `created_at`) VALUES
(1, 1, 'Kamal', 'kamal@gmail.com', 'Service Inquiry', 'I would like to know more about your waste collection services.', '2025-10-15 03:30:00'),
(2, 1, 'David', 'david@gmail.com', 'Partnership Opportunity', 'Our company is interested in partnering with TrashRoute for waste management.', '2025-10-15 03:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`) VALUES
(2),
(9),
(10),
(11),
(12),
(13),
(14),
(15),
(16),
(17),
(18);

-- --------------------------------------------------------

--
-- Table structure for table `customer_feedback`
--

CREATE TABLE `customer_feedback` (
  `feedback_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `pickup_completed` tinyint(1) DEFAULT 0,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_feedback`
--

INSERT INTO `customer_feedback` (`feedback_id`, `request_id`, `customer_id`, `pickup_completed`, `rating`, `comment`, `created_at`) VALUES
(1, 11, 2, 1, 5, 'company pickup is good', '2025-09-23 15:45:18'),
(2, 1, 2, 1, 4, 'nice', '2025-09-23 15:55:30');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `seen` tinyint(1) DEFAULT 0,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `request_id`, `company_id`, `customer_id`, `message`, `seen`, `dismissed_at`, `created_at`) VALUES
(1, 2, 10, NULL, 2, 'Pickup scheduled successfully for Paper (21kg). Request #10.', 0, NULL, '2025-09-23 13:11:49'),
(2, 2, 11, NULL, 2, 'Pickup scheduled successfully for Glass (38kg). Request #11.', 0, NULL, '2025-09-23 13:11:49'),
(3, 14, 8, 3, 14, 'Your pickup request #8 has been accepted by EcoWaste Solutions.', 0, NULL, '2025-09-23 13:16:09'),
(4, 2, 11, 3, 2, 'Your pickup request #11 has been accepted by EcoWaste Solutions.', 0, NULL, '2025-09-23 13:16:09'),
(5, 3, NULL, 3, NULL, 'Payment successful. Route #1 for glass activated. Customers: 2, Total Qty: 66 kg.', 1, NULL, '2025-09-23 13:16:09'),
(6, 2, 11, NULL, 2, 'Your pickup request #11 has been marked as completed.', 0, NULL, '2025-09-23 13:22:15'),
(7, 3, 11, 3, 2, 'Pickup request Yogarajah Vishnnu has been completed.', 0, NULL, '2025-09-23 13:22:15');

-- --------------------------------------------------------

--
-- Table structure for table `otp`
--

CREATE TABLE `otp` (
  `otp_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `expiration_time` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp`
--

INSERT INTO `otp` (`otp_id`, `user_id`, `otp_code`, `expiration_time`, `is_used`, `created_at`) VALUES
(1, 1, '181932', '2025-09-23 21:55:03', 1, '2025-09-23 09:55:03');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `card_number` varchar(16) DEFAULT NULL,
  `cardholder_name` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `pin_number` varchar(4) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 500.00,
  `payment_status` enum('Paid','Pending') DEFAULT 'Pending',
  `payment_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `company_id`, `route_id`, `card_number`, `cardholder_name`, `expiry_date`, `pin_number`, `amount`, `payment_status`, `payment_date`) VALUES
(1, 3, 1, '5134586394503945', 'Vimal', '0000-00-00', '123', 500.00, 'Paid', '2025-09-23 18:45:11');

-- --------------------------------------------------------

--
-- Table structure for table `pickup_requests`
--

CREATE TABLE `pickup_requests` (
  `request_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `waste_type` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('Request received','Pending','Accepted','Completed') DEFAULT 'Request received',
  `timestamp` datetime DEFAULT current_timestamp(),
  `otp` varchar(10) DEFAULT NULL,
  `otp_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pickup_requests`
--

INSERT INTO `pickup_requests` (`request_id`, `customer_id`, `waste_type`, `quantity`, `latitude`, `longitude`, `status`, `timestamp`, `otp`, `otp_verified`) VALUES
(1, 2, 'Plastic', 15, 6.93447800, 79.84277800, 'Completed', '2025-01-15 10:00:00', '123456', 0),
(2, 16, 'Paper', 25, 7.29057200, 80.63372800, 'Completed', '2025-01-15 10:15:00', '789012', 0),
(3, 9, 'Metal', 30, 6.05351900, 80.22097800, 'Completed', '2025-01-15 10:30:00', '345678', 0),
(4, 10, 'Glass', 20, 6.92736900, 79.86138000, 'Accepted', '2025-01-15 10:45:00', '901234', 0),
(5, 11, 'Plastic', 18, 8.31135600, 80.40365900, 'Request received', '2025-01-15 11:00:00', '567890', 0),
(6, 12, 'Paper', 22, 6.98208616, 81.08318678, 'Request received', '2025-01-15 11:15:00', '453457', 0),
(7, 13, 'Metal', 35, 6.98500000, 81.07500000, 'Request received', '2025-01-15 11:30:00', '786567', 0),
(8, 14, 'Glass', 28, 6.98500000, 81.07500000, 'Accepted', '2025-01-15 11:45:00', '354657', 0),
(10, 2, 'Paper', 21, 6.98496450, 81.07878336, 'Request received', '2025-09-23 18:40:47', '976734', 0),
(11, 2, 'Glass', 38, 6.98496450, 81.07878336, 'Completed', '2025-09-23 18:40:47', '990242', 1);

-- --------------------------------------------------------

--
-- Table structure for table `registered_users`
--

CREATE TABLE `registered_users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','company','admin') NOT NULL,
  `disable_status` enum('active','disabled','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registered_users`
--

INSERT INTO `registered_users` (`user_id`, `name`, `email`, `password_hash`, `contact_number`, `address`, `role`, `disable_status`, `created_at`) VALUES
(1, 'Vimalarasa Jayakulan', 'vjayakulan@gmail.com', '$2y$10$tCA0IwD7DbzejeO.UTjpLOljJW5LFjprvenMIkd6/LSjQ9t9Yz4lm', '0776104689', '643 KKS road Jaffna', 'admin', 'active', '2025-09-23 09:55:03'),
(2, 'Yogarajah Vishnnu', 'vishnnu@gmail.com', '$2y$10$KCcdVcxhhL9YpqyISiLwjOkPP4VIHYVlSZ7QjK36R110hMqGRCFc2', '0712345678', '123 lower Street, Badulla', 'customer', 'active', '2025-01-15 03:30:00'),
(3, 'EcoWaste Solutions', 'ecowaste@gmail.com', '$2y$10$800.XaA/gwZI32ZlggJC0uwuxVIO.XnySbyLgk/.8VmGwUFgdbL1S', '0771234567', '456 Business Avenue, Badulla', 'company', 'active', '2025-01-15 03:45:00'),
(4, 'GreenCollect Ltd', 'infogreen@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0712345678', '789 Green Street, Badulla', 'company', 'active', '2025-01-15 04:00:00'),
(5, 'WastePro Inc', 'wastepro@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0721122334', '321 Waste Lane, Badulla', 'company', 'active', '2025-01-15 04:15:00'),
(6, 'RecycleHub Co', 'recyclehub@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0743214567', '654 Recycle Road, Badulla', 'company', 'active', '2025-01-15 04:30:00'),
(8, 'CleanEarth Services', 'cleanearth@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0765554422', '987 Clean Street, Matara', 'company', 'active', '2025-01-15 04:45:00'),
(9, 'Gopeeshan', 'gopeesahn@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0777654321', '123 Main Street, Badulla', 'customer', 'active', '2025-01-15 05:00:00'),
(10, 'Mathangey Shanmugalingam', 'mathu@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0789876543', '456 Oak Avenue, Badulla', 'customer', 'active', '2025-01-15 05:15:00'),
(11, 'Gobiha Palanivel', 'gobiha@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0799871234', '789 Pine Road, Badulla', 'customer', 'active', '2025-01-15 05:30:00'),
(12, 'Abiramy Thirulinganathan', 'abiramy@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0712345678', '321 Elm Street, Jaffna', 'customer', 'active', '2025-01-15 05:45:00'),
(13, 'Tharshika Pathmanathan', 'tharshika@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0723456789', '654 Maple Drive, Badulla', 'customer', 'active', '2025-01-15 06:00:00'),
(14, 'Yoganathan Arultharsan', 'tharsan@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0734567890', '987 Cedar Lane, Badulla', 'customer', 'active', '2025-01-15 06:15:00'),
(15, 'Anandavasan Lavakesan', 'lavakesan@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0745678901', '147 Birch Court, Badulla', 'customer', 'active', '2025-01-15 06:30:00'),
(16, 'Sritharan Janakan', 'janakan@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0756789012', '258 Spruce Way, Badulla', 'customer', 'active', '2025-01-15 06:45:00'),
(17, 'Mathan', 'mathan@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0767890123', '369 Willow Street, Badulla', 'customer', 'active', '2025-01-15 07:00:00'),
(18, 'Aberam', 'aberam@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0778901234', '741 Poplar Avenue, Badulla', 'customer', 'active', '2025-01-15 07:15:00');

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `route_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `no_of_customers` int(11) DEFAULT 1,
  `is_accepted` tinyint(1) DEFAULT 0,
  `accepted_at` datetime DEFAULT NULL,
  `is_disabled` tinyint(1) DEFAULT 0,
  `route_details` text DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`route_id`, `company_id`, `no_of_customers`, `is_accepted`, `accepted_at`, `is_disabled`, `route_details`, `generated_at`) VALUES
(1, 3, 2, 1, '2025-09-23 18:45:11', 0, 'Route for glass waste collection - Total Customers: 2, Total Quantity: 66 kg', '2025-09-23 13:15:11');

-- --------------------------------------------------------

--
-- Table structure for table `route_request_mapping`
--

CREATE TABLE `route_request_mapping` (
  `mapping_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route_request_mapping`
--

INSERT INTO `route_request_mapping` (`mapping_id`, `route_id`, `request_id`, `created_at`) VALUES
(1, 1, 8, '2025-09-23 13:15:11'),
(2, 1, 11, '2025-09-23 13:15:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`company_id`);

--
-- Indexes for table `company_feedback`
--
ALTER TABLE `company_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `contact_us`
--
ALTER TABLE `contact_us`
  ADD PRIMARY KEY (`contact_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `idx_dismissed_at` (`dismissed_at`),
  ADD KEY `idx_company_seen_dismissed` (`company_id`,`seen`,`dismissed_at`);

--
-- Indexes for table `otp`
--
ALTER TABLE `otp`
  ADD PRIMARY KEY (`otp_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `route_id` (`route_id`);

--
-- Indexes for table `pickup_requests`
--
ALTER TABLE `pickup_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `registered_users`
--
ALTER TABLE `registered_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`route_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `route_request_mapping`
--
ALTER TABLE `route_request_mapping`
  ADD PRIMARY KEY (`mapping_id`),
  ADD UNIQUE KEY `unique_route_request` (`route_id`,`request_id`),
  ADD KEY `idx_route_mapping_route` (`route_id`),
  ADD KEY `idx_route_mapping_request` (`request_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `company_feedback`
--
ALTER TABLE `company_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact_us`
--
ALTER TABLE `contact_us`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `otp`
--
ALTER TABLE `otp`
  MODIFY `otp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pickup_requests`
--
ALTER TABLE `pickup_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `registered_users`
--
ALTER TABLE `registered_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `route_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `route_request_mapping`
--
ALTER TABLE `route_request_mapping`
  MODIFY `mapping_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `registered_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `companies`
--
ALTER TABLE `companies`
  ADD CONSTRAINT `companies_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `registered_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `company_feedback`
--
ALTER TABLE `company_feedback`
  ADD CONSTRAINT `company_feedback_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `pickup_requests` (`request_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `company_feedback_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE;

--
-- Constraints for table `contact_us`
--
ALTER TABLE `contact_us`
  ADD CONSTRAINT `contact_us_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `registered_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD CONSTRAINT `customer_feedback_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `pickup_requests` (`request_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_feedback_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `registered_users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `pickup_requests` (`request_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_4` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL;

--
-- Constraints for table `otp`
--
ALTER TABLE `otp`
  ADD CONSTRAINT `otp_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `registered_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`route_id`) ON DELETE CASCADE;

--
-- Constraints for table `pickup_requests`
--
ALTER TABLE `pickup_requests`
  ADD CONSTRAINT `pickup_requests_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `routes`
--
ALTER TABLE `routes`
  ADD CONSTRAINT `routes_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE;

--
-- Constraints for table `route_request_mapping`
--
ALTER TABLE `route_request_mapping`
  ADD CONSTRAINT `route_request_mapping_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `routes` (`route_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `route_request_mapping_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `pickup_requests` (`request_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
