-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 05, 2026 at 01:39 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `event_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) DEFAULT NULL,
  `sem` varchar(50) DEFAULT NULL,
  `event_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `event_description` text,
  `event_start_date` date NOT NULL,
  `event_end_date` date NOT NULL,
  `resource_person` varchar(100) DEFAULT NULL,
  `resource_link` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `remuneration` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `event_type`, `sem`, `event_name`, `event_description`, `event_start_date`, `event_end_date`, `resource_person`, `resource_link`, `file_path`, `photo_path`, `created_at`, `remuneration`) VALUES
(29, 'Skill Development', 'Semester 5', 'Skill Development Program On Full stack Web Development.', 'Skill Development Program On Full stack Web Development.', '2023-11-07', '2023-11-09', 'IVIS Labs Private Limited', NULL, '', '', '2025-11-27 14:50:23', 15000.00),
(28, 'Skill Development', 'Semester 5', 'Skill Development Program on mobile application Development.', 'Skill Development Program on mobile application Development.', '2023-11-07', '2023-11-08', 'Ravi Ramesh Babu Kurapatti', NULL, '', '', '2025-11-27 14:45:31', 7500.00),
(24, 'Seminar', 'Semester 3', 'Seminar on Elevate your reality:An introdction to AR/VR', 'Seminar on Elevate your reality:An introdction to AR/VR\r\n', '2023-12-05', '2023-12-05', 'Prof.Thotreingam Kasar,of Edspire Research Center', '', '', '', '2025-09-25 07:41:11', 0.00),
(25, 'Seminar', 'Semester 5', 'Seminar on Elevate your reality:An introdction to AR/VR', 'Seminar on Elevate your reality:An introdction to AR/VR\r\n', '2023-12-05', '2023-12-05', 'Prof.Thotreingam Kasar,of Edspire Research Center', '', '', '', '2025-09-25 07:44:42', 3000.00),
(26, 'Seminar', 'Semester 3', 'Workshop On Oops with Java ', '', '2023-01-11', '2023-01-13', '\"DALWIK Apps,Mumbai, \"', NULL, '', '', '2025-09-26 05:03:33', 41400.00),
(30, 'Workshop', 'Semester 5', 'Workshop On Database management system', 'Workshop On Database management system', '2024-02-09', '2024-02-10', 'Mr. Manjunath S.C, Industry Expert ', NULL, '', '', '2025-11-27 14:52:29', 30000.00),
(31, 'Industrial vist', 'Semester 5', 'industrial visit for L & W India ,Banglore', 'industrial visit for L & W India ,Banglore', '2024-02-29', '2024-02-29', '', NULL, '', '', '2025-11-27 14:56:00', 20163.00),
(32, 'Industrial vist', 'Semester 4', 'industrial visit for CSIR-4PI, Bangalore', 'industrial visit for CSIR-4PI, Bangalore', '2024-05-13', '2024-05-13', '', NULL, '', '', '2025-11-27 14:58:56', 19372.00),
(33, 'Workshop', 'Semester 4', 'Workshop On Industrial Training Programming on MERN', 'Workshop On Industrial Training Programming on MERN', '2024-06-06', '2024-06-08', '', NULL, '', '', '2025-11-27 15:01:39', 56950.00),
(34, 'Workshop', 'Semester 4', 'Workshop On Machine Learning using python', 'Workshop On Machine Learning using python', '2023-08-29', '2023-08-31', 'Karunadu technologies Pvt Ltd', NULL, '', '', '2025-11-27 15:04:41', 24800.00),
(35, 'Workshop', 'Semester 4', 'Workshop On Clean Coding using Python', 'Workshop On Clean Coding using Python', '2023-09-30', '2023-09-30', 'Thoughtworks Connected', NULL, '', '', '2025-11-27 15:07:06', 4886.00),
(36, 'Workshop', 'Semester 3', 'LinkedIn workshop', 'LinkedIn workshop', '2024-10-23', '2024-10-24', 'Linkdin', NULL, '', '', '2025-11-27 15:11:37', 5520.00),
(37, 'project program', 'Semester 4', 'KSCST project program', 'KSCST project program', '0001-01-01', '0001-01-01', '', NULL, '', '', '2025-11-27 15:15:25', 8260.00),
(38, 'Technical-Events', 'All Semesters', '“AI -VERSE” ', 'Talk on “Essentials of Maths in AI & ML” -A mini-project presentation by the students of Dept os CSE(AI&ML). ', '2023-08-29', '2023-08-29', 'Dr. P. M Shivamurthy-Data Scientist, iNDx.ai , California ', NULL, '', 'uploads/photos/1765164037_e772f306.png', '2025-12-08 03:20:37', 0.00),
(39, 'Tech-talk', 'All Semesters', '“Demystifying AI and Generative AI” ', 'Technical talk on “Demystifying AI and Generative AI” ', '2023-07-22', '2023-07-22', 'Dr. Naveen Patel,  Convenor, CII, Associate Vice President, Infosys Limited, ', NULL, '', 'uploads/photos/1765164373_7c2d680d.png', '2025-12-08 03:26:13', 0.00),
(40, 'open day', 'Semester 3', 'Open Day- A Project Exhibition', 'Open Day- A Project Exhibition” on 10th March 2023 by III Sem students as an outcome of Skill Lab Training, ', '2023-03-10', '2023-03-10', 'Dr.Manjuprasad.B', NULL, '', 'uploads/photos/1765164842_0eede95f.png', '2025-12-08 03:34:02', 0.00),
(41, 'Workshop', 'All Semesters', '“Clean Code Workshop ', 'Workshop on “Clean Code Workshop - Python” ', '2023-09-30', '2023-09-30', 'association with ThoughtWorks ConnectEd ', NULL, '', 'uploads/photos/1765164985_31a44a57.png', '2025-12-08 03:36:25', 0.00),
(42, 'awareness-program', 'Semester 1', 'Demonstration and Awareness Session of HP Servers ', 'Demonstration and Awareness Session of HP Servers ', '2023-11-17', '2023-11-17', 'Dr. Manjuprasad B, HoD, CSE(AI&ML) ', NULL, '', 'uploads/photos/1765165336_16fdf2c4.png', '2025-12-08 03:42:16', 0.00),
(43, 'Tech-talk', 'Semester 3', '“Introduction to IoT” ', 'Techtalk on “Introduction to IoT” on 20th December 2023  for III semester students . Dr. Manjuprasad B, HoD, CSE(AI&ML) was the resource person\r\n', '2023-12-20', '2023-12-20', 'Dr. Manjuprasad B, HoD, CSE(AI&ML) ', NULL, '', 'uploads/photos/1765166614_7169abc9.png', '2025-12-08 04:03:34', 0.00),
(45, 'Skill Development', 'Semester 5', 'Skill Development Program On Full stack Web Development.', 'Skill Development Program On Full stack Web Development.', '0000-00-00', '0000-00-00', 'IVIS Labs Private Limited', NULL, '', '', '0000-00-00 00:00:00', 15000.00),
(46, 'Skill Development', 'Semester 5', 'Skill Development Program on mobile application Development.', 'Skill Development Program on mobile application Development.', '0000-00-00', '0000-00-00', 'Ravi Ramesh Babu Kurapatti', NULL, '', '', '0000-00-00 00:00:00', 7500.00),
(47, 'Seminar', 'Semester 3', 'Seminar on Elevate your reality:An introdction to AR/VR', 'Seminar on Elevate your reality:An introdction to AR/VR\r\n', '0000-00-00', '0000-00-00', 'Prof.Thotreingam Kasar,of Edspire Research Center', '', '', '', '0000-00-00 00:00:00', 0.00),
(48, 'Seminar', 'Semester 5', 'Seminar on Elevate your reality:An introdction to AR/VR', 'Seminar on Elevate your reality:An introdction to AR/VR\r\n', '0000-00-00', '0000-00-00', 'Prof.Thotreingam Kasar,of Edspire Research Center', '', '', '', '0000-00-00 00:00:00', 3000.00),
(49, 'Seminar', 'Semester 3', 'Workshop On Oops with Java ', '', '0000-00-00', '0000-00-00', '\"DALWIK Apps,Mumbai, \"', NULL, '', '', '0000-00-00 00:00:00', 41400.00),
(50, 'Workshop', 'Semester 5', 'Workshop On Database management system', 'Workshop On Database management system', '0000-00-00', '0000-00-00', 'Mr. Manjunath S.C, Industry Expert ', NULL, '', '', '0000-00-00 00:00:00', 30000.00),
(51, 'Industrial vist', 'Semester 5', 'industrial visit for L & W India ,Banglore', 'industrial visit for L & W India ,Banglore', '0000-00-00', '0000-00-00', '', NULL, '', '', '0000-00-00 00:00:00', 20163.00),
(52, 'Industrial vist', 'Semester 4', 'industrial visit for CSIR-4PI, Bangalore', 'industrial visit for CSIR-4PI, Bangalore', '0000-00-00', '0000-00-00', '', NULL, '', '', '0000-00-00 00:00:00', 19372.00),
(53, 'Workshop', 'Semester 4', 'Workshop On Industrial Training Programming on MERN', 'Workshop On Industrial Training Programming on MERN', '0000-00-00', '0000-00-00', '', NULL, '', '', '0000-00-00 00:00:00', 56950.00),
(54, 'Workshop', 'Semester 4', 'Workshop On Machine Learning using python', 'Workshop On Machine Learning using python', '0000-00-00', '0000-00-00', 'Karunadu technologies Pvt Ltd', NULL, '', '', '0000-00-00 00:00:00', 24800.00),
(55, 'Workshop', 'Semester 4', 'Workshop On Clean Coding using Python', 'Workshop On Clean Coding using Python', '0000-00-00', '0000-00-00', 'Thoughtworks Connected', NULL, '', '', '0000-00-00 00:00:00', 4886.00),
(56, 'Workshop', 'Semester 3', 'LinkedIn workshop', 'LinkedIn workshop', '0000-00-00', '0000-00-00', 'Linkdin', NULL, '', '', '0000-00-00 00:00:00', 5520.00),
(57, 'project program', 'Semester 4', 'KSCST project program', 'KSCST project program', '0001-01-01', '0001-01-01', '', NULL, '', 'uploads/photos/1.png', '0000-00-00 00:00:00', 8260.00),
(58, 'Technical-Events', 'All Semesters', '“AI -VERSE” ', 'Talk on “Essentials of Maths in AI & ML” -A mini-project presentation by the students of Dept os CSE(AI&ML). ', '0000-00-00', '0000-00-00', 'Dr. P. M Shivamurthy-Data Scientist, iNDx.ai , California ', NULL, '', 'uploads/photos/1.png', '0000-00-00 00:00:00', 0.00),
(59, 'Tech-talk', 'All Semesters', '“Demystifying AI and Generative AI” ', 'Technical talk on “Demystifying AI and Generative AI” ', '0000-00-00', '0000-00-00', 'Dr. Naveen Patel,  Convenor, CII, Associate Vice President, Infosys Limited, ', NULL, '', 'uploads/photos/1.png', '0000-00-00 00:00:00', 0.00),
(60, 'open day', 'Semester 3', 'Open Day- A Project Exhibition', 'Open Day- A Project Exhibition” on 10th March 2023 by III Sem students as an outcome of Skill Lab Training, ', '0000-00-00', '0000-00-00', 'Dr.Manjuprasad.B', NULL, '', 'uploads/photos/1.png', '0000-00-00 00:00:00', 0.00),
(61, 'Workshop', 'All Semesters', '“Clean Code Workshop ', 'Workshop on “Clean Code Workshop - Python” ', '0000-00-00', '0000-00-00', 'association with ThoughtWorks ConnectEd ', NULL, '', 'uploads/photos/1.png', '0000-00-00 00:00:00', 0.00),
(62, 'awareness-program', 'Semester 1', 'Demonstration and Awareness Session of HP Servers ', 'Demonstration and Awareness Session of HP Servers ', '0000-00-00', '0000-00-00', 'Dr. Manjuprasad B, HoD, CSE(AI&ML) ', NULL, '', 'uploads/photos/1.png', '0000-00-00 00:00:00', 0.00),
(63, 'Tech-talk', 'Semester 3', '“Introduction to IoT” ', 'Techtalk on “Introduction to IoT” on 20th December 2023  for III semester students . Dr. Manjuprasad B, HoD, CSE(AI&ML) was the resource person\r\n', '0000-00-00', '0000-00-00', 'Dr. Manjuprasad B, HoD, CSE(AI&ML) ', NULL, '', 'uploads/photos/1.png', '0000-00-00 00:00:00', 0.00),
(64, 'open day', 'Semester 5', 'Open Day- A Project Exhibition', 'presented to students of 3rd sem etc....', '2025-12-10', '2025-12-10', 'xyz', NULL, 'uploads/files/1765342488_0a4fe405.docx', '', '2025-12-10 04:54:48', 5000.00);

-- --------------------------------------------------------

--
-- Table structure for table `event_types`
--

DROP TABLE IF EXISTS `event_types`;
CREATE TABLE IF NOT EXISTS `event_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `event_types`
--

INSERT INTO `event_types` (`id`, `type_name`) VALUES
(1, 'Seminar'),
(9, 'Tech-talk'),
(3, 'Hackathon'),
(4, 'Webinar'),
(5, 'Google Cloud Club'),
(6, 'Workshop'),
(7, 'Industrial vist'),
(8, 'Skill Development'),
(11, 'project program'),
(12, 'Technical-Events'),
(13, 'open day'),
(14, 'awareness-program');

-- --------------------------------------------------------

--
-- Table structure for table `resource_info`
--

DROP TABLE IF EXISTS `resource_info`;
CREATE TABLE IF NOT EXISTS `resource_info` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `email_id` varchar(255) DEFAULT NULL,
  `profile_link` varchar(500) DEFAULT NULL,
  `payment` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `resource_info`
--

INSERT INTO `resource_info` (`id`, `name`, `phone_number`, `company`, `designation`, `email_id`, `profile_link`, `payment`, `created_at`) VALUES
(11, 'Dr. P. M Shivamurthy-Data Scientist, iNDx.ai , California ', NULL, NULL, NULL, NULL, NULL, 0.00, '2025-12-08 03:20:37'),
(5, 'Ravi Ramesh Babu Kurapatti', NULL, NULL, NULL, NULL, NULL, 7500.00, '2025-11-27 14:45:31'),
(12, 'Dr. Naveen Patel,  Convenor, CII, Associate Vice President, Infosys Limited, ', NULL, NULL, NULL, NULL, NULL, 0.00, '2025-12-08 03:26:13'),
(6, 'IVIS Labs Private Limited', NULL, NULL, NULL, NULL, NULL, 15000.00, '2025-11-27 14:50:23'),
(7, 'Mr. Manjunath S.C, Industry Expert ', NULL, NULL, NULL, NULL, NULL, 30000.00, '2025-11-27 14:52:29'),
(8, 'Karunadu technologies Pvt Ltd', NULL, NULL, NULL, NULL, NULL, 24800.00, '2025-11-27 15:04:41'),
(9, 'Thoughtworks Connected', NULL, NULL, NULL, NULL, NULL, 4886.00, '2025-11-27 15:07:06'),
(10, 'Linkdin', NULL, NULL, NULL, NULL, NULL, 5520.00, '2025-11-27 15:11:37'),
(13, 'Dr.Manjuprasad.B', NULL, NULL, NULL, NULL, NULL, 0.00, '2025-12-08 03:34:02'),
(14, 'association with ThoughtWorks ConnectEd ', NULL, NULL, NULL, NULL, NULL, 0.00, '2025-12-08 03:36:25'),
(15, 'Dr. Manjuprasad B, HoD, CSE(AI&ML) ', NULL, NULL, NULL, NULL, NULL, 0.00, '2025-12-08 03:42:16'),
(16, 'xyz', NULL, NULL, NULL, NULL, NULL, 5000.00, '2025-12-10 04:54:48');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
