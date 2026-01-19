-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 19, 2026 at 07:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `trackback_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `user_id`, `action_type`, `action_details`, `ip_address`, `created_at`) VALUES
(51, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 18:16:52'),
(52, 1, 'LOGIN', '\"User logged in\"', '::1', '2026-01-18 18:50:52'),
(53, 14, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 18:54:49'),
(54, 14, 'REMOVE_WARNING', '{\"warning_id\":2}', '::1', '2026-01-18 18:55:16'),
(55, 14, 'REMOVE_WARNING', '{\"warning_id\":2}', '::1', '2026-01-18 18:59:40'),
(56, 14, 'REMOVE_WARNING', '{\"warning_id\":2}', '::1', '2026-01-18 19:01:46'),
(57, 14, 'BAN_POST', '{\"post_id\":2}', '::1', '2026-01-18 19:01:56'),
(58, 14, 'BAN_POST', '{\"post_id\":2}', '::1', '2026-01-18 19:04:38'),
(59, 14, 'REMOVE_WARNING', '{\"warning_id\":2}', '::1', '2026-01-18 19:04:46'),
(60, 14, 'REMOVE_WARNING', '{\"warning_id\":2}', '::1', '2026-01-18 19:04:54'),
(61, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 19:06:04'),
(62, 1, 'APPROVE_POST', '{\"post_id\":2}', '::1', '2026-01-18 19:06:09'),
(63, 14, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 19:06:37'),
(64, 14, 'REMOVE_WARNING', '{\"warning_id\":2}', '::1', '2026-01-18 19:07:04'),
(65, 14, 'BAN_POST', '{\"post_id\":2}', '::1', '2026-01-18 19:09:40'),
(66, 14, 'REMOVE_WARNING', '{\"warning_id\":2}', '::1', '2026-01-18 19:23:44'),
(67, 14, 'REMOVE_WARNING', '{\"warning_id\":2}', '::1', '2026-01-18 19:23:55'),
(68, 14, 'FLAG_POST', '{\"post_id\":1}', '::1', '2026-01-18 19:25:36'),
(69, 14, 'BAN_POST', '{\"post_id\":1}', '::1', '2026-01-18 19:25:42'),
(70, 14, 'ISSUE_WARNING', '{\"user_id\":12}', '::1', '2026-01-18 19:26:34'),
(71, 14, 'REMOVE_WARNING', '{\"warning_id\":3}', '::1', '2026-01-18 19:28:25'),
(72, 14, 'REMOVE_WARNING', '{\"warning_id\":2}', '::1', '2026-01-18 19:28:29'),
(73, 14, 'REMOVE_WARNING', '{\"warning_id\":2}', '::1', '2026-01-18 19:30:56'),
(74, 14, 'REMOVE_WARNING', '{\"warning_id\":2}', '::1', '2026-01-18 19:31:08'),
(75, 14, 'REMOVE_WARNING', '{\"warning_id\":2}', '::1', '2026-01-18 19:31:17'),
(76, 14, 'UNFLAG_POST', '{\"post_id\":2}', '::1', '2026-01-18 19:34:35'),
(77, 14, 'UNFLAG_POST', '{\"post_id\":1}', '::1', '2026-01-18 19:34:38'),
(78, 14, 'FLAG_POST', '{\"post_id\":2}', '::1', '2026-01-18 19:34:52'),
(79, 14, 'UNFLAG_POST', '{\"post_id\":2}', '::1', '2026-01-18 19:35:02'),
(80, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 19:37:21'),
(81, 1, 'APPOINT_MODERATOR', '{\"user_id\":13}', '::1', '2026-01-18 19:37:29'),
(82, 13, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 19:37:48'),
(83, 13, 'FLAG_POST', '{\"post_id\":2}', '::1', '2026-01-18 19:42:10'),
(84, 13, 'UNFLAG_POST', '{\"post_id\":2}', '::1', '2026-01-18 19:43:05'),
(85, 13, 'FLAG_POST', '{\"post_id\":2}', '::1', '2026-01-18 19:47:59'),
(86, 13, 'UNFLAG_POST', '{\"post_id\":2}', '::1', '2026-01-18 19:50:40'),
(87, 13, 'ISSUE_WARNING', '{\"user_id\":12}', '::1', '2026-01-18 19:55:49'),
(88, 13, 'FLAG_POST', '{\"post_id\":2}', '::1', '2026-01-18 19:55:59'),
(89, 13, 'UNFLAG_POST', '{\"post_id\":2}', '::1', '2026-01-18 19:57:01'),
(90, 13, 'REMOVE_WARNING', '{\"warning_id\":4}', '::1', '2026-01-18 20:00:42'),
(91, 13, 'BAN_POST', '{\"post_id\":2}', '::1', '2026-01-18 20:02:35'),
(92, 13, 'BAN_POST', '{\"post_id\":2}', '::1', '2026-01-18 20:04:03'),
(93, 13, 'FLAG_POST', '{\"post_id\":2}', '::1', '2026-01-18 20:09:25'),
(94, 13, 'UNFLAG_POST', '{\"post_id\":2}', '::1', '2026-01-18 20:09:29'),
(95, 13, 'FLAG_POST', '{\"post_id\":2}', '::1', '2026-01-18 20:09:51'),
(96, 13, 'BAN_POST', '{\"post_id\":2}', '::1', '2026-01-18 20:09:58'),
(97, 13, 'UNFLAG_POST', '{\"post_id\":2}', '::1', '2026-01-18 20:11:47'),
(98, 13, 'FLAG_POST', '{\"post_id\":2}', '::1', '2026-01-18 20:23:29'),
(99, 13, 'UNFLAG_POST', '{\"post_id\":2}', '::1', '2026-01-18 20:23:33'),
(100, 13, 'FLAG_POST', '{\"post_id\":1}', '::1', '2026-01-18 20:23:45'),
(101, 13, 'UNFLAG_POST', '{\"post_id\":1}', '::1', '2026-01-18 20:23:49'),
(102, 12, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 20:28:34'),
(103, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 20:56:25'),
(104, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:00:41'),
(105, 12, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 21:24:17'),
(106, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:24:34'),
(107, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:26:03'),
(108, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:26:11'),
(109, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:28:56'),
(110, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:31:19'),
(111, 12, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 21:32:45'),
(112, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:32:55'),
(113, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:35:44'),
(114, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:38:11'),
(115, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:40:11'),
(116, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:40:20'),
(117, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:41:49'),
(118, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:42:07'),
(119, 12, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 21:43:45'),
(120, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:43:51'),
(121, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:44:53'),
(122, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:48:45'),
(123, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:49:44'),
(124, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:49:53'),
(125, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:51:40'),
(126, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:55:19'),
(127, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:56:36'),
(128, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:58:18'),
(129, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:58:22'),
(130, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:58:38'),
(131, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 21:58:46'),
(132, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 22:45:40'),
(133, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 23:02:44'),
(134, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 23:07:57'),
(135, 12, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 23:12:09'),
(136, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 23:12:24'),
(137, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 23:15:18'),
(138, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 23:17:01'),
(139, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 23:18:05'),
(140, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 23:18:13'),
(141, 12, 'UPDATE_PROFILE', '[]', '::1', '2026-01-18 23:18:32'),
(142, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 23:19:28'),
(143, 13, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 23:55:57'),
(144, 13, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 23:57:06'),
(145, 12, 'LOGIN', 'User logged in via API', '::1', '2026-01-18 23:58:59'),
(146, 13, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 00:00:18'),
(147, 13, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 00:05:42'),
(148, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 00:06:23'),
(149, 12, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 00:19:36'),
(150, 13, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 00:20:02'),
(151, 13, 'FLAG_POST', '{\"post_id\":2}', '::1', '2026-01-19 00:20:25'),
(152, 12, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 00:20:46'),
(153, 13, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 00:21:16'),
(154, 13, 'UNFLAG_POST', '{\"post_id\":2}', '::1', '2026-01-19 00:21:22'),
(155, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 07:38:05'),
(156, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 07:38:33'),
(157, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 08:47:49'),
(158, 1, 'BAN_USER', '{\"target_user_id\":12,\"reason\":\"Not specified\"}', '::1', '2026-01-19 08:48:03'),
(159, 1, 'APPROVE_USER', '{\"target_user_id\":12}', '::1', '2026-01-19 08:48:13'),
(160, 1, 'BAN_POST', '{\"post_id\":2,\"reason\":\"Not specified\"}', '::1', '2026-01-19 08:48:26'),
(161, 1, 'APPROVE_POST', '{\"post_id\":2}', '::1', '2026-01-19 08:48:41'),
(162, 1, 'REMOVE_MODERATOR', '{\"user_id\":13}', '::1', '2026-01-19 08:49:14'),
(163, 1, 'CREATE_USER', '{\"new_user_id\":{\"success\":true,\"user_id\":15}}', '::1', '2026-01-19 08:56:14'),
(164, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 08:57:28'),
(165, 1, 'APPROVE_USER', '{\"target_user_id\":15}', '::1', '2026-01-19 08:57:35'),
(166, 15, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 08:58:00'),
(167, 15, 'UPDATE_PROFILE', '[]', '::1', '2026-01-19 08:58:33'),
(168, 15, 'UPDATE_PROFILE', '[]', '::1', '2026-01-19 08:58:33'),
(169, 15, 'CREATE_POST', '{\"post_id\":3,\"type\":\"lost\"}', '::1', '2026-01-19 09:01:29'),
(170, 15, 'CREATE_POST', '{\"post_id\":4,\"type\":\"found\"}', '::1', '2026-01-19 09:03:31'),
(171, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:03:46'),
(172, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:04:11'),
(173, 1, 'APPROVE_POST', '{\"post_id\":4}', '::1', '2026-01-19 09:04:20'),
(174, 1, 'APPROVE_POST', '{\"post_id\":3}', '::1', '2026-01-19 09:04:24'),
(175, 1, 'CREATE_USER', '{\"new_user_id\":{\"success\":true,\"user_id\":16}}', '::1', '2026-01-19 09:05:41'),
(176, 1, 'CREATE_USER', '{\"new_user_id\":{\"success\":true,\"user_id\":17}}', '::1', '2026-01-19 09:06:35'),
(177, 1, 'APPROVE_USER', '{\"target_user_id\":17}', '::1', '2026-01-19 09:07:09'),
(178, 1, 'APPROVE_USER', '{\"target_user_id\":16}', '::1', '2026-01-19 09:07:12'),
(179, 16, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:08:04'),
(180, 17, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:09:32'),
(181, 17, 'UPDATE_PROFILE', '[]', '::1', '2026-01-19 09:11:54'),
(182, 17, 'UPDATE_PROFILE', '[]', '::1', '2026-01-19 09:11:54'),
(183, 15, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:12:18'),
(184, 15, 'UPDATE_PROFILE', '[]', '::1', '2026-01-19 09:12:26'),
(185, 15, 'UPDATE_PROFILE', '[]', '::1', '2026-01-19 09:12:26'),
(186, 17, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:12:47'),
(187, 15, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:13:16'),
(188, 15, 'UPDATE_PROFILE', '[]', '::1', '2026-01-19 09:13:24'),
(189, 15, 'UPDATE_PROFILE', '[]', '::1', '2026-01-19 09:13:24'),
(190, 17, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:14:19'),
(191, 17, 'SEND_MESSAGE', '{\"receiver_id\":15}', '::1', '2026-01-19 09:14:37'),
(192, 16, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:15:08'),
(193, 16, 'ISSUE_WARNING', '{\"user_id\":15}', '::1', '2026-01-19 09:16:17'),
(194, 16, 'FLAG_POST', '{\"post_id\":4}', '::1', '2026-01-19 09:16:27'),
(195, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:16:38'),
(196, 15, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:17:38'),
(197, 15, 'SEND_MESSAGE', '{\"receiver_id\":17}', '::1', '2026-01-19 09:18:32'),
(198, 16, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:19:21'),
(199, 16, 'UNFLAG_POST', '{\"post_id\":4}', '::1', '2026-01-19 09:19:33'),
(200, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:20:36'),
(201, 1, 'BAN_USER', '{\"target_user_id\":15,\"reason\":\"Not specified\"}', '::1', '2026-01-19 09:20:49'),
(202, 17, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:21:17'),
(203, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:22:21'),
(204, 1, 'APPROVE_USER', '{\"target_user_id\":15}', '::1', '2026-01-19 09:22:27'),
(205, 15, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:22:40'),
(206, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:26:32'),
(207, 16, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:29:16'),
(208, 15, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:30:51'),
(209, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:33:06'),
(210, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:33:17'),
(211, 1, 'UPDATE_PROFILE', '[]', '::1', '2026-01-19 09:34:18'),
(212, 1, 'UPDATE_PROFILE', '[]', '::1', '2026-01-19 09:34:18'),
(213, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:34:41'),
(214, 1, 'UPDATE_PROFILE', '[]', '::1', '2026-01-19 09:36:31'),
(215, 16, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 09:37:20'),
(216, 15, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 12:46:01'),
(217, 15, 'CREATE_POST', '{\"post_id\":5,\"type\":\"lost\"}', '::1', '2026-01-19 12:49:31'),
(218, 15, 'CREATE_POST', '{\"post_id\":6,\"type\":\"lost\"}', '::1', '2026-01-19 12:53:21'),
(219, 15, 'CREATE_POST', '{\"post_id\":7,\"type\":\"found\"}', '::1', '2026-01-19 12:54:51'),
(220, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 12:55:08'),
(221, 1, 'APPROVE_USER', '{\"target_user_id\":20}', '::1', '2026-01-19 12:55:24'),
(222, 1, 'APPROVE_USER', '{\"target_user_id\":19}', '::1', '2026-01-19 12:55:28'),
(223, 1, 'APPROVE_USER', '{\"target_user_id\":18}', '::1', '2026-01-19 12:55:33'),
(224, 1, 'APPROVE_POST', '{\"post_id\":7}', '::1', '2026-01-19 12:55:40'),
(225, 1, 'APPROVE_POST', '{\"post_id\":6}', '::1', '2026-01-19 12:55:45'),
(226, 1, 'APPROVE_POST', '{\"post_id\":5}', '::1', '2026-01-19 12:55:54'),
(227, 17, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 12:56:26'),
(228, 17, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:01:31'),
(229, 17, 'CREATE_POST', '{\"post_id\":8,\"type\":\"found\"}', '::1', '2026-01-19 13:04:10'),
(230, 17, 'UPDATE_PROFILE', '[]', '::1', '2026-01-19 13:04:30'),
(231, 17, 'UPDATE_PROFILE', '[]', '::1', '2026-01-19 13:04:30'),
(232, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:14:13'),
(233, 1, 'APPROVE_POST', '{\"post_id\":8}', '::1', '2026-01-19 13:14:31'),
(234, 17, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:17:08'),
(235, 17, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:19:27'),
(236, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:20:50'),
(237, 1, 'FLAG_POST', '{\"post_id\":4}', '::1', '2026-01-19 13:34:28'),
(238, 1, 'UNFLAG_POST', '{\"post_id\":4}', '::1', '2026-01-19 13:34:38'),
(239, 1, 'ISSUE_WARNING', '{\"user_id\":15}', '::1', '2026-01-19 13:34:45'),
(240, 15, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:35:14'),
(241, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:36:11'),
(242, 1, 'APPROVE_USER', '{\"target_user_id\":21}', '::1', '2026-01-19 13:36:36'),
(243, 16, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:36:54'),
(244, 16, 'FLAG_POST', '{\"post_id\":3}', '::1', '2026-01-19 13:37:15'),
(245, 16, 'ISSUE_WARNING', '{\"user_id\":15}', '::1', '2026-01-19 13:37:26'),
(246, 15, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:37:53'),
(247, 17, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:42:24'),
(248, 21, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:43:18'),
(249, 21, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:43:54'),
(250, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:44:17'),
(251, 15, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:50:59'),
(252, 21, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:51:25'),
(253, 21, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:51:45'),
(254, 17, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 13:52:10'),
(255, 17, 'DELETE_POST', '{\"post_id\":8}', '::1', '2026-01-19 13:52:21'),
(256, 15, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 14:25:47'),
(257, 15, 'UPDATE_POST', '{\"post_id\":7}', '::1', '2026-01-19 14:32:17'),
(258, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 14:34:27'),
(259, 1, 'APPROVE_USER', '{\"target_user_id\":22}', '::1', '2026-01-19 14:34:34'),
(260, 22, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 14:35:17'),
(261, 22, 'CHANGE_PASSWORD', '[]', '::1', '2026-01-19 14:43:08'),
(262, 22, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 14:43:35'),
(263, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 14:58:34'),
(264, 1, 'LOGIN', 'User logged in', '::1', '2026-01-19 15:03:52'),
(265, 1, 'APPOINT_MODERATOR', '{\"user_id\":21}', '::1', '2026-01-19 15:04:13'),
(266, 1, 'REMOVE_MODERATOR', '{\"user_id\":21}', '::1', '2026-01-19 15:04:18'),
(267, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 15:18:26'),
(268, 1, 'LOGIN', 'User logged in via API', '::1', '2026-01-19 18:24:39'),
(269, 1, 'CREATE_USER', '{\"new_user_id\":{\"success\":true,\"user_id\":23}}', '::1', '2026-01-19 18:25:21');

-- --------------------------------------------------------

--
-- Table structure for table `disputes`
--

CREATE TABLE `disputes` (
  `dispute_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `claimant1_id` int(11) NOT NULL,
  `claimant2_id` int(11) NOT NULL,
  `moderator_id` int(11) DEFAULT NULL,
  `dispute_description` text NOT NULL,
  `dispute_status` enum('open','in_progress','resolved') DEFAULT 'open',
  `resolution` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message_content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `subject`, `message_content`, `is_read`, `sent_at`) VALUES
(17, 17, 15, 'Regarding: Notebook', 'Its mine', 1, '2026-01-19 09:14:37'),
(18, 16, 15, 'Warning Issued - Action Required', 'Dear User,\n\nYou have received a warning from the moderation team.\n\nReason: Inappropriate Content: Provide a proper image\n\nThis is warning #1 on your account. Please ensure you follow the community guidelines to avoid further action.\n\nIf you believe this warning was issued in error, please contact the moderation team.\n\nThank you,\nTrackBack Team', 1, '2026-01-19 09:16:17'),
(20, 15, 17, 'Re: Regarding: Notebook', 'Meet me tomorrow at 2PM at Anex 1', 1, '2026-01-19 09:18:32'),
(23, 16, 15, 'Your post has been flagged', 'Dear User,\n\nYour post \"Silver Apple watch series 11\" has been flagged by a moderator.\n\nReason: Policy Violation\n\nPlease review your post and make necessary corrections. If you believe this was a mistake, you can contact the moderator team.\n\nThank you,\nTrackBack Team', 1, '2026-01-19 13:37:15'),
(24, 16, 15, 'Warning Issued - Action Required', 'Dear User,\n\nYou have received a warning from the moderation team.\n\nReason: Policy Violation\n\nThis is warning #3 on your account. Please ensure you follow the community guidelines to avoid further action.\n\nIf you believe this warning was issued in error, please contact the moderation team.\n\nThank you,\nTrackBack Team', 1, '2026-01-19 13:37:26');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `notification_message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `related_post_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_type` enum('lost','found') NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `item_description` text NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `location` varchar(200) NOT NULL,
  `date_time` datetime NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `post_status` enum('pending','approved','banned','resolved') DEFAULT 'pending',
  `flagged` tinyint(1) DEFAULT 0,
  `flag_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`post_id`, `user_id`, `post_type`, `item_name`, `item_description`, `category`, `location`, `date_time`, `image_url`, `post_status`, `flagged`, `flag_reason`, `created_at`, `updated_at`) VALUES
(3, 15, 'lost', 'Silver Apple watch series 11', 'Lost it at Anex 3 on 17/1/26 after 3 PM', 'electronics', 'Anex 3', '2026-01-17 15:01:00', 'img/posts/696df2e91939c_1768813289.webp', 'approved', 1, NULL, '2026-01-19 09:01:29', '2026-01-19 13:37:15'),
(4, 15, 'found', 'Notebook', 'Black Clover Notebook', 'documents', 'DS0701', '2026-01-18 15:02:00', 'img/posts/696df36322d07_1768813411.jpg', 'approved', 0, NULL, '2026-01-19 09:03:31', '2026-01-19 13:34:38'),
(5, 15, 'lost', 'Apple Airpods pro 2', 'Lost it around anex 5 or 6. Probably between 1-2PM', 'electronics', 'Anex 5, 6', '2026-01-15 18:49:00', 'img/posts/696e285b926f5_1768826971.jpg', 'approved', 0, NULL, '2026-01-19 12:49:31', '2026-01-19 12:55:54'),
(6, 15, 'lost', 'Black Wallet', 'Lost my wallet yesterday after the 12 PM exam. It had my bank cards and around 7k cash.', 'accessories', 'AIUB', '2026-01-18 18:51:00', 'img/posts/696e2941bf772_1768827201.jpg', 'approved', 0, NULL, '2026-01-19 12:53:21', '2026-01-19 12:55:45'),
(7, 15, 'found', 'asdsd', 'asdsad', 'documents', 'asdas', '2026-01-22 20:36:00', NULL, 'approved', 0, NULL, '2026-01-19 12:54:51', '2026-01-19 14:32:17');

-- --------------------------------------------------------

--
-- Table structure for table `post_flags`
--

CREATE TABLE `post_flags` (
  `flag_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `moderator_id` int(11) NOT NULL,
  `flag_reason` text NOT NULL,
  `flagged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_flags`
--

INSERT INTO `post_flags` (`flag_id`, `post_id`, `moderator_id`, `flag_reason`, `flagged_at`) VALUES
(14, 3, 16, 'Policy Violation', '2026-01-19 13:37:15');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('admin','moderator','student') DEFAULT 'student',
  `university` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `account_status` enum('active','banned','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `student_id`, `name`, `email`, `password_hash`, `user_type`, `university`, `phone`, `profile_picture`, `account_status`, `created_at`, `updated_at`) VALUES
(1, 'ADMIN001', 'Administrator Shamiul', 'admin@trackback.com', '$2y$12$NpRLHh9yioCwq6GdInTQCuOcIX8LYoY0Nk5na3zBeM7emz1Mp3Pim', 'admin', '', '', NULL, 'banned', '2026-01-17 21:14:12', '2026-01-19 18:25:21'),
(15, 'USR1768812974', 'Mashrafi Ahsan', 'mashrafi@gmail.com', '$2y$12$xBg.lyPy0gCht3qILnwwW.faBi.5FBgq258eh6m7HYEBJhJ6wDUtm', '', 'AIUB', '01999999999', NULL, 'active', '2026-01-19 08:56:14', '2026-01-19 09:22:27'),
(16, 'USR1768813541', 'Sheikh Najmul', 'sheikh@gmail.com', '$2y$12$ui57KSDlVy1xdQqVePGd7O47dw4/Hl0iXNxCqKd3YFgi.z6iw6gca', 'moderator', NULL, NULL, NULL, 'active', '2026-01-19 09:05:41', '2026-01-19 09:07:12'),
(17, 'USR1768813595', 'Syed Iftear', 'syedsurids5@gmail.com', '$2y$12$foKVExEYRy5/5fg2meMkH.qtKhZv33AhwEVKU1kuwSs4I5lS1OQjS', '', 'AIUB', '01964722472', NULL, 'active', '2026-01-19 09:06:35', '2026-01-19 09:11:54'),
(18, '231345', 'Abir Rafi', 'abir@gmail.com', '$2y$12$Pg1TouMr35kX8l3hbtYXau05Bccrzut62bakMXJRpxj9gP2XRsSYO', 'student', 'NSU', NULL, NULL, 'active', '2026-01-19 12:33:33', '2026-01-19 12:55:33'),
(19, '23-55555-1', 'Rifat Kabir', 'rifat@gmail.com', '$2y$12$QOxQw.c7VRTZ6.ZeWtlHN.Gpf1oJ2CReju4JDwOqmU2Vsh.TDzZq2', 'student', 'AIUB', NULL, NULL, 'active', '2026-01-19 12:35:52', '2026-01-19 12:55:28'),
(20, '23-77777-1', 'Tutul Majumder', 'tutul@gmail.com', '$2y$12$eKN0iSngMOf.N83PREhk8e4PZajIi5vSs099Za8172pV5RMsjWwzW', 'student', 'AIUB', NULL, NULL, 'active', '2026-01-19 12:38:47', '2026-01-19 12:55:24'),
(21, 'admin@trackback.com', 'jsndnsk', 'a@b.com', '$2y$12$zuIRQHU4KcnLJyzwyp7d8O9EAhSQMPhv0uZzIb3I8LZcrpVLsVMsC', 'student', 'NSU', NULL, NULL, 'active', '2026-01-19 13:20:22', '2026-01-19 15:04:18'),
(23, 'USR1768847120', 'chocoTaco', 'admin5@trackback.com', '$2y$12$4E16zZnkfk7MhWTbG3nfbebiI3l3C/gEdiRZfhXSo80Uc1AUVfVya', 'admin', NULL, NULL, NULL, 'pending', '2026-01-19 18:25:21', '2026-01-19 18:25:21');

-- --------------------------------------------------------

--
-- Table structure for table `warnings`
--

CREATE TABLE `warnings` (
  `warning_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `moderator_id` int(11) NOT NULL,
  `warning_reason` text NOT NULL,
  `warning_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `removed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warnings`
--

INSERT INTO `warnings` (`warning_id`, `user_id`, `moderator_id`, `warning_reason`, `warning_date`, `removed_at`) VALUES
(5, 15, 16, 'Inappropriate Content: Provide a proper image', '2026-01-19 09:16:17', NULL),
(6, 15, 1, 'Spam', '2026-01-19 13:34:45', NULL),
(7, 15, 16, 'Policy Violation', '2026-01-19 13:37:26', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `disputes`
--
ALTER TABLE `disputes`
  ADD PRIMARY KEY (`dispute_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `claimant1_id` (`claimant1_id`),
  ADD KEY `claimant2_id` (`claimant2_id`),
  ADD KEY `moderator_id` (`moderator_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `related_post_id` (`related_post_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `post_flags`
--
ALTER TABLE `post_flags`
  ADD PRIMARY KEY (`flag_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `moderator_id` (`moderator_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `warnings`
--
ALTER TABLE `warnings`
  ADD PRIMARY KEY (`warning_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `moderator_id` (`moderator_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=270;

--
-- AUTO_INCREMENT for table `disputes`
--
ALTER TABLE `disputes`
  MODIFY `dispute_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `post_flags`
--
ALTER TABLE `post_flags`
  MODIFY `flag_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `warnings`
--
ALTER TABLE `warnings`
  MODIFY `warning_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `disputes`
--
ALTER TABLE `disputes`
  ADD CONSTRAINT `disputes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disputes_ibfk_2` FOREIGN KEY (`claimant1_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disputes_ibfk_3` FOREIGN KEY (`claimant2_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disputes_ibfk_4` FOREIGN KEY (`moderator_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`related_post_id`) REFERENCES `posts` (`post_id`) ON DELETE SET NULL;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `post_flags`
--
ALTER TABLE `post_flags`
  ADD CONSTRAINT `post_flags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_flags_ibfk_2` FOREIGN KEY (`moderator_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `warnings`
--
ALTER TABLE `warnings`
  ADD CONSTRAINT `warnings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warnings_ibfk_2` FOREIGN KEY (`moderator_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
