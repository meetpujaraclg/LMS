-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 24, 2025 at 08:48 AM
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
-- Database: `edtech_lms`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT 'default_admin.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `first_name`, `last_name`, `email`, `password`, `profile_picture`, `created_at`, `updated_at`) VALUES
(3, 'System', 'Admin', 'admin.edutech@zohomail.in', '$2y$10$CSiylbop/XLecRIS0aYBJeZmGiIbIX.pASi/b2rvOBBp/RX6owT1u', '1762937561_edutech.png', '2025-11-12 08:52:41', '2025-11-12 08:52:41');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#6c757d',
  `icon` varchar(50) DEFAULT 'folder',
  `course_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `color`, `icon`, `course_count`, `created_at`, `updated_at`) VALUES
(2, 'DIGITAL MARKETING', 'DIGITAL MARKETING', '#27ae60', 'shopping-cart', 0, '2025-11-07 09:44:04', '2025-11-07 09:44:04'),
(3, 'NEWS &amp; MEDIA', 'NEWS &amp; MEDIA', '#6c757d', 'book', 0, '2025-11-07 09:48:07', '2025-11-07 09:48:07');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `thumbnail` varchar(255) DEFAULT NULL,
  `duration` int(11) DEFAULT 0,
  `level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `title`, `description`, `instructor_id`, `is_published`, `category`, `price`, `thumbnail`, `duration`, `level`, `created_at`, `updated_at`) VALUES
(23, 'Cooking champ', 'This is the only cooking course you need!', 4, 0, 'Cook', 800.00, 'courses/694b56b066c38.jpg', 2, 'advanced', '2025-12-24 02:57:52', '2025-12-24 05:40:03');

-- --------------------------------------------------------

--
-- Table structure for table `course_materials`
--

CREATE TABLE `course_materials` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `material_type` enum('document','video','link','quiz','assignment') NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `external_url` varchar(500) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_modules`
--

CREATE TABLE `course_modules` (
  `id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_modules`
--

INSERT INTO `course_modules` (`id`, `course_id`, `title`, `description`, `sort_order`, `created_at`) VALUES
(1, 23, 'Introduction', 'This is the intro of kitchen tools', 1, '2025-12-24 02:58:25'),
(4, 23, 'New recipies', 'In this module, you&#039;ll learn to cook new recipies', 2, '2025-12-24 07:33:27');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `completed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `user_id`, `course_id`, `enrolled_at`, `completed_at`, `progress`, `completed`, `created_at`, `updated_at`) VALUES
(14, 25, 23, '2025-12-24 05:44:21', NULL, 0, 0, '2025-12-24 05:44:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `instructors`
--

CREATE TABLE `instructors` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `id_proof` varchar(255) NOT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `experience` varchar(100) DEFAULT NULL,
  `expertise_area` varchar(255) DEFAULT NULL,
  `profile_status` enum('pending','active','inactive') NOT NULL DEFAULT 'pending',
  `verified` enum('yes','no') DEFAULT 'no',
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructors`
--

INSERT INTO `instructors` (`id`, `first_name`, `last_name`, `email`, `password`, `profile_picture`, `bio`, `id_proof`, `qualification`, `experience`, `expertise_area`, `profile_status`, `verified`, `email_verified`, `created_at`, `updated_at`) VALUES
(4, 'Meet', 'Pujara', 'meet.pujara123382@marwadiuniversity.ac.in', '$2y$10$5KW2ghNrDnu1WQVaxVDMxe0Lqf8uqzV5iiWWC.i9VkNYK0FULTfOm', '16d74f6197620023be1780a0_1763202040.jpg', 'Hey', 'fcacc99942b5d1e588a2064f_1763202040.pdf', '2743d6cb39da9f5925d1f861_1763202040.pdf', '5', 'Web development', 'active', 'yes', 1, '2025-11-15 10:21:07', '2025-12-23 08:42:51');

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `id` int(11) NOT NULL,
  `module_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `duration` int(11) DEFAULT 0,
  `video_path` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `module_id`, `title`, `content`, `video_url`, `duration`, `video_path`, `sort_order`, `created_at`) VALUES
(1, 1, 'Kitchen tools', 'Hey', '', 21, 'videos/lesson_694b6d2eacc897.62209447.mp4', 0, '2025-12-24 04:33:50'),
(2, 1, 'Second', '2nd video', '', 21, 'videos/lesson_694b797fa0a9e7.16774323.mp4', 0, '2025-12-24 05:26:23'),
(3, 1, 'Third', '3rd video', '', 21, 'videos/lesson_694b79a3d07922.32287399.mp4', 0, '2025-12-24 05:27:00'),
(4, 1, 'Forth video', '4th video', '', 21, 'videos/lesson_694b79fb295836.12825822.mp4', 0, '2025-12-24 05:28:27'),
(5, 1, 'Fifth video', '5th video', '', 21, 'videos/lesson_694b7a1fe18d01.03484145.mp4', 0, '2025-12-24 05:29:04'),
(6, 1, 'Sixth Video', '6th video', '', 21, 'videos/lesson_694b7b0190d123.20060163.mp4', 0, '2025-12-24 05:32:49'),
(12, 4, 'Pizza 😋', 'In this lesson, you\'ll learn about making pizza 😀', '', 0, 'videos/lesson_694b9776a4c5c1.50350931.mp4', 0, '2025-12-24 07:34:15');

-- --------------------------------------------------------

--
-- Table structure for table `progress`
--

CREATE TABLE `progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_material_progress`
--

CREATE TABLE `student_material_progress` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `progress_percentage` int(11) DEFAULT 0,
  `time_spent` int(11) DEFAULT 0,
  `last_position` varchar(100) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'text',
  `setting_group` varchar(50) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `setting_group`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'EduTech', 'text', 'general', 'The name of your learning platform', '2025-11-06 18:37:29', '2025-11-12 10:43:55'),
(2, 'site_email', 'admin.edutech@zohomail.in', 'email', 'general', 'Primary contact email', '2025-11-06 18:37:29', '2025-11-12 10:43:55'),
(3, 'site_description', 'Learning Management System', 'textarea', 'general', 'Brief description of your platform', '2025-11-06 18:37:29', '2025-11-06 18:37:29'),
(4, 'site_url', 'http://localhost', 'url', 'general', 'Your website URL', '2025-11-06 18:37:29', '2025-11-14 12:31:59'),
(5, 'registration_enabled', '1', 'checkbox', 'system', 'Allow new user registrations', '2025-11-06 18:37:29', '2025-11-06 18:37:29'),
(6, 'maintenance_mode', '0', 'checkbox', 'system', 'Put site in maintenance mode', '2025-11-06 18:37:29', '2025-11-06 18:37:29'),
(7, 'max_file_size', '10', 'number', 'system', 'Maximum file upload size in MB', '2025-11-06 18:37:29', '2025-11-06 18:37:29'),
(8, 'allowed_file_types', 'jpg,jpeg,png,gif,pdf,mp4,avi,mov', 'text', 'system', 'Comma-separated list of allowed file types', '2025-11-06 18:37:29', '2025-11-06 18:37:29'),
(9, 'password_min_length', '6', 'number', 'security', 'Minimum password length', '2025-11-06 18:37:29', '2025-11-07 09:41:41'),
(10, 'login_attempts', '5', 'number', 'security', 'Maximum login attempts before lockout', '2025-11-06 18:37:29', '2025-11-06 18:37:29'),
(11, 'session_timeout', '60', 'number', 'security', 'Session timeout in minutes', '2025-11-06 18:37:29', '2025-11-06 18:37:29'),
(12, 'smtp_host', '', 'text', 'email', 'SMTP server host', '2025-11-06 18:37:29', '2025-11-06 18:37:29'),
(13, 'smtp_port', '587', 'number', 'email', 'SMTP server port', '2025-11-06 18:37:29', '2025-11-06 18:37:29'),
(14, 'smtp_username', '', 'text', 'email', 'SMTP username', '2025-11-06 18:37:29', '2025-11-06 18:37:29'),
(15, 'smtp_password', '', 'password', 'email', 'SMTP password', '2025-11-06 18:37:29', '2025-11-06 18:37:29'),
(16, 'currency', 'USD', 'text', 'payment', 'Default currency', '2025-11-06 18:37:29', '2025-11-06 18:37:29'),
(17, 'payment_gateway', 'stripe', 'select:paypal,stripe,razorpay', 'payment', 'Payment gateway', '2025-11-06 18:37:29', '2025-11-06 18:37:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `profile_picture`, `bio`, `created_at`, `updated_at`) VALUES
(25, 'meet.thakkar@zohomail.in', '$2y$10$2nCNkD7edylhPrQT1to9EO01RysPWNNYJpHNIWJ59QhNjbKaHg8WG', 'Meet', 'Thakkar', 'uploads/profile_pictures/1763461156_ProfilePhoto.jpg', 'Hey', '2025-11-18 10:19:56', '2025-12-04 07:11:21');

-- --------------------------------------------------------

--
-- Table structure for table `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `course_modules`
--
ALTER TABLE `course_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`user_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `instructors`
--
ALTER TABLE `instructors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `progress`
--
ALTER TABLE `progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `student_material_progress`
--
ALTER TABLE `student_material_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_material` (`student_id`,`material_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_progress` (`user_id`,`lesson_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `course_materials`
--
ALTER TABLE `course_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `course_modules`
--
ALTER TABLE `course_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `instructors`
--
ALTER TABLE `instructors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `progress`
--
ALTER TABLE `progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_material_progress`
--
ALTER TABLE `student_material_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD CONSTRAINT `course_materials_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_materials_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_modules`
--
ALTER TABLE `course_modules`
  ADD CONSTRAINT `course_modules_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `progress`
--
ALTER TABLE `progress`
  ADD CONSTRAINT `progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `progress_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`);

--
-- Constraints for table `student_material_progress`
--
ALTER TABLE `student_material_progress`
  ADD CONSTRAINT `student_material_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_material_progress_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `course_materials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_material_progress_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD CONSTRAINT `user_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_progress_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
