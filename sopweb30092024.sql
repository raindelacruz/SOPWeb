-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 30, 2024 at 09:41 AM
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
-- Database: `sopweb`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `created_at`) VALUES
(1, 3, 'Add Post', 'Post titled \"Post 13\" was added.', '2024-08-29 05:10:44'),
(2, 3, 'Delete Post', 'Deleted post with ID 24', '2024-08-29 05:21:06'),
(3, 3, 'Delete Post', 'Deleted SOP # 26 entitled ', '2024-08-29 05:22:52'),
(4, 3, 'Delete Post', 'Deleted SOP # 25 entitled ', '2024-08-29 05:24:24'),
(5, 3, 'Delete Post', 'Deleted SOP # 25 entitled \'Post 12\'', '2024-08-29 05:26:54'),
(6, 3, 'Add Post', 'Post titled \"SOP 12\" was added.', '2024-08-29 05:27:49'),
(7, 3, 'Change Status', 'User ID #1 status changed to \'active\'', '2024-08-29 06:26:29'),
(8, 3, 'Change Status', 'User ID #11 status changed to \'inactive\'', '2024-08-29 06:27:15'),
(9, 3, 'Change Status', 'User ID #11 status changed to \'active\'', '2024-08-29 06:27:24'),
(10, 3, 'Change Role', 'User ID #1 role changed to \'admin\'', '2024-08-29 06:27:27'),
(11, 3, 'Change Role', 'User ID #1 role changed to \'user\'', '2024-08-29 06:27:41'),
(12, 3, 'Change Status', 'User ID #11 status changed to \'inactive\'', '2024-08-29 23:26:44'),
(13, 3, 'Change Status', 'User ID #11 status changed to \'active\'', '2024-08-29 23:27:06'),
(14, 3, 'Change Role', 'User ID #11 role changed to \'admin\'', '2024-08-29 23:27:21'),
(15, 3, 'Change Role', 'User ID #11 role changed to \'user\'', '2024-08-29 23:28:32'),
(16, 3, 'Add Post', 'Post titled \"Post 14\" was added.', '2024-08-30 07:06:06'),
(17, 3, 'Change Status', 'User ID #1 status changed to \'inactive\'', '2024-08-30 07:06:32'),
(18, 3, 'Change Role', 'User ID #1 role changed to \'admin\'', '2024-08-30 07:06:40'),
(19, 3, 'Change Status', 'User ID #1 status changed to \'active\'', '2024-08-30 07:08:23'),
(20, 3, 'Change Role', 'User ID #1 role changed to \'user\'', '2024-08-30 07:08:32'),
(21, 3, 'Delete Post', 'Deleted SOP # 28 entitled \'Post 14\'', '2024-09-11 00:42:17'),
(22, 3, 'Add Post', 'Post titled \"SOP 13\" was added.', '2024-09-11 01:45:32'),
(23, 3, 'Change Status', 'User ID #12 status changed to \'active\'', '2024-09-11 02:21:50'),
(24, 3, 'Edit Post', 'Post titled \"SOP 13\" was added.', '2024-09-12 00:27:37'),
(25, 3, 'Edit Post', 'Post titled \"SOP 13\" was added.', '2024-09-12 03:55:04'),
(26, 3, 'Delete Post', 'Deleted SOP # 29 entitled \'SOP 13\'', '2024-09-12 03:55:27'),
(27, 3, 'Add Post', 'Post titled \"SOP 13\" was added.', '2024-09-12 03:57:13'),
(28, 3, 'Edit Post', 'Post titled \"SOP 13\" was added.', '2024-09-12 03:57:28'),
(29, 3, 'Edit Post', 'Post titled \"SOP 13\" was added.', '2024-09-12 03:58:01'),
(30, 3, 'Change Role', 'User ID #1 role changed to \'admin\'', '2024-09-12 07:54:56'),
(31, 1, 'Delete Post', 'Deleted SOP # 1 entitled \'Post 1\'', '2024-09-13 05:50:54'),
(32, 1, 'Delete Post', 'Deleted SOP # 30 entitled \'SOP 13\'', '2024-09-16 03:47:16'),
(33, 1, 'Delete Post', 'Deleted SOP # 27 entitled \'SOP 12\'', '2024-09-16 03:47:18'),
(34, 1, 'Delete Post', 'Deleted SOP # 20 entitled \'Post 11\'', '2024-09-16 03:47:19'),
(35, 1, 'Delete Post', 'Deleted SOP # 19 entitled \'Post 10\'', '2024-09-16 03:47:19'),
(36, 1, 'Delete Post', 'Deleted SOP # 18 entitled \'Post 9\'', '2024-09-16 03:47:20'),
(37, 1, 'Delete Post', 'Deleted SOP # 12 entitled \'Post 8\'', '2024-09-16 03:47:20'),
(38, 1, 'Delete Post', 'Deleted SOP # 7 entitled \'Post 7\'', '2024-09-16 03:47:21'),
(39, 1, 'Delete Post', 'Deleted SOP # 6 entitled \'Post 6\'', '2024-09-16 03:47:22'),
(40, 1, 'Delete Post', 'Deleted SOP # 5 entitled \'Post 5\'', '2024-09-16 03:47:23'),
(41, 1, 'Delete Post', 'Deleted SOP # 4 entitled \'Post 4\'', '2024-09-16 03:47:23'),
(42, 1, 'Delete Post', 'Deleted SOP # 3 entitled \'Post 3\'', '2024-09-16 03:47:24'),
(43, 1, 'Delete Post', 'Deleted SOP # 2 entitled \'Post 2\'', '2024-09-16 03:47:25'),
(44, 1, 'Add Post', 'Post titled \"SOP # 1\" was added.', '2024-09-16 03:53:30'),
(45, 1, 'Add Post', 'Post titled \"SOP # 2\" was added.', '2024-09-16 03:54:25'),
(46, 1, 'Add Post', 'Post titled \"SOP #3\" was added.', '2024-09-16 03:56:07'),
(47, 1, 'Add Post', 'Post titled \"SOP #4\" was added.', '2024-09-16 03:57:08'),
(48, 1, 'Add Post', 'Post titled \"SOP #5\" was added.', '2024-09-16 03:58:10'),
(49, 1, 'Add Post', 'Post titled \"SOP #6\" was added.', '2024-09-16 03:59:47'),
(50, 1, 'Add Post', 'Post titled \"SOP #7\" was added.', '2024-09-16 04:00:56'),
(51, 1, 'Edit Post', 'Post titled \"SOP #6\" was added.', '2024-09-16 06:20:20'),
(52, 1, 'Edit Post', 'Post titled \"SOP #6\" was added.', '2024-09-16 06:20:31'),
(53, 1, 'Add Post', 'Post titled \"SOP #8\" was added.', '2024-09-18 07:15:00'),
(54, 1, 'Change Role', 'User ID #50 role changed to \'admin\'', '2024-09-21 06:20:53'),
(55, 1, 'Change Role', 'User ID #50 role changed to \'user\'', '2024-09-21 06:20:54');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `reference_number` varchar(100) NOT NULL,
  `date_of_effectivity` date NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `amended_post_id` int(11) DEFAULT NULL,
  `superseded_post_id` int(11) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `is_superseded` tinyint(1) DEFAULT 0,
  `is_amendable` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `title`, `description`, `reference_number`, `date_of_effectivity`, `upload_date`, `amended_post_id`, `superseded_post_id`, `file`, `is_superseded`, `is_amendable`) VALUES
(31, 'SOP # 1', 'This is SOP 001', 'SOP 001', '2024-05-01', '2024-09-15 16:00:00', NULL, NULL, '66e7abba8ee278.61953244.pdf', 0, 1),
(32, 'SOP # 2', 'This is SOP #2 superseding SOP #1', 'SOP002', '2024-06-04', '2024-09-15 16:00:00', NULL, 31, '66e7abf1bc3823.92893912.pdf', 0, 1),
(33, 'SOP #3', 'This is SOP #3 amending SOP #1', 'SOP 003', '2024-07-25', '2024-09-15 16:00:00', 31, NULL, '66e7ac572d5c11.81833000.pdf', 0, 1),
(34, 'SOP #4', 'This is new SOP #4', 'SOP 004', '2024-06-28', '2024-09-15 16:00:00', NULL, NULL, '66e7ac941e2fe9.90256384.pdf', 0, 1),
(35, 'SOP #5', 'This is SOP #5 amending SOP #4', 'SOP 005', '2024-07-31', '2024-09-15 16:00:00', 34, NULL, '66e7acd29664e4.87659395.pdf', 0, 1),
(36, 'SOP #6', 'This is SOP #6 amending SOP #5', 'SOP 006', '2024-08-09', '2024-09-15 16:00:00', 35, NULL, '66e7ad330119f2.21497981.pdf', 0, 1),
(37, 'SOP #7', 'This is SOP #7', 'SOP 007', '2024-08-16', '2024-09-15 16:00:00', NULL, NULL, '66e7ad787c67a3.83102008.pdf', 0, 1),
(38, 'SOP #8', 'This is sop 8', 'SOP008', '2024-09-04', '2024-09-17 16:00:00', 33, NULL, '66ea7df4cb5057.30690800.pdf', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `office` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_number`, `firstname`, `lastname`, `middle_name`, `office`, `email`, `password`, `role`, `status`) VALUES
(1, '939908', 'Rainier John', 'Dela Cruz', 'Juico', 'CPMSD', 'rainier.delacruz@nfa.gov.ph', '$2y$10$mi60wsDeYE744f23mfk6bu/mJ7erhTIJ/RN78LsyETem4hFCJhLKy', 'admin', 'active'),
(3, '000000', 'Admin', 'Admin', NULL, 'Admin', 'admin@example.com', '$2y$10$mi60wsDeYE744f23mfk6bu/mJ7erhTIJ/RN78LsyETem4hFCJhLKy', 'admin', 'active'),
(49, '878675', 'Boots', 'Torres', 'Badiola', 'CPMSD', 'torres_bnn@yahoo.com', '$2y$10$cTDLTt/zwraO2tgmoQQsBel1yIphkg/YWTgvF80tJMe0WFKUTrTv.', 'user', 'inactive'),
(50, '39321', 'Mik', 'Dela', 'Ele', 'BSP', 'kelenzano@yahoo.com', '$2y$10$BR6xHeMJqXR0nuNXmPBJMO1M8gMMLyJLP7TGQwxuKA7QSbxMbE2eC', 'user', 'inactive'),
(51, '39321', 'Mik', 'Dela', 'Ele', 'BSP', 'kelenzano@gmail.com', '$2y$10$W9VWqOqwfOYhqaZCcFGQYe.QCCLKy4cGg2d9R6Vi9tnv3qnlvCjN.', 'user', 'inactive'),
(53, '939909', 'rainier', 'dela cruz', 'juico', 'cpmsd', 'rainierjdelacruz@gmail.com', '$2y$10$kSPKdqejm5cWbRr2YvHzb.5fWhlh2hnU5DLQjgZhlBpPIUGDEjn7y', 'user', 'inactive');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `amended_post_id` (`amended_post_id`),
  ADD KEY `superseded_post_id` (`superseded_post_id`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `fk_posts_amended_post_id` FOREIGN KEY (`amended_post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_superseded_post_id` FOREIGN KEY (`superseded_post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
