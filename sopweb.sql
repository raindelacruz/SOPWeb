-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 16, 2026 at 09:18 AM
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

-- --------------------------------------------------------

--
-- Table structure for table `document_relationships`
--

CREATE TABLE `document_relationships` (
  `id` int(10) UNSIGNED NOT NULL,
  `source_version_id` int(10) UNSIGNED NOT NULL,
  `target_version_id` int(10) UNSIGNED NOT NULL,
  `relationship_type` varchar(50) NOT NULL,
  `affected_sections` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `management_source` varchar(50) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `procedures`
--

CREATE TABLE `procedures` (
  `id` int(10) UNSIGNED NOT NULL,
  `procedure_code` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `owner_office` varchar(100) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'ACTIVE',
  `current_version_id` int(10) UNSIGNED DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `procedure_sections`
--

CREATE TABLE `procedure_sections` (
  `id` int(10) UNSIGNED NOT NULL,
  `procedure_id` int(10) UNSIGNED NOT NULL,
  `section_key` varchar(150) NOT NULL,
  `section_title` varchar(255) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `procedure_versions`
--

CREATE TABLE `procedure_versions` (
  `id` int(10) UNSIGNED NOT NULL,
  `procedure_id` int(10) UNSIGNED NOT NULL,
  `version_number` varchar(50) NOT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `summary_of_change` text DEFAULT NULL,
  `change_type` varchar(50) NOT NULL DEFAULT 'NEW',
  `effective_date` date DEFAULT NULL,
  `registration_date` date NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'REGISTERED',
  `file_path` varchar(255) DEFAULT NULL,
  `based_on_version_id` int(10) UNSIGNED DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `registered_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `section_change_log`
--

CREATE TABLE `section_change_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `procedure_section_id` int(10) UNSIGNED NOT NULL,
  `procedure_version_id` int(10) UNSIGNED NOT NULL,
  `document_relationship_id` int(10) UNSIGNED DEFAULT NULL,
  `change_type` varchar(50) NOT NULL,
  `entry_kind` varchar(50) NOT NULL DEFAULT 'AFFECTED_SECTION',
  `section_label` varchar(255) NOT NULL,
  `change_summary` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `role` enum('user','admin','super_admin') DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_number`, `firstname`, `lastname`, `middle_name`, `office`, `email`, `password`, `role`, `status`) VALUES
(1, '100001', 'Registry', 'SuperAdmin', 'A', 'CPMSD', 'superadmin@test.com', '$2y$10$4/iGSWdCwOxSmfMLamRsyOwTQvD2328o45Icx.1ElNQjgzvJmb3ye', 'super_admin', 'active'),
(2, '100002', 'Registry', 'Admin', 'B', 'AO', 'admin@test.com', '$2y$10$4/iGSWdCwOxSmfMLamRsyOwTQvD2328o45Icx.1ElNQjgzvJmb3ye', 'admin', 'active'),
(3, '100003', 'Records', 'Viewer', 'C', 'FD', 'user@test.com', '$2y$10$4/iGSWdCwOxSmfMLamRsyOwTQvD2328o45Icx.1ElNQjgzvJmb3ye', 'user', 'active'),
(4, '100004', 'Inactive', 'User', 'D', 'AGSD', 'inactive.fixture@sopweb.test', '$2y$10$ZRE4XYs6IbqQuvW3AlZGuer6TUlw9EkK5u8nx7NjQYgb0WTDQqD2G', 'user', 'inactive');

-- --------------------------------------------------------

--
-- Table structure for table `workflow_actions`
--

CREATE TABLE `workflow_actions` (
  `id` int(10) UNSIGNED NOT NULL,
  `procedure_version_id` int(10) UNSIGNED NOT NULL,
  `lifecycle_action_type` varchar(100) NOT NULL,
  `from_status` varchar(50) DEFAULT NULL,
  `to_status` varchar(50) DEFAULT NULL,
  `acted_by` int(10) UNSIGNED DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `acted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Indexes for table `document_relationships`
--
ALTER TABLE `document_relationships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_document_relationships_source_version_id` (`source_version_id`),
  ADD KEY `idx_document_relationships_target_version_id` (`target_version_id`),
  ADD KEY `idx_document_relationships_relationship_type` (`relationship_type`),
  ADD KEY `idx_document_relationships_management_source` (`management_source`);

--
-- Indexes for table `procedures`
--
ALTER TABLE `procedures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_procedures_procedure_code` (`procedure_code`),
  ADD KEY `idx_procedures_current_version_id` (`current_version_id`),
  ADD KEY `idx_procedures_created_by` (`created_by`);

--
-- Indexes for table `procedure_sections`
--
ALTER TABLE `procedure_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_procedure_sections_procedure_section_key` (`procedure_id`,`section_key`),
  ADD KEY `idx_procedure_sections_procedure_id` (`procedure_id`);

--
-- Indexes for table `procedure_versions`
--
ALTER TABLE `procedure_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_procedure_versions_procedure_id` (`procedure_id`),
  ADD KEY `idx_procedure_versions_based_on_version_id` (`based_on_version_id`),
  ADD KEY `idx_procedure_versions_status` (`status`),
  ADD KEY `idx_procedure_versions_effective_date` (`effective_date`);

--
-- Indexes for table `section_change_log`
--
ALTER TABLE `section_change_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_section_change_log_procedure_section_id` (`procedure_section_id`),
  ADD KEY `idx_section_change_log_procedure_version_id` (`procedure_version_id`),
  ADD KEY `idx_section_change_log_document_relationship_id` (`document_relationship_id`),
  ADD KEY `idx_section_change_log_change_type` (`change_type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `workflow_actions`
--
ALTER TABLE `workflow_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workflow_actions_procedure_version_id` (`procedure_version_id`),
  ADD KEY `idx_workflow_actions_acted_at` (`acted_at`),
  ADD KEY `idx_workflow_actions_lifecycle_action_type` (`lifecycle_action_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_relationships`
--
ALTER TABLE `document_relationships`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procedures`
--
ALTER TABLE `procedures`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procedure_sections`
--
ALTER TABLE `procedure_sections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procedure_versions`
--
ALTER TABLE `procedure_versions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `section_change_log`
--
ALTER TABLE `section_change_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `workflow_actions`
--
ALTER TABLE `workflow_actions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `document_relationships`
--
ALTER TABLE `document_relationships`
  ADD CONSTRAINT `fk_document_relationships_source` FOREIGN KEY (`source_version_id`) REFERENCES `procedure_versions` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_document_relationships_target` FOREIGN KEY (`target_version_id`) REFERENCES `procedure_versions` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `procedures`
--
ALTER TABLE `procedures`
  ADD CONSTRAINT `fk_procedures_current_version` FOREIGN KEY (`current_version_id`) REFERENCES `procedure_versions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `procedure_sections`
--
ALTER TABLE `procedure_sections`
  ADD CONSTRAINT `fk_procedure_sections_procedure` FOREIGN KEY (`procedure_id`) REFERENCES `procedures` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `procedure_versions`
--
ALTER TABLE `procedure_versions`
  ADD CONSTRAINT `fk_procedure_versions_based_on` FOREIGN KEY (`based_on_version_id`) REFERENCES `procedure_versions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_procedure_versions_procedure` FOREIGN KEY (`procedure_id`) REFERENCES `procedures` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `section_change_log`
--
ALTER TABLE `section_change_log`
  ADD CONSTRAINT `fk_section_change_log_relationship` FOREIGN KEY (`document_relationship_id`) REFERENCES `document_relationships` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_section_change_log_section` FOREIGN KEY (`procedure_section_id`) REFERENCES `procedure_sections` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_section_change_log_version` FOREIGN KEY (`procedure_version_id`) REFERENCES `procedure_versions` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `workflow_actions`
--
ALTER TABLE `workflow_actions`
  ADD CONSTRAINT `fk_workflow_actions_procedure_version` FOREIGN KEY (`procedure_version_id`) REFERENCES `procedure_versions` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
