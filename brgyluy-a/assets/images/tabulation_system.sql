-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 02, 2026 at 03:00 AM
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
-- Database: `tabulation_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', 'admin123', '2026-03-21 11:10:34'),
(4, 'skl', 'admin123', '2026-03-22 04:49:51');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_locked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `event_id`, `name`, `description`, `sort_order`, `is_locked`) VALUES
(8, 5, 'Double Kara', 'Double Kara jd dey', 0, 1),
(9, 5, 'Dance', 'murag Zumba ni?', 1, 1),
(31, 15, 'MLBB - 5v5', '5v5 mix\r\n Champion 3k\r\n 1st 2k\r\n 2nd 1K\r\nbahala na si batman', 0, 1),
(32, 15, 'Pickle Ball', 'Mix Double', 0, 1),
(33, 15, 'Volleyball (F)', '', 0, 1),
(34, 15, 'Basketball (U-21)', '', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `contestants`
--

CREATE TABLE `contestants` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','declined') DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contestants`
--

INSERT INTO `contestants` (`id`, `name`, `description`, `profile_image`, `status`, `created_at`) VALUES
(9, 'Dance Fussion Familya', '', '', 'approved', '2026-04-15 13:06:34'),
(10, 'Mendoza Zumba', '', '', 'approved', '2026-04-15 13:07:04'),
(11, 'The Brothers', '', '', 'approved', '2026-04-15 13:07:22'),
(12, 'Mama Mia Dancers', '', '', 'approved', '2026-04-15 13:07:37'),
(13, 'Ernie Suan Caling', '', '', 'approved', '2026-04-15 13:08:10'),
(14, 'Marilyn Ayunto', '', '', 'approved', '2026-04-15 13:09:01'),
(15, 'Vergillo Mier okay', '', '', 'approved', '2026-04-15 13:09:16'),
(16, 'Rolando Abarquez', '', '', 'approved', '2026-04-15 13:09:35'),
(17, 'Alfredo Egay', '', '', 'approved', '2026-04-15 13:09:58'),
(53, 'Hello world', '', '', 'approved', '2026-05-03 09:30:28'),
(79, 'Hello World', 'dawd', '1793104638_6ae09afedb1a8.jpg', 'pending', '2026-10-27 12:37:18'),
(82, '123', 'Nice', '1793106667_6ae0a2eb8d9dd.png', 'pending', '2026-10-27 13:11:07'),
(85, 'asasasd', '', '1793114376_6ae0c108aaf7a.png', 'pending', '2026-10-27 15:19:36'),
(88, 'Kumare', '', '1793188054_6ae1e0d67586c.jpg', 'approved', '2026-10-28 11:47:34'),
(92, 'Sample lng', '', '1793548942_6ae7628e78470.png', 'pending', '2026-11-01 16:02:22'),
(93, 'Oinoin', '', '1793549510_6ae764c640b61.jpg', 'pending', '2026-11-01 16:11:50');

-- --------------------------------------------------------

--
-- Table structure for table `contestant_category`
--

CREATE TABLE `contestant_category` (
  `contestant_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contestant_category`
--

INSERT INTO `contestant_category` (`contestant_id`, `category_id`) VALUES
(9, 9),
(10, 9),
(11, 9),
(12, 9),
(13, 8),
(14, 8),
(15, 8),
(16, 8),
(17, 8),
(79, 8),
(82, 9),
(85, 8),
(88, 31),
(92, 31),
(92, 32),
(93, 32),
(93, 33);

-- --------------------------------------------------------

--
-- Table structure for table `criteria`
--

CREATE TABLE `criteria` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `max_score` decimal(5,2) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `criteria`
--

INSERT INTO `criteria` (`id`, `category_id`, `name`, `description`, `max_score`, `sort_order`) VALUES
(24, 8, 'Creativity/Originality', '', 40.00, 0),
(25, 8, 'Costume & Apperance', '1', 25.00, 0),
(26, 8, 'Stage Presence & Confidence', '', 25.00, 2),
(27, 8, 'Overall Impact', '', 10.00, 3),
(28, 9, 'Syncronazation & Teamwork', '', 30.00, 0),
(29, 9, 'Execution Technique', '', 30.00, 1),
(30, 9, 'Choreography', '', 20.00, 2),
(31, 9, 'Props & Overall impact', '', 10.00, 3),
(43, 31, 'Win', 'Nice G mga Bai', 100.00, 0),
(44, 32, 'Win', '', 100.00, 0),
(45, 33, 'Win', '', 100.00, 0),
(46, 34, 'Win', '', 100.00, 0),
(47, 31, 'Losstreak', 'whahaha', 100.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `name`, `description`, `event_date`, `is_current`, `created_at`) VALUES
(5, 'Araw ng Purok Lina-ao', 'Araw ng Purok Lina-ao, April 15 2026', '2026-04-15', 0, '2026-04-15 10:34:52'),
(15, '2026 - Araw ng Baragay Luy-A', '48th Years of Celebration in Barangay Luy-a, Tukuran, Zamboanga del sur', '2026-12-18', 1, '2026-10-27 16:25:36');

-- --------------------------------------------------------

--
-- Table structure for table `judges`
--

CREATE TABLE `judges` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `judges`
--

INSERT INTO `judges` (`id`, `username`, `password`, `full_name`, `email`, `created_at`) VALUES
(10, 'judge1', 'judge1', 'Judge-1', '', '2026-04-15 13:00:04'),
(11, 'judge2', 'judge2', 'Judge-2', '', '2026-04-15 13:00:50'),
(12, 'judge3', 'judge3', 'Judge-3', '', '2026-04-15 13:04:15');

-- --------------------------------------------------------

--
-- Table structure for table `judge_category`
--

CREATE TABLE `judge_category` (
  `judge_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `judge_category`
--

INSERT INTO `judge_category` (`judge_id`, `category_id`) VALUES
(10, 8),
(10, 9),
(11, 8),
(11, 9),
(12, 8),
(12, 9);

-- --------------------------------------------------------

--
-- Table structure for table `scores`
--

CREATE TABLE `scores` (
  `id` int(11) NOT NULL,
  `judge_id` int(11) NOT NULL,
  `contestant_id` int(11) NOT NULL,
  `criterion_id` int(11) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scores`
--

INSERT INTO `scores` (`id`, `judge_id`, `contestant_id`, `criterion_id`, `score`, `created_at`, `updated_at`) VALUES
(295, 10, 17, 24, 40.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(296, 10, 17, 25, 21.00, '2026-04-19 09:53:47', '2026-05-10 06:11:52'),
(297, 10, 17, 26, 0.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(298, 10, 17, 27, 0.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(299, 10, 13, 24, 35.00, '2026-04-19 09:53:47', '2026-10-27 11:34:38'),
(300, 10, 13, 25, 25.00, '2026-04-19 09:53:47', '2026-04-22 14:58:30'),
(301, 10, 13, 26, 0.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(302, 10, 13, 27, 0.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(303, 10, 14, 24, 0.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(304, 10, 14, 25, 0.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(305, 10, 14, 26, 0.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(306, 10, 14, 27, 0.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(307, 10, 16, 24, 0.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(308, 10, 16, 25, 0.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(309, 10, 16, 26, 0.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(310, 10, 16, 27, 0.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(311, 10, 15, 24, 0.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(312, 10, 15, 25, 0.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(313, 10, 15, 26, 25.00, '2026-04-19 09:53:47', '2026-10-27 11:37:07'),
(314, 10, 15, 27, 0.00, '2026-04-19 09:53:47', '2026-04-19 09:53:47'),
(477, 11, 17, 24, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(478, 11, 17, 25, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(479, 11, 17, 26, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(480, 11, 17, 27, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(481, 11, 13, 24, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(482, 11, 13, 25, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(483, 11, 13, 26, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(484, 11, 13, 27, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(485, 11, 14, 24, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(486, 11, 14, 25, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(487, 11, 14, 26, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(488, 11, 14, 27, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(489, 11, 16, 24, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(490, 11, 16, 25, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(491, 11, 16, 26, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(492, 11, 16, 27, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(493, 11, 15, 24, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(494, 11, 15, 25, 25.00, '2026-10-27 12:54:27', '2026-10-27 12:56:12'),
(495, 11, 15, 26, 25.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(496, 11, 15, 27, 0.00, '2026-10-27 12:54:27', '2026-10-27 12:54:27'),
(517, 12, 17, 24, 20.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(518, 12, 17, 25, 15.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(519, 12, 17, 26, 15.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(520, 12, 17, 27, 10.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(521, 12, 13, 24, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(522, 12, 13, 25, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(523, 12, 13, 26, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(524, 12, 13, 27, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(525, 12, 14, 24, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(526, 12, 14, 25, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(527, 12, 14, 26, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(528, 12, 14, 27, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(529, 12, 16, 24, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(530, 12, 16, 25, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(531, 12, 16, 26, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(532, 12, 16, 27, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(533, 12, 15, 24, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(534, 12, 15, 25, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(535, 12, 15, 26, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28'),
(536, 12, 15, 27, 0.00, '2026-10-27 13:05:28', '2026-10-27 13:05:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `contestants`
--
ALTER TABLE `contestants`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contestant_category`
--
ALTER TABLE `contestant_category`
  ADD PRIMARY KEY (`contestant_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `criteria`
--
ALTER TABLE `criteria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `judges`
--
ALTER TABLE `judges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `judge_category`
--
ALTER TABLE `judge_category`
  ADD PRIMARY KEY (`judge_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `scores`
--
ALTER TABLE `scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_score` (`judge_id`,`contestant_id`,`criterion_id`),
  ADD KEY `contestant_id` (`contestant_id`),
  ADD KEY `criterion_id` (`criterion_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `contestants`
--
ALTER TABLE `contestants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `criteria`
--
ALTER TABLE `criteria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `judges`
--
ALTER TABLE `judges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `scores`
--
ALTER TABLE `scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=537;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contestant_category`
--
ALTER TABLE `contestant_category`
  ADD CONSTRAINT `contestant_category_ibfk_1` FOREIGN KEY (`contestant_id`) REFERENCES `contestants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contestant_category_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `criteria`
--
ALTER TABLE `criteria`
  ADD CONSTRAINT `criteria_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `judge_category`
--
ALTER TABLE `judge_category`
  ADD CONSTRAINT `judge_category_ibfk_1` FOREIGN KEY (`judge_id`) REFERENCES `judges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `judge_category_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `scores`
--
ALTER TABLE `scores`
  ADD CONSTRAINT `scores_ibfk_1` FOREIGN KEY (`judge_id`) REFERENCES `judges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scores_ibfk_2` FOREIGN KEY (`contestant_id`) REFERENCES `contestants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scores_ibfk_3` FOREIGN KEY (`criterion_id`) REFERENCES `criteria` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
