-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 17, 2026 at 10:08 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `techfest_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `queries`
--

CREATE TABLE `queries` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `event_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `team_name` varchar(100) NOT NULL,
  `college` varchar(255) DEFAULT 'Sanjivani College of Engineering',
  `lead_name` varchar(100) NOT NULL,
  `lead_email` varchar(100) NOT NULL,
  `lead_phone` varchar(20) NOT NULL,
  `additional_members` text DEFAULT NULL,
  `total_fee` int(11) DEFAULT NULL,
  `payment_status` varchar(20) DEFAULT 'Pending',
  `transaction_proof` varchar(255) DEFAULT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `event_name`, `category`, `team_name`, `college`, `lead_name`, `lead_email`, `lead_phone`, `additional_members`, `total_fee`, `payment_status`, `transaction_proof`, `registration_date`) VALUES
(1, 'cad', 'General', '', 'Sanjivani College of Engineering', 'Nayan Gawali', 'gawalinayan@gmail.com', '09960583746', '', 199, 'Pending', NULL, '2026-02-05 05:48:55'),
(2, 'expo', 'General', 'gagaan', 'Sanjivani College of Engineering', 'Nayan Gawali', 'v@gmail.com', '09960583746', 'aditya, om, ritesh', 796, 'Pending', NULL, '2026-02-05 05:56:17'),
(3, 'expo', 'General', 'nova', 'Sanjivani College of Engineering', 'om', 'DO@Gmail.com', '09960583746', 'gagan, nayan, utkarsh', 796, 'Pending', NULL, '2026-02-05 06:08:54'),
(4, 'code', 'General', '', 'iuiiij', 'yash jaggu', 'hodjjd@gmail.com', '9238982711', '', 199, 'Pending', NULL, '2026-02-16 19:48:10'),
(5, 'cad', 'General', '', 'Sanjivani College of Engineeringy', 'Nayan Gawali', 'gawalinayan89@gmail.com', '09960583746', '', 199, 'Pending', NULL, '2026-02-16 20:16:14'),
(6, 'expo', 'General', 'gagaan', 'Sanjivani College of Engineering', 'Nayan Gawali', 'gawalinayan89@gmail.com', '9960583746', 'hhh', 398, 'Pending', NULL, '2026-02-16 21:07:32'),
(7, 'tower', 'General', 'gagaan', 'Sanjivani College of Engineering', ' Gawali', 'gawalinayan89@gmail.com', '09960583746', '', 199, 'Pending', NULL, '2026-02-16 21:20:32'),
(8, 'robo', 'General', 'xxsa', 'Sanjivani College of Engineering', 'rakesh', 'gawalinayan89@gmail.com', '9960583746', '', 199, 'Pending', NULL, '2026-02-16 21:25:15'),
(9, 'expo', 'General', 'ritesh', 'Sanjivani College of Engineeringy', 'athrava hon', 'gawalinayan89@gmail.com', '9175811770', 'chaitanya, onkar', 597, 'Verified', NULL, '2026-02-17 06:41:19');

-- --------------------------------------------------------

--
-- Table structure for table `stay_requests`
--

CREATE TABLE `stay_requests` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `college_name` varchar(255) NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `number_of_nights` int(11) DEFAULT NULL,
  `check_in_date` date DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `special_requirements` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `queries`
--
ALTER TABLE `queries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stay_requests`
--
ALTER TABLE `stay_requests`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `queries`
--
ALTER TABLE `queries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `stay_requests`
--
ALTER TABLE `stay_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
