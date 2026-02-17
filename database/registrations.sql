-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 16, 2026 at 08:11 PM
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
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `event_name`, `category`, `team_name`, `college`, `lead_name`, `lead_email`, `lead_phone`, `additional_members`, `total_fee`, `registration_date`) VALUES
(1, 'cad', 'General', '', 'Sanjivani College of Engineering', 'Nayan Gawali', 'gawalinayan@gmail.com', '09960583746', '', 199, '2026-02-05 05:48:55'),
(2, 'expo', 'General', 'gagaan', 'Sanjivani College of Engineering', 'Nayan Gawali', 'v@gmail.com', '09960583746', 'aditya, om, ritesh', 796, '2026-02-05 05:56:17'),
(3, 'expo', 'General', 'nova', 'Sanjivani College of Engineering', 'om', 'DO@Gmail.com', '09960583746', 'gagan, nayan, utkarsh', 796, '2026-02-05 06:08:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
