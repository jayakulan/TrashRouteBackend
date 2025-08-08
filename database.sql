-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 08, 2025 at 07:34 AM
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
(12, 'Jayakulan', 'admin@gmail.com', 'admin', '2025-06-30 10:22:37');

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
(11, 'com/23'),
(59, 'REG-COMP-002'),
(61, 'REG-COMP-004'),
(63, 'REG-COMP-007'),
(65, 'REG-COMP-009'),
(73, 'com/23'),
(74, 'com/23'),
(76, 'com/45'),
(77, 'com/23'),
(78, 'com/21'),
(79, 'com/45'),
(83, 'com/23'),
(91, 'COM25');

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
(7, 14, 11, '123456', 1, 1, 5, '', '2025-07-21 10:59:11'),
(8, 16, 11, '098765', 1, 1, 5, '', '2025-08-02 04:37:41');

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
(1, 12, 'jayakulan', 'customer@gmail.com', 'hi', 'hello', '2025-07-06 05:14:48'),
(2, 12, 'fbdfgb', 'xfbdgbc@gmail.com', 'xbcgnbcvb', 'xcbcvnvbmvb', '2025-08-02 05:12:48');

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
(9),
(19),
(20),
(21),
(23),
(58),
(60),
(62),
(64),
(72),
(80),
(81),
(87),
(89);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(6, 72, '511790', '2025-07-22 06:11:54', 1, '2025-07-21 18:11:54'),
(7, 73, '949535', '2025-07-23 02:17:26', 0, '2025-07-22 14:17:26'),
(8, 74, '834265', '2025-07-23 02:18:30', 1, '2025-07-22 14:18:30'),
(9, 75, '714210', '2025-07-23 02:25:03', 0, '2025-07-22 14:25:03'),
(10, 76, '850493', '2025-07-23 02:25:52', 0, '2025-07-22 14:25:52'),
(11, 77, '689260', '2025-07-23 02:30:32', 0, '2025-07-22 14:30:32'),
(12, 78, '870348', '2025-07-23 02:34:23', 0, '2025-07-22 14:34:23'),
(16, 82, '169837', '2025-07-23 06:58:46', 0, '2025-07-22 18:58:46'),
(17, 83, '110534', '2025-07-23 07:11:41', 0, '2025-07-22 19:11:41'),
(18, 84, '193482', '2025-07-23 07:22:02', 0, '2025-07-22 19:22:02'),
(19, 85, '827293', '2025-07-23 17:06:51', 0, '2025-07-23 05:06:51'),
(20, 86, '702536', '2025-07-23 17:08:43', 0, '2025-07-23 05:08:43'),
(21, 87, '007913', '2025-07-23 17:10:55', 1, '2025-07-23 05:10:55'),
(22, 88, '560496', '2025-07-27 06:38:51', 0, '2025-07-26 18:38:51'),
(23, 89, '222246', '2025-07-27 07:27:51', 1, '2025-07-26 19:27:51'),
(25, 91, '242707', '2025-08-02 16:07:22', 1, '2025-08-02 04:07:22');

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
(49, 11, 56, '1234123412341234', 'jayan', '0000-00-00', '123', 500.00, 'Paid', '2025-07-21 16:28:36'),
(50, 11, 57, '1234123412341234', 'jayan', '0000-00-00', '123', 500.00, 'Paid', '2025-07-26 11:54:22'),
(51, 11, 58, '1234123412341234', 'jayan', '0000-00-00', '123', 500.00, 'Paid', '2025-07-27 01:25:11'),
(52, 11, 59, '1234123412341234', 'Jayan', '0000-00-00', '123', 500.00, 'Paid', '2025-08-02 09:42:35'),
(53, 11, 60, '1234123412341234', 'jayan', '0000-00-00', '123', 500.00, 'Paid', '2025-08-02 10:05:11'),
(54, 11, 61, '1235412341234234', 'jayan', '0000-00-00', '123', 500.00, 'Paid', '2025-08-02 15:06:12'),
(55, 11, 62, '1234123412341234', 'jayan', '0000-00-00', '123', 500.00, 'Paid', '2025-08-02 15:12:07');

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
(14, 19, 'Glass', 3, 6.93447800, 79.84277800, 'Completed', '2025-07-10 19:34:47', '123456', 1),
(15, 20, 'Metal', 7, 7.29057200, 80.63372800, 'Accepted', '2025-07-10 19:34:47', '123456', 0),
(16, 21, 'Paper', 10, 6.05351900, 80.22097800, 'Completed', '2025-07-10 19:34:47', '098765', 1),
(17, 23, 'Plastic', 4, 6.92736900, 79.86138000, 'Request received', '2025-07-10 19:34:47', '654321', 0),
(20, 58, 'Glass', 2, 8.31135600, 80.40365900, 'Completed', '2025-07-10 19:34:47', '008684', 1),
(21, 60, 'Plastic', 9, 7.18034900, 79.88456700, 'Request received', '2025-07-10 19:34:47', '123456', 0),
(74, 9, 'Glass', 22, 9.67377478, 80.02932508, 'Accepted', '2025-08-02 09:39:22', '951388', 0),


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
(9, 'Customer', 'customer@gmail.com', 'customer', '0712345678', 'badulla', 'customer', 'active', '2025-06-30 09:58:53'),
(11, 'company1', 'company@gmail.com', 'company', '0757658790', 'manner', 'company', 'active', '2025-06-30 10:13:42'),
(12, 'Jayakulan', 'admin@gmail.com', 'admin', '0768304046', 'Jaffna', 'admin', 'active', '2025-06-30 10:22:37'),
(19, 'Miami', 'miami@gmail.com', '$2y$10$ZhgXtLld3kSPxXBNfbxAru6QcGUEw/XO0DR.VNk1otDKWpfdHuOo.', 'lopsdvsd', 'lopik', 'customer', 'active', '2025-06-30 13:25:36'),
(20, 'nivethan', 'nivee@gmail.com', '$2y$10$EKI64DhUC9Rl4trxhOtpSuzP5ScZgaGI4.q7GSNjRes9WrjiFPQn6', '123012342134', 'jaffna', 'customer', 'active', '2025-06-30 14:36:31'),
(21, 'Vsihnnu', 'vishnnu@gmail.com', '$2y$10$2PRvTubzjtXgbu5yJtSoeOq7F56iVQvkFGBu8Mdgo74gsUh3Wx2ke', '23534456', 'sdsdfsfdv', 'customer', 'active', '2025-06-30 17:26:30'),
(23, 'muralitharan Abi', 'abi@gmail.com', '$2y$10$.iQbx6JG6NDKwAWG/gO/1uhO18rQ.eMW5Ti3XNuBa2QO/KzhIpld.', '3356856', 'gerhydgjm', 'customer', 'active', '2025-07-01 11:00:04'),
(58, 'Nimal Perera', 'nimal123@gmail.com', 'hashed_pw1', '0771234567', 'Colombo, Sri Lanka', 'customer', 'active', '2025-07-10 13:54:20'),
(59, 'Sunil De Silva', 'sunil.ds@example.com', 'hashed_pw2', '0712345678', 'Galle, Sri Lanka', 'company', 'active', '2025-07-10 13:54:20'),
(60, 'Ayesha Fernando', 'ayesha.f@gmail.com', 'hashed_pw3', '0789876543', 'Kandy, Sri Lanka', 'customer', 'active', '2025-07-10 13:54:20'),
(61, 'Ruwan Weerasinghe', 'ruwanw@gmail.com', 'hashed_pw4', '0765554422', 'Matara, Sri Lanka', 'company', 'active', '2025-07-10 13:54:20'),
(62, 'Dilani Abeykoon', 'dilania@gmail.com', 'hashed_pw5', '0777654321', 'Kurunegala, Sri Lanka', 'customer', 'active', '2025-07-10 13:54:20'),
(63, 'Mahesh Gunasekara', 'mahesh@wastepro.lk', 'hashed_pw6', '0721122334', 'Jaffna, Sri Lanka', 'company', 'active', '2025-07-10 13:54:20'),
(64, 'Kavindu Perera', 'kavindup@gmail.com', 'hashed_pw7', '0799871234', 'Badulla, Sri Lanka', 'customer', 'active', '2025-07-10 13:54:20'),
(65, 'Sanduni Rajapaksa', 'sandu.rp@eco.lk', 'hashed_pw8', '0743214567', 'Nuwara Eliya, Sri Lanka', 'company', 'active', '2025-07-10 13:54:20'),
(72, 'v jayakulan', 'jaya@gmail.com', '$2y$10$q0MWqvuApvF6z4edHKjzDO5kJwb8ogsmOYEShX5l2yUHlkLr/Ci.K', '1234567891', '643 KKS road Jaffna', 'customer', 'active', '2025-07-21 18:11:54'),
(73, 'global', 'global@gmail.com', '$2y$10$OemhUHcNJSVohOTUwmmTAOKBq90RYIkYZ/x2HWtzwGXiXBFsazbF2', '0768304046', 'jaffna', 'company', 'pending', '2025-07-22 14:17:26'),
(74, 'ert', 'ert@gmail.com', '$2y$10$2PjSsOSSnhwdWdERdTV6iO98YdOwHq4KSwHdB7kD39opPNK1gm/Za', '1234567890', 'dfdgbfg', 'company', 'active', '2025-07-22 14:18:30'),
(75, 'v jayakulan', 'jk@gmal.com', '$2y$10$kVcxLdm31TYFr8BuL97i.OO8UewfWta3q8dU7Zsk8x1Je4IDxzX4a', '2353445645', '643 KKS road Jaffna', 'customer', 'pending', '2025-07-22 14:25:03'),
(76, 'ert', 'eer@gmail.com', '$2y$10$gWB9Q4raxRMC4VhdYRESmON3rdr1wfbMXdg9sLKBs4aZoU/i46YsW', '0776104689', '643 KKS road Jaffna', 'company', 'pending', '2025-07-22 14:25:52'),
(77, 'abc', 'asd@gmail.com', '$2y$10$I1BpMV.6RzHeQavq6e0.GOi2iyYUoOgIjGvyESTz2/JYcx9qg19OG', '0776104689', '643 KKS road Jaffna', 'company', 'pending', '2025-07-22 14:30:32'),
(78, 'abc', 'qwer@gmail.com', '$2y$10$TFZdEY6u0k5icGwsZ8CyEeml/j//t4h0pyTQc90VzDgq8HFKLFyn.', '1234567890', '643 KKS road Jaffna', 'company', 'pending', '2025-07-22 14:34:23'),
(79, 'abc', 'axs@gmail.com', '$2y$10$dJMUqsuCMWsGXKFcZRTJJedYvg2bkJYw/h75GiCXLFgybRV/7iMyO', '0776104689', '643 KKS road Jaffna', 'company', 'active', '2025-07-22 17:58:24'),
(80, 'vjayakulan', 'jk@gmail.com', '$2y$10$YMb2Okb5M8EarMUDsaw4gO5GQF7acV22ZpLmVdfknl49eEO03.mE6', '0776104689', '643 KKS road Jaffna', 'customer', 'active', '2025-07-22 18:37:52'),
(81, 'v jayakulan', 'as@gmail.com', '$2y$10$5EGbeyNi.00EA5manPpUWOGYt.aj/f5lOyRmDAXOjFyU5lmTbb8LG', '0776104689', '643 KKS road Jaffna', 'customer', 'active', '2025-07-22 18:44:59'),
(82, 'v jayakulan', 'lok@gmail.com', '$2y$10$JsOOe764kkBK0ZD3N59mu.Kt6WdSk0pBwwBOq9kAk8.bL3T2CDWoK', '0776104689', '643 KKS road Jaffna', 'customer', 'pending', '2025-07-22 18:58:46'),
(83, 'abc', 'company12@gmail.com', '$2y$10$8T6/n2lCc5C/ESWi5EG1pOEwpWjXy0OuE3WHskwA0gKANnjN.VEBS', '0776104689', '643 KKS road Jaffna', 'company', 'pending', '2025-07-22 19:11:41'),
(84, 'v jayakulandxfbcv', 'fgdgfg@gmail.com', '$2y$10$HPTYqm8yRkQedrb3MTG0cO60rqwHK6JfhQm7.Jgw5MAcF5h8poTGK', '0776104689', '643 KKS road Jaffna', 'customer', 'pending', '2025-07-22 19:22:02'),
(85, 'kamal', 'kamalq@gmail.com', '$2y$10$P.kB4cDIasuyO0UtZ/OxHOyGUh2TR/IisFqYHXqu83zOMkdpsxHVq', '0768304046', '643 KKS road Jaffna', 'customer', 'pending', '2025-07-23 05:06:51'),
(86, 'kamal', 'kaqwer@gmail.com', '$2y$10$piTX/sT.hhY5KVImtywPAO6jd8ZH1JqlxEQuufcIvz6pNlaGpespa', '0768304046', '643 KKS road Jaffna', 'customer', 'pending', '2025-07-23 05:08:43'),
(87, 'v jayakulan', 'qweqerwe@gmail.com', '$2y$10$.qRi8CNWHvgurROBtRwwreQ7ANelS/GEOpZ6bdT9S4i5U0K7nHAG.', '0776104689', '643 KKS road Jaffna', 'customer', 'active', '2025-07-23 05:10:55'),
(88, 'assdd', 'ad2####@gmail.com', '$2y$10$TQb5KgunfhF216Ef1IfGIOIpXNrATNuhrowTAEpM9gUuTvj0LneLW', '0776104689', '643 KKS road Jaffna', 'customer', 'pending', '2025-07-26 18:38:51'),
(89, 'Gdfgdf', 'adadfsdf@gmail.com', '$2y$10$lR0g4HxI.2kvwDM.LSEnWO53sQwJfPoDtuJ9pVLmdmBP.Tu4lSmJy', '0776104689', '643 KKS road Jaffna', 'customer', 'disabled', '2025-07-26 19:27:51'),
(91, 'rathini', 'vrathini1974@gmail.com', '$2y$10$Zph1fD0zS7HTE4DQ9SD8L.PNGKvJmH/0MZ8wHx7kIXOelJGUWengO', '0718619215', 'jaffna srilanka', 'company', 'active', '2025-08-02 04:07:22');

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
(56, 11, 2, 1, '2025-07-21 16:28:36', 0, 'Route for glass waste collection - Total Customers: 2, Total Quantity: 5 kg', '2025-07-21 10:58:36'),
(57, 11, 2, 1, '2025-07-26 11:54:22', 0, 'Route for metal waste collection - Total Customers: 2, Total Quantity: 13 kg', '2025-07-26 06:24:22'),
(58, 11, 2, 1, '2025-07-27 01:25:11', 0, 'Route for glass waste collection - Total Customers: 2, Total Quantity: 5 kg', '2025-07-26 19:55:11'),
(59, 11, 3, 1, '2025-08-02 09:42:35', 0, 'Route for glass waste collection - Total Customers: 3, Total Quantity: 27 kg', '2025-08-02 04:12:35'),
(60, 11, 1, 1, '2025-08-02 10:05:11', 0, 'Route for paper waste collection - Total Customers: 1, Total Quantity: 10 kg', '2025-08-02 04:35:11'),
(61, 11, 1, 1, '2025-08-02 15:06:12', 0, 'Route for metal waste collection - Total Customers: 1, Total Quantity: 28 kg', '2025-08-02 09:36:12'),
(62, 11, 1, 1, '2025-08-02 15:12:07', 0, 'Route for paper waste collection - Total Customers: 1, Total Quantity: 16 kg', '2025-08-02 09:42:07');

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
(120, 56, 14, '2025-07-21 10:58:36'),
(121, 56, 20, '2025-07-21 10:58:36'),
(122, 57, 15, '2025-07-26 06:24:22'),
(124, 58, 14, '2025-07-26 19:55:11'),
(125, 58, 20, '2025-07-26 19:55:11'),
(126, 59, 14, '2025-08-02 04:12:35'),
(127, 59, 20, '2025-08-02 04:12:35'),
(128, 59, 74, '2025-08-02 04:12:35'),
(129, 60, 16, '2025-08-02 04:35:11'),
(130, 61, 75, '2025-08-02 09:36:12'),
(131, 62, 76, '2025-08-02 09:42:07');

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
  ADD KEY `company_id` (`company_id`),
  ADD KEY `customer_id` (`customer_id`);

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
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `contact_us`
--
ALTER TABLE `contact_us`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp`
--
ALTER TABLE `otp`
  MODIFY `otp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `pickup_requests`
--
ALTER TABLE `pickup_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `registered_users`
--
ALTER TABLE `registered_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `route_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `route_request_mapping`
--
ALTER TABLE `route_request_mapping`
  MODIFY `mapping_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

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
