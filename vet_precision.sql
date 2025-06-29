-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: Jun 26, 2025 at 12:21 PM
-- Server version: 11.4.6-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vet_precision`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `duration_minutes` int(11) DEFAULT 30,
  `status` enum('requested','confirmed','completed','cancelled') DEFAULT 'requested',
  `type` varchar(50) DEFAULT 'General Checkup',
  `reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `pet_id`, `appointment_date`, `appointment_time`, `duration_minutes`, `status`, `type`, `reason`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-06-09', '09:00:00', 30, 'confirmed', 'Checkup', 'Annual vaccination', NULL, 4, '2025-06-08 14:40:50', '2025-06-08 14:40:50'),
(2, 2, '2025-06-10', '10:30:00', 30, 'cancelled', 'Grooming', 'Regular grooming', NULL, 4, '2025-06-08 14:40:50', '2025-06-25 16:29:31'),
(3, 3, '2025-06-11', '14:00:00', 30, 'confirmed', 'Checkup', 'Skin irritation', NULL, 5, '2025-06-08 14:40:50', '2025-06-08 14:40:50'),
(4, 1, '2025-06-30', '09:00:00', 30, 'completed', 'Checkup', 'Testing lang', 'Wala', 4, '2025-06-24 17:37:11', '2025-06-25 13:33:52'),
(5, 6, '2025-07-03', '10:30:00', 30, 'cancelled', 'Checkup', 'Consultation po Pls', 'May nakita po kaming tumor sa babang part niya kaya want mo naming magpa-consult', 8, '2025-06-25 03:28:27', '2025-06-25 20:53:04'),
(6, 8, '2025-06-30', '15:00:00', 30, 'completed', 'Checkup', 'Testing visit', 'Additional Testing', 6, '2025-06-25 12:22:38', '2025-06-26 10:03:47'),
(7, 10, '2025-06-30', '12:00:00', 30, 'confirmed', 'Checkup', 'Precious testing', 'Debby additional testing', 6, '2025-06-25 13:22:57', '2025-06-25 13:22:57'),
(8, 3, '2025-06-30', '09:30:00', 30, 'confirmed', 'Checkup', 'Robert test', 'Additional Robert Test', 6, '2025-06-25 15:58:55', '2025-06-25 15:58:55'),
(9, 1, '2025-06-28', '11:00:00', 30, 'confirmed', 'Checkup', 'Maria Garcia Visit 1', 'Additional Maria Garcia Test 1', 6, '2025-06-25 16:11:50', '2025-06-25 16:11:50'),
(10, 1, '2025-07-02', '12:00:00', 30, 'cancelled', 'Checkup', 'Maria Garcia Visit 2', 'Additional Maria Garcia Test 2', 6, '2025-06-25 16:12:54', '2025-06-25 16:42:52'),
(12, 1, '2025-07-02', '11:30:00', 30, 'requested', 'Checkup', 'skin irritation', 'nose allergy', 4, '2025-06-25 16:44:56', '2025-06-25 16:44:56'),
(13, 8, '2025-07-01', '11:00:00', 30, 'completed', 'Checkup', 'pls pls', 'yeyy', 8, '2025-06-26 10:01:39', '2025-06-26 10:03:52');

-- --------------------------------------------------------

--
-- Stand-in structure for view `appointment_details`
-- (See below for the actual view)
--
CREATE TABLE `appointment_details` (
`appointment_id` int(11)
,`appointment_date` date
,`appointment_time` time
,`status` enum('requested','confirmed','completed','cancelled')
,`type` varchar(50)
,`reason` text
,`pet_name` varchar(50)
,`species` varchar(50)
,`owner_name` varchar(101)
,`owner_phone` varchar(20)
);

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `record_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `follow_up_required` tinyint(1) DEFAULT 0,
  `follow_up_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`record_id`, `pet_id`, `appointment_id`, `visit_date`, `weight`, `temperature`, `heart_rate`, `respiratory_rate`, `symptoms`, `diagnosis`, `treatment`, `prescription`, `follow_up_required`, `follow_up_date`, `created_by`, `created_at`) VALUES
(1, 8, 6, '2025-06-30', 26.00, 39.0, 500, 100, 'may sakit2', 'ang sakit grabe2', 'testing2', 'testing2', 1, '2025-07-01', 6, '2025-06-25 21:47:54');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `appointment_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 8, 6, 'Your appointment for Agapito on June 30, 2025 at 3:00 PM was cancelled.', 'appointment', 0, '2025-06-26 18:00:16'),
(2, 8, 6, 'Your appointment for Agapito on June 30, 2025 at 3:00 PM is completed.', 'appointment', 0, '2025-06-26 18:03:47'),
(3, 8, 13, 'Your appointment for Agapito on July 1, 2025 at 11:00 AM is completed.', 'appointment', 0, '2025-06-26 18:03:52');

-- --------------------------------------------------------

--
-- Table structure for table `owners`
--

CREATE TABLE `owners` (
  `owner_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `owners`
--

INSERT INTO `owners` (`owner_id`, `user_id`, `phone`, `address`, `city`, `emergency_contact`, `emergency_phone`, `created_at`) VALUES
(1, 4, '09171234567', '123 Main St', 'Angeles City', 'Juan Garcia', '09187654321', '2025-06-08 14:40:49'),
(2, 5, '09281234567', '456 Oak Ave', 'San Fernando', 'Linda Johnson', '09298765432', '2025-06-08 14:40:49'),
(3, 6, '09300255287', 'Diyan lang sa tabi', 'Lubao, Pampanga', NULL, NULL, '2025-06-08 17:04:30'),
(4, 7, '09271642408', '14D Mapagbigay St. V.luna Brgy. pinyahan', 'Quezon City', NULL, NULL, '2025-06-10 15:05:05'),
(5, 8, '09271642408', '14D Mapagbigay St. V.luna Brgy. pinyahan', 'Quezon City', NULL, NULL, '2025-06-10 15:05:59'),
(6, 9, '09300255287', '178, St. Benedict, Remedios, Lubao', 'Pampanga', NULL, NULL, '2025-06-13 17:39:19'),
(7, 10, '09669982765', '22 M. Mariano St., Purok 3 New Lower Bicutan', 'Taguig City', NULL, NULL, '2025-06-25 02:34:56'),
(8, 11, '', '', '', NULL, NULL, '2025-06-25 12:47:55');

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `pet_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `species` varchar(50) NOT NULL,
  `breed` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `microchip_id` varchar(50) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`pet_id`, `owner_id`, `name`, `species`, `breed`, `date_of_birth`, `gender`, `color`, `weight`, `microchip_id`, `photo_url`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Max', 'Dog', 'Golden Retriever', '2020-03-15', 'male', NULL, 25.50, NULL, NULL, NULL, 1, '2025-06-08 14:40:50', '2025-06-08 14:40:50'),
(2, 1, 'Luna', 'Cat', 'Persian', '2021-06-20', 'female', NULL, 4.20, NULL, NULL, NULL, 1, '2025-06-08 14:40:50', '2025-06-08 14:40:50'),
(3, 2, 'Rocky', 'Dog', 'German Shepherd', '2019-01-10', 'male', NULL, 30.00, NULL, NULL, NULL, 1, '2025-06-08 14:40:50', '2025-06-08 14:40:50'),
(4, 2, 'Bella', 'Dog', 'Labrador', '2022-02-28', 'female', NULL, 22.30, NULL, NULL, NULL, 1, '2025-06-08 14:40:50', '2025-06-08 14:40:50'),
(5, 3, 'Butete', 'Dog', 'Mixed Breed', '2022-02-17', 'male', 'Brown', 12.60, '09118201', NULL, NULL, 1, '2025-06-13 13:54:51', '2025-06-13 13:54:51'),
(6, 5, 'Butete', 'Dog', 'Dachshund', '2018-09-23', 'female', 'White nose', 14.60, NULL, NULL, 'Allergic to Humans', 1, '2025-06-13 17:37:07', '2025-06-13 17:37:07'),
(7, 6, 'cray-cray', 'Cat', 'American Shorthair', '2009-12-12', 'male', 'Brown', 12.00, '21', NULL, '123123', 1, '2025-06-13 18:45:36', '2025-06-13 18:45:36'),
(8, 5, 'Agapito', 'Dog', 'Mixed Breed', '2020-04-12', 'male', 'Brown with White Streaks', 10.60, NULL, NULL, NULL, 1, '2025-06-14 01:59:04', '2025-06-14 01:59:04'),
(9, 7, 'Rovie', 'Cat', 'Persian', '2003-04-23', 'female', 'White', 25.00, 'N/A', NULL, 'Asthma', 1, '2025-06-25 02:36:43', '2025-06-25 02:36:43'),
(10, 7, 'Baste', 'Dog', 'Siberian Husky', '2025-06-10', 'male', 'Black', 35.00, 'N/A', NULL, NULL, 1, '2025-06-25 05:43:38', '2025-06-25 05:43:38'),
(11, 7, 'Baste', 'Dog', NULL, '2025-06-10', 'male', 'Black', 35.00, 'N/A', NULL, NULL, 1, '2025-06-25 06:03:40', '2025-06-25 06:03:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('staff','client') NOT NULL DEFAULT 'client',
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `role`, `first_name`, `last_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin@vetprecision.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Admin', 'User', 1, '2025-06-08 14:40:49', '2025-06-08 14:40:49'),
(2, 'vet@vetprecision.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Dr. John', 'Smith', 1, '2025-06-08 14:40:49', '2025-06-08 14:40:49'),
(3, 'reception@vetprecision.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Jane', 'Doe', 1, '2025-06-08 14:40:49', '2025-06-08 14:40:49'),
(4, 'client1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'Maria', 'Garcia', 1, '2025-06-08 14:40:49', '2025-06-08 14:40:49'),
(5, 'client2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'Robert', 'Johnson', 1, '2025-06-08 14:40:49', '2025-06-08 14:40:49'),
(6, 'manansalarin@gmail.com', '$2y$10$MTaBmlfJP7C/wh/XtatpK..osgGODPL.TkhWI4HNyrWvZFcKPFcIe', 'staff', 'Roan', 'Manansala', 1, '2025-06-08 17:04:30', '2025-06-13 14:07:38'),
(7, 'jane.doe@gmail.com', '$2y$10$kfEbf4RJ1XzH1tOm6tOjTuzoLmtLMZlfAQRvstSdNtpwZ55P2KCK.', 'client', 'Jane', 'Doe', 1, '2025-06-10 15:05:04', '2025-06-10 15:05:04'),
(8, 'ryan.percival@gmail.com', '$2y$10$6YHyIInI9mIrft8ne8.mouOFkUovOdGX/q96ViHwbC6KjlnpHeqHC', 'client', 'ryan', 'percival', 1, '2025-06-10 15:05:58', '2025-06-10 15:05:58'),
(9, 'butete@gmail.com', '$2y$10$CSYngzGXjazTGez70EHZ0O1QOvoDtZFvYPe0c/V8LQD.cZhFRrnLu', 'client', 'Roan', 'Manansala', 1, '2025-06-13 17:39:19', '2025-06-13 17:39:19'),
(10, 'deborahgrace0118@gmail.com', '$2y$10$VLjOvOE2EjaZ/K/OnEsa1.oM0WApjGLNCX6kpbn/8F/zwaRBSb44S', 'client', 'Precious Grace Deborah', 'Manucom', 1, '2025-06-25 02:34:55', '2025-06-25 02:34:55'),
(11, 'angel@gmail.com', '$2y$10$gmPHeNI4VxDH6C7pCj35zuD0I6Rly8rZrwIQGZlxKcE3ckpl0hDTa', 'client', 'Roan', 'Manansala', 1, '2025-06-25 12:47:55', '2025-06-25 12:47:55');

-- --------------------------------------------------------

--
-- Structure for view `appointment_details`
--
DROP TABLE IF EXISTS `appointment_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `appointment_details`  AS SELECT `a`.`appointment_id` AS `appointment_id`, `a`.`appointment_date` AS `appointment_date`, `a`.`appointment_time` AS `appointment_time`, `a`.`status` AS `status`, `a`.`type` AS `type`, `a`.`reason` AS `reason`, `p`.`name` AS `pet_name`, `p`.`species` AS `species`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `owner_name`, `o`.`phone` AS `owner_phone` FROM (((`appointments` `a` join `pets` `p` on(`a`.`pet_id` = `p`.`pet_id`)) join `owners` `o` on(`p`.`owner_id` = `o`.`owner_id`)) join `users` `u` on(`o`.`user_id` = `u`.`user_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD UNIQUE KEY `unique_appointment` (`appointment_date`,`appointment_time`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_date` (`appointment_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_pet` (`pet_id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_pet` (`pet_id`),
  ADD KEY `idx_visit_date` (`visit_date`),
  ADD KEY `idx_appointment` (`appointment_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id_idx` (`user_id`),
  ADD KEY `appointment_id_idx` (`appointment_id`);

--
-- Indexes for table `owners`
--
ALTER TABLE `owners`
  ADD PRIMARY KEY (`owner_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`pet_id`),
  ADD KEY `idx_owner` (`owner_id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_species` (`species`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `owners`
--
ALTER TABLE `owners`
  MODIFY `owner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `pet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `medical_records_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `owners`
--
ALTER TABLE `owners`
  ADD CONSTRAINT `owners_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`owner_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
