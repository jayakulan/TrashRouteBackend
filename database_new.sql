-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 15, 2025 at 10:00 AM
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
-- Database: `trashroute_new`
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
(1, 'Sarah Johnson', 'admin@trashroute.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-01-15 10:00:00');

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
(2, 'ECO-2025-001'),
(3, 'GREEN-2025-002'),
(4, 'WASTE-2025-003'),
(5, 'RECYCLE-2025-004'),
(6, 'CLEAN-2025-005');

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
(1, 1, 2, '123456', 1, 1, 5, 'Excellent service!', '2025-01-15 10:30:00'),
(2, 2, 3, '789012', 1, 1, 4, 'Good pickup experience', '2025-01-15 11:00:00'),
(3, 3, 4, '345678', 1, 1, 5, 'Very professional team', '2025-01-15 11:30:00');

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
(1, 1, 'Michael Brown', 'michael.brown@email.com', 'Service Inquiry', 'I would like to know more about your waste collection services.', '2025-01-15 09:00:00'),
(2, 1, 'Emily Davis', 'emily.davis@email.com', 'Partnership Opportunity', 'Our company is interested in partnering with TrashRoute for waste management.', '2025-01-15 09:15:00');

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
(7),
(8),
(9),
(10),
(11),
(12),
(13),
(14),
(15),
(16);

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
(1, 1, 7, 1, 5, 'Great service, very punctual!', '2025-01-15 10:45:00'),
(2, 2, 8, 1, 4, 'Good experience overall', '2025-01-15 11:15:00'),
(3, 3, 9, 1, 5, 'Excellent waste collection service', '2025-01-15 11:45:00');

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
(1, 7, 1, NULL, 7, 'Pickup scheduled successfully for Plastic (15kg). Request #1.', 0, NULL, '2025-01-15 10:00:00'),
(2, 8, 2, 2, 8, 'Your pickup request #2 has been accepted by EcoWaste Solutions.', 0, NULL, '2025-01-15 10:15:00'),
(3, 2, NULL, 2, NULL, 'Payment successful. Route #1 for plastic activated. Customers: 1, Total Qty: 15 kg.', 1, NULL, '2025-01-15 10:15:00'),
(4, 7, 1, NULL, 7, 'Your pickup request #1 has been marked as completed.', 0, NULL, '2025-01-15 10:30:00'),
(5, 2, 1, 2, 7, 'Pickup request John Smith has been completed.', 0, NULL, '2025-01-15 10:30:00');

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
(1, 7, '123456', '2025-01-15 12:00:00', 1, '2025-01-15 10:00:00'),
(2, 8, '789012', '2025-01-15 12:15:00', 1, '2025-01-15 10:15:00'),
(3, 9, '345678', '2025-01-15 12:30:00', 1, '2025-01-15 10:30:00'),
(4, 10, '901234', '2025-01-15 12:45:00', 0, '2025-01-15 10:45:00'),
(5, 11, '567890', '2025-01-15 13:00:00', 0, '2025-01-15 11:00:00');

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
(1, 2, 1, '4532123456789012', 'EcoWaste Solutions', '2026-12-31', '1234', 500.00, 'Paid', '2025-01-15 10:15:00'),
(2, 3, 2, '5555123456789012', 'GreenCollect Ltd', '2027-06-30', '5678', 500.00, 'Paid', '2025-01-15 10:30:00'),
(3, 4, 3, '4111111111111111', 'WastePro Inc', '2026-03-31', '9012', 500.00, 'Paid', '2025-01-15 10:45:00');

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
(1, 7, 'Plastic', 15, 6.93447800, 79.84277800, 'Completed', '2025-01-15 10:00:00', '123456', 1),
(2, 8, 'Paper', 25, 7.29057200, 80.63372800, 'Completed', '2025-01-15 10:15:00', '789012', 1),
(3, 9, 'Metal', 30, 6.05351900, 80.22097800, 'Completed', '2025-01-15 10:30:00', '345678', 1),
(4, 10, 'Glass', 20, 6.92736900, 79.86138000, 'Accepted', '2025-01-15 10:45:00', '901234', 0),
(5, 11, 'Plastic', 18, 8.31135600, 80.40365900, 'Pending', '2025-01-15 11:00:00', '567890', 0),
(6, 12, 'Paper', 22, 6.98208616, 81.08318678, 'Request received', '2025-01-15 11:15:00', NULL, 0),
(7, 13, 'Metal', 35, 6.98500000, 81.07500000, 'Request received', '2025-01-15 11:30:00', NULL, 0),
(8, 14, 'Glass', 28, 6.98500000, 81.07500000, 'Request received', '2025-01-15 11:45:00', NULL, 0);

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
(1, 'Sarah Johnson', 'admin@trashroute.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0712345678', '123 Admin Street, Colombo', 'admin', 'active', '2025-01-15 09:00:00'),
(2, 'EcoWaste Solutions', 'contact@ecowaste.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0771234567', '456 Business Avenue, Colombo', 'company', 'active', '2025-01-15 09:15:00'),
(3, 'GreenCollect Ltd', 'info@greencollect.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0712345678', '789 Green Street, Kandy', 'company', 'active', '2025-01-15 09:30:00'),
(4, 'WastePro Inc', 'admin@wastepro.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0721122334', '321 Waste Lane, Galle', 'company', 'active', '2025-01-15 09:45:00'),
(5, 'RecycleHub Co', 'contact@recyclehub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0743214567', '654 Recycle Road, Jaffna', 'company', 'active', '2025-01-15 10:00:00'),
(6, 'CleanEarth Services', 'info@cleanearth.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0765554422', '987 Clean Street, Matara', 'company', 'active', '2025-01-15 10:15:00'),
(7, 'John Smith', 'john.smith@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0777654321', '123 Main Street, Colombo', 'customer', 'active', '2025-01-15 10:30:00'),
(8, 'Emily Johnson', 'emily.johnson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0789876543', '456 Oak Avenue, Kandy', 'customer', 'active', '2025-01-15 10:45:00'),
(9, 'Michael Brown', 'michael.brown@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0799871234', '789 Pine Road, Galle', 'customer', 'active', '2025-01-15 11:00:00'),
(10, 'Sarah Davis', 'sarah.davis@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0712345678', '321 Elm Street, Jaffna', 'customer', 'active', '2025-01-15 11:15:00'),
(11, 'David Wilson', 'david.wilson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0723456789', '654 Maple Drive, Matara', 'customer', 'active', '2025-01-15 11:30:00'),
(12, 'Lisa Anderson', 'lisa.anderson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0734567890', '987 Cedar Lane, Negombo', 'customer', 'active', '2025-01-15 11:45:00'),
(13, 'Robert Taylor', 'robert.taylor@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0745678901', '147 Birch Court, Anuradhapura', 'customer', 'active', '2025-01-15 12:00:00'),
(14, 'Jennifer Martinez', 'jennifer.martinez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0756789012', '258 Spruce Way, Kurunegala', 'customer', 'active', '2025-01-15 12:15:00'),
(15, 'Christopher Lee', 'christopher.lee@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0767890123', '369 Willow Street, Ratnapura', 'customer', 'active', '2025-01-15 12:30:00'),
(16, 'Amanda Garcia', 'amanda.garcia@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0778901234', '741 Poplar Avenue, Badulla', 'customer', 'active', '2025-01-15 12:45:00');

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
(1, 2, 1, 1, '2025-01-15 10:15:00', 0, 'Route for plastic waste collection - Total Customers: 1, Total Quantity: 15 kg', '2025-01-15 10:00:00'),
(2, 3, 1, 1, '2025-01-15 10:30:00', 0, 'Route for paper waste collection - Total Customers: 1, Total Quantity: 25 kg', '2025-01-15 10:15:00'),
(3, 4, 1, 1, '2025-01-15 10:45:00', 0, 'Route for metal waste collection - Total Customers: 1, Total Quantity: 30 kg', '2025-01-15 10:30:00'),
(4, 5, 2, 0, NULL, 0, 'Route for glass waste collection - Total Customers: 2, Total Quantity: 48 kg', '2025-01-15 11:00:00'),
(5, 6, 3, 0, NULL, 0, 'Route for mixed waste collection - Total Customers: 3, Total Quantity: 75 kg', '2025-01-15 11:30:00');

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
(1, 1, 1, '2025-01-15 10:00:00'),
(2, 2, 2, '2025-01-15 10:15:00'),
(3, 3, 3, '2025-01-15 10:30:00'),
(4, 4, 4, '2025-01-15 11:00:00'),
(5, 4, 8, '2025-01-15 11:00:00'),
(6, 5, 5, '2025-01-15 11:30:00'),
(7, 5, 6, '2025-01-15 11:30:00'),
(8, 5, 7, '2025-01-15 11:30:00');

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
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contact_us`
--
ALTER TABLE `contact_us`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `otp`
--
ALTER TABLE `otp`
  MODIFY `otp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pickup_requests`
--
ALTER TABLE `pickup_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `registered_users`
--
ALTER TABLE `registered_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `route_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `route_request_mapping`
--
ALTER TABLE `route_request_mapping`
  MODIFY `mapping_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `auto_update_status` ON SCHEDULE EVERY 1 DAY STARTS '2025-01-15 09:00:00' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE pickup_requests
  SET status = 'request received'
  WHERE status = 'accepted'
    AND created_at < NOW() - INTERVAL 3 DAY$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

