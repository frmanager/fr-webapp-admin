-- phpMyAdmin SQL Dump
-- version 4.6.2
-- https://www.phpmyadmin.net/
--
-- Host: mysql.lrespto.org
-- Generation Time: Sep 26, 2016 at 08:25 PM
-- Server version: 5.6.25-log
-- PHP Version: 7.0.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lrespto_funrun`
--

--
-- Dumping data for table `campaignawardstyle`
--

INSERT INTO `campaignawardstyle` (`id`, `display_name`, `value`, `description`) VALUES
(1, 'Rank', 'place', ''),
(2, 'Donation Level', 'level', 'award received if (Teacher/Student) reach donation amount');

--
-- Dumping data for table `campaignawardtype`
--

INSERT INTO `campaignawardtype` (`id`, `display_name`, `value`, `description`) VALUES
(1, 'Teacher/Class', 'teacher', ''),
(2, 'Student/Individual', 'student', '');
--
-- Dumping data for table `campaignaward`
--

INSERT INTO `campaignaward` (`id`, `campaignawardtype_id`, `campaignawardstyle_id`, `name`, `place`, `amount`, `description`) VALUES
(1, 1, 2, '10 Minutes Extra Recess', NULL, 50, NULL),
(2, 1, 2, 'Crazy Day', NULL, 150, 'Silly socks, backwards clothes, funny hats'),
(3, 1, 2, 'Popsicles', NULL, 300, 'Popsicles'),
(4, 1, 2, 'Wear Favorite Sports Team', NULL, 400, NULL),
(5, 1, 2, 'No Homework Pass', NULL, 500, NULL),
(6, 1, 2, 'PJ Day', NULL, 600, NULL),
(7, 1, 2, 'Crazy Hair Day', NULL, 700, NULL),
(8, 1, 2, '20 Minutes Extra Recess', NULL, 800, NULL),
(9, 1, 2, 'Picnic Lunch Outside', NULL, 900, NULL),
(10, 1, 2, 'Movie Matinee w/Popcorn', NULL, 1000, NULL),
(11, 1, 1, 'Private reptile show', 1, NULL, NULL),
(12, 1, 1, 'Private Art Class', 2, NULL, 'From the Edgmoore Art Studio'),
(13, 1, 1, 'Cupcake Party', 3, NULL, 'From Confectios'),
(14, 2, 1, 'Kindle Tablet', 1, NULL, NULL);

--
-- Dumping data for table `campaignsetting`
--

INSERT INTO `campaignsetting` (`id`, `display_name`, `value`, `description`, `format`) VALUES
(1, 'campaign_start_date', '9/15/2016', NULL, 'mm/dd/yyyy'),
(2, 'campaign_end_date', '10/27/2016', NULL, 'mm/dd/yyyy'),
(3, 'campaign_funding_goal', '20000', NULL, 'an Amount, no commas!'),
(4, 'campaign_url', 'http://funrun.lrespto.org', NULL, 'FQDN');

--
-- Dumping data for table `grade`
--

INSERT INTO `grade` (`id`, `name`) VALUES
(11, '1st Grade'),
(12, '2nd Grade'),
(13, '3rd Grade'),
(14, '4th Grade'),
(15, '5th Grade'),
(17, 'ED'),
(16, 'ID'),
(10, 'Kindergarten');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
