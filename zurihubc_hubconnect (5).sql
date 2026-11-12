-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 12, 2026 at 03:39 PM
-- Server version: 10.11.19-MariaDB
-- PHP Version: 8.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `zurihubc_hubconnect`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(40) DEFAULT NULL,
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_activity_log`
--

INSERT INTO `admin_activity_log` (`id`, `admin_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'settings.upload', 'setting', NULL, 'logo_full: (none) -> site/a5fcb4436effc3e4.png', '41.209.14.76', '2026-10-16 11:05:35'),
(2, 1, 'settings.upload', 'setting', NULL, 'logo_compact: (none) -> site/11a1895a4bc60514.png', '41.209.14.76', '2026-10-16 11:05:42'),
(3, 1, 'settings.upload', 'setting', NULL, 'logo_icon: (none) -> site/eb09ad23d2407863.png', '41.209.14.76', '2026-10-16 11:05:51'),
(4, 1, 'settings.upload', 'setting', NULL, 'favicon: (none) -> site/2c0cb6f89086df9f.png', '41.209.14.76', '2026-10-16 11:05:59'),
(5, 1, 'settings.upload', 'setting', NULL, 'logo_header_scrolled: (none) -> site/4ad34c0215314573.png', '41.209.14.76', '2026-10-16 11:06:15'),
(6, 1, 'settings.upload', 'setting', NULL, 'logo_white: (none) -> site/1b230b87530cca84.png', '41.209.14.76', '2026-10-16 11:06:32'),
(7, 1, 'settings.upload', 'setting', NULL, 'logo_header_scrolled_dark: (none) -> site/1b230b87530cca84.png (media library)', '41.209.14.76', '2026-10-16 11:07:00'),
(8, 1, 'settings.upload', 'setting', NULL, 'logo_footer: (none) -> site/4ad34c0215314573.png (media library)', '41.209.14.76', '2026-10-16 11:07:33'),
(9, 1, 'settings.upload', 'setting', NULL, 'logo_footer_dark: (none) -> site/1b230b87530cca84.png (media library)', '41.209.14.76', '2026-10-16 11:07:38'),
(10, 1, 'settings.upload', 'setting', NULL, 'logo_dark: (none) -> site/1b230b87530cca84.png (media library)', '41.209.14.76', '2026-10-16 11:07:52'),
(11, 1, 'settings.save', 'settings', NULL, 'logo_h_header, logo_h_header_scrolled, logo_h_header_mobile, logo_w_footer, logo_h_admin', '41.209.14.76', '2026-10-16 11:08:22'),
(12, 1, 'settings.change', 'setting', NULL, 'logo_h_header: 40 -> 54', '41.209.14.76', '2026-10-16 11:08:22'),
(13, 1, 'settings.change', 'setting', NULL, 'logo_h_header_scrolled: 34 -> 50', '41.209.14.76', '2026-10-16 11:08:22'),
(14, 1, 'settings.change', 'setting', NULL, 'logo_h_header_mobile: 28 -> 31', '41.209.14.76', '2026-10-16 11:08:22'),
(15, 1, 'settings.change', 'setting', NULL, 'logo_w_footer: 140 -> 155', '41.209.14.76', '2026-10-16 11:08:22'),
(16, 1, 'settings.change', 'setting', NULL, 'logo_h_admin: 32 -> 40', '41.209.14.76', '2026-10-16 11:08:22'),
(17, 1, 'settings.save', 'settings', NULL, 'manual_mpesa_enabled, manual_mpesa_details', '41.209.14.76', '2026-10-16 11:10:44'),
(18, 1, 'settings.change', 'setting', NULL, 'manual_mpesa_enabled: off -> on', '41.209.14.76', '2026-10-16 11:10:44'),
(19, 1, 'settings.change', 'setting', NULL, 'manual_mpesa_details: (empty) -> Paybill 123456, account CONNECTHUB.', '41.209.14.76', '2026-10-16 11:10:44'),
(20, 1, 'settings.save', 'settings', NULL, 'bank_enabled', '41.209.14.76', '2026-10-16 11:11:17'),
(21, 1, 'settings.change', 'setting', NULL, 'bank_enabled: off -> on', '41.209.14.76', '2026-10-16 11:11:17'),
(22, 1, 'payment_approve', 'payment', 1, 'Payment approved. Boost running until 16 Oct 2026, 12:29 pm.', '41.209.14.76', '2026-10-16 11:59:35'),
(23, 1, 'payment_approve', 'payment', 2, 'Payment approved. Gift sent.', '41.209.14.76', '2026-10-16 12:01:35'),
(24, 1, 'payment_approve', 'payment', 3, 'Payment approved. Gift sent.', '41.209.14.76', '2026-10-16 12:16:27'),
(25, 1, 'host.view_selfie', 'host', 9026, 'Opened the selfie', '41.209.14.76', '2026-10-16 12:58:15'),
(26, 1, 'host.view_id', 'host', 9026, 'Opened the ID document', '41.209.14.76', '2026-10-16 12:58:15'),
(27, 1, 'host.id_verify', 'host', 9026, 'Shee', '41.209.14.76', '2026-10-16 12:58:20'),
(28, 1, 'host.approve', 'host', 9026, 'Shee, ID verified', '41.209.14.76', '2026-10-16 12:58:31'),
(29, 1, 'payment_approve', 'payment', 5, 'Payment approved. Subscription active until 6 Dec 2026.', '41.209.14.76', '2026-10-16 13:01:42'),
(30, 1, 'payment_approve', 'payment', 4, 'Payment approved. Featured until 23 Oct 2026.', '41.209.14.76', '2026-10-16 13:02:47'),
(31, 1, 'tools.cron', 'tools', NULL, 'Ran due jobs: 8 tasks, 0 failed', '41.209.14.76', '2026-10-16 13:13:18'),
(32, 1, 'tools.backup', 'tools', NULL, 'Downloaded a full database backup', '41.209.14.76', '2026-10-16 13:13:40'),
(33, 1, 'settings.upload', 'setting', NULL, 'og_image: (none) -> site/701baa472b6bc25c.jpg', '41.209.14.76', '2026-10-16 13:15:28'),
(34, 1, 'settings.upload', 'setting', NULL, 'logo_mobile: (none) -> site/3dc7c95ead56a575.png', '41.209.14.76', '2026-10-16 13:18:06'),
(35, 1, 'settings.upload', 'setting', NULL, 'logo_mobile_dark: (none) -> site/1b230b87530cca84.png (media library)', '41.209.14.76', '2026-10-16 13:18:20'),
(36, 1, 'settings.upload', 'setting', NULL, 'logo_mobile_white: (none) -> site/4ad34c0215314573.png (media library)', '41.209.14.76', '2026-10-16 13:18:34'),
(37, 1, 'settings.save', 'settings', NULL, 'logo_h_header_mobile', '41.209.14.76', '2026-10-16 13:19:20'),
(38, 1, 'settings.change', 'setting', NULL, 'logo_h_header_mobile: 31 -> 38', '41.209.14.76', '2026-10-16 13:19:20'),
(39, 1, 'settings.save', 'settings', NULL, 'logo_h_header_mobile', '41.209.14.76', '2026-10-16 13:19:34'),
(40, 1, 'settings.change', 'setting', NULL, 'logo_h_header_mobile: 38 -> 42', '41.209.14.76', '2026-10-16 13:19:34'),
(41, 1, 'settings.upload', 'setting', NULL, 'pwa_icon: (none) -> hero/1c38000c553fc0d4.jpg', '41.209.14.76', '2026-10-16 13:51:08'),
(42, 1, 'host.approve', 'host', 2, 'Juice, ID verified', '41.209.14.76', '2026-10-16 14:19:14'),
(43, 1, 'settings.save', 'settings', NULL, 'hosts_directory_access', '41.209.14.76', '2026-10-16 14:58:56'),
(44, 1, 'settings.change', 'setting', NULL, 'hosts_directory_access: vip -> logged_in', '41.209.14.76', '2026-10-16 14:58:56'),
(45, 1, 'intl_approve', 'user', 9025, NULL, '41.209.14.76', '2026-10-16 15:06:33'),
(46, 1, 'host_names_save', 'settings', NULL, 'Concierge / Concierges (/concierges, /concierge)', '41.209.14.76', '2026-10-16 15:17:15'),
(47, 1, 'landing_section_hide', 'landing_section', 10, NULL, '41.209.14.76', '2026-10-16 15:23:10'),
(48, 1, 'settings.save', 'settings', NULL, 'hero_layout', '41.209.14.76', '2026-10-16 15:23:41'),
(49, 1, 'settings.change', 'setting', NULL, 'hero_layout: collage -> simple', '41.209.14.76', '2026-10-16 15:23:41'),
(50, 1, 'settings.save', 'settings', NULL, 'hero_layout', '41.209.14.76', '2026-10-16 15:24:01'),
(51, 1, 'settings.change', 'setting', NULL, 'hero_layout: simple -> collage', '41.209.14.76', '2026-10-16 15:24:01'),
(52, 1, 'tools.cron', 'tools', NULL, 'Ran due jobs: 11 tasks, 0 failed', '41.209.14.76', '2026-10-16 15:24:46'),
(53, 1, 'boost_pack_update', 'boost_pack', 1, 'Quick boost', '41.209.14.76', '2026-10-16 15:27:42'),
(54, 1, 'boost_pack_update', 'boost_pack', 2, 'Evening boost price 150.00 to 100', '41.209.14.76', '2026-10-16 15:28:03'),
(55, 1, 'boost_pack_update', 'boost_pack', 3, 'All day price 500.00 to 300', '41.209.14.76', '2026-10-16 15:28:15'),
(56, 1, 'payment_approve', 'payment', 7, 'Payment approved. Booking deposit received.', '41.209.14.76', '2026-10-16 15:43:02'),
(57, 1, 'payment_approve', 'payment', 8, 'Payment approved. Gift sent.', '41.209.14.76', '2026-10-16 15:51:07'),
(58, 1, 'landing_section_reorder', 'landing_section', NULL, 'Reordered sections', '41.209.14.76', '2026-10-16 15:54:55'),
(59, 1, 'landing_section_reorder', 'landing_section', NULL, 'Reordered sections', '41.209.14.76', '2026-10-16 15:55:08'),
(60, 1, 'landing_section_hide', 'landing_section', 4, NULL, '41.209.14.76', '2026-10-16 15:55:18'),
(61, 1, 'landing_section_hide', 'landing_section', 7, NULL, '41.209.14.76', '2026-10-16 15:55:22'),
(62, 1, 'intl_rules', 'settings', NULL, 'International settings changed (mode access)', '41.209.14.76', '2026-10-16 17:26:13'),
(63, 1, 'settings.save', 'settings', NULL, 'logo_w_menu', '41.209.14.76', '2026-10-16 17:31:04'),
(64, 1, 'settings.change', 'setting', NULL, 'logo_w_menu: 140 -> 160', '41.209.14.76', '2026-10-16 17:31:04'),
(65, 1, 'settings.save', 'settings', NULL, 'logo_h_app_header', '41.209.14.76', '2026-10-16 17:31:34'),
(66, 1, 'settings.change', 'setting', NULL, 'logo_h_app_header: 28 -> 35', '41.209.14.76', '2026-10-16 17:31:34'),
(67, 1, 'settings.save', 'settings', NULL, 'logo_h_app_header', '41.209.14.76', '2026-10-16 17:31:44'),
(68, 1, 'settings.change', 'setting', NULL, 'logo_h_app_header: 35 -> 44', '41.209.14.76', '2026-10-16 17:31:44'),
(69, 1, 'settings.save', 'settings', NULL, 'logo_h_app_header', '41.209.14.76', '2026-10-16 17:32:04'),
(70, 1, 'settings.change', 'setting', NULL, 'logo_h_app_header: 44 -> 50', '41.209.14.76', '2026-10-16 17:32:04'),
(71, 1, 'settings.save', 'settings', NULL, 'logo_h_header_mobile', '41.209.14.76', '2026-10-16 17:33:36'),
(72, 1, 'settings.change', 'setting', NULL, 'logo_h_header_mobile: 42 -> 50', '41.209.14.76', '2026-10-16 17:33:36'),
(73, 1, 'settings.save', 'settings', NULL, 'logo_h_admin', '41.209.14.76', '2026-10-16 17:34:04'),
(74, 1, 'settings.change', 'setting', NULL, 'logo_h_admin: 40 -> 50', '41.209.14.76', '2026-10-16 17:34:04'),
(75, 1, 'photo.upload', 'user', 9001, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:28:51'),
(76, 1, 'photo.primary', 'photo', 65, 'Member #9001', '41.209.14.76', '2026-10-16 18:29:02'),
(77, 1, 'photo.upload', 'user', 9024, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:30:38'),
(78, 1, 'photo.primary', 'photo', 66, 'Member #9024', '41.209.14.76', '2026-10-16 18:30:57'),
(79, 1, 'photo.upload', 'user', 9023, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:34:07'),
(80, 1, 'photo.primary', 'photo', 67, 'Member #9023', '41.209.14.76', '2026-10-16 18:34:28'),
(81, 1, 'photo.upload', 'user', 9022, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:36:54'),
(82, 1, 'photo.primary', 'photo', 68, 'Member #9022', '41.209.14.76', '2026-10-16 18:37:25'),
(83, 1, 'photo.upload', 'user', 9021, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:39:32'),
(84, 1, 'photo.primary', 'photo', 69, 'Member #9021', '41.209.14.76', '2026-10-16 18:39:48'),
(85, 1, 'photo.upload', 'user', 9020, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:42:43'),
(86, 1, 'photo.primary', 'photo', 70, 'Member #9020', '41.209.14.76', '2026-10-16 18:43:03'),
(87, 1, 'photo.upload', 'user', 9019, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:45:05'),
(88, 1, 'photo.primary', 'photo', 71, 'Member #9019', '41.209.14.76', '2026-10-16 18:45:26'),
(89, 1, 'photo.upload', 'user', 9018, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:46:56'),
(90, 1, 'photo.primary', 'photo', 72, 'Member #9018', '41.209.14.76', '2026-10-16 18:47:16'),
(91, 1, 'photo.upload', 'user', 9017, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:49:54'),
(92, 1, 'photo.primary', 'photo', 73, 'Member #9017', '41.209.14.76', '2026-10-16 18:50:09'),
(93, 1, 'photo.upload', 'user', 9002, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:51:43'),
(94, 1, 'photo.primary', 'photo', 74, 'Member #9002', '41.209.14.76', '2026-10-16 18:52:02'),
(95, 1, 'photo.upload', 'user', 9003, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:52:38'),
(96, 1, 'photo.primary', 'photo', 75, 'Member #9003', '41.209.14.76', '2026-10-16 18:52:56'),
(97, 1, 'photo.upload', 'user', 9004, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:53:22'),
(98, 1, 'photo.primary', 'photo', 76, 'Member #9004', '41.209.14.76', '2026-10-16 18:53:42'),
(99, 1, 'photo.upload', 'user', 9005, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:56:02'),
(100, 1, 'photo.primary', 'photo', 77, 'Member #9005', '41.209.14.76', '2026-10-16 18:56:36'),
(101, 1, 'photo.upload', 'user', 9006, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:57:08'),
(102, 1, 'photo.primary', 'photo', 78, 'Member #9006', '41.209.14.76', '2026-10-16 18:57:22'),
(103, 1, 'photo.upload', 'user', 9007, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:57:50'),
(104, 1, 'photo.primary', 'photo', 79, 'Member #9007', '41.209.14.76', '2026-10-16 18:57:59'),
(105, 1, 'photo.upload', 'user', 9008, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:58:27'),
(106, 1, 'photo.primary', 'photo', 80, 'Member #9008', '41.209.14.76', '2026-10-16 18:58:36'),
(107, 1, 'photo.upload', 'user', 9009, '1 photo added by an admin', '41.209.14.76', '2026-10-16 18:59:30'),
(108, 1, 'photo.primary', 'photo', 81, 'Member #9009', '41.209.14.76', '2026-10-16 18:59:52'),
(109, 1, 'photo.upload', 'user', 9010, '1 photo added by an admin', '41.209.14.76', '2026-10-16 19:00:28'),
(110, 1, 'photo.primary', 'photo', 82, 'Member #9010', '41.209.14.76', '2026-10-16 19:00:37'),
(111, 1, 'photo.upload', 'user', 9011, '1 photo added by an admin', '41.209.14.76', '2026-10-16 19:01:02'),
(112, 1, 'photo.primary', 'photo', 83, 'Member #9011', '41.209.14.76', '2026-10-16 19:01:07'),
(113, 1, 'photo.upload', 'user', 9012, '1 photo added by an admin', '41.209.14.76', '2026-10-16 19:01:44'),
(114, 1, 'photo.primary', 'photo', 84, 'Member #9012', '41.209.14.76', '2026-10-16 19:01:49'),
(115, 1, 'photo.upload', 'user', 9013, '1 photo added by an admin', '41.209.14.76', '2026-10-16 19:03:13'),
(116, 1, 'photo.primary', 'photo', 85, 'Member #9013', '41.209.14.76', '2026-10-16 19:03:17'),
(117, 1, 'photo.upload', 'user', 9014, '1 photo added by an admin', '41.209.14.76', '2026-10-16 19:03:54'),
(118, 1, 'photo.primary', 'photo', 86, 'Member #9014', '41.209.14.76', '2026-10-16 19:04:03'),
(119, 1, 'photo.upload', 'user', 9015, '1 photo added by an admin', '41.209.14.76', '2026-10-16 19:04:49'),
(120, 1, 'photo.primary', 'photo', 87, 'Member #9015', '41.209.14.76', '2026-10-16 19:04:56'),
(121, 1, 'photo.upload', 'user', 9016, '1 photo added by an admin', '41.209.14.76', '2026-10-16 19:05:50'),
(122, 1, 'photo.primary', 'photo', 88, 'Member #9016', '41.209.14.76', '2026-10-16 19:05:54'),
(123, 1, 'photo.upload', 'user', 9025, '1 photo added by an admin', '41.209.14.76', '2026-10-16 19:07:30'),
(124, 1, 'photo.upload', 'user', 9025, '1 photo added by an admin', '41.209.14.76', '2026-10-16 19:08:07'),
(125, 1, 'photo.primary', 'photo', 90, 'Member #9025', '41.209.14.76', '2026-10-16 19:08:12'),
(126, 1, 'photo.delete', 'photo', 57, 'Member #9025', '41.209.14.76', '2026-10-16 19:08:16'),
(127, 1, 'landing_section_update', 'landing_section', 1, 'locations', '41.209.14.76', '2026-10-16 19:10:08'),
(128, 1, 'landing_section_update', 'landing_section', 1, 'locations', '41.209.14.76', '2026-10-16 19:10:25'),
(129, 1, 'landing_section_update', 'landing_section', 3, 'how_it_works', '41.209.14.76', '2026-10-16 19:22:18'),
(130, 1, 'landing_section_update', 'landing_section', 5, 'safety', '41.209.14.76', '2026-10-16 19:23:28'),
(131, 1, 'landing_section_update', 'landing_section', 5, 'safety', '41.209.14.76', '2026-10-16 19:26:08'),
(132, 1, 'landing_section_update', 'landing_section', 6, 'hosts', '41.209.14.76', '2026-10-16 19:27:41'),
(133, 1, 'settings.upload', 'setting', NULL, 'hero_art_1: @art/hero-1.webp -> hero/0b33ec6247dda791.jpg', '41.209.14.76', '2026-10-16 19:41:34'),
(134, 1, 'settings.upload', 'setting', NULL, 'hero_art_2: @art/hero-2.webp -> hero/028d74bec445aa4a.jpg', '41.209.14.76', '2026-10-16 19:41:42'),
(135, 1, 'settings.upload', 'setting', NULL, 'hero_art_3: @art/hero-3.webp -> hero/def30aeef3b3336d.jpg', '41.209.14.76', '2026-10-16 19:45:56'),
(136, 1, 'settings.upload', 'setting', NULL, 'hero_art_1: hero/0b33ec6247dda791.jpg -> hero/8869e0254b0f9e81.jpg', '41.209.14.76', '2026-10-16 19:52:24'),
(137, 1, 'settings.upload', 'setting', NULL, 'hero_art_2: hero/028d74bec445aa4a.jpg -> hero/e8bf098bec88889e.jpg', '41.209.14.76', '2026-10-16 19:52:30'),
(138, 1, 'settings.upload', 'setting', NULL, 'hero_art_2: hero/e8bf098bec88889e.jpg -> hero/9013919f50afddf1.jpg', '41.209.14.76', '2026-10-16 20:07:47'),
(139, 1, 'settings.upload', 'setting', NULL, 'hero_art_3: hero/def30aeef3b3336d.jpg -> hero/417e7d6e2485e501.jpg', '41.209.14.76', '2026-10-16 20:07:58'),
(140, 1, 'settings.upload', 'setting', NULL, 'hero_art_3: hero/417e7d6e2485e501.jpg -> hero/d2861aa0d4a2cab6.jpg', '41.209.14.76', '2026-10-16 20:08:53'),
(141, 1, 'blog_update', 'blog_post', 3, 'Making friends in a new town: a practical guide for Kenya (published)', '41.209.14.76', '2026-10-16 20:14:13'),
(142, 1, 'blog_update', 'blog_post', 1, 'First message ideas that get replies: 5 openers that work (published)', '41.209.14.76', '2026-10-16 20:15:25'),
(143, 1, 'blog_update', 'blog_post', 1, 'First message ideas that get replies: 5 openers that work (published)', '41.209.14.76', '2026-10-16 20:20:30'),
(144, 1, 'blog_update', 'blog_post', 2, 'How to meet someone from the internet safely (published)', '41.209.14.76', '2026-10-16 20:30:35'),
(145, 1, 'blog_update', 'blog_post', 30, 'Texting rules for early dating: timing, pace and calls (published)', '41.209.14.76', '2026-10-16 20:32:00'),
(146, 1, 'blog_update', 'blog_post', 29, 'Signs someone likes you online, and how to be sure (published)', '41.209.14.76', '2026-10-16 20:45:22'),
(147, 1, 'blog_update', 'blog_post', 28, 'Should you send money to someone you met online? (published)', '41.209.14.76', '2026-10-16 20:46:14'),
(148, 1, 'blog_update', 'blog_post', 31, 'Why profile verification matters and how to get verified (published)', '41.209.14.76', '2026-10-16 20:48:19'),
(149, 1, 'blog_update', 'blog_post', 27, 'Romance scams in Kenya: warning signs and what to do (published)', '41.209.14.76', '2026-10-16 20:52:12'),
(150, 1, 'blog_update', 'blog_post', 27, 'Romance scams in Kenya: warning signs and what to do (published)', '41.209.14.76', '2026-10-16 20:52:20'),
(151, 1, 'blog_update', 'blog_post', 27, 'Romance scams in Kenya: warning signs and what to do (published)', '41.209.14.76', '2026-10-16 20:53:18'),
(152, 1, 'blog_update', 'blog_post', 26, 'How to protect your privacy on dating apps (published)', '41.209.14.76', '2026-10-16 20:53:27'),
(153, 1, 'blog_update', 'blog_post', 25, 'Online dating in Kenya: the complete guide for beginners (published)', '41.209.14.76', '2026-10-16 20:55:57'),
(154, 1, 'blog_update', 'blog_post', 24, 'Meeting people abroad safely: a guide for Kenyans online (published)', '41.209.14.76', '2026-10-16 20:57:06'),
(155, 1, 'blog_update', 'blog_post', 23, 'Long distance relationship tips that actually work (published)', '41.209.14.76', '2026-10-16 21:00:25'),
(156, 1, 'blog_update', 'blog_post', 22, 'Introducing your partner to family in Kenya: a respectful guide (published)', '41.209.14.76', '2026-10-16 21:01:54'),
(157, 1, 'blog_update', 'blog_post', 21, 'How to write a dating profile bio that sounds like you (published)', '41.209.14.76', '2026-10-16 21:03:54'),
(158, 1, 'blog_update', 'blog_post', 20, 'How to verify someone is real before meeting them (published)', '41.209.14.76', '2026-10-16 21:04:22'),
(159, 1, 'blog_update', 'blog_post', 19, 'How to start a conversation online and keep it going (published)', '41.209.14.76', '2026-10-16 21:06:05'),
(160, 1, 'blog_update', 'blog_post', 18, 'How to spot fake profiles and catfishing online (published)', '41.209.14.76', '2026-10-16 21:08:04'),
(161, 1, 'blog_update', 'blog_post', 17, 'How to make friends as an adult: simple steps that work (published)', '41.209.14.76', '2026-10-16 21:10:37'),
(162, 1, 'blog_update', 'blog_post', 16, 'How to know if you are ready to date again after a loss (published)', '41.209.14.76', '2026-10-16 21:12:28'),
(163, 1, 'blog_update', 'blog_post', 15, 'How to handle rejection gracefully, giving and receiving (published)', '41.209.14.76', '2026-10-16 21:16:41'),
(164, 1, 'tools.cron', 'tools', NULL, 'Ran due jobs: 11 tasks, 0 failed', '41.209.14.76', '2026-10-16 21:20:20'),
(165, 1, 'blog_update', 'blog_post', 1, 'First message ideas that get replies: 5 openers that work (published)', '41.209.14.76', '2026-10-16 21:29:34'),
(166, 1, 'blog_update', 'blog_post', 1, 'First message ideas that get replies: 5 openers that work (published)', '41.209.14.76', '2026-10-16 21:31:53'),
(167, 1, 'media.upload', 'media', 46, 'library/181f18a79ce9e25d.png', '41.209.14.76', '2026-10-16 23:50:40'),
(168, 1, 'settings.upload', 'setting', NULL, 'logo_header_member: (none) -> library/181f18a79ce9e25d.png (media library)', '41.209.14.76', '2026-10-16 23:50:42'),
(169, 1, 'settings.upload', 'setting', NULL, 'logo_sidepanel: (none) -> library/181f18a79ce9e25d.png (media library)', '41.209.14.76', '2026-10-16 23:52:12'),
(170, 1, 'settings.save', 'settings', NULL, 'logo_h_header_member', '41.209.14.76', '2026-10-16 23:52:56'),
(171, 1, 'settings.change', 'setting', NULL, 'logo_h_header_member: 34 -> 44', '41.209.14.76', '2026-10-16 23:52:56'),
(172, 1, 'landing_section_update', 'landing_section', 1, 'locations', '41.209.14.76', '2026-10-16 23:55:59'),
(173, 1, 'landing_section_update', 'landing_section', 1, 'locations', '41.209.14.76', '2026-10-16 23:56:57'),
(174, 1, 'landing_section_update', 'landing_section', 1, 'locations', '41.209.14.76', '2026-10-16 23:57:50'),
(175, 1, 'tools.cron', 'tools', NULL, 'Ran due jobs: 11 tasks, 0 failed', '41.209.14.76', '2026-10-17 00:12:12'),
(176, 1, 'photo.upload', 'user', 9038, '1 photo added by an admin', '41.209.14.76', '2026-11-09 12:12:30'),
(177, 1, 'photo.primary', 'photo', 100, 'Member #9038', '41.209.14.76', '2026-11-09 12:12:37'),
(178, 1, 'photo.upload', 'user', 9037, '1 photo added by an admin', '41.209.14.76', '2026-11-09 12:13:13'),
(179, 1, 'photo.primary', 'photo', 101, 'Member #9037', '41.209.14.76', '2026-11-09 12:13:40'),
(180, 1, 'photo.upload', 'user', 9036, '1 photo added by an admin', '41.209.14.76', '2026-11-09 12:14:14'),
(181, 1, 'photo.primary', 'photo', 102, 'Member #9036', '41.209.14.76', '2026-11-09 12:14:19'),
(182, 1, 'photo.upload', 'user', 9034, '1 photo added by an admin', '41.209.14.76', '2026-11-09 12:14:54'),
(183, 1, 'photo.primary', 'photo', 103, 'Member #9034', '41.209.14.76', '2026-11-09 12:16:05'),
(184, 1, 'photo.upload', 'user', 9035, '1 photo added by an admin', '41.209.14.76', '2026-11-09 12:16:26'),
(185, 1, 'photo.primary', 'photo', 104, 'Member #9035', '41.209.14.76', '2026-11-09 12:16:33'),
(186, 1, 'photo.upload', 'user', 9032, '1 photo added by an admin', '41.209.14.76', '2026-11-09 12:17:12'),
(187, 1, 'photo.primary', 'photo', 105, 'Member #9032', '41.209.14.76', '2026-11-09 12:17:21'),
(188, 1, 'photo.upload', 'user', 9033, '1 photo added by an admin', '41.209.14.76', '2026-11-09 12:20:49'),
(189, 1, 'photo.primary', 'photo', 106, 'Member #9033', '41.209.14.76', '2026-11-09 12:20:53'),
(190, 1, 'photo.upload', 'user', 9031, '1 photo added by an admin', '41.209.14.76', '2026-11-09 12:21:08'),
(191, 1, 'photo.primary', 'photo', 107, 'Member #9031', '41.209.14.76', '2026-11-09 12:21:14'),
(192, 1, 'photo.upload', 'user', 9030, '1 photo added by an admin', '41.209.14.76', '2026-11-09 12:21:39'),
(193, 1, 'photo.primary', 'photo', 108, 'Member #9030', '41.209.14.76', '2026-11-09 12:21:45'),
(194, 1, 'host_names_save', 'settings', NULL, 'Escort / Escorts (/concierges, /concierge)', '41.209.14.76', '2026-11-10 00:30:02'),
(195, 1, 'settings.upload', 'setting', NULL, 'logo_auth: (none) -> library/181f18a79ce9e25d.png (media library)', '41.209.14.76', '2026-11-10 00:35:59'),
(196, 1, 'settings.upload', 'setting', NULL, 'logo_auth_dark: (none) -> site/1b230b87530cca84.png (media library)', '41.209.14.76', '2026-11-10 00:36:22'),
(197, 1, 'host_seo_save', 'settings', NULL, 'Directory page', '41.209.14.76', '2026-11-10 00:39:42'),
(198, 1, 'host_seo_save', 'settings', NULL, 'Keywords: 5', '41.209.14.76', '2026-11-10 00:40:16'),
(199, 1, 'agent_visibility_save', 'settings', NULL, 'Changed: host_show_rates', '41.209.14.76', '2026-11-10 00:42:10'),
(200, 1, 'dm_pack_update', 'dm_pack', 1, 'Starter', '41.209.14.76', '2026-11-10 00:43:17'),
(201, 1, 'dm_pack_update', 'dm_pack', 2, 'Popular', '41.209.14.76', '2026-11-10 00:43:31'),
(202, 1, 'dm_pack_update', 'dm_pack', 3, 'Best value', '41.209.14.76', '2026-11-10 00:43:42'),
(203, 1, 'landing_section_hide', 'landing_section', 8, NULL, '41.209.14.76', '2026-11-10 00:45:14'),
(204, 1, 'landing_section_hide', 'landing_section', 9, NULL, '41.209.14.76', '2026-11-10 00:45:15'),
(205, 1, 'elite.approve', 'user', 9001, 'Wanjiru', '41.209.14.76', '2026-11-12 09:33:11'),
(206, 1, 'payment_approve', 'payment', 9, 'Payment approved. Elite pass until 12 Dec 2026.', '41.209.14.76', '2026-11-12 09:36:35'),
(207, 1, 'payment_approve', 'payment', 6, 'Payment approved. Subscription active until 25 Nov 2026.', '41.209.14.76', '2026-11-12 09:36:38'),
(208, 1, 'settings.save', 'settings', NULL, 'booking_enabled', '41.209.14.76', '2026-11-12 09:45:10'),
(209, 1, 'settings.change', 'setting', NULL, 'booking_enabled: on -> off', '41.209.14.76', '2026-11-12 09:45:10'),
(210, 1, 'media.upload', 'media', 47, 'library/52e31e52f4861a2f.jpg', '41.209.14.76', '2026-11-12 09:47:06'),
(211, 1, 'settings.upload', 'setting', NULL, 'hosts_hero_image: @art/hosts.webp -> library/52e31e52f4861a2f.jpg (media library)', '41.209.14.76', '2026-11-12 09:47:08'),
(212, 1, 'settings.save', 'settings', NULL, 'host_selfie_required, host_min_about_chars, host_rules_text, host_rules_text_contact', '41.209.14.76', '2026-11-12 09:47:55'),
(213, 1, 'settings.change', 'setting', NULL, 'host_selfie_required: on -> off', '41.209.14.76', '2026-11-12 09:47:55'),
(214, 1, 'settings.change', 'setting', NULL, 'host_min_about_chars: 80 -> 20', '41.209.14.76', '2026-11-12 09:47:55'),
(215, 1, 'settings.change', 'setting', NULL, 'host_rules_text: ConnectHub Hosts offer friendly, professional company: dinners, city… -> ConnectHub Escorts offer friendly, professional company: dinners, cit…', '41.209.14.76', '2026-11-12 09:47:55'),
(216, 1, 'settings.change', 'setting', NULL, 'host_rules_text_contact: ConnectHub Hosts offer friendly, professional company: dinners, city… -> ConnectHub Escorts offer friendly, professional company: dinners, cit…', '41.209.14.76', '2026-11-12 09:47:55'),
(217, 1, 'settings.save', 'settings', NULL, 'host_rules_text, host_rules_text_contact, host_banned_terms', '41.209.14.76', '2026-11-12 09:50:51'),
(218, 1, 'settings.change', 'setting', NULL, 'host_rules_text: ConnectHub Escorts offer friendly, professional company: dinners, cit… -> - Escorts must be 18 or over and ID-verified. - Respect each other\'s…', '41.209.14.76', '2026-11-12 09:50:51'),
(219, 1, 'settings.change', 'setting', NULL, 'host_rules_text_contact: ConnectHub Escorts offer friendly, professional company: dinners, cit… -> - Escorts must be 18 or over and ID-verified. - Nothing is booked or…', '41.209.14.76', '2026-11-12 09:50:51'),
(220, 1, 'settings.change', 'setting', NULL, 'host_banned_terms: escort, sex, sexual, erotic, nude, naked, fetish, hookup, happy endin… -> (empty)', '41.209.14.76', '2026-11-12 09:50:51'),
(221, 1, 'settings.save', 'settings', NULL, 'host_listing_price_weekly, host_listing_price_monthly, host_listing_price_yearly', '41.209.14.76', '2026-11-12 09:51:45'),
(222, 1, 'settings.change', 'setting', NULL, 'host_listing_price_weekly: 300 -> 100', '41.209.14.76', '2026-11-12 09:51:45'),
(223, 1, 'settings.change', 'setting', NULL, 'host_listing_price_monthly: 1000 -> 300', '41.209.14.76', '2026-11-12 09:51:45'),
(224, 1, 'settings.change', 'setting', NULL, 'host_listing_price_yearly: 9000 -> 3000', '41.209.14.76', '2026-11-12 09:51:45'),
(225, 1, 'settings.save', 'settings', NULL, 'host_min_photos', '41.209.14.76', '2026-11-12 09:53:27'),
(226, 1, 'settings.change', 'setting', NULL, 'host_min_photos: 3 -> 2', '41.209.14.76', '2026-11-12 09:53:27'),
(227, 1, 'settings.save', 'settings', NULL, 'host_connections_enabled', '41.209.14.76', '2026-11-12 09:53:49'),
(228, 1, 'settings.change', 'setting', NULL, 'host_connections_enabled: off -> on', '41.209.14.76', '2026-11-12 09:53:49'),
(229, 1, 'settings.save', 'settings', NULL, 'host_connections_page_title, host_connections_menu_label', '41.209.14.76', '2026-11-12 09:54:38'),
(230, 1, 'settings.change', 'setting', NULL, 'host_connections_page_title: Connection profiles -> Escorts profiles', '41.209.14.76', '2026-11-12 09:54:38'),
(231, 1, 'settings.change', 'setting', NULL, 'host_connections_menu_label: Connection profiles -> Escorts profiles', '41.209.14.76', '2026-11-12 09:54:38'),
(232, 1, 'settings.save', 'settings', NULL, 'hosts_per_page', '41.209.14.76', '2026-11-12 09:57:44'),
(233, 1, 'settings.change', 'setting', NULL, 'hosts_per_page: 12 -> 48', '41.209.14.76', '2026-11-12 09:57:44'),
(234, 1, 'settings.save', 'settings', NULL, 'hosts_directory_access', '41.209.14.76', '2026-11-12 09:58:06'),
(235, 1, 'settings.change', 'setting', NULL, 'hosts_directory_access: logged_in -> everyone', '41.209.14.76', '2026-11-12 09:58:06'),
(236, 1, 'settings.upload', 'setting', NULL, 'hosts_hero_image: library/52e31e52f4861a2f.jpg -> hero/0b33ec6247dda791.jpg (media library)', '41.209.14.76', '2026-11-12 09:58:34'),
(237, 1, 'choices.save', 'settings', NULL, 'Visitor choices', '154.159.252.86', '2026-11-12 17:10:20'),
(238, 1, 'story_approve', 'agent_story', 980006, NULL, '41.209.14.76', '2026-11-12 22:18:17'),
(239, 1, 'elite.feature', 'user', 9034, 'Esther', '41.209.14.76', '2026-11-12 22:52:47'),
(240, 1, 'landing_section_hide', 'landing_section', 6, NULL, '41.209.14.76', '2026-11-12 23:28:07'),
(241, 1, 'host_service_create', 'host_service', 39, 'Threesome', '41.209.14.76', '2026-11-12 23:31:24'),
(242, 1, 'host_service_create', 'host_service', 40, 'Girlfriend Experience (GFE)', '41.209.14.76', '2026-11-12 23:32:47'),
(243, 1, 'host_service_create', 'host_service', 41, 'Anal', '41.209.14.76', '2026-11-12 23:33:30'),
(244, 1, 'host_service_create', 'host_service', 42, 'BJ', '41.209.14.76', '2026-11-12 23:34:14'),
(245, 1, 'host_service_update', 'host_service', 42, 'BJ', '41.209.14.76', '2026-11-12 23:36:10'),
(246, 1, 'host_service_create', 'host_service', 43, 'COB', '41.209.14.76', '2026-11-12 23:36:57');

-- --------------------------------------------------------

--
-- Table structure for table `admin_roles`
--

CREATE TABLE `admin_roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(40) NOT NULL,
  `name` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `permissions` mediumtext NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_roles`
--

INSERT INTO `admin_roles` (`id`, `slug`, `name`, `description`, `permissions`, `is_system`, `created_at`) VALUES
(1, 'super_admin', 'Super admin', 'Full access to everything, including settings and roles.', '{\"*\":\"full\"}', 1, '2026-10-16 06:16:42'),
(2, 'moderator', 'Moderator', 'Reviews photos, reports, verifications and members.', '{\"dashboard\":\"view\",\"users\":\"edit\",\"hosts\":\"edit\",\"moderation\":\"full\",\"reports\":\"full\",\"verifications\":\"full\",\"messages\":\"view\",\"logs\":\"view\"}', 1, '2026-10-16 06:16:42'),
(3, 'support', 'Support agent', 'Answers members and handles contact messages.', '{\"dashboard\":\"view\",\"users\":\"view\",\"reports\":\"view\",\"messages\":\"full\",\"payments\":\"view\",\"newsletter\":\"view\"}', 1, '2026-10-16 06:16:42'),
(4, 'finance', 'Finance', 'Payments, subscriptions, payouts and bookings.', '{\"dashboard\":\"view\",\"payments\":\"full\",\"subscriptions\":\"full\",\"plans\":\"view\",\"payouts\":\"full\",\"bookings\":\"full\",\"analytics\":\"view\",\"users\":\"view\"}', 1, '2026-10-16 06:16:42'),
(5, 'editor', 'Content editor', 'Pages, blog, FAQs, landing page and navigation.', '{\"dashboard\":\"view\",\"content\":\"full\",\"landing\":\"full\",\"nav\":\"full\",\"newsletter\":\"edit\",\"messages\":\"view\",\"media\":\"full\"}', 1, '2026-10-16 06:16:42');

-- --------------------------------------------------------

--
-- Table structure for table `admin_user_notes`
--

CREATE TABLE `admin_user_notes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `note` varchar(2000) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `advertisements`
--

CREATE TABLE `advertisements` (
  `id` int(10) UNSIGNED NOT NULL,
  `advertiser_name` varchar(100) NOT NULL,
  `advertiser_email` varchar(190) DEFAULT NULL,
  `advertiser_phone` varchar(25) DEFAULT NULL,
  `title` varchar(160) NOT NULL,
  `description` varchar(300) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `link_url` varchar(500) NOT NULL,
  `cta_text` varchar(40) NOT NULL DEFAULT 'Learn more',
  `ad_type` enum('banner','card','popup','story') NOT NULL DEFAULT 'card',
  `placement` enum('discover','search','landing','hosts','messages','browse','elite','global') NOT NULL DEFAULT 'discover',
  `starts_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `max_views` int(10) UNSIGNED DEFAULT NULL,
  `max_clicks` int(10) UNSIGNED DEFAULT NULL,
  `views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `clicks` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('unpaid','pending','active','paused','rejected') NOT NULL DEFAULT 'pending',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `package_id` int(10) UNSIGNED DEFAULT NULL,
  `duration_days` int(10) UNSIGNED DEFAULT NULL,
  `payment_id` int(10) UNSIGNED DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `draft_token` varchar(32) DEFAULT NULL,
  `placements` varchar(120) DEFAULT NULL,
  `review_note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `advertisement_events`
--

CREATE TABLE `advertisement_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `advertisement_id` int(10) UNSIGNED NOT NULL,
  `event_type` enum('impression','click','story_view') NOT NULL,
  `placement` varchar(40) DEFAULT NULL,
  `visitor_key` char(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ad_packages`
--

CREATE TABLE `ad_packages` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `tagline` varchar(140) DEFAULT NULL,
  `days` int(10) UNSIGNED NOT NULL DEFAULT 7,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `placements` varchar(120) NOT NULL DEFAULT 'landing,discover',
  `is_popular` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ad_packages`
--

INSERT INTO `ad_packages` (`id`, `name`, `tagline`, `days`, `price`, `placements`, `is_popular`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Starter', 'A week in the feed, to try it out.', 7, 500.00, 'discover,browse', 0, 1, 1, '2026-11-09 08:02:23'),
(2, 'Growth', 'Two weeks on the home page and in the feed.', 14, 1200.00, 'landing,discover,browse', 1, 1, 2, '2026-11-09 08:02:23'),
(3, 'Spotlight', 'A month everywhere people look.', 30, 2500.00, 'landing,discover,search,browse,hosts', 0, 1, 3, '2026-11-09 08:02:23');

-- --------------------------------------------------------

--
-- Table structure for table `agent_stories`
--

CREATE TABLE `agent_stories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `media_type` enum('image','video') NOT NULL,
  `media_path` varchar(255) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `caption` varchar(500) DEFAULT NULL,
  `status` enum('pending','active','rejected','hidden') NOT NULL DEFAULT 'pending',
  `moderation_note` varchar(500) DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `view_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `stats_recorded` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `agent_stories`
--

INSERT INTO `agent_stories` (`id`, `user_id`, `media_type`, `media_path`, `thumbnail_path`, `caption`, `status`, `moderation_note`, `reviewed_by`, `reviewed_at`, `published_at`, `view_count`, `stats_recorded`, `created_at`, `expires_at`) VALUES
(980001, 9017, 'image', 'stories/demo/faith-evening.jpg', 'stories/demo/faith-evening_thumb.jpg', 'A little city sparkle tonight ✨', 'active', NULL, NULL, NULL, '2026-11-12 12:22:03', 3, 0, '2026-11-12 12:22:03', '2026-11-13 12:22:03'),
(980002, 9023, 'image', 'stories/demo/peter-night-out.jpg', 'stories/demo/peter-night-out_thumb.jpg', 'Good conversations and a great night out.', 'active', NULL, NULL, NULL, '2026-11-12 12:22:03', 3, 0, '2026-11-12 12:22:03', '2026-11-13 12:22:03'),
(980003, 9018, 'image', 'stories/demo/naomi-coast.jpg', 'stories/demo/naomi-coast_thumb.jpg', 'Coastal light and a slower kind of afternoon.', 'active', NULL, NULL, NULL, '2026-11-12 12:22:03', 3, 0, '2026-11-12 12:22:03', '2026-11-13 12:22:03'),
(980004, 9024, 'image', 'stories/demo/joseph-gym.jpg', 'stories/demo/joseph-gym_thumb.jpg', 'A little movement keeps the day in balance.', 'active', NULL, NULL, NULL, '2026-11-12 12:22:03', 2, 0, '2026-11-12 12:22:03', '2026-11-13 12:22:03'),
(980005, 9019, 'image', 'stories/demo/amina-lifestyle.jpg', 'stories/demo/amina-lifestyle_thumb.jpg', 'Old Town charm, warm evenings and good company.', 'active', NULL, NULL, NULL, '2026-11-12 12:22:03', 3, 0, '2026-11-12 12:22:03', '2026-11-13 12:22:03'),
(980007, 2, 'image', 'stories/2026/11/2_0dcd2752c1b1338596949cb9.jpg', 'stories/2026/11/2_0dcd2752c1b1338596949cb9_t.jpg', 'Come get some😜', 'active', NULL, NULL, NULL, '2026-11-12 23:25:19', 1, 0, '2026-11-12 23:25:19', '2026-11-13 23:25:19');

-- --------------------------------------------------------

--
-- Table structure for table `agent_story_daily_stats`
--

CREATE TABLE `agent_story_daily_stats` (
  `stat_date` date NOT NULL,
  `stories_published` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `image_stories` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `video_stories` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `views` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `agent_story_daily_stats`
--

INSERT INTO `agent_story_daily_stats` (`stat_date`, `stories_published`, `image_stories`, `video_stories`, `views`, `updated_at`) VALUES
('2026-11-12', 1, 1, 0, 1, '2026-11-12 23:24:46');

-- --------------------------------------------------------

--
-- Table structure for table `agent_story_views`
--

CREATE TABLE `agent_story_views` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `story_id` bigint(20) UNSIGNED NOT NULL,
  `viewer_key` char(64) NOT NULL,
  `viewer_user_id` int(10) UNSIGNED DEFAULT NULL,
  `viewed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `agent_story_views`
--

INSERT INTO `agent_story_views` (`id`, `story_id`, `viewer_key`, `viewer_user_id`, `viewed_at`) VALUES
(1, 980003, '7f855f851476912030e310977a624667cdf1ec977177cf24914a7bc73c177887', NULL, '2026-11-12 20:24:01'),
(2, 980001, '7f855f851476912030e310977a624667cdf1ec977177cf24914a7bc73c177887', NULL, '2026-11-12 20:24:03'),
(3, 980004, '7f855f851476912030e310977a624667cdf1ec977177cf24914a7bc73c177887', NULL, '2026-11-12 20:24:05'),
(4, 980005, '7f855f851476912030e310977a624667cdf1ec977177cf24914a7bc73c177887', NULL, '2026-11-12 20:24:06'),
(5, 980002, '7f855f851476912030e310977a624667cdf1ec977177cf24914a7bc73c177887', NULL, '2026-11-12 20:24:07'),
(7, 980005, '6468799434f1b4c59fa0eae6731a13d68c1f9b9fdd9280df72389af468ad43d3', 9001, '2026-11-12 20:25:26'),
(8, 980003, '6468799434f1b4c59fa0eae6731a13d68c1f9b9fdd9280df72389af468ad43d3', 9001, '2026-11-12 20:54:57'),
(9, 980001, '6468799434f1b4c59fa0eae6731a13d68c1f9b9fdd9280df72389af468ad43d3', 9001, '2026-11-12 20:54:58'),
(12, 980004, '6468799434f1b4c59fa0eae6731a13d68c1f9b9fdd9280df72389af468ad43d3', 9001, '2026-11-12 20:57:50'),
(15, 980002, '6468799434f1b4c59fa0eae6731a13d68c1f9b9fdd9280df72389af468ad43d3', 9001, '2026-11-12 20:57:55'),
(18, 980001, '6ea0b55a144d4bb332f35dc575aedf1d9dff84fe857ce5f8cf3b91008948d268', NULL, '2026-11-12 22:15:36'),
(19, 980003, '6ea0b55a144d4bb332f35dc575aedf1d9dff84fe857ce5f8cf3b91008948d268', NULL, '2026-11-12 22:15:41'),
(20, 980002, '6ea0b55a144d4bb332f35dc575aedf1d9dff84fe857ce5f8cf3b91008948d268', NULL, '2026-11-12 22:15:44'),
(21, 980005, '6ea0b55a144d4bb332f35dc575aedf1d9dff84fe857ce5f8cf3b91008948d268', NULL, '2026-11-12 22:15:45'),
(23, 980007, 'e73caaab34b095366a0747a2fdd35f38241408634a39ec502463dfda13e4732a', NULL, '2026-11-12 23:25:42');

-- --------------------------------------------------------

--
-- Table structure for table `algorithm_scores`
--

CREATE TABLE `algorithm_scores` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `target_id` int(10) UNSIGNED NOT NULL,
  `interest_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `location_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `demographic_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `dwell_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `search_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `activity_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `quality_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `computed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `algorithm_scores`
--

INSERT INTO `algorithm_scores` (`user_id`, `target_id`, `interest_score`, `location_score`, `demographic_score`, `dwell_score`, `search_score`, `activity_score`, `quality_score`, `total_score`, `computed_at`) VALUES
(2, 9030, 30.00, 68.07, 64.00, 50.00, 50.00, 45.00, 82.28, 51.83, '2026-11-12 10:02:51'),
(9001, 9002, 0.00, 0.00, 83.13, 34.93, 50.00, 20.00, 98.05, 29.61, '2026-11-12 22:25:05'),
(9001, 9003, 16.67, 0.00, 68.00, 38.95, 50.00, 20.00, 79.80, 31.20, '2026-11-12 22:25:05'),
(9001, 9004, 33.33, 0.00, 81.25, 65.73, 50.00, 20.00, 98.05, 42.28, '2026-11-12 22:25:05'),
(9001, 9005, 16.67, 22.13, 68.00, 31.07, 50.00, 20.00, 79.80, 34.44, '2026-11-12 22:25:05'),
(9001, 9006, 16.67, 9.76, 73.75, 68.59, 50.00, 20.00, 98.05, 39.37, '2026-11-12 22:25:05'),
(9001, 9007, 16.67, 0.00, 83.13, 49.97, 50.00, 20.00, 98.05, 36.03, '2026-11-12 22:25:05'),
(9001, 9008, 33.33, 0.02, 64.25, 47.54, 50.00, 20.00, 79.80, 36.10, '2026-11-12 22:25:05'),
(9001, 9009, 33.33, 8.72, 100.00, 44.95, 50.00, 20.00, 98.05, 43.72, '2026-11-12 22:25:05'),
(9001, 9010, 0.00, 0.00, 100.00, 46.39, 50.00, 20.00, 79.80, 32.95, '2026-11-12 22:25:05'),
(9001, 9011, 16.67, 16.90, 83.00, 52.56, 50.00, 20.00, 98.05, 39.78, '2026-11-12 22:25:05'),
(9001, 9012, 33.33, 21.76, 94.38, 55.70, 50.00, 20.00, 98.05, 47.10, '2026-11-12 22:25:05'),
(9001, 9013, 16.67, 0.00, 100.00, 34.93, 50.00, 20.00, 79.80, 35.40, '2026-11-12 22:25:05'),
(9001, 9014, 0.00, 0.01, 83.00, 35.37, 50.00, 20.00, 79.80, 28.75, '2026-11-12 22:25:05'),
(9001, 9015, 0.00, 34.48, 83.00, 25.73, 50.00, 20.00, 79.80, 34.20, '2026-11-12 22:25:05'),
(9001, 9016, 16.67, 15.33, 83.00, 14.99, 50.00, 20.00, 79.80, 32.92, '2026-11-12 22:25:05'),
(9001, 9025, 50.00, 100.00, 73.63, 86.74, 50.00, 20.00, 64.68, 74.79, '2026-11-12 22:25:05'),
(9001, 9027, 30.00, 22.13, 85.00, 19.88, 50.00, 5.00, 61.75, 36.25, '2026-11-12 22:25:05'),
(9001, 9028, 30.00, 16.73, 85.00, 19.88, 50.00, 5.00, 62.40, 35.20, '2026-11-12 22:25:05'),
(9001, 9030, 30.00, 20.72, 79.00, 19.88, 50.00, 45.00, 82.28, 40.09, '2026-11-12 22:25:05'),
(9001, 9031, 30.00, 15.68, 77.00, 16.74, 50.00, 45.00, 82.28, 38.31, '2026-11-12 22:25:05'),
(9001, 9032, 30.00, 0.00, 77.00, 2.09, 50.00, 45.00, 64.03, 32.07, '2026-11-12 22:25:05'),
(9002, 9001, 0.00, 0.00, 76.88, 50.00, 50.00, 100.00, 88.78, 38.47, '2026-10-16 13:13:18'),
(9002, 9003, 16.67, 0.00, 38.63, 50.00, 50.00, 85.00, 70.53, 34.49, '2026-10-16 13:13:18'),
(9002, 9004, 16.67, 2.60, 65.00, 50.00, 50.00, 85.00, 88.78, 39.88, '2026-10-16 13:13:18'),
(9002, 9005, 0.00, 0.00, 41.75, 50.00, 50.00, 85.00, 70.53, 30.79, '2026-10-16 13:13:18'),
(9002, 9006, 16.67, 0.00, 57.50, 50.00, 50.00, 85.00, 88.78, 38.23, '2026-10-16 13:13:18'),
(9002, 9007, 16.67, 0.00, 65.00, 50.00, 50.00, 85.00, 88.78, 39.36, '2026-10-16 13:13:18'),
(9002, 9008, 0.00, 0.00, 48.00, 50.00, 50.00, 65.00, 70.53, 29.73, '2026-10-16 13:13:18'),
(9002, 9009, 0.00, 0.00, 100.00, 50.00, 50.00, 85.00, 88.78, 40.44, '2026-10-16 13:13:18'),
(9002, 9010, 0.00, 2.56, 100.00, 50.00, 50.00, 85.00, 70.53, 40.04, '2026-10-16 13:13:18'),
(9002, 9011, 0.00, 0.00, 83.00, 50.00, 50.00, 85.00, 88.78, 37.89, '2026-10-16 13:13:18'),
(9002, 9012, 16.67, 0.00, 98.13, 50.00, 50.00, 85.00, 88.78, 44.33, '2026-10-16 13:13:18'),
(9002, 9013, 16.67, 0.00, 100.00, 50.00, 50.00, 85.00, 70.53, 43.69, '2026-10-16 13:13:18'),
(9002, 9014, 16.67, 0.22, 79.88, 50.00, 50.00, 65.00, 70.53, 38.72, '2026-10-16 13:13:18'),
(9002, 9015, 0.00, 0.00, 83.00, 50.00, 50.00, 65.00, 70.53, 34.98, '2026-10-16 13:13:18'),
(9002, 9016, 16.67, 0.00, 83.00, 50.00, 50.00, 65.00, 70.53, 39.14, '2026-10-16 13:13:18'),
(9002, 9025, 16.67, 0.00, 67.38, 50.00, 50.00, 85.00, 56.07, 49.81, '2026-10-16 13:13:18'),
(9003, 9001, 16.67, 0.00, 61.13, 50.00, 50.00, 100.00, 88.78, 40.27, '2026-10-16 13:13:18'),
(9003, 9002, 16.67, 0.00, 42.38, 50.00, 50.00, 85.00, 88.78, 35.96, '2026-10-16 13:13:18'),
(9003, 9004, 0.00, 0.00, 40.50, 50.00, 50.00, 85.00, 88.78, 31.51, '2026-10-16 13:13:18'),
(9003, 9005, 0.00, 0.00, 65.00, 50.00, 50.00, 85.00, 70.53, 34.28, '2026-10-16 13:13:18'),
(9003, 9006, 0.00, 0.00, 33.00, 50.00, 50.00, 85.00, 88.78, 30.39, '2026-10-16 13:13:18'),
(9003, 9007, 50.00, 0.40, 42.38, 50.00, 50.00, 85.00, 88.78, 44.38, '2026-10-16 13:13:18'),
(9003, 9008, 16.67, 0.00, 57.50, 50.00, 50.00, 65.00, 70.53, 35.32, '2026-10-16 13:13:18'),
(9003, 9009, 0.00, 0.00, 83.00, 50.00, 50.00, 85.00, 88.78, 37.89, '2026-10-16 13:13:18'),
(9003, 9010, 0.00, 0.00, 83.00, 50.00, 50.00, 85.00, 70.53, 36.98, '2026-10-16 13:13:18'),
(9003, 9011, 16.67, 0.00, 98.13, 50.00, 50.00, 85.00, 88.78, 44.32, '2026-10-16 13:13:18'),
(9003, 9012, 16.67, 0.00, 70.50, 50.00, 50.00, 85.00, 88.78, 40.18, '2026-10-16 13:13:18'),
(9003, 9013, 66.67, 100.00, 83.00, 50.00, 50.00, 85.00, 70.53, 73.64, '2026-10-16 13:13:18'),
(9003, 9014, 16.67, 0.00, 100.00, 50.00, 50.00, 65.00, 70.53, 41.69, '2026-10-16 13:13:18'),
(9003, 9015, 0.00, 0.00, 83.00, 50.00, 50.00, 65.00, 70.53, 34.98, '2026-10-16 13:13:18'),
(9003, 9016, 33.33, 0.00, 83.00, 50.00, 50.00, 65.00, 70.53, 43.31, '2026-10-16 13:13:18'),
(9003, 9025, 33.33, 0.00, 93.75, 50.00, 50.00, 85.00, 56.07, 57.93, '2026-10-16 13:13:18'),
(9004, 9001, 33.33, 0.07, 73.75, 50.00, 50.00, 100.00, 88.78, 46.35, '2026-10-16 13:13:18'),
(9004, 9002, 16.67, 8.78, 65.00, 50.00, 50.00, 85.00, 88.78, 41.11, '2026-10-16 13:13:18'),
(9004, 9003, 0.00, 0.00, 35.50, 50.00, 50.00, 85.00, 70.53, 29.85, '2026-10-16 13:13:18'),
(9004, 9005, 0.00, 0.07, 38.63, 50.00, 50.00, 85.00, 70.53, 30.33, '2026-10-16 13:13:18'),
(9004, 9006, 50.00, 0.07, 61.25, 50.00, 50.00, 85.00, 88.78, 47.14, '2026-10-16 13:13:18'),
(9004, 9007, 16.67, 0.00, 61.88, 50.00, 50.00, 85.00, 88.78, 38.89, '2026-10-16 13:13:18'),
(9004, 9008, 16.67, 0.31, 48.00, 50.00, 50.00, 65.00, 70.53, 33.95, '2026-10-16 13:13:18'),
(9004, 9009, 0.00, 0.03, 100.00, 50.00, 50.00, 85.00, 88.78, 40.45, '2026-10-16 13:13:18'),
(9004, 9010, 50.00, 100.00, 100.00, 50.00, 50.00, 85.00, 70.53, 72.03, '2026-10-16 13:13:18'),
(9004, 9011, 16.67, 0.07, 83.00, 50.00, 50.00, 85.00, 88.78, 42.07, '2026-10-16 13:13:18'),
(9004, 9012, 33.33, 0.09, 100.00, 50.00, 50.00, 85.00, 88.78, 48.79, '2026-10-16 13:13:18'),
(9004, 9013, 0.00, 0.00, 96.88, 50.00, 50.00, 85.00, 70.53, 39.06, '2026-10-16 13:13:18'),
(9004, 9014, 0.00, 2.83, 76.75, 50.00, 50.00, 65.00, 70.53, 34.61, '2026-10-16 13:13:18'),
(9004, 9015, 0.00, 0.06, 83.00, 50.00, 50.00, 65.00, 70.53, 34.99, '2026-10-16 13:13:18'),
(9004, 9016, 0.00, 0.07, 83.00, 50.00, 50.00, 65.00, 70.53, 34.99, '2026-10-16 13:13:18'),
(9004, 9025, 33.33, 0.06, 64.25, 50.00, 50.00, 85.00, 56.07, 53.52, '2026-10-16 13:13:18'),
(9005, 9001, 16.67, 72.00, 63.00, 50.00, 50.00, 100.00, 88.78, 62.96, '2026-10-16 13:13:18'),
(9005, 9002, 0.00, 0.00, 44.25, 50.00, 50.00, 85.00, 88.78, 32.08, '2026-10-16 13:13:18'),
(9005, 9003, 0.00, 0.00, 65.00, 50.00, 50.00, 85.00, 70.53, 34.28, '2026-10-16 13:13:18'),
(9005, 9004, 0.00, 0.00, 42.38, 50.00, 50.00, 85.00, 88.78, 31.80, '2026-10-16 13:13:18'),
(9005, 9006, 0.00, 35.44, 34.88, 50.00, 50.00, 85.00, 88.78, 37.76, '2026-10-16 13:13:18'),
(9005, 9007, 0.00, 0.00, 44.25, 50.00, 50.00, 85.00, 88.78, 32.08, '2026-10-16 13:13:18'),
(9005, 9008, 0.00, 0.00, 59.38, 50.00, 50.00, 65.00, 70.53, 31.43, '2026-10-16 13:13:18'),
(9005, 9009, 33.33, 10.15, 83.00, 50.00, 50.00, 85.00, 88.78, 48.25, '2026-10-16 13:13:18'),
(9005, 9010, 0.00, 0.00, 83.00, 50.00, 50.00, 85.00, 70.53, 36.98, '2026-10-16 13:13:18'),
(9005, 9011, 0.00, 71.25, 100.00, 50.00, 50.00, 85.00, 88.78, 54.69, '2026-10-16 13:13:18'),
(9005, 9012, 0.00, 47.63, 69.25, 50.00, 50.00, 85.00, 88.78, 45.35, '2026-10-16 13:13:18'),
(9005, 9013, 0.00, 0.00, 83.00, 50.00, 50.00, 85.00, 70.53, 36.98, '2026-10-16 13:13:18'),
(9005, 9014, 0.00, 0.00, 100.00, 50.00, 50.00, 65.00, 70.53, 37.53, '2026-10-16 13:13:18'),
(9005, 9015, 0.00, 4.14, 83.00, 50.00, 50.00, 65.00, 70.53, 35.81, '2026-10-16 13:13:18'),
(9005, 9016, 0.00, 61.40, 79.88, 50.00, 50.00, 65.00, 70.53, 46.79, '2026-10-16 13:13:18'),
(9005, 9025, 16.67, 16.34, 93.75, 50.00, 50.00, 85.00, 56.07, 57.03, '2026-10-16 13:13:18'),
(9006, 9001, 16.67, 56.63, 61.25, 50.00, 50.00, 100.00, 88.78, 67.62, '2026-10-16 13:13:18'),
(9006, 9002, 16.67, 0.00, 52.50, 50.00, 50.00, 85.00, 88.78, 37.48, '2026-10-16 13:13:18'),
(9006, 9003, 0.00, 0.00, 23.00, 50.00, 50.00, 85.00, 70.53, 27.98, '2026-10-16 13:13:18'),
(9006, 9004, 50.00, 0.00, 58.75, 50.00, 50.00, 85.00, 88.78, 46.75, '2026-10-16 13:13:18'),
(9006, 9005, 0.00, 43.61, 26.13, 50.00, 50.00, 85.00, 70.53, 37.17, '2026-10-16 13:13:18'),
(9006, 9007, 16.67, 0.00, 49.38, 50.00, 50.00, 85.00, 88.78, 37.01, '2026-10-16 13:13:18'),
(9006, 9008, 16.67, 0.00, 38.63, 50.00, 50.00, 65.00, 70.53, 32.49, '2026-10-16 13:13:18'),
(9006, 9009, 16.67, 12.02, 96.88, 50.00, 50.00, 85.00, 88.78, 46.54, '2026-10-16 13:13:18'),
(9006, 9010, 33.33, 0.00, 85.63, 50.00, 50.00, 85.00, 70.53, 45.70, '2026-10-16 13:13:18'),
(9006, 9011, 33.33, 55.99, 83.00, 50.00, 50.00, 85.00, 88.78, 57.42, '2026-10-16 13:13:18'),
(9006, 9012, 33.33, 36.75, 100.00, 50.00, 50.00, 85.00, 88.78, 56.12, '2026-10-16 13:13:18'),
(9006, 9013, 0.00, 0.00, 80.63, 50.00, 50.00, 85.00, 70.53, 36.62, '2026-10-16 13:13:18'),
(9006, 9014, 0.00, 0.01, 58.63, 50.00, 50.00, 65.00, 70.53, 31.32, '2026-10-16 13:13:18'),
(9006, 9015, 16.67, 3.53, 83.00, 50.00, 50.00, 65.00, 70.53, 39.85, '2026-10-16 13:13:18'),
(9006, 9016, 0.00, 63.66, 83.00, 50.00, 50.00, 65.00, 70.53, 47.71, '2026-10-16 13:13:18'),
(9006, 9025, 33.33, 10.40, 58.00, 50.00, 50.00, 85.00, 56.07, 54.65, '2026-10-16 13:13:18'),
(9007, 9001, 16.67, 0.00, 76.88, 50.00, 50.00, 100.00, 88.78, 42.64, '2026-10-16 13:13:18'),
(9007, 9002, 16.67, 0.00, 65.00, 50.00, 50.00, 85.00, 88.78, 39.36, '2026-10-16 13:13:18'),
(9007, 9003, 50.00, 3.65, 38.63, 50.00, 50.00, 85.00, 70.53, 43.55, '2026-10-16 13:13:18'),
(9007, 9004, 16.67, 0.00, 63.13, 50.00, 50.00, 85.00, 88.78, 39.07, '2026-10-16 13:13:18'),
(9007, 9005, 0.00, 0.00, 41.75, 50.00, 50.00, 85.00, 70.53, 30.79, '2026-10-16 13:13:18'),
(9007, 9006, 16.67, 0.00, 55.63, 50.00, 50.00, 85.00, 88.78, 37.95, '2026-10-16 13:13:18'),
(9007, 9008, 16.67, 0.00, 46.13, 50.00, 50.00, 65.00, 70.53, 33.61, '2026-10-16 13:13:18'),
(9007, 9009, 0.00, 0.00, 100.00, 50.00, 50.00, 85.00, 88.78, 40.44, '2026-10-16 13:13:18'),
(9007, 9010, 0.00, 0.00, 100.00, 50.00, 50.00, 85.00, 70.53, 39.53, '2026-10-16 13:13:18'),
(9007, 9011, 16.67, 0.00, 83.00, 50.00, 50.00, 85.00, 88.78, 42.06, '2026-10-16 13:13:18'),
(9007, 9012, 33.33, 0.00, 96.25, 50.00, 50.00, 85.00, 88.78, 48.21, '2026-10-16 13:13:18'),
(9007, 9013, 50.00, 3.62, 100.00, 50.00, 50.00, 85.00, 70.53, 52.75, '2026-10-16 13:13:18'),
(9007, 9014, 0.00, 0.00, 79.88, 50.00, 50.00, 65.00, 70.53, 34.51, '2026-10-16 13:13:18'),
(9007, 9015, 0.00, 0.00, 83.00, 50.00, 50.00, 65.00, 70.53, 34.98, '2026-10-16 13:13:18'),
(9007, 9016, 0.00, 0.00, 83.00, 50.00, 50.00, 65.00, 70.53, 34.98, '2026-10-16 13:13:18'),
(9007, 9025, 33.33, 0.00, 67.38, 50.00, 50.00, 85.00, 56.07, 53.98, '2026-10-16 13:13:18'),
(9008, 9001, 33.33, 4.58, 56.75, 50.00, 50.00, 100.00, 88.78, 60.70, '2026-10-16 13:13:18'),
(9008, 9002, 0.00, 0.49, 48.00, 50.00, 50.00, 85.00, 88.78, 32.74, '2026-10-16 13:13:18'),
(9008, 9003, 16.67, 0.00, 52.50, 50.00, 50.00, 85.00, 70.53, 36.57, '2026-10-16 13:13:18'),
(9008, 9004, 16.67, 1.31, 48.00, 50.00, 50.00, 85.00, 88.78, 37.07, '2026-10-16 13:13:18'),
(9008, 9005, 0.00, 4.83, 55.63, 50.00, 50.00, 85.00, 70.53, 33.84, '2026-10-16 13:13:18'),
(9008, 9006, 16.67, 4.13, 42.38, 50.00, 50.00, 85.00, 88.78, 36.79, '2026-10-16 13:13:18'),
(9008, 9007, 16.67, 0.00, 44.88, 50.00, 50.00, 85.00, 88.78, 36.34, '2026-10-16 13:13:18'),
(9008, 9009, 0.00, 3.12, 83.00, 50.00, 50.00, 85.00, 88.78, 38.51, '2026-10-16 13:13:18'),
(9008, 9010, 16.67, 1.33, 83.00, 50.00, 50.00, 85.00, 70.53, 41.41, '2026-10-16 13:13:18'),
(9008, 9011, 0.00, 4.51, 100.00, 50.00, 50.00, 85.00, 88.78, 41.34, '2026-10-16 13:13:18'),
(9008, 9012, 16.67, 5.59, 83.00, 50.00, 50.00, 85.00, 88.78, 43.17, '2026-10-16 13:13:18'),
(9008, 9013, 16.67, 0.00, 79.88, 50.00, 50.00, 85.00, 70.53, 40.67, '2026-10-16 13:13:18'),
(9008, 9014, 0.00, 9.05, 93.75, 50.00, 50.00, 65.00, 70.53, 38.40, '2026-10-16 13:13:18'),
(9008, 9015, 16.67, 8.79, 83.00, 50.00, 50.00, 65.00, 70.53, 40.90, '2026-10-16 13:13:18'),
(9008, 9016, 0.00, 4.58, 83.00, 50.00, 50.00, 65.00, 70.53, 35.89, '2026-10-16 13:13:18'),
(9008, 9025, 16.67, 6.68, 81.25, 50.00, 50.00, 85.00, 56.07, 53.23, '2026-10-16 13:13:18'),
(9009, 9001, 33.33, 31.21, 100.00, 50.00, 50.00, 100.00, 88.78, 64.52, '2026-10-16 13:13:18'),
(9009, 9002, 0.00, 0.00, 100.00, 50.00, 50.00, 85.00, 88.78, 40.44, '2026-10-16 13:13:18'),
(9009, 9003, 0.00, 0.00, 83.00, 50.00, 50.00, 85.00, 70.53, 36.98, '2026-10-16 13:13:18'),
(9009, 9004, 0.00, 0.00, 100.00, 50.00, 50.00, 85.00, 88.78, 40.44, '2026-10-16 13:13:18'),
(9009, 9005, 33.33, 31.86, 83.00, 50.00, 50.00, 85.00, 70.53, 51.68, '2026-10-16 13:13:18'),
(9009, 9006, 16.67, 26.61, 98.13, 50.00, 50.00, 85.00, 88.78, 49.65, '2026-10-16 13:13:18'),
(9009, 9007, 0.00, 0.00, 100.00, 50.00, 50.00, 85.00, 88.78, 40.44, '2026-10-16 13:13:18'),
(9009, 9008, 0.00, 0.10, 83.00, 50.00, 50.00, 65.00, 70.53, 35.00, '2026-10-16 13:13:18'),
(9009, 9010, 16.67, 0.00, 65.00, 50.00, 50.00, 85.00, 70.53, 38.44, '2026-10-16 13:13:18'),
(9009, 9011, 0.00, 32.45, 48.00, 50.00, 50.00, 85.00, 88.78, 39.13, '2026-10-16 13:13:18'),
(9009, 9012, 16.67, 22.02, 58.75, 50.00, 50.00, 85.00, 88.78, 42.82, '2026-10-16 13:13:18'),
(9009, 9013, 0.00, 0.00, 65.00, 50.00, 50.00, 85.00, 70.53, 34.28, '2026-10-16 13:13:18'),
(9009, 9014, 33.33, 0.11, 46.13, 50.00, 50.00, 65.00, 70.53, 37.80, '2026-10-16 13:13:18'),
(9009, 9015, 16.67, 12.01, 48.00, 50.00, 50.00, 65.00, 70.53, 36.30, '2026-10-16 13:13:18'),
(9009, 9016, 0.00, 28.80, 44.88, 50.00, 50.00, 65.00, 70.53, 35.02, '2026-10-16 13:13:18'),
(9009, 9025, 16.67, 21.84, 56.75, 50.00, 50.00, 85.00, 56.07, 52.58, '2026-10-16 13:13:18'),
(9010, 9001, 0.00, 0.07, 100.00, 50.00, 50.00, 100.00, 88.78, 41.95, '2026-10-16 13:13:18'),
(9010, 9002, 0.00, 8.70, 100.00, 50.00, 50.00, 85.00, 88.78, 42.18, '2026-10-16 13:13:18'),
(9010, 9003, 0.00, 0.00, 83.00, 50.00, 50.00, 85.00, 70.53, 36.98, '2026-10-16 13:13:18'),
(9010, 9004, 50.00, 100.00, 100.00, 50.00, 50.00, 85.00, 88.78, 72.94, '2026-10-16 13:13:18'),
(9010, 9005, 0.00, 0.07, 83.00, 50.00, 50.00, 85.00, 70.53, 36.99, '2026-10-16 13:13:18'),
(9010, 9006, 33.33, 0.08, 89.38, 50.00, 50.00, 85.00, 88.78, 47.19, '2026-10-16 13:13:18'),
(9010, 9007, 0.00, 0.00, 100.00, 50.00, 50.00, 85.00, 88.78, 40.44, '2026-10-16 13:13:18'),
(9010, 9008, 16.67, 0.31, 83.00, 50.00, 50.00, 65.00, 70.53, 39.21, '2026-10-16 13:13:18'),
(9010, 9009, 16.67, 0.03, 65.00, 50.00, 50.00, 85.00, 88.78, 39.36, '2026-10-16 13:13:18'),
(9010, 9011, 0.00, 0.07, 48.00, 50.00, 50.00, 85.00, 88.78, 32.65, '2026-10-16 13:13:18'),
(9010, 9012, 16.67, 0.09, 53.75, 50.00, 50.00, 85.00, 88.78, 37.69, '2026-10-16 13:13:18'),
(9010, 9013, 0.00, 0.00, 65.00, 50.00, 50.00, 85.00, 70.53, 34.28, '2026-10-16 13:13:18'),
(9010, 9014, 33.33, 2.88, 48.00, 50.00, 50.00, 65.00, 70.53, 38.64, '2026-10-16 13:13:18'),
(9010, 9015, 0.00, 0.07, 44.88, 50.00, 50.00, 65.00, 70.53, 29.27, '2026-10-16 13:13:18'),
(9010, 9016, 0.00, 0.07, 41.75, 50.00, 50.00, 65.00, 70.53, 28.80, '2026-10-16 13:13:18'),
(9010, 9025, 16.67, 0.06, 59.88, 50.00, 50.00, 85.00, 56.07, 48.70, '2026-10-16 13:13:18'),
(9011, 9001, 16.67, 100.00, 83.00, 50.00, 50.00, 100.00, 88.78, 79.56, '2026-10-16 13:13:18'),
(9011, 9002, 0.00, 0.00, 83.00, 50.00, 50.00, 85.00, 88.78, 37.89, '2026-10-16 13:13:18'),
(9011, 9003, 16.67, 0.00, 96.88, 50.00, 50.00, 85.00, 70.53, 43.22, '2026-10-16 13:13:18'),
(9011, 9004, 16.67, 0.00, 83.00, 50.00, 50.00, 85.00, 88.78, 42.06, '2026-10-16 13:13:18'),
(9011, 9005, 0.00, 76.25, 100.00, 50.00, 50.00, 85.00, 70.53, 54.78, '2026-10-16 13:13:18'),
(9011, 9006, 33.33, 55.99, 83.00, 50.00, 50.00, 85.00, 88.78, 57.42, '2026-10-16 13:13:18'),
(9011, 9007, 16.67, 0.00, 83.00, 50.00, 50.00, 85.00, 88.78, 42.06, '2026-10-16 13:13:18'),
(9011, 9008, 0.00, 0.00, 100.00, 50.00, 50.00, 65.00, 70.53, 37.53, '2026-10-16 13:13:18'),
(9011, 9009, 0.00, 16.51, 48.00, 50.00, 50.00, 85.00, 88.78, 35.94, '2026-10-16 13:13:18'),
(9011, 9010, 0.00, 0.00, 48.00, 50.00, 50.00, 85.00, 70.53, 31.73, '2026-10-16 13:13:18'),
(9011, 9012, 0.00, 48.69, 48.00, 50.00, 50.00, 85.00, 88.78, 42.38, '2026-10-16 13:13:18'),
(9011, 9013, 16.67, 0.00, 46.13, 50.00, 50.00, 85.00, 70.53, 35.61, '2026-10-16 13:13:18'),
(9011, 9014, 0.00, 0.01, 61.25, 50.00, 50.00, 65.00, 70.53, 31.72, '2026-10-16 13:13:18'),
(9011, 9015, 0.00, 5.97, 48.00, 50.00, 50.00, 65.00, 70.53, 30.92, '2026-10-16 13:13:18'),
(9011, 9016, 0.00, 81.58, 48.00, 50.00, 50.00, 65.00, 70.53, 46.04, '2026-10-16 13:13:18'),
(9011, 9025, 50.00, 17.92, 70.63, 50.00, 50.00, 85.00, 56.07, 62.21, '2026-10-16 13:13:18'),
(9012, 9001, 33.33, 51.58, 90.63, 50.00, 50.00, 100.00, 88.78, 67.18, '2026-10-16 13:13:18'),
(9012, 9002, 16.67, 0.00, 96.88, 50.00, 50.00, 85.00, 88.78, 44.14, '2026-10-16 13:13:18'),
(9012, 9003, 16.67, 0.00, 65.50, 50.00, 50.00, 85.00, 70.53, 38.52, '2026-10-16 13:13:18'),
(9012, 9004, 33.33, 0.00, 100.00, 50.00, 50.00, 85.00, 88.78, 48.77, '2026-10-16 13:13:18'),
(9012, 9005, 0.00, 55.24, 66.75, 50.00, 50.00, 85.00, 70.53, 45.59, '2026-10-16 13:13:18'),
(9012, 9006, 33.33, 36.75, 100.00, 50.00, 50.00, 85.00, 88.78, 56.12, '2026-10-16 13:13:18'),
(9012, 9007, 33.33, 0.00, 93.75, 50.00, 50.00, 85.00, 88.78, 47.84, '2026-10-16 13:13:18'),
(9012, 9008, 16.67, 0.01, 83.00, 50.00, 50.00, 65.00, 70.53, 39.15, '2026-10-16 13:13:18'),
(9012, 9009, 16.67, 8.89, 61.25, 50.00, 50.00, 85.00, 88.78, 40.57, '2026-10-16 13:13:18'),
(9012, 9010, 16.67, 0.00, 56.25, 50.00, 50.00, 85.00, 70.53, 37.13, '2026-10-16 13:13:18'),
(9012, 9011, 0.00, 48.69, 48.00, 50.00, 50.00, 85.00, 88.78, 42.38, '2026-10-16 13:13:18'),
(9012, 9013, 16.67, 0.00, 51.25, 50.00, 50.00, 85.00, 70.53, 36.38, '2026-10-16 13:13:18'),
(9012, 9014, 0.00, 0.02, 29.25, 50.00, 50.00, 65.00, 70.53, 26.92, '2026-10-16 13:13:18'),
(9012, 9015, 16.67, 8.67, 48.00, 50.00, 50.00, 65.00, 70.53, 35.63, '2026-10-16 13:13:18'),
(9012, 9016, 0.00, 53.03, 46.13, 50.00, 50.00, 65.00, 70.53, 40.05, '2026-10-16 13:13:18'),
(9012, 9025, 16.67, 23.38, 41.13, 50.00, 50.00, 85.00, 56.07, 50.55, '2026-10-16 13:13:18'),
(9013, 9001, 16.67, 0.00, 100.00, 50.00, 50.00, 100.00, 88.78, 46.11, '2026-10-16 13:13:18'),
(9013, 9002, 16.67, 0.00, 100.00, 50.00, 50.00, 85.00, 88.78, 44.61, '2026-10-16 13:13:18'),
(9013, 9003, 66.67, 100.00, 83.00, 50.00, 50.00, 85.00, 70.53, 73.64, '2026-10-16 13:13:18'),
(9013, 9004, 0.00, 0.00, 98.13, 50.00, 50.00, 85.00, 88.78, 40.16, '2026-10-16 13:13:18'),
(9013, 9005, 0.00, 0.00, 83.00, 50.00, 50.00, 85.00, 70.53, 36.98, '2026-10-16 13:13:18'),
(9013, 9006, 0.00, 0.00, 84.38, 50.00, 50.00, 85.00, 88.78, 38.10, '2026-10-16 13:13:18'),
(9013, 9007, 50.00, 6.29, 100.00, 50.00, 50.00, 85.00, 88.78, 54.20, '2026-10-16 13:13:18'),
(9013, 9008, 16.67, 0.00, 81.13, 50.00, 50.00, 65.00, 70.53, 38.86, '2026-10-16 13:13:18'),
(9013, 9009, 0.00, 0.00, 65.00, 50.00, 50.00, 85.00, 88.78, 35.19, '2026-10-16 13:13:18'),
(9013, 9010, 0.00, 0.00, 65.00, 50.00, 50.00, 85.00, 70.53, 34.28, '2026-10-16 13:13:18'),
(9013, 9011, 16.67, 0.00, 44.88, 50.00, 50.00, 85.00, 88.78, 36.34, '2026-10-16 13:13:18'),
(9013, 9012, 16.67, 0.00, 48.75, 50.00, 50.00, 85.00, 88.78, 36.92, '2026-10-16 13:13:18'),
(9013, 9014, 0.00, 0.00, 48.00, 50.00, 50.00, 65.00, 70.53, 29.73, '2026-10-16 13:13:18'),
(9013, 9015, 0.00, 0.00, 41.75, 50.00, 50.00, 65.00, 70.53, 28.79, '2026-10-16 13:13:18'),
(9013, 9016, 0.00, 0.00, 38.63, 50.00, 50.00, 65.00, 70.53, 28.32, '2026-10-16 13:13:18'),
(9013, 9025, 16.67, 0.00, 63.00, 50.00, 50.00, 85.00, 56.07, 49.15, '2026-10-16 13:13:18'),
(9014, 9001, 0.00, 0.36, 83.00, 50.00, 50.00, 100.00, 88.78, 47.46, '2026-10-16 13:13:18'),
(9014, 9002, 16.67, 0.22, 81.13, 50.00, 50.00, 85.00, 88.78, 41.82, '2026-10-16 13:13:18'),
(9014, 9003, 16.67, 0.00, 100.00, 50.00, 50.00, 85.00, 70.53, 43.69, '2026-10-16 13:13:18'),
(9014, 9004, 0.00, 0.48, 79.25, 50.00, 50.00, 85.00, 88.78, 37.42, '2026-10-16 13:13:18'),
(9014, 9005, 0.00, 0.36, 100.00, 50.00, 50.00, 85.00, 70.53, 39.60, '2026-10-16 13:13:18'),
(9014, 9006, 0.00, 0.39, 62.38, 50.00, 50.00, 85.00, 88.78, 34.87, '2026-10-16 13:13:18'),
(9014, 9007, 0.00, 0.00, 81.13, 50.00, 50.00, 85.00, 88.78, 37.61, '2026-10-16 13:13:18'),
(9014, 9008, 0.00, 0.82, 96.25, 50.00, 50.00, 65.00, 70.53, 37.13, '2026-10-16 13:13:18'),
(9014, 9009, 33.33, 0.11, 44.88, 50.00, 50.00, 85.00, 88.78, 40.53, '2026-10-16 13:13:18'),
(9014, 9010, 33.33, 0.49, 48.00, 50.00, 50.00, 85.00, 70.53, 40.16, '2026-10-16 13:13:18'),
(9014, 9011, 0.00, 0.35, 58.75, 50.00, 50.00, 85.00, 88.78, 34.32, '2026-10-16 13:13:18'),
(9014, 9012, 0.00, 0.51, 26.75, 50.00, 50.00, 85.00, 88.78, 29.55, '2026-10-16 13:13:18'),
(9014, 9013, 0.00, 0.00, 48.00, 50.00, 50.00, 85.00, 70.53, 31.73, '2026-10-16 13:13:18'),
(9014, 9015, 0.00, 0.33, 38.63, 50.00, 50.00, 65.00, 70.53, 28.39, '2026-10-16 13:13:18'),
(9014, 9016, 16.67, 0.39, 35.50, 50.00, 50.00, 65.00, 70.53, 32.10, '2026-10-16 13:13:18'),
(9014, 9025, 33.33, 0.34, 80.00, 50.00, 50.00, 85.00, 56.07, 55.94, '2026-10-16 13:13:18'),
(9015, 9001, 0.00, 24.62, 83.00, 50.00, 50.00, 100.00, 88.78, 52.31, '2026-10-16 13:13:18'),
(9015, 9002, 0.00, 0.01, 83.00, 50.00, 50.00, 85.00, 88.78, 37.89, '2026-10-16 13:13:18'),
(9015, 9003, 0.00, 0.00, 83.00, 50.00, 50.00, 85.00, 70.53, 36.98, '2026-10-16 13:13:18'),
(9015, 9004, 0.00, 0.01, 83.00, 50.00, 50.00, 85.00, 88.78, 37.89, '2026-10-16 13:13:18'),
(9015, 9005, 0.00, 27.98, 83.00, 50.00, 50.00, 85.00, 70.53, 42.57, '2026-10-16 13:13:18'),
(9015, 9006, 16.67, 18.79, 83.00, 50.00, 50.00, 85.00, 88.78, 45.81, '2026-10-16 13:13:18'),
(9015, 9007, 0.00, 0.00, 83.00, 50.00, 50.00, 85.00, 88.78, 37.89, '2026-10-16 13:13:18'),
(9015, 9008, 16.67, 2.04, 83.00, 50.00, 50.00, 65.00, 70.53, 39.55, '2026-10-16 13:13:18'),
(9015, 9009, 16.67, 18.35, 48.00, 50.00, 50.00, 85.00, 88.78, 40.48, '2026-10-16 13:13:18'),
(9015, 9010, 0.00, 0.02, 46.13, 50.00, 50.00, 85.00, 70.53, 31.45, '2026-10-16 13:13:18'),
(9015, 9011, 0.00, 24.44, 48.00, 50.00, 50.00, 85.00, 88.78, 37.53, '2026-10-16 13:13:18'),
(9015, 9012, 16.67, 29.44, 48.00, 50.00, 50.00, 85.00, 88.78, 42.69, '2026-10-16 13:13:18'),
(9015, 9013, 0.00, 0.00, 44.25, 50.00, 50.00, 85.00, 70.53, 31.16, '2026-10-16 13:13:18'),
(9015, 9014, 0.00, 1.04, 42.38, 50.00, 50.00, 65.00, 70.53, 29.09, '2026-10-16 13:13:18'),
(9015, 9016, 0.00, 23.53, 48.00, 50.00, 50.00, 65.00, 70.53, 34.43, '2026-10-16 13:13:18'),
(9015, 9025, 33.33, 57.37, 56.75, 50.00, 50.00, 85.00, 56.07, 63.86, '2026-10-16 13:13:18'),
(9016, 9001, 16.67, 100.00, 83.00, 50.00, 50.00, 100.00, 88.78, 71.56, '2026-10-16 13:13:18'),
(9016, 9002, 16.67, 0.00, 83.00, 50.00, 50.00, 85.00, 88.78, 42.06, '2026-10-16 13:13:18'),
(9016, 9003, 33.33, 0.00, 83.00, 50.00, 50.00, 85.00, 70.53, 45.31, '2026-10-16 13:13:18'),
(9016, 9004, 0.00, 0.00, 83.00, 50.00, 50.00, 85.00, 88.78, 37.89, '2026-10-16 13:13:18'),
(9016, 9005, 0.00, 72.24, 81.13, 50.00, 50.00, 85.00, 70.53, 51.14, '2026-10-16 13:13:18'),
(9016, 9006, 0.00, 68.63, 83.00, 50.00, 50.00, 85.00, 88.78, 51.62, '2026-10-16 13:13:18'),
(9016, 9007, 0.00, 0.00, 83.00, 50.00, 50.00, 85.00, 88.78, 37.89, '2026-10-16 13:13:18'),
(9016, 9008, 0.00, 0.03, 83.00, 50.00, 50.00, 65.00, 70.53, 34.98, '2026-10-16 13:13:18'),
(9016, 9009, 0.00, 19.02, 46.13, 50.00, 50.00, 85.00, 88.78, 36.16, '2026-10-16 13:13:18'),
(9016, 9010, 0.00, 0.00, 44.25, 50.00, 50.00, 85.00, 70.53, 31.16, '2026-10-16 13:13:18'),
(9016, 9011, 0.00, 84.40, 48.00, 50.00, 50.00, 85.00, 88.78, 49.52, '2026-10-16 13:13:18'),
(9016, 9012, 0.00, 58.94, 44.88, 50.00, 50.00, 85.00, 88.78, 43.96, '2026-10-16 13:13:18'),
(9016, 9013, 0.00, 0.00, 42.38, 50.00, 50.00, 85.00, 70.53, 30.88, '2026-10-16 13:13:18'),
(9016, 9014, 16.67, 0.06, 40.50, 50.00, 50.00, 65.00, 70.53, 32.78, '2026-10-16 13:13:18'),
(9016, 9015, 0.00, 8.97, 48.00, 50.00, 50.00, 65.00, 70.53, 31.52, '2026-10-16 13:13:18'),
(9016, 9025, 16.67, 22.09, 59.88, 50.00, 50.00, 85.00, 56.07, 53.10, '2026-10-16 13:13:18'),
(9025, 9001, 50.00, 42.54, 77.38, 50.00, 50.00, 100.00, 88.78, 59.55, '2026-10-16 12:12:22'),
(9025, 9002, 16.67, 0.01, 73.63, 50.00, 50.00, 85.00, 88.78, 40.65, '2026-10-16 12:12:22'),
(9025, 9003, 33.33, 0.00, 96.25, 50.00, 50.00, 85.00, 70.53, 47.30, '2026-10-16 12:12:22'),
(9025, 9004, 33.33, 0.01, 71.75, 50.00, 50.00, 85.00, 88.78, 44.54, '2026-10-16 12:12:22'),
(9025, 9005, 16.67, 48.46, 96.25, 50.00, 50.00, 85.00, 70.53, 52.82, '2026-10-16 12:12:22'),
(9025, 9006, 33.33, 32.24, 68.00, 50.00, 50.00, 85.00, 88.78, 50.42, '2026-10-16 12:12:22'),
(9025, 9007, 33.33, 0.00, 73.63, 50.00, 50.00, 85.00, 88.78, 44.82, '2026-10-16 12:12:22'),
(9025, 9008, 16.67, 1.32, 88.75, 50.00, 50.00, 65.00, 70.53, 40.27, '2026-10-16 12:12:22'),
(9025, 9009, 16.67, 29.61, 64.25, 50.00, 50.00, 85.00, 88.78, 45.17, '2026-10-16 12:12:22'),
(9025, 9010, 16.67, 0.01, 66.13, 50.00, 50.00, 85.00, 70.53, 38.62, '2026-10-16 12:12:22'),
(9025, 9011, 50.00, 42.33, 79.38, 50.00, 50.00, 85.00, 88.78, 58.31, '2026-10-16 12:12:22'),
(9025, 9012, 16.67, 48.36, 54.88, 50.00, 50.00, 85.00, 88.78, 47.51, '2026-10-16 12:12:22'),
(9025, 9013, 16.67, 0.00, 68.00, 50.00, 50.00, 85.00, 70.53, 38.89, '2026-10-16 12:12:22'),
(9025, 9014, 33.33, 1.05, 85.00, 50.00, 50.00, 65.00, 70.53, 43.82, '2026-10-16 12:12:22'),
(9025, 9015, 33.33, 57.37, 64.25, 50.00, 50.00, 65.00, 70.53, 51.97, '2026-10-16 12:12:22'),
(9025, 9016, 16.67, 40.41, 66.13, 50.00, 50.00, 65.00, 70.53, 44.69, '2026-10-16 12:12:22');

-- --------------------------------------------------------

--
-- Table structure for table `analytics_daily`
--

CREATE TABLE `analytics_daily` (
  `stat_date` date NOT NULL,
  `metric` varchar(40) NOT NULL,
  `value` bigint(20) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `analytics_daily`
--

INSERT INTO `analytics_daily` (`stat_date`, `metric`, `value`) VALUES
('2026-10-15', 'dau', 17),
('2026-10-15', 'host_signups', 0),
('2026-10-15', 'likes', 0),
('2026-10-15', 'matches', 0),
('2026-10-15', 'messages', 0),
('2026-10-15', 'pay_count', 0),
('2026-10-15', 'reports', 0),
('2026-10-15', 'rev_all', 0),
('2026-10-15', 'rev_booking', 0),
('2026-10-15', 'rev_boost', 0),
('2026-10-15', 'rev_gift', 0),
('2026-10-15', 'rev_other', 0),
('2026-10-15', 'rev_subscription', 0),
('2026-10-15', 'signups', 0),
('2026-10-16', 'dau', 7),
('2026-10-16', 'host_signups', 2),
('2026-10-16', 'likes', 12),
('2026-10-16', 'matches', 1),
('2026-10-16', 'messages', 11),
('2026-10-16', 'pay_count', 7),
('2026-10-16', 'reports', 0),
('2026-10-16', 'rev_all', 2699),
('2026-10-16', 'rev_booking', 2000),
('2026-10-16', 'rev_boost', 50),
('2026-10-16', 'rev_gift', 150),
('2026-10-16', 'rev_other', 299),
('2026-10-16', 'rev_subscription', 200),
('2026-10-16', 'signups', 3);

-- --------------------------------------------------------

--
-- Table structure for table `bio_suggestions`
--

CREATE TABLE `bio_suggestions` (
  `id` int(10) UNSIGNED NOT NULL,
  `text` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bio_suggestions`
--

INSERT INTO `bio_suggestions` (`id`, `text`, `sort_order`, `is_active`) VALUES
(1, 'Weekend hiker, weekday coffee addict.', 1, 1),
(2, 'Looking for good conversation and someone who laughs easily.', 2, 1),
(3, 'I will always say yes to nyama choma.', 3, 1),
(4, 'New in town and keen to meet people who know the best spots.', 4, 1),
(5, 'Big on family, faith and Sunday lunches.', 5, 1),
(6, 'Part dreamer, part planner. Mostly hungry.', 6, 1),
(7, 'Here for real connections, not endless chatting.', 7, 1),
(8, 'Ask me about my last trip.', 8, 1);

-- --------------------------------------------------------

--
-- Table structure for table `blocks`
--

CREATE TABLE `blocks` (
  `blocker_id` int(10) UNSIGNED NOT NULL,
  `blocked_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

CREATE TABLE `blog_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `description` varchar(400) DEFAULT NULL,
  `meta_title` varchar(160) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_categories`
--

INSERT INTO `blog_categories` (`id`, `name`, `slug`, `sort_order`, `description`, `meta_title`) VALUES
(1, 'Dating tips', 'dating-tips', 1, 'Practical advice on first messages, first dates, texting and asking someone out, written for people dating in Kenya.', 'Dating tips and advice for Kenya'),
(2, 'Safety', 'safety', 2, 'How to stay safe when you meet people online: spotting fake profiles, avoiding romance scams, protecting your privacy and meeting for the first time.', 'Online dating safety tips for Kenya'),
(3, 'Community', 'community', 3, 'Friendship, loneliness and finding your people in a new town. Honest ideas for building a circle that lasts.', 'Making friends and finding community in Kenya'),
(4, 'Profiles and photos', 'profiles', 4, 'How to write a bio that sounds like you, choose photos that get noticed and build the trust that gets you replies.', 'Dating profile and photo tips'),
(5, 'Relationships', 'relationships', 5, 'Green flags, red flags, long distance, introducing family and knowing when you are ready, for every stage of getting to know someone.', 'Relationship advice for Kenyans'),
(6, 'Dating in Kenya', 'kenya-guides', 6, 'Local guides: online dating in Kenya, etiquette, first date ideas around the country and meeting people abroad safely.', 'Dating in Kenya: guides and etiquette');

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `author_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `excerpt` varchar(400) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `content` mediumtext DEFAULT NULL,
  `cover_path` varchar(255) DEFAULT NULL,
  `cover_alt` varchar(160) DEFAULT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` varchar(300) DEFAULT NULL,
  `focus_keyword` varchar(80) DEFAULT NULL,
  `faq` mediumtext DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `canonical_url` varchar(300) DEFAULT NULL,
  `noindex` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `category_id`, `author_id`, `title`, `slug`, `excerpt`, `tags`, `content`, `cover_path`, `cover_alt`, `meta_title`, `meta_description`, `focus_keyword`, `faq`, `og_image`, `canonical_url`, `noindex`, `status`, `views`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'First message ideas that get replies: 5 openers that work', 'five-first-message-openers', 'Skip \"hey\". These five first message ideas are short, specific and easy to answer, with real examples you can copy and make your own.', 'first message, conversation starters, online dating, dating tips, openers, Kenya', '<p>Most conversations online never get past the first line. If you have ever wondered why your messages go unanswered, the problem is usually not you. It is the opener. These first message ideas are built around one simple truth: people reply to messages that are easy and pleasant to answer, and they ignore the ones that make them work.</p><p>Below you will find five openers that work, real examples to adapt, the mistakes that get messages ignored, and a few habits that keep a chat going once it has started. Everything here applies whether you are looking for a partner, a date or a new friend in your town.</p><h2>Why most first messages fail</h2><p>Think about the person on the other end. They may have received a dozen messages today, and most of them look the same: \"hi\", \"hey beautiful\", \"how are you?\", \"can we be friends?\". None of these say anything about them, and none are fun to answer. Replying to \"hey\" means starting a conversation from nothing, so many people simply do not.</p><p>A good first message does three jobs at once. It shows you actually looked at their profile, it gives them something easy to talk about, and it lets them see a little of your personality. If your message does those three things in two or three lines, you are already ahead of most people.</p><h2>Pick something from their profile</h2><p>This is the most reliable opener there is. Read their bio and look at their photos and interests, then choose one real detail and ask about it. It proves you are not sending the same line to everyone.</p><ul>\r\n<li>\"You mentioned hiking in Ngong. Which trail is your favourite?\"</li>\r\n<li>\"Your photo at the coast looks great. Was that Diani or Watamu?\"</li>\r\n<li>\"I saw you love baking. What is the one thing you make that always goes first?\"</li>\r\n</ul><p>Notice that each example asks about something the person enjoys talking about. People like to share what they love, so these questions are a pleasure to answer. Avoid commenting only on looks. A compliment on a smile or an outfit is fine, but pair it with a question, otherwise it is easy to thank you and stop there.</p><h2>Ask a light either-or question</h2><p>Either-or questions are quick to answer, which is why they work so well, especially for people who find small talk tiring. Instead of asking something broad, give two simple choices.</p><ul>\r\n<li>\"Coffee or tea in the morning? I need to know before we go any further.\"</li>\r\n<li>\"Beach weekend or mountain weekend?\"</li>\r\n<li>\"Nyama choma on Saturday or a quiet movie night?\"</li>\r\n</ul><p>The answer takes five seconds, and the follow-up writes itself. If they say beach, you ask which beach. If they say tea, you ask masala or plain. A small question like this gets the ball rolling without any pressure.</p><h2>Share something small about yourself</h2><p>A conversation should feel like a two-way street. After you ask a question, it helps to offer a little of yourself too, so the other person is not being interviewed. Mention a film you watched, a place you tried, or a song you cannot stop playing, and then ask what they think.</p><p>For example: \"I finally tried the new walking trail in Karura this weekend and I am still sore. Do you have a favourite place to get outside in the city?\" That message tells them something real about you, shows you are active, and ends with a question. It is warm, specific and easy to answer.</p><h2>Be playful, not clever</h2><p>A little humour makes you memorable, but try-hard jokes or pick-up lines usually backfire. The goal is to be kind and light, not to perform. A gentle, self-aware joke works better than a line you found online.</p><ul>\r\n<li>\"I have been staring at this message box for ten minutes trying to sound interesting. How am I doing so far?\"</li>\r\n<li>\"Quick poll: is it acceptable to put pineapple on pizza? Your answer decides everything.\"</li>\r\n</ul><p>Keep jokes friendly and never at their expense. If you are not naturally funny, that is fine. Warmth and curiosity are just as attractive, and being yourself will serve you better over time than playing a character.</p><h2>Keep it short</h2><p>A long first message can feel heavy, like a lot of effort to match. Two or three lines is the sweet spot. You are not trying to tell your life story, only to open a door. Save the longer stories for when they have replied and the chat is flowing.</p><p>Also check your spelling and punctuation before you send. You do not need perfect grammar, but a message that is easy to read shows care. Avoid sending several messages in a row before they have replied, because it can feel pushy and it makes it harder for them to know where to start.</p><h2>First message ideas to skip</h2><p>Some openers almost never work, and a few can make a poor first impression or even make someone uncomfortable. Skip these:</p><ul>\r\n<li>A bare \"hi\", \"hey\" or \"hello\" with nothing else.</li>\r\n<li>Comments only about someone\'s body.</li>\r\n<li>Asking for their phone number, WhatsApp or location in the first message.</li>\r\n<li>Copy-and-paste lines that could be sent to anyone.</li>\r\n<li>Anything that asks for money, favours or personal details such as where they live or work.</li>\r\n</ul><p>The last point matters for safety as well as manners. Anyone who asks for money or private details early on is a warning sign. Read our guide on <a href=\"/blog/romance-scams-in-kenya-warning-signs\">romance scams in Kenya</a> to learn what to look for.</p><h2>How to keep the conversation going</h2><p>Once they reply, your job is to keep things relaxed and curious. Answer their question properly, then ask one of your own. Follow the thread they gave you instead of jumping to a new topic. If they say they love cooking, ask what they cooked last, not what their job is.</p><p>Watch the balance. If you are always the one asking, add something about yourself. If you are always the one answering, ask something back. When the chat is going well after a few days, you can suggest moving to a call or meeting somewhere public. Our guide to <a href=\"/blog/how-to-start-a-conversation-online\">starting and keeping a conversation online</a> goes deeper, and the <a href=\"/blog/meet-someone-safely\">checklist for meeting someone safely</a> will help when you are ready.</p><h2>What to do if you do not get a reply</h2><p>Sooner or later, a message will go unanswered. This is normal and it is almost never personal. People get busy, lose interest, or simply do not log in often. Send one friendly follow-up after a few days if you really want to, and then let it go.</p><p>The best use of your energy is to send a better message to the next person and to make your own profile as inviting as possible. Add clear photos, write a bio that sounds like you, and pick a few interests so others have something to ask you about. You can <a href=\"/browse\">browse people near you</a> any time, or <a href=\"/register\">create your free profile</a> in a couple of minutes and try one of these openers today.</p>', 'blog/aeae321826c1e813.jpg', 'A person smiling at a phone while typing a message on a sofa', 'First message ideas that get a reply (with examples)', 'Stuck on what to say? Use these first message ideas and examples to start a real conversation online, and avoid the openers that get ignored.', 'first message ideas', '[{\"q\":\"What is a good first message to send on a dating site?\",\"a\":\"A good first message is short, specific to the other person, and ends with a question that is easy to answer. Mention one detail from their profile, such as a place, hobby or photo, and ask about it. Two or three lines is plenty.\"},{\"q\":\"Is it okay to just say hi or hey?\",\"a\":\"You can, but it rarely works. \\\"Hey\\\" gives the other person nothing to respond to, so they have to do all the work. Add one specific line about them and a simple question, and your reply rate will usually improve.\"},{\"q\":\"How long should I wait for a reply before sending another message?\",\"a\":\"Give it at least a day or two. People check their phones at different times and some only log in a few times a week. If you have heard nothing after several days, send one light follow-up at most, then move on kindly.\"},{\"q\":\"Should women message first or wait for men to start?\",\"a\":\"Anyone can send the first message. There is no rule that one person has to go first, and starting the conversation yourself saves time and shows confidence. On ConnectHub you can message anyone whose profile you like, whatever your gender.\"},{\"q\":\"What should I do if I do not get a reply?\",\"a\":\"Do not take it personally. Many people are busy, not interested, or simply forgot to answer. Send a different, better message to someone else, and keep your profile photos and bio up to date so more people have a reason to reply.\"}]', NULL, NULL, 0, 'published', 4, '2026-10-16 06:16:00', '2026-10-16 06:16:42', '2026-10-16 21:31:53'),
(2, 2, 1, 'How to meet someone from the internet safely', 'meet-someone-safely', 'A practical checklist for a first meeting: video call first, choose a public place, tell a friend, arrange your own transport and know how to leave.', 'safety, first date, meeting online matches, online dating safety, Kenya, dating tips', '<p>Meeting someone for the first time is exciting, and it is also the moment when a little planning pays off the most. This guide shows you how to meet someone from the internet safely, step by step, from the chats before you meet to the message you send when you get home. None of it is complicated, and none of it will spoil the fun. It simply keeps you in charge of the evening.</p><p>You do not need to be afraid of meeting people online. Most people on dating and friendship sites are exactly who they say they are. But because you cannot see everything about a person through a screen, a few habits are worth building into every first meeting, whether it is a romantic date or a new friend in town.</p><h2>Before you meet: do the groundwork</h2><p>Safety starts well before the day. In the days of messaging, pay attention to how the person behaves. Do they answer your questions, or do they dodge them? Do their stories stay the same from one day to the next? Do they respect it when you say you are busy?</p><p>It also helps to look at the profile itself. A verified badge tells you the person has completed an extra check, which is a good sign but not a promise. Read our guide on <a href=\"/blog/how-to-verify-someone-is-real-before-meeting\">how to verify someone is real before meeting</a> for simple steps you can take before you agree to a date.</p><p>Keep personal details light until you trust the person. You can chat about your work in general terms without naming your office, and you can talk about your estate without giving your gate number. Our article on <a href=\"/blog/protect-your-privacy-on-dating-apps\">protecting your privacy on dating apps</a> has more on this.</p><h2>Have a video call first</h2><p>A ten-minute video call is the single most useful step you can take. It confirms that the person looks like their photos, that they are comfortable being seen, and that the voice matches the messages. It also tells you quickly whether you enjoy talking to each other, which saves you a trip if you do not.</p><p>Keep the call casual. Say something like: \"Before we meet, shall we do a quick video call this week? I like to say hello properly first.\" A genuine person will usually say yes, even if they are shy about it. If someone has a new excuse every time, such as a broken camera, bad network or no data bundles, be careful. One excuse is normal. A pattern of excuses is not.</p><h2>How to meet someone from the internet safely: your first-meeting checklist</h2><p>Use this list as a simple routine. You can screenshot it and keep it on your phone.</p><ol>\r\n<li><strong>Choose the place yourself.</strong> Suggest a busy, public spot you already know, such as a well-known cafe in Westlands, a restaurant inside a mall in Nyali, or a popular garden on a weekend afternoon.</li>\r\n<li><strong>Meet in daylight or early evening.</strong> Later nights make everything harder, from getting a ride to thinking clearly.</li>\r\n<li><strong>Tell a friend.</strong> Share the person\'s name, their profile, the place and the time you expect to be done.</li>\r\n<li><strong>Travel on your own.</strong> Take a matatu, a ride-hailing car, a boda boda or drive yourself. Do not accept a pick-up for a first meeting.</li>\r\n<li><strong>Keep your phone charged.</strong> Carry a small power bank if you can, and enough airtime or data to call for help.</li>\r\n<li><strong>Stay sober enough to decide well.</strong> One drink is plenty. Never leave a drink unattended, and do not accept a drink you did not see being poured.</li>\r\n<li><strong>Keep the first meeting short.</strong> An hour or two over coffee is ideal. You can always plan a longer second date.</li>\r\n<li><strong>Do not go anywhere private.</strong> Say no politely to invitations to a home, a lodge or a drive somewhere quiet.</li>\r\n</ol><p>If you are looking for places that work well, our list of <a href=\"/blog/first-date-ideas-in-nairobi\">first date ideas in Nairobi</a> includes relaxed, public options, and there is a companion list of <a href=\"/blog/first-date-ideas-outside-nairobi\">first date ideas outside Nairobi</a> too.</p><h2>Tell a friend and set up a check-in</h2><p>Telling someone where you are going is not a sign of distrust. It is simply what sensible people do. Send a friend a message before you leave, something like: \"Meeting Brian from ConnectHub at Java in Kilimani at 5pm. Should be done by 7. I will text you when I am home.\"</p><p>Agree on a simple signal. For example, if your friend calls you and you say \"I need to go and look after my sister\", that means you want an excuse to leave. Many people also share their live location with one trusted person for the evening. Switch it off when you are home safe.</p><h2>Trust your instincts during the date</h2><p>You will not always be able to say exactly why something feels wrong. That is fine. Your gut notices small things, such as someone who does not match their messages, who gets angry over little things, or who keeps pushing you to do something you said no to.</p><p>Watch for these signs during the meeting:</p><ul>\r\n<li>Their story differs from what they told you online, such as a different job, age or city.</li>\r\n<li>They ignore your boundaries, for example by pressuring you to drink more or move somewhere else.</li>\r\n<li>They ask to borrow money, or mention an emergency that needs cash.</li>\r\n<li>They become angry or rude when you decline something.</li>\r\n<li>They want to know your exact address, your workplace or your family details very quickly.</li>\r\n</ul><p>None of these means you must run, but each one means you should slow down, say no, and consider ending the date.</p><h2>How to leave if you want to</h2><p>You are allowed to leave a date at any time, for any reason, including \"I just do not feel it.\" You do not owe a long explanation. Here are a few short lines you can use:</p><ul>\r\n<li>\"This has been nice, but I am going to head off now. Take care.\"</li>\r\n<li>\"I have an early morning, so I will leave you here. Thanks for coming.\"</li>\r\n<li>\"I do not think this is a match for me, but I wish you well.\"</li>\r\n</ul><p>Say it calmly, stand up, and go. If the person will not let you leave, move towards other people, a security guard or a staff member and ask for help. Call 999 or 112 if you feel unsafe. Having your own transport matters here, because you can walk out and get a ride without needing anything from them.</p><h2>After the date</h2><p>When you are home, text your friend so they know you are safe. Then take a few minutes to think. How did you feel during the date, and how do you feel now? Relaxed and happy is a good sign. Uneasy or drained deserves attention, even if you cannot explain it.</p><p>If you want to see them again, say so warmly. If you do not, a kind and short message is enough: \"Thank you for today. I enjoyed meeting you, but I do not feel a romantic connection. I wish you all the best.\" Our guide on <a href=\"/blog/how-to-handle-rejection-gracefully\">handling rejection gracefully</a> can help with both sides of this conversation.</p><p>If anyone behaves badly, whether before, during or after a meeting, block them and report them. Our article on <a href=\"/blog/how-to-block-and-report-someone-online\">how to block and report someone online</a> walks you through it, and the <a href=\"/safety-tips\">ConnectHub safety tips</a> page is a good quick reference.</p><h2>Meeting someone for the first time as a friend</h2><p>Everything above applies to friendship meetings as well. A new friend can be just as lovely, and just as unknown, as a new date. Suggest a coffee, a walk in a busy park or a group activity such as a game night, tell someone where you are going, and use your own transport. If you are new to a city, our tips on <a href=\"/blog/making-friends-in-a-new-town\">making friends in a new town</a> may help.</p><p>When you are ready, you can <a href=\"/browse\">browse verified profiles near you</a>, or <a href=\"/register\">create your free profile</a> and start talking to people. Take your time, ask questions, and meet only when it feels right for you.</p>', 'blog/869be3a419e44195.jpg', 'Two people having coffee at a busy outdoor cafe in the afternoon', 'How to meet someone from the internet safely', 'Learn how to meet someone from the internet safely with a simple checklist: video call first, public place, own transport, a friend in the loop.', 'meet someone from the internet safely', '[{\"q\":\"Is it safe to meet someone from the internet?\",\"a\":\"Millions of people meet good friends and partners online, and most first meetings go well. The risk is lower when you prepare: talk on video first, meet in a busy public place, tell a friend where you are, and use your own transport. Preparation does not remove all risk, but it puts you in control.\"},{\"q\":\"Where is the best place for a first meeting?\",\"a\":\"Choose a busy public place in daylight, such as a popular cafe, a restaurant inside a mall, or a garden with plenty of other people around. Avoid private homes, lodges, parked cars and quiet spots. Pick somewhere you already know so you are comfortable with the exits and the surroundings.\"},{\"q\":\"Should I tell someone I am going on a date?\",\"a\":\"Yes. Tell a trusted friend or family member the name of the person, the place and the time you expect to finish. Share your live location if you are comfortable doing so, and agree to send a message when you are home safe.\"},{\"q\":\"What should I do if I feel uncomfortable on a date?\",\"a\":\"Trust that feeling. You do not owe anyone an explanation or a second drink. Say you need to leave, go to a staff member or a busy area if needed, and use your own transport to get away. If you ever feel in danger, call 999 or 112.\"},{\"q\":\"Should I let them pick me up or drop me home?\",\"a\":\"For the first few meetings it is safer to travel on your own, whether by matatu, ride-hailing app, boda boda or your own car. Having your own way home means you can leave whenever you want, and nobody learns your home address before you trust them.\"},{\"q\":\"How soon is too soon to meet in person?\",\"a\":\"There is no fixed rule. Many people feel comfortable after a week or two of chatting and at least one video call. If someone pushes to meet immediately, or refuses every video call, treat that as a reason to slow down.\"}]', NULL, NULL, 0, 'published', 5, '2026-10-16 06:16:00', '2026-10-16 06:16:42', '2026-10-16 20:30:35'),
(3, 3, 1, 'Making friends in a new town: a practical guide for Kenya', 'making-friends-in-a-new-town', 'New job, transfer or campus in another town? Here is how to build real friendships from scratch with routines, clubs, faith groups, sports and patience.', 'making friends, new town, moving for work, community, friendship, Kenya', '<p>Moving to a new place for a job, a transfer or university is exciting, and it can also feel very quiet. Making friends in a new town takes longer than most people expect, especially when your family, old classmates and your usual estate are far away. The good news is that friendship is a skill you can practise, not luck. This guide covers routines, clubs, volunteering, faith communities, sports, co-working spaces and events, with plenty of room to be patient with yourself.</p><p>Whether you have just arrived in Nakuru, Eldoret, Kisumu, Nyeri, Mombasa or a small market town, the same basic ideas apply. You do not need to become a different person. You only need to put yourself in the same places often enough for people to get to know you.</p><h2>Why the first months feel so quiet</h2><p>At home, friendships built up slowly over years without you noticing. A classmate lived two plots away. A cousin dropped by on Sunday. In a new town none of that exists yet, and every plan has to be started by you. That is tiring, and it is completely normal.</p><p>It helps to understand what is really going on. You are not unlikeable and the town is not unfriendly. Most people already have full routines and are not looking out for newcomers, so the first step usually has to come from you. Once you accept that, the job becomes simple: show up in the same places until faces become names, and names become friends.</p><h2>Start with a routine, not a big plan</h2><p>Many newcomers wait for the perfect friend group to appear. A better approach is to build a small routine that puts you near the same people again and again. Friendship grows from repeated, easy contact.</p><ul>\r\n<li>Buy your morning chai or mandazi from the same kiosk and learn the vendor\'s name.</li>\r\n<li>Walk or jog the same route at the same time and greet the regulars.</li>\r\n<li>Do your Saturday shopping at the same market and chat with the same stallholders.</li>\r\n<li>Eat lunch at the same place near work instead of at your desk.</li>\r\n<li>Use the same stage when you take a matatu, and say good morning to the people you see daily.</li>\r\n</ul><p>None of this feels like making friends, which is exactly why it works. After a few weeks you will have a handful of friendly faces, and the town will already feel less strange.</p><h2>Making friends in a new town: join something that meets weekly</h2><p>A group that meets on a fixed day does the hard work for you. You do not have to invent a reason to see the same people, because the meeting is the reason. Look for something that matches something you already enjoy.</p><ul>\r\n<li>Sports: Sunday football, volleyball, netball, basketball, a running club or a cycling group.</li>\r\n<li>Outdoors: hiking groups, nature walks, tree planting days.</li>\r\n<li>Creative: choir, drama, dance classes, photography walks, a book club.</li>\r\n<li>Learning: language classes, a coding group, a debate club, a toastmasters style speaking group.</li>\r\n</ul><p>Ask colleagues, shopkeepers and neighbours what is on in town. Notice boards, WhatsApp groups and social media pages for your town are also useful. Commit to going four times before you decide whether it suits you. The first visit is nearly always awkward, and the fourth is where the friendliness begins.</p><h2>Church, mosque and community groups</h2><p>For many Kenyans, faith communities are one of the quickest ways to feel at home. A fellowship, youth group, choir, women\'s or men\'s group, or a mosque study circle gives you a ready-made community that meets regularly and often looks after newcomers. If you are already part of a faith, find a congregation near where you live and introduce yourself to a leader or a friendly face after the service.</p><p>If you are not religious, you can still find community through clan or county associations, alumni groups, or professional and cultural societies. Many towns have groups for people from the same home area who now live elsewhere, and they can be a warm place to land. Only join what feels right for you, and remember that you are free to try a group and move on.</p><h2>Give your time through volunteering</h2><p>Volunteering is one of the best ways to meet people, because you are all focused on one task, so conversation comes easily. Look for a clean-up day, a school or children\'s home visit, a mentorship programme for students, an animal welfare group or a community project run by a local organisation. Ask what help they need and offer a regular slot, even if it is only one Saturday a month.</p><p>People who give their time tend to be kind and open, and they remember who turns up. Working side by side also lets you get to know someone without the pressure of sitting across from them and making conversation.</p><h2>Work, study spaces and co-working</h2><p>If you have moved for a job, your colleagues are the obvious first circle. Say yes when someone invites you for lunch, tea or a Friday meal, even if you are tired. Offer to join a plan instead of waiting for an invitation. Staying friendly with people at work does not mean sharing everything, but small invitations add up.</p><p>If you work remotely or run a small business, a co-working space or a cafe with good internet can solve two problems at once. You will see the same people most days, and a casual chat over a plug socket often turns into a coffee. Students can do the same by joining a study group, a faculty club or a hostel activity instead of staying in their room.</p><h2>Say yes to events and make the first move</h2><p>Towns have more going on than newcomers realise: church bazaars, charity runs, agricultural shows, music nights, football viewings, cultural festivals, weekend markets. Go to one a week if you can. Going alone is fine, and many people are friendlier to someone on their own than to a loud group.</p><p>When you meet someone you like, make the next step easy and low pressure. Here are a few lines that work:</p><ul>\r\n<li>\"I am heading for a coffee after this. Want to come?\"</li>\r\n<li>\"A few of us are going to watch the match on Saturday. Join us if you are free.\"</li>\r\n<li>\"Can I get your number? I would like to tell you about that hiking group.\"</li>\r\n</ul><p>Most people say yes, and the ones who say no usually have a real reason. Do not take it personally. Keep inviting different people and you will soon find the ones who invite you back.</p><h2>Turn acquaintances into friends</h2><p>Meeting someone is the start, not the finish. A friendship deepens when you do small things consistently. Follow up the day after a nice conversation. Remember a detail, such as a job interview or an exam, and ask how it went. Suggest something simple, like a walk, a meal at a local joint or nyama choma on a payday weekend.</p><p>Share a little about yourself too, and not only the polished version. Letting someone see that you miss home, or that you are finding the town hard, often invites them to open up as well. Be reliable, be on time and keep your word. These ordinary habits are what people remember.</p><h2>Be patient, and look after yourself</h2><p>It is easy to feel discouraged when weeks pass and your phone stays quiet. Remember that real friendship has stages. First you are a familiar face, then someone people greet, then someone they invite, and finally someone they call when things go wrong. Each stage takes time.</p><p>Keep in touch with people back home through calls and voice notes, because old friends are a comfort while you build new ones. Look after the basics too: eat properly, sleep, move around, and get out of your room even on days you do not feel like it. If the quiet starts to feel heavy and does not lift, speak to someone you trust or a counsellor. You can read more in our guide to <a href=\"/blog/feeling-lonely-in-the-city\">feeling lonely in the city</a>.</p><h2>Use your phone to meet people nearby</h2><p>Technology can speed things up. Local social groups and platforms like ConnectHub let you find people in your area who also want company, whether that is friendship, a chat or something more. You can <a href=\"/browse\">browse people near you</a>, read their profiles and start a conversation at your own pace. If you are not sure what to say, our guide to <a href=\"/blog/how-to-start-a-conversation-online\">starting a conversation online</a> has simple ideas.</p><p>When you decide to meet someone in person, choose a busy public place, tell a friend where you are going, and read the <a href=\"/safety-tips\">safety tips</a> first. For the wider picture on building a social life from nothing, see <a href=\"/blog/how-to-make-friends-as-an-adult\">how to make friends as an adult</a>. Your new town can become home faster than you think. Start with one routine, one group and one invitation this week.</p>', 'blog/ad7e37323e375f4f.jpg', 'A group of young people laughing together at an outdoor table in a Kenyan town', 'Making friends in a new town: practical tips for Kenya', 'Moved for work or study? Learn how to start making friends in a new town in Kenya, from clubs and church to sports, volunteering and events.', 'making friends in a new town', '[{\"q\":\"How long does it take to make friends in a new town?\",\"a\":\"There is no fixed timeline. For most people it takes a few months of regular contact before an acquaintance becomes a real friend. Keep showing up to the same places and groups, and be patient. Slow progress does not mean you are doing it wrong.\"},{\"q\":\"How do I make friends in a new town if I am shy?\",\"a\":\"Choose activities with a built-in focus, such as a running group, a choir, a volunteer day or a class, so you are not forced into small talk. Arrive a little early, set yourself one small goal like learning two names, and go home when you have done it.\"},{\"q\":\"Where can I meet people after moving to a new town for work?\",\"a\":\"Start with your workplace, then add places that meet weekly: sports teams, church or mosque groups, volunteer projects, hobby clubs, co-working spaces and local events. Repeated contact with the same people matters more than going to many different places once.\"},{\"q\":\"Is it okay to use an app to meet new friends in a new town?\",\"a\":\"Yes. Many people use apps to find others nearby who also want company. Stay sensible: read profiles carefully, chat for a while, and meet in a busy public place the first time, ideally with someone knowing where you are.\"},{\"q\":\"What if people in my new town seem to already have their own friend groups?\",\"a\":\"Most people do, but that does not mean they have no room for one more. Be the one to suggest a coffee, a walk or a football match. People are often glad to be invited, because everyone else is waiting for someone else to make the first move.\"}]', NULL, NULL, 0, 'published', 6, '2026-10-16 06:16:00', '2026-10-16 06:16:42', '2026-10-16 20:14:13'),
(4, 4, NULL, 'Best dating profile photos: what to use and what to avoid', 'best-dating-profile-photos', 'Which photos to choose, how to use light and a natural smile, what to do about group shots, and the pictures that quietly put people off.', 'profile photos, dating photos, online dating, profile tips, photo tips, Kenya', '<p>People decide quickly whether to read your bio, and the deciding factor is almost always your pictures. If you want to find the best dating profile photos from the pictures already on your phone, you do not need a photographer or a new wardrobe. You need good light, a genuine smile and a bit of honesty about what you actually look like.</p>\r\n<p>This guide walks through which photos to pick, how to take better ones, what to do about group shots, and the common mistakes that make people swipe past. It applies whether you are hoping to meet a partner or make new friends.</p>\r\n\r\n<h2>Why your first photo does so much work</h2>\r\n<p>Your main picture is your first impression. It shows up before your name, before your bio and before anyone knows you have a great sense of humour. A clear, friendly face tells people they are looking at a real person, and that helps them feel comfortable sending a message.</p>\r\n<p>Honesty is the bigger goal. Your photos should make a stranger feel, when they meet you, that you look like your profile. Nothing damages trust faster than arriving at a first meeting and seeing someone who looks very different from the pictures. The best photos are flattering and truthful at the same time.</p>\r\n\r\n<h2>Choosing the best dating profile photos</h2>\r\n<p>Aim for a small set that each shows something different. A good mix looks like this:</p>\r\n<ol>\r\n<li><strong>A clear headshot.</strong> Just you, face visible, recent and well lit. This is your main picture.</li>\r\n<li><strong>A full-length photo.</strong> Standing or walking, so people can see your overall look and style.</li>\r\n<li><strong>A hobby or activity shot.</strong> You cooking, hiking, playing football, at a market or at a concert. This gives people something to ask about.</li>\r\n<li><strong>A social photo.</strong> One picture with friends or family, where you are easy to pick out.</li>\r\n<li><strong>A relaxed candid.</strong> A natural moment where you are laughing or looking away from the camera.</li>\r\n</ol>\r\n<p>You do not need all five. Three honest pictures beat six blurry ones. Pick the photos where you feel like yourself, not the ones where you are trying to look like someone else.</p>\r\n\r\n<h2>Lighting that makes you look your best</h2>\r\n<p>Light matters more than the camera. A cheap phone in good light beats an expensive one in a dark room. These simple habits help:</p>\r\n<ul>\r\n<li>Face a window or step outside in the morning or late afternoon, when the sun is soft.</li>\r\n<li>Avoid harsh midday sun straight overhead, which creates shadows under your eyes. A shaded spot works well.</li>\r\n<li>Do not stand with a bright window or the sun behind you, or your face will turn dark.</li>\r\n<li>Skip the camera flash at night, which flattens your face. Move near a lamp or a lit doorway instead.</li>\r\n<li>Wipe your phone lens. A smudge makes every picture look hazy.</li>\r\n</ul>\r\n<p>Ask a friend to take a few shots, or lean your phone on something steady and use the timer. Take plenty of pictures and choose the best one, because the first few are usually the stiffest.</p>\r\n\r\n<h2>Smiling naturally in photos</h2>\r\n<p>A real smile makes you look approachable and kind. A forced one makes people wonder if you are comfortable. If smiling on cue feels awkward, try thinking of something that makes you laugh, or have a friend say something funny right before the shot. Relaxed shoulders and a slight tilt of the head also help.</p>\r\n<p>You do not need a wide grin in every picture. A calm, friendly expression in some photos and a laughing one in others shows range. Look straight at the camera in at least one so people can see your eyes.</p>\r\n\r\n<h2>Group photos and photos with other people</h2>\r\n<p>A group photo can show that you have a lively social life, but it comes with risks. If people cannot tell which one is you, they will move on. Use only one, never as your first picture, and make sure you are easy to find. If you are the one in the middle or in a bright colour, even better.</p>\r\n<p>Respect other people\'s privacy too. Ask friends before using a photo they are in, and avoid pictures that show children. Do not use photos with an ex, even if you cropped them out, because it often shows. Pictures of your car, house or valuables are better left off, as they say little about you and can attract the wrong attention.</p>\r\n\r\n<h2>Photos to leave out</h2>\r\n<p>Some pictures quietly work against you. Try to avoid:</p>\r\n<ul>\r\n<li>Sunglasses or hats that hide your face in every photo.</li>\r\n<li>Heavy filters, face-smoothing apps or pictures many years old.</li>\r\n<li>Blurry, dark or very distant shots.</li>\r\n<li>Mirror selfies in untidy bathrooms.</li>\r\n<li>Photos that show your location, such as your home gate, workplace badge or number plate.</li>\r\n<li>Pictures of celebrities, cartoons or images that are not you.</li>\r\n<li>Anything revealing or suggestive, which is against the community rules and attracts the wrong kind of attention.</li>\r\n</ul>\r\n<p>Pictures taken from other people\'s profiles or the internet are not allowed and are a hallmark of fake accounts. If you want to understand how people spot those, read our guide on <a href=\"/blog/how-to-spot-fake-profiles-and-catfishing\">how to spot fake profiles and catfishing</a>.</p>\r\n\r\n<h2>Photos for dating and photos for friendship</h2>\r\n<p>What you pick can shift a little depending on what you want from the site. If you are hoping to meet a partner, include at least one picture that shows you dressed up a bit, such as at a wedding, a church service or a dinner, and one in everyday clothes. If you are looking for friends, activity shots matter even more: the five-a-side pitch, the hiking trail, the book club or the market on a Saturday morning.</p>\r\n<p>Either way, the aim is the same. A stranger should be able to look at your pictures and picture a pleasant afternoon with you. Ask yourself what story the set tells. If every photo is at a club, people may assume that is all you do. If every photo is a formal studio portrait, you may seem distant. A little variety makes you feel like a full, real person.</p>\r\n<p>It also helps to think about the background. A tidy, simple setting such as a garden, a street with murals, a cafe or a view of the city keeps attention on your face. Clutter, other people half in the frame and distracting signs draw the eye away from you.</p>\r\n\r\n<h2>How ConnectHub handles photos</h2>\r\n<p>To help keep the community respectful and genuine, ConnectHub reviews profile photos and has limits on how many you can add. That means it pays to choose your strongest pictures rather than uploading everything. Members can also earn a verified badge, which shows that the person behind the profile has been checked by the team. You can read more about why that matters in our piece on <a href=\"/blog/why-profile-verification-matters\">why profile verification matters</a>.</p>\r\n<p>If one of your photos is not approved, do not take it personally. Replace it with a clearer, more suitable picture and try again.</p>\r\n\r\n<h2>Put it together and keep it fresh</h2>\r\n<p>Once you have your set, check them together. Do they all look like the same person on the same year? Do they show a bit of your life? Is there at least one clear face shot? Show them to a trusted friend and ask for an honest opinion.</p>\r\n<p>Update your photos every few months or when your look changes, such as a new haircut or beard. Pair them with a warm, specific bio, and our guide on <a href=\"/blog/how-to-write-a-dating-profile-bio\">how to write a dating profile bio</a> will help with that. Then <a href=\"/browse\">browse people near you</a> and see how your profile compares, or <a href=\"/register\">create your free profile</a> if you have not yet.</p>', NULL, 'A person smiling outdoors in natural daylight for a profile photo', 'Best dating profile photos: a simple guide', 'Choose the best dating profile photos with simple advice on lighting, smiling, group shots and what to avoid, so your profile gets more replies.', 'best dating profile photos', '[{\"q\":\"How many photos should I have on my dating profile?\",\"a\":\"Three to five good photos is a sensible aim. That is enough to show your face, your style and a bit of your life. Add more only if each one shows something new. ConnectHub has a limit on how many photos you can add, so choose your strongest ones.\"},{\"q\":\"What should my main profile photo look like?\",\"a\":\"It should be a clear, recent picture of you alone, with your face visible and good natural light. A relaxed smile works better than a stiff pose. Avoid sunglasses, heavy filters and anything where it is hard to tell which person is you.\"},{\"q\":\"Are group photos a good idea?\",\"a\":\"One group photo is fine and shows you have friends, but it should never be your main picture. People want to know who they are looking at straight away. Make sure you are easy to spot, and ask your friends before sharing pictures that show them.\"},{\"q\":\"Is it okay to use filters or edit my photos?\",\"a\":\"Light touches such as brightening are fine. Heavy filters, face-smoothing and old pictures cause awkwardness when you meet, because you look different from your profile. Honest photos build trust, and trust is what leads to good conversations.\"},{\"q\":\"Why do profile photos get reviewed?\",\"a\":\"Reviewing photos helps keep the community respectful and safe, and helps catch pictures that break the rules or belong to someone else. It protects genuine members from fake profiles and makes the people you meet more likely to be who they say they are.\"}]', NULL, NULL, 0, 'published', 0, '2026-09-15 09:12:11', '2026-10-16 08:12:11', '2026-10-16 08:12:11');
INSERT INTO `blog_posts` (`id`, `category_id`, `author_id`, `title`, `slug`, `excerpt`, `tags`, `content`, `cover_path`, `cover_alt`, `meta_title`, `meta_description`, `focus_keyword`, `faq`, `og_image`, `canonical_url`, `noindex`, `status`, `views`, `published_at`, `created_at`, `updated_at`) VALUES
(5, 6, NULL, 'Dating etiquette in Kenya: manners, money and respect', 'dating-etiquette-in-kenya', 'From punctuality to who pays, family expectations and the English, Swahili and Sheng mix, here is a warm, practical guide to good manners when dating in Kenya.', 'dating etiquette, Kenya, manners, who pays, family, respect, culture', '<p>Good manners cost nothing, and they make a big difference when you are getting to know someone. If you want to understand dating etiquette in Kenya, the short version is this: be respectful, be honest, be on time and read the situation. Kenya is a diverse country with city habits, rural habits, many languages and many beliefs, so there is no single rulebook. There are, however, some common expectations that help dates go well.</p>\r\n<p>This guide covers punctuality, money, family, language, faith, public and private behaviour and a few everyday courtesies. Treat it as friendly guidance, not a strict script. The goal is to make the other person feel comfortable and respected.</p>\r\n\r\n<h2>Respect is the foundation of dating etiquette in Kenya</h2>\r\n<p>Whatever else you do, treat the person in front of you with respect. Listen when they speak, do not interrupt, and keep your phone away. Look at them when you talk, and be warm to the waiter, the boda boda rider and the security guard as well. People notice how you treat others, and it tells them a lot about your character.</p>\r\n<p>Respect also means accepting a no. If someone says they are not interested, or that they want to move slowly, take it kindly. Do not pressure, guilt-trip or keep messaging. Our guide on <a href=\"/blog/how-to-handle-rejection-gracefully\">how to handle rejection gracefully</a> is useful if you are on either side of that moment.</p>\r\n\r\n<h2>Punctuality and communication</h2>\r\n<p>Time is a sign of respect. Many people in Nairobi and other big towns plan dates around traffic and matatu schedules, so leave earlier than you think you need to. Aim to arrive on time or a few minutes early. If you are running late, send a message right away with an honest estimate, and say sorry when you arrive.</p>\r\n<p>The same goes for messages. You do not need to reply within seconds, but disappearing for days without a word feels unkind. If you are busy, say so. If you are no longer interested, say so politely instead of going silent. For pacing, read our tips on <a href=\"/blog/texting-rules-early-dating\">texting rules in early dating</a>.</p>\r\n\r\n<h2>Who pays, and how to talk about it</h2>\r\n<p>This is one of the most talked-about questions. In general terms, many Kenyans still expect the person who invites to pay on a first date, and traditionally that has often been the man. At the same time, more and more people prefer to split the bill, especially early on, and many women are happy to pay or share. Neither approach is wrong.</p>\r\n<p>What matters is how you handle it:</p>\r\n<ul>\r\n<li>If you invited, offer to pay, and do it without making a show of it.</li>\r\n<li>If the other person offers, say thank you, and offer to get the next one.</li>\r\n<li>If you want to split, say it lightly: \"Shall we share this one?\"</li>\r\n<li>Choose a place that suits both budgets, and do not choose somewhere you cannot afford to impress someone.</li>\r\n<li>Never treat a meal as a debt that must be repaid with attention or affection.</li>\r\n</ul>\r\n<p>Be careful with money in other ways too. Do not ask someone you have only just met for M-Pesa, airtime or loans, and do not send money to a person you have not met in person. Read <a href=\"/blog/should-you-send-money-to-someone-you-met-online\">should you send money to someone you met online</a> for more.</p>\r\n\r\n<h2>Family and community expectations</h2>\r\n<p>In Kenya, relationships are rarely just between two people. Parents, siblings, aunties, church friends and elders can all have opinions, and in many families marriage is a community matter. Some people keep their dating life private until things are serious, while others are open from the start. Both are understandable.</p>\r\n<p>Be sensitive to this. Do not post a new partner on social media or turn up at their home before they are ready. Ask questions like \"Is your family aware you are dating?\" and respect the answer. When things do become serious, introducing someone is a big step and deserves care and a little planning. See <a href=\"/blog/introducing-your-partner-to-family-in-kenya\">introducing your partner to family in Kenya</a> for help with that conversation.</p>\r\n\r\n<h2>Language: English, Swahili and Sheng</h2>\r\n<p>Most Kenyans move easily between English, Swahili, Sheng and their mother tongue, sometimes within a single sentence. Use the mix that feels natural, and follow the other person\'s lead. If they write in formal English, match them at first. If they drop into Swahili or Sheng, you can relax a little.</p>\r\n<p>A few simple courtesies help. Do not laugh at someone\'s accent or mistakes. Do not switch to a language to exclude a third person at the table. Be careful with Sheng slang that you do not fully understand, because it can sound rude, and avoid pet names with someone you barely know. Clear, kind language is always better than clever language.</p>\r\n\r\n<h2>Religion and background, without stereotypes</h2>\r\n<p>Faith is important to many Kenyans, and it shapes habits such as what people eat, when they pray and how they spend weekends. Some people want a partner of the same faith, while others are open. Likewise, your background or home community may matter to your family, even if it does not matter to you.</p>\r\n<p>The respectful approach is to ask, not to assume. Never decide what someone believes, eats or values because of their religion, tribe or region. People are individuals. Ask open questions such as \"What does Sunday look like for you?\" and share your own honestly. If your values differ on something important, like faith, children or where you want to live, talk about it kindly and early so nobody is hurt later.</p>\r\n\r\n<h2>Public and private behaviour</h2>\r\n<p>Public displays of affection are viewed differently across communities and places, so use common sense on early dates. A warm greeting and polite manners are always fine. Anything more should depend on both people being comfortable and on the setting. If you are unsure, hold back and follow their lead.</p>\r\n<p>Privacy matters too. Do not share photos or private messages without permission, and do not discuss someone\'s personal life with others. Meeting in a public place for the first few dates is polite and sensible, and it is a good safety habit as well. Our <a href=\"/blog/meet-someone-safely\">checklist for meeting someone safely</a> and our <a href=\"/safety-tips\">safety tips</a> cover the basics. Consent and comfort always come first, and anyone who ignores them is showing a red flag, so read <a href=\"/blog/green-flags-and-red-flags-in-early-dating\">green flags and red flags in early dating</a> when you are unsure.</p>\r\n\r\n<h2>Ending things with kindness</h2>\r\n<p>Not every date leads to a second one, and that is normal. Good etiquette matters most at the end. If you are not interested, tell the person kindly and clearly instead of disappearing. A short message such as \"Thank you for meeting me, I enjoyed meeting you, but I do not feel a romantic connection\" is respectful and saves them from waiting and wondering.</p>\r\n<p>If you are the one being turned down, say thank you and wish them well. Do not argue, bargain or send angry messages, and do not share their private details with friends or online. People remember how you handled a disappointment far longer than they remember the disappointment itself, and kindness at the end keeps your reputation, and your own peace of mind, in good shape.</p>\r\n\r\n<h2>Everyday courtesies that people remember</h2>\r\n<p>Small actions leave a big impression. Dress neatly for the occasion, and ask what plan suits them rather than deciding everything. Greet properly, thank them for their time, and send a short message afterward, such as \"I had a nice time today, thank you.\" Be honest about your intentions, and do not pretend to be single, wealthy or ready for marriage if you are not.</p>\r\n<p>Online, the same manners apply. Write a clear profile, reply kindly, and do not send rude or unwanted messages. You can <a href=\"/browse\">browse people near you</a>, and if you are brand new, our guide to <a href=\"/blog/online-dating-in-kenya-complete-guide\">online dating in Kenya</a> covers the whole journey from profile to first date. When you are ready, <a href=\"/register\">create your profile</a> and bring your best manners with you. A little respect, honesty and punctuality go a very long way.</p>', NULL, 'A couple sharing a polite conversation at a restaurant table in Nairobi', 'Dating etiquette in Kenya: manners and respect guide', 'A practical guide to dating etiquette in Kenya: punctuality, who pays, family expectations, language, faith and public versus private behaviour.', 'dating etiquette in Kenya', '[{\"q\":\"Who should pay on a first date in Kenya?\",\"a\":\"There is no single rule. Many people expect the person who invited to pay, while others prefer to split the bill. The best approach is to be relaxed and honest. Offer to pay or to split, accept a kind offer gracefully, and avoid any pressure either way.\"},{\"q\":\"Is it rude to be late for a date?\",\"a\":\"Being late without a message is considered disrespectful by most people. Traffic in Nairobi and other towns is real, so leave early. If you are delayed, send a short message with an honest estimate and apologise when you arrive.\"},{\"q\":\"Do I need to meet my date\'s family early on?\",\"a\":\"No. Family introductions usually come when a relationship is serious and both people are ready. Talk about it openly, go at a pace that feels right, and never feel pushed into a meeting before you are comfortable.\"},{\"q\":\"Which language should I use when chatting with someone new?\",\"a\":\"Follow their lead. Many Kenyans move between English, Swahili and Sheng in one conversation. Start in the language you are both comfortable with, and avoid showing off or mocking someone\'s accent or mother tongue.\"},{\"q\":\"How should I talk about religion or ethnic background when dating?\",\"a\":\"Be curious, respectful and honest about what matters to you. Ask about someone\'s beliefs and values instead of assuming, and never judge a person by their community. Talk about big questions, such as faith and children, early enough to avoid hurt later.\"}]', NULL, NULL, 0, 'published', 1, '2026-09-16 09:12:11', '2026-10-16 08:12:11', '2026-10-16 08:12:11'),
(6, 4, NULL, 'Dating profile mistakes to avoid if you want more replies', 'dating-profile-mistakes-to-avoid', 'The common dating profile mistakes that quietly cost you messages, from vague bios to risky details, and how to fix each one in minutes.', 'profile mistakes, dating profile, online dating, profile tips, dating advice, Kenya', '<p>You can be warm, funny and genuinely lovely and still get very few messages if your profile is working against you. The good news is that most dating profile mistakes are small, and each one can be fixed in a few minutes. You do not need to become a different person. You only need to remove the things that quietly put people off.</p>\r\n<p>Below are the mistakes we see most often, grouped by photos, words and safety, with a simple fix for each. Read them with a kind eye, because nearly everyone has made at least one.</p>\r\n\r\n<h2>Dating profile mistakes with photos</h2>\r\n<p>Photos are the first thing anyone sees, so mistakes here cost the most. The most common ones are:</p>\r\n<ul>\r\n<li>Only one photo, or none at all. People are hesitant to message a profile without a face.</li>\r\n<li>Your face hidden by sunglasses, a cap, a mask or a filter in every picture.</li>\r\n<li>Blurry, dark or far-away pictures where nobody can really see you.</li>\r\n<li>Group photos as the main picture, so viewers cannot tell which person you are.</li>\r\n<li>Pictures that are many years old, which cause awkwardness at the first meeting.</li>\r\n</ul>\r\n<p>The fix is simple. Use three or four clear, recent photos in good natural light, with your face visible in the first one. Our guide to the <a href=\"/blog/best-dating-profile-photos\">best dating profile photos</a> goes through lighting, smiles and what to leave out.</p>\r\n\r\n<h2>Bio mistakes that make people scroll past</h2>\r\n<p>The words on your profile should sound like a warm person you would like to meet. These slips do the opposite:</p>\r\n<ul>\r\n<li><strong>Leaving it blank.</strong> It suggests you are not serious and gives no conversation starter.</li>\r\n<li><strong>Writing \"ask me\".</strong> People want a reason to ask, not a job to do.</li>\r\n<li><strong>Using clichés.</strong> \"I love to laugh, travel and have fun\" describes almost everyone.</li>\r\n<li><strong>Listing complaints.</strong> \"No drama, no liars, no time wasters\" sounds like a person who has been hurt and is bracing for more.</li>\r\n<li><strong>Writing a novel.</strong> A very long bio is tiring to read, and most people will skip it.</li>\r\n</ul>\r\n<p>Instead, write three to six short sentences with specific details, such as a favourite place, a hobby or a goal. Add one honest line about what you are looking for and finish with a question. Our guide on <a href=\"/blog/how-to-write-a-dating-profile-bio\">how to write a dating profile bio</a> has a formula and sample bios you can copy.</p>\r\n\r\n<h2>Being too negative or too demanding</h2>\r\n<p>It is fair to have standards. The trouble starts when your profile is mostly about what you will not accept. A long list of demands can make you seem rigid, and it filters out good people who simply phrase things differently.</p>\r\n<p>Turn each negative into a positive. Instead of \"no liars\", write \"I value honesty\". Instead of \"must be tall, rich and religious\", pick the one or two things that truly matter and describe the kind of relationship you would enjoy. Positive language invites people in, while negative language sets up a wall before the conversation has begun.</p>\r\n\r\n<h2>Dishonest details that backfire</h2>\r\n<p>It can be tempting to nudge the truth: a younger age, a better job, a different relationship status or a picture that is a little old. These shortcuts always catch up with you. If someone meets you and finds the profile was not true, trust is gone before you have had a chance to impress them in person.</p>\r\n<p>Honesty does not mean sharing everything. It means that what you do share is true. You can keep your salary private and still be honest about your work. You can leave out the details of a painful past and still be truthful about where you are now. Real details attract people who want the real you, and those are the people worth meeting.</p>\r\n\r\n<h2>Giving away too much personal information</h2>\r\n<p>Some of the most serious dating profile mistakes are about safety rather than style. Do not put your phone number, social media handles, home address, workplace name or daily routine on your profile. Avoid photos that show your gate, your plate number or your office badge.</p>\r\n<p>It is far safer to chat on the platform first and share more once you trust someone. Our guide to <a href=\"/blog/protect-your-privacy-on-dating-apps\">protecting your privacy on dating apps</a> explains what to hold back and when. Also remember that anyone who pushes you for money or private details early on is a warning sign, which you can read about in <a href=\"/blog/romance-scams-in-kenya-warning-signs\">romance scams in Kenya</a>.</p>\r\n\r\n<h2>Forgetting that a profile is a conversation</h2>\r\n<p>A profile is not a monologue. It should leave the door open for people to say hello. Many profiles fail because there is nothing to respond to: no named hobbies, no question, no quirky detail. Reading them feels like reading a form.</p>\r\n<p>Add a hook. Mention something you are curious about, a food you are trying to master, a place you want to visit or a debate you will happily have. \"Settle this for me: is the best chapati at home or at a roadside stall?\" is far easier to answer than \"Hi, I am looking for someone special.\"</p>\r\n\r\n<h2>Messaging habits that undo a good profile</h2>\r\n<p>Even a lovely profile can be let down by what happens after the first hello. A few habits are worth watching:</p>\r\n<ul>\r\n<li>Sending the same copied message to everyone, which people can spot at once.</li>\r\n<li>Asking for a phone number or WhatsApp in your first message.</li>\r\n<li>Replying only with one word, which leaves the other person to carry the chat.</li>\r\n<li>Sending many messages in a row when you have had no reply.</li>\r\n<li>Getting upset or rude when someone is not interested.</li>\r\n</ul>\r\n<p>Treat every chat as a conversation between two real people. Ask about something they mentioned, share something about yourself and give them space to answer. If someone says no or goes quiet, thank them and move on. Our guide to <a href=\"/blog/how-to-handle-rejection-gracefully\">handling rejection gracefully</a> can help when it stings.</p>\r\n\r\n<h2>Letting your profile go stale</h2>\r\n<p>A profile that has not changed for a year can look forgotten. Your life moves, and your profile should keep up. Update your photos when your look changes, refresh your bio when your goals shift and check that your interests still match what you enjoy.</p>\r\n<p>Make it a habit to review your profile every month or two. Look at it as a stranger would, and ask: would I message this person? If not, tweak one thing. Small, steady changes are easier than a big overhaul, and they keep your profile feeling alive.</p>\r\n\r\n<h2>A quick checklist before you save</h2>\r\n<p>Mistakes are normal, and fixing them is easy. Do not delete your whole profile because one part is weak. Change the photo, tighten the opening line or remove the private detail, and give it a week to see how the replies change.</p>\r\n<p>Before you log off, run through these questions:</p>\r\n<ol>\r\n<li>Is my first photo a clear, recent picture of my face?</li>\r\n<li>Does my bio include at least two specific details?</li>\r\n<li>Have I said what I am looking for in one kind sentence?</li>\r\n<li>Is everything I wrote true?</li>\r\n<li>Have I kept my number, address and workplace private?</li>\r\n<li>Does my profile end with something easy to reply to?</li>\r\n</ol>\r\n<p>If you can answer yes to all six, you are in good shape. Consider adding a verified badge for extra trust, which we explain in <a href=\"/blog/why-profile-verification-matters\">why profile verification matters</a>. Then <a href=\"/browse\">browse people near you</a> and send a message, or <a href=\"/register\">create your free profile</a> and start fresh.</p>', NULL, 'A person reviewing their dating profile on a phone with a pen and notebook', 'Dating profile mistakes to avoid (and how to fix them)', 'Spot the dating profile mistakes that cost you replies, from blurry photos to oversharing, and learn quick fixes that make your profile work better.', 'dating profile mistakes', '[{\"q\":\"What is the biggest mistake people make on a dating profile?\",\"a\":\"Being vague. A profile with unclear photos and a bio that says only \\\"ask me\\\" or \\\"just here to meet people\\\" gives others nothing to respond to. Specific details, clear photos and one honest sentence about what you want fix most problems quickly.\"},{\"q\":\"Should I include my phone number or social media on my profile?\",\"a\":\"No. Keep your phone number, social media handles and exact location private until you have chatted for a while and feel comfortable. Sharing them publicly can lead to unwanted messages, and it is also a safety risk if the wrong person sees them.\"},{\"q\":\"Is it a mistake to list a long set of requirements?\",\"a\":\"Usually, yes. Long lists of must-haves and must-nots sound demanding and put off kind people who might suit you well. Share two or three values you care about in positive words instead, such as honesty, respect and good communication.\"},{\"q\":\"Do I have to be honest about my age and relationship status?\",\"a\":\"Yes. Being honest on these basics builds trust and avoids hurt later. Fake details are quickly discovered when you meet or talk, and they damage the connection before it begins. Honest profiles attract people who want the real you.\"},{\"q\":\"How can I check whether my profile has any of these mistakes?\",\"a\":\"Ask a trusted friend to read it as a stranger and tell you what they would message you about. Check that your photos are clear, your bio has specific details and nothing private is showing. Fix one thing at a time and see how replies change.\"}]', NULL, NULL, 0, 'published', 1, '2026-09-17 09:12:11', '2026-10-16 08:12:11', '2026-10-16 08:12:11'),
(7, 5, NULL, 'How to define the relationship: when and how to ask what are we', 'define-the-relationship-talk', 'When to ask \"what are we?\", how to talk about exclusivity without pressure, and word-for-word scripts you can use for the define the relationship talk.', 'define the relationship, what are we, exclusivity, relationship talk, dating advice, Kenya', '<p>You have been seeing someone for a while. You message every day, you have been on a few dates, and it feels good. But you do not know what you are to each other, and the question keeps slipping into your head. Learning when and how to define the relationship can save you weeks of guessing and a lot of anxiety. It is also much less scary than it sounds.</p>\r\n<p>This guide explains when the right moment is, what to think about before you ask, how to talk about exclusivity, and gives you scripts you can adjust and use. It also covers what to do when the answer is not the one you hoped for.</p>\r\n\r\n<h2>Why the what are we talk is worth having</h2>\r\n<p>Without a clear conversation, two people can be in very different places and never know it. One may think they are building something serious, while the other thinks it is a casual friendship. Neither is wrong to feel what they feel, but silence leaves room for hurt and misunderstanding.</p>\r\n<p>Being clear is a kindness. It lets you both choose with full information, and it shows respect for the other person. A relationship that can survive an honest conversation is much stronger than one that depends on avoiding it.</p>\r\n\r\n<h2>When to define the relationship</h2>\r\n<p>There is no magic date, but some signs suggest it is a good time:</p>\r\n<ul>\r\n<li>You have spent real time together, on several dates and not only through messages.</li>\r\n<li>You talk or text most days and look forward to it.</li>\r\n<li>You find yourself wondering whether they are seeing other people.</li>\r\n<li>You have started introducing each other to friends, or making plans a few weeks ahead.</li>\r\n<li>You know what you want, even if only roughly, and the uncertainty is starting to bother you.</li>\r\n</ul>\r\n<p>If you have only chatted a few times or have not met in person, it may be too early. Spend more time getting to know each other first. Our guide to <a href=\"/blog/first-date-conversation-topics\">first date conversation topics</a> can help you learn what matters to each other along the way.</p>\r\n\r\n<h2>Get clear on what you want first</h2>\r\n<p>Before you start the conversation, spend a few minutes with yourself. It is hard to ask for something you have not defined. Ask yourself:</p>\r\n<ol>\r\n<li>Do I want a serious, committed relationship with this person?</li>\r\n<li>Do I want to be exclusive, or am I still exploring?</li>\r\n<li>What would make me feel secure, and what would make me feel uneasy?</li>\r\n<li>What will I do if they want something different?</li>\r\n</ol>\r\n<p>The last question matters most. Decide ahead of time what you are willing to accept. It is easier to stay calm in the conversation when you know where your own line is.</p>\r\n\r\n<h2>Choosing the right moment and place</h2>\r\n<p>Timing changes how the conversation feels. Pick a relaxed moment, not in the middle of an argument, after a long day or when one of you is rushing out. A quiet walk, a meal or a phone call at a calm hour all work well. Avoid raising it in front of friends or by dropping a heavy question into a casual text.</p>\r\n<p>If you feel nervous, give a gentle heads up: \"I would love to chat about where we are at, are you free tonight?\" This stops it feeling like an ambush and gives the other person time to think. If you are not sure how to handle a first reaction, our guide on <a href=\"/blog/how-to-handle-rejection-gracefully\">how to handle rejection gracefully</a> will help whatever the answer is.</p>\r\n\r\n<h2>Scripts you can copy for the what are we talk</h2>\r\n<p>Words are easier when you have a starting point. Adjust these to your own voice:</p>\r\n<ul>\r\n<li><strong>The gentle opener.</strong> \"I really enjoy spending time with you. I would like to know how you see us, and I will tell you how I feel too.\"</li>\r\n<li><strong>The direct but kind version.</strong> \"I am not seeing anyone else, and I would like us to be exclusive. How do you feel about that?\"</li>\r\n<li><strong>The checking-in version.</strong> \"We have been seeing each other for a while now. What are you looking for at this point?\"</li>\r\n<li><strong>If you want to take it slow.</strong> \"I like you a lot and I am not seeing anyone else. I am not ready to put a label on it yet, but I wanted to be honest.\"</li>\r\n<li><strong>If you want something serious.</strong> \"I am looking for a committed relationship. I would like to see whether that is where we are heading.\"</li>\r\n</ul>\r\n<p>Speak about your own feelings rather than accusing. \"I feel unsure where we stand\" lands much softer than \"You never tell me what is going on.\" Then stop talking and listen.</p>\r\n\r\n<h2>Talking about exclusivity clearly</h2>\r\n<p>Exclusivity simply means you are both seeing only each other. It does not always come with a label, and different people understand it differently. That is why it is worth saying out loud, rather than assuming.</p>\r\n<p>Be specific about what you mean. Does it include chatting on dating platforms? Should you delete your profile, pause it or leave it? Some couples agree to turn off their profiles together as a small, clear signal. Others discuss it more gradually. Whatever you decide, make sure you both mean the same thing. Mixed assumptions cause far more pain than an honest conversation.</p>\r\n\r\n<h2>What to do with their answer</h2>\r\n<p>A conversation like this can go three ways. They may feel the same, which is lovely, and you can celebrate. They may need time, in which case you can ask kindly what that looks like, and set a gentle limit for yourself. Or they may want something different, which hurts but is better to know now.</p>\r\n<p>If they say they need time, listen for specifics. \"I am just finishing a busy season at work and want to take it slowly\" is different from \"I do not do labels\" said over and over for months. Trust patterns, not promises. If you find yourself waiting indefinitely and feeling worse, it is fair to step back. Check our guide to <a href=\"/blog/green-flags-and-red-flags-in-early-dating\">green flags and red flags in early dating</a> to see how their behaviour compares with their words.</p>\r\n<p>Whatever happens, stay kind. Do not argue, bargain or beg. You are allowed to want clarity, and you are allowed to leave if you cannot get it.</p>\r\n\r\n<h2>Mistakes to avoid in this conversation</h2>\r\n<p>A few common slips can turn a simple talk into a stressful one:</p>\r\n<ul>\r\n<li>Raising it in the middle of an argument or when one of you is upset.</li>\r\n<li>Using ultimatums, such as \"Commit now or we are done\", before you have shared how you feel.</li>\r\n<li>Reading too much into one answer instead of looking at how they behave over time.</li>\r\n<li>Asking by text and then reading meaning into emojis or slow replies.</li>\r\n<li>Telling them what they feel instead of asking.</li>\r\n</ul>\r\n<p>Keep your tone curious and warm. You are not trying to win, only to understand where you both stand. If the first conversation does not settle everything, you can have a second one a few days later, once you have both had time to think.</p>\r\n\r\n<h2>After the talk</h2>\r\n<p>If you both decide to be together, small habits will keep the clarity going. Check in now and then, say thank you, and talk about little things before they become big ones. You might also begin to meet each other\'s friends, and eventually your families, which you can read about in <a href=\"/blog/introducing-your-partner-to-family-in-kenya\">introducing your partner to family in Kenya</a>.</p>\r\n<p>If you are still meeting people and not yet at this stage, that is fine too. You can <a href=\"/browse\">browse people near you</a> and take things at your own pace, or <a href=\"/register\">create your free profile</a> to start a new connection with clear intentions from the start.</p>', NULL, 'Two people having a calm, honest conversation on a bench in a park', 'Define the relationship talk: when and how to ask', 'Not sure when to define the relationship? Learn the right time to ask what are we, how to talk about exclusivity and scripts you can use today.', 'define the relationship', '[{\"q\":\"How long should you date before asking what are we?\",\"a\":\"There is no fixed number of weeks. Many people feel ready after a handful of dates, once they have spent real time together and know what they want. If you are already wondering, you are probably ready to raise it, kindly and without pressure.\"},{\"q\":\"What if I ask and they are not ready to commit?\",\"a\":\"Thank them for being honest. You can decide whether their timeline suits you. If they say not yet, ask whether that means weeks or never. Staying in an unclear situation for a long time usually hurts, so it is fair to walk away if you want more.\"},{\"q\":\"Is it okay to ask for exclusivity early on?\",\"a\":\"Yes, if it matters to you. It is better to ask than to assume. Say what you want in a calm way and ask what they think. Their answer, and how they give it, will help you decide whether to continue.\"},{\"q\":\"Who should bring up the define the relationship talk first?\",\"a\":\"Whoever feels ready. There is no rule that it must be one gender or the other. Waiting for the other person to start can leave both of you stuck, so starting the conversation yourself shows confidence and honesty.\"},{\"q\":\"Should I have this talk by text or in person?\",\"a\":\"In person or on a call is best, because tone is easy to misread in writing. A text can be used to ask for a good time to talk, but the real conversation deserves a voice or a face, especially if the answer is important to you.\"}]', NULL, NULL, 0, 'published', 1, '2026-09-18 09:12:11', '2026-10-16 08:12:11', '2026-10-16 08:12:11'),
(8, 3, NULL, 'Feeling lonely in the city: small, kind steps that help', 'feeling-lonely-in-the-city', 'You can be surrounded by thousands in Nairobi and still feel alone. Here are gentle, practical steps to feel more connected, one small thing at a time.', 'loneliness, city life, Nairobi, connection, friendship, wellbeing, Kenya', '<p>Nairobi, Mombasa, Kisumu and Nakuru are full of people, yet many of us go home to a quiet room and wonder why we feel so alone. If you are feeling lonely in the city, please know that you are not strange and you are not the only one. Moving for work, long commutes, high rent and busy schedules make it easy to drift away from people, even when we are surrounded by them.</p>\r\n<p>This article is not a magic fix. It is a collection of small, kind and practical steps that many people find helpful, written in plain language. Take what is useful and leave the rest.</p>\r\n\r\n<h2>Why feeling lonely in the city is so common</h2>\r\n<p>Loneliness is not the same as being by yourself. You can be alone and feel perfectly peaceful, and you can be in a packed matatu or a busy office and feel invisible. Loneliness is the gap between the connection you want and the connection you have.</p>\r\n<p>City life widens that gap in several ways. Many people have moved far from family and childhood friends. Long journeys leave little energy for socialising. Neighbours may be strangers, and everyone seems to be rushing. Social media can make it worse, because other people\'s best moments look like constant parties while you sit at home. None of this says anything bad about you. It is simply how city life can work.</p>\r\n\r\n<h2>Name the feeling without judging it</h2>\r\n<p>The first step is to say it plainly: \"I am feeling lonely right now.\" Many people try to hide the feeling or feel ashamed of it, which only makes it heavier. Loneliness is a human signal, like hunger, that tells you something you need is missing. It is not a personal failure.</p>\r\n<p>Try to speak to yourself the way you would speak to a friend in the same position. You would not call them pathetic. You would say it is hard, and ask what might help. Give yourself that same patience.</p>\r\n\r\n<h2>Start small, because small counts</h2>\r\n<p>When you feel lonely, big goals like \"I must make ten friends\" can feel impossible. Choose something tiny and doable today. Small actions add up faster than you expect.</p>\r\n<ul>\r\n<li>Greet your neighbour, the guard or the shopkeeper by name.</li>\r\n<li>Send a voice note to a friend or cousin you have not spoken to in a while.</li>\r\n<li>Reply properly to a message you have been ignoring.</li>\r\n<li>Sit in a cafe or park for an hour instead of in your room.</li>\r\n<li>Ask a colleague how their weekend was and listen to the answer.</li>\r\n</ul>\r\n<p>Each of these is a small thread. Over a few weeks, threads become a net that holds you.</p>\r\n\r\n<h2>Build a gentle routine outside your room</h2>\r\n<p>A simple routine gives your week some shape and puts you near people without asking too much of you. You do not need to be the loudest person in the room, only a regular one.</p>\r\n<p>Try one weekly plan that gets you out of the house: a morning walk in a nearby park, an evening run, a church or mosque fellowship, a community clean-up, a class or a pickup football game. Go at the same time each week. After a few visits, faces become familiar, and familiar faces become the beginning of friendship. For ideas on groups to join, see our guide to <a href=\"/blog/making-friends-in-a-new-town\">making friends in a new town</a>.</p>\r\n\r\n<h2>Reach back to the people you already have</h2>\r\n<p>When you are feeling lonely in the city, it is tempting to wait for someone to contact you. Often the people you miss are also busy and a little shy about getting in touch first. A short message can mean a lot: \"I was thinking about you. How are you?\"</p>\r\n<p>Phone calls, voice notes and video chats can keep home close. Set up a regular slot, perhaps Sunday evening, to call your family or an old friend. At the same time, make room for new people. Friends from home are a comfort, but friends nearby can share a meal, a walk or a quiet afternoon.</p>\r\n\r\n<h2>Find your people through shared interests</h2>\r\n<p>Friendship is easier when you already have something in common. Think about what you enjoy, such as football, gospel music, cooking, photography, hiking or board games, and look for others who enjoy it too. A shared interest gives you something to talk about, so you do not have to think of clever conversation.</p>\r\n<p>Ask around at work, in your estate WhatsApp group or at your place of worship. Someone often knows of a group that meets on weekends. If you are shy, go with one clear job, such as helping set up chairs or handing out water at an event. A job gives you a reason to talk to people and a natural way to leave when you are done.</p>\r\n\r\n<h2>Look after your body and your space</h2>\r\n<p>Loneliness feels heavier when you are tired and run down. Small acts of care help you cope better. Try to eat proper meals, drink water, sleep at regular times and move your body, even if it is only a short walk around your estate in daylight.</p>\r\n<p>Make your space feel like yours. Open the curtains, put on music, add a plant or a photo, and cook something warm even if it is only for yourself. A home that feels pleasant makes it easier to come back to, and easier to invite someone over later.</p>\r\n\r\n<h2>Be careful with screens</h2>\r\n<p>Your phone can connect you or drain you. Scrolling through other people\'s highlights at night can leave you feeling more alone. If you notice that happening, try setting a time limit, muting accounts that make you feel low, or swapping some scrolling for a call with a real person.</p>\r\n<p>Technology can also help. Messaging someone who shares your interests, joining a local group chat or using a friendship and dating platform to meet people nearby can ease the isolation. If you try that, go slowly. Chat for a while, meet in a busy public place, tell a friend where you are, and never send money to someone you have not met. Our <a href=\"/safety-tips\">safety tips</a> explain the basics. You can also <a href=\"/browse\">browse people near you</a> and take as long as you like to decide who to talk to.</p>\r\n\r\n<h2>What to do on the hardest evenings</h2>\r\n<p>Some evenings, like Sundays or after a hard day, the quiet feels loud. Plan for them in advance. Here is a small list you can keep on your phone:</p>\r\n<ol>\r\n<li>Call or voice-note one person, even for five minutes.</li>\r\n<li>Step outside, if it is safe, and walk around the block.</li>\r\n<li>Put on a playlist, a sermon, a podcast or a comedy that makes you feel less alone.</li>\r\n<li>Cook or order something you enjoy and eat it at a table, not on your bed.</li>\r\n<li>Write down one thing you will do tomorrow to see another person.</li>\r\n</ol>\r\n<p>None of these will remove loneliness in one night, but they make the evening kinder, and they build the habit of looking after yourself.</p>\r\n\r\n<h2>When loneliness feels heavy, reach for support</h2>\r\n<p>Everyone feels lonely sometimes. But if the feeling is heavy, stays for weeks, or begins to affect your sleep, appetite, work or your sense of hope, please talk to someone. This could be a trusted friend, a family member, a pastor, imam or elder you respect, or a qualified counsellor. You do not need to wait until things are very bad, and asking for help is a sign of strength.</p>\r\n<p>If you ever feel that you might harm yourself, contact emergency services or a trusted person right away, and do not stay alone with those thoughts. Beyond that, remember that connection is built slowly. Our guides on <a href=\"/blog/how-to-make-friends-as-an-adult\">how to make friends as an adult</a> and <a href=\"/blog/how-to-start-a-conversation-online\">starting a conversation online</a> offer simple next steps whenever you feel ready. Be patient with yourself. One small step today is enough.</p>', NULL, 'A person sitting by a window watching city lights in the evening', 'Feeling lonely in the city: gentle steps that help', 'Feeling lonely in the city is common, even in a crowd. Try these small, practical steps to feel more connected, and know when to talk to someone.', 'feeling lonely in the city', '[{\"q\":\"Is it normal to feel lonely in a big city like Nairobi?\",\"a\":\"Yes, very. Big cities are full of people, but everyone is busy, commuting and tired, and many have moved away from family. Feeling alone in a crowd is a common experience and does not mean something is wrong with you.\"},{\"q\":\"What can I do when I feel lonely at night?\",\"a\":\"Try a simple routine: call or voice-note a friend, take a short walk if it is safe, cook something warm, or play music you love. Write down one small plan for tomorrow so you have something to look forward to. Be kind to yourself.\"},{\"q\":\"How do I meet people when I work long hours?\",\"a\":\"Pick one small, regular thing rather than many big plans. A weekly sports session, a Sunday fellowship or a lunch with a colleague fits into most schedules. Short, repeated contact builds friendship better than rare big events.\"},{\"q\":\"When should I talk to a counsellor about loneliness?\",\"a\":\"If the feeling is heavy, lasts for weeks, or starts to affect your sleep, appetite, work or daily life, speak to someone you trust or a qualified counsellor. Reaching out is a sign of strength, and you do not have to wait until things are very bad.\"},{\"q\":\"Can online platforms help with loneliness?\",\"a\":\"They can be one helpful tool. Meeting people nearby who also want company can make a big difference. Go at your own pace, keep chats respectful, and meet in public places. They work best alongside real-life routines rather than replacing them.\"}]', NULL, NULL, 0, 'published', 1, '2026-09-19 09:12:11', '2026-10-16 08:12:11', '2026-10-16 08:12:11');
INSERT INTO `blog_posts` (`id`, `category_id`, `author_id`, `title`, `slug`, `excerpt`, `tags`, `content`, `cover_path`, `cover_alt`, `meta_title`, `meta_description`, `focus_keyword`, `faq`, `og_image`, `canonical_url`, `noindex`, `status`, `views`, `published_at`, `created_at`, `updated_at`) VALUES
(9, 1, NULL, 'First date conversation topics: what to ask and avoid', 'first-date-conversation-topics', 'Nervous about running out of things to say? These first date conversation topics, questions and tips for awkward silence will keep things easy.', 'first date, conversation topics, questions, dating tips, awkward silence, Kenya', '<p>Many people spend more time worrying about what they will say than about what they will wear. The good news is that you do not need to be a brilliant speaker. A handful of first date conversation topics, a few good questions, and a calm attitude to silence are enough to make the meeting feel easy. This guide gives you all three, with examples you can use in Nairobi, Mombasa, Kisumu or wherever you meet.</p>\r\n<p>If you are still choosing where to go, see our <a href=\"/blog/first-date-ideas-in-nairobi\">first date ideas in Nairobi</a>. A walk or a market stroll gives you plenty to talk about without any effort.</p>\r\n\r\n<h2>The goal of first date talk</h2>\r\n<p>The point of conversation on a first date is not to impress. It is to find out whether you enjoy each other\'s company. That means curiosity beats performance every time. You are trying to learn what the person is like, what they care about, and how it feels to laugh with them.</p>\r\n<p>Good conversation has a balance. Each of you asks questions, each of you answers, and each of you shares a little. If you notice that you have been talking for ten minutes straight, pause and ask something. If you notice that you have been asking everything, add a story of your own.</p>\r\n\r\n<h2>Easy first date conversation topics that always work</h2>\r\n<p>These topics are friendly, low risk and rich in follow-up questions. Choose two or three that suit the mood.</p>\r\n<ul>\r\n<li><strong>Food:</strong> favourite meals, what they cook, the best street snack in town, how they like their chai.</li>\r\n<li><strong>Places:</strong> their hometown, upcountry visits, the best trip they ever took, a place they would love to see.</li>\r\n<li><strong>Music and films:</strong> songs they play on repeat, a series they cannot stop watching, the last film that surprised them.</li>\r\n<li><strong>Hobbies and weekends:</strong> sports, hiking, church or community groups, creative projects, how they relax.</li>\r\n<li><strong>Work and learning:</strong> what they enjoy about their job or studies, and what they would do if money was no object.</li>\r\n<li><strong>Funny stories:</strong> a travel mishap, a matatu adventure, a school memory that still makes them laugh.</li>\r\n</ul>\r\n<p>When someone shares something they enjoy, they relax. That makes it a lovely way to begin and to settle into the date.</p>\r\n\r\n<h2>Questions that go a little deeper</h2>\r\n<p>Once the first few minutes have passed and you both feel comfortable, move from surface questions to slightly more personal ones. The aim is warmth, not interrogation.</p>\r\n<ol>\r\n<li>\"What has been the best part of this year so far?\"</li>\r\n<li>\"What is something you are proud of that people may not know?\"</li>\r\n<li>\"What does a really good weekend look like to you?\"</li>\r\n<li>\"Who in your life makes you laugh the most?\"</li>\r\n<li>\"What is something new you would love to learn?\"</li>\r\n<li>\"What do you value most in a friend?\"</li>\r\n</ol>\r\n<p>These questions show interest in who the person is, not only what they do. Listen to the answer fully before replying. A kind follow-up such as \"Tell me more about that\" often does more than a clever comment.</p>\r\n\r\n<h2>Topics to avoid on a first date</h2>\r\n<p>Some subjects are better saved for later, once trust has grown. Bringing them up too soon can turn a happy meeting into a stressful one.</p>\r\n<ul>\r\n<li>Long complaints about exes or past hurts.</li>\r\n<li>Detailed talk about money, salaries, debts or what the other person can give you.</li>\r\n<li>Strong political or religious debates that may divide you.</li>\r\n<li>Pressure about marriage, children or timelines.</li>\r\n<li>Gossip about friends, family or colleagues.</li>\r\n<li>Anything that feels like an interview or a test.</li>\r\n</ul>\r\n<p>It is fine to be honest about what you are looking for, for example \"I am hoping to meet someone for something serious.\" Just say it simply and move on. If you want to know what healthy behaviour looks like early on, read our guide to <a href=\"/blog/green-flags-and-red-flags-in-early-dating\">green flags and red flags in early dating</a>.</p>\r\n\r\n<h2>Listen well, and others will love talking to you</h2>\r\n<p>Many people think a good talker speaks a lot. Usually, a good talker listens a lot. Put your phone away, face the person, and let them finish their thoughts. Nod, smile, and react to what they say. Small reactions such as \"That sounds amazing\" or \"Ha, I can picture it\" show that you are present.</p>\r\n<p>Use their words in your next question. If they say they grew up in Kakamega and miss the food, ask what their favourite dish was. This keeps the talk flowing and tells them you were paying attention. It also takes pressure off you, since you do not have to invent topics from nothing.</p>\r\n\r\n<h2>How to handle awkward silence</h2>\r\n<p>Every date has a quiet moment. It feels longer to you than it does to the other person. Silence is not a failure, and often it just means two people are thinking or enjoying the setting. Here is what you can do when it happens:</p>\r\n<ul>\r\n<li><strong>Take a breath and smile.</strong> A relaxed face makes the pause feel comfortable.</li>\r\n<li><strong>Comment on the surroundings.</strong> \"This place has such a nice atmosphere. Have you been before?\"</li>\r\n<li><strong>Return to an earlier topic.</strong> \"You mentioned your trip to the coast. What was the best meal there?\"</li>\r\n<li><strong>Ask a light either-or.</strong> \"Mornings or evenings? Sunrise or sunset?\"</li>\r\n<li><strong>Be honest and gentle.</strong> \"I am a little nervous, so forgive me if I go quiet for a second.\"</li>\r\n</ul>\r\n<p>Admitting nerves is often charming. It gives the other person permission to relax too, and many people will answer with, \"Me too.\"</p>\r\n\r\n<h2>Prepare a little, then relax</h2>\r\n<p>A tiny bit of preparation lowers nerves a lot. Before you leave, think of two or three things you would happily talk about, such as a recent trip, a show you enjoyed, or a funny story from your week. Have a couple of questions ready too. You will probably not need them, but knowing they are there helps you breathe.</p>\r\n<p>Then let the plan go and enjoy the moment. The best conversations wander. If you start with food and end up laughing about school days, that is a good date. Keep your phone away, give the person your full attention, and remember that they are probably just as nervous as you are.</p>\r\n\r\n<h2>Reading the mood and ending well</h2>\r\n<p>Watch how the person responds. If they lean in, smile and ask you questions, the conversation is going well. If they answer briefly, look away or check the time often, try changing the subject or ask whether they would like to walk or move on. Not every date will click, and that is perfectly fine.</p>\r\n<p>When it is time to end, be kind and clear. If you enjoyed it, say so: \"I had a lovely time. I would like to see you again.\" If you did not feel a spark, you can say, \"Thank you for today, it was nice to meet you,\" and leave it there. Be honest, but do not be unkind. Our article on <a href=\"/blog/how-to-handle-rejection-gracefully\">handling rejection gracefully</a> can help with that moment.</p>\r\n\r\n<h2>Stay safe while you talk</h2>\r\n<p>A pleasant conversation is a good sign, but it is not a reason to lower your guard. Keep personal details such as your home address and workplace private on a first meeting. Do not discuss lending, investing or helping with money. If the person asks for it, that is a red flag. Tell a friend where you are, arrange your own transport home, and use our <a href=\"/safety-tips\">safety tips</a> as a checklist.</p>\r\n<p>When you are ready to meet someone new, you can <a href=\"/browse\">browse people near you</a>, start a chat, and bring a few of these questions with you. Be curious, be kind, and enjoy the company.</p>', NULL, 'Two people chatting and smiling over cups of tea at a busy cafe', 'First date conversation topics that never run dry', 'Good first date conversation topics, questions to ask, subjects to avoid, and simple ways to handle awkward silence so your first meeting feels relaxed.', 'first date conversation topics', '[{\"q\":\"What should I talk about on a first date?\",\"a\":\"Stick to easy, positive subjects: favourite food, places you enjoy, music, hobbies, funny travel stories, and what you are looking forward to. Ask open questions, listen well, and share a little about yourself so the talk goes both ways.\"},{\"q\":\"What topics should I avoid on a first date?\",\"a\":\"Avoid complaining about exes, detailed money talk, heavy family conflict, strong political arguments, and pressure about marriage or children. These can come up later when you know each other better. Early on, keep things light and curious.\"},{\"q\":\"What do I do if there is an awkward silence?\",\"a\":\"Stay calm, because short pauses are normal. Comment on something around you, ask a light follow-up about an earlier answer, or ask a fun either-or question. You can also smile and say you are a bit nervous, which usually eases things.\"},{\"q\":\"How do I stop being nervous on a first date?\",\"a\":\"Arrive a little early, breathe slowly, and remind yourself the goal is just to enjoy a conversation. Prepare two or three questions in advance, choose a relaxed public place, and remember that the other person is probably nervous too.\"},{\"q\":\"Is it okay to ask about their past relationships?\",\"a\":\"It is better to wait. Light questions about what they are looking for are fine, but detailed talk about exes can feel heavy and invasive on a first meeting. If they bring it up themselves, listen kindly and do not pry for details.\"}]', NULL, NULL, 0, 'published', 1, '2026-09-20 09:12:11', '2026-10-16 08:12:11', '2026-10-16 08:12:11'),
(10, 1, NULL, 'First date ideas in Nairobi: free and budget-friendly', 'first-date-ideas-in-nairobi', 'Looking for first date ideas in Nairobi that are safe, public and easy on the wallet? Try these daytime coffee, park, museum and market ideas.', 'first date, Nairobi, date ideas, budget dates, Karura Forest, dating tips, Kenya', '<p>You do not need an expensive dinner to have a good first meeting. The best first date ideas in Nairobi are simple, public and relaxed, and they give you something to look at and talk about besides each other. This guide shares free and budget-friendly options, from daytime coffee to forest walks, plus a few practical tips so you feel safe and prepared.</p>\r\n<p>A quick note on money: prices change and every place sets its own, so costs vary. We have not listed any prices here. Check entry fees, opening times and menus before you go, and agree on a plan you can both afford.</p>\r\n\r\n<h2>What makes a good first date</h2>\r\n<p>A good first date has three qualities. It is in a public place, it is short, and it is easy to leave. You are meeting someone you know mostly through messages, so the goal is simply to see whether you enjoy each other\'s company in real life.</p>\r\n<p>Activities that give you something to do together tend to work better than a long sit-down meal. Walking or looking around takes the pressure off eye contact, and the surroundings give you natural topics when conversation slows down. Keep the plan to one or two hours. If it goes well, you can always stay longer.</p>\r\n\r\n<h2>Daytime coffee or tea in a busy spot</h2>\r\n<p>The classic for a reason. A coffee, tea or fresh juice at a busy cafe in daylight is low cost, easy to arrange and easy to end politely. Choose a place with other customers, a clear entrance, and good light. Many malls and town centres around Nairobi have such spots.</p>\r\n<ul>\r\n<li>Pick a time when the place is lively, such as mid-morning or early afternoon.</li>\r\n<li>Arrive a few minutes early so you can choose a seat you like.</li>\r\n<li>Order something small. You are not signing up for a long lunch.</li>\r\n<li>Have an easy exit line ready, such as \"I have to be somewhere at four, but this was really nice.\"</li>\r\n</ul>\r\n<p>If you are not sure what to talk about, our list of <a href=\"/blog/first-date-conversation-topics\">first date conversation topics</a> will help you relax.</p>\r\n\r\n<h2>Walks in parks and forest trails</h2>\r\n<p>Walking dates are free or very cheap, and they suit people who feel nervous face to face. Nairobi has green spaces you can enjoy in the daytime, and Karura Forest is one of the best known, with marked trails, bike paths and picnic areas. Other public parks and nature areas around the city and county can work too.</p>\r\n<p>Because you will be outdoors, a few precautions help:</p>\r\n<ul>\r\n<li>Go in the morning or early afternoon, when more people are around.</li>\r\n<li>Stay on the main, busy paths and keep your phone charged.</li>\r\n<li>Check entry rules and fees at the gate, since they vary.</li>\r\n<li>Tell a friend your plan and share your location until you are home.</li>\r\n<li>Wear comfortable shoes and carry water and a light jacket.</li>\r\n</ul>\r\n<p>Walks work best as a second date or a first date with a firm time limit. If the person suggests somewhere secluded, politely suggest a busier spot. A good match will understand.</p>\r\n\r\n<h2>Museums, galleries and cultural spaces</h2>\r\n<p>Museums and galleries are a great way to turn quiet moments into conversation. You can comment on what you see, ask what each other finds interesting, and learn something about their taste without any pressure. Nairobi has museums, art spaces and cultural centres that are open to the public, and many are in busy areas that are easy to reach by matatu, ride-hailing or on foot.</p>\r\n<p>Opening hours and entry fees vary, so check ahead. Some places have cheaper rates for residents or students, and some have free days or events. If you are not sure, a quick call or message before you go will save an awkward moment at the gate.</p>\r\n\r\n<h2>Markets and street food strolls</h2>\r\n<p>A walk through a lively market is full of colour, sound and small surprises, which makes it easy to talk. You can look at crafts, fruit, clothes or books, share a snack, and compare opinions. Keep a hand on your phone and bag in crowded places, and keep your own money for your own purchases.</p>\r\n<p>Food does not have to be fancy. Roasted maize, fresh fruit, chips, samosas or a cup of chai from a busy stall all make good shared moments. A shared snack is also a gentle way to see how someone relaxes. If you like nyama choma, suggest it for a later date at a busy, well-known place in the daytime, rather than a long evening on the first meeting.</p>\r\n\r\n<h2>Simple food spots that will not strain your budget</h2>\r\n<p>If you both want a proper meal, you do not need a costly restaurant. Choose a casual, busy eatery in a public area and keep the order simple. Think of a plate of githeri, a light lunch, a plain pizza or a snack and a drink. Agree on the plan beforehand so nobody is surprised by the bill.</p>\r\n<ul>\r\n<li>Say plainly what you are comfortable spending. Honest people respect that.</li>\r\n<li>Offer to split the bill or take turns on later dates.</li>\r\n<li>Avoid places that demand a set menu or a large deposit.</li>\r\n<li>If your date seems uneasy about paying, keep it light and kind.</li>\r\n</ul>\r\n<p>Money should never be a test, and it should never be a request. If someone asks you to send money, pay for their transport in advance, or cover an emergency before you have even met, treat it as a warning sign and read <a href=\"/blog/should-you-send-money-to-someone-you-met-online\">why you should not send money to someone you met online</a>.</p>\r\n\r\n<h2>First date ideas in Nairobi when it rains</h2>\r\n<p>Nairobi weather can change quickly, so it helps to have a backup. Indoor options include a bookshop browse, a gallery visit, a board game cafe, a food court, or a casual chai in a busy mall. Bring an umbrella and message your date early if plans need to shift. A relaxed attitude to a change of plan is itself a good sign.</p>\r\n<p>If you do not live in Nairobi, the same ideas work in other towns. Our guide to <a href=\"/blog/first-date-ideas-outside-nairobi\">first date ideas outside Nairobi</a> covers more places.</p>\r\n\r\n<h2>Safety checklist before you go</h2>\r\n<h3>Getting there and getting home</h3>\r\n<p>Plan your travel before the date, not during it. Nairobi traffic can be slow, so leave early and tell your date if you are running late. Use whatever transport you trust, whether that is a matatu, a ride-hailing app or your own car, and always arrange your own way home. Do not rely on the other person to take you home on a first meeting.</p>\r\n<p>Keep your phone charged and carry a little cash for emergencies. If the date ends later than planned, message your friend, share your route, and get home before it is dark whenever you can.</p>\r\n<h3>Your pre-date checklist</h3>\r\n<p>Whatever you choose, keep the basics in place. They take a few minutes and make the whole date more relaxing.</p>\r\n<ol>\r\n<li>Meet in a public place and arrive by your own transport.</li>\r\n<li>Tell a friend or family member who you are meeting, where, and when you will check in.</li>\r\n<li>Share your live location with someone you trust.</li>\r\n<li>Keep your drink in sight and your belongings close.</li>\r\n<li>Do not share your home address or workplace on the first meeting.</li>\r\n<li>Leave if anything feels wrong, without explaining.</li>\r\n</ol>\r\n<p>Before meeting, a short video call is a good way to confirm they look and sound like their photos. Our guides to <a href=\"/blog/meet-someone-safely\">meeting someone safely</a> and our <a href=\"/safety-tips\">safety tips</a> page go through the full routine.</p>\r\n\r\n<h2>Plan your first meeting</h2>\r\n<p>The best first date is the one where both of you feel comfortable enough to be yourselves. Pick a public place, keep it short, agree on a plan that suits both budgets, and enjoy the company. If you have not met anyone yet, you can <a href=\"/browse\">browse people near you</a> and start a chat, or <a href=\"/register\">create your free profile</a> and see who says hello.</p>', NULL, 'Two people walking together along a shaded forest path in Nairobi', 'First date ideas in Nairobi on any budget', 'Simple first date ideas in Nairobi that are free or budget-friendly: daytime coffee, park walks, museums and markets, plus safety tips for meeting.', 'first date ideas in Nairobi', '[{\"q\":\"What is a good cheap first date in Nairobi?\",\"a\":\"A daytime walk in a public park or forest trail, a coffee or tea at a busy cafe, or a stroll through a market costs little and keeps the pressure low. Costs vary by place, so check entry fees and prices beforehand.\"},{\"q\":\"Is it safe to go on a first date in a park?\",\"a\":\"It can be, if you choose a popular daytime spot with other people around, stay on main paths, tell a friend where you are, and arrange your own transport. Avoid quiet corners and never go anywhere secluded with someone you have only just met.\"},{\"q\":\"Who should pay on a first date?\",\"a\":\"There is no single rule. Many people split the bill or take turns on later dates, and some prefer that the person who invited pays. Talk about it lightly, be gracious, and choose a plan that suits both budgets so nobody feels stretched.\"},{\"q\":\"How long should a first date last?\",\"a\":\"One to two hours is plenty. A short first date leaves you both wanting more and makes it easy to leave if the chemistry is missing. You can always suggest a second meeting if it went well.\"},{\"q\":\"What should I wear on a first date in Nairobi?\",\"a\":\"Wear something comfortable that suits the plan and the weather. Nairobi mornings can be cool and afternoons sunny or rainy, so a light jacket or umbrella helps. Choose clean, simple clothes you feel confident in, and comfortable shoes if you will be walking.\"}]', NULL, NULL, 0, 'published', 1, '2026-09-21 09:12:11', '2026-10-16 08:12:11', '2026-10-16 08:12:11'),
(11, 6, NULL, 'First date ideas outside Nairobi: easy, safe public spots', 'first-date-ideas-outside-nairobi', 'From Mombasa beaches to Kisumu lakeside walks and Naivasha picnics, here are relaxed first date ideas outside Nairobi that are public, simple and affordable.', 'first date, date ideas, Mombasa, Kisumu, Nakuru, Eldoret, Nyeri, Naivasha', '<p>Nairobi gets most of the attention, but some of the best first dates happen elsewhere. If you are looking for first date ideas outside Nairobi, Kenya has plenty of choice: warm coastal evenings in Mombasa, breezy lakeside walks in Kisumu, green parks in Nakuru, cool mornings in Nyeri and open skies near Naivasha. Smaller towns often have a slower pace, which makes it easier to relax and talk.</p>\r\n<p>This guide offers simple, public and affordable ideas that work in many Kenyan towns, along with practical tips for planning and staying safe. We do not name specific businesses or prices, because those change. Instead, think of these as ideas you can match to your own town.</p>\r\n\r\n<h2>What makes a good first date anywhere</h2>\r\n<p>Before choosing a place, think about what a first date is for. It is a chance to see whether you enjoy each other\'s company, so you want somewhere you can hear each other and feel comfortable. The best first dates share a few qualities:</p>\r\n<ul>\r\n<li>Public and busy enough that you feel safe.</li>\r\n<li>Short and low pressure, usually one to two hours.</li>\r\n<li>Easy to leave if the chemistry is not there.</li>\r\n<li>Something to look at or do, so silences feel natural.</li>\r\n<li>Affordable for both of you.</li>\r\n</ul>\r\n<p>Keep the plan simple. You do not need a fancy restaurant or a long itinerary. A thoughtful, relaxed meeting says more about you than an expensive one. For conversation ideas, see our guide to <a href=\"/blog/first-date-conversation-topics\">first date conversation topics</a>.</p>\r\n\r\n<h2>Mombasa and the coast: first date ideas outside Nairobi with a sea breeze</h2>\r\n<p>The coast has an easy, holiday feel that suits a first meeting. Choose a public beach in daylight or early evening and take a slow walk along the sand, with a stop for fresh coconut water or a cold drink. Many people enjoy a sunset by the water, as long as you pick a busy stretch where other people are around.</p>\r\n<p>In Mombasa you can also wander through the old town, with its narrow streets and old buildings, or sit with a coffee or juice and watch the street life. A ride on the ferry across the channel can be a fun shared experience, and a meal of seafood, samosas or coastal snacks in a busy place is a good way to talk. Check opening times and any entry fees yourself beforehand, and avoid quiet, isolated beaches for a first meeting.</p>\r\n\r\n<h2>Kisumu and the lakeside</h2>\r\n<p>Lake Victoria gives Kisumu a gentle, open setting. A walk along a busy public lakeside area, especially late afternoon, is a pleasant first date. You can watch the fishing boats, enjoy the breeze and talk without the noise of town. Tilapia and other lake fish are a local favourite, so a shared meal at a popular, busy spot can be a nice finish.</p>\r\n<p>If you both enjoy nature, a visit to a public wildlife area or a park near town can be an easy outing, subject to opening times and any fees. As with any date, keep to well-used areas, go in daylight and avoid being alone at the water\'s edge as it gets dark.</p>\r\n\r\n<h2>Nakuru and Naivasha: parks, lakes and open air</h2>\r\n<p>Nakuru offers green spaces and a lively town centre. A walk in a public park, a visit to a busy cafe or a stroll through a local market can all work well. If you both like wildlife, the national park near town is a popular outing, though it takes more planning and transport, so save it for when you know each other better.</p>\r\n<p>Naivasha is known for its lake and scenery. A picnic in a public, well-visited spot, a short boat ride in a group setting or a walk with views can be lovely. Because these outings often need transport and time, arrange them with care, travel in daylight and keep your own way home. For a first meeting, a simple cafe or lunch in town is perfectly fine, and you can plan something bigger later.</p>\r\n\r\n<h2>Eldoret and Nyeri: cool air and slower days</h2>\r\n<p>In the cooler highlands, a warm drink goes a long way. In Eldoret, you can try a cafe, a stroll around town, a visit to a local market or a walk in a park. The area is known for running, so if you are both active, a gentle walk or jog in a public place can be fun and relaxed.</p>\r\n<p>Nyeri has a calm, scenic feel, with views toward Mount Kenya on clear days. A cup of tea or coffee in a pleasant garden or cafe, a walk in a town park or a visit to a local market can all make easy first dates. Because the air can be chilly, carry a light jacket, and have a cosy indoor backup if it rains.</p>\r\n\r\n<h2>Markets, cafes and street food</h2>\r\n<p>Almost every Kenyan town has a market, and markets are good for dates. You get lots to look at, things to talk about and plenty of people around. Try fruit you have never tasted, share roasted maize or a snack, and chat with the stall owners. It is relaxed, affordable and full of personality.</p>\r\n<p>A simple cafe is another reliable option. Pick a busy, welcoming place with good light and music that is not too loud. Order something small, and enjoy the conversation. If you prefer something more active, try a short walk around a town centre, a public garden or a museum. If you want a heartier meal, nyama choma in a busy, well-known spot is a very Kenyan way to share an hour or two.</p>\r\n\r\n<h2>Plan the details, not just the place</h2>\r\n<p>Good dates are often about small details. Check the weather and the time of sunset, and decide how you will each travel and when you will leave. Agree on a clear meeting point, such as the entrance of a park or a well-known junction, so nobody wanders around searching. If the plan needs entry fees or a boat ride, confirm the cost beforehand and agree on who pays, so nobody is surprised.</p>\r\n<p>Think about comfort too. Wear shoes that suit walking, carry water and sun protection at the coast, and bring a light jacket in the highlands. Keep a simple plan B in your head, such as a cafe nearby, in case the rain comes or the place is too crowded. Being prepared helps you relax and focus on your date.</p>\r\n\r\n<h2>Keep it safe and comfortable</h2>\r\n<p>A first date is a meeting between two people who do not know each other well yet, so a few habits are worth keeping, wherever you are. Choose a public place, preferably in daylight or early evening. Arrange your own transport, whether matatu, boda boda or taxi, so you can leave when you wish. Tell a friend where you are going and who you are meeting, and share your live location if possible.</p>\r\n<p>Keep your phone charged, keep M-Pesa and personal details private, and do not leave your drink unattended. Do not accept a lift from someone you have just met. Read our <a href=\"/blog/meet-someone-safely\">checklist for meeting someone safely</a> and our <a href=\"/safety-tips\">safety tips</a> before you go. If something feels wrong, it is perfectly fine to end the date politely and go home.</p>\r\n\r\n<h2>Small towns, privacy and good manners</h2>\r\n<p>In a smaller town, people often know each other, so word can travel. If you value privacy, pick a spot slightly away from your usual places, or meet in a nearby town. Do not share details of your date with others, and be discreet about the person you are seeing until you both agree to share.</p>\r\n<p>Good manners matter everywhere. Be on time, be friendly to everyone, and agree early on how you will handle the bill, whether one person pays or you split. Our guide to <a href=\"/blog/dating-etiquette-in-kenya\">dating etiquette in Kenya</a> has more on this. If you have just started chatting with someone nearby, <a href=\"/browse\">browse people near you</a>, or <a href=\"/register\">join for free</a>. And if you are heading to the capital, see our guide to <a href=\"/blog/first-date-ideas-in-nairobi\">first date ideas in Nairobi</a>. Wherever you meet, a relaxed plan and a warm smile are the best start.</p>', NULL, 'Two people walking along a lakeshore at sunset on a first date', 'First date ideas outside Nairobi: easy, public spots', 'Looking for first date ideas outside Nairobi? Try beaches, lakeside walks, markets, cafes and parks in Mombasa, Kisumu, Nakuru, Eldoret, Nyeri and Naivasha.', 'first date ideas outside Nairobi', '[{\"q\":\"What are good first date ideas outside Nairobi?\",\"a\":\"Simple, public and relaxed plans work best: a walk by a lake or beach, a visit to a local market, a coffee or tea at a busy cafe, a stroll in a park or a shared lunch. Pick something where you can talk easily and leave comfortably.\"},{\"q\":\"Is it safe to meet a first date in a smaller town?\",\"a\":\"It can be, with the same care you would take anywhere. Choose a busy public place in daylight, arrange your own transport, tell a friend where you are, and avoid isolated spots. Trust your instincts and leave if you feel uneasy.\"},{\"q\":\"How much should I spend on a first date?\",\"a\":\"You do not need to spend much. A cup of tea, a fruit snack or a walk can make a lovely first date. Choose something that suits both budgets, and talk openly about who pays so nobody feels awkward or pressured.\"},{\"q\":\"What if the town is small and everyone knows everyone?\",\"a\":\"That can be both a comfort and a challenge. If privacy matters, choose a place a bit away from your usual haunts, or go to a nearby town. Be respectful about not sharing details of your date with others.\"},{\"q\":\"What should I do if the weather is bad on the day?\",\"a\":\"Have a cosy backup, such as a cafe, a restaurant or a museum. Check the weather the day before, message your date early with a simple alternative, and stay flexible. A change of plan can even make the date more memorable.\"}]', NULL, NULL, 0, 'published', 0, '2026-09-22 09:12:11', '2026-10-16 08:12:11', '2026-10-16 08:12:11'),
(12, 5, NULL, 'Green flags and red flags in early dating to watch for', 'green-flags-and-red-flags-in-early-dating', 'How to tell a promising connection from a worrying one in the first weeks, with clear examples of green flags and red flags and what to do about them.', 'green flags, red flags, early dating, relationships, dating advice, boundaries, Kenya', '<p>The first few weeks of getting to know someone are exciting, and that excitement can make it hard to see clearly. Learning to notice green flags and red flags helps you slow down, trust your instincts and spend your energy on people who treat you well. It is not about being suspicious of everyone, it is about paying attention.</p>\r\n<p>Below you will find the signs to look for in how someone communicates, respects your boundaries and behaves over time, plus practical steps for what to do when something feels off. These apply to dating, but most also fit new friendships.</p>\r\n\r\n<h2>Why early signs are worth noticing</h2>\r\n<p>People usually show you their character in small moments: how they answer a message, how they speak about others, how they react when plans change. Those moments are easier to read at the start, before feelings are deep. Once you are attached, it is much harder to step back and see clearly.</p>\r\n<p>A flag is a signal, not a verdict. A green flag does not promise a perfect relationship, and a red flag does not always mean someone is a bad person. Together, though, they help you decide how fast to move and how much trust to give. Think of them as road signs rather than a final map.</p>\r\n\r\n<h2>Green flags to look for</h2>\r\n<p>Healthy early dating usually feels calm, not like a rollercoaster. Look for these signs:</p>\r\n<ul>\r\n<li><strong>Consistent communication.</strong> They reply in a steady, natural way and do not disappear for days and then flood you with attention.</li>\r\n<li><strong>Doing what they say.</strong> If they say they will call at eight, they call, or they let you know if something changes.</li>\r\n<li><strong>Curiosity about you.</strong> They ask questions, remember the answers and bring them up later.</li>\r\n<li><strong>Respect for your boundaries.</strong> When you say no or not yet, they accept it without sulking or pressure.</li>\r\n<li><strong>Honesty about the basics.</strong> Their age, work, family situation and relationship status match what they told you.</li>\r\n<li><strong>Kindness to others.</strong> They treat waiters, drivers, family and friends with courtesy, not only you.</li>\r\n</ul>\r\n<p>A useful test is how you feel after talking to them. If you usually feel relaxed, valued and a bit happier, that is a good sign. If you usually feel anxious or confused, pay attention to that too.</p>\r\n\r\n<h2>Red flags to take seriously</h2>\r\n<p>Some signs deserve a pause, and a few deserve an exit. Watch for these:</p>\r\n<ul>\r\n<li><strong>Pushing past your limits.</strong> Pressuring you to meet somewhere private, share personal details or move faster than you want.</li>\r\n<li><strong>Asking for money.</strong> Any request for money, airtime, fees or a loan early on, whatever the reason or emergency.</li>\r\n<li><strong>Stories that do not add up.</strong> Details that change, a life that sounds too perfect or reasons why they cannot video call or meet.</li>\r\n<li><strong>Too much, too soon.</strong> Intense declarations of love within days, or calling you their future spouse before you have met.</li>\r\n<li><strong>Controlling behaviour.</strong> Telling you who to talk to, getting angry when you are busy or demanding to see your phone.</li>\r\n<li><strong>Disrespect.</strong> Mocking you, putting down your friends or speaking badly of every ex.</li>\r\n</ul>\r\n<p>The money one is especially important. Read our guides on <a href=\"/blog/romance-scams-in-kenya-warning-signs\">romance scams in Kenya</a> and <a href=\"/blog/should-you-send-money-to-someone-you-met-online\">whether to send money to someone you met online</a> before you ever consider helping someone you have not met in person.</p>\r\n\r\n<h2>Yellow flags and mixed signals</h2>\r\n<p>Not everything fits neatly into green or red. There is a middle area of mixed signals that need a conversation rather than a decision. Examples include a person who is very busy and replies slowly, someone who is shy and takes time to open up, or a person who is still healing from a past relationship.</p>\r\n<p>The way to handle these is to talk about them kindly. You might say, \"I have noticed we only talk late at night. Can we plan something earlier in the week?\" Their response tells you a lot. A person who listens and adjusts is showing a green flag. A person who gets defensive, mocks you or ignores the request is showing something else.</p>\r\n\r\n<h2>Green flags and red flags in how they handle conflict</h2>\r\n<p>You cannot know how someone behaves under pressure until they face a small disagreement. The early weeks usually offer a few chances: a cancelled plan, a misunderstood message, a difference of opinion.</p>\r\n<p>Healthy responses include listening without interrupting, apologising when they are wrong and asking what you need. Unhealthy ones include shouting, going silent for days as punishment, blaming you for everything or turning the argument into a joke. Notice whether you can say \"that bothered me\" and still feel safe afterwards. If you cannot, that is important information.</p>\r\n\r\n<h2>Flags you can spot in messages before you meet</h2>\r\n<p>You can learn a lot from chatting. Watch how the conversation feels over the first week or two. A green flag sounds like \"Take your time, I am happy to chat until you are comfortable,\" or \"Would you like to do a video call first?\" A red flag sounds like \"Give me your number now,\" \"Why are you asking so many questions?\" or \"I cannot call, but I can tell you I love you.\"</p>\r\n<p>Pay attention to how someone responds when you slow things down. If you say you would rather meet in a public place, a good person will agree at once. If you say you would prefer not to share your address, a good person will not argue. Someone who sulks, pushes or suddenly cools off when you set a simple limit is telling you something important. Also notice whether the conversation is balanced. Do they ask about you as much as they talk about themselves, or does every chat circle back to their needs?</p>\r\n\r\n<h2>Trust your gut, then check the facts</h2>\r\n<p>Your instincts are useful. If something feels off, you do not need a perfect reason before you slow down. At the same time, do not rely on feelings alone. Check what you can. Ask for a video call, see whether their story stays consistent, and talk to a friend who can look at the situation from outside.</p>\r\n<p>Our guide on <a href=\"/blog/how-to-verify-someone-is-real-before-meeting\">how to verify someone is real before meeting</a> has simple steps, and the <a href=\"/safety-tips\">safety tips</a> page covers what to do before a first meeting. Looking for a verified badge on a profile can also help, as we explain in <a href=\"/blog/why-profile-verification-matters\">why profile verification matters</a>.</p>\r\n\r\n<h2>What to do when you spot a flag</h2>\r\n<p>When you notice a red flag, you have four sensible options, depending on how serious it is.</p>\r\n<ol>\r\n<li><strong>Pause.</strong> Slow the pace, take a day to think and do not make big decisions while feeling swept up.</li>\r\n<li><strong>Talk about it.</strong> For small or unclear things, say what you noticed and ask a calm question.</li>\r\n<li><strong>Set a boundary.</strong> Tell them clearly what you will and will not accept, then see whether they respect it.</li>\r\n<li><strong>Leave.</strong> For pressure, lies, controlling behaviour or money requests, step away. You do not owe anyone a long explanation.</li>\r\n</ol>\r\n<p>If someone makes you feel unsafe, stop replying, block them and report them. Our guide on <a href=\"/blog/how-to-block-and-report-someone-online\">how to block and report someone online</a> explains how. Remember that choosing yourself is not rude. It is how you make room for people who deserve your time. When you are ready, <a href=\"/browse\">browse people near you</a> and keep these signs in mind as you chat.</p>', NULL, 'Two people talking comfortably over tea at a cafe table', 'Green flags and red flags in early dating', 'Learn the green flags and red flags in early dating, from how someone communicates to how they handle boundaries, and what to do when you spot them.', 'green flags and red flags', '[{\"q\":\"What are green flags in early dating?\",\"a\":\"Green flags are signs of a healthy connection, such as consistent communication, respect for your boundaries, honesty about who they are, curiosity about you and keeping their word. They show up in small everyday behaviour, not only in big gestures.\"},{\"q\":\"What is the biggest red flag in the early weeks?\",\"a\":\"Disrespect for your boundaries is the most important one. If someone pushes after you say no, pressures you to move faster or asks for money, that matters more than any other sign. It tells you how they will treat you when you disagree.\"},{\"q\":\"Is one red flag enough to walk away?\",\"a\":\"It depends on the flag. A single small thing, like being late once, is worth a calm conversation. Serious ones, such as pressure, lying, controlling behaviour or asking for money, are reasons to step back quickly. Notice patterns rather than judging one moment.\"},{\"q\":\"Can I ignore a red flag if I really like the person?\",\"a\":\"Liking someone makes red flags easy to explain away, so be honest with yourself. Ask what you would tell a close friend in the same situation. If you would advise caution, take your own advice and talk to someone you trust.\"},{\"q\":\"What should I do if I notice a red flag?\",\"a\":\"Name it calmly, either to yourself or to the other person, and watch how they respond. Someone who listens and changes is different from someone who gets defensive. If you feel unsafe or pressured, end the contact, block them and report them if needed.\"}]', NULL, NULL, 0, 'published', 1, '2026-09-24 09:12:11', '2026-10-16 08:12:11', '2026-10-16 08:12:11');
INSERT INTO `blog_posts` (`id`, `category_id`, `author_id`, `title`, `slug`, `excerpt`, `tags`, `content`, `cover_path`, `cover_alt`, `meta_title`, `meta_description`, `focus_keyword`, `faq`, `og_image`, `canonical_url`, `noindex`, `status`, `views`, `published_at`, `created_at`, `updated_at`) VALUES
(13, 1, NULL, 'How to ask someone out over text, with examples to copy', 'how-to-ask-someone-out-over-text', 'Wondering how to ask someone out over text without it feeling awkward? Here is when to do it, what to write, and how to handle any answer.', 'ask someone out, texting, first date, dating tips, messaging, Kenya', '<p>Asking someone out is nerve-racking, and doing it through a screen does not always make it easier. Still, learning how to ask someone out over text is a useful skill, especially when you met online and most of your chats happen on your phone. A clear, kind message makes it easy for the person to say yes, and just as easy to say no without feeling awkward.</p>\r\n<p>This guide covers when to ask, what to write, example messages you can adapt, and how to respond to every kind of answer, so you can send that text with confidence.</p>\r\n\r\n<h2>Know when the time is right</h2>\r\n<p>There is no exact number of days or messages that makes the moment right, but there are signs. The conversation flows without effort. They reply with real sentences, ask you questions, and share small details about their life. You have both laughed at something together. These are good signs that a meeting would be welcome.</p>\r\n<p>It is also possible to wait too long. If you chat for weeks without any mention of meeting, the friendship may settle into a text-only routine, and one of you may drift away. Once you have had a few days of good conversation, a simple invitation usually feels natural. Our guide on <a href=\"/blog/signs-someone-likes-you-online\">signs someone likes you online</a> can help you read the mood.</p>\r\n\r\n<h2>Be clear, specific and low pressure</h2>\r\n<p>The best invitations have three parts: a warm opening, a specific plan, and an easy way out. Vague messages such as \"we should meet sometime\" are easy to ignore. A specific one gives the other person something to say yes to.</p>\r\n<ul>\r\n<li><strong>Warm opening:</strong> \"I have really enjoyed chatting with you.\"</li>\r\n<li><strong>Specific plan:</strong> \"Would you like to get coffee this Saturday around 3?\"</li>\r\n<li><strong>Easy way out:</strong> \"No pressure at all if you would rather wait.\"</li>\r\n</ul>\r\n<p>Putting it together: \"I have really enjoyed chatting with you this week. Would you like to get coffee on Saturday around 3? No pressure at all if you would rather wait.\" It is friendly, clear, and leaves room for an honest answer.</p>\r\n\r\n<h2>How to ask someone out over text: examples to copy</h2>\r\n<p>Use these as starting points and change the words so they sound like you. People can tell when a message is copied, so add one personal detail from your chats.</p>\r\n<ol>\r\n<li><strong>Simple and warm:</strong> \"I am enjoying our conversations. Want to meet for chai this weekend? There is a busy place near the mall I like.\"</li>\r\n<li><strong>Based on a shared interest:</strong> \"You said you love walking trails. Would you like to do a morning walk together on Sunday? We can keep it short.\"</li>\r\n<li><strong>Light and funny:</strong> \"I think we have talked enough about nyama choma. Time to see if we agree on the real thing. Lunch on Saturday?\"</li>\r\n<li><strong>Shy but honest:</strong> \"I am a bit nervous typing this, but I would really like to meet you. Would coffee on Friday afternoon work?\"</li>\r\n<li><strong>Very low pressure:</strong> \"No rush at all, but if you ever feel like meeting for a quick juice somewhere public, I would be happy to.\"</li>\r\n</ol>\r\n<p>Notice that every example names a public setting or an easy activity. That helps both people feel safe. For more plans that suit most budgets, see our <a href=\"/blog/first-date-ideas-in-nairobi\">first date ideas in Nairobi</a>.</p>\r\n\r\n<h2>Choose the plan wisely</h2>\r\n<p>A simple, short plan is easier to accept than a big one. Coffee, tea, a walk, or a casual lunch all work. Avoid asking someone to your home, to a private place, or to a long evening out on a first meeting. It puts pressure on them and it is not safe for either of you.</p>\r\n<p>Be flexible about the time and offer to let them pick the place if they prefer. Say plainly that you will make your own way there. It is a small thing that shows you respect their comfort. If they suggest a different public spot or time, take it as a good sign: they are already imagining the date.</p>\r\n\r\n<h2>Avoid messages that make it harder</h2>\r\n<p>Some invitations, however well meant, create stress. Try to avoid these:</p>\r\n<ul>\r\n<li>Asking late at night when the mood is unclear.</li>\r\n<li>Sending a long emotional message before you have even met.</li>\r\n<li>Saying \"you owe me a date\" or anything that sounds like pressure.</li>\r\n<li>Asking them to pay or send money for transport or tickets.</li>\r\n<li>Sending repeated messages when they have not replied.</li>\r\n</ul>\r\n<p>Also avoid asking in a group chat or in a way that puts them on the spot publicly. A private message is kinder and gives them freedom to answer honestly.</p>\r\n\r\n<h2>Ask in your own voice</h2>\r\n<p>You do not need perfect words. Write the way you normally speak, in the language you and the other person use most, whether that is English, Swahili or a mix of both. A message that sounds like you feels honest, and honesty is attractive. If you usually joke around, keep a little humour. If you are more quiet and sincere, let that show instead.</p>\r\n<p>Read your text once before sending to check that the day, time and place are clear. Then send it and trust yourself. A short, warm invitation will always sound better than a long speech full of apologies.</p>\r\n\r\n<h2>What to do while you wait for a reply</h2>\r\n<p>Hitting send is the hard part. After that, try to carry on with your day. Put the phone down, go for a walk, or talk to a friend. Checking every two minutes only raises your stress and does not make a reply arrive sooner.</p>\r\n<p>Give them at least a day. People may be at work, travelling, or thinking about their answer. If a whole day or two passes, you can send one light follow-up such as \"No rush on my message, just wanted to say I am happy to chat either way.\" Then leave the next step to them. Our <a href=\"/blog/texting-rules-early-dating\">texting rules for early dating</a> explain timing in more detail.</p>\r\n\r\n<h2>How to respond to yes, maybe or no</h2>\r\n<p>If they say yes, confirm the details clearly: day, time and place, and say you are looking forward to it. Share your plan with a friend and keep your own transport arranged.</p>\r\n<p>If they say maybe or suggest a different day, that is usually a good sign. Pick a new time that works, and do not take a change of plan personally. If they say they are not sure yet, you can reply, \"No problem, we can keep chatting and see how we feel.\" Then give it time without pressure.</p>\r\n<p>If they say no, thank them for being honest. A short answer is best: \"Thanks for telling me, I appreciate it. Take care.\" Do not argue, bargain, or ask for reasons. A no is not a failure, it is information, and it saves you both time. You can read more in our guide to <a href=\"/blog/how-to-handle-rejection-gracefully\">handling rejection gracefully</a>.</p>\r\n\r\n<h2>Keep the first meeting safe</h2>\r\n<p>Once they say yes, remember the basics. Meet in a public place in the daytime, tell a friend where you will be, and arrive and leave on your own. Do not share your home address or workplace yet. If something does not feel right when you arrive, you are allowed to leave. Our <a href=\"/safety-tips\">safety tips</a> page has a full checklist.</p>\r\n<p>If you have not found someone to invite yet, you can <a href=\"/browse\">browse people near you</a> and start a conversation. A calm, specific, friendly text is all you need. Send it, let it go, and trust that the right person will be glad you asked.</p>', NULL, 'A young man typing a message on his phone outside a cafe', 'How to ask someone out over text (with examples)', 'Learn how to ask someone out over text: the right time, clear wording, real examples you can copy, and how to respond kindly to a yes, a maybe or a no.', 'how to ask someone out over text', '[{\"q\":\"Is it okay to ask someone out over text?\",\"a\":\"Yes, especially if you met online or chat mostly by message. A clear, friendly text gives the other person time to think and a comfortable way to answer. Just keep it short, specific, and pressure free.\"},{\"q\":\"How long should I chat before asking someone out?\",\"a\":\"There is no fixed number of days. When the conversation flows, you both reply with energy, and you have shared a few real details, it is a good time. Waiting for months can make the chat feel stuck and lose momentum.\"},{\"q\":\"What do I write when asking someone out?\",\"a\":\"Say that you enjoy talking, suggest a specific plan in a public place, and offer an easy way out. For example: \\\"I have enjoyed our chats. Would you like to get coffee on Saturday afternoon? No pressure if not.\\\"\"},{\"q\":\"What if they say they are busy?\",\"a\":\"A busy answer can be real. If they suggest another day, great. If they only say they are busy and offer nothing else, they may not be interested. Reply kindly, leave the door open once, and then move on without chasing.\"},{\"q\":\"Should I ask them out on a call instead?\",\"a\":\"A call can feel warm, but text is fine and often less stressful. Some people like to do a short call first to confirm comfort before meeting. Choose whichever suits you both, and always meet in a public place for the first date.\"}]', NULL, NULL, 0, 'published', 1, '2026-09-25 09:12:11', '2026-10-16 08:12:11', '2026-10-16 08:12:11'),
(14, 2, NULL, 'How to block and report someone online', 'how-to-block-and-report-someone-online', 'When a chat turns uncomfortable, you can step away. Learn how to block, report, keep evidence, handle harassment and get help in Kenya if you feel in danger.', 'block, report, harassment, online safety, evidence, GBV helpline, Kenya', '<p>Most conversations online are pleasant, but sometimes one goes wrong. A message makes you uncomfortable, someone will not take no for an answer, or a stranger begins to demand money or photos. Knowing how to block and report someone online means you can end the contact quickly, keep yourself safe and help others at the same time. This guide shows you when to act, what to save first and where to get help in Kenya.</p>\r\n<p>You never need to wait for something terrible to happen. If a chat makes you uneasy, you are allowed to leave it. You do not owe anyone politeness at the cost of your own peace.</p>\r\n\r\n<h2>When to block someone</h2>\r\n<p>Blocking is a quiet, private way of closing the door. It stops the person from messaging you or seeing your profile. You can use it as soon as you feel the need, and there is no rule saying you must give a warning first.</p>\r\n<p>Think about blocking when someone:</p>\r\n<ul>\r\n<li>Keeps messaging after you said you are not interested.</li>\r\n<li>Sends rude, insulting or threatening messages.</li>\r\n<li>Pressures you for photos, money, your address or your number.</li>\r\n<li>Becomes angry or guilt-tripping when you set a boundary.</li>\r\n<li>Sends messages that make you feel afraid or uncomfortable, even if you cannot say why.</li>\r\n<li>Appears to be using a fake identity.</li>\r\n</ul>\r\n<p>Feeling uneasy is reason enough. You do not need to prove that someone has done something wrong in order to protect your own space.</p>\r\n\r\n<h2>How to block and report someone online: step by step</h2>\r\n<p>The exact buttons vary from app to app, but the process is similar almost everywhere. Here is a simple routine you can follow.</p>\r\n<ol>\r\n<li><strong>Save evidence first.</strong> Take screenshots of the chat, the profile and any payment details before you do anything else.</li>\r\n<li><strong>Do not reply or argue.</strong> A final angry message rarely helps and can encourage more contact.</li>\r\n<li><strong>Open their profile or chat.</strong> Look for the menu, usually three dots or a flag icon.</li>\r\n<li><strong>Choose block.</strong> This ends their access to you.</li>\r\n<li><strong>Choose report.</strong> Pick the reason that fits best and add a few details, such as what they said and when.</li>\r\n<li><strong>Check your settings.</strong> Review who can see your profile and your last active time.</li>\r\n<li><strong>Tell someone you trust.</strong> You do not have to carry this by yourself.</li>\r\n</ol>\r\n<p>On ConnectHub, you can block and report from any profile or conversation. Reports are reviewed so that accounts that break the rules can be dealt with. Our <a href=\"/safety-tips\">safety tips</a> page also explains how we expect everyone to behave.</p>\r\n\r\n<h2>What to say, and what not to say</h2>\r\n<p>You are not required to explain yourself before you block. Still, if you want to end a conversation politely, one short message is enough, and then you stop. Here are a few lines you can copy.</p>\r\n<ul>\r\n<li>\"Thank you for chatting, but I do not feel a connection. I wish you well.\"</li>\r\n<li>\"Please do not message me again. I will block this account if you do.\"</li>\r\n<li>\"I am not comfortable with what you are asking, so I am ending this chat.\"</li>\r\n</ul>\r\n<p>If the person is rude, threatening or pressuring you for money or photos, skip the message altogether and go straight to block and report. Long explanations invite arguments, and arguments give the other person something to hold on to. Remember that a boundary does not need a reason to be valid.</p>\r\n\r\n<h2>Keep evidence safely</h2>\r\n<p>Evidence matters if you later need to report to your bank or to the authorities. It is easy to lose once you block someone, so save it first.</p>\r\n<ul>\r\n<li>Screenshots of messages that show the date and time.</li>\r\n<li>The profile name, username, photos and any phone numbers.</li>\r\n<li>M-Pesa, bank or airtime receipts if any money was requested or sent.</li>\r\n<li>Links or account names on other platforms.</li>\r\n<li>A short note of what happened, written the same day while you remember the details.</li>\r\n</ul>\r\n<p>Store copies somewhere safe, such as a folder on your phone or an email to yourself, not only inside the app. Do not edit screenshots, and do not delete the original messages. If you are worried about your own privacy while doing this, our guide to <a href=\"/blog/protect-your-privacy-on-dating-apps\">protecting your privacy on dating apps</a> may help.</p>\r\n\r\n<h2>Handling harassment and unwanted contact</h2>\r\n<p>Harassment can take many forms. It might be constant messages, insults, threats, spreading rumours or turning up on other platforms after you blocked someone. It can be hard to know how to react, so here is a steady approach.</p>\r\n<p>First, do not respond. Sometimes people continue because they get a reaction. Second, block every account or number they use, and report each one. Third, tighten your privacy: make social media accounts private, remove personal details and change any passwords you shared. Fourth, tell a friend or family member so someone else knows what is happening.</p>\r\n<p>If the behaviour continues or becomes frightening, you can report it to the Directorate of Criminal Investigations (DCI) with your screenshots. You do not need to wait until you are sure it is a crime. The officers can tell you what steps are possible.</p>\r\n\r\n<h2>If someone is blackmailing or threatening you</h2>\r\n<p>Some people threaten to share private photos or information unless you pay or send more. This is frightening, but it is not your fault, and paying rarely makes it stop. Stop replying, keep the messages, block the account and report it. Contact your mobile money provider if you have sent money, and report to the DCI.</p>\r\n<p>If you feel that you or someone else is in immediate danger, call 999 or 112 straight away. You can say clearly: \"I am being threatened by someone I met online.\" Try also to tell a trusted person so you are not alone with it. For other signs of a scam, read our guide to <a href=\"/blog/romance-scams-in-kenya-warning-signs\">romance scams in Kenya</a>.</p>\r\n\r\n<h2>Where to get help in Kenya</h2>\r\n<p>You do not have to handle serious situations alone. Here are key contacts to keep handy.</p>\r\n<ul>\r\n<li><strong>999 or 112:</strong> emergency numbers if you or someone else is in immediate danger.</li>\r\n<li><strong>1195:</strong> the national gender-based violence helpline, for anyone experiencing or worried about violence or abuse.</li>\r\n<li><strong>116:</strong> Childline Kenya, only if a child or a minor is involved. If you believe someone you are talking to is a minor, stop the conversation and report the profile.</li>\r\n<li><strong>The DCI:</strong> for reporting fraud, blackmail, threats and persistent harassment.</li>\r\n<li><strong>Your bank or mobile money provider:</strong> if you have sent money.</li>\r\n</ul>\r\n<p>These services exist to help. You do not need perfect evidence or a perfect story before you reach out.</p>\r\n\r\n<h2>Looking after yourself afterwards</h2>\r\n<p>Dealing with an unpleasant experience can leave you feeling tired, embarrassed or on edge. All of that is normal. Take a break from messaging if you need one, talk to a friend or a counsellor, and remind yourself that you did the right thing by stepping away.</p>\r\n<p>When you feel ready, you can return to meeting people with a few extra habits: ask for a video call early, keep personal details private for now, and follow the steps in our guide to <a href=\"/blog/meet-someone-safely\">meeting someone safely</a>. You can also <a href=\"/browse\">browse verified profiles near you</a> or <a href=\"/register\">create a free profile</a> whenever you like. Most people you meet will be kind, and you now know exactly what to do on the rare occasion that they are not.</p>', NULL, 'A person tapping the block option on a phone screen', 'How to block and report someone online safely', 'Learn how to block and report someone online: when to do it, what evidence to keep, how to handle harassment and where to get help in Kenya.', 'block and report someone online', '[{\"q\":\"What is the difference between blocking and reporting?\",\"a\":\"Blocking stops a person from contacting you or seeing your profile, and it is private. Reporting tells the platform about their behaviour so it can review the account and act. You can do both at once, and doing both helps protect other users too.\"},{\"q\":\"Will the person know that I blocked or reported them?\",\"a\":\"Blocking is usually quiet, though they may notice they can no longer reach you. Reports are handled by the platform and are not shown to the person you reported. You do not need to explain or warn them, and you do not owe them a goodbye.\"},{\"q\":\"What evidence should I keep before I block someone?\",\"a\":\"Take screenshots of the messages, the profile, usernames, phone numbers and any payment details such as M-Pesa messages. Note dates and times. Save this in a safe place before you block, because you may not be able to see the chat afterwards.\"},{\"q\":\"What if the person keeps contacting me from new accounts or numbers?\",\"a\":\"Block each new account or number, report each one, and keep screenshots of every attempt. Tell a trusted friend, and consider reporting persistent harassment to the Directorate of Criminal Investigations (DCI). If you feel in danger, call 999 or 112.\"},{\"q\":\"Who can I call if I am in danger or experiencing violence?\",\"a\":\"If you are in immediate danger, call 999 or 112. In Kenya, the national gender-based violence helpline is 1195. If a child is involved, Childline Kenya is 116. These services are there to help, and you do not need proof before you call.\"}]', NULL, NULL, 0, 'published', 1, '2026-09-26 09:12:11', '2026-10-16 08:12:11', '2026-10-16 08:12:11'),
(15, 1, 1, 'How to handle rejection gracefully, giving and receiving', 'how-to-handle-rejection-gracefully', 'Rejection hurts on both sides. Learn how to handle rejection gracefully, whether you are hearing a no or kindly giving one, with words you can use.', 'rejection, dating tips, confidence, kindness, moving on, online dating, Kenya', '<p>Nobody enjoys hearing no. Whether it is a message that never gets answered, a polite \"I do not feel a connection\", or a date that ends with no second one, rejection stings. Learning how to handle rejection gracefully will not remove the sting, but it will protect your dignity, your confidence and your reputation. And since everyone who dates will also have to say no at some point, it is just as important to learn how to give a no kindly.</p><p>This guide covers both sides, with simple wording you can use and habits that help you bounce back.</p><h2>Why rejection hurts so much</h2><p>Being turned down touches something deep. We want to be wanted, and a no can feel like a verdict on who we are. That feeling is normal, and it does not mean you are weak. It means you were brave enough to try.</p><p>It helps to remember what a no actually means. In most cases it says only that this particular person did not feel the spark, did not have the time, or was looking for something different. It does not measure your looks, your worth or your future. Two good people can simply not fit. Seeing it this way makes it far easier to carry.</p><h2>How to handle rejection gracefully when you receive it</h2><p>The first minutes matter most. Pause before you reply. If you are hurt or angry, do not type anything yet. Put the phone down, breathe, and give yourself a little time. Then, if a reply is needed, keep it short and kind.</p><ul>\r\n<li>\"Thanks for being honest. I wish you all the best.\"</li>\r\n<li>\"I appreciate you telling me. Take care.\"</li>\r\n<li>\"No problem at all. It was nice talking to you.\"</li>\r\n</ul><p>That is enough. You do not owe an argument, a debate, or an explanation of your feelings. A calm reply shows maturity, and it leaves you feeling better later, because you will not regret what you wrote. Do not beg, bargain or ask for a second chance in the same message.</p><h2>What not to do after a no</h2><p>Some reactions feel natural but make things worse. Avoid these:</p><ol>\r\n<li>Sending several angry or sad messages in a row.</li>\r\n<li>Asking \"why\" again and again, or demanding a reason.</li>\r\n<li>Insulting the person, or telling friends nasty stories about them.</li>\r\n<li>Creating new accounts or using other numbers to contact them.</li>\r\n<li>Checking their profile repeatedly and reading meaning into it.</li>\r\n<li>Making guilt-based comments like \"after all I did for you\".</li>\r\n</ol><p>Persistent contact after a clear no is not romance. It is pressure, and it can make the other person feel unsafe. If they ask you to stop, stop. Respecting a no is one of the clearest signs of good character.</p><h2>The special sting of silence</h2><p>Sometimes there is no clear no at all. The replies get shorter, then stop. This is often harder than a direct refusal, because you are left guessing. It is tempting to send message after message hoping for an answer, but that usually deepens the hurt.</p><p>A good approach is to send one calm check-in, such as \"I have not heard from you, so I will assume you are not interested. No hard feelings, take care.\" Then let it rest. You have been clear and kind, and you have given yourself permission to move on without waiting for an answer that may never come. Closure is something you can give yourself, even when the other person does not.</p><h2>Looking after yourself afterwards</h2><p>Rejection leaves a bruise, so treat it like one. Give yourself a day or two to feel disappointed without pretending you are fine. Then do things that restore you.</p><ul>\r\n<li>Talk to a trusted friend or family member who will listen without judging.</li>\r\n<li>Get outside. A walk, a run or time at a park clears the mind.</li>\r\n<li>Keep your routine: sleep, meals, exercise and work.</li>\r\n<li>Write down what you learned, or what you liked about the experience.</li>\r\n<li>Limit time scrolling their pictures or replaying the chat.</li>\r\n</ul><p>If you are feeling low for a long time, or the rejection touches an old wound, speak to someone you trust or a counsellor. Looking for support is a strength. If you are wondering whether you are ready to meet someone new, our guide on <a href=\"/blog/how-to-know-if-you-are-ready-to-date-again\">how to know if you are ready to date again</a> may help.</p><h2>How to give a no kindly</h2><p>Now the other side. Saying no is uncomfortable, and many people avoid it by going silent. Silence may feel easier for you, but it leaves the other person guessing and hoping. A brief, honest message is far kinder, even if it is hard to write.</p><p>A good no has three parts: thanks, clarity and good wishes.</p><ul>\r\n<li>\"Thank you for your messages, I have enjoyed chatting. I do not feel we are a match, but I wish you all the best.\"</li>\r\n<li>\"I appreciate you asking me out. I am going to say no, but I hope you find someone wonderful.\"</li>\r\n<li>\"After the date, I can see we want different things. Thank you for a pleasant afternoon.\"</li>\r\n</ul><p>Avoid vague lines such as \"maybe later\" or \"I am busy\" when you mean no. They give false hope and drag things out. You do not need to list reasons, and you should never insult the person. Say it once, kindly, and stay firm if they respond with disappointment.</p><h2>When someone will not accept your no</h2><p>Most people take a no with grace. A few do not. If someone keeps messaging after a clear refusal, insults you, makes threats, or guilt-trips you, you are not required to keep being polite. Your safety comes first.</p><ol>\r\n<li>State your decision once, clearly: \"I have said no. Please do not contact me again.\"</li>\r\n<li>Stop replying. Do not argue or explain further.</li>\r\n<li>Block the person, and save screenshots in case you need them.</li>\r\n<li>Report their profile to the platform so they cannot do the same to others.</li>\r\n<li>Tell a friend or family member if you feel threatened.</li>\r\n</ol><p>Our article on <a href=\"/blog/how-to-block-and-report-someone-online\">how to block and report someone online</a> walks through these steps, and our <a href=\"/safety-tips\">safety tips</a> page has more advice for staying safe.</p><h2>Turn rejection into growth</h2><p>Once the sting fades, you can look back with a clear head. Not every no needs a lesson, but a few questions can help. Was my message specific and polite? Did I ask at a good time? Was my profile showing the real me? Sometimes small changes, like clearer photos or a warmer bio, make a difference. You might find our guides on <a href=\"/blog/how-to-write-a-dating-profile-bio\">writing a dating profile bio</a> and <a href=\"/blog/best-dating-profile-photos\">the best dating profile photos</a> useful.</p><p>Also remember that every no brings you closer to a yes that fits. Rejection is part of meeting people, in the same way that rain is part of a Nairobi year. It passes, and things grow afterwards.</p><h2>Keep going, kindly</h2><p>You will be rejected, and you will reject others. Handled well, both can be done with respect. Receive a no with calm, give a no with kindness, protect yourself when someone will not listen, and look after your heart in between.</p><p>When you feel ready, <a href=\"/browse\">browse people near you</a> and start a conversation. There are many people out there, and the right one will be glad to hear from you.</p>', 'blog/d1cbf0543a99d54d.jpg', 'A young woman looking calmly out of a window with a cup of tea in her hands', 'How to handle rejection gracefully in dating', 'Learn how to handle rejection gracefully: what to say when you get a no, how to give one kindly, and how to bounce back with your confidence intact.', 'how to handle rejection gracefully', '[{\"q\":\"How do I deal with being rejected by someone I like?\",\"a\":\"Allow yourself to feel disappointed, then reply with a short, kind message if one is needed. Talk to a friend, take a walk, and avoid sending angry texts. Remember that a no says that you are not a match for them, not that you are not worthy.\"},{\"q\":\"How do I say no politely to someone online?\",\"a\":\"Be clear, short and kind. Thank them for their interest, say you do not feel a match, and wish them well. Avoid vague excuses that give false hope. If they keep pushing after a clear no, stop replying and use the block and report options.\"},{\"q\":\"Should I ask why I was rejected?\",\"a\":\"It is better not to. Most people cannot or will not explain, and pressing for reasons puts them in an uncomfortable position. Accept the answer, thank them for being honest, and use the energy to look after yourself and meet new people.\"},{\"q\":\"Is ghosting ever okay?\",\"a\":\"Ghosting is unkind in most cases, because the other person is left guessing. The exception is when you feel unsafe or when someone is abusive or ignores a clear no. Then you have every right to stop replying and to block them.\"},{\"q\":\"How long does it take to get over rejection?\",\"a\":\"It depends on the person and how much hope you invested. Be patient with yourself. Keep up routines like sleep, exercise and time with friends, and avoid checking their profile again and again. The sting usually fades faster when you stay busy.\"}]', NULL, NULL, 0, 'published', 1, '2026-09-27 09:12:00', '2026-10-16 08:12:11', '2026-10-16 21:16:41'),
(16, 5, 1, 'How to know if you are ready to date again after a loss', 'how-to-know-if-you-are-ready-to-date-again', 'A gentle guide to the signs you may be ready to date again after a breakup or loss, plus how to take the first step slowly and kindly.', 'ready to date again, after a breakup, healing, moving on, relationships, dating advice, Kenya', '<p>After a breakup, a divorce or the loss of a partner, the question \"am I ready to date again?\" can feel heavy. Friends may nudge you to get out there, family may worry, and you may not know what you want. The truth is that there is no schedule you have to follow. Only you can tell when you feel ready to date again, and it is perfectly fine to take as long as you need.</p><p>This guide offers gentle signs to look for, honest questions to ask yourself, and ways to take a first step slowly if you decide you want to. It is written with care, because endings and losses are painful, and healing is not a straight line.</p><h2>There is no right timeline</h2><p>You may have heard rules like \"wait six months\" or \"half the length of the relationship\". These make for neat advice, but they do not describe real people. Some people feel open to company within weeks, especially after a relationship that had already ended in their hearts. Others need years, especially after losing someone they loved deeply.</p><p>Instead of counting months, notice how you feel. Readiness is less about time passing and more about your own heart, mind and daily life settling enough to make space for someone new. Be wary of anyone, including yourself, who insists that you should be over it by now.</p><h2>Signs you may be ready to date again</h2><p>None of these need to be perfect, and you do not need to tick every one. Together they can give you a sense of where you are:</p><ul>\r\n<li>You can think about the past without being flooded by anger, panic or tears most of the time.</li>\r\n<li>You are interested in meeting new people because you want company, not because you need rescuing.</li>\r\n<li>You enjoy your own life: friends, work, hobbies, faith, family and quiet evenings.</li>\r\n<li>You are not hoping that a new person will make your ex or your loss feel less real.</li>\r\n<li>You can speak about the past without constant blame, and you have some sense of what you learned.</li>\r\n<li>The idea of a coffee or a conversation feels more curious than frightening.</li>\r\n</ul><p>If most of these feel true, you may be ready to try. If very few do, that is also useful to know, and it simply means your heart is asking for more time.</p><h2>Honest questions to ask yourself</h2><p>Grab a notebook or find a quiet moment, and answer these as honestly as you can. Nobody else will read your answers.</p><ol>\r\n<li>Why do I want to date right now? Is it company, loneliness, pressure from others or boredom?</li>\r\n<li>Do I still check on my ex, or hope they will come back?</li>\r\n<li>If a new person did not reply, would I feel disappointed or completely crushed?</li>\r\n<li>What do I want from the next relationship, and what did I need that I did not get before?</li>\r\n<li>Am I able to be patient with someone new, or am I comparing everyone to the past?</li>\r\n</ol><p>There are no wrong answers. If some of your answers worry you, be gentle with yourself. They simply show you where more healing might help before you begin.</p><h2>When you are grieving a loss</h2><p>If you have lost a husband, wife or partner to death, the path looks different. Grief does not follow a rulebook, and wanting companionship again does not betray the person you loved. Many people feel guilty or fear what family, church or community will think. These feelings are common and they deserve patience.</p><p>It can help to talk to someone who understands. A trained counsellor, a faith leader you trust or a close friend can listen without judgement and help you sort through the mix of emotions. If your sadness feels too heavy to carry on your own, please reach out to a professional or someone close to you. You do not have to work through it alone.</p><h2>Signs you may need more time</h2><p>Needing more time is not failure. Here are signs that a pause might be kind to yourself:</p><ul>\r\n<li>You are looking for someone to fill a gap or to make an ex jealous.</li>\r\n<li>Thinking about the past still leaves you in tears or anger every day.</li>\r\n<li>You are not sleeping, eating or functioning well, and daily life feels very hard.</li>\r\n<li>You compare every new person to the one you lost.</li>\r\n<li>You feel pushed by friends or family, not drawn by your own wish.</li>\r\n</ul><p>If several of these ring true, consider speaking with a counsellor or someone you trust. Spending time on friends, rest, movement and things you love is never wasted. It makes you stronger and kinder to yourself, which is the best foundation for meeting someone else.</p><h2>Dealing with comparison and old feelings</h2><p>When you start meeting new people, old feelings may come back at odd moments. A smell, a song or a place can remind you of the past and bring a wave of sadness. This does not mean you are not ready. It usually means you are human. What matters is whether you can feel it, let it pass and still carry on.</p><p>Comparison is common too. You may catch yourself thinking, \"She does not laugh like she did\" or \"He does not cook like him.\" Try to notice it gently without judging yourself. Everyone is different, and a new person will never replace the old one. If you want to enjoy a fresh connection, give it room to be its own thing. Writing down your thoughts, talking to a friend or visiting a counsellor can help you sort what belongs to the past and what belongs to today.</p><h2>How to start slowly if you want to</h2><p>You do not have to jump into dating. There are gentler first steps, and you can set the pace at every stage.</p><ul>\r\n<li>Begin with friendship. Many people use a platform first to chat and meet new people without pressure.</li>\r\n<li>Set small goals, like one conversation a week or one coffee this month.</li>\r\n<li>Build your profile in your own time. Our guide on <a href=\"/blog/how-to-write-a-dating-profile-bio\">how to write a dating profile bio</a> can help you say what you want honestly.</li>\r\n<li>Tell a friend what you are doing, so you have someone to talk to after each step.</li>\r\n<li>Allow yourself to stop. If a day feels like too much, close the app and rest.</li>\r\n</ul><p>You can also <a href=\"/browse\">browse people near you</a> without sending a message. Looking is a perfectly acceptable first step, and it lets you test how it feels without any obligation.</p><h2>Being honest and kind with the people you meet</h2><p>When you do start talking to someone, honesty helps. You do not need to share every detail, but it is fair to say that you are taking things slowly after a hard time. A kind person will respect that. Someone who rushes you or makes you feel guilty is showing you something worth noticing, and you can read more in our guide to <a href=\"/blog/green-flags-and-red-flags-in-early-dating\">green flags and red flags in early dating</a>.</p><p>Protect your wellbeing as you go. Meet in public, tell a trusted person where you are and follow our <a href=\"/safety-tips\">safety tips</a>. Be careful with anyone who moves very fast or asks for money. If a first date does not feel right, or if dating becomes more painful than hopeful, you are allowed to stop and try again later.</p><p>Healing and hope can live side by side. Readiness is not a finish line, it is a feeling that grows. Take your time, be gentle with yourself and remember that wanting connection again is a sign of a heart that is still open.</p>', 'blog/51c5c5d324900d69.jpg', 'A person sitting quietly by a window with a cup of tea, looking thoughtful and calm', 'Are you ready to date again? Gentle signs to check', 'Wondering if you are ready to date again after a breakup or loss? Read gentle signs to look for, questions to ask yourself and how to start slowly.', 'ready to date again', '[{\"q\":\"How long should I wait before dating after a breakup?\",\"a\":\"There is no correct number of weeks or months. Some people feel ready sooner and others need much longer. A better question is how you feel: calm when you think about the past, interested in meeting someone new and not hoping to fill a gap.\"},{\"q\":\"Is it normal to feel guilty about dating again after losing a partner?\",\"a\":\"Yes, very normal. Wanting company again does not mean you loved them less or have forgotten them. If the guilt feels heavy, it can help to talk with a counsellor, a faith leader or a trusted friend who can support you through it.\"},{\"q\":\"Can I date to get over my ex?\",\"a\":\"It is better not to use a new person as a way to forget the old one. It can feel good for a moment, but it often brings confusion and can hurt both of you. Spend some time understanding your own feelings first, then meet people with an open heart.\"},{\"q\":\"What if I am not sure whether I am ready?\",\"a\":\"Start small. Chat with friends, join a group, or build a profile and browse without pressure. You can look and take things slowly. If you find that every conversation leaves you upset, that is a sign to pause and give yourself more time.\"},{\"q\":\"Should I tell a new person about my past?\",\"a\":\"Share what you are comfortable with, when you are comfortable. You do not owe anyone your whole story on a first chat. As trust grows, you can say more. It is fair to be honest that you are taking things slowly after a hard time.\"}]', NULL, NULL, 0, 'published', 1, '2026-09-28 09:12:00', '2026-10-16 08:12:11', '2026-10-16 21:12:28');
INSERT INTO `blog_posts` (`id`, `category_id`, `author_id`, `title`, `slug`, `excerpt`, `tags`, `content`, `cover_path`, `cover_alt`, `meta_title`, `meta_description`, `focus_keyword`, `faq`, `og_image`, `canonical_url`, `noindex`, `status`, `views`, `published_at`, `created_at`, `updated_at`) VALUES
(17, 3, 1, 'How to make friends as an adult: simple steps that work', 'how-to-make-friends-as-an-adult', 'Work, family and bills leave little time for friendship. Here is how to make friends as an adult without awkwardness, with real Kenyan examples.', 'adult friendships, making friends, friendship, community, social life, Kenya', '<p>Nobody tells you that friendship gets harder after school. Once you start working, paying rent and maybe raising children, the easy daily contact of campus disappears. If you have been wondering how to make friends as an adult, you are in good company, and the answer is simpler than it feels. It takes a bit of intention, a bit of courage and a lot of small repeated moments.</p><p>This guide gives you practical steps that fit a busy Kenyan life, from the matatu stage to the office, the estate WhatsApp group and the weekend nyama choma. No tricks, only habits that work.</p><h2>Why adult friendships feel harder</h2><p>At school, you saw the same faces every day, with free time and shared problems. As an adult, your schedule is crowded, your friends are spread across towns, and many people are tired by evening. Friendships do not form on their own anymore. Someone has to send the first message, suggest the first meal and remember to follow up.</p><p>The encouraging part is that almost everyone feels this. Many of the people you see at work or at church would love an extra friend and are simply waiting for someone else to start. That someone can be you.</p><h2>How to make friends as an adult: start with who you already know</h2><p>Before searching for strangers, look at the people already around you. Colleagues, neighbours, old schoolmates, cousins, friends of friends and the parents at your child\'s school are all warm leads. A friendship built on a small existing connection is much easier than starting from zero.</p><ul>\r\n<li>Message an old classmate you liked and suggest catching up over tea.</li>\r\n<li>Invite a colleague to lunch instead of eating at your desk.</li>\r\n<li>Ask a friend to bring a friend along to your next plan.</li>\r\n<li>Greet your neighbours properly and offer a small kindness, like sharing something from the market.</li>\r\n</ul><p>This costs nothing and often gives quick results. You only need one or two people to start, because they will introduce you to others.</p><h2>Choose places where you see the same people again</h2><p>A single event rarely creates a friendship, but a weekly activity can. Look for things that meet on a regular schedule so you see the same faces over several weeks. Repetition makes people comfortable.</p><ul>\r\n<li>Sports and fitness: a running club, a Saturday football game, a gym class or a swimming group.</li>\r\n<li>Faith and community: a church or mosque fellowship, a youth or women\'s group, a chama or welfare group.</li>\r\n<li>Skills and hobbies: a book club, a photography walk, a cooking class, a choir or a language group.</li>\r\n<li>Giving back: clean-up days, mentoring students, tree planting or community projects.</li>\r\n</ul><p>Pick one and go consistently for a month before judging it. The first visit is always the most awkward. By the third or fourth, people will start saving you a seat.</p><h2>Make the first move, kindly and simply</h2><p>Most adults are waiting for the other person to speak first. You can break that pattern with one easy sentence. Compliment something real, ask a simple question, or comment on what you are both doing.</p><p>Some lines you can borrow: \"Have you been coming here long?\", \"I am new to this group, any tips?\" or \"That is a great bag, where did you get it?\" Keep your tone friendly and your body language open. If the chat goes well, take it one step further, with something like \"I usually get tea after this, would you like to join?\"</p><p>If you want ideas for chatting with people online as well as face to face, see our guide to <a href=\"/blog/how-to-start-a-conversation-online\">starting a conversation online</a> and the <a href=\"/blog/five-first-message-openers\">five first message openers</a>. The same principles of being specific, warm and curious work everywhere.</p><h2>Invite people to do something specific</h2><p>Vague plans like \"let us meet sometime\" almost never happen. A specific, easy invitation is far more likely to get a yes. Name the activity, the place and the time, and keep the stakes low.</p><p>For example: \"I am walking at Uhuru Gardens on Saturday morning, would you like to join?\" or \"A few of us are watching the game at a place near town on Sunday, come along.\" If they cannot make it, they will often suggest another time. If they do not, invite someone else and try again later. You are not being rejected, only matched with the right schedule.</p><h2>Be the person who follows up</h2><p>Most potential friendships fade because nobody follows up. After you have had a good chat, send a short message the same day or the next: \"Nice meeting you today, I enjoyed our talk about football.\" It tells the other person you were glad to meet them, and it opens the door for the next plan.</p><p>Keep going gently. A voice note, a funny picture, or a question about something they mentioned last time keeps the connection alive. Remember small details such as a job interview, an exam or a sick relative, and ask how it went. This is how acquaintances become friends.</p><h2>Give friendship time and room</h2><p>Friendship deepens through time spent together, small favours and honest conversation. You cannot rush it. Be reliable: arrive on time, keep your promises, and let people know when plans change. Be generous with your attention, put your phone away when you are with someone, and let them finish their story.</p><p>Share a little of yourself, too. People feel closer when they see the real you, including your worries and your humour. At the same time, take your time with very personal details, and trust your instincts about who has earned that closeness.</p><h2>Make time, even when life is busy</h2><p>Busy people often say they have no time for friends, but friendship does not need much, only consistency. Pair it with something you already do. Walk with a friend instead of walking alone, run errands together, or catch up by voice note while you cook. A thirty minute tea once a week can keep a friendship alive better than a big outing twice a year.</p><p>Protect one slot in your week for people, the way you protect a meeting at work. It could be Saturday morning, Wednesday lunch or Sunday after church. When the slot is already in your diary, it becomes much easier to say yes, and your friends learn that you are someone who shows up.</p><h2>Meeting people online, sensibly</h2><p>Apps and websites are now a normal way to meet people, including for friendship. If you live in a place where you do not know many people, they can help you find others near you who want company too. You can <a href=\"/browse\">browse profiles</a> on ConnectHub, read what people share about their interests and start a chat. A clear profile helps others know what you are looking for, so see our tips on <a href=\"/blog/how-to-write-a-dating-profile-bio\">writing a bio that sounds like you</a>.</p><p>Take the same care you would anywhere. Do not send money, do not share your home address early, and meet in a busy public place the first time. Read our <a href=\"/safety-tips\">safety tips</a> and the guide to <a href=\"/blog/meet-someone-safely\">meeting someone safely</a> before you go.</p><h2>When it does not click, move on kindly</h2><p>Not every person will become a friend, and that is fine. Some people will be busy, some will not match your energy, and some will drift away. Do not take it as proof that something is wrong with you. Keep your circle of acquaintances wide, and let friendships deepen with the people who respond warmly.</p><p>If you are feeling low or isolated and it is lasting, talk to someone you trust or a counsellor. You can also read our article on <a href=\"/blog/making-friends-in-a-new-town\">making friends in a new town</a> if you have recently moved. Start with one message to one person this week, and let that small step lead to the next.</p>', 'blog/15c5e598f554b254.jpg', 'Three adult friends sharing a meal and laughing at a roadside cafe', 'How to make friends as an adult: simple, real steps', 'Wondering how to make friends as an adult? Practical steps for busy people in Kenya: where to meet people, what to say and how to keep friendships going.', 'how to make friends as an adult', '[{\"q\":\"Why is it so hard to make friends as an adult?\",\"a\":\"School and campus put you in the same place with the same people every day. Adult life spreads people across jobs, families and estates, so you have to create the contact yourself. It is not you. The effort is simply more deliberate than it used to be.\"},{\"q\":\"Where can adults meet new friends in Kenya?\",\"a\":\"Good places include sports and fitness groups, church or mosque fellowships, volunteer projects, hobby clubs, professional networks, alumni groups, neighbourhood associations and local events. Choose something that meets regularly so you see the same people more than once.\"},{\"q\":\"How do I turn a casual acquaintance into a friend?\",\"a\":\"Suggest something small and specific, such as a coffee, a walk or watching a match together. Follow up after a good chat, remember details about their life, and keep your promises. Friendship grows through steady, friendly contact over weeks and months.\"},{\"q\":\"Is it weird to ask someone to be my friend?\",\"a\":\"Saying it outright can feel odd, but inviting someone to do something is completely normal. Ask them to join you for tea or a hike instead. The invitation says what you mean without putting pressure on either of you.\"},{\"q\":\"Can I make real friends online?\",\"a\":\"Yes, many people do. Use a trusted platform, take time to chat, and move to a call before meeting. Meet in a busy public place the first time and tell someone where you are going. Take it slowly and trust your instincts.\"}]', NULL, NULL, 0, 'published', 1, '2026-09-29 09:12:00', '2026-10-16 08:12:11', '2026-10-16 21:10:37'),
(18, 2, 1, 'How to spot fake profiles and catfishing online', 'how-to-spot-fake-profiles-and-catfishing', 'Learn the common signs of fake profiles and catfishing, from too-perfect photos to endless excuses, plus simple checks you can do before you trust anyone.', 'fake profiles, catfishing, online dating safety, verification, scams, Kenya', '<p>Most people online are honest, but not everyone is. A fake profile can waste your time, hurt your feelings or, worse, be used to take your money. Knowing how to spot fake profiles and catfishing is one of the most useful skills you can have, and the good news is that the warning signs are quite consistent. This guide shows you what to look for, what to check, and what to do next.</p><p>Spotting a fake is rarely about one big clue. It is about small things that do not add up. Learn the patterns below and you will notice them quickly.</p><h2>What catfishing actually is</h2><p>Catfishing means pretending to be someone you are not in order to build a relationship online. The person behind the profile might use another person\'s photos, a false name, a made-up job or a story of living somewhere they do not. Some do it out of loneliness. Others do it deliberately to deceive, to ask for money, or to collect private details.</p><p>You do not need to judge why someone is doing it. A profile that is built on lies is not a safe place to put your trust, whatever the reason. If a fake profile is also asking for money, read our guide to <a href=\"/blog/romance-scams-in-kenya-warning-signs\">romance scams in Kenya</a> as well.</p><h2>How to spot fake profiles: the photo clues</h2><p>Photos are the first thing most of us look at, and the first place a fake often shows itself.</p><ul>\r\n<li><strong>Too perfect.</strong> Every picture looks like a magazine shoot or professional modelling photo, with no ordinary snapshots.</li>\r\n<li><strong>Only one or two photos.</strong> Real people usually have a mix of photos from different places and times.</li>\r\n<li><strong>No group or casual pictures.</strong> Genuine profiles often include friends, events and everyday moments.</li>\r\n<li><strong>Different looks in each photo.</strong> The person seems to change age, face shape or style between pictures.</li>\r\n<li><strong>Odd details.</strong> Strange hands, blurry backgrounds that do not make sense, or a watermark from another website.</li>\r\n<li><strong>Photos that do not match the story.</strong> They say they live in Eldoret, but every photo is in a snowy city.</li>\r\n</ul><p>A reverse image search can help. You save one of their photos and search for it using a search engine\'s image tool. If the picture appears on other sites under other names, you have your answer.</p><h2>Bio and profile warning signs</h2><p>The written parts of a profile can tell you a lot. Read the bio slowly and ask yourself whether it sounds like a real person.</p><p>Fake profiles often have a very short bio, or one that is written to appeal to everyone, full of phrases like \"God-fearing, loving, honest, caring man looking for a serious relationship.\" They may list a high-status job that is hard to check, such as an engineer on an offshore rig, a doctor with an international organisation or a soldier on a peace mission. These professions are not impossible, but they are popular choices for people telling stories.</p><p>Also look at consistency. Does the age match the photos? Does the location match what they say in chat? Does the job they mention fit the way they write and the hours they message? If the details drift from one day to the next, take notice.</p><h2>Behaviour that gives a fake away</h2><p>Often it is how someone acts that tells you the truth, more than what the profile says.</p><ol>\r\n<li><strong>They move too fast.</strong> They say \"I love you\" or \"you are my soulmate\" within days, before you have even spoken properly.</li>\r\n<li><strong>They avoid video calls.</strong> There is always a reason: a broken camera, poor network, a work restriction.</li>\r\n<li><strong>They want to leave the platform quickly.</strong> They push you to move to a private chat before they have earned your trust.</li>\r\n<li><strong>Their answers are vague.</strong> Simple questions about their day or their town get general replies or sudden changes of subject.</li>\r\n<li><strong>They can never meet.</strong> Plans keep falling through at the last minute, always for a dramatic reason.</li>\r\n<li><strong>They ask for money or favours.</strong> This can be airtime, M-Pesa, a gift card, help with a fee or an urgent loan.</li>\r\n</ol><p>Any one of these can happen with an honest person. A shy person may avoid cameras. A busy person may cancel. But when several appear together, treat it as a red flag, and do not feel rude for asking questions.</p><h2>Simple checks you can do yourself</h2><p>You do not need special tools. Here are practical checks that take a few minutes.</p><ul>\r\n<li><strong>Ask for a short video call.</strong> It is the strongest check there is. A real person can usually manage five minutes.</li>\r\n<li><strong>Look for a verified badge.</strong> On ConnectHub, a verified badge means the person has completed an extra check. It is a useful sign, but still use your own judgement.</li>\r\n<li><strong>Try a reverse image search.</strong> Use two or three of their photos.</li>\r\n<li><strong>Check their social profiles.</strong> A real person usually has some history, such as old posts, tagged photos and friends who comment. A brand new account with few followers is worth a question.</li>\r\n<li><strong>Ask small, specific questions.</strong> Where is the best mandazi near your place? What is the traffic like on your road today? Real answers come easily.</li>\r\n<li><strong>Compare stories over time.</strong> Write down key facts, such as their job and town, and see whether they stay the same.</li>\r\n</ul><p>We have a longer walk-through in <a href=\"/blog/how-to-verify-someone-is-real-before-meeting\">how to verify someone is real before meeting</a>, and a short read on <a href=\"/blog/why-profile-verification-matters\">why profile verification matters</a>.</p><h2>What to do if you suspect a fake</h2><p>If something feels wrong, you do not need proof before you act. Here is a calm way to respond.</p><ul>\r\n<li>Stop sharing personal details such as your address, workplace, ID details or family information.</li>\r\n<li>Never send money, airtime or gift cards, however good the story is.</li>\r\n<li>Take screenshots of the profile and key messages.</li>\r\n<li>Ask politely for a live video call. You might say: \"I would love to see you on video this week. Are you free on Saturday?\"</li>\r\n<li>If they refuse or become angry, block them and report the profile to the platform.</li>\r\n</ul><p>Our guide on <a href=\"/blog/how-to-block-and-report-someone-online\">how to block and report someone online</a> covers the steps in more detail.</p><h2>If you have been fooled, it is not your fault</h2><p>Many sensible, kind people have been caught by fake profiles. These deceptions are built by people who put effort into being convincing, and they work because they use warmth, attention and patience. Feeling embarrassed is natural, but you have done nothing wrong by trusting someone. The person who lied is responsible for the lie.</p><p>If you have shared private details or sent money, stop contact, keep every message and screenshot, and contact your bank or mobile money provider quickly. You can also report to the Directorate of Criminal Investigations (DCI). If you feel in danger at any point, call 999 or 112. Talking to a trusted friend can also help you feel less alone with it.</p><h2>Staying careful and still being open</h2><p>The aim is not to become suspicious of everyone. It is to be curious, ask questions, and let trust grow at the speed that real evidence supports. A genuine person will be happy to chat on video, answer questions and take things at a steady pace.</p><p>You can read more on staying safe on our <a href=\"/safety-tips\">safety tips</a> page, then <a href=\"/browse\">browse profiles near you</a> with confidence, or <a href=\"/register\">sign up free</a> to start meeting real people.</p>', 'blog/ef83f20f0e137738.jpg', 'A person looking carefully at a profile photo on a smartphone', 'How to spot fake profiles and catfishing online', 'Find out how to spot fake profiles and catfishing: the photo, bio and behaviour clues to look for, simple checks, and what to do if you suspect one.', 'how to spot fake profiles', '[{\"q\":\"What is catfishing?\",\"a\":\"Catfishing is when someone creates a false identity online, usually with another person\'s photos and a made-up story, to deceive others. The reasons vary. Some people want attention or company, while others want money or personal information. Either way, the deception is what makes it harmful.\"},{\"q\":\"How can I tell if a profile is fake?\",\"a\":\"Look for photos that look like a model or stock image, a bio that is very short or oddly perfect, a story that keeps changing, refusal to video call, and early talk of love or money. No single sign proves a profile is fake, but several together are a strong warning.\"},{\"q\":\"Can I check whether someone is using stolen photos?\",\"a\":\"Yes. A reverse image search lets you upload a photo and see where else it appears online. If the same pictures show up under different names or on unrelated accounts, the profile is probably not genuine. Do this before you share personal details.\"},{\"q\":\"Why do people create fake profiles?\",\"a\":\"Reasons include trying to steal money, collecting personal details, harassing others, or simply pretending to be someone more impressive. Whatever the reason, you do not have to work out why. You only need to protect yourself and report the profile.\"},{\"q\":\"What should I do if I think I am talking to a catfish?\",\"a\":\"Stop sharing personal details, never send money, and ask for a live video call. If they refuse or make excuses, step back. Block the profile and report it to the platform so it can be reviewed. Keep screenshots of the chat in case you need them.\"}]', NULL, NULL, 0, 'published', 1, '2026-09-30 09:12:00', '2026-10-16 08:12:11', '2026-10-16 21:08:04'),
(19, 1, 1, 'How to start a conversation online and keep it going', 'how-to-start-a-conversation-online', 'Hello is the easy part. Learn what to ask, how to follow the thread, and how to avoid dry replies so your online chats feel natural and fun.', 'conversation, online chat, dating tips, messaging, friendship, Kenya', '<p>Sending the first message takes courage, but keeping the chat alive after \"hello\" is where most people struggle. If you have ever typed something, deleted it, and typed it again, this guide on how to start a conversation online is for you. You will find simple questions that work, examples you can copy, and ways to avoid the dry, one-word replies that kill a good chat.</p><p>It does not matter whether you are looking for a partner, a date or a new friend in Nairobi, Mombasa, Kisumu or anywhere else. The same small habits make conversations easier for everyone.</p><h2>Why chats die after hello</h2><p>Most online chats do not end because two people dislike each other. They end because nobody gave the other person anything to work with. \"Hi. How are you?\" gets \"Fine, you?\" and then silence. Neither person did anything wrong, but the conversation had no direction.</p><p>Chats also fade when one person asks all the questions and the other only answers, or when messages are so long and heavy that replying feels like homework. The fix is simple: make every message easy to answer, and make it a little personal. If you want ideas for the very first line, our guide to <a href=\"/blog/five-first-message-openers\">five first message openers</a> is a good place to begin.</p><h2>How to start a conversation online without sounding scripted</h2><p>The best starting point is always the other person\'s profile. Read their bio, look at their photos, and notice their interests. Then choose one detail and ask about it in your own words.</p><ul>\r\n<li>\"I saw you love matatu rides with good music. Which song would you pick for a long trip to Kisumu?\"</li>\r\n<li>\"Your photo at Fort Jesus looks great. Is Mombasa home, or were you visiting?\"</li>\r\n<li>\"You say you cook on Sundays. What is your best dish?\"</li>\r\n</ul><p>Each of these shows you paid attention, and each asks about something the person probably enjoys discussing. Keep your opener to two or three lines. You are opening a door, not writing a letter.</p><p>If their profile is nearly empty, do not fall back on \"hey\". Try a light either-or question instead, such as \"Sunday plan: long lunch or long nap?\" It costs them five seconds to answer and gives you something to follow.</p><h2>Ask open questions that people enjoy answering</h2><p>A closed question can be answered with one word. An open question invites a short story. Compare \"Do you like music?\" with \"What is the one song you can listen to on repeat without getting tired?\" The second one gets a real answer.</p><p>Here are open questions that work well in the first few days:</p><ol>\r\n<li>\"What was the best part of your week so far?\"</li>\r\n<li>\"If you could take a day off tomorrow, what would you do with it?\"</li>\r\n<li>\"What is a small thing that always makes your day better?\"</li>\r\n<li>\"What is your favourite place to eat when you want something simple and good?\"</li>\r\n<li>\"What are you looking forward to this month?\"</li>\r\n</ol><p>Avoid interview-style questions about salary, family problems, or past relationships. They feel heavy this early and can make a person want to pull back. Stay with things that are easy and enjoyable to talk about, and let deeper topics arrive naturally.</p><h2>Follow the thread instead of jumping topics</h2><p>The easiest way to keep talking is to use their last answer as your next question. If they say they went hiking at Ngong Hills, do not jump to \"What do you do for work?\". Ask which part they liked, who they went with, or whether they are planning another trip.</p><p>This is called following the thread, and it makes people feel heard. Each answer holds three or four small hooks. Pick the one that sounds most interesting and pull on it. For example:</p><p>They write: \"I went to the coast last weekend, I needed a break.\"<br>You write: \"That sounds well earned. Did you manage to do nothing at all, or did you end up exploring?\"</p><p>A simple reaction before your question also helps. \"That sounds fun\", \"I love that\" or \"Ha, same\" shows you are listening before you move on.</p><h2>Share a little so it feels like a two-way chat</h2><p>If you only ask questions, the other person may feel like they are in an interview. Balance each question with a small piece of your own story. You do not need to be dramatic. A short detail is enough.</p><p>For example: \"I tried to make mandazi this morning and they came out like small rocks. Have you ever had a cooking disaster?\" This tells them something real about you, adds a little humour, and still ends with an easy question.</p><p>A good rule is to answer their question properly, add one detail about yourself, and then ask something back. If you do this, the chat will rarely feel one-sided. It is also a gentle way to show your personality, which matters far more than being clever.</p><h2>How to avoid dry replies</h2><p>Dry replies are the single word answers like \"ok\", \"fine\" or \"nice\". Sometimes you cause them without noticing, and sometimes the other person is simply busy. Here is how to make good replies more likely:</p><ul>\r\n<li>Ask about a specific thing, not a general one. \"How was the trip?\" is better than \"How are you?\".</li>\r\n<li>Give a choice. \"Beach or bush getaway?\" is quicker to answer than \"What do you like to do?\".</li>\r\n<li>Do not stack several questions in one message. Pick the best one.</li>\r\n<li>Avoid yes or no questions unless they lead somewhere.</li>\r\n<li>Do not reply with only \"lol\" or \"nice\" yourself. Add a line so the chat has fuel.</li>\r\n</ul><p>If the replies stay very short for days, they may be busy or not very interested. Do not chase. Send one friendly message, and if the energy does not change, put your attention on someone whose replies match yours. Our guide to <a href=\"/blog/texting-rules-early-dating\">texting rules for early dating</a> covers pace and timing in more detail.</p><h2>Reviving a chat that has gone quiet</h2><p>Sometimes a conversation just stops. It happens to everyone, and it is not always a rejection. Life gets busy and replies slip down the inbox. If you want to try again, keep it light and without guilt.</p><ul>\r\n<li>\"Hope your week has been kind to you. Did you ever try that place you mentioned?\"</li>\r\n<li>\"Random thought, I just heard a song that made me think of our chat about road trips.\"</li>\r\n<li>\"Hi, no pressure at all, I was just curious how your weekend went.\"</li>\r\n</ul><p>Send one message, not three. If they answer, wonderful. If they do not, let it go with a clear mind. Never send angry or sulky messages like \"why are you ignoring me?\". They push people away and say more about stress than about interest.</p><h2>Keep it safe from the first message</h2><p>A good conversation builds trust, but trust should grow slowly. In the first days, keep your home address, workplace details, and ID information private. Be cautious if someone asks for money, airtime, M-Pesa, or help with an emergency, however sad the story sounds. Our article on <a href=\"/blog/romance-scams-in-kenya-warning-signs\">romance scam warning signs in Kenya</a> explains the patterns to watch for.</p><p>When you feel comfortable, move to a short voice or video call before meeting. Then choose a busy public place, tell a friend where you are going, and use our <a href=\"/safety-tips\">safety tips</a> as a checklist. Safe habits do not spoil romance. They protect it.</p><h2>Put it into practice today</h2><p>You do not need to be a natural talker. You need to be curious, specific and kind. Start with one detail from someone\'s profile, ask an open question, follow their answer, and share a little about yourself. If it flows, wonderful. If it does not, you have lost nothing and learned something.</p><p>Ready to try it? <a href=\"/browse\">Browse people near you</a>, pick someone whose profile makes you curious, and send a message that you would enjoy answering yourself.</p>', 'blog/15f9e2bb9d262bc2.jpg', 'Two friends laughing while reading a chat on a phone at a cafe table', 'How to start a conversation online (and keep it fun)', 'Learn how to start a conversation online, what to ask after hello, and how to avoid dry replies, with simple examples you can copy today.', 'how to start a conversation online', '[{\"q\":\"How do I start a conversation with someone I like online?\",\"a\":\"Pick one real detail from their profile, such as a place, hobby or photo, and ask a simple question about it. Keep it to two or three lines. A specific, friendly question is much easier to answer than a bare hello.\"},{\"q\":\"What do I say after hello?\",\"a\":\"Ask something open that connects to their interests, like what they enjoyed most this week or how they usually spend a weekend. Then share a small detail about yourself so the chat feels like a two-way exchange and not an interview.\"},{\"q\":\"Why does the other person give one-word replies?\",\"a\":\"Short replies often mean the person is busy, tired, shy, or unsure what to say. They can also mean low interest. Try one easy, specific question. If the answers stay dry for a few days, relax and move on kindly.\"},{\"q\":\"How often should I message someone I just met online?\",\"a\":\"There is no perfect number. Match their pace and avoid sending many messages in a row. One good message and a patient wait for the reply works better than constant check-ins. A steady, relaxed rhythm feels comfortable to most people.\"},{\"q\":\"When should I move the chat to a call or WhatsApp?\",\"a\":\"When the conversation flows easily for several days and you both seem comfortable, suggest a short voice or video call. Do not push for personal contacts in the first messages, and keep your address and workplace private until you trust the person.\"}]', NULL, NULL, 0, 'published', 1, '2026-10-01 09:12:00', '2026-10-16 08:12:11', '2026-10-16 21:06:05'),
(20, 2, 1, 'How to verify someone is real before meeting them', 'how-to-verify-someone-is-real-before-meeting', 'A simple set of checks you can do from your phone: video call, reverse image search, consistency, verified badges, social profiles and easy questions.', 'verify, catfish, video call, reverse image search, verified badge, online safety, Kenya', '<p>You have been chatting for a week, the conversation is lovely, and now they would like to meet. Before you say yes, it is worth taking a few minutes to verify someone is real before meeting them. This does not mean you are suspicious or unkind. It means you are making sure the person who turns up matches the person you have been talking to.</p><p>The checks below are simple, quick and polite. You can do most of them from your phone, and they work for dates, new friends and any other meeting with someone you know only online.</p><h2>Start with a short video call</h2><p>A video call is the most reliable check you can do, and it takes only a few minutes. It shows you that there is a real person behind the messages, that they look like their photos, and that their voice and manner fit the way they write. It also tells you whether you enjoy talking face to face, which is useful to know before you travel across Nairobi or Mombasa.</p><p>Make it easy and friendly. You could say: \"I am enjoying our chats. Shall we do a ten minute video call on Thursday evening before we meet?\" Real people usually agree, even if they feel a little shy. Notice how they behave on the call. Are they relaxed? Do they answer easily when you ask about their day? Do they match their photos?</p><h2>Excuses and what they tell you</h2><p>Almost everyone has had a bad network day, so one excuse is not alarming. What matters is the pattern. A person who has a different reason every time, such as a broken camera, no bundles, a poor signal or strict rules at work, may be hiding something.</p><p>You can respond kindly and firmly: \"No problem, we can try again this weekend. I only meet people after a quick video call, it is just my safety rule.\" If they get angry, offer a voice call but never a video call, or push you to meet anyway, you have learned something important. Our guide on <a href=\"/blog/how-to-spot-fake-profiles-and-catfishing\">how to spot fake profiles and catfishing</a> explains the other signs that go with this one.</p><h2>Use a reverse image search</h2><p>If you have doubts about someone\'s photos, a reverse image search can help. In general terms, you save or screenshot one of their pictures and upload it to the image search tool of a search engine. The tool then shows you where the same or similar images appear online.</p><ul>\r\n<li>If the pictures appear under a different name, the profile is probably borrowed.</li>\r\n<li>If they come from a model, an influencer or a stock photo page, it is not a real personal profile.</li>\r\n<li>If nothing appears, that is neutral. It does not prove they are real, but it is not a warning.</li>\r\n</ul><p>Try two or three different photos, not only the first. Remember that this check can only catch stolen images. Someone could still use their own photos and lie about other things, which is why you should combine this with the other steps.</p><h2>How to verify someone is real before meeting: consistency checks</h2><p>Honest people tell a steady story. It helps to keep a few quick notes about what they have told you, such as their job, their town, their age and their family, and see whether the details stay the same over days and weeks.</p><p>Look for these signs of inconsistency:</p><ul>\r\n<li>Their job or city changes between conversations.</li>\r\n<li>Their age or birthday does not match.</li>\r\n<li>They say they live in Nairobi but their messages arrive at odd hours and they cannot describe local places.</li>\r\n<li>Their language and spelling suddenly change from one chat to the next, as if a different person is typing.</li>\r\n<li>Stories about the past have gaps that never get filled in.</li>\r\n</ul><p>People do forget small details, so do not treat a single slip as a lie. Look at the overall picture.</p><h2>Verified badges and social profiles</h2><p>A verified badge on a profile means the person has completed an extra check on the platform. On ConnectHub, it is a helpful sign that the person is serious about being genuine, and it makes impersonation harder. It is not a promise of good character, so keep using the other steps. You can read more in our article on <a href=\"/blog/why-profile-verification-matters\">why profile verification matters</a>.</p><p>You can also look at their social media, if they are happy to share it. A real account usually has some history: older posts, photos with friends, comments from people who know them, and tagged pictures over time. A brand new account with a handful of photos and very few friends deserves a question. Be fair about this, though. Some honest people keep their social media private, and you can always ask politely.</p><h2>Ask simple questions that real people answer easily</h2><p>Everyday questions work well because real people answer them without effort, and made-up identities find it harder to keep them up. You do not need to interrogate anyone. Weave them into normal conversation.</p><ul>\r\n<li>\"What is the traffic like on your road at the moment?\"</li>\r\n<li>\"Which is your favourite place for chai or coffee near you?\"</li>\r\n<li>\"What did you have for lunch today?\"</li>\r\n<li>\"Can you send a quick photo of your view right now?\" A simple photo of the sky, a street or a cup of tea is easy to take.</li>\r\n<li>\"Tell me about your friends. What do you all do on weekends?\"</li>\r\n</ul><p>Pay attention to how the answers feel. Real replies are specific, a little messy and often include a bit of humour. Vague, copied or dramatic replies are worth noticing.</p><h2>A quick pre-meeting checklist</h2><p>Before you agree to meet, run through this short list. If you can tick most of it, you can go ahead with more confidence.</p><ol>\r\n<li>You have had at least one video call and they matched their photos.</li>\r\n<li>Their story has stayed the same over several days.</li>\r\n<li>A reverse image search of two or three photos found nothing suspicious.</li>\r\n<li>They answered ordinary questions in a natural, specific way.</li>\r\n<li>They have not asked you for money, airtime or private details.</li>\r\n<li>They respect your boundaries, including when you say you want to meet in a public place.</li>\r\n</ol><p>If two or three of these are missing, do not feel pressure to meet. You can say, \"I would like to wait a little longer before we meet.\" A person who is genuinely interested will understand. If they are not willing to wait, that is a useful answer in itself, and you have lost nothing but a few messages.</p><h2>Be ready to be checked, too</h2><p>Verification goes both ways. A thoughtful person will probably check you as well, so be open to a video call and happy to answer simple questions. You can still protect your privacy while doing so. Our guide to <a href=\"/blog/protect-your-privacy-on-dating-apps\">protecting your privacy on dating apps</a> shows you what to share and what to hold back.</p><p>Once you have checked, you can plan the meeting with confidence. Choose a busy public place, tell a friend where you are going and use your own transport. The full routine is in our checklist for <a href=\"/blog/meet-someone-safely\">meeting someone safely</a>, and our <a href=\"/safety-tips\">safety tips</a> page is a handy quick reference. If anything you find worries you, you can walk away, and you can block and report the profile.</p><p>When you are ready, <a href=\"/browse\">browse verified profiles near you</a> or <a href=\"/register\">create your free profile</a> and start talking to real people.</p>', 'blog/4d2f4605c765f19b.jpg', 'Two people on a video call, smiling at their phone screens', 'How to verify someone is real before meeting', 'Learn how to verify someone is real before meeting: video call, reverse image search, verified badge, social profiles and easy questions to ask.', 'verify someone is real before meeting', '[{\"q\":\"How can I check if someone online is real?\",\"a\":\"Start with a short video call, which is the strongest check. Then look at whether their photos appear elsewhere using a reverse image search, whether their story stays consistent, whether they have a verified badge, and whether their social profiles look genuine and have some history.\"},{\"q\":\"What if they refuse to video call?\",\"a\":\"Ask once or twice, kindly. One excuse can be genuine, but a pattern of excuses is a red flag. You can say you would like to meet only after a quick video call. If they refuse every time, it is wise to step back and not meet them.\"},{\"q\":\"Does a verified badge mean someone is completely safe?\",\"a\":\"No. A verified badge means the person completed an extra check, which is a good sign and makes impersonation harder. It is not a guarantee of character, so keep using your own judgement, meet in public and tell a friend.\"},{\"q\":\"How do I do a reverse image search?\",\"a\":\"Save or screenshot one of their photos, then upload it to a search engine\'s image search tool. It will show where the same or similar images appear online. If the photos belong to someone with a different name, or appear on stock or model pages, the profile is likely fake.\"},{\"q\":\"Is it rude to check someone before meeting?\",\"a\":\"No. Checking is a normal and sensible part of online dating, and genuine people expect it. They are probably checking you too. Keep your checks polite and respectful, and be ready to answer their simple questions as well.\"}]', NULL, NULL, 0, 'published', 2, '2026-10-02 09:12:00', '2026-10-16 08:12:11', '2026-10-16 21:04:22');
INSERT INTO `blog_posts` (`id`, `category_id`, `author_id`, `title`, `slug`, `excerpt`, `tags`, `content`, `cover_path`, `cover_alt`, `meta_title`, `meta_description`, `focus_keyword`, `faq`, `og_image`, `canonical_url`, `noindex`, `status`, `views`, `published_at`, `created_at`, `updated_at`) VALUES
(21, 4, 1, 'How to write a dating profile bio that sounds like you', 'how-to-write-a-dating-profile-bio', 'A simple four-part formula, examples for different personalities and ready-to-copy sample bios, so your profile starts real conversations.', 'dating profile bio, profile tips, online dating, sample bios, about me, Kenya', '<p>Your bio is the few lines people read right after your photos, and it often decides whether they send a message or keep scrolling. If you have been staring at the empty box wondering how to write a dating profile bio that does not sound boring or boastful, take a breath. You do not need to be a writer. You need to be specific, honest and a little warm.</p><p>This guide gives you a simple formula, examples for different personalities, a list of what to include and what to skip, and sample bios you can copy and make your own. It works whether you want a partner, a date or a new friend.</p><h2>Why your bio matters more than you think</h2><p>Photos get attention, but words build interest. Two people can have equally nice pictures, and the one who has written something real will usually get the conversation. A good bio does three things: it tells people who you are, it hints at what you would enjoy doing together, and it gives them an easy reason to say hello.</p><p>Think of it as a short, friendly introduction at a gathering. You would not recite your CV or list everything you dislike. You would say a little about yourself, show some warmth and ask something back. The same approach works on a profile.</p><h2>How to write a dating profile bio in four simple steps</h2><p>If you are not sure where to begin, use this four-part formula. Each part is one sentence, and together they make a bio that feels complete.</p><ol>\r\n<li><strong>The hook.</strong> One line that shows your personality. \"Nairobi born, weekend adventurer, professional taster of street mandazi.\"</li>\r\n<li><strong>The passions.</strong> Two or three specific things you enjoy. \"I love hiking, gospel and soul music, and cooking for friends on Sundays.\"</li>\r\n<li><strong>The goal.</strong> What you are here for, in plain words. \"Looking for something genuine and long term with someone kind.\"</li>\r\n<li><strong>The question.</strong> End with something easy to answer. \"Tell me your best hidden eating spot in town.\"</li>\r\n</ol><p>Write the four lines first, then read them aloud. If it sounds like something you would actually say to a new friend, you have got it right. If it sounds like a poster or a job application, loosen it up.</p><h2>What to include in your bio</h2><p>The best bios are full of small, concrete details. \"I like travelling\" could be anyone. \"I am slowly visiting every county, and Lamu is next\" is only you. Here is what to reach for:</p><ul>\r\n<li>Two or three real interests, named specifically, such as a sport, a cuisine, a genre of music or a hobby.</li>\r\n<li>A hint of your daily life, such as your work, your studies or your side project, without giving away your exact workplace.</li>\r\n<li>Something that shows your sense of humour or your values, whichever feels more natural.</li>\r\n<li>What you are looking for, in one kind sentence.</li>\r\n<li>A conversation hook: a question, a recommendation request or a playful challenge.</li>\r\n</ul><p>Specific details do the heavy lifting. They give people something to ask about, and they make your first messages more interesting for you to read too.</p><h2>What to skip in your bio</h2><p>A few habits quietly push good people away. Try to leave these out:</p><ul>\r\n<li>Lists of what you refuse to put up with. It reads as bitter, even if you have been hurt before.</li>\r\n<li>Phrases like \"no drama\" or \"not here to waste time\", which sound defensive.</li>\r\n<li>Clichés such as \"I love to laugh\" or \"I am simple and easy-going\". Show it instead.</li>\r\n<li>Your phone number, social media handles or exact address. Keep personal contact details private until you trust someone. Our guide to <a href=\"/blog/protect-your-privacy-on-dating-apps\">protecting your privacy on dating apps</a> explains why.</li>\r\n<li>Negative comments about your ex or about the opposite gender.</li>\r\n</ul><p>You can state your standards without a list of warnings. \"I value honesty and good communication\" says the same thing in a way that invites people in rather than fencing them out.</p><h2>Bio examples for different personalities</h2><p>You do not have to sound bubbly if you are not bubbly. Here is how the formula bends to suit different people.</p><p><strong>The quiet one.</strong> \"Introvert with a loud laugh once I know you. Happiest with a good book, a pot of chai and one or two close friends. Looking for someone calm and genuine. What are you reading at the moment?\"</p><p><strong>The funny one.</strong> \"I can cook one very good pilau and I will mention it often. Football on weekends, terrible at dancing but I try anyway. Here for good company and good banter. Convince me that your team is better than mine.\"</p><p><strong>The outdoorsy one.</strong> \"Hiking Ngong on Saturdays, chasing sunsets at the coast when I can. I am happiest outside and a little muddy. Looking for a partner who likes adventure and slow mornings too. Where is the best trail you have walked?\"</p><p><strong>The family-minded one.</strong> \"Close to my family and proud of it. Sunday lunch is non-negotiable in my house. I am looking for a serious relationship with someone who values kindness and faith. What is the dish that tastes like home to you?\"</p><p><strong>The career-focused one.</strong> \"Busy building my business, but I always make time for good people. I enjoy early-morning runs, podcasts and learning new things. I want a partner who has their own goals and supports mine. What are you working towards this year?\"</p><p>Notice that none of these use big words or brag about money or status. They are warm, particular and end with a question.</p><h2>Ready-to-copy sample bios</h2><p>Use these as a starting point. Change the details so they fit your life, because a bio that is copied word for word will not sound like you.</p><ul>\r\n<li>\"Teacher by day, amateur chef by night. I love matatu playlists, long walks and any film with a good soundtrack. Looking for someone honest and kind to share Sunday afternoons with. What is your comfort food?\"</li>\r\n<li>\"New in town and still learning the shortcuts. I enjoy live music, board games and trying every chips-and-chicken spot I can find. Would love to meet people for friendship first and see where it goes. Any recommendations?\"</li>\r\n<li>\"Software developer who still prefers real conversations to screens. I run, I read, I make decent ugali. Here for something genuine with someone who laughs easily. What is the best thing you ate this week?\"</li>\r\n<li>\"Mother of one, lover of gospel music and good tea. My family keeps me grounded and my friends keep me laughing. Looking for a respectful man who knows what he wants. Tell me something that made you smile today.\"</li>\r\n<li>\"Studying part time, working full time and sleeping when I can. I am looking for friends and maybe more, but mostly for people who make me laugh. Who is your favourite comedian?\"</li>\r\n</ul><h2>Make your bio easy to reply to</h2><p>A bio is not only a description, it is an invitation. Before you save it, read it as a stranger would and ask: what would I message this person about? If you cannot find anything, add a detail. A named hobby, a favourite place, a half-finished goal or a playful opinion all work.</p><p>Ending with a question is the simplest trick. It turns your profile from a statement into the start of a chat, and it means the other person does not have to work to think of an opener. If you would like more ideas for what to say once someone replies, see our <a href=\"/blog/five-first-message-openers\">first message openers</a> and the guide to <a href=\"/blog/how-to-start-a-conversation-online\">starting a conversation online</a>.</p><h2>Edit, test and refresh it</h2><p>Do not worry about getting it perfect on the first try. Write a draft, leave it for a day, then reread it with fresh eyes. Cut anything that sounds like a cliché and add one detail you would be proud to talk about. Ask a trusted friend whether it sounds like you.</p><p>Your bio works together with your pictures, so pair it with clear, honest photos. We cover that in our guide to the <a href=\"/blog/best-dating-profile-photos\">best dating profile photos</a>, and you can avoid the common slips in <a href=\"/blog/dating-profile-mistakes-to-avoid\">dating profile mistakes to avoid</a>. When you are ready, <a href=\"/register\">create your free profile</a> or update the one you already have, and see who says hello.</p>', 'blog/2e7c152d5bf1974a.jpg', 'A person writing their profile bio on a phone at a cafe table', 'How to write a dating profile bio (with examples)', 'Learn how to write a dating profile bio that gets replies, with a simple formula, examples for different personalities and sample bios you can copy.', 'how to write a dating profile bio', '[{\"q\":\"How long should a dating profile bio be?\",\"a\":\"Aim for three to six short sentences, roughly fifty to ninety words. That is enough to show your personality and give people something to ask about, without turning into an essay. If someone has to scroll to finish reading, it is probably too long.\"},{\"q\":\"What should I write in my bio if I am shy?\",\"a\":\"Be honest about it and let your interests do the talking. Something like \\\"I am quiet at first but very funny once I am comfortable\\\" works well. Then name two or three things you love, so people have easy questions to start with.\"},{\"q\":\"Should I say what I am looking for in my bio?\",\"a\":\"Yes, in one clear and kind sentence. Saying you want a serious relationship, a friendship or to see where things go saves everyone time. Avoid long lists of demands, because they read as pressure and put off people who might be a good match.\"},{\"q\":\"Is it okay to leave my bio blank?\",\"a\":\"It is better to write something. A blank bio gives people no reason to message you, and it can look like you are not serious. Even two honest lines about what you enjoy will bring more replies than an empty box.\"},{\"q\":\"How often should I update my bio?\",\"a\":\"Check it every month or two, and change it when something in your life changes, such as a new hobby, a new job or a different goal. A fresh bio also gives you a reason to look at your photos again and replace the weaker ones.\"}]', NULL, NULL, 0, 'published', 1, '2026-10-03 09:12:00', '2026-10-16 08:12:11', '2026-10-16 21:03:54'),
(22, 5, 1, 'Introducing your partner to family in Kenya: a respectful guide', 'introducing-your-partner-to-family-in-kenya', 'When to introduce someone to your family, how to prepare both sides, and how to handle expectations with respect across different Kenyan communities.', 'meeting the family, introducing partner, family expectations, Kenyan culture, relationships, Kenya', '<p>In Kenya, a relationship is rarely just between two people. Parents, aunties, uncles, siblings and sometimes the whole village have an interest in who you are with. That is why introducing your partner to family is a big step, and one worth planning with care. Done well, it builds trust, honours the people who raised you and tells your partner they matter to you.</p><p>This guide offers general advice for timing, preparation, respect and handling the unexpected. Kenya has many communities, each with its own customs, so treat everything here as a starting point and ask your own family about how they like to do things.</p><h2>What an introduction really means</h2><p>Introducing someone to your family says something. For many families it signals that you are serious, and that the person is more than a passing friend. Your relatives may have expectations, questions and hopes the moment someone arrives. Some will be warm and curious, while others will be quiet and watchful.</p><p>Understanding this helps you plan. Before you invite anyone to meet your relatives, ask yourself whether you are ready for the meaning that comes with it. If you are still unsure about where you stand with your partner, have the conversation first. Our guide to the <a href=\"/blog/define-the-relationship-talk\">define the relationship talk</a> can help you get clear.</p><h2>Timing for introducing your partner to family</h2><p>There is no perfect date, but some signs suggest you are ready:</p><ul>\r\n<li>You are both clearly committed, and you have talked about a shared future.</li>\r\n<li>You have met each other\'s close friends and feel comfortable together.</li>\r\n<li>You trust your partner to be respectful and kind to your family.</li>\r\n<li>You are ready to answer your family\'s honest questions about the relationship.</li>\r\n<li>You are not rushing because of pressure, an anniversary or fear of losing the person.</li>\r\n</ul><p>Be careful about doing it too early. Meeting family very quickly can create expectations before you and your partner have decided what you want. At the same time, hiding a serious relationship for years can hurt trust. Aim for a moment when the introduction feels like a natural next step.</p><h2>Prepare both sides before the day</h2><p>Preparation takes the fear out of the meeting. For your partner, share a short briefing:</p><ol>\r\n<li>Who will be there, their names and how they are related to you.</li>\r\n<li>How your family usually greets guests, and anything to know about manners at the table.</li>\r\n<li>Whether a small gift would be welcome, such as fruit, tea or bread.</li>\r\n<li>Topics that may be sensitive, such as family disagreements or past relationships.</li>\r\n<li>What is expected of the visit, such as how long to stay and whether to share a meal.</li>\r\n</ol><p>Prepare your family too. Tell them a little about your partner beforehand, such as their name, what they do and what you like about them. A parent who has already heard a few kind words will usually be warmer than one who is surprised. Ask what they would like to know and be honest about the nature of the relationship.</p><h2>Understanding different family expectations</h2><p>Families across Kenya hold different values about courtship, marriage, religion, age, education and work. Some expect elders to be consulted and greeted formally. Some are more relaxed and modern in their approach. Many are a mix, shaped by faith, upbringing, region and generation. It would be unfair to assume that a family will behave in a certain way because of where they come from.</p><p>The kinder approach is to ask. \"How does your family like to do introductions?\" is a simple question that shows respect. Listen to your partner\'s answers about their own family too. If your families come from different communities, take time to learn about each other\'s customs. Curiosity goes a long way, and it is usually received as a sign of care.</p><h2>What to do on the day</h2><p>A few simple habits make a good impression, whichever family you are visiting:</p><ul>\r\n<li>Arrive on time and dress neatly in a way that respects the home.</li>\r\n<li>Greet the elders first, with warmth, and learn the names beforehand.</li>\r\n<li>Accept offered tea or food graciously, even if only a little.</li>\r\n<li>Be yourself, but be polite. Smile, listen and ask friendly questions about the family.</li>\r\n<li>Avoid arguments, strong opinions on touchy topics and too much affection in front of elders.</li>\r\n<li>Thank everyone as you leave, and send a short message the next day.</li>\r\n</ul><p>Relatives may ask direct questions, such as where you work, where you are from or what your intentions are. Answer honestly and calmly, and do not take curiosity as hostility. If you do not know how to answer a question yet, it is fine to say, \"We are taking it step by step.\"</p><h2>Conversations about marriage and customs</h2><p>In some families, the introduction leads to discussions about marriage and the customs that go with it. Different communities approach these in different ways, and some families do not follow traditional practices at all. Your relatives may mention the idea of bringing elders together, or ask when you plan to take the next step.</p><p>It is best to treat these conversations with respect and patience. Do not make promises before you and your partner have agreed what you want. If something is raised that you are not ready for, thank them for their interest and say that you will talk with your partner and come back to them. Ask elders or relatives you trust to explain how things are usually done in your family, so there are no surprises. Honest communication between the two of you matters most.</p><h2>Supporting your partner through the nerves</h2><p>Meeting a partner\'s relatives can be stressful for anyone. Your partner may worry about saying the wrong thing, being judged or not fitting in. Help by staying close during the visit, including them in the conversation and stepping in gently if someone asks something too personal. A quick squeeze of the hand or a smile across the room goes a long way.</p><p>Afterwards, check in without fishing for compliments. Ask how it felt, what they enjoyed and whether anything worried them. Share what your family said in a kind and honest way. If a relative made a rude comment, do not repeat it word for word, but do not pretend it did not happen either. Tell your partner you noticed, that you value them and that you will handle it. Feeling backed up is what turns a nervous first visit into a good memory.</p><h2>When things do not go smoothly</h2><p>Not every introduction goes well. A relative may seem cold, ask difficult questions or express worries. Try not to react in the moment. Often there is a worry behind the coolness, such as concern about distance, religion, past hurts or simply wanting the best for you.</p><p>After the meeting, talk with your partner in private. Thank them for coming, listen to how they felt and reassure them. Then speak with your family and ask what concerns them. Allow time. Families often warm up with repeated, respectful contact. If disapproval stays strong, speak with someone older and trusted who can help, and remember that you can love and respect your family while also making your own decisions.</p><p>It is also wise to stay safe if you are meeting a partner for the first time. If you met online, read our guide on <a href=\"/blog/how-to-verify-someone-is-real-before-meeting\">how to verify someone is real before meeting</a> before bringing them into your family circle.</p><h2>After the introduction</h2><p>Keep the connection going. Greet your partner\'s family when you speak, remember names and attend gatherings when you can. These small acts build relationships that last. If you are still getting to know each other, our article on <a href=\"/blog/dating-etiquette-in-kenya\">dating etiquette in Kenya</a> may offer helpful context.</p><p>Meeting family is a milestone, not a test you must pass perfectly. Be sincere, be respectful and give everyone time. If you are still looking for someone to share that milestone with, you can <a href=\"/browse\">browse people near you</a> or <a href=\"/register\">create your free profile</a> and take your next step at your own pace.</p>', 'blog/0b783cabb980349c.jpg', 'A family sharing a meal at a table while welcoming a guest', 'Introducing your partner to family in Kenya', 'A respectful guide to introducing your partner to family in Kenya: timing, preparation, family expectations, what to say and how to handle tricky moments.', 'introducing your partner to family', '[{\"q\":\"When is the right time to introduce my partner to my family?\",\"a\":\"When the relationship is serious, you are both committed and you are sure you want your partner to be part of your wider life. There is no fixed timeline. Many people wait until they have talked about the future, so the meeting feels meaningful for everyone.\"},{\"q\":\"Should I prepare my partner before meeting my family?\",\"a\":\"Yes. Share who will be there, how your family usually greets visitors, any customs worth knowing and topics to avoid. A short, friendly briefing helps your partner feel respected and relaxed, and it avoids awkward surprises on the day.\"},{\"q\":\"What if my family does not approve of my partner?\",\"a\":\"Listen first and try to understand the real concern, which may be about worry rather than rejection. Stay calm and respectful, and give people time. Talk to a trusted elder or relative who may help. In the end, the decision is yours, made with care for everyone.\"},{\"q\":\"Do I need to bring a gift when meeting the family?\",\"a\":\"It is a thoughtful gesture in many homes and communities, and need not be expensive. Fruit, tea, bread or a small item for the household shows respect. Ask your partner or a relative what is suitable, since customs differ between families.\"},{\"q\":\"What if my partner and I come from different communities or religions?\",\"a\":\"Be open and patient. Learn about each other\'s customs, speak honestly about what matters to each of you and involve family gradually. Respect works in both directions. Many couples find that curiosity and kindness help families come to understand each other over time.\"}]', NULL, NULL, 0, 'published', 1, '2026-10-04 09:12:00', '2026-10-16 08:12:11', '2026-10-16 21:01:54'),
(23, 5, 1, 'Long distance relationship tips that actually work', 'long-distance-relationship-tips', 'Practical ways to stay close across towns, counties or countries, from communication habits and trust to time zones, visits and handling money wisely.', 'long distance relationship, long distance love, communication, trust, relationships, diaspora, Kenya', '<p>Maybe you met someone from another county, or your partner moved to another city for work. Maybe they live abroad, in the diaspora, and you speak across time zones. Distance is hard, but plenty of couples make it work. With the right habits, it can even make your communication stronger. These long distance relationship tips will help you stay close, build trust and plan for the future.</p><p>Whether you are separated by a few hours on the road from Nairobi to Kisumu or by several time zones, the basics are the same: honest communication, steady effort, realistic expectations and a shared direction.</p><h2>Start with the same goals</h2><p>Before anything else, make sure you are both on the same page. Are you both committed? Do you see this as a temporary stage or an indefinite arrangement? Do you want to live in the same place one day? Many long distance couples struggle not because of the miles, but because they never discussed what they were working towards.</p><p>You do not need a five-year plan on day one. A loose agreement is enough: \"I would like us to be together in the same place eventually, and I want us to talk about that in a few months.\" If you are unsure how to raise it, our guide to the <a href=\"/blog/define-the-relationship-talk\">define the relationship talk</a> gives you scripts.</p><h2>Long distance relationship tips for daily communication</h2><p>Good communication is not about talking all the time. It is about feeling connected. Here are habits that work:</p><ul>\r\n<li>Agree on a rhythm. A short daily message and a longer call a few times a week suits many couples.</li>\r\n<li>Share small things, such as what you ate, a funny moment, a song you liked. Little details make you feel part of each other\'s days.</li>\r\n<li>Use different channels. Voice notes, photos, calls and video chats all feel different, so mix them.</li>\r\n<li>Do not measure love by reply speed. Everyone has busy days, and silence does not always mean something is wrong.</li>\r\n<li>Say when you are tired or not in the mood to talk, rather than going quiet without explanation.</li>\r\n</ul><p>Be careful not to turn every call into a status report. Ask real questions, listen and let some calls be silly or quiet. If you need ideas, our <a href=\"/blog/first-date-conversation-topics\">first date conversation topics</a> work just as well for a video call.</p><h2>Make time zones and schedules work</h2><p>If you live in different countries, time zones can feel like a wall. Work out a few overlapping windows when you are both awake and free, and protect them. It is better to have two reliable calls a week than ten rushed ones.</p><p>Be fair about who sacrifices sleep. If one person always stays up late or wakes up early, resentment can build. Take turns, and agree on days when you are both allowed to skip. When you cannot talk live, voice notes are a lovely way to share your day, because you can listen when it suits you and hear each other\'s voices.</p><h2>Build trust and handle jealousy</h2><p>Distance can make small worries grow, since you cannot see what the other person is doing. The answer is not checking up or demanding passwords. It is honesty and consistency. Do what you say you will do, tell each other about your plans and mention the friends you spend time with.</p><p>When jealousy shows up, talk about it early and kindly. Say \"I felt a bit uneasy when I did not hear from you, can we talk about it?\" rather than accusing. If you notice patterns that worry you, such as stories that change or reasons why you can never video call, read our guide to <a href=\"/blog/green-flags-and-red-flags-in-early-dating\">green flags and red flags in early dating</a>. A real partner is happy to show up on camera.</p><h2>Plan visits and keep something to look forward to</h2><p>Waiting is easier when you have a date in view. Try to keep the next visit planned, even if it is far away. Talk about what you would like to do, from a simple dinner to exploring a place neither of you knows. Our ideas for <a href=\"/blog/first-date-ideas-in-nairobi\">first date ideas in Nairobi</a> and <a href=\"/blog/first-date-ideas-outside-nairobi\">outside Nairobi</a> can spark plans for your time together.</p><p>Visits also tell you things that screens cannot. How do you get along when you share space? How does the other person treat people around them? Do not put too much pressure on each visit to be perfect. Real life includes tired days and small disagreements, and that is useful information.</p><p>If you are meeting for the first time, stay safe. Choose a public place, tell a friend, arrange your own transport and follow the steps in our guide to <a href=\"/blog/meet-someone-safely\">meeting someone safely</a>. If your partner lives overseas, our page on <a href=\"/international\">meeting people from other countries</a> and the guide to <a href=\"/blog/meeting-people-abroad-safely\">meeting people abroad safely</a> are worth reading.</p><h2>Keep the spark alive from afar</h2><p>Routine can creep into any relationship, and distance makes it easy to fall into the same call every night. Add some playfulness. Watch the same film at the same time and chat about it afterwards. Read a book together and discuss a chapter each week. Cook the same recipe on video call and compare the results. Send a surprise voice note in the morning, or a photo of something that made you think of them.</p><p>Write each other a longer message now and then, saying what you appreciate. A few sincere lines can mean more than hours of small talk. If you can afford it, a small parcel or a delivered treat can be a lovely surprise, as long as you have met in person and trust each other. Keep it light and unpressured, because the goal is to feel close, not to prove love through spending.</p><h2>Be careful with money</h2><p>Money can be a source of trouble in long distance relationships, especially across countries. The most important rule is simple: never send money, airtime or gifts to someone you have not met in person, no matter how convincing the story. Stories about hospital bills, customs fees, stuck parcels or sudden emergencies are common tricks.</p><p>Even in a committed relationship, talk openly before helping each other financially. Agree what is fair, keep it transparent and do not feel pressured. If something feels off, speak to a trusted friend or relative. Our guides on <a href=\"/blog/romance-scams-in-kenya-warning-signs\">romance scams in Kenya</a> and <a href=\"/blog/should-you-send-money-to-someone-you-met-online\">whether to send money to someone you met online</a> explain what to watch out for.</p><h2>Look after yourself too</h2><p>A long distance relationship should add to your life, not take it over. Keep up your friendships, hobbies and routines. Go out with friends, join a group, try something new. Having a full life makes you a more relaxed partner, and it makes the hard days easier.</p><p>It is normal to feel lonely sometimes, especially on weekends and holidays. Tell your partner, but also lean on the people near you. Our guide on <a href=\"/blog/feeling-lonely-in-the-city\">feeling lonely in the city</a> has ideas for building a community around you while you wait to be together.</p><h2>Know when to talk about closing the distance</h2><p>Most long distance relationships work best with an eventual plan. This does not need to be a set date, but both of you should know the direction. Who might move? What would you need first, such as a job, savings or family discussions? How will you decide together?</p><p>If the conversation always gets avoided, or if one of you is doing all the planning, that is a sign to speak up gently. A healthy relationship can talk about hard things. If you would like to meet someone who lives closer to you as well, you can <a href=\"/browse\">browse people near you</a> or <a href=\"/register\">create your free profile</a>, and remember that choosing what is right for you is always allowed.</p>', 'blog/d16a6efcda633fc4.jpg', 'A person smiling during a video call on a phone while sitting at a window', 'Long distance relationship tips for staying close', 'Try these long distance relationship tips for communication, trust, time zones, visits and money, whether you live in different towns or countries.', 'long distance relationship tips', '[{\"q\":\"How often should we talk in a long distance relationship?\",\"a\":\"As often as feels good to both of you, without it becoming a chore. Some couples talk a little every day, others prefer a few longer calls each week. Agree on a rhythm together and be flexible when work or life gets busy.\"},{\"q\":\"How do you build trust when you live far apart?\",\"a\":\"Be honest, reliable and open about your days. Do what you say you will, share plans, and talk about worries before they grow. Trust also grows through meeting in person, so plan visits and introduce each other to your friends when you can.\"},{\"q\":\"How often should long distance couples visit each other?\",\"a\":\"It depends on distance, time and money. Many couples find that having the next visit already planned makes the waiting easier. Even if visits are months apart, a date on the calendar gives you both something solid to look forward to.\"},{\"q\":\"Is it safe to send money to a partner I have not met?\",\"a\":\"No. If you have never met in person, do not send money, airtime or gifts, whatever the reason. Requests for money from someone you have not met are a common warning sign of a scam. Meet first, build trust over time and ask a friend for advice.\"},{\"q\":\"When does a long distance relationship need an end date?\",\"a\":\"Eventually, most couples need a plan for closing the distance, even a loose one. You do not need exact dates, but you should both know whether one of you will move, when you might talk about it and what you each want in the long run.\"}]', NULL, NULL, 0, 'published', 2, '2026-10-05 09:12:00', '2026-10-16 08:12:11', '2026-10-16 21:00:25'),
(24, 6, 1, 'Meeting people abroad safely: a guide for Kenyans online', 'meeting-people-abroad-safely', 'Want to connect with someone in another country? Learn how to handle time zones, video calls, visa and ticket requests, scams and culture gaps with confidence.', 'international, meeting people abroad, scams, video calls, long distance, safety, Kenya', '<p>The internet has made the world smaller. You can now chat with someone in London, Toronto, Dubai or Johannesburg from a matatu on the way home. Many Kenyans enjoy this, whether they want a pen pal, a friend with a different view of the world or something more. But meeting people abroad safely takes extra care, because distance makes it harder to check who someone really is. This guide explains how to enjoy those connections and protect yourself.</p><p>It is written for anyone using an international feature, and the advice is simple: go slowly, ask for proof, keep your money in your pocket and trust your instincts.</p><h2>Why people connect across borders</h2><p>There are many good reasons to talk to people in other countries. You may want to learn about another culture, practise a language, make a friend with shared interests or meet a partner who sees life differently. Some Kenyans abroad also want to reconnect with home. These are all normal and healthy reasons.</p><p>ConnectHub has an <a href=\"/international\">international section</a> for this purpose. It lets you find people in other countries who are also looking to connect. As with any online meeting, the platform can give you tools, but your own habits protect you most.</p><h2>The basic rules of meeting people abroad safely</h2><p>Before you start, remember five rules. They are simple, and they will prevent most problems:</p><ol>\r\n<li>Never send money, airtime or gifts to someone you have not met in person.</li>\r\n<li>Never pay for a visa, a flight, a fee or a document for someone you have not met.</li>\r\n<li>Keep your ID, bank, M-Pesa and address details private.</li>\r\n<li>Insist on live video calls before building trust.</li>\r\n<li>Slow down if anyone rushes you, and report anything suspicious.</li>\r\n</ol><p>If you remember nothing else, remember these. They apply whether the person says they live in Europe, America, Asia or a neighbouring African country.</p><h2>Time zones and the rhythm of a long-distance chat</h2><p>When someone lives in a different country, the clock becomes part of your relationship. When it is noon in Nairobi, it may be early morning in New York or late evening in Sydney. Check their local time before messaging or calling, and agree on a regular time that works for you both.</p><p>Be patient with gaps in replies. A person who does not answer at once may be asleep or at work, and it does not mean they are losing interest. At the same time, watch for patterns that do not make sense. Someone who says they are in one country but is only awake at times that match another may not be telling the truth. Consistency is a good sign, and a story that keeps changing is not.</p><h2>Video calls: the best test you have</h2><p>A live video call is the single most useful safety tool. It shows you a real person, in real time, who matches the photos. Ask for one early, before you share much about yourself. It does not need to be long or fancy. A ten-minute call is enough to see a face, hear a voice and notice how the person behaves.</p><p>Be cautious if they:</p><ul>\r\n<li>Always say the camera is broken or the network is poor.</li>\r\n<li>Offer only voice notes or pre-recorded clips.</li>\r\n<li>Look very different from their photos, or the video freezes in a suspicious way.</li>\r\n<li>Get angry or defensive when you ask for a call.</li>\r\n</ul><p>Our guides to <a href=\"/blog/how-to-verify-someone-is-real-before-meeting\">how to verify someone is real before meeting</a> and <a href=\"/blog/how-to-spot-fake-profiles-and-catfishing\">spotting fake profiles and catfishing</a> explain more checks.</p><h2>Visas, tickets and immigration claims</h2><p>This is where many people lose money. A scammer may say, \"I will bring you to my country, but I need you to pay for the visa processing first,\" or \"Send me money for your ticket and I will refund you.\" Others say they have a contact in an embassy or at an airport who can speed things up for a fee. All of these should make you stop at once.</p><p>Here is the truth in plain terms. Visa and immigration decisions are made by the official authorities of each country, not by a private person you met online. A genuine friend or partner will never ask you to pay for their travel, their documents or their fees. If you want to travel anywhere, find out the real process through official government and embassy channels yourself, and do not rely on a stranger\'s promises. If anything about the process is unclear, seek advice from a qualified and licensed professional.</p><p>The same applies the other way round. Be careful if someone asks you to receive money, parcels or documents on their behalf, or to move money through your account. That can pull you into trouble you did not create.</p><h2>Common scams and warning signs</h2><p>Scams that target people looking for connections abroad often follow a pattern. Learn it, and you will spot it early.</p><ul>\r\n<li>The person quickly says they love you, calls you their soulmate or wants exclusivity within days.</li>\r\n<li>They claim a job that keeps them away, such as an oil rig, the military or an overseas contract, so they cannot video call.</li>\r\n<li>They face a sudden emergency, a hospital bill, a customs fee, a frozen account or a stolen wallet, and ask for money.</li>\r\n<li>They want to move the chat to a private app right away.</li>\r\n<li>They send a gift or a parcel and then ask you to pay a clearing fee.</li>\r\n<li>Their story has gaps, their photos look too perfect or their English does not match where they claim to be from.</li>\r\n</ul><p>Our detailed guide to <a href=\"/blog/romance-scams-in-kenya-warning-signs\">romance scams in Kenya and their warning signs</a> covers these in depth, and <a href=\"/blog/should-you-send-money-to-someone-you-met-online\">should you send money to someone you met online</a> explains why the answer is always no. If someone makes you uncomfortable, learn how to <a href=\"/blog/how-to-block-and-report-someone-online\">block and report someone online</a>.</p><h2>Cultural differences and expectations</h2><p>Even with a genuine person, cultures differ. Directness, humour, family involvement, ideas about money, religion and the pace of a relationship vary widely from country to country. What feels polite in Kenya may feel cold somewhere else, and the reverse is true too.</p><p>Be curious and avoid stereotypes. Ask questions about daily life, and share your own without assuming the other person will understand everything. Talk early about what you each want, whether friendship, dating or marriage, and whether either of you wants to move countries. Do not assume that a polite compliment means a promise. Clear and kind communication prevents many misunderstandings.</p><h2>Protecting your privacy while you talk</h2><p>Even with a person who seems genuine, share details slowly. Early on, there is no need to give your full name, home address, workplace, ID number, or banking and M-Pesa information. Be careful with photos too, especially anything private, because pictures can be saved, copied and misused. If someone pressures you for photos or details, that is a reason to step back.</p><p>Keep your conversations on the platform for as long as you can, because it gives you tools to block and report. Use strong passwords and be wary of links or files that someone sends you. Our guide to <a href=\"/blog/protect-your-privacy-on-dating-apps\">protecting your privacy on dating apps</a> has simple steps you can follow today.</p><h2>Keeping it healthy and moving to a meeting</h2><p>If a connection grows over weeks or months, you may want to meet in person. Take it slowly and plan carefully. Choose a neutral public place, arrange your own travel and accommodation, tell family or a trusted friend your plans and share your itinerary. Do not rely on the other person for a place to stay at the start, and keep your documents and money in your own control.</p><p>Meanwhile, support the relationship in healthy ways, with regular calls, honest talk and realistic expectations. Our <a href=\"/blog/long-distance-relationship-tips\">long distance relationship tips</a> can help, and our <a href=\"/safety-tips\">safety tips</a> are worth reading before any meeting. For the full picture, see <a href=\"/blog/online-dating-in-kenya-complete-guide\">online dating in Kenya</a>.</p><p>When you are ready to explore, visit the <a href=\"/international\">international section</a>, keep your guard up and enjoy meeting new people with confidence. The world is full of kind people, and a careful approach helps you find them.</p>', 'blog/9dbc479a8dda0aa3.jpg', 'A person on a video call on a laptop with a world map on the wall behind', 'Meeting people abroad safely: tips for Kenyans', 'Tips for meeting people abroad safely online: time zones, video calls, never paying for visas or tickets, spotting scams and handling cultural differences.', 'meeting people abroad safely', '[{\"q\":\"Is it safe to talk to people in other countries online?\",\"a\":\"It can be, if you stay alert. Use a platform with safety tools, keep personal details private, insist on video calls, and never send money. Most connections are harmless, but scammers target people looking for connections abroad, so caution is essential.\"},{\"q\":\"Should I ever pay for a visa or flight ticket for someone I met online?\",\"a\":\"No. Never pay for a visa, ticket, fee or any other cost for someone you have not met in person, and be careful even after that. This is one of the most common scam patterns, and the requests often sound urgent and emotional.\"},{\"q\":\"Can someone online promise to get me a visa or a job abroad?\",\"a\":\"No private person can promise that. Visas and immigration decisions are made by the official authorities of each country. Anyone who says they can arrange it for a fee or for love is a major warning sign. Check official channels yourself.\"},{\"q\":\"How do I confirm that an international contact is real?\",\"a\":\"Ask for a live video call, not a recorded clip. Check that their photos and story stay consistent, and see whether they will talk about everyday details. If they always have an excuse to avoid video, treat that as a red flag and step back.\"},{\"q\":\"How do I handle time zone differences?\",\"a\":\"Agree on a regular time that works for both of you and check each other\'s local time before calling. Be patient about delays in replies. If someone is sleeping while you chat, that is normal, not a sign they are ignoring you.\"}]', NULL, NULL, 0, 'published', 1, '2026-10-08 09:12:00', '2026-10-16 08:12:11', '2026-10-16 20:57:06');
INSERT INTO `blog_posts` (`id`, `category_id`, `author_id`, `title`, `slug`, `excerpt`, `tags`, `content`, `cover_path`, `cover_alt`, `meta_title`, `meta_description`, `focus_keyword`, `faq`, `og_image`, `canonical_url`, `noindex`, `status`, `views`, `published_at`, `created_at`, `updated_at`) VALUES
(25, 6, 1, 'Online dating in Kenya: the complete guide for beginners', 'online-dating-in-kenya-complete-guide', 'Everything you need to start online dating in Kenya: choosing a platform, building a profile, messaging, safety, first meetings, payments and culture.', 'online dating, Kenya, dating guide, safety, profiles, M-Pesa, first date', '<p>A few years ago, most Kenyans met partners through school, church, work or family introductions. Today, many people also meet through their phones. If you are curious about online dating in Kenya, or you tried it once and found it confusing, this guide walks you through the whole process in plain language. We cover how it works, how to pick a platform, how to build a profile, what to say, how to stay safe, how to handle the first meeting and the habits that lead to better results.</p><p>You do not need to be tech-savvy. You need patience, honesty and a bit of caution. Let us start with the basics.</p><h2>How online dating in Kenya works</h2><p>The idea is simple. You create a profile with photos and a short bio, then you browse other people\'s profiles, send messages and see who replies. Some platforms suggest people near you. Others let you search by age, location or interests. When you both feel there is a connection, you chat, perhaps move to a call, and eventually meet in person.</p><p>Online dating is not only about romance. People use these platforms to find a serious partner, to date casually without pressure, to meet friends in a new town or to connect with people abroad. Be clear with yourself about what you want, because that helps you choose the right people and write an honest profile.</p><p>The big advantage is reach. In a busy city like Nairobi, or a smaller town like Nyeri, you may never cross paths with someone who shares your interests. A good platform puts those people in front of you. The challenge is that you cannot see who is real or trustworthy at first glance, which is why the rest of this guide stresses care and common sense.</p><h2>Choosing a platform wisely</h2><p>Not all platforms are equal. Before you create an account, check a few things:</p><ul>\r\n<li>Verification: Does the platform check that profiles belong to real people? Read our article on <a href=\"/blog/why-profile-verification-matters\">why profile verification matters</a>.</li>\r\n<li>Safety tools: Can you block and report someone easily?</li>\r\n<li>Clear pricing: Is it obvious what is free and what you pay for?</li>\r\n<li>Local focus: Are the people near you and genuinely in Kenya, or scattered across the world?</li>\r\n<li>Privacy: Does it explain how your photos and details are used?</li>\r\n</ul><p>Spend ten minutes reading the <a href=\"/faq\">frequently asked questions</a> and the <a href=\"/about\">about page</a> of any platform you consider. Be wary of any service that promises instant love, pressures you to pay at once or asks you to move to another app on the first day. If something feels too good to be true, it usually is.</p><h2>Building a profile that gets attention</h2><p>Your profile is your first impression, and it has two main parts: photos and words.</p><p>For photos, use clear, recent pictures in good light. One should show your face clearly and one should show you doing something you enjoy. Avoid group photos where nobody can tell which person you are, heavy filters, and pictures of other people. Our guide to <a href=\"/blog/best-dating-profile-photos\">the best dating profile photos</a> goes into detail.</p><p>For your bio, write like you speak. Mention two or three real interests, such as hiking, gospel music or football, your work or studies in general terms, and what you are looking for. A short, warm bio beats a long list of demands. Take a look at <a href=\"/blog/how-to-write-a-dating-profile-bio\">how to write a dating profile bio</a> and the common <a href=\"/blog/dating-profile-mistakes-to-avoid\">dating profile mistakes to avoid</a>.</p><p>Be honest about your age, location and intentions. Small exaggerations cause awkward moments later, and people notice. Honesty also attracts the kind of person you really want to meet.</p><h2>Starting conversations and keeping them going</h2><p>The first message matters. \"Hi\" or \"hey\" rarely gets a reply, because it gives the other person nothing to respond to. Instead, mention something specific from their profile and ask a simple question. For example: \"I saw you love hiking Ngong Hills. Do you have a favourite spot?\" Our guides on <a href=\"/blog/five-first-message-openers\">five first message openers</a> and <a href=\"/blog/how-to-start-a-conversation-online\">how to start a conversation online</a> give plenty of examples.</p><p>Once the chat is going, keep it balanced. Ask questions, answer yours properly and share something about yourself. Do not send many messages in a row if they have not replied, and do not read too much into slow replies. People work, study and live busy lives. For pacing, see <a href=\"/blog/texting-rules-early-dating\">texting rules for early dating</a>.</p><p>If the conversation is going well after some days, you can suggest a short call or an outing. A kind way to do it is shown in <a href=\"/blog/how-to-ask-someone-out-over-text\">how to ask someone out over text</a>. If they say no, thank them and move on. Learning to <a href=\"/blog/how-to-handle-rejection-gracefully\">handle rejection gracefully</a> will save you stress and keep your self-esteem intact.</p><h2>Staying safe from the start</h2><p>Most people online are genuine, but a small number are not, and the safest approach is to assume nothing until you have reason to trust. Keep these rules in mind from the first message:</p><ul>\r\n<li>Never send money, airtime or mobile payments to someone you have not met, whatever the reason.</li>\r\n<li>Do not share your home address, workplace details, ID or financial information early.</li>\r\n<li>Be suspicious of anyone who says \"I love you\" within days, or who always has an emergency.</li>\r\n<li>Insist on a live video call before meeting. Someone who refuses every time may not be who they say they are.</li>\r\n<li>Use the block and report tools whenever someone makes you uncomfortable.</li>\r\n</ul><p>Read our guides to <a href=\"/blog/how-to-spot-fake-profiles-and-catfishing\">spotting fake profiles and catfishing</a>, <a href=\"/blog/romance-scams-in-kenya-warning-signs\">romance scam warning signs</a> and <a href=\"/blog/protect-your-privacy-on-dating-apps\">protecting your privacy on dating apps</a>. The platform\'s own <a href=\"/safety-tips\">safety tips</a> are worth a few minutes before you start.</p><h2>Money, plans and M-Pesa in general terms</h2><p>Many dating platforms are free to join and browse, with paid plans that unlock extra features such as more messaging or visibility. Before you pay, check exactly what each plan includes, how long it lasts and how to cancel. You can see how ConnectHub explains its options on the <a href=\"/pricing\">pricing page</a>. Remember that paying for a plan is a payment to the platform, not to another person.</p><p>In Kenya, mobile money is how most people pay for things, and that is also true on dating platforms. Use the official payment option inside the platform, check the payee name before you confirm, and keep your confirmation message. Never send M-Pesa directly to someone you met online, even for something small. Requests like \"send me airtime\", \"help with hospital bills\" or \"pay the clearing fee for my gift\" are classic tricks. Read <a href=\"/blog/should-you-send-money-to-someone-you-met-online\">should you send money to someone you met online</a> to understand why.</p><h2>Verifying someone and planning the first meeting</h2><p>Before you meet, do a quick reality check. Look for consistent photos, a profile that matches what they say, and a willingness to talk on the phone or video. A short video call is the single best test. If they will not show their face live, or keep making excuses, pause the conversation.</p><p>Our article on <a href=\"/blog/how-to-verify-someone-is-real-before-meeting\">how to verify someone is real before meeting</a> lists simple steps. Trust your gut as well. If something feels off, it is fine to stop, even if you cannot explain why.</p><p>Once you are happy they are real, plan the meeting carefully. Choose a public, busy place in daylight or early evening, such as a cafe, a mall, a garden or a restaurant, and arrange your own transport so you can leave when you want. Tell a friend where you are going and who you are meeting, and share your live location if you can. Do not accept a lift from a first date, and keep your drink in sight.</p><p>Keep the first meeting short, perhaps an hour or two, so there is no pressure. Ideas are in our guides to <a href=\"/blog/first-date-ideas-in-nairobi\">first date ideas in Nairobi</a>, <a href=\"/blog/first-date-ideas-outside-nairobi\">first date ideas outside Nairobi</a> and <a href=\"/blog/first-date-conversation-topics\">first date conversation topics</a>. Use the <a href=\"/blog/meet-someone-safely\">safety checklist for meeting someone</a> beforehand.</p><h2>Culture, family and expectations</h2><p>Dating in Kenya often involves family, community and faith in ways that differ from other places. Some people are open about dating, while others keep it private until things are serious. Be respectful of these differences, and be honest early about what matters to you, whether that is religion, children, location or marriage.</p><p>You will meet people who speak English, Swahili, Sheng or their mother tongue, often mixed in the same chat. Meet people where they are, and avoid assumptions about someone because of their background. Our guide to <a href=\"/blog/dating-etiquette-in-kenya\">dating etiquette in Kenya</a> covers manners, who pays and public versus private behaviour, and <a href=\"/blog/introducing-your-partner-to-family-in-kenya\">introducing your partner to family in Kenya</a> helps when things get serious.</p><p>Timing matters too. Punctuality is a sign of respect, so arrive on time or send a message if you are delayed, especially if you are coming by matatu through Nairobi traffic. On the first date, it is common and kind for the person who invited to offer to pay, but many people prefer to split the bill, and either is fine if you talk about it openly and without pressure.</p><h2>Common pitfalls and how to avoid them</h2><p>Most frustrations in online dating are avoidable. Watch for these patterns:</p><ol>\r\n<li>Chasing too many chats at once, so nobody gets real attention.</li>\r\n<li>Judging people only on photos instead of how they communicate.</li>\r\n<li>Moving too fast, or letting someone else rush you.</li>\r\n<li>Ignoring red flags because you are lonely or hopeful. See <a href=\"/blog/green-flags-and-red-flags-in-early-dating\">green flags and red flags in early dating</a>.</li>\r\n<li>Giving up after a few disappointments. Dating takes patience.</li>\r\n</ol><p>If you have recently ended a relationship, give yourself time first. The guide on <a href=\"/blog/how-to-know-if-you-are-ready-to-date-again\">how to know if you are ready to date again</a> can help you decide. And when you have found someone you like, the <a href=\"/blog/define-the-relationship-talk\">define the relationship talk</a> is an honest way to move forward.</p><h2>Meeting people abroad, and taking the first step</h2><p>Some people want to connect with someone in another country, whether for friendship, culture or a relationship. ConnectHub has an <a href=\"/international\">international section</a> for this. The same rules apply with extra care: use video calls, watch time zones, and never pay for visas or tickets for someone you have not met. Our guide to <a href=\"/blog/meeting-people-abroad-safely\">meeting people abroad safely</a> explains the risks, and <a href=\"/blog/long-distance-relationship-tips\">long distance relationship tips</a> helps you keep things healthy.</p><p>Online dating in Kenya works best when you are clear, patient and careful. Start with an honest profile, send thoughtful messages, move at your own pace and trust your instincts. Whether you want a partner, a friend or simply good company, take it one conversation at a time.</p><p>When you are ready, <a href=\"/register\">create your free profile</a>, <a href=\"/browse\">browse people near you</a> and see what feels right. You can always return to the <a href=\"/blog\">ConnectHub blog</a> for more guides along the way.</p>', 'blog/25e562d53e773353.jpg', 'A young Kenyan couple smiling over coffee while one holds a phone', 'Online dating in Kenya: the complete beginner guide', 'A complete guide to online dating in Kenya: how it works, picking a platform, writing a profile, staying safe, M-Pesa payments and meeting in person.', 'online dating in Kenya', '[{\"q\":\"Is online dating in Kenya safe?\",\"a\":\"It can be safe if you take sensible precautions. Use a platform with verified profiles, keep personal details private early on, never send money to someone you have not met, meet in a busy public place, and tell a friend where you are going.\"},{\"q\":\"How do I choose a good dating site or app in Kenya?\",\"a\":\"Look for clear safety information, profile verification, easy ways to block and report, transparent pricing and real people near you. Read the terms, check what is free and what is paid, and avoid any service that pressures you to pay quickly.\"},{\"q\":\"Do I have to pay to use online dating in Kenya?\",\"a\":\"Many platforms let you create a profile and browse for free, with paid plans for extra features such as more messaging. Check what is included before you pay, and pay only through the official platform, never by sending money directly to another user.\"},{\"q\":\"How soon should I meet someone I met online?\",\"a\":\"There is no fixed rule. Many people chat for a week or two, then move to a voice or video call, and meet once they feel comfortable. Do not let anyone rush you. A genuine person will respect your pace.\"},{\"q\":\"What are the biggest mistakes people make when dating online?\",\"a\":\"Common mistakes include using unclear photos, sending only \\\"hi\\\", sharing personal details too early, trusting someone who asks for money, and expecting a perfect match immediately. Be honest, patient and cautious, and enjoy getting to know people.\"},{\"q\":\"Can I use online dating to meet people abroad?\",\"a\":\"Yes, some platforms offer an international section for meeting people in other countries. Be extra careful: never pay for visas or tickets for someone you have not met, and use video calls to confirm they are real.\"}]', NULL, NULL, 0, 'published', 1, '2026-10-09 09:12:00', '2026-10-16 08:12:11', '2026-10-16 20:55:57'),
(26, 2, 1, 'How to protect your privacy on dating apps', 'protect-your-privacy-on-dating-apps', 'What to keep private, how photos and locations can give you away, and simple habits for usernames, screenshots and sharing, before you trust someone new.', 'privacy, data protection, online safety, dating apps, personal information, Kenya', '<p>Meeting new people online is fun, but a dating profile also puts a lot of information about you in front of strangers. Learning to protect your privacy on dating apps does not mean being secretive or cold. It means choosing what to share, when to share it and with whom, so that you stay in control. This guide covers practical habits you can start using today.</p><p>Think of privacy like a front gate. You are happy to welcome good people inside, but you decide when to open it and how far they get. The rest of this article shows you how to do that without losing the warmth that makes dating enjoyable.</p><h2>Why you should protect your privacy on dating apps</h2><p>Most people you meet are exactly who they seem, and a little privacy does not change that. The reason to be careful is that information is hard to take back. A detail you share in week one can be copied, forwarded or used in ways you never imagined, and a stranger who knows your workplace, your estate and your routine knows far more than a new match needs to.</p><p>Privacy also protects you from small, everyday annoyances, such as unwanted visits, constant messages from someone you have decided not to see, or people who find your family on social media. Good habits cost you almost nothing and keep these problems away.</p><h2>What not to share, at least at first</h2><p>The early days of chatting are for getting to know a person, not for handing over the keys to your life. Until you have met several times and built real trust, hold back on the following.</p><ul>\r\n<li><strong>Your home address</strong>, including your estate name, building and gate details.</li>\r\n<li><strong>Your exact workplace</strong> or the office floor you work on.</li>\r\n<li><strong>Your ID number, passport details or KRA PIN.</strong></li>\r\n<li><strong>Bank, M-Pesa or card details</strong>, passwords, PINs and one-time codes. A genuine person will never need these.</li>\r\n<li><strong>Private or intimate photos.</strong> Once sent, you cannot control where they go.</li>\r\n<li><strong>Your daily routine</strong>, such as when you leave work and which route you take.</li>\r\n<li><strong>Details about children or relatives</strong>, such as their school and where they live.</li>\r\n</ul><p>You can still be open about your interests, your humour and your general story. For example, you can say you work in finance in Nairobi without naming the company. That is enough to have a good conversation.</p><h2>Photos, metadata and what the background says</h2><p>Photos are one of the biggest ways people give away more than they intend. There are two things to think about.</p><p>First, the picture itself. A selfie outside your gate, in front of your building or in uniform with a visible name tag tells a stranger where you live or work. Choose photos with neutral backgrounds, such as a park, a cafe or a plain wall, and avoid clear shots of number plates, school logos or street signs.</p><p>Second, hidden information. Many phones attach extra details to photos, which can include the date, the time and sometimes the place where the picture was taken. Different apps handle this in different ways, and many remove it when you upload, but you should not assume so. The safest habit is to turn off location tagging for your camera in your phone settings, and to be thoughtful about sending original photo files directly to someone you do not know well. If you want help choosing good profile photos, see our guide to <a href=\"/blog/best-dating-profile-photos\">best dating profile photos</a>.</p><h2>Keep your location general</h2><p>You can be easy to find without giving an exact location. Share your town or neighbourhood, such as Kilimani, Nyali or Milimani, not your street. When arranging to meet, suggest a public place and do not offer to be collected from home. Be careful with live location sharing, which is useful with a trusted friend on a date but not with someone you have only just matched with.</p><p>Check your app settings, too. Look for options that control whether your distance, last active time or exact area is visible, and set them to what you are comfortable with. Social media can also reveal where you are, so think about tagged check-ins and posts that show your home or workplace.</p><h2>Usernames, social media and being searchable</h2><p>It is easy to forget that a username can lead to much more than a dating profile. If you use the same handle, email or photo everywhere, someone can follow the trail to your other accounts, your friends and your family.</p><ul>\r\n<li>Use a first name or nickname, not your full name.</li>\r\n<li>Avoid a username that includes your surname, birth year or workplace.</li>\r\n<li>Consider a separate email address for dating and friendship sites.</li>\r\n<li>Review your social media privacy settings so strangers cannot see everything.</li>\r\n<li>Be thoughtful about when you follow or connect with someone, and wait until you trust them.</li>\r\n</ul><p>Checking whether a match is real is fair too. Our article on <a href=\"/blog/how-to-verify-someone-is-real-before-meeting\">how to verify someone is real before meeting</a> shows you how to do it without oversharing yourself.</p><h2>Moving to WhatsApp and swapping numbers</h2><p>Many people move to WhatsApp very quickly, but it is a big step. Your number can lead to your profile photo, your status and sometimes your name, and it is hard to take back once shared. Stay on the app until you feel comfortable, have a video call, and then decide.</p><p>When you do share your number, you can say: \"I am happy to move to WhatsApp after we have had a quick video call this week.\" If someone pushes to leave the platform in the first day or two, treat it as a warning sign, particularly if money or secrecy comes up later. Our guide to <a href=\"/blog/romance-scams-in-kenya-warning-signs\">romance scams in Kenya</a> explains why.</p><h2>How to share more as trust grows</h2><p>Privacy is not a wall. It is a dial, and you turn it up or down as you get to know someone. A sensible pace looks something like this: first you chat on the app, then you have a video call, then you meet in a public place, and only after a few good meetings do you share things like where you live or who your family are.</p><p>Watch how the other person treats your boundaries. Someone who says \"no problem, take your time\" when you decline a question is showing respect. Someone who sulks, pushes or keeps asking is showing something else. You can always say, \"I like to keep that private for now, but I am happy to tell you more later.\" That line is polite, honest and easy to use.</p><h2>Screenshots, sharing and respecting others</h2><p>Screenshots are useful, and they are also risky. You should keep screenshots of harassment or suspicious behaviour as evidence. But avoid posting or forwarding other people\'s private messages and photos without their permission. Sharing someone\'s private pictures can hurt them badly and cause trouble for you.</p><p>The same care applies to your own images. Never send a private picture to someone you do not know well, however sweet they are. If someone pressures you for one, that tells you what you need to know. Block them and report them, and see our guide on <a href=\"/blog/how-to-block-and-report-someone-online\">how to block and report someone online</a>.</p><h2>Your rights over your personal data in Kenya</h2><p>Kenya\'s Data Protection Act 2019 gives people rights over their personal data, including the right to know how it is being used and to ask for corrections. Companies that handle personal data have responsibilities too. This is a general overview and not legal advice. If you have a specific concern, the Office of the Data Protection Commissioner is the right place to look.</p><p>On a practical level, it is worth reading how any app uses your information, checking your privacy settings regularly and deleting accounts you no longer use. You can find out how ConnectHub approaches safety on our <a href=\"/safety-tips\">safety tips</a> page and in our <a href=\"/faq\">FAQ</a>. When you are ready to meet people at your own pace, you can <a href=\"/register\">create a free profile</a> or <a href=\"/browse\">browse people near you</a>.</p>', 'blog/338684a3cf8c2237.jpg', 'A person holding a phone with a padlock icon on the screen', 'How to protect your privacy on dating apps', 'Learn how to protect your privacy on dating apps: what not to share, photo and location tips, safer usernames and your rights over personal data in Kenya.', 'protect your privacy on dating apps', '[{\"q\":\"What personal information should I never share on a dating app?\",\"a\":\"Avoid sharing your home address, exact workplace, ID number, bank or M-Pesa details, passwords, one-time codes and private photos. Also be careful with your children\'s school and your daily routine. Share more only as trust grows and after you have met in person.\"},{\"q\":\"Can photos reveal where I live?\",\"a\":\"They can. Photos sometimes carry hidden details such as the time and place they were taken, and the background can show a gate, building or landmark near your home. Choose photos with neutral backgrounds and check your phone settings for location tagging.\"},{\"q\":\"Is it safe to move to WhatsApp quickly?\",\"a\":\"It is better to wait until you have talked for a while and feel comfortable. Moving to a personal number shares your phone number and often your profile photo and status. Chat on the app first, and consider a video call before you swap numbers.\"},{\"q\":\"Do I have rights over my personal data in Kenya?\",\"a\":\"Yes. Kenya\'s Data Protection Act 2019 gives people rights over their personal data, such as knowing how it is used and asking for corrections. This is a general overview and not legal advice. For specific questions, contact the Office of the Data Protection Commissioner.\"},{\"q\":\"Should I screenshot or share someone else\'s messages?\",\"a\":\"Keep screenshots for your own records, especially if someone is harassing you. Do not post or forward private chats or photos of other people without their consent. Respecting privacy goes both ways, and it keeps you out of trouble too.\"}]', NULL, NULL, 0, 'published', 3, '2026-10-10 09:12:00', '2026-10-16 08:12:11', '2026-10-16 20:53:27'),
(27, 2, 1, 'Romance scams in Kenya: warning signs and what to do', 'romance-scams-in-kenya-warning-signs', 'Love bombing, urgent emergencies, requests for M-Pesa or airtime, parcel fees and fake investments: the warning signs of romance scams and how to respond.', 'romance scams, online scams, M-Pesa, safety, DCI, warning signs, Kenya', '<p>Nobody sets out to be tricked. Romance scams in Kenya, like everywhere else, work because they mix real human feelings with a carefully built story. They can happen to anyone who is kind, hopeful or simply busy and trusting. The most useful thing you can do is learn the pattern, because most of these scams follow the same steps.</p><p>This guide explains the warning signs, the most common stories, and exactly what to do if you think you are being scammed or already have been. If this has happened to you, please remember that it is not your fault. The person who lied is the one who did wrong.</p><h2>How a romance scam usually works</h2><p>The pattern is nearly always the same. First, a profile appears that looks attractive and trustworthy. Contact starts warmly, with lots of attention and compliments. Over days or weeks, the scammer builds a close bond and wins your trust. Then a problem appears, and with it a request for help, usually money. If you help once, there is often a second request, and a third.</p><p>The scammer is patient because patience works. They may chat with you every morning and evening, remember details about your family and say all the right things. The warmth feels real, which is exactly why it is so effective.</p><h2>Warning signs of romance scams in Kenya</h2><p>No single sign proves anything, but these are the ones to take seriously, especially in combination.</p><ul>\r\n<li><strong>Love bombing.</strong> They say \"I love you\" or \"you are my destiny\" within days and shower you with affection before you have properly met.</li>\r\n<li><strong>No video call, ever.</strong> They always have a reason, such as poor network, a broken camera or strict rules at work.</li>\r\n<li><strong>A dramatic job overseas.</strong> Common claims include an engineer on an oil rig, a doctor with an international organisation, a soldier on a mission or a businessperson stuck abroad.</li>\r\n<li><strong>Urgent emergencies.</strong> A sudden hospital bill, a stolen wallet, an arrest, an accident or a family crisis, always needing money fast.</li>\r\n<li><strong>Pressure and secrecy.</strong> They ask you to keep the relationship private from your friends or family, or they get angry when you ask questions.</li>\r\n<li><strong>They cannot ever meet.</strong> Flights are cancelled, visas delayed, and plans fall through at the last minute.</li>\r\n</ul><h2>What scammers ask for</h2><p>Money is rarely requested in the first message. It usually arrives after trust has been built, and it often starts small.</p><ul>\r\n<li><strong>M-Pesa or mobile money.</strong> A small amount for \"fare\", \"airtime\" or \"data bundles\", then larger amounts.</li>\r\n<li><strong>Airtime or vouchers.</strong> Easy to ask for and hard to trace.</li>\r\n<li><strong>Bank transfers.</strong> For a hospital, a lawyer, school fees or a business deal.</li>\r\n<li><strong>Cryptocurrency.</strong> Often linked to an \"investment\", with promises that you will get much more back.</li>\r\n<li><strong>Gift cards or online payments.</strong> Especially when they claim to be abroad.</li>\r\n</ul><p>The rule is simple. If someone you have not met in person asks for money in any form, the answer is no. Our guide on <a href=\"/blog/should-you-send-money-to-someone-you-met-online\">whether you should send money to someone you met online</a> has polite ways to say it.</p><h2>Parcel, customs and investment scams</h2><p>Two stories come up again and again, so it helps to recognise them.</p><p><strong>The parcel or customs fee scam.</strong> The person says they are sending you a gift, perhaps a phone, jewellery, cash or a care package from abroad. Then you receive a call or message from a \"courier\" or \"customs officer\" saying the parcel is held at the airport and you must pay a clearance fee, tax or storage charge by M-Pesa. There is no parcel. The fee is the whole point, and once you pay, a new fee usually appears.</p><p><strong>The investment scam.</strong> After weeks of friendly chat, the person mentions an easy way to make money, such as crypto trading, forex or a special business opportunity. They show you screenshots of profits and encourage you to put in a little. It may even appear to work at first. When you try to withdraw or invest more, there is always a new charge or a delay. Real partners do not need you to send money to prove you love them.</p><h2>Why these scams work on smart people</h2><p>If you have ever thought \"I would never fall for that\", you are in good company. These scams do not work because people are foolish. They work because they feel personal. The scammer pays attention, mirrors your values and often chooses a moment when you feel lonely, stressed or hopeful. Anyone can be reached this way.</p><p>Understanding that helps you act sooner. If you notice yourself being rushed, flattered into silence or told to keep things secret, pause. Talk to a trusted friend. A good friend will see the pattern even when you cannot. For more signs of a made-up identity, read <a href=\"/blog/how-to-spot-fake-profiles-and-catfishing\">how to spot fake profiles and catfishing</a>.</p><h2>What to do if you have been scammed</h2><p>If you realise you have been targeted, take these steps in order. Do them as calmly as you can.</p><ol>\r\n<li><strong>Stop contact.</strong> Do not argue, threaten or plead. Simply stop replying, and do not send anything more, even to \"get your money back\".</li>\r\n<li><strong>Keep evidence.</strong> Save chat messages, profile screenshots, phone numbers, M-Pesa messages, receipts and any account details they gave you.</li>\r\n<li><strong>Contact your bank or mobile money provider.</strong> Report what happened straight away. Quick reports give them the best chance to help.</li>\r\n<li><strong>Report to the platform.</strong> Block the profile and report it so the account can be reviewed and others protected.</li>\r\n<li><strong>Report to the Directorate of Criminal Investigations (DCI).</strong> Take your evidence with you or follow the reporting channels the DCI provides.</li>\r\n<li><strong>Protect your accounts.</strong> If you shared passwords, PINs or ID details, change passwords and tell your provider.</li>\r\n</ol><p>If you are in immediate danger, or someone is threatening or blackmailing you, call 999 or 112. Be careful about people who contact you afterwards offering to recover your money for a fee. That is a common follow-up scam. Our article on <a href=\"/blog/how-to-block-and-report-someone-online\">how to block and report someone online</a> has more on collecting evidence.</p><h2>If someone you love may be a target</h2><p>Sometimes you are the one who spots the warning signs in a friend, a parent or a cousin. Perhaps they are suddenly secretive about their phone, talking about a partner they have never met, or short of money for no clear reason. Be careful how you raise it. If you say \"you are being foolish\", they are likely to defend the other person and close the door on you.</p><p>Try something gentler: \"I am happy you have found someone who makes you smile. Would you mind if we looked at the profile together, and asked for a quick video call?\" Ask questions instead of delivering verdicts, offer to sit with them while they check, and make it clear that you are on their side whatever happens. People who feel judged tend to hide things, while people who feel supported tend to ask for help sooner.</p><h2>How to protect yourself going forward</h2><p>Good habits make you much harder to trick. Ask for a video call early. Be cautious when someone moves very fast. Keep conversations on the platform until you trust the person. Tell a friend about new connections. Never send money, airtime or private photos to someone you have not met and checked.</p><p>Verified badges on ConnectHub add one more layer, and you can read more on our <a href=\"/safety-tips\">safety tips</a> page. When you are ready to meet someone, follow our checklist for <a href=\"/blog/meet-someone-safely\">meeting someone safely</a>. You can <a href=\"/browse\">browse real people near you</a> or <a href=\"/register\">create a free profile</a> any time.</p>', 'blog/73fa7a5ee80bbc22.jpg', 'A person reading a worrying message on their phone at a desk', 'Romance scams in Kenya: warning signs to know', 'Know the warning signs of romance scams in Kenya, from love bombing to M-Pesa requests and parcel fees, and what to do if you have been scammed.', 'romance scams in Kenya', '[{\"q\":\"What is a romance scam?\",\"a\":\"A romance scam is when someone pretends to love or care about you in order to get your money or personal information. The scammer builds trust over days or weeks, then invents a reason to ask for help. The feelings are often fake, but the harm to the victim is real.\"},{\"q\":\"How do I know if someone online is a scammer?\",\"a\":\"Common signs are declarations of love very early, refusing video calls, a story about working overseas, constant emergencies, and any request for money, airtime, M-Pesa, bank transfers or crypto. If someone you have never met asks for money, treat it as a scam until proven otherwise.\"},{\"q\":\"What should I do if I have sent money to a scammer?\",\"a\":\"Stop all contact, do not send anything more, and keep every message, receipt and screenshot. Contact your bank or mobile money provider straight away to report what happened, then report to the platform and to the Directorate of Criminal Investigations (DCI). Acting quickly gives you the best chance of help.\"},{\"q\":\"Can I get my money back after a romance scam?\",\"a\":\"It is not always possible, and no one can promise it. Your bank or mobile money provider may be able to act on a quick report, and the DCI can look into the case. Beware of anyone who offers to recover your money for a fee, because that is often a second scam.\"},{\"q\":\"Are romance scams only about money?\",\"a\":\"No. Some scammers want personal details such as ID copies, photos or passwords, which can be used for fraud or blackmail. Be careful about what you share, and never send private photos or documents to someone you have not met and verified.\"},{\"q\":\"Who can I report a romance scam to in Kenya?\",\"a\":\"Report to the platform where you met the person, to your bank or mobile money provider, and to the Directorate of Criminal Investigations (DCI). If you or someone else is in immediate danger, call 999 or 112.\"}]', NULL, NULL, 0, 'published', 3, '2026-10-11 09:12:00', '2026-10-16 08:12:11', '2026-10-16 20:52:12'),
(28, 2, 1, 'Should you send money to someone you met online?', 'should-you-send-money-to-someone-you-met-online', 'The honest answer is almost always no. Learn what makes a request risky, when helping is different, and how to say no kindly with scripts you can copy.', 'money, scams, safety, M-Pesa, online dating, saying no, Kenya', '<p>It usually begins gently. You have been chatting for a couple of weeks, you feel a connection, and then a message arrives: they are in trouble, and could you help, just this once? If you are wondering whether to send money to someone you met online, the short, honest answer is almost always no. This article explains why, how to tell a risky request from a normal one, and how to say no without guilt.</p><p>Saying no does not make you unkind. It makes you careful. A person who truly cares about you will respect that.</p><h2>Why the answer is almost always no</h2><p>When you have only known someone through a screen, you cannot confirm who they are, where they are or whether their story is true. Photos can be copied. Names can be invented. Even a warm voice on a call can belong to someone playing a role. Once you send money by M-Pesa, bank transfer or any other quick method, it is often very hard to get back.</p><p>There is also a pattern worth knowing. People who ask for money early in an online friendship often ask again. A small amount for airtime today becomes a hospital bill next week and a \"business loan\" the week after. The first yes makes the next request easier. For the full picture of how this plays out, read our guide to <a href=\"/blog/romance-scams-in-kenya-warning-signs\">romance scams in Kenya</a>.</p><h2>Should you send money to someone you met online? Look at the request</h2><p>Real life does include genuine emergencies, so it helps to look closely at what is being asked and how. Here are the features that make a request risky.</p><ul>\r\n<li><strong>You have never met in person.</strong> This alone is reason enough to decline.</li>\r\n<li><strong>It is urgent.</strong> \"I need it in the next hour or something terrible will happen.\" Pressure is a tactic.</li>\r\n<li><strong>It is secret.</strong> They ask you not to tell anyone or say it is embarrassing.</li>\r\n<li><strong>It goes to a personal number or odd account.</strong> Especially a name that does not match theirs.</li>\r\n<li><strong>It comes with guilt.</strong> \"If you really loved me you would help.\" Love does not need a payment.</li>\r\n<li><strong>The story keeps growing.</strong> Each time you help, a new problem appears.</li>\r\n<li><strong>It involves crypto, gift cards or investments.</strong> These are favourite tools of scammers.</li>\r\n</ul><p>A request may be gentler than this. It might be \"just a little airtime\" or \"a small amount for fare to see you\". Treat small requests the same way. Their size is not what matters. The pattern is.</p><h2>Legitimate versus risky: telling the difference</h2><p>It is fair to ask whether there is ever a safe time. Here is a simple way to think about it.</p><ol>\r\n<li><strong>Have you met in person many times?</strong> If not, the answer stays no.</li>\r\n<li><strong>Have you met their friends or family and seen how they live?</strong> A genuine relationship has real people around it.</li>\r\n<li><strong>Could you check the story yourself?</strong> You could call the hospital, speak to a relative or see the situation directly.</li>\r\n<li><strong>Is it your decision, made calmly, with no pressure?</strong> A decision made in a hurry is rarely a good one.</li>\r\n<li><strong>Could you afford to lose this money and not be harmed?</strong> If losing it would hurt you, do not send it.</li>\r\n</ol><p>Even when all of these are true, it is wise to talk it over with a trusted friend first. If you are still chatting online and have not met, you are in the \"no\" zone, whatever the story.</p><h2>How to say no kindly</h2><p>Many people agree to send money because they feel awkward saying no. You do not need a perfect speech. Pick a line that sounds like you and keep it short. Here are scripts you can copy.</p><ul>\r\n<li>\"I care about you, but I do not send money to anyone I have not known in person. I hope you find a solution.\"</li>\r\n<li>\"I cannot help with money. If it is a medical emergency, I would suggest speaking to the hospital or your family.\"</li>\r\n<li>\"My rule is that I never lend money to people online, and it is not personal. I hope you understand.\"</li>\r\n<li>\"I am not comfortable with this. Let us talk about something else, or I will end the chat here.\"</li>\r\n</ul><p>Say it once, then stop. You do not have to defend your decision or keep proving that you are not selfish.</p><h2>Common stories behind the request</h2><p>The reasons change, but the shape stays the same. Here are some you may hear, and what to think when you do.</p><ul>\r\n<li><strong>\"I am stuck at the airport and need a fee to travel to you.\"</strong> If someone truly wants to meet you, they can arrange their own journey, and you can meet in a public place on your own terms.</li>\r\n<li><strong>\"My parcel to you is held by customs.\"</strong> Real gifts do not need you to pay a stranger by M-Pesa.</li>\r\n<li><strong>\"My mother is in hospital.\"</strong> It is a painful story, which is exactly why it works. A hospital can be called, and family members exist.</li>\r\n<li><strong>\"I have a business chance that will make us both rich.\"</strong> Anyone who truly loves you will not need your savings to prove it.</li>\r\n<li><strong>\"I lost my phone and wallet, please send airtime.\"</strong> If they can message you, they can ask a friend, and a friend of theirs will exist.</li>\r\n</ul><p>You do not have to prove a story is false before you say no. It is enough that you cannot confirm it, and that you have not met.</p><h2>If they push, guilt you or get angry</h2><p>Pay attention to how a person responds to your no. A genuine friend might be disappointed but will accept it and still treat you with respect. Someone who tries to guilt you, call you cold, threaten to end things or keep repeating the request is showing you what the relationship is really about.</p><p>You might hear lines such as \"I thought you were different\" or \"I will lose everything because of you\". Do not be moved by these. You are not responsible for a stranger\'s crisis. Stop replying, take screenshots, block the profile and report it. Our guide on <a href=\"/blog/how-to-block-and-report-someone-online\">how to block and report someone online</a> shows you how, and <a href=\"/blog/how-to-spot-fake-profiles-and-catfishing\">how to spot fake profiles and catfishing</a> will help you check whether the profile was real in the first place.</p><h2>What to do if you already sent money</h2><p>If you have already sent money, please be gentle with yourself. This has happened to many thoughtful people, and the person who lied is the one at fault. The most important step is to stop. Do not send more to \"fix\" the problem or to recover what you lost.</p><ul>\r\n<li>Keep receipts, M-Pesa messages and chats.</li>\r\n<li>Contact your bank or mobile money provider quickly and explain what happened.</li>\r\n<li>Report the profile to the platform.</li>\r\n<li>Report to the Directorate of Criminal Investigations (DCI).</li>\r\n<li>Call 999 or 112 if anyone threatens you.</li>\r\n</ul><p>Be cautious of anyone who offers to get your money back for an upfront fee. That is another common trick.</p><h2>Keep your heart open and your wallet closed</h2><p>You can be warm, trusting and hopeful and still protect your money. Real connections are built on conversation, time and meeting in person, not on transfers. If you are chatting with someone new, take it slowly, ask for a video call, and meet in a public place when you are ready, using our <a href=\"/blog/meet-someone-safely\">checklist for meeting someone safely</a>.</p><p>ConnectHub offers verified profiles and block and report tools to help, and our <a href=\"/safety-tips\">safety tips</a> page has more. You can <a href=\"/browse\">browse real people near you</a> or <a href=\"/register\">create a free profile</a> and start meeting people the safe way.</p>', 'blog/cdc80af18518221a.jpg', 'A person holding a phone showing a mobile money request, looking unsure', 'Should you send money to someone you met online?', 'Wondering whether to send money to someone you met online? Here is why the answer is almost always no, plus polite scripts for saying it.', 'send money to someone you met online', '[{\"q\":\"Is it ever okay to send money to someone I met online?\",\"a\":\"Not before you have met in person, known them for a long time and seen their life with your own eyes. Even then, think carefully. If you have only chatted online, the safest answer is no, whatever the story. A genuine person will understand your boundary.\"},{\"q\":\"What if they say it is a real emergency?\",\"a\":\"Emergencies are the most common reason scammers give. Stay calm and slow down. Suggest that they contact family, their employer, a hospital or the authorities where they are. If their reaction to your refusal is anger or guilt, that tells you a lot.\"},{\"q\":\"How do I say no without hurting their feelings?\",\"a\":\"Be kind, short and firm. You might say: \\\"I care about you, but I do not send money to anyone I have not known in person. I hope you find a solution.\\\" You do not need to explain further, and you do not need to apologise.\"},{\"q\":\"Is sending airtime or a small amount safe?\",\"a\":\"Small requests are often the first step. Scammers test whether you will say yes, then ask for more. Treat airtime, data bundles and small M-Pesa amounts the same as large sums when the person is someone you have not met.\"},{\"q\":\"What if I already sent money?\",\"a\":\"Stop sending more, keep your receipts and messages, and contact your bank or mobile money provider quickly. Report the person to the platform and to the Directorate of Criminal Investigations (DCI). It is not your fault, and reporting helps protect others.\"}]', NULL, NULL, 0, 'published', 3, '2026-10-12 09:12:00', '2026-10-16 08:12:11', '2026-10-16 20:46:14');
INSERT INTO `blog_posts` (`id`, `category_id`, `author_id`, `title`, `slug`, `excerpt`, `tags`, `content`, `cover_path`, `cover_alt`, `meta_title`, `meta_description`, `focus_keyword`, `faq`, `og_image`, `canonical_url`, `noindex`, `status`, `views`, `published_at`, `created_at`, `updated_at`) VALUES
(29, 1, 1, 'Signs someone likes you online, and how to be sure', 'signs-someone-likes-you-online', 'Not sure if they are interested? These signs someone likes you online show real interest, how to read mixed signals, and what to do next.', 'signs of interest, online dating, mixed signals, messaging, dating tips, Kenya', '<p>Reading someone through a screen is hard. There is no tone of voice, no body language, and one short message can mean many things. Still, there are clear signs someone likes you online, and once you know them you can stop overthinking every reply. This guide explains the signals that show real interest, the mixed signals that deserve a closer look, and the kind way to find out for sure.</p><p>One thing to remember from the start: no single sign proves anything. Look for a pattern over several days, not a single message.</p><h2>They put in steady effort</h2><p>The clearest sign of interest is effort. A person who likes you will make time to talk, even when they are busy. They may not reply instantly, but they do come back. Their messages are more than one word, and they try to keep the chat alive.</p><ul>\r\n<li>They reply with real sentences and not just \"ok\" or \"lol\".</li>\r\n<li>They start conversations as often as you do.</li>\r\n<li>They say good morning or check in after a long day.</li>\r\n<li>They tell you when they will be busy, so you are not left guessing.</li>\r\n</ul><p>Consistency matters more than speed. Someone who replies warmly every day, even if it takes a few hours, is showing more interest than someone who replies in seconds one day and disappears for a week.</p><h2>They ask about you and remember the answers</h2><p>Curiosity is a big signal. People who like you want to know what your life is like. They ask follow-up questions, and they remember what you told them. If you mentioned an interview on Tuesday and they ask how it went on Wednesday, they were listening.</p><p>Look for questions that go beyond small talk, such as \"How was your sister\'s wedding?\" or \"Did you finish the book you were reading?\". Remembering small details is a way of saying \"You matter to me\" without using those words. If you want to deepen this kind of chat, see our guide on <a href=\"/blog/how-to-start-a-conversation-online\">how to start and keep a conversation online</a>.</p><h2>They share about themselves</h2><p>Interest goes both ways. When someone likes you, they open up gradually. They share stories about their family, their work, their hopes and the funny things that happened to them today. They may send you a photo of their lunch or a meme that made them think of you.</p><p>Notice whether they share things that feel personal. Vulnerability, even in small amounts, usually means trust. If a person only answers your questions but never offers anything of their own, they may be shy, or they may simply be less engaged. Time will tell which.</p><h2>The signs someone likes you online during busy weeks</h2><p>Everyone has hard weeks. The telling thing is how a person behaves when life is busy. Someone who likes you will usually find a small way to stay in touch. They might send a short message like, \"Crazy week at work, but I thought of you. Let us catch up on the weekend.\"</p><p>That small gesture tells you a lot. It shows they value the connection enough to protect it. By contrast, someone who vanishes without a word and then returns with no explanation, over and over, is showing you where you sit on their list. Do not make excuses for a pattern that leaves you feeling anxious.</p><h2>They move towards meeting you</h2><p>Talk is lovely, but action is the strongest sign. A person who likes you will start to suggest more connection. They may say, \"I would love to hear your voice,\" or \"We should get coffee when you are free.\" They might ask about your schedule and suggest a real plan.</p><ul>\r\n<li>They suggest a voice or video call.</li>\r\n<li>They propose a specific time and a public place to meet.</li>\r\n<li>They respect your boundaries and do not rush you.</li>\r\n<li>They are happy to meet somewhere busy and in the daytime.</li>\r\n</ul><p>Respect for your comfort is just as important as interest itself. Someone who truly likes you will not mind a public place, a friend knowing your plans, or a video call first. If someone pushes for secrecy, private places or quick commitment, be careful. Our <a href=\"/blog/green-flags-and-red-flags-in-early-dating\">green flags and red flags guide</a> explains what to watch for.</p><h2>Small signs that add up</h2><p>Beyond the big ones, there are quieter signals that tend to show up together when someone is interested. None of them means much alone, but a handful in the same week is encouraging.</p><ul>\r\n<li>They react warmly to your photos and updates, and they say something kind and specific.</li>\r\n<li>They use your name, or a friendly nickname you both enjoy.</li>\r\n<li>They tease you gently and laugh at your jokes.</li>\r\n<li>They talk about the future in small ways, such as \"we should try that place one day\".</li>\r\n<li>They tell you about their day without being asked.</li>\r\n</ul><p>Think of these as clues, not proof. People show affection in different ways, and shy people may be warm in tiny details rather than big gestures. Give each person room to be themselves.</p><h2>Mixed signals, and what to do about them</h2><p>Sometimes the signs point in different directions. They are very warm for two days and then quiet. They flirt but never suggest meeting. They like your photos but give short replies. This can happen for ordinary reasons such as stress, shyness or a busy job. It can also mean someone is keeping their options open.</p><p>Here is a calm way to handle it:</p><ol>\r\n<li>Watch the pattern for a week or two before deciding.</li>\r\n<li>Match their energy. Do not chase, and do not play games.</li>\r\n<li>Ask a direct, friendly question: \"I enjoy chatting with you. Would you like to meet up sometime?\"</li>\r\n<li>Pay attention to the answer, and to what they do afterwards.</li>\r\n<li>If the signals stay confusing, move your attention elsewhere.</li>\r\n</ol><p>You deserve clarity. Our <a href=\"/blog/texting-rules-early-dating\">texting rules for early dating</a> can help you tell the difference between a busy person and a disinterested one.</p><h2>Be careful with signs that are too good</h2><p>Real interest grows steadily. Be careful when it arrives suddenly and very strongly. If someone you have never met says they love you within days, calls you pet names immediately, or tells you that you are their destiny, slow down. Add to that any request for money, airtime, gifts or an emergency favour, and it is a serious warning sign.</p><p>Genuine people can be affectionate, but they do not need your money to prove it. Read our article on <a href=\"/blog/romance-scams-in-kenya-warning-signs\">romance scam warning signs in Kenya</a> and never send money to someone you have not met in person and come to know well. If a profile or story feels strange, you can also report it to our team.</p><h2>What to do when you think they like you</h2><p>Once you see the signs, the next step is simple: be honest and kind. You can say, \"I really enjoy talking with you. Would you like to meet for coffee?\" Keep the invitation public, specific and low pressure. Our guide to <a href=\"/blog/how-to-ask-someone-out-over-text\">asking someone out over text</a> has examples you can copy.</p><p>If they are not ready, thank them for their honesty and give them space. You cannot control another person\'s feelings, but you can choose how you act. When you want to meet new people, you can <a href=\"/browse\">browse people near you</a> or <a href=\"/register\">join for free</a> and begin a conversation today.</p>', 'blog/12f3550ed64907c0.jpg', 'A woman smiling at a message on her phone while sitting on a balcony', 'Signs someone likes you online: what to look for', 'Wondering about the signs someone likes you online? Learn the clear signals of real interest, the mixed signals to watch, and how to find out kindly.', 'signs someone likes you online', '[{\"q\":\"How do I know if someone likes me over text?\",\"a\":\"Look for steady replies, questions about you, remembered details, effort to keep the chat going and suggestions to talk or meet. One sign alone proves little, but several together usually show real interest.\"},{\"q\":\"Does slow replying mean they are not interested?\",\"a\":\"Not always. Some people work long hours, travel, or simply are not glued to their phones. Look at the overall pattern and effort. Slow but warm and consistent replies are different from vague, cold ones that never go anywhere.\"},{\"q\":\"What are mixed signals in online dating?\",\"a\":\"Mixed signals are behaviour that does not match, such as being very warm one day and distant the next, or flirting but never making plans. If it keeps happening, ask directly and kindly, and decide based on what they do, not what they hint.\"},{\"q\":\"Should I tell someone I like them first?\",\"a\":\"You can. Saying it kindly and simply is brave and saves time. Use a low pressure line such as \\\"I enjoy talking to you and would like to get to know you better.\\\" Accept the answer gracefully, whatever it is.\"},{\"q\":\"Can someone who likes me still ask for money?\",\"a\":\"Real interest does not require money. If someone you have never met asks for cash, airtime, M-Pesa or help with an emergency, treat it as a serious warning sign, whatever affection they show, and do not send anything.\"}]', NULL, NULL, 0, 'published', 2, '2026-10-13 09:12:00', '2026-10-16 08:12:11', '2026-10-16 20:45:22'),
(30, 1, 1, 'Texting rules for early dating: timing, pace and calls', 'texting-rules-early-dating', 'How often should you text, how fast should you reply, and when should you call? Simple texting rules for early dating that keep things relaxed.', 'texting, early dating, reply time, mixed signals, phone call, dating tips, Kenya', '<p>Early dating happens mostly on a phone screen, so a lot of anxiety starts there. How soon should you reply? How many messages are too many? What does a short answer mean? These texting rules for early dating are not strict laws. They are friendly guidelines that help you stay relaxed, avoid games, and read the situation fairly.</p><p>The aim is simple: communicate in a way that is honest, kind and comfortable for both of you, and then move towards meeting safely in real life.</p><h2>Match the pace, do not play games</h2><p>The first rule is the most useful. Aim to match the other person\'s rhythm, not to win a contest. If one person messages twenty times a day and the other once in the evening, someone will feel overwhelmed and someone will feel ignored. Neither is a bad person, they just have different habits.</p><p>Notice what feels comfortable and say so gently. For example, \"I am not on my phone much during work, but I love catching up in the evening.\" That one sentence removes a lot of guesswork. Avoid deliberately waiting hours to appear busy. Games waste time and make honest people anxious.</p><h2>How often to message</h2><p>There is no correct number of messages per day. In the first week, a few good conversations are better than a constant stream of small ones. Quality beats quantity. A thoughtful message in the morning and a longer chat in the evening can feel more connected than twenty \"hi\" and \"what are you doing\" messages.</p><ul>\r\n<li>Send messages when you have something to say, not just to fill silence.</li>\r\n<li>Avoid sending several messages in a row before they reply.</li>\r\n<li>Let conversations end naturally. It is fine to say goodnight and pick up tomorrow.</li>\r\n<li>Do not expect all day availability from someone you have just met.</li>\r\n</ul><p>If you need ideas to keep the chat interesting, see our guide to <a href=\"/blog/how-to-start-a-conversation-online\">starting and keeping a conversation online</a>.</p><h2>Good manners that never go out of style</h2><p>A few simple courtesies make every chat more pleasant. Say please and thank you. Do not send messages at very late or very early hours unless you both chat then. Avoid voice notes that run for minutes unless the other person likes them. Respect it when someone says they are not comfortable sharing something. And if you are going to cancel a plan, say so early and kindly, rather than disappearing.</p><h2>Reply timing without the stress</h2><p>People reply at different speeds. Some answer in seconds, some at lunch, some at night. Many Kenyans work long hours, spend time in transport, or share one phone with limited data, so slow replies do not always mean low interest. Look at the overall effort over several days, not a single gap.</p><p>A good habit is to reply when you can give a real answer. If you are busy, send a short note: \"In meetings all day, will reply properly tonight.\" It takes ten seconds and prevents misunderstandings. If you notice the other person takes a long time, avoid sending angry or guilt-heavy messages such as \"Why are you ignoring me?\". They push people away. Instead, wait and see whether they return with warmth.</p><h2>Texting rules for early dating when you feel mixed signals</h2><p>Mixed signals are one of the most common frustrations. One day they send long, warm messages, and the next day they give short, cold ones. It could be stress, shyness, a busy job or simple uncertainty. It could also be that they are not that interested.</p><ol>\r\n<li>Give it a week or two and watch the pattern.</li>\r\n<li>Do not rush to fill gaps with your own worry.</li>\r\n<li>Ask kindly and directly: \"I like talking with you. Is there something you would like us to do, like a call or a coffee?\"</li>\r\n<li>Judge them on what they do, not on one nice sentence.</li>\r\n<li>If things stay unclear, step back and focus on people who are consistent.</li>\r\n</ol><p>It can help to read about <a href=\"/blog/signs-someone-likes-you-online\">signs someone likes you online</a> to compare what you are seeing with healthy interest. Remember that you are allowed to want clear communication.</p><h2>What to keep for later</h2><p>Text is a lovely way to get to know someone, but it is not the place for everything. Heavy topics such as past hurts, money problems, or serious arguments are better discussed in a call or in person, where tone is clear. The same goes for big declarations of feeling. Starting slowly protects both of you.</p><p>Tone is also easy to misread in writing. A short reply may be a rushed one, and a joke can land as a criticism. Before you assume the worst, read the message again, picture the person typing it between two meetings, and ask a friendly question if you are unsure. \"Did I read that right?\" is a perfectly good thing to type. Clear, kind words, a sensible emoji now and then, and a little patience prevent most arguments before they begin.</p><p>If a misunderstanding does happen, fix it quickly and simply. Say sorry if you were careless, explain what you meant, and move on. Do not let a small confusion grow into a silent standoff of several days. Most early dating friction is nothing more than two busy people reading each other\'s messages too fast.</p><p>Also avoid sending anything you would not want shared. Keep private photos and sensitive personal details to yourself until you really trust a person, and even then be careful. Our guide on how to <a href=\"/blog/protect-your-privacy-on-dating-apps\">protect your privacy on dating apps</a> explains the habits that keep you safe.</p><h2>Moving from texting to a call</h2><p>After several days or a week of good conversation, a short call is the natural next step. Hearing someone\'s voice shows warmth and humour that text cannot, and it helps you confirm that the person is who they say they are. A quick video call is even better for that.</p><p>Make the request easy. You might say, \"I am enjoying our chats. Would you like to do a ten minute call on Thursday evening?\" Choose a time that suits them and keep the first call short. If they keep avoiding a call or refuse a video call every time, be careful, because that can be a sign that the profile is not genuine. Our article on <a href=\"/blog/how-to-spot-fake-profiles-and-catfishing\">how to spot fake profiles and catfishing</a> has more detail.</p><h2>When texting becomes a warning sign</h2><p>Some behaviour is not a texting style, it is a red flag. If someone sends constant messages and gets angry when you do not reply, demands your location, insists on secrecy, or asks for money, airtime or M-Pesa, take a step back. A person who respects you respects your time and your boundaries.</p><ul>\r\n<li>Pressure to reply immediately, every time.</li>\r\n<li>Guilt or anger when you are busy.</li>\r\n<li>Requests for private photos, money or personal documents.</li>\r\n<li>Strong declarations of love before you have met.</li>\r\n</ul><p>If you feel unsafe, you can stop replying, block the person, and report them. Read about <a href=\"/blog/how-to-block-and-report-someone-online\">how to block and report someone online</a> and trust your instincts.</p><h2>From chat to a safe first meeting</h2><p>The purpose of early texting is to find out whether you want to meet. When you both feel comfortable, suggest a simple public plan, such as coffee in the daytime. Tell a friend, arrange your own transport and keep your personal details private until you know the person better. Our <a href=\"/safety-tips\">safety tips</a> page has a checklist you can follow.</p><p>If you are still looking for someone to chat with, you can <a href=\"/browse\">browse people near you</a> and start a conversation at your own pace. Relaxed, honest and consistent beats clever every time.</p>', 'blog/a5559a7cf9a1cbb7.jpg', 'A person holding a phone and looking relaxed while drinking tea', 'Texting rules for early dating that actually help', 'Practical texting rules for early dating: how often to message, how fast to reply, how to handle mixed signals, and when to move from chat to a call.', 'texting rules for early dating', '[{\"q\":\"How often should you text someone you are dating?\",\"a\":\"Match each other\'s pace and keep it comfortable. Some people chat daily, others a few times a week. What matters is that the rhythm feels natural to both of you and that neither person feels anxious or ignored.\"},{\"q\":\"How long should you wait to reply to a text?\",\"a\":\"There is no magic number. Reply when you have a moment to write something thoughtful. Waiting on purpose to look busy is a game. If you will be away for a long time, a short message saying so is kind and clear.\"},{\"q\":\"Is double texting bad?\",\"a\":\"One extra message is fine, especially to share something funny or confirm plans. Sending many messages without a reply can feel pushy. If you have heard nothing for a day or two, send one light follow-up and then let them respond in their own time.\"},{\"q\":\"When should we switch from texting to a phone call?\",\"a\":\"After a few days or a week of good conversation, suggest a short voice or video call. It helps you check that the person is real and that you enjoy their voice. Ask first, keep it brief, and pick a time that suits them.\"},{\"q\":\"What should I do if they send mixed signals by text?\",\"a\":\"Watch the pattern, not one message. If warm and cold swings keep happening, ask a kind, direct question about what they want. If the answers stay vague, protect your peace and give your attention to someone who is clear.\"}]', NULL, NULL, 0, 'published', 3, '2026-10-14 09:12:00', '2026-10-16 08:12:11', '2026-10-16 20:32:00'),
(31, 4, 1, 'Why profile verification matters and how to get verified', 'why-profile-verification-matters', 'What a verified badge means, why it builds trust, how a selfie or ID check works in general, and how to get verified from your profile settings.', 'profile verification, verified badge, trust, safety, online dating, Kenya', '<p>Online, anyone can say they are anyone. A friendly photo and a nice bio take minutes to create, and that is why trust is the most valuable thing on any dating or friendship site. Profile verification is one of the simplest ways to build it. It gives you a quick signal that the person you are chatting with has been checked, and it lets you show that you are real too.</p><p>This guide explains what a verified badge means, why it matters, how verification generally works, and how to get yours on ConnectHub. It also covers what a badge cannot do, so you can use it wisely.</p><h2>What a verified badge actually means</h2><p>A verified badge is a small mark on a profile that shows the person has completed a check. In general, the check confirms that the face in the profile photos belongs to a real person who is taking part in the verification, not someone using pictures borrowed from the internet. Some checks also confirm basic details using an identity document.</p><p>Think of it like a name tag at a gathering. It does not tell you everything about someone, but it tells you that they stood in front of the organisers and were willing to be seen. That small step already sets them apart from profiles that have never been checked.</p><h2>Why profile verification matters</h2><p>There are three clear benefits, whether you are the one browsing or the one being browsed.</p><ul>\r\n<li><strong>It reduces fake profiles.</strong> People who want to deceive others rarely go through a check that requires their own face. A badge makes it harder for catfishers to blend in.</li>\r\n<li><strong>It saves you time and worry.</strong> When you see a badge, you can relax a little and focus on whether you actually get along.</li>\r\n<li><strong>It shows you are serious.</strong> Taking a few minutes to verify tells others you value honesty and are here for real connections, which can lead to more replies.</li>\r\n</ul><p>If you have ever worried about whether someone online is genuine, you are not alone. Our guide to <a href=\"/blog/how-to-spot-fake-profiles-and-catfishing\">spotting fake profiles and catfishing</a> covers the warning signs, and a verified badge is one more helpful clue.</p><h2>How verification works in general</h2><p>Methods differ from site to site, but most follow a similar idea. Here are the common approaches:</p><ol>\r\n<li><strong>A selfie check.</strong> You take a fresh photo of yourself, sometimes copying a pose, so the team can compare it with your profile pictures.</li>\r\n<li><strong>An ID check.</strong> You provide a photo of an identity document, and the team confirms that it matches the person on the profile.</li>\r\n<li><strong>A team review.</strong> A real person looks at what you submitted, decides whether it matches and approves or asks you to try again.</li>\r\n</ol><p>Because a person reviews each submission, it can take a little time, so be patient. If you are asked to retry, it is usually because of poor lighting or a blurry image rather than anything wrong with you.</p><h2>How to get verified on ConnectHub</h2><p>On ConnectHub, verification is handled from your profile settings. The steps are straightforward:</p><ol>\r\n<li>Log in and open your profile settings.</li>\r\n<li>Find the verification option and read the instructions shown.</li>\r\n<li>Submit what is asked for, such as a clear selfie or a document, following the on-screen guidance.</li>\r\n<li>Wait for the team to review it. Once it is approved, the verified badge appears on your profile.</li>\r\n</ol><p>To make approval easy, take your selfie in good natural light, face the camera, remove sunglasses and hats, and make sure your profile photos show you clearly. If your pictures are blurry or hide your face, it is harder to match them. Our guide to the <a href=\"/blog/best-dating-profile-photos\">best dating profile photos</a> will help you choose pictures that work. Do not have a profile yet? You can <a href=\"/register\">create your free profile</a> and begin from there.</p><h2>Keeping your documents private</h2><p>It is sensible to ask what happens to your selfie or ID. In general, a responsible site uses these only for the check, keeps them out of public view and limits who can see them. Your documents should never appear on your profile, and other members should never be able to open them.</p><p>The key safety rule is to submit documents only through the official verification steps inside the site or app. Never send a copy of your ID to another member, even if they say it is to \"prove you are real\". Anyone who asks for that is a warning sign. You can read more about guarding your details in our guide to <a href=\"/blog/protect-your-privacy-on-dating-apps\">protecting your privacy on dating apps</a>, and you can see how ConnectHub handles data on the <a href=\"/faq\">FAQ page</a>.</p><h2>Who benefits most from getting verified</h2><p>Everyone gains something, but a few groups especially notice the difference.</p><ul>\r\n<li><strong>Newcomers.</strong> If you have just joined, a badge helps people trust you before you have a long chat history.</li>\r\n<li><strong>Cautious members.</strong> Many people hold back from messaging until they see some sign of genuineness, and a badge gives them that.</li>\r\n<li><strong>People who are looking for something serious.</strong> Verification signals that you take the process and the other person seriously.</li>\r\n<li><strong>Members who want to meet people from other places.</strong> When you cannot easily check someone in person, a badge adds extra comfort, as our page for <a href=\"/international\">international members</a> explains.</li>\r\n</ul><p>There is also a benefit for the community as a whole. Every verified member makes it a little harder for fake accounts to stand out, and a place with more honest profiles is a better place to make friends and find love.</p><h2>What a badge cannot promise</h2><p>A badge is helpful, but it is not a guarantee of character. It shows that a check was passed, not that someone will always be honest, kind or available. People can pass a check and still behave badly later, and a person without a badge is not automatically untrustworthy, since they may simply not have done it yet.</p><p>So keep your usual habits. Meet in a public place, tell a friend where you are going, do not send money and take your time before sharing personal details. Our <a href=\"/safety-tips\">safety tips</a> and the guide to <a href=\"/blog/meet-someone-safely\">meeting someone safely</a> are worth a read before any first meeting.</p><h2>Using verification to your advantage</h2><p>Verification works best as one layer in a set of habits. Chat for a while on the platform, ask questions that only a real person could answer comfortably and notice whether their words match their photos.</p><p>If you are verified, mention it lightly, or simply let the badge speak for itself. If you are looking at someone else\'s profile, treat a badge as a reason to feel a bit more at ease, not as a reason to skip your own checks. If someone refuses a video call or avoids any proof that they are real, that tells you something as well. Our guide on <a href=\"/blog/how-to-verify-someone-is-real-before-meeting\">how to verify someone is real before meeting</a> has practical steps you can use.</p><p>Trust builds one small step at a time. A clear photo, an honest bio and a verified badge together make a profile that people feel good about messaging. When you are ready, <a href=\"/browse\">browse people near you</a> and see who is looking for the same thing you are.</p>', 'blog/907ae955ec0265f3.jpg', 'A phone showing a profile with a verified badge next to a smiling photo', 'Why profile verification matters on dating sites', 'Learn why profile verification matters, what a verified badge tells you, how selfie and ID checks work and how to get verified on ConnectHub.', 'profile verification', '[{\"q\":\"What does a verified badge mean on a profile?\",\"a\":\"It means the team has checked that the person behind the profile matches the photos they have shared, usually through a selfie or an ID check. It is a sign of effort and honesty, though you should still use common sense and follow safety tips when meeting someone.\"},{\"q\":\"Does a verified badge mean someone is completely safe?\",\"a\":\"No. A badge shows that a check was passed, not that the person is perfect or certain to behave well. Treat it as one helpful signal, and keep meeting in public places, telling a friend where you are and never sending money to someone you met online.\"},{\"q\":\"How do I get verified on ConnectHub?\",\"a\":\"Open your profile settings and look for the verification option, then follow the steps shown. You will usually be asked to submit a selfie or a document for the team to review. Once it is approved, the badge appears on your profile.\"},{\"q\":\"Is it safe to send my ID or selfie for verification?\",\"a\":\"Documents are used only for the check and are handled with care, and they are not shown on your profile. Always send them only through the official verification steps inside the app or website, and never to someone who messages you privately asking for a copy.\"},{\"q\":\"Why was my verification not approved?\",\"a\":\"Common reasons are a blurry selfie, poor lighting, a face that does not clearly match your profile photos or a document that is hard to read. Retake the photo in good light, face the camera and make sure your profile pictures show you clearly, then try again.\"}]', NULL, NULL, 0, 'published', 3, '2026-10-15 09:12:00', '2026-10-16 08:12:11', '2026-10-16 20:48:19');

-- --------------------------------------------------------

--
-- Table structure for table `boosts`
--

CREATE TABLE `boosts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `payment_id` int(10) UNSIGNED DEFAULT NULL,
  `source` enum('plan','purchase','admin') NOT NULL DEFAULT 'plan',
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `views_gained` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `boosts`
--

INSERT INTO `boosts` (`id`, `user_id`, `payment_id`, `source`, `started_at`, `expires_at`, `views_gained`) VALUES
(1, 9001, 1, 'purchase', '2026-10-16 11:59:35', '2026-10-16 12:29:35', 0);

-- --------------------------------------------------------

--
-- Table structure for table `boost_credits`
--

CREATE TABLE `boost_credits` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `payment_id` int(10) UNSIGNED DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `boost_credits`
--

INSERT INTO `boost_credits` (`id`, `user_id`, `payment_id`, `used_at`, `created_at`) VALUES
(1, 9001, NULL, NULL, '2026-10-16 13:01:42'),
(2, 9001, NULL, NULL, '2026-11-12 09:36:38');

-- --------------------------------------------------------

--
-- Table structure for table `boost_packs`
--

CREATE TABLE `boost_packs` (
  `id` int(10) UNSIGNED NOT NULL,
  `kind` enum('boost','featured') NOT NULL DEFAULT 'boost',
  `name` varchar(60) NOT NULL,
  `minutes` int(10) UNSIGNED NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `badge` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `boost_packs`
--

INSERT INTO `boost_packs` (`id`, `kind`, `name`, `minutes`, `price`, `badge`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'boost', 'Quick boost', 60, 50.00, NULL, 1, 1, '2026-10-16 13:38:36'),
(2, 'boost', 'Evening boost', 180, 100.00, 'Popular', 1, 2, '2026-10-16 13:38:36'),
(3, 'boost', 'All day', 1440, 300.00, 'Best value', 1, 3, '2026-10-16 13:38:36'),
(4, 'featured', 'Featured week', 10080, 299.00, NULL, 1, 1, '2026-10-16 13:38:36'),
(5, 'featured', 'Featured month', 43200, 897.00, 'Best value', 1, 2, '2026-10-16 13:38:36');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied','archived','spam') NOT NULL DEFAULT 'new',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reply_text` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(10) UNSIGNED NOT NULL,
  `user1_id` int(10) UNSIGNED NOT NULL,
  `user2_id` int(10) UNSIGNED NOT NULL,
  `initiated_by` int(10) UNSIGNED NOT NULL,
  `last_message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `user1_unread` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `user2_unread` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `user1_archived` tinyint(1) NOT NULL DEFAULT 0,
  `user2_archived` tinyint(1) NOT NULL DEFAULT 0,
  `user1_muted` tinyint(1) NOT NULL DEFAULT 0,
  `user2_muted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `user1_id`, `user2_id`, `initiated_by`, `last_message_id`, `last_message_at`, `user1_unread`, `user2_unread`, `user1_archived`, `user2_archived`, `user1_muted`, `user2_muted`, `created_at`) VALUES
(1, 9001, 9025, 9001, 15, '2026-11-08 22:34:30', 0, 2, 0, 0, 0, 0, '2026-10-16 12:13:31'),
(2, 2, 9001, 9001, 13, '2026-10-16 22:03:36', 1, 0, 0, 0, 0, 0, '2026-10-16 15:45:36'),
(3, 9001, 9021, 9001, 16, '2026-11-12 20:27:46', 0, 1, 0, 0, 0, 0, '2026-11-12 20:27:31');

-- --------------------------------------------------------

--
-- Table structure for table `conversation_pins`
--

CREATE TABLE `conversation_pins` (
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conversation_pins`
--

INSERT INTO `conversation_pins` (`conversation_id`, `user_id`, `created_at`) VALUES
(1, 9001, '2026-10-16 12:17:09');

-- --------------------------------------------------------

--
-- Table structure for table `daily_picks`
--

CREATE TABLE `daily_picks` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `picked_user_id` int(10) UNSIGNED NOT NULL,
  `pick_date` date NOT NULL,
  `score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_acted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_picks`
--

INSERT INTO `daily_picks` (`user_id`, `picked_user_id`, `pick_date`, `score`, `is_acted`, `created_at`) VALUES
(9001, 9002, '2026-11-12', 28.62, 0, '2026-11-12 09:26:47'),
(9001, 9003, '2026-10-20', 34.16, 0, '2026-10-20 22:06:55'),
(9001, 9003, '2026-10-23', 31.66, 0, '2026-10-23 10:12:13'),
(9001, 9003, '2026-10-31', 31.66, 0, '2026-10-31 19:30:11'),
(9001, 9003, '2026-11-08', 32.13, 0, '2026-11-08 22:30:38'),
(9001, 9003, '2026-11-09', 32.13, 0, '2026-11-09 09:37:34'),
(9001, 9003, '2026-11-12', 31.40, 0, '2026-11-12 09:26:47'),
(9001, 9004, '2026-10-20', 41.52, 0, '2026-10-20 22:06:55'),
(9001, 9004, '2026-10-23', 39.02, 0, '2026-10-23 10:12:13'),
(9001, 9004, '2026-10-31', 39.02, 0, '2026-10-31 19:30:11'),
(9001, 9004, '2026-11-08', 39.02, 0, '2026-11-08 22:30:38'),
(9001, 9004, '2026-11-09', 39.02, 0, '2026-11-09 09:37:34'),
(9001, 9004, '2026-11-12', 39.04, 1, '2026-11-12 09:26:47'),
(9001, 9007, '2026-10-20', 36.52, 0, '2026-10-20 22:06:55'),
(9001, 9007, '2026-10-23', 34.02, 0, '2026-10-23 10:12:13'),
(9001, 9007, '2026-10-31', 34.02, 0, '2026-10-31 19:30:11'),
(9001, 9007, '2026-11-08', 34.02, 0, '2026-11-08 22:30:38'),
(9001, 9007, '2026-11-09', 34.02, 0, '2026-11-09 09:37:34'),
(9001, 9007, '2026-11-12', 34.16, 1, '2026-11-12 09:26:47'),
(9001, 9010, '2026-10-20', 33.83, 0, '2026-10-20 22:06:55'),
(9001, 9010, '2026-10-23', 31.33, 0, '2026-10-23 10:12:13'),
(9001, 9010, '2026-10-31', 31.33, 0, '2026-10-31 19:30:11'),
(9001, 9010, '2026-11-08', 31.33, 0, '2026-11-08 22:30:38'),
(9001, 9010, '2026-11-09', 31.33, 0, '2026-11-09 09:37:34'),
(9001, 9010, '2026-11-12', 31.48, 0, '2026-11-12 09:26:47'),
(9001, 9011, '2026-10-16', 62.06, 1, '2026-10-16 11:56:24'),
(9001, 9012, '2026-10-16', 58.24, 1, '2026-10-16 11:56:24'),
(9001, 9013, '2026-10-20', 36.60, 0, '2026-10-20 22:06:55'),
(9001, 9013, '2026-10-23', 34.10, 0, '2026-10-23 10:12:13'),
(9001, 9013, '2026-10-31', 34.10, 0, '2026-10-31 19:30:11'),
(9001, 9013, '2026-11-08', 34.10, 0, '2026-11-08 22:30:38'),
(9001, 9013, '2026-11-09', 34.10, 0, '2026-11-09 09:37:34'),
(9001, 9013, '2026-11-12', 34.40, 0, '2026-11-12 09:26:47'),
(9001, 9016, '2026-10-16', 59.14, 1, '2026-10-16 11:56:24'),
(9001, 9030, '2026-11-10', 50.25, 1, '2026-11-10 00:21:46'),
(9001, 9031, '2026-11-12', 38.72, 0, '2026-11-12 09:26:47'),
(9001, 9032, '2026-11-12', 32.12, 0, '2026-11-12 09:26:47'),
(9025, 9001, '2026-10-16', 59.55, 1, '2026-10-16 12:12:25'),
(9025, 9005, '2026-10-16', 52.82, 0, '2026-10-16 12:12:25'),
(9025, 9006, '2026-10-16', 50.42, 0, '2026-10-16 12:12:25'),
(9025, 9011, '2026-10-16', 58.31, 1, '2026-10-16 12:12:25'),
(9025, 9015, '2026-10-16', 51.97, 0, '2026-10-16 12:12:25');

-- --------------------------------------------------------

--
-- Table structure for table `daily_usage`
--

CREATE TABLE `daily_usage` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `usage_date` date NOT NULL,
  `swipes_used` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `superlikes_used` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `messages_sent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `rewinds_used` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_usage`
--

INSERT INTO `daily_usage` (`user_id`, `usage_date`, `swipes_used`, `superlikes_used`, `messages_sent`, `rewinds_used`) VALUES
(2, '2026-10-16', 0, 0, 2, 0),
(9001, '2026-10-16', 7, 3, 5, 0),
(9001, '2026-11-08', 0, 0, 2, 0),
(9001, '2026-11-10', 0, 1, 0, 0),
(9001, '2026-11-12', 1, 1, 1, 0),
(9025, '2026-10-16', 2, 0, 4, 0);

-- --------------------------------------------------------

--
-- Table structure for table `discovery_preferences`
--

CREATE TABLE `discovery_preferences` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `age_min` tinyint(3) UNSIGNED NOT NULL DEFAULT 18,
  `age_max` tinyint(3) UNSIGNED NOT NULL DEFAULT 45,
  `distance_km` smallint(5) UNSIGNED NOT NULL DEFAULT 50,
  `show_verified_only` tinyint(1) NOT NULL DEFAULT 0,
  `show_photos_only` tinyint(1) NOT NULL DEFAULT 1,
  `hide_swiped` tinyint(1) NOT NULL DEFAULT 1,
  `preferred_view` varchar(10) DEFAULT NULL,
  `view_set_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `discovery_preferences`
--

INSERT INTO `discovery_preferences` (`user_id`, `age_min`, `age_max`, `distance_km`, `show_verified_only`, `show_photos_only`, `hide_swiped`, `preferred_view`, `view_set_at`, `updated_at`) VALUES
(2, 18, 45, 50, 0, 1, 1, NULL, NULL, '2026-10-16 11:27:53'),
(9001, 26, 36, 25, 0, 1, 1, 'swipe', '2026-11-12 23:01:24', '2026-11-12 23:01:24'),
(9002, 28, 40, 40, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9003, 25, 35, 30, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9004, 29, 40, 60, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9005, 25, 34, 20, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9006, 33, 45, 25, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9007, 28, 40, 50, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9008, 29, 40, 80, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9009, 25, 34, 40, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9010, 24, 33, 60, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9011, 26, 36, 25, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9012, 30, 42, 25, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9013, 23, 32, 60, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9014, 22, 31, 40, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9015, 25, 36, 50, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9016, 24, 35, 30, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9017, 22, 45, 50, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9018, 22, 45, 50, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9019, 22, 45, 50, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9020, 22, 45, 50, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9021, 22, 45, 50, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9022, 22, 45, 50, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9023, 22, 45, 50, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9024, 22, 45, 50, 0, 1, 1, NULL, NULL, '2026-10-16 04:48:22'),
(9025, 18, 45, 50, 0, 1, 1, NULL, NULL, '2026-10-16 12:09:04'),
(9026, 18, 45, 50, 0, 1, 1, NULL, NULL, '2026-10-16 12:49:57'),
(9027, 25, 36, 40, 0, 1, 1, NULL, NULL, '2026-11-08 14:52:46'),
(9028, 27, 40, 35, 0, 1, 1, NULL, NULL, '2026-11-08 14:52:46'),
(9029, 18, 45, 50, 0, 1, 1, NULL, NULL, '2026-11-09 09:33:44');

-- --------------------------------------------------------

--
-- Table structure for table `dm_ledger`
--

CREATE TABLE `dm_ledger` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `delta` int(11) NOT NULL,
  `kind` varchar(20) NOT NULL,
  `ref_id` int(10) UNSIGNED DEFAULT NULL,
  `note` varchar(160) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dm_packs`
--

CREATE TABLE `dm_packs` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `credits` int(10) UNSIGNED NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `badge` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dm_packs`
--

INSERT INTO `dm_packs` (`id`, `name`, `credits`, `price`, `badge`, `is_active`, `sort_order`) VALUES
(1, 'Starter', 3, 50.00, NULL, 1, 1),
(2, 'Popular', 10, 100.00, 'Popular', 1, 2),
(3, 'Best value', 25, 250.00, 'Best value', 1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `dm_unlocks`
--

CREATE TABLE `dm_unlocks` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `target_id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `elite_access`
--

CREATE TABLE `elite_access` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `credits` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `credits_until` datetime DEFAULT NULL,
  `pass_until` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `elite_access`
--

INSERT INTO `elite_access` (`user_id`, `credits`, `credits_until`, `pass_until`, `updated_at`) VALUES
(9001, 0, NULL, '2026-12-12 09:36:35', '2026-11-12 09:36:35');

-- --------------------------------------------------------

--
-- Table structure for table `elite_profiles`
--

CREATE TABLE `elite_profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'pending',
  `headline` varchar(120) NOT NULL DEFAULT '',
  `about` text DEFAULT NULL,
  `industry` varchar(40) NOT NULL DEFAULT '',
  `lifestyle` varchar(20) NOT NULL DEFAULT '',
  `offers` varchar(255) NOT NULL DEFAULT '',
  `seeking` varchar(255) NOT NULL DEFAULT '',
  `criteria` text DEFAULT NULL,
  `min_score` tinyint(3) UNSIGNED NOT NULL DEFAULT 60,
  `filter_on` tinyint(1) NOT NULL DEFAULT 1,
  `hide_in_dating` tinyint(1) NOT NULL DEFAULT 1,
  `share_phone` tinyint(1) NOT NULL DEFAULT 0,
  `photos_after_accept` tinyint(1) NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `paid_until` datetime DEFAULT NULL,
  `views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `admin_note` varchar(255) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `admin_photo_privacy` tinyint(4) NOT NULL DEFAULT -1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `elite_profiles`
--

INSERT INTO `elite_profiles` (`user_id`, `status`, `headline`, `about`, `industry`, `lifestyle`, `offers`, `seeking`, `criteria`, `min_score`, `filter_on`, `hide_in_dating`, `share_phone`, `photos_after_accept`, `is_verified`, `is_featured`, `paid_until`, `views`, `admin_note`, `approved_at`, `created_at`, `updated_at`, `admin_photo_privacy`) VALUES
(9001, 'approved', 'CEO and entrepreneur', 'Loving, caring, open minded, loves travelling and meeting new people', 'tech', 'comfortable', 'travel,networking', 'longterm', '{\"gender\":{\"v\":\"female\",\"w\":\"nice\"},\"age\":{\"min\":18,\"max\":60,\"w\":\"nice\"},\"place\":{\"ids\":[22,47,19],\"w\":\"nice\"},\"verified\":{\"w\":\"off\"},\"photo\":{\"w\":\"must\"}}', 60, 1, 1, 1, 0, 0, 0, NULL, 0, NULL, '2026-11-12 09:33:11', '2026-11-12 09:31:59', '2026-11-12 09:33:11', -1),
(9033, 'approved', 'Property developer who still cooks on Sundays', 'Twenty years in real estate across Nairobi and Nakuru. I travel a fair amount and would rather do it with company than alone. Direct, unhurried, and not interested in games.', 'property', 'affluent', 'travel,dining,events', 'longterm,travel', NULL, 65, 1, 1, 0, 0, 1, 1, '2031-11-09 03:49:59', 2, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59', '2026-11-12 09:36:54', -1),
(9034, 'approved', 'Banker, art collector, very bad golfer', 'I work hard and I am honest about it. Weekends are for galleries, the coast, and whoever can keep up with a conversation about anything but work.', 'finance', 'wealthy', 'dining,networking,marriage', 'marriage,longterm', NULL, 70, 1, 1, 0, 1, 1, 1, '2031-11-09 03:49:59', 2, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59', '2026-11-12 22:53:11', -1),
(9035, 'approved', 'Builds software, mentors founders, reads too much', 'Kisumu born and happy about it. I spend my weeks on product and my weekends on the lake. Looking for someone with their own thing going on.', 'tech', 'comfortable', 'mentorship,networking,travel', 'longterm,friendship', NULL, 60, 1, 1, 0, 0, 1, 0, '2031-11-09 03:49:59', 2, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59', '2026-11-12 20:47:15', -1);

-- --------------------------------------------------------

--
-- Table structure for table `elite_requests`
--

CREATE TABLE `elite_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `from_id` int(10) UNSIGNED NOT NULL,
  `to_id` int(10) UNSIGNED NOT NULL,
  `message` varchar(600) NOT NULL DEFAULT '',
  `status` varchar(10) NOT NULL DEFAULT 'pending',
  `score` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `passed` tinyint(1) NOT NULL DEFAULT 1,
  `detail` varchar(600) DEFAULT NULL,
  `seen_at` datetime DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `elite_requests`
--

INSERT INTO `elite_requests` (`id`, `from_id`, `to_id`, `message`, `status`, `score`, `passed`, `detail`, `seen_at`, `responded_at`, `created_at`) VALUES
(1, 9001, 9033, 'Hello I noticed we both value I noticed we both value the same things', 'pending', 100, 1, '[[\"Has a profile photo\",1,\"nice\"]]', NULL, NULL, '2026-11-12 09:34:36'),
(2, 9001, 9034, 'I am looking for something long-term, and sweet', 'pending', 100, 1, '[[\"Has a profile photo\",1,\"nice\"]]', NULL, NULL, '2026-11-12 09:38:11'),
(3, 9001, 9035, 'I am looking for something long-term, and sweet to me and can treat me well', 'pending', 100, 1, '[[\"Has a profile photo\",1,\"nice\"]]', NULL, NULL, '2026-11-12 09:39:48');

-- --------------------------------------------------------

--
-- Table structure for table `elite_saves`
--

CREATE TABLE `elite_saves` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `owner_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `elite_unlocks`
--

CREATE TABLE `elite_unlocks` (
  `id` int(10) UNSIGNED NOT NULL,
  `viewer_id` int(10) UNSIGNED NOT NULL,
  `owner_id` int(10) UNSIGNED NOT NULL,
  `source` varchar(10) NOT NULL DEFAULT 'single',
  `payment_id` int(10) UNSIGNED DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(10) UNSIGNED NOT NULL,
  `category` varchar(60) NOT NULL DEFAULT 'General',
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `category`, `question`, `answer`, `sort_order`, `is_active`) VALUES
(1, 'Getting started', 'Is it free to join?', 'Yes. Creating a profile, browsing, liking and replying to messages are free. Premium plans add extras such as unlimited likes, seeing who likes you and advanced filters.', 1, 1),
(2, 'Getting started', 'Who can join?', 'Anyone aged 18 or over. We ask for your date of birth when you register and members who are under 18 are removed.', 2, 1),
(3, 'Getting started', 'How do I get more matches?', 'Add at least three clear photos, write a short bio, choose your interests and keep your location up to date. Complete profiles are shown to more people.', 3, 1),
(4, 'Getting started', 'Can I use it on my phone?', 'Yes. Open the site in your phone browser and choose \"Add to home screen\" for an app-like experience. There is nothing to download from an app store.', 4, 1),
(5, 'Privacy and safety', 'Who can see my photos?', 'You choose. Visitors who are not signed in may see blurred photos, depending on the site settings, and you can hide your online status, distance and last active time from your privacy settings.', 5, 1),
(6, 'Privacy and safety', 'Are my phone number and email visible?', 'Not unless you choose to share them. Contact details are hidden by default and only appear to the people the site allows, for example matched or Premium members, and only if you switch sharing on.', 6, 1),
(7, 'Privacy and safety', 'How do I report or block someone?', 'Open their profile or conversation and tap the three-dot menu. Blocking is instant and they will not be able to see or message you. Reports are reviewed by our team.', 7, 1),
(8, 'Privacy and safety', 'How does verification work?', 'Send a short selfie from your profile settings. Our team compares it with your photos and, if it matches, adds a verified badge to your profile.', 8, 1),
(9, 'Premium and payments', 'How do I pay?', 'You can pay with M-Pesa. You will receive a prompt on your phone to enter your PIN. Bank transfer is also accepted and is activated once our team confirms it.', 9, 1),
(10, 'Premium and payments', 'Can I cancel Premium?', 'Yes, any time from Billing. You keep your Premium features until the end of the period you paid for.', 10, 1),
(11, 'Premium and payments', 'What is a boost?', 'A boost puts your profile at the front of the line in your area for a short time so more people see you.', 11, 1),
(12, 'Hosts', 'What is a Host?', 'A Host is an approved, ID-verified member who offers friendly company for dinners, events, city tours and travel. Hosts set their own rates and services.', 12, 1),
(13, 'Hosts', 'How do bookings work?', 'Choose a Host, pick a date and time, and pay the deposit. The Host confirms the booking and you settle the balance with them directly. Cancellation rules are shown before you pay.', 13, 1),
(14, 'Hosts', 'Is this an escort service?', 'No. Hosts provide social company only. Sexual services are not permitted and any Host or client who asks for them is removed. Read the Host guidelines for details.', 14, 1);

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `target_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`user_id`, `target_id`, `created_at`) VALUES
(9001, 9004, '2026-11-12 09:27:06'),
(9001, 9006, '2026-10-16 12:04:51'),
(9001, 9011, '2026-10-16 12:00:43'),
(9001, 9025, '2026-10-16 12:17:16'),
(9001, 9026, '2026-10-16 18:03:47');

-- --------------------------------------------------------

--
-- Table structure for table `gift_types`
--

CREATE TABLE `gift_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `icon` varchar(40) NOT NULL DEFAULT 'gift',
  `emoji` varchar(16) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gift_types`
--

INSERT INTO `gift_types` (`id`, `name`, `icon`, `emoji`, `price`, `is_active`, `sort_order`) VALUES
(1, 'Rose', 'flower-2', '🌹', 50.00, 1, 1),
(2, 'Coffee', 'coffee', '☕', 100.00, 1, 2),
(3, 'Chocolates', 'gift', '🍫', 150.00, 1, 3),
(4, 'Teddy bear', 'gift', '🧸', 250.00, 1, 4),
(5, 'Birthday cake', 'cake', '🎂', 300.00, 1, 5),
(6, 'Champagne', 'wine', '🥂', 500.00, 1, 6),
(7, 'Diamond', 'gem', '💎', 1000.00, 1, 7),
(8, 'Crown', 'crown', '👑', 1500.00, 1, 8);

-- --------------------------------------------------------

--
-- Table structure for table `hero_slides`
--

CREATE TABLE `hero_slides` (
  `id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `mobile_path` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `alt_text` varchar(160) DEFAULT NULL,
  `headline` varchar(160) DEFAULT NULL,
  `subheadline` varchar(300) DEFAULT NULL,
  `cta_text` varchar(40) DEFAULT NULL,
  `cta_url` varchar(255) DEFAULT NULL,
  `overlay_opacity` tinyint(3) UNSIGNED DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `focal_x` tinyint(3) UNSIGNED NOT NULL DEFAULT 50,
  `focal_y` tinyint(3) UNSIGNED NOT NULL DEFAULT 50,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `host_blocked_dates`
--

CREATE TABLE `host_blocked_dates` (
  `id` int(10) UNSIGNED NOT NULL,
  `host_id` int(10) UNSIGNED NOT NULL,
  `blocked_on` date NOT NULL,
  `reason` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `host_blocked_dates`
--

INSERT INTO `host_blocked_dates` (`id`, `host_id`, `blocked_on`, `reason`) VALUES
(1, 9017, '2026-10-25', 'Family event'),
(2, 9017, '2026-11-01', 'Away'),
(3, 9018, '2026-10-25', 'Family event'),
(4, 9018, '2026-11-01', 'Away'),
(5, 9019, '2026-10-25', 'Family event'),
(6, 9019, '2026-11-01', 'Away'),
(7, 9020, '2026-10-25', 'Family event'),
(8, 9020, '2026-11-01', 'Away'),
(9, 9021, '2026-10-25', 'Family event'),
(10, 9021, '2026-11-01', 'Away'),
(11, 9022, '2026-10-25', 'Family event'),
(12, 9022, '2026-11-01', 'Away'),
(13, 9023, '2026-10-25', 'Family event'),
(14, 9023, '2026-11-01', 'Away'),
(15, 9024, '2026-10-25', 'Family event'),
(16, 9024, '2026-11-01', 'Away');

-- --------------------------------------------------------

--
-- Table structure for table `host_bookings`
--

CREATE TABLE `host_bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `host_id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `duration_hours` decimal(4,1) NOT NULL,
  `service_ids` varchar(255) DEFAULT NULL,
  `location_note` varchar(200) DEFAULT NULL,
  `client_note` varchar(500) DEFAULT NULL,
  `host_note` varchar(500) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `deposit_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `commission_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `commission_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `host_earning` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('awaiting_payment','pending','confirmed','completed','cancelled','declined','no_show') NOT NULL DEFAULT 'awaiting_payment',
  `cancelled_by` enum('client','host','admin') DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `host_bookings`
--

INSERT INTO `host_bookings` (`id`, `host_id`, `client_id`, `booking_date`, `start_time`, `duration_hours`, `service_ids`, `location_note`, `client_note`, `host_note`, `total_amount`, `deposit_amount`, `commission_pct`, `commission_amount`, `host_earning`, `payment_id`, `status`, `cancelled_by`, `cancel_reason`, `created_at`, `updated_at`) VALUES
(1, 9017, 9009, '2026-10-10', '19:00:00', 2.0, '1', 'A restaurant in Westlands', NULL, NULL, 5000.00, 2500.00, 15.00, 750.00, 4250.00, NULL, 'completed', NULL, NULL, '2026-10-07 11:20:00', '2026-10-16 04:48:22'),
(2, 9017, 9011, '2026-09-27', '19:30:00', 2.0, '1', 'A restaurant in Kilimani', NULL, NULL, 5000.00, 2500.00, 15.00, 750.00, 4250.00, NULL, 'completed', NULL, NULL, '2026-09-24 11:20:00', '2026-10-16 04:48:22'),
(3, 9017, 9015, '2026-09-13', '18:00:00', 4.0, '4', 'A wedding reception in Karen', NULL, NULL, 9000.00, 4500.00, 15.00, 1350.00, 7650.00, NULL, 'completed', NULL, NULL, '2026-09-10 11:20:00', '2026-10-16 04:48:22'),
(4, 9017, 9014, '2026-08-30', '12:30:00', 1.0, '2', 'A café in Westlands', NULL, NULL, 2500.00, 1250.00, 15.00, 375.00, 2125.00, NULL, 'completed', NULL, NULL, '2026-08-27 11:20:00', '2026-10-16 04:48:22'),
(5, 9018, 9009, '2026-10-07', '10:00:00', 4.0, '3', 'Meeting point agreed in chat', NULL, NULL, 8000.00, 4000.00, 15.00, 1200.00, 6800.00, NULL, 'completed', NULL, NULL, '2026-10-04 11:20:00', '2026-10-16 04:48:22'),
(6, 9018, 9012, '2026-09-22', '09:30:00', 5.0, '3', 'Karen', NULL, NULL, 10000.00, 5000.00, 15.00, 1500.00, 8500.00, NULL, 'completed', NULL, NULL, '2026-09-19 11:20:00', '2026-10-16 04:48:22'),
(7, 9018, 9001, '2026-09-08', '14:00:00', 2.0, '12', 'Karen', NULL, NULL, 4200.00, 2100.00, 15.00, 630.00, 3570.00, NULL, 'completed', NULL, NULL, '2026-09-05 11:20:00', '2026-10-16 04:48:22'),
(8, 9019, 9013, '2026-10-05', '16:00:00', 4.0, '1', 'Old Town', NULL, NULL, 7500.00, 3750.00, 15.00, 1125.00, 6375.00, NULL, 'completed', NULL, NULL, '2026-10-02 11:20:00', '2026-10-16 04:48:22'),
(9, 9019, 9003, '2026-09-18', '10:30:00', 2.0, '3', 'Old Town', NULL, NULL, 4000.00, 2000.00, 15.00, 600.00, 3400.00, NULL, 'completed', NULL, NULL, '2026-09-15 11:20:00', '2026-10-16 04:48:22'),
(10, 9019, 9014, '2026-08-25', '15:00:00', 3.0, '2', 'Nyali', NULL, NULL, 6000.00, 3000.00, 15.00, 900.00, 5100.00, NULL, 'completed', NULL, NULL, '2026-08-22 11:20:00', '2026-10-16 04:48:22'),
(11, 9020, 9011, '2026-10-02', '19:00:00', 3.0, '5', 'A restaurant in Kilimani', NULL, NULL, 10500.00, 5250.00, 15.00, 1575.00, 8925.00, NULL, 'completed', NULL, NULL, '2026-09-29 11:20:00', '2026-10-16 04:48:22'),
(12, 9020, 9009, '2026-09-05', '18:30:00', 4.0, '4', 'Hotel in Upper Hill', NULL, NULL, 14000.00, 7000.00, 15.00, 2100.00, 11900.00, NULL, 'completed', NULL, NULL, '2026-09-02 11:20:00', '2026-10-16 04:48:22'),
(13, 9020, 9012, '2026-08-14', '19:30:00', 3.0, '5', 'A restaurant in Westlands', NULL, NULL, 10500.00, 5250.00, 15.00, 1575.00, 8925.00, NULL, 'completed', NULL, NULL, '2026-08-11 11:20:00', '2026-10-16 04:48:22'),
(14, 9021, 9010, '2026-10-09', '06:00:00', 1.5, '9', 'Karura Forest', NULL, NULL, 2700.00, 1350.00, 15.00, 405.00, 2295.00, NULL, 'completed', NULL, NULL, '2026-10-06 11:20:00', '2026-10-16 04:48:22'),
(15, 9021, 9001, '2026-09-25', '07:00:00', 4.0, '8', 'Ngong Hills', NULL, NULL, 6500.00, 3250.00, 15.00, 975.00, 5525.00, NULL, 'completed', NULL, NULL, '2026-09-22 11:20:00', '2026-10-16 04:48:22'),
(16, 9021, 9008, '2026-09-01', '06:30:00', 4.0, '8', 'Ngong Hills', NULL, NULL, 6500.00, 3250.00, 15.00, 975.00, 5525.00, NULL, 'completed', NULL, NULL, '2026-08-29 11:20:00', '2026-10-16 04:48:22'),
(17, 9022, 9015, '2026-10-03', '09:00:00', 4.0, '7', 'Diani Beach', NULL, NULL, 6500.00, 3250.00, 15.00, 975.00, 5525.00, NULL, 'completed', NULL, NULL, '2026-09-30 11:20:00', '2026-10-16 04:48:22'),
(18, 9022, 9016, '2026-09-17', '10:00:00', 5.0, '7', 'Diani Beach', NULL, NULL, 8200.00, 4100.00, 15.00, 1230.00, 6970.00, NULL, 'completed', NULL, NULL, '2026-09-14 11:20:00', '2026-10-16 04:48:22'),
(19, 9022, 9013, '2026-08-22', '09:30:00', 4.0, '7', 'Diani Beach', NULL, NULL, 6500.00, 3250.00, 15.00, 975.00, 5525.00, NULL, 'completed', NULL, NULL, '2026-08-19 11:20:00', '2026-10-16 04:48:22'),
(20, 9023, 9006, '2026-10-06', '15:00:00', 6.0, '4', 'A wedding in Karen', NULL, NULL, 13500.00, 6750.00, 15.00, 2025.00, 11475.00, NULL, 'completed', NULL, NULL, '2026-10-03 11:20:00', '2026-10-16 04:48:22'),
(21, 9023, 9002, '2026-09-10', '19:00:00', 2.0, '1', 'A restaurant in Kilimani', NULL, NULL, 5600.00, 2800.00, 15.00, 840.00, 4760.00, NULL, 'completed', NULL, NULL, '2026-09-07 11:20:00', '2026-10-16 04:48:22'),
(22, 9023, 9004, '2026-08-19', '18:00:00', 3.0, '4', 'Hotel in Westlands', NULL, NULL, 8500.00, 4250.00, 15.00, 1275.00, 7225.00, NULL, 'completed', NULL, NULL, '2026-08-16 11:20:00', '2026-10-16 04:48:22'),
(23, 9024, 9010, '2026-09-29', '08:30:00', 5.0, '8', 'Hell’s Gate National Park', NULL, NULL, 9500.00, 4750.00, 15.00, 1425.00, 8075.00, NULL, 'completed', NULL, NULL, '2026-09-26 11:20:00', '2026-10-16 04:48:22'),
(24, 9024, 9016, '2026-09-07', '09:00:00', 4.0, '3', 'Lake Naivasha', NULL, NULL, 8500.00, 4250.00, 15.00, 1275.00, 7225.00, NULL, 'completed', NULL, NULL, '2026-09-04 11:20:00', '2026-10-16 04:48:22'),
(25, 9024, 9013, '2026-08-11', '08:00:00', 5.0, '8', 'Hell’s Gate National Park', NULL, NULL, 9500.00, 4750.00, 15.00, 1425.00, 8075.00, NULL, 'completed', NULL, NULL, '2026-08-08 11:20:00', '2026-10-16 04:48:22'),
(26, 2, 9001, '2026-10-17', '13:00:00', 2.0, NULL, 'Java (Thika)', NULL, NULL, 4000.00, 2000.00, 15.00, 300.00, 1700.00, 7, 'no_show', NULL, NULL, '2026-10-16 15:42:38', '2026-11-09 09:35:55');

-- --------------------------------------------------------

--
-- Table structure for table `host_booking_events`
--

CREATE TABLE `host_booking_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `actor_id` int(10) UNSIGNED DEFAULT NULL,
  `actor_role` varchar(10) DEFAULT NULL,
  `event` varchar(30) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `host_booking_events`
--

INSERT INTO `host_booking_events` (`id`, `booking_id`, `actor_id`, `actor_role`, `event`, `note`, `created_at`) VALUES
(1, 1, 9009, 'user', 'created', NULL, '2026-10-07 11:20:00'),
(2, 1, NULL, 'system', 'paid', NULL, '2026-10-07 11:29:00'),
(3, 1, 9017, 'host', 'accepted', NULL, '2026-10-07 14:05:00'),
(4, 1, NULL, 'system', 'completed', NULL, '2026-10-10 21:00:00'),
(5, 2, 9011, 'user', 'created', NULL, '2026-09-24 11:20:00'),
(6, 2, NULL, 'system', 'paid', NULL, '2026-09-24 11:29:00'),
(7, 2, 9017, 'host', 'accepted', NULL, '2026-09-24 14:05:00'),
(8, 2, NULL, 'system', 'completed', NULL, '2026-09-27 21:30:00'),
(9, 3, 9015, 'user', 'created', NULL, '2026-09-10 11:20:00'),
(10, 3, NULL, 'system', 'paid', NULL, '2026-09-10 11:29:00'),
(11, 3, 9017, 'host', 'accepted', NULL, '2026-09-10 14:05:00'),
(12, 3, NULL, 'system', 'completed', NULL, '2026-09-13 22:00:00'),
(13, 4, 9014, 'user', 'created', NULL, '2026-08-27 11:20:00'),
(14, 4, NULL, 'system', 'paid', NULL, '2026-08-27 11:29:00'),
(15, 4, 9017, 'host', 'accepted', NULL, '2026-08-27 14:05:00'),
(16, 4, NULL, 'system', 'completed', NULL, '2026-08-30 13:30:00'),
(17, 5, 9009, 'user', 'created', NULL, '2026-10-04 11:20:00'),
(18, 5, NULL, 'system', 'paid', NULL, '2026-10-04 11:29:00'),
(19, 5, 9018, 'host', 'accepted', NULL, '2026-10-04 14:05:00'),
(20, 5, NULL, 'system', 'completed', NULL, '2026-10-07 14:00:00'),
(21, 6, 9012, 'user', 'created', NULL, '2026-09-19 11:20:00'),
(22, 6, NULL, 'system', 'paid', NULL, '2026-09-19 11:29:00'),
(23, 6, 9018, 'host', 'accepted', NULL, '2026-09-19 14:05:00'),
(24, 6, NULL, 'system', 'completed', NULL, '2026-09-22 14:30:00'),
(25, 7, 9001, 'user', 'created', NULL, '2026-09-05 11:20:00'),
(26, 7, NULL, 'system', 'paid', NULL, '2026-09-05 11:29:00'),
(27, 7, 9018, 'host', 'accepted', NULL, '2026-09-05 14:05:00'),
(28, 7, NULL, 'system', 'completed', NULL, '2026-09-08 16:00:00'),
(29, 8, 9013, 'user', 'created', NULL, '2026-10-02 11:20:00'),
(30, 8, NULL, 'system', 'paid', NULL, '2026-10-02 11:29:00'),
(31, 8, 9019, 'host', 'accepted', NULL, '2026-10-02 14:05:00'),
(32, 8, NULL, 'system', 'completed', NULL, '2026-10-05 20:00:00'),
(33, 9, 9003, 'user', 'created', NULL, '2026-09-15 11:20:00'),
(34, 9, NULL, 'system', 'paid', NULL, '2026-09-15 11:29:00'),
(35, 9, 9019, 'host', 'accepted', NULL, '2026-09-15 14:05:00'),
(36, 9, NULL, 'system', 'completed', NULL, '2026-09-18 12:30:00'),
(37, 10, 9014, 'user', 'created', NULL, '2026-08-22 11:20:00'),
(38, 10, NULL, 'system', 'paid', NULL, '2026-08-22 11:29:00'),
(39, 10, 9019, 'host', 'accepted', NULL, '2026-08-22 14:05:00'),
(40, 10, NULL, 'system', 'completed', NULL, '2026-08-25 18:00:00'),
(41, 11, 9011, 'user', 'created', NULL, '2026-09-29 11:20:00'),
(42, 11, NULL, 'system', 'paid', NULL, '2026-09-29 11:29:00'),
(43, 11, 9020, 'host', 'accepted', NULL, '2026-09-29 14:05:00'),
(44, 11, NULL, 'system', 'completed', NULL, '2026-10-02 22:00:00'),
(45, 12, 9009, 'user', 'created', NULL, '2026-09-02 11:20:00'),
(46, 12, NULL, 'system', 'paid', NULL, '2026-09-02 11:29:00'),
(47, 12, 9020, 'host', 'accepted', NULL, '2026-09-02 14:05:00'),
(48, 12, NULL, 'system', 'completed', NULL, '2026-09-05 22:30:00'),
(49, 13, 9012, 'user', 'created', NULL, '2026-08-11 11:20:00'),
(50, 13, NULL, 'system', 'paid', NULL, '2026-08-11 11:29:00'),
(51, 13, 9020, 'host', 'accepted', NULL, '2026-08-11 14:05:00'),
(52, 13, NULL, 'system', 'completed', NULL, '2026-08-14 22:30:00'),
(53, 14, 9010, 'user', 'created', NULL, '2026-10-06 11:20:00'),
(54, 14, NULL, 'system', 'paid', NULL, '2026-10-06 11:29:00'),
(55, 14, 9021, 'host', 'accepted', NULL, '2026-10-06 14:05:00'),
(56, 14, NULL, 'system', 'completed', NULL, '2026-10-09 07:30:00'),
(57, 15, 9001, 'user', 'created', NULL, '2026-09-22 11:20:00'),
(58, 15, NULL, 'system', 'paid', NULL, '2026-09-22 11:29:00'),
(59, 15, 9021, 'host', 'accepted', NULL, '2026-09-22 14:05:00'),
(60, 15, NULL, 'system', 'completed', NULL, '2026-09-25 11:00:00'),
(61, 16, 9008, 'user', 'created', NULL, '2026-08-29 11:20:00'),
(62, 16, NULL, 'system', 'paid', NULL, '2026-08-29 11:29:00'),
(63, 16, 9021, 'host', 'accepted', NULL, '2026-08-29 14:05:00'),
(64, 16, NULL, 'system', 'completed', NULL, '2026-09-01 10:30:00'),
(65, 17, 9015, 'user', 'created', NULL, '2026-09-30 11:20:00'),
(66, 17, NULL, 'system', 'paid', NULL, '2026-09-30 11:29:00'),
(67, 17, 9022, 'host', 'accepted', NULL, '2026-09-30 14:05:00'),
(68, 17, NULL, 'system', 'completed', NULL, '2026-10-03 13:00:00'),
(69, 18, 9016, 'user', 'created', NULL, '2026-09-14 11:20:00'),
(70, 18, NULL, 'system', 'paid', NULL, '2026-09-14 11:29:00'),
(71, 18, 9022, 'host', 'accepted', NULL, '2026-09-14 14:05:00'),
(72, 18, NULL, 'system', 'completed', NULL, '2026-09-17 15:00:00'),
(73, 19, 9013, 'user', 'created', NULL, '2026-08-19 11:20:00'),
(74, 19, NULL, 'system', 'paid', NULL, '2026-08-19 11:29:00'),
(75, 19, 9022, 'host', 'accepted', NULL, '2026-08-19 14:05:00'),
(76, 19, NULL, 'system', 'completed', NULL, '2026-08-22 13:30:00'),
(77, 20, 9006, 'user', 'created', NULL, '2026-10-03 11:20:00'),
(78, 20, NULL, 'system', 'paid', NULL, '2026-10-03 11:29:00'),
(79, 20, 9023, 'host', 'accepted', NULL, '2026-10-03 14:05:00'),
(80, 20, NULL, 'system', 'completed', NULL, '2026-10-06 21:00:00'),
(81, 21, 9002, 'user', 'created', NULL, '2026-09-07 11:20:00'),
(82, 21, NULL, 'system', 'paid', NULL, '2026-09-07 11:29:00'),
(83, 21, 9023, 'host', 'accepted', NULL, '2026-09-07 14:05:00'),
(84, 21, NULL, 'system', 'completed', NULL, '2026-09-10 21:00:00'),
(85, 22, 9004, 'user', 'created', NULL, '2026-08-16 11:20:00'),
(86, 22, NULL, 'system', 'paid', NULL, '2026-08-16 11:29:00'),
(87, 22, 9023, 'host', 'accepted', NULL, '2026-08-16 14:05:00'),
(88, 22, NULL, 'system', 'completed', NULL, '2026-08-19 21:00:00'),
(89, 23, 9010, 'user', 'created', NULL, '2026-09-26 11:20:00'),
(90, 23, NULL, 'system', 'paid', NULL, '2026-09-26 11:29:00'),
(91, 23, 9024, 'host', 'accepted', NULL, '2026-09-26 14:05:00'),
(92, 23, NULL, 'system', 'completed', NULL, '2026-09-29 13:30:00'),
(93, 24, 9016, 'user', 'created', NULL, '2026-09-04 11:20:00'),
(94, 24, NULL, 'system', 'paid', NULL, '2026-09-04 11:29:00'),
(95, 24, 9024, 'host', 'accepted', NULL, '2026-09-04 14:05:00'),
(96, 24, NULL, 'system', 'completed', NULL, '2026-09-07 13:00:00'),
(97, 25, 9013, 'user', 'created', NULL, '2026-08-08 11:20:00'),
(98, 25, NULL, 'system', 'paid', NULL, '2026-08-08 11:29:00'),
(99, 25, 9024, 'host', 'accepted', NULL, '2026-08-08 14:05:00'),
(100, 25, NULL, 'system', 'completed', NULL, '2026-08-11 13:00:00'),
(101, 26, 9001, 'user', 'created', NULL, '2026-10-16 15:42:38'),
(102, 26, 9001, 'user', 'paid', 'Deposit KSh 2,000 received', '2026-10-16 15:43:02'),
(103, 26, 2, 'host', 'accepted', NULL, '2026-10-16 15:44:30'),
(104, 26, 2, 'host', 'no_show', NULL, '2026-11-09 09:35:55');

-- --------------------------------------------------------

--
-- Table structure for table `host_booking_ext`
--

CREATE TABLE `host_booking_ext` (
  `booking_id` int(10) UNSIGNED NOT NULL,
  `offering_id` int(10) UNSIGNED DEFAULT NULL,
  `offering_name` varchar(80) DEFAULT NULL,
  `area_id` int(10) UNSIGNED DEFAULT NULL,
  `travel_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `base_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `accept_by` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `reminder_sent_at` datetime DEFAULT NULL,
  `client_confirmed_at` datetime DEFAULT NULL,
  `refund_pct` decimal(5,2) DEFAULT NULL,
  `refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `refund_status` varchar(12) NOT NULL DEFAULT 'none',
  `settled_amount` decimal(10,2) DEFAULT NULL,
  `settled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `host_booking_ext`
--

INSERT INTO `host_booking_ext` (`booking_id`, `offering_id`, `offering_name`, `area_id`, `travel_fee`, `base_amount`, `accept_by`, `paid_at`, `confirmed_at`, `completed_at`, `closed_at`, `reminder_sent_at`, `client_confirmed_at`, `refund_pct`, `refund_amount`, `refund_status`, `settled_amount`, `settled_at`) VALUES
(1, 1, 'Dinner company', NULL, 0.00, 5000.00, NULL, '2026-10-07 11:29:00', '2026-10-07 14:05:00', '2026-10-10 21:00:00', '2026-10-10 21:00:00', NULL, NULL, NULL, 0.00, 'none', 1750.00, '2026-10-11 09:00:00'),
(2, 1, 'Dinner company', NULL, 0.00, 5000.00, NULL, '2026-09-24 11:29:00', '2026-09-24 14:05:00', '2026-09-27 21:30:00', '2026-09-27 21:30:00', NULL, NULL, NULL, 0.00, 'none', 1750.00, '2026-09-28 09:00:00'),
(3, 3, 'Event plus-one', NULL, 0.00, 9000.00, NULL, '2026-09-10 11:29:00', '2026-09-10 14:05:00', '2026-09-13 22:00:00', '2026-09-13 22:00:00', NULL, NULL, NULL, 0.00, 'none', 3150.00, '2026-09-14 09:00:00'),
(4, 2, 'Coffee and conversation', NULL, 0.00, 2500.00, NULL, '2026-08-27 11:29:00', '2026-08-27 14:05:00', '2026-08-30 13:30:00', '2026-08-30 13:30:00', NULL, NULL, NULL, 0.00, 'none', 875.00, '2026-08-31 09:00:00'),
(5, 4, 'City highlights', NULL, 0.00, 8000.00, NULL, '2026-10-04 11:29:00', '2026-10-04 14:05:00', '2026-10-07 14:00:00', '2026-10-07 14:00:00', NULL, NULL, NULL, 0.00, 'none', 2800.00, '2026-10-08 09:00:00'),
(6, 5, 'Karen and Langata afternoon', NULL, 0.00, 10000.00, NULL, '2026-09-19 11:29:00', '2026-09-19 14:05:00', '2026-09-22 14:30:00', '2026-09-22 14:30:00', NULL, NULL, NULL, 0.00, 'none', 3500.00, '2026-09-23 09:00:00'),
(7, 6, 'French conversation walk', NULL, 0.00, 4200.00, NULL, '2026-09-05 11:29:00', '2026-09-05 14:05:00', '2026-09-08 16:00:00', '2026-09-08 16:00:00', NULL, NULL, NULL, 0.00, 'none', 1470.00, '2026-09-09 09:00:00'),
(8, 9, 'Sunset and dinner', NULL, 0.00, 7500.00, NULL, '2026-10-02 11:29:00', '2026-10-02 14:05:00', '2026-10-05 20:00:00', '2026-10-05 20:00:00', NULL, NULL, NULL, 0.00, 'none', 2625.00, '2026-10-06 09:00:00'),
(9, 7, 'Old Town walk', NULL, 0.00, 4000.00, NULL, '2026-09-15 11:29:00', '2026-09-15 14:05:00', '2026-09-18 12:30:00', '2026-09-18 12:30:00', NULL, NULL, NULL, 0.00, 'none', 1400.00, '2026-09-19 09:00:00'),
(10, 8, 'Swahili food afternoon', NULL, 0.00, 6000.00, NULL, '2026-08-22 11:29:00', '2026-08-22 14:05:00', '2026-08-25 18:00:00', '2026-08-25 18:00:00', NULL, NULL, NULL, 0.00, 'none', 2100.00, '2026-08-26 09:00:00'),
(11, 10, 'Business dinner', NULL, 0.00, 10500.00, NULL, '2026-09-29 11:29:00', '2026-09-29 14:05:00', '2026-10-02 22:00:00', '2026-10-02 22:00:00', NULL, NULL, NULL, 0.00, 'none', 3675.00, '2026-10-03 09:00:00'),
(12, 11, 'Corporate event plus-one', NULL, 0.00, 14000.00, NULL, '2026-09-02 11:29:00', '2026-09-02 14:05:00', '2026-09-05 22:30:00', '2026-09-05 22:30:00', NULL, NULL, NULL, 0.00, 'none', 4900.00, '2026-09-06 09:00:00'),
(13, 10, 'Business dinner', NULL, 0.00, 10500.00, NULL, '2026-08-11 11:29:00', '2026-08-11 14:05:00', '2026-08-14 22:30:00', '2026-08-14 22:30:00', NULL, NULL, NULL, 0.00, 'none', 3675.00, '2026-08-15 09:00:00'),
(14, 13, 'Sunrise run', NULL, 0.00, 2700.00, NULL, '2026-10-06 11:29:00', '2026-10-06 14:05:00', '2026-10-09 07:30:00', '2026-10-09 07:30:00', NULL, NULL, NULL, 0.00, 'none', 945.00, '2026-10-10 09:00:00'),
(15, 12, 'Ngong Hills hike', NULL, 0.00, 6500.00, NULL, '2026-09-22 11:29:00', '2026-09-22 14:05:00', '2026-09-25 11:00:00', '2026-09-25 11:00:00', NULL, NULL, NULL, 0.00, 'none', 2275.00, '2026-09-26 09:00:00'),
(16, 12, 'Ngong Hills hike', NULL, 0.00, 6500.00, NULL, '2026-08-29 11:29:00', '2026-08-29 14:05:00', '2026-09-01 10:30:00', '2026-09-01 10:30:00', NULL, NULL, NULL, 0.00, 'none', 2275.00, '2026-09-02 09:00:00'),
(17, 15, 'Beach morning', NULL, 0.00, 6500.00, NULL, '2026-09-30 11:29:00', '2026-09-30 14:05:00', '2026-10-03 13:00:00', '2026-10-03 13:00:00', NULL, NULL, NULL, 0.00, 'none', 2275.00, '2026-10-04 09:00:00'),
(18, 16, 'Snorkel and lunch', NULL, 0.00, 8200.00, NULL, '2026-09-14 11:29:00', '2026-09-14 14:05:00', '2026-09-17 15:00:00', '2026-09-17 15:00:00', NULL, NULL, NULL, 0.00, 'none', 2870.00, '2026-09-18 09:00:00'),
(19, 15, 'Beach morning', NULL, 0.00, 6500.00, NULL, '2026-08-19 11:29:00', '2026-08-19 14:05:00', '2026-08-22 13:30:00', '2026-08-22 13:30:00', NULL, NULL, NULL, 0.00, 'none', 2275.00, '2026-08-23 09:00:00'),
(20, 18, 'Wedding plus-one', NULL, 0.00, 13500.00, NULL, '2026-10-03 11:29:00', '2026-10-03 14:05:00', '2026-10-06 21:00:00', '2026-10-06 21:00:00', NULL, NULL, NULL, 0.00, 'none', 4725.00, '2026-10-07 09:00:00'),
(21, 19, 'Dinner company', NULL, 0.00, 5600.00, NULL, '2026-09-07 11:29:00', '2026-09-07 14:05:00', '2026-09-10 21:00:00', '2026-09-10 21:00:00', NULL, NULL, NULL, 0.00, 'none', 1960.00, '2026-09-11 09:00:00'),
(22, 20, 'Work event plus-one', NULL, 0.00, 8500.00, NULL, '2026-08-16 11:29:00', '2026-08-16 14:05:00', '2026-08-19 21:00:00', '2026-08-19 21:00:00', NULL, NULL, NULL, 0.00, 'none', 2975.00, '2026-08-20 09:00:00'),
(23, 21, 'Hell’s Gate by bike', NULL, 0.00, 9500.00, NULL, '2026-09-26 11:29:00', '2026-09-26 14:05:00', '2026-09-29 13:30:00', '2026-09-29 13:30:00', NULL, NULL, NULL, 0.00, 'none', 3325.00, '2026-09-30 09:00:00'),
(24, 22, 'Lake Naivasha boat day', NULL, 0.00, 8500.00, NULL, '2026-09-04 11:29:00', '2026-09-04 14:05:00', '2026-09-07 13:00:00', '2026-09-07 13:00:00', NULL, NULL, NULL, 0.00, 'none', 2975.00, '2026-09-08 09:00:00'),
(25, 21, 'Hell’s Gate by bike', NULL, 0.00, 9500.00, NULL, '2026-08-08 11:29:00', '2026-08-08 14:05:00', '2026-08-11 13:00:00', '2026-08-11 13:00:00', NULL, NULL, NULL, 0.00, 'none', 3325.00, '2026-08-12 09:00:00'),
(26, NULL, 'Custom time', 111, 0.00, 4000.00, '2026-10-17 03:43:02', '2026-10-16 15:43:02', '2026-10-16 15:44:30', NULL, '2026-11-09 09:35:55', '2026-10-16 21:20:20', NULL, 0.00, 0.00, 'none', 1700.00, '2026-11-09 09:35:55');

-- --------------------------------------------------------

--
-- Table structure for table `host_categories`
--

CREATE TABLE `host_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `icon` varchar(40) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `seo_title` varchar(120) DEFAULT NULL,
  `seo_description` varchar(300) DEFAULT NULL,
  `seo_keywords` varchar(500) DEFAULT NULL,
  `seo_intro` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `host_categories`
--

INSERT INTO `host_categories` (`id`, `name`, `icon`, `sort_order`, `is_active`, `seo_title`, `seo_description`, `seo_keywords`, `seo_intro`) VALUES
(1, 'Dinner and social companion', 'utensils', 1, 1, NULL, NULL, NULL, NULL),
(2, 'Travel companion', 'plane', 2, 1, NULL, NULL, NULL, NULL),
(3, 'Local guide', 'map-pin', 3, 1, NULL, NULL, NULL, NULL),
(4, 'Event companion', 'party-popper', 4, 1, NULL, NULL, NULL, NULL),
(5, 'Activity partner', 'footprints', 5, 1, NULL, NULL, NULL, NULL),
(6, 'Conversation partner', 'message-circle', 6, 1, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `host_connection_profiles`
--

CREATE TABLE `host_connection_profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `approval_status` varchar(12) NOT NULL DEFAULT 'draft',
  `title` varchar(90) NOT NULL DEFAULT '',
  `about_text` varchar(800) NOT NULL DEFAULT '',
  `city` varchar(80) NOT NULL DEFAULT '',
  `area` varchar(120) NOT NULL DEFAULT '',
  `contact_phone` varchar(30) DEFAULT NULL,
  `contact_whatsapp` varchar(30) DEFAULT NULL,
  `contact_method` varchar(20) NOT NULL DEFAULT 'platform',
  `service_codes` varchar(500) NOT NULL DEFAULT '',
  `photo_ids` varchar(500) NOT NULL DEFAULT '',
  `admin_note` varchar(255) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `host_connection_services`
--

CREATE TABLE `host_connection_services` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(12) NOT NULL,
  `label` varchar(70) NOT NULL,
  `description` varchar(180) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `host_connection_services`
--

INSERT INTO `host_connection_services` (`id`, `code`, `label`, `description`, `is_active`, `sort_order`) VALUES
(1, 'SOC', 'Social meet-up', 'Meet for a conversation in a public place', 1, 1),
(2, 'DIN', 'Dinner company', 'Company for a meal or public event', 1, 2),
(3, 'EVT', 'Event companion', 'Company at public events and celebrations', 1, 3),
(4, 'TRV', 'Travel companion', 'Company for planned trips and city visits', 1, 4),
(5, 'COF', 'Coffee chat', 'A casual coffee and conversation', 1, 5),
(6, 'VID', 'Video chat', 'A friendly online conversation', 1, 6),
(7, 'LAN', 'Language practice', 'Practice a language together', 1, 7),
(8, 'WALK', 'Walk partner', 'A walk in a public, well-lit place', 1, 8);

-- --------------------------------------------------------

--
-- Table structure for table `host_offerings`
--

CREATE TABLE `host_offerings` (
  `id` int(10) UNSIGNED NOT NULL,
  `host_id` int(10) UNSIGNED NOT NULL,
  `service_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `duration_hours` decimal(4,1) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `host_offerings`
--

INSERT INTO `host_offerings` (`id`, `host_id`, `service_id`, `name`, `description`, `duration_hours`, `price`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 9017, 1, 'Dinner company', 'A relaxed evening at a restaurant of your choice.', 2.0, 5000.00, 1, 0, '2026-10-16 04:48:22'),
(2, 9017, 2, 'Coffee and conversation', 'A daytime meet-up at a café.', 1.0, 2500.00, 1, 1, '2026-10-16 04:48:22'),
(3, 9017, 4, 'Event plus-one', 'Company for a wedding, launch or party.', 4.0, 9000.00, 1, 2, '2026-10-16 04:48:22'),
(4, 9018, 3, 'City highlights', 'A half-day around Nairobi’s best-known places.', 4.0, 8000.00, 1, 0, '2026-10-16 04:48:22'),
(5, 9018, 3, 'Karen and Langata afternoon', 'Giraffe Centre, a nature walk and a long lunch.', 5.0, 10000.00, 1, 1, '2026-10-16 04:48:22'),
(6, 9018, 12, 'French conversation walk', 'Practise French while we explore.', 2.0, 4200.00, 1, 2, '2026-10-16 04:48:22'),
(7, 9019, 3, 'Old Town walk', 'Two hours through the lanes, doors and stories of Old Town.', 2.0, 4000.00, 1, 0, '2026-10-16 04:48:22'),
(8, 9019, 2, 'Swahili food afternoon', 'Street snacks, a proper lunch and kahawa.', 3.0, 6000.00, 1, 1, '2026-10-16 04:48:22'),
(9, 9019, 1, 'Sunset and dinner', 'Fort Jesus at golden hour, then dinner.', 4.0, 7500.00, 1, 2, '2026-10-16 04:48:22'),
(10, 9020, 5, 'Business dinner', 'Polished company for a client or partner dinner.', 3.0, 10500.00, 1, 0, '2026-10-16 04:48:22'),
(11, 9020, 4, 'Corporate event plus-one', 'Company for a gala, awards night or conference dinner.', 4.0, 14000.00, 1, 1, '2026-10-16 04:48:22'),
(12, 9021, 8, 'Ngong Hills hike', 'A guided half-day hike with snacks.', 4.0, 6500.00, 1, 0, '2026-10-16 04:48:22'),
(13, 9021, 9, 'Sunrise run', 'An easy-paced run with a coach.', 1.5, 2700.00, 1, 1, '2026-10-16 04:48:22'),
(14, 9021, 8, 'Full-day trail', 'A longer day on the trails near Kiserian.', 8.0, 11000.00, 1, 2, '2026-10-16 04:48:22'),
(15, 9022, 7, 'Beach morning', 'A relaxed morning on the quieter stretch of Diani.', 4.0, 6500.00, 1, 0, '2026-10-16 04:48:22'),
(16, 9022, 7, 'Snorkel and lunch', 'A short boat trip, snorkelling and seafood lunch.', 5.0, 8200.00, 1, 1, '2026-10-16 04:48:22'),
(17, 9022, 12, 'German practice walk', 'Practise German while we walk the beach.', 1.5, 2700.00, 1, 2, '2026-10-16 04:48:22'),
(18, 9023, 4, 'Wedding plus-one', 'Company for the ceremony and reception.', 6.0, 13500.00, 1, 0, '2026-10-16 04:48:22'),
(19, 9023, 1, 'Dinner company', 'A relaxed dinner at a restaurant of your choice.', 2.0, 5600.00, 1, 1, '2026-10-16 04:48:22'),
(20, 9023, 4, 'Work event plus-one', 'Company for a corporate event or launch.', 3.0, 8500.00, 1, 2, '2026-10-16 04:48:22'),
(21, 9024, 8, 'Hell’s Gate by bike', 'A guided cycle through the gorge with a picnic.', 5.0, 9500.00, 1, 0, '2026-10-16 04:48:22'),
(22, 9024, 3, 'Lake Naivasha boat day', 'Hippos, birds and Crescent Island.', 4.0, 8500.00, 1, 1, '2026-10-16 04:48:22'),
(23, 9024, 6, 'Weekend getaway guide', 'Two days of travel companionship around the lake.', 16.0, 26000.00, 1, 2, '2026-10-16 04:48:22');

-- --------------------------------------------------------

--
-- Table structure for table `host_payouts`
--

CREATE TABLE `host_payouts` (
  `id` int(10) UNSIGNED NOT NULL,
  `host_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(20) NOT NULL DEFAULT 'mpesa',
  `destination` varchar(80) NOT NULL,
  `status` enum('requested','paid','rejected') NOT NULL DEFAULT 'requested',
  `admin_note` varchar(255) DEFAULT NULL,
  `payment_reference` varchar(60) DEFAULT NULL,
  `processed_by` int(10) UNSIGNED DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `host_profiles`
--

CREATE TABLE `host_profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `display_name` varchar(100) NOT NULL,
  `tagline` varchar(120) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `contact_intro` varchar(220) DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `half_day_rate` decimal(10,2) DEFAULT NULL,
  `full_day_rate` decimal(10,2) DEFAULT NULL,
  `min_hours` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `travel_available` tinyint(1) NOT NULL DEFAULT 0,
  `travel_fee` decimal(10,2) DEFAULT NULL,
  `service_ids` varchar(255) DEFAULT NULL,
  `service_prices` varchar(1500) DEFAULT NULL,
  `service_area_ids` varchar(255) DEFAULT NULL,
  `weekly_availability` text DEFAULT NULL,
  `banner_path` varchar(255) DEFAULT NULL,
  `approval_status` enum('draft','pending','approved','rejected','suspended') NOT NULL DEFAULT 'draft',
  `approval_note` varchar(255) DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `id_verified` tinyint(1) NOT NULL DEFAULT 0,
  `photo_verified` tinyint(1) NOT NULL DEFAULT 0,
  `total_bookings` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_earned` decimal(12,2) NOT NULL DEFAULT 0.00,
  `rating_avg` decimal(3,2) NOT NULL DEFAULT 0.00,
  `review_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `profile_views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `submitted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `host_profiles`
--

INSERT INTO `host_profiles` (`user_id`, `category_id`, `display_name`, `tagline`, `about`, `contact_intro`, `hourly_rate`, `half_day_rate`, `full_day_rate`, `min_hours`, `travel_available`, `travel_fee`, `service_ids`, `service_prices`, `service_area_ids`, `weekly_availability`, `banner_path`, `approval_status`, `approval_note`, `approved_by`, `approved_at`, `is_featured`, `id_verified`, `photo_verified`, `total_bookings`, `total_earned`, `rating_avg`, `review_count`, `profile_views`, `submitted_at`, `created_at`, `updated_at`) VALUES
(2, 2, 'Juice', 'Here for good food, great energy, and genuine laughs', 'Easygoing, present, and always down for a proper night out or a relaxed evening. I appreciate people who value discretion and simplicity as much as I do. No games, no awkward small talk. Just pure comfort, attentive company, and an effortless date from start to finish.', NULL, 2000.00, NULL, NULL, 1, 0, NULL, '1,5,13,14,15,17,11,3,6,7,10,22', NULL, '96,115,111,95,116,99', '{\"mon\":[[\"10:00\",\"20:00\"]],\"tue\":[[\"10:00\",\"20:00\"]],\"wed\":[[\"10:00\",\"20:00\"]],\"thu\":[[\"10:00\",\"20:00\"]],\"fri\":[[\"10:00\",\"20:00\"]],\"sat\":[[\"10:00\",\"20:00\"]],\"sun\":[[\"10:00\",\"20:00\"]]}', NULL, 'approved', NULL, 1, '2026-10-16 14:19:14', 0, 1, 0, 0, 1700.00, 0.00, 0, 3, '2026-10-16 14:18:59', '2026-10-16 11:27:53', '2026-11-12 23:26:19'),
(9017, 1, 'Faith', 'Warm, well-read company for dinners and nights out', 'I spent six years in hospitality and PR, so I am comfortable in any room and happy to listen. I enjoy a good dinner, live music and meeting people from different walks of life. I arrive on time, keep to the plan we agree in advance and always choose somewhere safe and well lit.', NULL, 2500.00, 8500.00, 15000.00, 2, 1, 800.00, '1,2,4,11', NULL, '95,96,98,99,106', '{\"mon\": [[\"10:00\", \"21:00\"]], \"tue\": [[\"10:00\", \"21:00\"]], \"wed\": [[\"10:00\", \"21:00\"]], \"thu\": [[\"10:00\", \"21:00\"]], \"fri\": [[\"10:00\", \"21:00\"]], \"sat\": [[\"10:00\", \"21:00\"]], \"sun\": [[\"10:00\", \"21:00\"]]}', NULL, 'approved', NULL, 1, '2026-05-25 04:48:22', 1, 1, 0, 4, 18275.00, 4.75, 4, 487, '2026-05-23 04:48:22', '2026-05-19 04:48:22', '2026-11-12 21:21:43'),
(9018, 3, 'Naomi', 'Nairobi guide who knows the quiet corners as well as the sights', 'Licensed guide with eight years of showing visitors and new residents around Nairobi. I plan routes around your interests, from the National Museum and the Giraffe Centre to the best nyama choma in Karen. Comfortable shoes, curiosity and a sense of humour are all you need.', NULL, 2200.00, 8000.00, 14000.00, 2, 1, 1000.00, '2,3,8,12', NULL, '94,95,96,97,102', '{\"mon\": [[\"10:00\", \"21:00\"]], \"tue\": [[\"10:00\", \"21:00\"]], \"wed\": [[\"10:00\", \"21:00\"]], \"thu\": [[\"10:00\", \"21:00\"]], \"fri\": [[\"10:00\", \"21:00\"]], \"sat\": [[\"10:00\", \"21:00\"]], \"sun\": [[\"10:00\", \"21:00\"]]}', NULL, 'approved', NULL, 1, '2026-05-05 04:48:22', 1, 1, 0, 3, 18870.00, 4.67, 3, 616, '2026-05-03 04:48:22', '2026-04-29 04:48:22', '2026-11-09 09:35:23'),
(9019, 3, 'Amina', 'Old Town stories, Swahili food and the best sunset spots', 'Mombasa born and raised. I guide visitors through Old Town, Fort Jesus and the spice markets, and I know which stalls make the best kahawa and halwa. Modest dress, respectful company and plenty of local history.', NULL, 2000.00, 7000.00, 12500.00, 2, 0, NULL, '1,2,3,12', NULL, '48,107,108,109', '{\"mon\": [[\"10:00\", \"21:00\"]], \"tue\": [[\"10:00\", \"21:00\"]], \"wed\": [[\"10:00\", \"21:00\"]], \"thu\": [[\"10:00\", \"21:00\"]], \"fri\": [[\"10:00\", \"21:00\"]], \"sat\": [[\"10:00\", \"21:00\"]], \"sun\": [[\"10:00\", \"21:00\"]]}', NULL, 'approved', NULL, 1, '2026-06-22 04:48:22', 0, 1, 0, 3, 14875.00, 4.67, 3, 355, '2026-06-20 04:48:22', '2026-06-16 04:48:22', '2026-10-16 04:48:22'),
(9020, 1, 'Grace', 'Polished company for business dinners and corporate events', 'Ten years in corporate banking taught me how to make clients feel at ease. I am a good listener, comfortable with formal dinners and easy to brief on who is who beforehand. Discreet, punctual and always professional.', NULL, 3500.00, 12000.00, 22000.00, 2, 1, 1500.00, '1,4,5', NULL, '94,95,96,98,99', '{\"mon\": [[\"12:00\", \"22:00\"]], \"tue\": [[\"12:00\", \"22:00\"]], \"wed\": [[\"12:00\", \"22:00\"]], \"thu\": [[\"12:00\", \"22:00\"]], \"fri\": [[\"12:00\", \"22:00\"]], \"sat\": [[\"10:00\", \"20:00\"]], \"sun\": []}', NULL, 'approved', NULL, 1, '2026-06-06 04:48:22', 0, 1, 0, 3, 29750.00, 4.67, 3, 298, '2026-06-04 04:48:22', '2026-05-31 04:48:22', '2026-10-16 04:48:22'),
(9021, 5, 'Mercy', 'Trail runs, Ngong Hills hikes and a patient pace', 'Certified fitness coach who spends most weekends on the trails around Ngong and Kiserian. I match the pace to you and bring water, snacks and a first-aid kit. Great for hiking partners, early-morning runs or a first attempt at a longer trail.', NULL, 1800.00, 6500.00, 11000.00, 2, 1, 700.00, '7,8,9', NULL, '97,102,120,121,122', '{\"mon\": [[\"05:30\", \"13:00\"]], \"tue\": [[\"05:30\", \"13:00\"]], \"wed\": [[\"05:30\", \"13:00\"]], \"thu\": [[\"05:30\", \"13:00\"]], \"fri\": [[\"05:30\", \"13:00\"]], \"sat\": [[\"05:30\", \"17:00\"]], \"sun\": [[\"05:30\", \"17:00\"]]}', NULL, 'approved', NULL, 1, '2026-07-19 04:48:22', 0, 1, 0, 3, 13345.00, 4.67, 3, 223, '2026-07-17 04:48:22', '2026-07-13 04:48:22', '2026-11-12 22:15:52'),
(9022, 5, 'Mwanaisha', 'Beach days, snorkelling trips and sunsets in Diani', 'I teach snorkelling and paddleboarding at a beach club in Diani, so I know the quiet stretches and the best times for calm water. Relaxed company for beach days and boat trips. I carry sunscreen for everyone.', NULL, 1800.00, 6500.00, 11500.00, 2, 0, NULL, '2,3,7,12', NULL, '132,133,134', '{\"mon\": [[\"10:00\", \"21:00\"]], \"tue\": [[\"10:00\", \"21:00\"]], \"wed\": [[\"10:00\", \"21:00\"]], \"thu\": [[\"10:00\", \"21:00\"]], \"fri\": [[\"10:00\", \"21:00\"]], \"sat\": [[\"10:00\", \"21:00\"]], \"sun\": [[\"10:00\", \"21:00\"]]}', NULL, 'approved', NULL, 1, '2026-08-05 04:48:22', 0, 1, 0, 3, 18020.00, 4.67, 3, 192, '2026-08-03 04:48:22', '2026-07-30 04:48:22', '2026-11-08 22:32:33'),
(9023, 4, 'Peter', 'A relaxed, well-mannered plus-one for weddings and parties', 'Marketing manager who has been to a lot of weddings and enjoys every one. I dress well, mingle easily and stay with the plan we agree beforehand. Good for weddings, work events and parties where you would rather arrive with company.', NULL, 2800.00, 9500.00, 17000.00, 2, 1, 1000.00, '1,4,5', NULL, '95,96,97,98,99', '{\"mon\": [], \"tue\": [], \"wed\": [], \"thu\": [], \"fri\": [[\"16:00\", \"23:00\"]], \"sat\": [[\"08:00\", \"23:00\"]], \"sun\": [[\"08:00\", \"20:00\"]]}', NULL, 'approved', NULL, 1, '2026-07-04 04:48:22', 0, 1, 0, 3, 23460.00, 4.67, 3, 204, '2026-07-02 04:48:22', '2026-06-28 04:48:22', '2026-10-16 04:48:22'),
(9024, 2, 'Joseph', 'Naivasha and Hell’s Gate guide with a safe pair of hands', 'Safari guide with twelve years around Lake Naivasha, Hell’s Gate and Crescent Island. I drive carefully, carry a first-aid kit and know when the animals are most active. Ideal for travellers who would like a calm, informed companion for the day or the weekend.', NULL, 2400.00, 8500.00, 15500.00, 3, 1, 1200.00, '3,6,8', NULL, '79,124,125,126', '{\"mon\": [[\"10:00\", \"21:00\"]], \"tue\": [[\"10:00\", \"21:00\"]], \"wed\": [[\"10:00\", \"21:00\"]], \"thu\": [[\"10:00\", \"21:00\"]], \"fri\": [[\"10:00\", \"21:00\"]], \"sat\": [[\"10:00\", \"21:00\"]], \"sun\": [[\"10:00\", \"21:00\"]]}', NULL, 'approved', NULL, 1, '2026-07-24 04:48:22', 0, 1, 0, 3, 23375.00, 4.67, 3, 167, '2026-07-22 04:48:22', '2026-07-18 04:48:22', '2026-10-16 04:48:22'),
(9026, 5, 'Shee', 'Here for good food, great energy, and genuine laughs', 'Confident and composed, but completely approachable. I bring a grounded vibe and pay attention to the little details that make a date feel comfortable. If you want a companion who is sharp, discreet, and easy to talk to about almost anything, we will get along just fine.', NULL, 2000.00, NULL, 4000.00, 1, 0, NULL, '2,13,20,24,25,26', NULL, '47,111,116,112', '{\"mon\":[[\"10:00\",\"20:00\"]],\"tue\":[[\"10:00\",\"20:00\"]],\"wed\":[[\"10:00\",\"20:00\"]],\"thu\":[[\"10:00\",\"20:00\"]],\"fri\":[[\"10:00\",\"20:00\"]],\"sat\":[[\"10:00\",\"20:00\"]],\"sun\":[[\"10:00\",\"20:00\"]]}', NULL, 'approved', NULL, 1, '2026-10-16 12:58:31', 0, 1, 0, 0, 0.00, 0.00, 0, 1, '2026-10-16 12:57:19', '2026-10-16 12:49:57', '2026-10-16 18:03:31'),
(9029, NULL, 'Juice', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'draft', NULL, NULL, NULL, 0, 0, 0, 0, 0.00, 0.00, 0, 0, NULL, '2026-11-09 09:33:44', '2026-11-09 09:33:44'),
(9036, 1, 'Grace', 'Dinners, galleries and city evenings', 'I have hosted in Nairobi for four years: business dinners, gallery openings, and showing visitors the parts of the city they would never find alone. Warm, punctual and very easy to talk to. Company only.', 'WhatsApp is fastest. I answer in the evenings.', 2500.00, 9000.00, 16000.00, 2, 1, 1500.00, '1,2,4,5', '{\"1\":2500,\"4\":4000}', NULL, NULL, NULL, 'approved', NULL, NULL, '2026-11-09 03:49:59', 1, 1, 1, 18, 0.00, 4.80, 12, 5, '2026-11-09 03:49:59', '2026-11-09 03:49:59', '2026-11-12 21:25:41'),
(9037, 2, 'Hassan', 'Coast travel company and local guide', 'Born in Old Town and I know every corner of it. I travel with clients along the coast, handle the plans and the haggling, and make sure the days run smoothly. Company and guiding only.', 'Call any time before 9pm, or WhatsApp me.', 2000.00, 7500.00, 13000.00, 3, 1, 2500.00, '2,3,6', '{\"3\":3000}', NULL, NULL, NULL, 'approved', NULL, NULL, '2026-11-09 03:49:59', 0, 1, 1, 9, 0.00, 4.60, 7, 2, '2026-11-09 03:49:59', '2026-11-09 03:49:59', '2026-11-12 21:04:02'),
(9038, 4, 'Irene', 'Weddings, parties and plus-one company', 'New here but not new to events. I am the person you bring to a wedding when you do not want to answer questions about why you came alone. Friendly, well dressed and good with strangers. Company only.', 'Message me here first and we can take it from there.', 1800.00, 6500.00, 11000.00, 2, 0, NULL, '4,2,1', NULL, NULL, NULL, NULL, 'approved', NULL, NULL, '2026-11-09 03:49:59', 0, 1, 0, 0, 0.00, 0.00, 0, 4, '2026-11-09 03:49:59', '2026-11-09 03:49:59', '2026-11-12 21:21:27');

-- --------------------------------------------------------

--
-- Table structure for table `host_profile_ext`
--

CREATE TABLE `host_profile_ext` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `instant_booking` tinyint(1) NOT NULL DEFAULT 0,
  `bookings_paused` tinyint(1) NOT NULL DEFAULT 0,
  `show_prices` tinyint(1) NOT NULL DEFAULT 1,
  `listing_until` datetime DEFAULT NULL,
  `listing_period` varchar(10) DEFAULT NULL,
  `allow_custom_hours` tinyint(1) NOT NULL DEFAULT 1,
  `max_hours` decimal(4,1) DEFAULT NULL,
  `payout_phone` varchar(25) DEFAULT NULL,
  `payout_name` varchar(100) DEFAULT NULL,
  `id_doc_type` varchar(20) DEFAULT NULL,
  `id_doc_path` varchar(255) DEFAULT NULL,
  `selfie_path` varchar(255) DEFAULT NULL,
  `id_submitted_at` datetime DEFAULT NULL,
  `rules_agreed_at` datetime DEFAULT NULL,
  `rules_agreed_ip` varchar(45) DEFAULT NULL,
  `notify_email_bookings` tinyint(1) NOT NULL DEFAULT 1,
  `notify_email_reminders` tinyint(1) NOT NULL DEFAULT 1,
  `notify_email_reviews` tinyint(1) NOT NULL DEFAULT 1,
  `deactivate_requested_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_vip` tinyint(1) NOT NULL DEFAULT 0,
  `pin_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `promo_text` varchar(140) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `host_profile_ext`
--

INSERT INTO `host_profile_ext` (`user_id`, `instant_booking`, `bookings_paused`, `show_prices`, `listing_until`, `listing_period`, `allow_custom_hours`, `max_hours`, `payout_phone`, `payout_name`, `id_doc_type`, `id_doc_path`, `selfie_path`, `id_submitted_at`, `rules_agreed_at`, `rules_agreed_ip`, `notify_email_bookings`, `notify_email_reminders`, `notify_email_reviews`, `deactivate_requested_at`, `created_at`, `updated_at`, `is_vip`, `pin_order`, `promo_text`) VALUES
(2, 0, 0, 1, NULL, NULL, 1, NULL, '254758256440', 'Dennis Mwangi', 'national_id', 'host_id/2/id_987bbf21a828633f.jpg', 'host_id/2/selfie_53066122266f2656.jpg', '2026-10-16 14:18:30', '2026-10-16 14:18:56', '41.209.14.76', 1, 1, 1, NULL, '2026-10-16 14:16:33', '2026-10-16 14:18:56', 0, 0, NULL),
(9017, 1, 0, 1, NULL, NULL, 1, NULL, '254700000217', 'Faith Wambui', NULL, NULL, NULL, NULL, '2026-05-24 04:48:22', NULL, 1, 1, 1, NULL, '2026-05-19 04:48:22', '2026-10-16 04:48:22', 0, 0, NULL),
(9018, 0, 0, 1, NULL, NULL, 1, NULL, '254700000218', 'Naomi Chepkoech', NULL, NULL, NULL, NULL, '2026-05-04 04:48:22', NULL, 1, 1, 1, NULL, '2026-04-29 04:48:22', '2026-10-16 04:48:22', 0, 0, NULL),
(9019, 1, 0, 1, NULL, NULL, 1, NULL, '254700000219', 'Amina Hassan', NULL, NULL, NULL, NULL, '2026-06-21 04:48:22', NULL, 1, 1, 1, NULL, '2026-06-16 04:48:22', '2026-10-16 04:48:22', 0, 0, NULL),
(9020, 0, 0, 1, NULL, NULL, 1, NULL, '254700000220', 'Grace Nyambura', NULL, NULL, NULL, NULL, '2026-06-05 04:48:22', NULL, 1, 1, 1, NULL, '2026-05-31 04:48:22', '2026-10-16 04:48:22', 0, 0, NULL),
(9021, 1, 0, 1, NULL, NULL, 1, NULL, '254700000221', 'Mercy Naserian', NULL, NULL, NULL, NULL, '2026-07-18 04:48:22', NULL, 1, 1, 1, NULL, '2026-07-13 04:48:22', '2026-10-16 04:48:22', 0, 0, NULL),
(9022, 1, 0, 1, NULL, NULL, 1, NULL, '254700000222', 'Mwanaisha Juma', NULL, NULL, NULL, NULL, '2026-08-04 04:48:22', NULL, 1, 1, 1, NULL, '2026-07-30 04:48:22', '2026-10-16 04:48:22', 0, 0, NULL),
(9023, 0, 0, 1, NULL, NULL, 1, NULL, '254700000223', 'Peter Njuguna', NULL, NULL, NULL, NULL, '2026-07-03 04:48:22', NULL, 1, 1, 1, NULL, '2026-06-28 04:48:22', '2026-10-16 04:48:22', 0, 0, NULL),
(9024, 0, 0, 1, NULL, NULL, 1, NULL, '254700000224', 'Joseph Kimani', NULL, NULL, NULL, NULL, '2026-07-23 04:48:22', NULL, 1, 1, 1, NULL, '2026-07-18 04:48:22', '2026-10-16 04:48:22', 0, 0, NULL),
(9026, 0, 0, 1, NULL, NULL, 0, NULL, '254710896260', 'lucy', 'national_id', 'host_id/9026/id_ccefa163d3a5e3b3.jpg', 'host_id/9026/selfie_4ea566dbe169c077.jpg', '2026-10-16 12:56:52', '2026-10-16 12:57:14', '41.209.14.76', 1, 1, 1, NULL, '2026-10-16 12:52:55', '2026-10-16 12:57:14', 0, 0, NULL),
(9036, 1, 0, 1, '2031-11-09 03:49:59', 'yearly', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-09 03:49:59', NULL, 1, 1, 1, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59', 0, 0, NULL),
(9037, 0, 0, 1, '2031-11-09 03:49:59', 'yearly', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-09 03:49:59', NULL, 1, 1, 1, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59', 0, 0, NULL),
(9038, 0, 0, 1, '2031-11-09 03:49:59', 'yearly', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-09 03:49:59', NULL, 1, 1, 1, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `host_reviews`
--

CREATE TABLE `host_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `host_id` int(10) UNSIGNED NOT NULL,
  `reviewer_id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `review_text` varchar(1000) DEFAULT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `host_reply` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `host_reviews`
--

INSERT INTO `host_reviews` (`id`, `host_id`, `reviewer_id`, `booking_id`, `rating`, `review_text`, `is_anonymous`, `status`, `host_reply`, `created_at`) VALUES
(1, 9017, 9009, 1, 5, 'Faith was easy to talk to and arrived early. The evening flew by and she picked a lovely place. Would book again.', 1, 'approved', 'Thank you, I enjoyed it too.', '2026-10-10 23:10:00'),
(2, 9017, 9011, 2, 5, 'Professional, funny and thoughtful. Exactly as described on her profile.', 1, 'approved', NULL, '2026-09-27 23:10:00'),
(3, 9017, 9015, 3, 4, 'Great company at an event where I knew nobody. Slightly late but she messaged ahead.', 0, 'approved', 'Apologies again for the traffic, glad it went well.', '2026-09-13 23:10:00'),
(4, 9017, 9014, 4, 5, 'Relaxed and genuinely friendly. Good conversation throughout.', 1, 'approved', NULL, '2026-08-30 23:10:00'),
(5, 9018, 9009, 5, 5, 'Naomi knows Nairobi inside out and tailored the day to what I like. Highly recommended.', 0, 'approved', NULL, '2026-10-07 23:10:00'),
(6, 9018, 9012, 6, 5, 'Took my visiting cousin around and we both learned something new. Patient, funny and very organised.', 1, 'approved', NULL, '2026-09-22 23:10:00'),
(7, 9018, 9001, 7, 4, 'Good for practising my French. Relaxed pace and useful corrections.', 0, 'approved', 'Your French was already strong, see you again.', '2026-09-08 23:10:00'),
(8, 9019, 9013, 8, 5, 'Amina told stories I had never heard, even though I live here. Lovely afternoon.', 0, 'approved', NULL, '2026-10-05 23:10:00'),
(9, 9019, 9003, 9, 5, 'Brought my friend visiting from Kisumu. Respectful, warm and well informed.', 1, 'approved', NULL, '2026-09-18 23:10:00'),
(10, 9019, 9014, 10, 4, 'Good food and a good guide. Would have liked a bit more time at Fort Jesus.', 1, 'approved', 'Happy to plan a longer visit next time.', '2026-08-25 23:10:00'),
(11, 9020, 9011, 11, 5, 'Exactly the polished, easy company I needed for a client dinner. Very well prepared.', 1, 'approved', NULL, '2026-10-02 23:10:00'),
(12, 9020, 9009, 12, 5, 'Handled a room full of strangers with ease. Highly professional.', 1, 'approved', NULL, '2026-09-05 23:10:00'),
(13, 9020, 9012, 13, 4, 'Very professional. Briefing beforehand made it smooth.', 0, 'approved', 'Thank you, glad it was useful.', '2026-08-14 23:10:00'),
(14, 9021, 9010, 14, 5, 'Great pace and great encouragement. I finished my first 10K because of her.', 0, 'approved', NULL, '2026-10-09 23:10:00'),
(15, 9021, 9001, 15, 5, 'A beautiful morning and the snacks were a bonus. Very safety-minded.', 1, 'approved', NULL, '2026-09-25 23:10:00'),
(16, 9021, 9008, 16, 4, 'Fun and friendly. Weather was against us but she adapted the route well.', 1, 'approved', 'Next time we pick a clear morning!', '2026-09-01 23:10:00'),
(17, 9022, 9015, 17, 5, 'Calm, funny and very safe in the water. Best morning of my trip.', 0, 'approved', NULL, '2026-10-03 23:10:00'),
(18, 9022, 9016, 18, 5, 'Brilliant afternoon. She knew exactly where to go.', 1, 'approved', NULL, '2026-09-17 23:10:00'),
(19, 9022, 9013, 19, 4, 'Relaxed and professional. Good local knowledge.', 1, 'approved', 'Thank you!', '2026-08-22 23:10:00'),
(20, 9023, 9006, 20, 5, 'Polite, funny and a great dancer. Made a stressful family wedding easy.', 1, 'approved', NULL, '2026-10-06 23:10:00'),
(21, 9023, 9002, 21, 5, 'Excellent conversation and very respectful. Would book again.', 1, 'approved', NULL, '2026-09-10 23:10:00'),
(22, 9023, 9004, 22, 4, 'Good company and easy to talk to at a work event.', 0, 'approved', 'Thank you, glad it went well.', '2026-08-19 23:10:00'),
(23, 9024, 9010, 23, 5, 'Knowledgeable and patient. Spotted more wildlife than I expected.', 0, 'approved', NULL, '2026-09-29 23:10:00'),
(24, 9024, 9016, 24, 5, 'A relaxed day with great stories about the lake.', 1, 'approved', NULL, '2026-09-07 23:10:00'),
(25, 9024, 9013, 25, 4, 'Safe, calm and knows the park well. Slow start but great day.', 1, 'approved', 'Thank you, see you on the next trip.', '2026-08-11 23:10:00');

-- --------------------------------------------------------

--
-- Table structure for table `host_services`
--

CREATE TABLE `host_services` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `group_name` varchar(50) NOT NULL DEFAULT 'More',
  `icon` varchar(40) DEFAULT NULL,
  `color` varchar(16) NOT NULL DEFAULT '',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `host_services`
--

INSERT INTO `host_services` (`id`, `name`, `description`, `group_name`, `icon`, `color`, `sort_order`, `is_active`) VALUES
(1, 'Dinner', 'Share a meal at a restaurant of your choice.', 'Meals and conversation', 'utensils', '', 1, 1),
(2, 'Coffee or lunch', 'A relaxed daytime meet-up.', 'Meals and conversation', 'coffee', '', 2, 1),
(3, 'City tour', 'See the best of the city with a local.', 'Travel and outdoors', 'map', '', 14, 1),
(4, 'Event plus-one', 'Company at weddings, parties and corporate events.', 'Events and nights out', 'party-popper', '', 8, 1),
(5, 'Business dinner', 'Polished company for client dinners.', 'Meals and conversation', 'briefcase', '', 3, 1),
(6, 'Weekend getaway', 'Travel company for short trips.', 'Travel and outdoors', 'plane', '', 15, 1),
(7, 'Beach day', 'Spend a day at the coast.', 'Travel and outdoors', 'umbrella', '', 16, 1),
(8, 'Hiking buddy', 'Trails and nature walks.', 'Travel and outdoors', 'mountain-snow', '', 17, 1),
(9, 'Gym or run partner', 'Keep each other on track.', 'Active and lifestyle', 'dumbbell', '', 20, 1),
(10, 'Shopping companion', 'Help choosing, carrying and chatting.', 'Active and lifestyle', 'shopping-bag', '', 21, 1),
(11, 'Movie night', 'Cinema or a quiet night in.', 'Events and nights out', 'clapperboard', '', 13, 1),
(12, 'Language practice', 'Practise English, Kiswahili, French or others.', 'Meals and conversation', 'languages', '', 6, 1),
(13, 'Rooftop drinks', 'Drinks and good conversation at a public venue.', 'Meals and conversation', 'wine', '', 4, 1),
(14, 'Conversation partner', 'Talk about anything over a coffee or a walk.', 'Meals and conversation', 'message-circle', '', 5, 1),
(15, 'Video call company', 'A friendly chat or dinner by video call.', 'Meals and conversation', 'video', '', 7, 1),
(16, 'Wedding guest company', 'Someone easy to be around at a wedding.', 'Events and nights out', 'heart-handshake', '', 9, 1),
(17, 'Concert or festival', 'Live music, festivals and shows.', 'Events and nights out', 'music', '', 10, 1),
(18, 'Sports match', 'Football, rugby or any match day.', 'Events and nights out', 'trophy', '', 11, 1),
(19, 'Gallery or museum', 'Art, culture and a slow afternoon.', 'Events and nights out', 'frame', '', 12, 1),
(20, 'Safari companion', 'Game drives and park visits.', 'Travel and outdoors', 'binoculars', '', 18, 1),
(21, 'Road trip company', 'Someone good company on the road.', 'Travel and outdoors', 'car-front', '', 19, 1),
(22, 'Photography walk', 'Explore the city with a camera.', 'Active and lifestyle', 'camera', '', 22, 1),
(23, 'Cooking together', 'Cook a meal side by side.', 'Active and lifestyle', 'chef-hat', '', 23, 1),
(24, 'Dance practice', 'Practise steps for a wedding or a night out.', 'Active and lifestyle', 'audio-lines', '', 24, 1),
(25, 'Public venue meet-ups', 'Always at a public, well-lit place.', 'How we meet', 'map-pin', '', 25, 1),
(26, 'Can travel to you', 'Meets you at a public place in your area.', 'How we meet', 'car', '', 26, 1),
(27, 'Dinner Dates', 'A relaxed dinner for two at a restaurant of your choice.', 'Meals and conversation', 'utensils', '', 27, 1),
(28, 'Travel Companion', 'Companionship for planned trips, sightseeing and travel activities.', 'Travel and outdoors', 'plane', '', 22, 1),
(29, 'Massage', 'Wellness massage offered by appropriately qualified providers, where applicable.', 'Wellness', 'hand', '', 26, 1),
(30, 'Meet at my place', 'You meet at a place the host looks after. Details are agreed in the chat first.', 'How we meet', 'home', '', 30, 1),
(31, 'Dinner Date', 'Company for dinner at a restaurant or another agreed public venue.', 'Meals and conversation', 'utensils', '', 23, 1),
(32, 'Girlfriend Experience', 'A date-style companionship experience focused on conversation and shared activities.', 'Companionship', 'heart-handshake', '', 24, 1),
(33, 'Lesbian Entertainment', 'Entertainment and social companionship for consenting adults, within platform rules.', 'Entertainment', 'heart', '', 25, 1),
(34, 'Social Companion', 'Friendly company for social outings, conversations and public activities.', 'Companionship', 'users', '', 27, 1),
(35, 'Hotel Companion', 'Companionship for agreed social activities at appropriate public hospitality venues.', 'Travel and outdoors', 'hotel', '', 28, 1),
(36, 'Weekend Companion', 'Company for planned weekend outings and activities.', 'Travel and outdoors', 'calendar-days', '', 29, 1),
(37, 'Photography Companion', 'Company for photography walks, sightseeing and creative outings.', 'Active and lifestyle', 'camera', '', 30, 1),
(39, 'Threesome', NULL, 'Entertainment', 'user-round', 'emerald', 31, 1),
(40, 'Girlfriend Experience (GFE)', NULL, 'Travel and outdoors', 'heart-handshake', 'violet', 32, 1),
(41, 'Anal', NULL, 'Entertainment', 'shield-check', 'rose', 33, 1),
(42, 'BJ', NULL, 'Entertainment', 'message-square-heart', 'amber', 34, 1),
(43, 'COB', NULL, 'Entertainment', 'sparkles', 'teal', 35, 1);

-- --------------------------------------------------------

--
-- Table structure for table `ice_breakers`
--

CREATE TABLE `ice_breakers` (
  `id` int(10) UNSIGNED NOT NULL,
  `text` varchar(200) NOT NULL,
  `category` varchar(30) NOT NULL DEFAULT 'casual',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ice_breakers`
--

INSERT INTO `ice_breakers` (`id`, `text`, `category`, `is_active`) VALUES
(1, 'What is your favourite place to eat in town?', 'food', 1),
(2, 'If you could travel anywhere next month, where would you go?', 'travel', 1),
(3, 'What is the best thing that happened to you this week?', 'casual', 1),
(4, 'Coffee, tea or something stronger?', 'food', 1),
(5, 'What are you listening to on repeat right now?', 'music', 1),
(6, 'What is your ideal Sunday?', 'casual', 1),
(7, 'Which place in Kenya should everyone visit once?', 'travel', 1),
(8, 'What is a small thing that always makes your day better?', 'casual', 1),
(9, 'What is the last film or series you could not stop watching?', 'entertainment', 1),
(10, 'Early bird or night owl?', 'casual', 1),
(11, 'What is your go-to karaoke song?', 'music', 1),
(12, 'What would your perfect weekend look like?', 'casual', 1);

-- --------------------------------------------------------

--
-- Table structure for table `interests`
--

CREATE TABLE `interests` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `emoji` varchar(16) DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'General',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `interests`
--

INSERT INTO `interests` (`id`, `name`, `icon`, `emoji`, `category`, `sort_order`, `is_active`) VALUES
(1, 'Travel', 'plane', '✈️', 'Lifestyle', 1, 1),
(2, 'Hiking', 'mountain-snow', '🥾', 'Outdoors', 2, 1),
(3, 'Camping', 'tent-tree', '⛺', 'Outdoors', 3, 1),
(4, 'Safari', 'binoculars', '🦁', 'Outdoors', 4, 1),
(5, 'Beach days', 'umbrella', '🏖️', 'Outdoors', 5, 1),
(6, 'Running', 'footprints', '🏃', 'Fitness', 6, 1),
(7, 'Gym', 'dumbbell', '🏋️', 'Fitness', 7, 1),
(8, 'Yoga', 'flower-2', '🧘', 'Fitness', 8, 1),
(9, 'Football', 'football', '⚽', 'Sports', 9, 1),
(10, 'Basketball', 'basketball', '🏀', 'Sports', 10, 1),
(11, 'Cycling', 'bike', '🚴', 'Fitness', 11, 1),
(12, 'Swimming', 'waves', '🏊', 'Fitness', 12, 1),
(13, 'Cooking', 'chef-hat', '🍳', 'Food & drink', 13, 1),
(14, 'Baking', 'cake-slice', '🧁', 'Food & drink', 14, 1),
(15, 'Nyama choma', 'flame', '🍖', 'Food & drink', 15, 1),
(16, 'Coffee', 'coffee', '☕', 'Food & drink', 16, 1),
(17, 'Wine', 'wine', '🍷', 'Food & drink', 17, 1),
(18, 'Craft beer', 'beer', '🍺', 'Food & drink', 18, 1),
(19, 'Street food', 'sandwich', '🌮', 'Food & drink', 19, 1),
(20, 'Vegan food', 'leaf', '🥗', 'Food & drink', 20, 1),
(21, 'Movies', 'clapperboard', '🎬', 'Entertainment', 21, 1),
(22, 'Series', 'tv', '📺', 'Entertainment', 22, 1),
(23, 'Stand-up comedy', 'mic', '🎤', 'Entertainment', 23, 1),
(24, 'Live music', 'guitar', '🎸', 'Music', 24, 1),
(25, 'Afrobeats', 'audio-lines', '🎶', 'Music', 25, 1),
(26, 'Gospel', 'music-2', '🙏', 'Music', 26, 1),
(27, 'Benga', 'drum', '🎵', 'Music', 27, 1),
(28, 'Hip hop', 'headphones', '🎧', 'Music', 28, 1),
(29, 'Jazz', 'piano', '🎷', 'Music', 29, 1),
(30, 'Dancing', 'party-popper', '💃', 'Music', 30, 1),
(31, 'Reading', 'book-open', '📚', 'Creative', 31, 1),
(32, 'Writing', 'pen-line', '✍️', 'Creative', 32, 1),
(33, 'Photography', 'camera', '📷', 'Creative', 33, 1),
(34, 'Painting', 'palette', '🎨', 'Creative', 34, 1),
(35, 'Fashion', 'shirt', '👗', 'Creative', 35, 1),
(36, 'Poetry', 'feather', '📜', 'Creative', 36, 1),
(37, 'Gaming', 'gamepad-2', '🎮', 'Entertainment', 37, 1),
(38, 'Board games', 'dices', '🎲', 'Entertainment', 38, 1),
(39, 'Tech', 'cpu', '💻', 'Interests', 39, 1),
(40, 'Startups', 'rocket', '🚀', 'Interests', 40, 1),
(41, 'Investing', 'trending-up', '📈', 'Interests', 41, 1),
(42, 'Politics', 'landmark', '🗳️', 'Interests', 42, 1),
(43, 'Volunteering', 'hand-heart', '🤝', 'Interests', 43, 1),
(44, 'Faith', 'church', '⛪', 'Interests', 44, 1),
(45, 'Meditation', 'brain', '🌿', 'Interests', 45, 1),
(46, 'Gardening', 'sprout', '🌱', 'Lifestyle', 46, 1),
(47, 'Pets', 'paw-print', '🐕', 'Lifestyle', 47, 1),
(48, 'Road trips', 'route', '🚗', 'Lifestyle', 48, 1),
(49, 'Motorbikes', 'motorbike', '🏍️', 'Lifestyle', 49, 1),
(50, 'Cars', 'car-front', '🚙', 'Lifestyle', 50, 1),
(51, 'Art galleries', 'frame', '🖼️', 'Creative', 51, 1),
(52, 'Theatre', 'drama', '🎭', 'Entertainment', 52, 1),
(53, 'Festivals', 'tent', '🎪', 'Entertainment', 53, 1),
(54, 'Karaoke', 'mic-vocal', '🎙️', 'Music', 54, 1),
(55, 'Languages', 'languages', '🗣️', 'Interests', 55, 1),
(56, 'Podcasts', 'podcast', '🎙', 'Entertainment', 56, 1),
(57, 'Fishing', 'fish', '🎣', 'Outdoors', 57, 1),
(58, 'Bird watching', 'bird', '🦜', 'Outdoors', 58, 1),
(59, 'Mountain biking', 'mountain', '🚵', 'Outdoors', 59, 1),
(60, 'Quiet evenings in', 'sofa', '🕯️', 'Lifestyle', 60, 1);

-- --------------------------------------------------------

--
-- Table structure for table `intl_access`
--

CREATE TABLE `intl_access` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `source` varchar(12) NOT NULL DEFAULT 'purchase',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `intl_profiles`
--

CREATE TABLE `intl_profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'pending',
  `country` char(2) NOT NULL,
  `city` varchar(80) DEFAULT NULL,
  `languages` varchar(200) NOT NULL DEFAULT '',
  `purposes` varchar(120) NOT NULL DEFAULT '',
  `want_countries` varchar(500) NOT NULL DEFAULT '',
  `headline` varchar(120) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `contact_type` varchar(12) DEFAULT NULL,
  `contact_dial` varchar(6) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `contact_share` varchar(8) NOT NULL DEFAULT 'chat',
  `listed_until` datetime DEFAULT NULL,
  `admin_note` varchar(255) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `intl_profiles`
--

INSERT INTO `intl_profiles` (`user_id`, `status`, `country`, `city`, `languages`, `purposes`, `want_countries`, `headline`, `about`, `contact_type`, `contact_dial`, `contact_number`, `contact_share`, `listed_until`, `admin_note`, `approved_at`, `created_at`, `updated_at`) VALUES
(9025, 'active', 'US', 'LA', 'en', 'relationship', '', 'Loving, caring', NULL, 'whatsapp', '1', '23456789', 'never', NULL, NULL, '2026-10-16 15:06:33', '2026-10-16 15:06:14', '2026-10-16 15:06:33');

-- --------------------------------------------------------

--
-- Table structure for table `landing_sections`
--

CREATE TABLE `landing_sections` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_key` varchar(40) NOT NULL,
  `title` varchar(120) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `content` mediumtext DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `landing_sections`
--

INSERT INTO `landing_sections` (`id`, `section_key`, `title`, `is_enabled`, `sort_order`, `content`, `updated_at`) VALUES
(1, 'locations', 'Browse by place', 1, 2, '{\"title\":\"Find people in your town\",\"subtitle\":\"Pick a county or town to see who is nearby.\",\"show\":\"mixed\",\"count\":16,\"source\":\"picked\",\"only_members\":false,\"style\":\"chips\",\"show_counts\":true}', '2026-10-16 23:57:50'),
(2, 'profiles', 'New faces', 1, 1, '{\"title\": \"New faces near you\", \"subtitle\": \"Recently joined members, shown by distance once you sign in.\", \"cta_text\": \"See everyone\", \"cta_url\": \"/browse\"}', '2026-10-16 15:55:07'),
(3, 'how_it_works', 'How it works', 1, 3, '{\"title\":\"From hello to meeting in three steps\",\"subtitle\":\"No endless profiles to fill in, no pressure.\",\"items\":[{\"icon\":\"user-plus\",\"image\":\"landing/6ee5e9ab0a624f76.jpg\",\"title\":\"Create your profile\",\"text\":\"Add a few photos, say what you are looking for and pick your interests. It takes about two minutes.\"},{\"icon\":\"compass\",\"image\":\"landing/2a247f02f35c8cdc.jpg\",\"title\":\"See who is nearby\",\"text\":\"Browse by swiping, a grid or a list. Everyone is shown by distance, so you meet people you can actually meet.\"},{\"icon\":\"message-circle\",\"image\":\"landing/24a6f122b2b1e6b7.jpg\",\"title\":\"Say hello\",\"text\":\"When you both like each other, start a conversation. Take it at your own pace and meet when you are ready.\"}]}', '2026-10-16 19:22:18'),
(4, 'features', 'Why people choose us', 0, 4, '{\"title\": \"Built around trust and privacy\", \"subtitle\": \"The things that make meeting new people feel safe.\", \"items\": [{\"icon\": \"badge-check\", \"title\": \"Verified profiles\", \"text\": \"Members can verify with a selfie, and verified profiles carry a badge.\"}, {\"icon\": \"eye-off\", \"title\": \"You control what is visible\", \"text\": \"Hide your online status, distance and contact details, or browse in incognito.\"}, {\"icon\": \"map-pin\", \"title\": \"Local by design\", \"text\": \"Results are ordered by how close people are, across all 47 counties.\"}, {\"icon\": \"shield-check\", \"title\": \"Moderated and reportable\", \"text\": \"Photos are checked, and every profile has a block and report button.\"}, {\"icon\": \"smartphone\", \"title\": \"Works like an app\", \"text\": \"Add it to your home screen from your browser. No app store needed.\"}, {\"icon\": \"wallet\", \"title\": \"Pay with M-Pesa\", \"text\": \"Upgrade in seconds with M-Pesa. No card needed.\"}]}', '2026-10-16 15:55:18'),
(5, 'safety', 'Safety', 1, 6, '{\"title\":\"Your safety comes first\",\"subtitle\":\"Simple habits that keep meeting people enjoyable.\",\"items\":[{\"icon\":\"video\",\"text\":\"Chat first, then move to a video call before you meet.\"},{\"icon\":\"map-pin\",\"text\":\"Meet in a public place and tell a friend where you are going.\"},{\"icon\":\"lock\",\"text\":\"Keep your phone number private until you trust the person.\"},{\"icon\":\"flag\",\"text\":\"Report anything that feels wrong. We review every report.\"}],\"cta_text\":\"Read all safety tips\",\"cta_url\":\"/safety-tips\",\"image\":\"landing/3065324248d992ec.jpg\"}', '2026-11-09 08:02:23'),
(6, 'hosts', 'Escorts', 0, 7, '{\"title\":\"Browse approved Escorts\",\"subtitle\":\"Escorts are approved, ID-verified adults offering non-sexual companionship, public events and conversation. Nothing is booked or paid for on this platform.\",\"cta_text\":\"View Escorts\",\"cta_url\":\"/hosts\",\"secondary_text\":\"Become an Escort\",\"secondary_url\":\"/register?type=host\",\"bullets\":[\"ID-checked and approved by our team\",\"Clear profile details and contact options\",\"Contact details are shown according to each profile settings\"],\"image\":\"landing/5d5d7a2da34f87fc.jpg\"}', '2026-11-12 23:28:07'),
(7, 'premium', 'Premium', 0, 9, '{\"title\": \"Upgrade when you are ready\", \"subtitle\": \"Start free. Go Premium for unlimited likes and to see who likes you.\", \"cta_text\": \"Compare plans\", \"cta_url\": \"/pricing\"}', '2026-11-09 08:02:23'),
(8, 'testimonials', 'Stories', 0, 10, '{\"title\": \"What members say\", \"subtitle\": \"Real words from people on the site.\"}', '2026-11-10 00:45:14'),
(9, 'blog', 'From the blog', 0, 11, '{\"title\": \"Advice for meeting people\", \"subtitle\": \"Short reads on dating, safety and making friends.\", \"count\": 3}', '2026-11-10 00:45:15'),
(10, 'faq', 'Questions', 0, 12, '{\"title\": \"Questions people ask\", \"subtitle\": \"Quick answers before you join.\", \"count\": 5}', '2026-11-09 08:02:23'),
(11, 'cta', 'Join', 1, 13, '{\"title\": \"Your next good conversation is nearby\", \"subtitle\": \"Create a free profile and see who is around you today.\", \"cta_text\": \"Create your profile\", \"cta_url\": \"/register\", \"secondary_text\": \"I already have an account\", \"secondary_url\": \"/login\"}', '2026-11-09 08:02:23'),
(12, 'international', 'International', 1, 8, '{\"title\":\"Meet someone beyond the border\",\"subtitle\":\"Friendships, language exchange and romance with people in other countries. Everyone chose to be found.\",\"cta_text\":\"Meet people abroad\",\"cta_url\":\"/international\"}', '2026-11-09 08:02:23'),
(13, 'agents_top', 'Featured Escorts', 1, 0, '{\"title\":\"Featured Escorts near you\",\"subtitle\":\"Profiles selected for featured placement.\",\"tier_vip\":false,\"tier_featured\":true,\"tier_premium\":false,\"tier_paid\":false,\"tier_boosted\":false,\"fill_others\":false,\"sort\":\"rotate\",\"category\":0,\"count\":12,\"layout\":\"grid\",\"card_style\":\"overlay\",\"cols_desktop\":4,\"cols_tablet\":3,\"cols_mobile\":2,\"show_services\":false,\"max_services\":3,\"show_rating\":false,\"show_price\":true,\"show_tier\":true,\"show_contact\":true,\"show_bio\":true,\"cta_text\":\"See all Escorts\",\"cta_url\":\"/hosts\"}', '2026-11-12 21:21:10'),
(14, 'agents_new', 'Recently Joined Escorts', 1, 1, '{\"title\":\"Recently Joined Escorts\",\"subtitle\":\"Newly approved profiles, with no repeats from the sections above.\",\"tier_vip\":false,\"tier_featured\":false,\"tier_premium\":false,\"tier_paid\":false,\"tier_boosted\":false,\"fill_others\":true,\"sort\":\"newest\",\"category\":0,\"count\":16,\"layout\":\"grid\",\"card_style\":\"stack\",\"cols_desktop\":4,\"cols_tablet\":3,\"cols_mobile\":2,\"show_services\":false,\"max_services\":3,\"show_rating\":false,\"show_price\":true,\"show_tier\":false,\"show_contact\":true,\"show_bio\":true,\"cta_text\":\"Browse all Escorts\",\"cta_url\":\"/hosts\"}', '2026-11-12 21:21:10'),
(15, 'elite', 'Elite', 1, 1, '{\"title\":\"The Elite circle\",\"subtitle\":\"Established, successful people who know what they want. Verified profiles, and they choose who reaches them.\",\"count\":6,\"cta_text\":\"Explore Elite\",\"cta_url\":\"/elite\"}', '2026-11-08 14:24:37'),
(16, 'promos', 'Promotions', 1, 5, '{\"title\":\"Worth a look\",\"subtitle\":\"Offers and news from businesses near you.\",\"count\":6,\"cta_text\":\"Advertise with us\",\"cta_url\":\"/advertise\"}', '2026-11-09 08:02:23'),
(17, 'agents_vip', 'VIP Escorts', 1, -4, '{\"title\":\"VIP Escorts\",\"subtitle\":\"Priority profiles with VIP access.\",\"tier_vip\":true,\"tier_featured\":false,\"tier_premium\":false,\"tier_paid\":false,\"tier_boosted\":false,\"fill_others\":false,\"sort\":\"rotate\",\"category\":0,\"count\":12,\"layout\":\"grid\",\"card_style\":\"stack\",\"cols_desktop\":4,\"cols_tablet\":3,\"cols_mobile\":2,\"show_services\":false,\"show_rating\":false,\"show_price\":true,\"show_tier\":true,\"show_contact\":true,\"show_bio\":true,\"cta_text\":\"See all Escorts\",\"cta_url\":\"/hosts\"}', '2026-11-12 21:21:10'),
(18, 'agents_prime', 'Prime Escorts', 1, -3, '{\"title\":\"Prime Escorts\",\"subtitle\":\"Profiles included with an active Prime subscription.\",\"tier_vip\":false,\"tier_featured\":false,\"tier_premium\":true,\"tier_paid\":false,\"tier_boosted\":false,\"fill_others\":false,\"sort\":\"rotate\",\"category\":0,\"count\":12,\"layout\":\"grid\",\"card_style\":\"stack\",\"cols_desktop\":4,\"cols_tablet\":3,\"cols_mobile\":2,\"show_services\":false,\"show_rating\":false,\"show_price\":true,\"show_tier\":true,\"show_contact\":true,\"show_bio\":true,\"cta_text\":\"See all Escorts\",\"cta_url\":\"/hosts\"}', '2026-11-12 21:21:10');

-- --------------------------------------------------------

--
-- Table structure for table `launch_access_campaigns`
--

CREATE TABLE `launch_access_campaigns` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL DEFAULT 'Launch Access',
  `description` varchar(500) DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `duration_days` smallint(5) UNSIGNED NOT NULL DEFAULT 60,
  `include_existing` tinyint(1) NOT NULL DEFAULT 0,
  `eligible_roles` varchar(80) NOT NULL DEFAULT 'user',
  `features_json` mediumtext NOT NULL,
  `reminder_days` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `launch_access_campaigns`
--

INSERT INTO `launch_access_campaigns` (`id`, `name`, `description`, `is_enabled`, `starts_at`, `ends_at`, `duration_days`, `include_existing`, `eligible_roles`, `features_json`, `reminder_days`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'ConnectHub Launch Access', 'A limited launch promotion for new members. No payment method is required and no automatic charge is made when access ends.', 0, NULL, NULL, 60, 0, 'user', '{\"daily_swipes\":-1,\"daily_messages\":-1,\"see_who_liked\":true,\"advanced_filters\":true,\"read_receipts\":true,\"profile_visitors\":true,\"no_ads\":true,\"max_photos\":12}', 3, NULL, '2026-11-12 20:20:04', '2026-11-12 20:20:04');

-- --------------------------------------------------------

--
-- Table structure for table `launch_access_grants`
--

CREATE TABLE `launch_access_grants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campaign_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `starts_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `status` enum('active','expired','revoked') NOT NULL DEFAULT 'active',
  `features_json` mediumtext NOT NULL,
  `reminder_sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` enum('county','town') NOT NULL DEFAULT 'town',
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `county_code` tinyint(3) UNSIGNED DEFAULT NULL,
  `region` varchar(40) DEFAULT NULL,
  `latitude` decimal(9,6) NOT NULL,
  `longitude` decimal(9,6) NOT NULL,
  `is_popular` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL,
  `show_landing` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `seo_intro` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `type`, `name`, `slug`, `parent_id`, `county_code`, `region`, `latitude`, `longitude`, `is_popular`, `sort_order`, `image_path`, `show_landing`, `is_active`, `seo_intro`) VALUES
(1, 'county', 'Mombasa', 'mombasa', NULL, 1, 'Coast', -4.043500, 39.668200, 1, 20, NULL, 1, 1, NULL),
(2, 'county', 'Kwale', 'kwale', NULL, 2, 'Coast', -4.173700, 39.452100, 0, 1002, NULL, 0, 1, NULL),
(3, 'county', 'Kilifi', 'kilifi', NULL, 3, 'Coast', -3.510700, 39.909300, 1, 100, NULL, 1, 1, NULL),
(4, 'county', 'Tana River', 'tana-river', NULL, 4, 'Coast', -1.499700, 40.031600, 0, 1004, NULL, 0, 1, NULL),
(5, 'county', 'Lamu', 'lamu', NULL, 5, 'Coast', -2.271700, 40.902000, 0, 1005, NULL, 0, 1, NULL),
(6, 'county', 'Taita-Taveta', 'taita-taveta', NULL, 6, 'Coast', -3.396100, 38.556000, 0, 1006, NULL, 0, 1, NULL),
(7, 'county', 'Garissa', 'garissa', NULL, 7, 'North Eastern', -0.453200, 39.646100, 0, 1007, NULL, 0, 1, NULL),
(8, 'county', 'Wajir', 'wajir', NULL, 8, 'North Eastern', 1.747100, 40.057300, 0, 1008, NULL, 0, 1, NULL),
(9, 'county', 'Mandera', 'mandera', NULL, 9, 'North Eastern', 3.936600, 41.867000, 0, 1009, NULL, 0, 1, NULL),
(10, 'county', 'Marsabit', 'marsabit', NULL, 10, 'Eastern', 2.328400, 37.989900, 0, 1010, NULL, 0, 1, NULL),
(11, 'county', 'Isiolo', 'isiolo', NULL, 11, 'Eastern', 0.354600, 37.582200, 0, 1011, NULL, 0, 1, NULL),
(12, 'county', 'Meru', 'meru', NULL, 12, 'Eastern', 0.048000, 37.655900, 0, 1012, NULL, 0, 1, NULL),
(13, 'county', 'Tharaka-Nithi', 'tharaka-nithi', NULL, 13, 'Eastern', -0.333300, 37.650000, 0, 1013, NULL, 0, 1, NULL),
(14, 'county', 'Embu', 'embu', NULL, 14, 'Eastern', -0.538900, 37.459600, 0, 1014, NULL, 0, 1, NULL),
(15, 'county', 'Kitui', 'kitui', NULL, 15, 'Eastern', -1.366700, 38.010600, 0, 1015, NULL, 0, 1, NULL),
(16, 'county', 'Machakos', 'machakos', NULL, 16, 'Eastern', -1.517700, 37.263400, 1, 80, NULL, 1, 1, NULL),
(17, 'county', 'Makueni', 'makueni', NULL, 17, 'Eastern', -1.803900, 37.620200, 0, 1017, NULL, 0, 1, NULL),
(18, 'county', 'Nyandarua', 'nyandarua', NULL, 18, 'Central', -0.266700, 36.383300, 0, 1018, NULL, 0, 1, NULL),
(19, 'county', 'Nyeri', 'nyeri', NULL, 19, 'Central', -0.420100, 36.947600, 1, 90, NULL, 1, 1, NULL),
(20, 'county', 'Kirinyaga', 'kirinyaga', NULL, 20, 'Central', -0.498900, 37.280300, 0, 1020, NULL, 0, 1, NULL),
(21, 'county', 'Murang\'a', 'murang-a', NULL, 21, 'Central', -0.721000, 37.152600, 0, 1021, NULL, 0, 1, NULL),
(22, 'county', 'Kiambu', 'kiambu', NULL, 22, 'Central', -1.171400, 36.835600, 1, 60, NULL, 1, 1, NULL),
(23, 'county', 'Turkana', 'turkana', NULL, 23, 'Rift Valley', 3.119000, 35.597000, 0, 1023, NULL, 0, 1, NULL),
(24, 'county', 'West Pokot', 'west-pokot', NULL, 24, 'Rift Valley', 1.238900, 35.111900, 0, 1024, NULL, 0, 1, NULL),
(25, 'county', 'Samburu', 'samburu', NULL, 25, 'Rift Valley', 1.100000, 36.700000, 0, 1025, NULL, 0, 1, NULL),
(26, 'county', 'Trans-Nzoia', 'trans-nzoia', NULL, 26, 'Rift Valley', 1.019100, 34.997700, 0, 1026, NULL, 0, 1, NULL),
(27, 'county', 'Uasin Gishu', 'uasin-gishu', NULL, 27, 'Rift Valley', 0.514300, 35.269800, 1, 50, NULL, 1, 1, NULL),
(28, 'county', 'Elgeyo-Marakwet', 'elgeyo-marakwet', NULL, 28, 'Rift Valley', 0.566700, 35.516700, 0, 1028, NULL, 0, 1, NULL),
(29, 'county', 'Nandi', 'nandi', NULL, 29, 'Rift Valley', 0.200000, 35.100000, 0, 1029, NULL, 0, 1, NULL),
(30, 'county', 'Baringo', 'baringo', NULL, 30, 'Rift Valley', 0.466700, 35.966700, 0, 1030, NULL, 0, 1, NULL),
(31, 'county', 'Laikipia', 'laikipia', NULL, 31, 'Rift Valley', 0.016700, 37.066700, 0, 1031, NULL, 0, 1, NULL),
(32, 'county', 'Nakuru', 'nakuru', NULL, 32, 'Rift Valley', -0.303100, 36.080000, 1, 40, NULL, 1, 1, NULL),
(33, 'county', 'Narok', 'narok', NULL, 33, 'Rift Valley', -1.083300, 35.866700, 0, 1033, NULL, 0, 1, NULL),
(34, 'county', 'Kajiado', 'kajiado', NULL, 34, 'Rift Valley', -1.850000, 36.783300, 1, 70, NULL, 1, 1, NULL),
(35, 'county', 'Kericho', 'kericho', NULL, 35, 'Rift Valley', -0.368900, 35.286300, 0, 1035, NULL, 0, 1, NULL),
(36, 'county', 'Bomet', 'bomet', NULL, 36, 'Rift Valley', -0.783300, 35.350000, 0, 1036, NULL, 0, 1, NULL),
(37, 'county', 'Kakamega', 'kakamega', NULL, 37, 'Western', 0.282700, 34.751900, 0, 1037, NULL, 0, 1, NULL),
(38, 'county', 'Vihiga', 'vihiga', NULL, 38, 'Western', 0.066700, 34.716700, 0, 1038, NULL, 0, 1, NULL),
(39, 'county', 'Bungoma', 'bungoma', NULL, 39, 'Western', 0.563500, 34.560600, 0, 1039, NULL, 0, 1, NULL),
(40, 'county', 'Busia', 'busia', NULL, 40, 'Western', 0.460800, 34.111500, 0, 1040, NULL, 0, 1, NULL),
(41, 'county', 'Siaya', 'siaya', NULL, 41, 'Nyanza', 0.060700, 34.288100, 0, 1041, NULL, 0, 1, NULL),
(42, 'county', 'Kisumu', 'kisumu', NULL, 42, 'Nyanza', -0.091700, 34.768000, 1, 30, NULL, 1, 1, NULL),
(43, 'county', 'Homa Bay', 'homa-bay', NULL, 43, 'Nyanza', -0.527300, 34.457100, 0, 1043, NULL, 0, 1, NULL),
(44, 'county', 'Migori', 'migori', NULL, 44, 'Nyanza', -1.063400, 34.473100, 0, 1044, NULL, 0, 1, NULL),
(45, 'county', 'Kisii', 'kisii', NULL, 45, 'Nyanza', -0.681700, 34.766700, 0, 1045, NULL, 0, 1, NULL),
(46, 'county', 'Nyamira', 'nyamira', NULL, 46, 'Nyanza', -0.566700, 34.950000, 0, 1046, NULL, 0, 1, NULL),
(47, 'county', 'Nairobi', 'nairobi', NULL, 47, 'Nairobi', -1.292100, 36.821900, 1, 10, NULL, 1, 1, NULL),
(48, 'town', 'Mombasa Island', 'mombasa-island', 1, 1, 'Coast', -4.043500, 39.668200, 0, 2001, NULL, 0, 1, NULL),
(49, 'town', 'Kwale Town', 'kwale-town', 2, 2, 'Coast', -4.173700, 39.452100, 0, 2002, NULL, 0, 1, NULL),
(50, 'town', 'Kilifi Town', 'kilifi-town', 3, 3, 'Coast', -3.510700, 39.909300, 0, 2003, NULL, 0, 1, NULL),
(51, 'town', 'Hola', 'hola', 4, 4, 'Coast', -1.499700, 40.031600, 0, 2004, NULL, 0, 1, NULL),
(52, 'town', 'Lamu Town', 'lamu-town', 5, 5, 'Coast', -2.271700, 40.902000, 0, 2005, NULL, 0, 1, NULL),
(53, 'town', 'Voi', 'voi', 6, 6, 'Coast', -3.396100, 38.556000, 0, 2006, NULL, 0, 1, NULL),
(54, 'town', 'Garissa Town', 'garissa-town', 7, 7, 'North Eastern', -0.453200, 39.646100, 0, 2007, NULL, 0, 1, NULL),
(55, 'town', 'Wajir Town', 'wajir-town', 8, 8, 'North Eastern', 1.747100, 40.057300, 0, 2008, NULL, 0, 1, NULL),
(56, 'town', 'Mandera Town', 'mandera-town', 9, 9, 'North Eastern', 3.936600, 41.867000, 0, 2009, NULL, 0, 1, NULL),
(57, 'town', 'Marsabit Town', 'marsabit-town', 10, 10, 'Eastern', 2.328400, 37.989900, 0, 2010, NULL, 0, 1, NULL),
(58, 'town', 'Isiolo Town', 'isiolo-town', 11, 11, 'Eastern', 0.354600, 37.582200, 0, 2011, NULL, 0, 1, NULL),
(59, 'town', 'Meru Town', 'meru-town', 12, 12, 'Eastern', 0.048000, 37.655900, 0, 2012, NULL, 0, 1, NULL),
(60, 'town', 'Chuka', 'chuka', 13, 13, 'Eastern', -0.333300, 37.650000, 0, 2013, NULL, 0, 1, NULL),
(61, 'town', 'Embu Town', 'embu-town', 14, 14, 'Eastern', -0.538900, 37.459600, 0, 2014, NULL, 0, 1, NULL),
(62, 'town', 'Kitui Town', 'kitui-town', 15, 15, 'Eastern', -1.366700, 38.010600, 0, 2015, NULL, 0, 1, NULL),
(63, 'town', 'Machakos Town', 'machakos-town', 16, 16, 'Eastern', -1.517700, 37.263400, 0, 2016, NULL, 0, 1, NULL),
(64, 'town', 'Wote', 'wote', 17, 17, 'Eastern', -1.803900, 37.620200, 0, 2017, NULL, 0, 1, NULL),
(65, 'town', 'Ol Kalou', 'ol-kalou', 18, 18, 'Central', -0.266700, 36.383300, 0, 2018, NULL, 0, 1, NULL),
(66, 'town', 'Nyeri Town', 'nyeri-town', 19, 19, 'Central', -0.420100, 36.947600, 0, 2019, NULL, 0, 1, NULL),
(67, 'town', 'Kerugoya', 'kerugoya', 20, 20, 'Central', -0.498900, 37.280300, 0, 2020, NULL, 0, 1, NULL),
(68, 'town', 'Murang\'a Town', 'murang-a-town', 21, 21, 'Central', -0.721000, 37.152600, 0, 2021, NULL, 0, 1, NULL),
(69, 'town', 'Kiambu Town', 'kiambu-town', 22, 22, 'Central', -1.171400, 36.835600, 0, 2022, NULL, 0, 1, NULL),
(70, 'town', 'Lodwar', 'lodwar', 23, 23, 'Rift Valley', 3.119000, 35.597000, 0, 2023, NULL, 0, 1, NULL),
(71, 'town', 'Kapenguria', 'kapenguria', 24, 24, 'Rift Valley', 1.238900, 35.111900, 0, 2024, NULL, 0, 1, NULL),
(72, 'town', 'Maralal', 'maralal', 25, 25, 'Rift Valley', 1.100000, 36.700000, 0, 2025, NULL, 0, 1, NULL),
(73, 'town', 'Kitale', 'kitale', 26, 26, 'Rift Valley', 1.019100, 34.997700, 0, 2026, NULL, 0, 1, NULL),
(74, 'town', 'Eldoret', 'eldoret', 27, 27, 'Rift Valley', 0.514300, 35.269800, 0, 2027, NULL, 0, 1, NULL),
(75, 'town', 'Iten', 'iten', 28, 28, 'Rift Valley', 0.566700, 35.516700, 0, 2028, NULL, 0, 1, NULL),
(76, 'town', 'Kapsabet', 'kapsabet', 29, 29, 'Rift Valley', 0.200000, 35.100000, 0, 2029, NULL, 0, 1, NULL),
(77, 'town', 'Kabarnet', 'kabarnet', 30, 30, 'Rift Valley', 0.466700, 35.966700, 0, 2030, NULL, 0, 1, NULL),
(78, 'town', 'Nanyuki', 'nanyuki', 31, 31, 'Rift Valley', 0.016700, 37.066700, 0, 2031, NULL, 0, 1, NULL),
(79, 'town', 'Nakuru Town', 'nakuru-town', 32, 32, 'Rift Valley', -0.303100, 36.080000, 0, 2032, NULL, 0, 1, NULL),
(80, 'town', 'Narok Town', 'narok-town', 33, 33, 'Rift Valley', -1.083300, 35.866700, 0, 2033, NULL, 0, 1, NULL),
(81, 'town', 'Kajiado Town', 'kajiado-town', 34, 34, 'Rift Valley', -1.850000, 36.783300, 0, 2034, NULL, 0, 1, NULL),
(82, 'town', 'Kericho Town', 'kericho-town', 35, 35, 'Rift Valley', -0.368900, 35.286300, 0, 2035, NULL, 0, 1, NULL),
(83, 'town', 'Bomet Town', 'bomet-town', 36, 36, 'Rift Valley', -0.783300, 35.350000, 0, 2036, NULL, 0, 1, NULL),
(84, 'town', 'Kakamega Town', 'kakamega-town', 37, 37, 'Western', 0.282700, 34.751900, 0, 2037, NULL, 0, 1, NULL),
(85, 'town', 'Mbale', 'mbale', 38, 38, 'Western', 0.066700, 34.716700, 0, 2038, NULL, 0, 1, NULL),
(86, 'town', 'Bungoma Town', 'bungoma-town', 39, 39, 'Western', 0.563500, 34.560600, 0, 2039, NULL, 0, 1, NULL),
(87, 'town', 'Busia Town', 'busia-town', 40, 40, 'Western', 0.460800, 34.111500, 0, 2040, NULL, 0, 1, NULL),
(88, 'town', 'Siaya Town', 'siaya-town', 41, 41, 'Nyanza', 0.060700, 34.288100, 0, 2041, NULL, 0, 1, NULL),
(89, 'town', 'Kisumu City', 'kisumu-city', 42, 42, 'Nyanza', -0.091700, 34.768000, 0, 2042, NULL, 0, 1, NULL),
(90, 'town', 'Homa Bay Town', 'homa-bay-town', 43, 43, 'Nyanza', -0.527300, 34.457100, 0, 2043, NULL, 0, 1, NULL),
(91, 'town', 'Migori Town', 'migori-town', 44, 44, 'Nyanza', -1.063400, 34.473100, 0, 2044, NULL, 0, 1, NULL),
(92, 'town', 'Kisii Town', 'kisii-town', 45, 45, 'Nyanza', -0.681700, 34.766700, 0, 2045, NULL, 0, 1, NULL),
(93, 'town', 'Nyamira Town', 'nyamira-town', 46, 46, 'Nyanza', -0.566700, 34.950000, 0, 2046, NULL, 0, 1, NULL),
(94, 'town', 'Nairobi CBD', 'nairobi-cbd', 47, 47, 'Nairobi', -1.292100, 36.821900, 0, 2047, NULL, 0, 1, NULL),
(95, 'town', 'Westlands', 'westlands', 47, 47, 'Nairobi', -1.267600, 36.810800, 1, 15, NULL, 1, 1, NULL),
(96, 'town', 'Kilimani', 'kilimani', 47, 47, 'Nairobi', -1.289900, 36.786700, 1, 25, NULL, 1, 1, NULL),
(97, 'town', 'Karen', 'karen', 47, 47, 'Nairobi', -1.319700, 36.711700, 1, 35, NULL, 1, 1, NULL),
(98, 'town', 'Lavington', 'lavington', 47, 47, 'Nairobi', -1.283300, 36.766700, 0, 3000, NULL, 0, 1, NULL),
(99, 'town', 'Kileleshwa', 'kileleshwa', 47, 47, 'Nairobi', -1.283300, 36.783300, 0, 3000, NULL, 0, 1, NULL),
(100, 'town', 'Kasarani', 'kasarani', 47, 47, 'Nairobi', -1.220000, 36.897000, 0, 3000, NULL, 0, 1, NULL),
(101, 'town', 'Embakasi', 'embakasi', 47, 47, 'Nairobi', -1.320000, 36.900000, 0, 3000, NULL, 0, 1, NULL),
(102, 'town', 'Langata', 'langata', 47, 47, 'Nairobi', -1.366700, 36.750000, 0, 3000, NULL, 0, 1, NULL),
(103, 'town', 'Roysambu', 'roysambu', 47, 47, 'Nairobi', -1.218600, 36.890000, 0, 3000, NULL, 0, 1, NULL),
(104, 'town', 'South B', 'south-b', 47, 47, 'Nairobi', -1.310000, 36.833300, 0, 3000, NULL, 0, 1, NULL),
(105, 'town', 'Eastleigh', 'eastleigh', 47, 47, 'Nairobi', -1.275000, 36.850000, 0, 3000, NULL, 0, 1, NULL),
(106, 'town', 'Parklands', 'parklands', 47, 47, 'Nairobi', -1.260000, 36.820000, 0, 3000, NULL, 0, 1, NULL),
(107, 'town', 'Nyali', 'nyali', 1, 1, 'Coast', -4.020000, 39.715000, 1, 45, NULL, 1, 1, NULL),
(108, 'town', 'Bamburi', 'bamburi', 1, 1, 'Coast', -3.983000, 39.730000, 0, 3000, NULL, 0, 1, NULL),
(109, 'town', 'Likoni', 'likoni', 1, 1, 'Coast', -4.090000, 39.660000, 0, 3000, NULL, 0, 1, NULL),
(110, 'town', 'Mtwapa', 'mtwapa', 1, 1, 'Coast', -3.933300, 39.733300, 0, 3000, NULL, 0, 1, NULL),
(111, 'town', 'Thika', 'thika', 22, 22, 'Central', -1.033200, 37.069300, 1, 55, NULL, 1, 1, NULL),
(112, 'town', 'Ruiru', 'ruiru', 22, 22, 'Central', -1.150000, 36.960000, 0, 3000, NULL, 0, 1, NULL),
(113, 'town', 'Kikuyu', 'kikuyu', 22, 22, 'Central', -1.250000, 36.666700, 0, 3000, NULL, 0, 1, NULL),
(114, 'town', 'Limuru', 'limuru', 22, 22, 'Central', -1.100000, 36.650000, 0, 3000, NULL, 0, 1, NULL),
(115, 'town', 'Ruaka', 'ruaka', 22, 22, 'Central', -1.200000, 36.783300, 1, 65, NULL, 1, 1, NULL),
(116, 'town', 'Juja', 'juja', 22, 22, 'Central', -1.100000, 37.016700, 0, 3000, NULL, 0, 1, NULL),
(117, 'town', 'Athi River', 'athi-river', 16, 16, 'Eastern', -1.456000, 36.978000, 1, 75, NULL, 1, 1, NULL),
(118, 'town', 'Kangundo', 'kangundo', 16, 16, 'Eastern', -1.300000, 37.350000, 0, 3000, NULL, 0, 1, NULL),
(119, 'town', 'Kitengela', 'kitengela', 34, 34, 'Rift Valley', -1.475000, 36.950000, 1, 85, NULL, 1, 1, NULL),
(120, 'town', 'Ongata Rongai', 'ongata-rongai', 34, 34, 'Rift Valley', -1.396000, 36.760000, 1, 95, NULL, 1, 1, NULL),
(121, 'town', 'Ngong', 'ngong', 34, 34, 'Rift Valley', -1.366700, 36.650000, 0, 3000, NULL, 0, 1, NULL),
(122, 'town', 'Kiserian', 'kiserian', 34, 34, 'Rift Valley', -1.416700, 36.683300, 0, 3000, NULL, 0, 1, NULL),
(123, 'town', 'Namanga', 'namanga', 34, 34, 'Rift Valley', -2.550000, 36.783300, 0, 3000, NULL, 0, 1, NULL),
(124, 'town', 'Naivasha', 'naivasha', 32, 32, 'Rift Valley', -0.716700, 36.433300, 1, 105, NULL, 1, 1, NULL),
(125, 'town', 'Gilgil', 'gilgil', 32, 32, 'Rift Valley', -0.500000, 36.316700, 0, 3000, NULL, 0, 1, NULL),
(126, 'town', 'Molo', 'molo', 32, 32, 'Rift Valley', -0.250000, 35.733300, 0, 3000, NULL, 0, 1, NULL),
(127, 'town', 'Njoro', 'njoro', 32, 32, 'Rift Valley', -0.333300, 35.933300, 0, 3000, NULL, 0, 1, NULL),
(128, 'town', 'Maseno', 'maseno', 42, 42, 'Nyanza', -0.005000, 34.602500, 0, 3000, NULL, 0, 1, NULL),
(129, 'town', 'Ahero', 'ahero', 42, 42, 'Nyanza', -0.170000, 34.916700, 0, 3000, NULL, 0, 1, NULL),
(130, 'town', 'Malindi', 'malindi', 3, 3, 'Coast', -3.219200, 40.116900, 1, 115, NULL, 1, 1, NULL),
(131, 'town', 'Watamu', 'watamu', 3, 3, 'Coast', -3.355000, 40.023800, 0, 3000, NULL, 0, 1, NULL),
(132, 'town', 'Diani Beach', 'diani-beach', 2, 2, 'Coast', -4.316700, 39.583300, 1, 125, NULL, 1, 1, NULL),
(133, 'town', 'Ukunda', 'ukunda', 2, 2, 'Coast', -4.283300, 39.566700, 0, 3000, NULL, 0, 1, NULL),
(134, 'town', 'Msambweni', 'msambweni', 2, 2, 'Coast', -4.466700, 39.483300, 0, 3000, NULL, 0, 1, NULL),
(135, 'town', 'Karatina', 'karatina', 19, 19, 'Central', -0.483300, 37.133300, 0, 3000, NULL, 0, 1, NULL),
(136, 'town', 'Othaya', 'othaya', 19, 19, 'Central', -0.550000, 36.950000, 0, 3000, NULL, 0, 1, NULL),
(137, 'town', 'Rumuruti', 'rumuruti', 31, 31, 'Rift Valley', 0.266700, 36.533300, 0, 3000, NULL, 0, 1, NULL),
(138, 'town', 'Maua', 'maua', 12, 12, 'Eastern', 0.233300, 37.933300, 0, 3000, NULL, 0, 1, NULL),
(139, 'town', 'Runyenjes', 'runyenjes', 14, 14, 'Eastern', -0.416700, 37.566700, 0, 3000, NULL, 0, 1, NULL),
(140, 'town', 'Maragua', 'maragua', 21, 21, 'Central', -0.783300, 37.133300, 0, 3000, NULL, 0, 1, NULL),
(141, 'town', 'Mumias', 'mumias', 37, 37, 'Western', 0.333300, 34.483300, 0, 3000, NULL, 0, 1, NULL),
(142, 'town', 'Webuye', 'webuye', 39, 39, 'Western', 0.616700, 34.766700, 0, 3000, NULL, 0, 1, NULL),
(143, 'town', 'Kimilili', 'kimilili', 39, 39, 'Western', 0.783300, 34.716700, 0, 3000, NULL, 0, 1, NULL),
(144, 'town', 'Malaba', 'malaba', 40, 40, 'Western', 0.635000, 34.275000, 0, 3000, NULL, 0, 1, NULL),
(145, 'town', 'Rongo', 'rongo', 44, 44, 'Nyanza', -0.766700, 34.600000, 0, 3000, NULL, 0, 1, NULL),
(146, 'town', 'Mbita', 'mbita', 43, 43, 'Nyanza', -0.422200, 34.204800, 0, 3000, NULL, 0, 1, NULL),
(147, 'town', 'Bondo', 'bondo', 41, 41, 'Nyanza', 0.233300, 34.266700, 0, 3000, NULL, 0, 1, NULL),
(148, 'town', 'Litein', 'litein', 35, 35, 'Rift Valley', -0.583300, 35.200000, 0, 3000, NULL, 0, 1, NULL),
(149, 'town', 'Taveta', 'taveta', 6, 6, 'Coast', -3.400000, 37.683300, 0, 3000, NULL, 0, 1, NULL),
(150, 'town', 'Mtito Andei', 'mtito-andei', 17, 17, 'Eastern', -2.683300, 38.166700, 0, 3000, NULL, 0, 1, NULL),
(151, 'town', 'Burnt Forest', 'burnt-forest', 27, 27, 'Rift Valley', 0.433300, 35.366700, 0, 3000, NULL, 0, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `id` int(10) UNSIGNED NOT NULL,
  `user1_id` int(10) UNSIGNED NOT NULL,
  `user2_id` int(10) UNSIGNED NOT NULL,
  `matched_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `unmatched_by` int(10) UNSIGNED DEFAULT NULL,
  `unmatched_at` datetime DEFAULT NULL,
  `user1_seen` tinyint(1) NOT NULL DEFAULT 0,
  `user2_seen` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`id`, `user1_id`, `user2_id`, `matched_at`, `is_active`, `unmatched_by`, `unmatched_at`, `user1_seen`, `user2_seen`) VALUES
(1, 9001, 9025, '2026-10-16 12:13:23', 1, NULL, NULL, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `media_files`
--

CREATE TABLE `media_files` (
  `id` int(10) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `thumb_path` varchar(255) DEFAULT NULL,
  `title` varchar(120) NOT NULL DEFAULT '',
  `kind` enum('image','video') NOT NULL DEFAULT 'image',
  `width` smallint(5) UNSIGNED DEFAULT NULL,
  `height` smallint(5) UNSIGNED DEFAULT NULL,
  `file_size` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `media_files`
--

INSERT INTO `media_files` (`id`, `path`, `thumb_path`, `title`, `kind`, `width`, `height`, `file_size`, `uploaded_by`, `created_at`) VALUES
(1, 'site/a5fcb4436effc3e4.png', 'library/_thumbs/9084bff679c6b9fd.png', 'Glossy ConnectHub Connection Logo', 'image', 1600, 533, 429464, 1, '2026-10-16 11:05:35'),
(2, 'site/11a1895a4bc60514.png', 'library/_thumbs/b7a45eac73cf3259.png', 'Glossy ConnectHub Connection Logo', 'image', 1600, 533, 429464, 1, '2026-10-16 11:05:42'),
(3, 'site/eb09ad23d2407863.png', 'library/_thumbs/3b0919ed6b7b1cde.png', 'Glossy Intertwined Heart Figures', 'image', 1356, 1159, 738949, 1, '2026-10-16 11:05:51'),
(4, 'site/2c0cb6f89086df9f.png', NULL, 'Glossy Intertwined Love Icon', 'image', 512, 512, 192646, 1, '2026-10-16 11:05:59'),
(5, 'site/4ad34c0215314573.png', 'library/_thumbs/015d3497d6e092fb.png', 'ConnectHub Heart Embrace Logo', 'image', 1600, 533, 235624, 1, '2026-10-16 11:06:15'),
(6, 'site/1b230b87530cca84.png', 'library/_thumbs/82a35407575ecaae.png', 'Glossy ConnectHub Connection Logo', 'image', 1600, 533, 429464, 1, '2026-10-16 11:06:32'),
(7, 'site/701baa472b6bc25c.jpg', 'library/_thumbs/f0738dcced59ecbd.jpg', 'Glossy Intertwined Heart Figures', 'image', 1356, 1159, 89166, 1, '2026-10-16 13:15:28'),
(8, 'site/3dc7c95ead56a575.png', 'library/_thumbs/88885b536daf9acb.png', 'ConnectHub Heart Embrace Logo', 'image', 1600, 533, 235624, 1, '2026-10-16 13:18:06'),
(9, 'hero/1c38000c553fc0d4.jpg', 'library/_thumbs/91d4020a777a9cb8.jpg', 'Glossy Intertwined Heart Emblem', 'image', 1254, 1254, 114641, 1, '2026-10-16 13:51:08'),
(10, 'landing/6ee5e9ab0a624f76.jpg', 'library/_thumbs/596e19e00e2e780b.jpg', 'Create Your Profile Showcase', 'image', 1374, 1145, 149027, 1, '2026-10-16 19:22:18'),
(11, 'landing/2a247f02f35c8cdc.jpg', 'library/_thumbs/b9de72786b11886f.jpg', 'Pastel Nearby Dating Map Interface', 'image', 1374, 1145, 206126, 1, '2026-10-16 19:22:18'),
(12, 'landing/24a6f122b2b1e6b7.jpg', 'library/_thumbs/8faede5666436788.jpg', '3D Dating App Match Scene', 'image', 1374, 1145, 172689, 1, '2026-10-16 19:22:18'),
(13, 'landing/e2b61758f5f0c659.jpg', 'library/_thumbs/b9806858b2abf1a7.jpg', 'Friendly Dating App Safety Hub', 'image', 1536, 1024, 204904, 1, '2026-10-16 19:23:28'),
(14, 'landing/3065324248d992ec.jpg', 'library/_thumbs/8d8875f60633cb1a.jpg', 'Pastel Online Dating Safety Illustration', 'image', 1024, 1536, 152692, 1, '2026-10-16 19:26:08'),
(15, 'landing/5d5d7a2da34f87fc.jpg', 'library/_thumbs/d846f2015a9108f8.jpg', 'Glamorous Travel Concierge Hero Composition', 'image', 1374, 1145, 204183, 1, '2026-10-16 19:27:41'),
(16, 'hero/0b33ec6247dda791.jpg', 'library/_thumbs/aeb0793cdb2ba7d8.jpg', 'Sunset Rooftop City Romance', 'image', 1024, 1536, 233050, 1, '2026-10-16 19:41:34'),
(17, 'hero/028d74bec445aa4a.jpg', 'library/_thumbs/5368a27ff35636bd.jpg', 'Romantic Tropical Sunset Stroll', 'image', 1024, 1536, 277241, 1, '2026-10-16 19:41:42'),
(18, 'hero/def30aeef3b3336d.jpg', 'library/_thumbs/c150ab500eeed5ce.jpg', '3D Dating App Match Scene', 'image', 1374, 1145, 172689, 1, '2026-10-16 19:45:56'),
(19, 'hero/8869e0254b0f9e81.jpg', 'library/_thumbs/8eab90a4d5af0f2f.jpg', 'Modern Dating App Romance', 'image', 1145, 1374, 206548, 1, '2026-10-16 19:52:24'),
(20, 'hero/e8bf098bec88889e.jpg', 'library/_thumbs/6330ad83443a91db.jpg', 'A Romantic 3D Dating Map', 'image', 1145, 1374, 223703, 1, '2026-10-16 19:52:30'),
(21, 'hero/9013919f50afddf1.jpg', 'library/_thumbs/55e3499434214f26.jpg', 'Romantic Matchmaking App Dreams', 'image', 1145, 1374, 219212, 1, '2026-10-16 20:07:47'),
(22, 'hero/417e7d6e2485e501.jpg', 'library/_thumbs/c07280a17615cd17.jpg', 'Connected by Love Dating App Couple', 'image', 1672, 941, 179960, 1, '2026-10-16 20:07:58'),
(23, 'hero/d2861aa0d4a2cab6.jpg', 'library/_thumbs/5cb05869f614e3ab.jpg', 'Glossy 3D Dating App Hearts', 'image', 1254, 1254, 229710, 1, '2026-10-16 20:08:53'),
(24, 'blog/ad7e37323e375f4f.jpg', 'library/_thumbs/80ce932e12238200.jpg', 'Cheerful Friends Coffee Meetup', 'image', 1536, 1024, 315945, 1, '2026-10-16 20:14:13'),
(25, 'blog/90eb066ac7d1d465.jpg', 'library/_thumbs/6c25ad230deefbdf.jpg', 'Secure Video Dating Connection', 'image', 1536, 1024, 296947, 1, '2026-10-16 20:15:25'),
(26, 'blog/869be3a419e44195.jpg', 'library/_thumbs/5e168c0b0265571b.jpg', 'Verified Connections Safe Dating Profiles', 'image', 1536, 1024, 316908, 1, '2026-10-16 20:30:35'),
(27, 'blog/a5559a7cf9a1cbb7.jpg', 'library/_thumbs/db361114b70e2830.jpg', 'Modern Dating Texting Guide', 'image', 1536, 1024, 228427, 1, '2026-10-16 20:32:00'),
(28, 'blog/12f3550ed64907c0.jpg', 'library/_thumbs/73f2836cd3fa53ce.jpg', 'Signs They’re Into You', 'image', 1536, 1024, 209944, 1, '2026-10-16 20:45:22'),
(29, 'blog/cdc80af18518221a.jpg', 'library/_thumbs/c83eb7821dcd8024.jpg', 'Think Before Sending Money Online', 'image', 1536, 1024, 275566, 1, '2026-10-16 20:46:14'),
(30, 'blog/907ae955ec0265f3.jpg', 'library/_thumbs/9e4abfa09e2fb1f3.jpg', 'Verified Dating Profile Interface', 'image', 1536, 1024, 233168, 1, '2026-10-16 20:48:19'),
(31, 'blog/73fa7a5ee80bbc22.jpg', 'library/_thumbs/62f1bef5f075fad7.jpg', 'Kenyan Online Romance Scam Warning', 'image', 1536, 1024, 291454, 1, '2026-10-16 20:52:12'),
(32, 'blog/338684a3cf8c2237.jpg', 'library/_thumbs/0512bb158f477354.jpg', 'Dating App Privacy Protection', 'image', 1536, 1024, 229123, 1, '2026-10-16 20:53:27'),
(33, 'blog/25e562d53e773353.jpg', 'library/_thumbs/c3c0a7ba48ab2253.jpg', 'Kenyan Online Dating Guide', 'image', 1536, 1024, 282856, 1, '2026-10-16 20:55:57'),
(34, 'blog/9dbc479a8dda0aa3.jpg', 'library/_thumbs/9a799580b8f8d7f9.jpg', 'Safe Connections Across Borders', 'image', 1536, 1024, 289597, 1, '2026-10-16 20:57:06'),
(35, 'blog/d16a6efcda633fc4.jpg', 'library/_thumbs/8cb8c9d77c6a60d9.jpg', 'Love Across Nairobi and Paris', 'image', 1536, 1024, 277491, 1, '2026-10-16 21:00:25'),
(36, 'blog/0b783cabb980349c.jpg', 'library/_thumbs/3e64909ff9cd2c57.jpg', 'Warm Kenyan Family Introduction', 'image', 1536, 1024, 291112, 1, '2026-10-16 21:01:54'),
(37, 'blog/2e7c152d5bf1974a.jpg', 'library/_thumbs/fafee5843f15c6b5.jpg', 'Crafting a Meaningful Dating Profile', 'image', 1536, 1024, 283501, 1, '2026-10-16 21:03:54'),
(38, 'blog/4d2f4605c765f19b.jpg', 'library/_thumbs/0aad609af5ffade0.jpg', 'Smart Dating Safety Check', 'image', 1536, 1024, 291466, 1, '2026-10-16 21:04:22'),
(39, 'blog/15f9e2bb9d262bc2.jpg', 'library/_thumbs/41b5a9bed4928ea5.jpg', 'Warm Conversations, Meaningful Connections', 'image', 1536, 1024, 324097, 1, '2026-10-16 21:06:05'),
(40, 'blog/ef83f20f0e137738.jpg', 'library/_thumbs/e1719f453b2e0655.jpg', 'Spotting Fake Dating Profiles Safely', 'image', 1536, 1024, 295135, 1, '2026-10-16 21:08:04'),
(41, 'blog/15c5e598f554b254.jpg', 'library/_thumbs/2818d4ecc3ae1818.jpg', 'Making Friends Over Coffee', 'image', 1536, 1024, 287735, 1, '2026-10-16 21:10:37'),
(42, 'blog/51c5c5d324900d69.jpg', 'library/_thumbs/6c2ea6d9c95c330e.jpg', 'A Thoughtful Guide to New Beginnings', 'image', 1536, 1024, 284363, 1, '2026-10-16 21:12:28'),
(43, 'blog/d1cbf0543a99d54d.jpg', 'library/_thumbs/7850411b91b65581.jpg', 'Reflective Rejection Healing at Sunset', 'image', 1536, 1024, 323082, 1, '2026-10-16 21:16:41'),
(44, 'blog/0e7c78cbe8c0ec0e.jpg', 'library/_thumbs/1f4d4b7e5c6746da.jpg', 'Warm Dating Message Guide with Chat Mockup', 'image', 1672, 941, 266354, 1, '2026-10-16 21:29:34'),
(45, 'blog/aeae321826c1e813.jpg', 'library/_thumbs/0d9723d106378af6.jpg', 'Cozy First Message Ideas', 'image', 1672, 941, 274483, 1, '2026-10-16 21:31:53'),
(46, 'library/181f18a79ce9e25d.png', 'library/_thumbs/187303dc14f39563.png', 'ConnectHub Heart Embrace Logo', 'image', 2172, 724, 391307, 1, '2026-10-16 23:50:40'),
(47, 'library/52e31e52f4861a2f.jpg', 'library/_thumbs/bd67f4ca6145d73f.jpg', '165800', 'image', 736, 981, 115157, 1, '2026-11-12 09:47:06');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `type` enum('text','image','system') NOT NULL DEFAULT 'text',
  `content` text DEFAULT NULL,
  `media_path` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `edited_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `type`, `content`, `media_path`, `is_read`, `read_at`, `is_deleted`, `edited_at`, `created_at`) VALUES
(1, 1, 9001, 'text', 'What are you listening to on repeat right now?', NULL, 1, '2026-10-16 12:13:47', 0, NULL, '2026-10-16 12:13:44'),
(2, 1, 9025, 'text', 'Lots of Amapiano lately. Currently got ZeP on repeat. What are you listening to?', NULL, 1, '2026-10-16 12:14:55', 0, NULL, '2026-10-16 12:14:54'),
(3, 1, 9001, 'text', 'Medicine by jaywilliz', NULL, 1, '2026-10-16 12:15:38', 0, NULL, '2026-10-16 12:15:36'),
(4, 1, 9001, 'system', '{\"kind\":\"gift\",\"gift\":\"Rose\",\"emoji\":\"🌹\",\"note\":\"\",\"gift_id\":2}', NULL, 1, '2026-10-16 12:16:30', 0, NULL, '2026-10-16 12:16:27'),
(5, 1, 9025, 'text', 'Thank you for the gift', NULL, 1, '2026-10-16 12:16:42', 0, NULL, '2026-10-16 12:16:41'),
(6, 1, 9025, 'text', 'Welcome', NULL, 1, '2026-10-16 12:16:48', 0, NULL, '2026-10-16 12:16:47'),
(7, 1, 9001, 'text', 'Where are you at', NULL, 1, '2026-10-16 15:01:57', 0, NULL, '2026-10-16 13:47:29'),
(8, 1, 9025, 'text', 'Nairobi..im free today', NULL, 1, '2026-10-16 15:46:31', 0, NULL, '2026-10-16 15:02:23'),
(9, 2, 9001, 'text', 'Hello', NULL, 1, '2026-10-16 15:50:31', 0, NULL, '2026-10-16 15:45:45'),
(10, 2, 2, 'text', 'Hi', NULL, 1, '2026-10-16 22:03:18', 0, NULL, '2026-10-16 15:50:35'),
(11, 2, 9001, 'system', '{\"kind\":\"gift\",\"gift\":\"Rose\",\"emoji\":\"🌹\",\"note\":\"\",\"gift_id\":3}', NULL, 1, '2026-10-16 15:52:36', 0, NULL, '2026-10-16 15:51:07'),
(12, 2, 2, 'text', 'Thanks', NULL, 1, '2026-10-16 22:03:18', 0, NULL, '2026-10-16 15:52:49'),
(13, 2, 9001, 'text', 'Whatchu doing', NULL, 0, NULL, 0, NULL, '2026-10-16 22:03:36'),
(14, 1, 9001, 'text', 'How was your weekend?', NULL, 0, NULL, 0, NULL, '2026-11-08 22:34:16'),
(15, 1, 9001, 'text', '😎', NULL, 0, NULL, 0, NULL, '2026-11-08 22:34:30'),
(16, 3, 9001, 'text', 'Hello', NULL, 0, NULL, 0, NULL, '2026-11-12 20:27:46');

-- --------------------------------------------------------

--
-- Table structure for table `message_abuse_events`
--

CREATE TABLE `message_abuse_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED DEFAULT NULL,
  `reason` varchar(40) NOT NULL,
  `content_hash` char(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mpesa_b2c_requests`
--

CREATE TABLE `mpesa_b2c_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `kind` varchar(20) NOT NULL,
  `ref_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `phone` varchar(20) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `remarks` varchar(100) DEFAULT NULL,
  `originator_id` varchar(60) NOT NULL,
  `conversation_id` varchar(80) DEFAULT NULL,
  `status` enum('queued','sent','completed','failed','timeout') NOT NULL DEFAULT 'queued',
  `receipt` varchar(30) DEFAULT NULL,
  `result_code` varchar(12) DEFAULT NULL,
  `result_desc` varchar(255) DEFAULT NULL,
  `receiver_name` varchar(120) DEFAULT NULL,
  `requested_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mpesa_c2b_transactions`
--

CREATE TABLE `mpesa_c2b_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `trans_id` varchar(30) NOT NULL,
  `trans_type` varchar(30) DEFAULT NULL,
  `trans_time` varchar(20) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `shortcode` varchar(20) DEFAULT NULL,
  `bill_ref` varchar(60) DEFAULT NULL,
  `msisdn` varchar(80) DEFAULT NULL,
  `payer_name` varchar(120) DEFAULT NULL,
  `org_balance` varchar(30) DEFAULT NULL,
  `status` enum('matched','unmatched','underpaid','duplicate','manual') NOT NULL DEFAULT 'unmatched',
  `payment_id` int(10) UNSIGNED DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nav_items`
--

CREATE TABLE `nav_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `menu` varchar(30) NOT NULL,
  `label` varchar(60) NOT NULL,
  `url` varchar(255) NOT NULL,
  `icon` varchar(40) DEFAULT NULL,
  `audience` enum('any','guest','user','host','premium') NOT NULL DEFAULT 'any',
  `badge_key` varchar(30) DEFAULT NULL,
  `new_tab` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nav_items`
--

INSERT INTO `nav_items` (`id`, `menu`, `label`, `url`, `icon`, `audience`, `badge_key`, `new_tab`, `sort_order`, `is_active`) VALUES
(1, 'header', 'Browse', '/browse', 'compass', 'any', NULL, 0, 1, 1),
(2, 'header', 'Hosts', '/hosts', 'concierge-bell', 'any', NULL, 0, 2, 1),
(3, 'header', 'Pricing', '/pricing', 'sparkles', 'any', NULL, 0, 3, 1),
(4, 'header', 'Blog', '/blog', 'newspaper', 'any', NULL, 0, 4, 1),
(5, 'header', 'Help', '/faq', 'circle-help', 'any', NULL, 0, 5, 1),
(6, 'footer_explore', 'Browse members', '/browse', 'compass', 'any', NULL, 0, 1, 1),
(7, 'footer_explore', 'Hosts', '/hosts', 'concierge-bell', 'any', NULL, 0, 2, 1),
(8, 'footer_explore', 'Pricing', '/pricing', 'sparkles', 'any', NULL, 0, 3, 1),
(9, 'footer_explore', 'Blog', '/blog', 'newspaper', 'any', NULL, 0, 4, 1),
(10, 'footer_company', 'About us', '/about', 'info', 'any', NULL, 0, 1, 1),
(11, 'footer_company', 'Contact', '/contact', 'mail', 'any', NULL, 0, 2, 1),
(12, 'footer_company', 'Help centre', '/faq', 'circle-help', 'any', NULL, 0, 3, 1),
(13, 'footer_company', 'Safety tips', '/safety-tips', 'shield-check', 'any', NULL, 0, 4, 1),
(14, 'app_sidebar', 'Discover', '/discover', 'compass', 'user', NULL, 0, 1, 1),
(15, 'app_sidebar', 'Search', '/search', 'search', 'user', NULL, 0, 2, 1),
(16, 'app_sidebar', 'Likes', '/likes', 'heart', 'user', 'likes', 0, 3, 1),
(17, 'app_sidebar', 'Matches', '/matches', 'matches', 'user', 'matches', 0, 4, 1),
(18, 'app_sidebar', 'Messages', '/messages', 'message-circle', 'user', 'messages', 0, 5, 1),
(19, 'app_sidebar', 'Visitors', '/visitors', 'eye', 'user', NULL, 0, 6, 1),
(20, 'app_sidebar', 'Favourites', '/favorites', 'star', 'user', NULL, 0, 7, 1),
(21, 'app_sidebar', 'Hosts', '/hosts', 'concierge-bell', 'user', NULL, 0, 8, 1),
(22, 'app_sidebar', 'Notifications', '/notifications', 'bell', 'user', 'notifications', 0, 9, 1),
(23, 'app_sidebar', 'Go Premium', '/premium', 'crown', 'user', NULL, 0, 20, 1),
(24, 'app_tabbar', 'Discover', '/discover', 'compass', 'user', NULL, 0, 1, 1),
(25, 'app_tabbar', 'Likes', '/likes', 'heart', 'user', 'likes', 0, 2, 1),
(26, 'app_tabbar', 'Messages', '/messages', 'message-circle', 'user', 'messages', 0, 3, 1),
(27, 'app_tabbar', 'Matches', '/matches', 'matches', 'user', 'matches', 0, 4, 1),
(28, 'app_tabbar', 'Profile', '/profile', 'user', 'user', NULL, 0, 5, 1),
(29, 'host_sidebar', 'Dashboard', '/host/dashboard', 'layout-dashboard', 'host', NULL, 0, 1, 1),
(30, 'host_sidebar', 'My profile', '/host/profile', 'user', 'host', NULL, 0, 2, 1),
(31, 'host_sidebar', 'Services and rates', '/host/services', 'list-checks', 'host', NULL, 0, 3, 1),
(32, 'host_sidebar', 'Availability', '/host/availability', 'calendar', 'host', NULL, 0, 4, 1),
(33, 'host_sidebar', 'Bookings', '/host/bookings', 'calendar-check', 'host', NULL, 0, 5, 1),
(34, 'host_sidebar', 'Earnings', '/host/earnings', 'wallet', 'host', NULL, 0, 6, 1),
(35, 'host_sidebar', 'Payouts', '/host/payouts', 'banknote', 'host', NULL, 0, 7, 1),
(36, 'host_sidebar', 'Reviews', '/host/reviews', 'star', 'host', NULL, 0, 8, 1),
(37, 'host_sidebar', 'Messages', '/messages', 'message-circle', 'host', 'messages', 0, 9, 1),
(38, 'host_sidebar', 'Notifications', '/notifications', 'bell', 'host', 'notifications', 0, 10, 1),
(39, 'host_tabbar', 'Home', '/host/dashboard', 'layout-dashboard', 'host', NULL, 0, 1, 1),
(40, 'host_tabbar', 'Bookings', '/host/bookings', 'calendar-check', 'host', NULL, 0, 2, 1),
(41, 'host_tabbar', 'Messages', '/messages', 'message-circle', 'host', 'messages', 0, 3, 1),
(42, 'host_tabbar', 'Earnings', '/host/earnings', 'wallet', 'host', NULL, 0, 4, 1),
(43, 'host_tabbar', 'Profile', '/host/profile', 'user', 'host', NULL, 0, 5, 1),
(44, 'app_sidebar', 'International', '/international', 'globe', 'user', NULL, 0, 8, 1),
(45, 'footer_explore', 'International', '/international', 'globe', 'any', NULL, 0, 5, 1),
(46, 'host_sidebar', 'Connection profile', '/host/connections', 'heart-handshake', 'host', NULL, 0, 9, 1),
(47, 'public_tabbar', 'Home', '/', 'home', 'any', NULL, 0, 1, 1),
(48, 'public_tabbar', 'Hosts', '/hosts', 'concierge-bell', 'any', NULL, 0, 2, 1),
(49, 'public_tabbar', 'Browse', '/browse', 'compass', 'any', NULL, 0, 3, 1),
(50, 'public_tabbar', 'Pricing', '/pricing', 'sparkles', 'any', NULL, 0, 4, 1),
(51, 'public_tabbar', 'Join', '/register', 'user-plus', 'guest', NULL, 0, 5, 1),
(54, 'header', 'Elite', '/elite', 'gem', 'any', NULL, 0, 3, 1),
(55, 'app_sidebar', 'Elite', '/elite', 'gem', 'user', NULL, 0, 8, 1),
(56, 'footer_explore', 'Elite', '/elite', 'gem', 'any', NULL, 0, 3, 1),
(57, 'footer_company', 'Advertise', '/advertise', 'megaphone', 'any', NULL, 0, 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_campaigns`
--

CREATE TABLE `newsletter_campaigns` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(200) NOT NULL,
  `body_html` mediumtext NOT NULL,
  `status` enum('draft','sending','sent','cancelled') NOT NULL DEFAULT 'draft',
  `total_recipients` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `sent_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `failed_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_subscriber_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `newsletter_campaigns`
--

INSERT INTO `newsletter_campaigns` (`id`, `subject`, `body_html`, `status`, `total_recipients`, `sent_count`, `failed_count`, `last_subscriber_id`, `created_by`, `created_at`, `started_at`, `finished_at`) VALUES
(1, 'Test', '<p>Hi</p>', 'draft', 0, 0, 0, 0, 1, '2026-10-17 00:13:32', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `token` varchar(40) DEFAULT NULL,
  `source` varchar(30) DEFAULT NULL,
  `unsubscribed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`id`, `email`, `is_active`, `created_at`, `token`, `source`, `unsubscribed_at`) VALUES
(1, 'mwangidennis546@gmail.com', 1, '2026-10-16 15:49:55', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(30) NOT NULL,
  `title` varchar(160) NOT NULL,
  `body` varchar(400) DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `sender_id` int(10) UNSIGNED DEFAULT NULL,
  `data` text DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `body`, `action_url`, `sender_id`, `data`, `is_read`, `read_at`, `created_at`) VALUES
(1, 9008, 'visit', 'Someone viewed your profile', 'Upgrade to see who has been looking.', '/visitors', NULL, NULL, 0, NULL, '2026-10-16 11:56:09'),
(2, 9016, 'like', 'Someone likes you', 'Upgrade to see who, or keep swiping and you may find them.', '/likes', NULL, '{\"swipe\":2}', 0, NULL, '2026-10-16 11:56:32'),
(3, 9012, 'like', 'Someone likes you', 'Upgrade to see who, or keep swiping and you may find them.', '/likes', NULL, '{\"swipe\":3}', 0, NULL, '2026-10-16 11:56:34'),
(4, 9005, 'like', 'Someone likes you', 'Upgrade to see who, or keep swiping and you may find them.', '/likes', NULL, '{\"swipe\":4}', 0, NULL, '2026-10-16 11:57:13'),
(5, 9006, 'superlike', 'Wanjiru super liked you', 'They really want you to notice. Take a look.', '/u/9001', 9001, '{\"swipe\":5}', 0, NULL, '2026-10-16 11:57:16'),
(6, 1, 'payment', 'Payment waiting for review', 'KSh 50 from a member (CH2610162267DE).', '/admin/payments', NULL, NULL, 0, NULL, '2026-10-16 11:58:56'),
(7, 9001, 'payment', 'Your boost is live', 'Your profile is shown first until 12:29 pm.', '/boost', NULL, NULL, 0, NULL, '2026-10-16 11:59:35'),
(8, 9011, 'visit', 'Wanjiru viewed your profile', 'See who else has been looking.', '/visitors', 9001, NULL, 0, NULL, '2026-10-16 11:59:55'),
(9, 9011, 'superlike', 'Wanjiru super liked you', 'They really want you to notice. Take a look.', '/u/9001', 9001, '{\"swipe\":1}', 0, NULL, '2026-10-16 12:00:02'),
(10, 1, 'payment', 'Payment waiting for review', 'KSh 50 from a member (CH261016C58328).', '/admin/payments', NULL, NULL, 0, NULL, '2026-10-16 12:01:22'),
(11, 9011, 'gift', 'Wanjiru sent you a Rose 🌹', NULL, '/gifts?tab=received', 9001, '{\"gift_id\":1}', 0, NULL, '2026-10-16 12:01:35'),
(12, 9001, 'payment', 'Your gift is on its way', 'Your Rose 🌹 was delivered to Victor.', '/gifts?tab=sent', NULL, NULL, 1, '2026-10-16 12:01:42', '2026-10-16 12:01:35'),
(13, 9006, 'visit', 'Wanjiru viewed your profile', 'See who else has been looking.', '/visitors', 9001, NULL, 0, NULL, '2026-10-16 12:04:07'),
(14, 9025, 'system', 'Welcome to ConnectHub, lewis', 'Your profile is live. Start with a few swipes, or check who is nearby.', '/discover', NULL, NULL, 0, NULL, '2026-10-16 12:12:22'),
(15, 9008, 'superlike', 'Wanjiru super liked you', 'They really want you to notice. Take a look.', '/u/9001', 9001, '{\"swipe\":6}', 0, NULL, '2026-10-16 12:12:38'),
(16, 9009, 'like', 'Wanjiru likes you', 'Like them back to start talking.', '/likes', 9001, '{\"swipe\":7}', 0, NULL, '2026-10-16 12:12:41'),
(17, 9014, 'like', 'Someone likes you', 'Upgrade to see who, or keep swiping and you may find them.', '/likes', NULL, '{\"swipe\":8}', 0, NULL, '2026-10-16 12:12:45'),
(18, 9015, 'like', 'Wanjiru likes you', 'Like them back to start talking.', '/likes', 9001, '{\"swipe\":9}', 0, NULL, '2026-10-16 12:12:47'),
(19, 9025, 'visit', 'Someone viewed your profile', 'Upgrade to see who has been looking.', '/visitors', NULL, NULL, 0, NULL, '2026-10-16 12:13:01'),
(20, 9025, 'like', 'Someone likes you', 'Upgrade to see who, or keep swiping and you may find them.', '/likes', NULL, '{\"swipe\":10}', 0, NULL, '2026-10-16 12:13:06'),
(21, 9025, 'match', 'You matched with Wanjiru', 'Say hello while the moment is fresh.', '/messages/with/9001', 9001, '{\"match\":1}', 0, NULL, '2026-10-16 12:13:23'),
(22, 9001, 'match', 'You matched with lewis', 'Say hello while the moment is fresh.', '/messages/with/9025', 9025, '{\"match\":1}', 0, NULL, '2026-10-16 12:13:23'),
(23, 9025, 'message', 'New message from Wanjiru', '😎', '/messages/1', 9001, '{\"conversation_id\":1}', 0, NULL, '2026-11-08 22:34:30'),
(24, 9001, 'message', 'New message from lewis', 'Nairobi..im free today', '/messages/1', 9025, '{\"conversation_id\":1}', 0, NULL, '2026-10-16 15:02:23'),
(25, 1, 'payment', 'Payment waiting for review', 'KSh 50 from a member (CH2610161B22B0).', '/admin/payments', NULL, NULL, 0, NULL, '2026-10-16 12:16:15'),
(26, 9025, 'gift', 'Wanjiru sent you a Rose 🌹', NULL, '/messages/1', 9001, '{\"gift_id\":2}', 0, NULL, '2026-10-16 12:16:27'),
(27, 9001, 'payment', 'Your gift is on its way', 'Your Rose 🌹 was delivered to lewis.', '/gifts?tab=sent', NULL, NULL, 1, '2026-10-16 12:19:44', '2026-10-16 12:16:27'),
(28, 9012, 'visit', 'Someone viewed your profile', 'Upgrade to see who has been looking.', '/visitors', NULL, NULL, 0, NULL, '2026-10-16 12:28:26'),
(29, 9016, 'visit', 'Someone viewed your profile', 'Upgrade to see who has been looking.', '/visitors', NULL, NULL, 0, NULL, '2026-10-16 12:41:48'),
(30, 9026, 'host_status', 'Application received', 'We will review it within 24 hours and let you know.', '/host/dashboard', NULL, NULL, 0, NULL, '2026-10-16 12:57:19'),
(31, 1, 'host_status', 'New Host application', 'Shee has applied to become a Host.', '/admin/hosts', NULL, NULL, 0, NULL, '2026-10-16 12:57:19'),
(32, 9026, 'verification', 'Your ID is verified', 'Your profile now shows the ID verified mark.', '/host/dashboard', NULL, NULL, 0, NULL, '2026-10-16 12:58:20'),
(33, 9026, 'host_status', 'Your Host profile is approved', 'You are live in the Host directory. Check your availability so clients can book.', '/host/dashboard', NULL, NULL, 0, NULL, '2026-10-16 12:58:31'),
(34, 1, 'payment', 'Payment waiting for review', 'KSh 200 from a member (CH26101665021F).', '/admin/payments', NULL, NULL, 0, NULL, '2026-10-16 13:01:32'),
(35, 9001, 'payment', 'Your plan is active', 'Thank you. Your plan runs until 6 Dec 2026.', '/billing', NULL, NULL, 0, NULL, '2026-10-16 13:01:42'),
(36, 9001, 'payment', 'You are featured', 'Your profile is featured until 23 Oct 2026.', '/billing', NULL, NULL, 0, NULL, '2026-10-16 13:02:47'),
(37, 2, 'host_status', 'Application received', 'We will review it within 24 hours and let you know.', '/host/dashboard', NULL, NULL, 0, NULL, '2026-10-16 14:18:59'),
(38, 1, 'host_status', 'New Host application', 'Juice has applied to become a Host.', '/admin/hosts', NULL, NULL, 0, NULL, '2026-10-16 14:18:59'),
(39, 2, 'host_status', 'Your Host profile is approved', 'You are live in the Host directory. Check your availability so clients can book.', '/host/dashboard', NULL, NULL, 0, NULL, '2026-10-16 14:19:14'),
(40, 9011, 'like', 'lewis likes you', 'Like them back to start talking.', '/likes', 9025, '{\"swipe\":12}', 0, NULL, '2026-10-16 15:00:00'),
(41, 9025, 'intl', 'You are approved for International', 'Your international profile is approved. You are live.', '/international', NULL, NULL, 0, NULL, '2026-10-16 15:06:33'),
(42, 1, 'payment', 'Payment waiting for review', 'KSh 2,000 from a member (CH2610161B5612).', '/admin/payments', NULL, NULL, 0, NULL, '2026-10-16 15:42:50'),
(43, 2, 'booking', 'New booking request', 'Wanjiru would like to book Sat 17 Oct, 1:00 pm. Please answer before Sat 17 Oct, 3:43 am.', '/host/bookings/26', 9001, '{\"booking_id\":26}', 0, NULL, '2026-10-16 15:43:02'),
(44, 9001, 'booking_update', 'Request sent to Juice', 'Your deposit is paid. We will tell you as soon as Juice answers.', '/bookings/26', 2, '{\"booking_id\":26}', 0, NULL, '2026-10-16 15:43:02'),
(45, 9001, 'booking_update', 'Booking confirmed', 'Juice accepted your request for Sat 17 Oct, 1:00 pm.', '/bookings/26', 2, '{\"booking_id\":26}', 1, '2026-10-16 15:44:59', '2026-10-16 15:44:30'),
(46, 2, 'message', 'New message from Wanjiru', 'Whatchu doing', '/messages/2', 9001, '{\"conversation_id\":2}', 0, NULL, '2026-10-16 22:03:36'),
(47, 1, 'payment', 'Payment waiting for review', 'KSh 50 from a member (CH261016D6E93B).', '/admin/payments', NULL, NULL, 0, NULL, '2026-10-16 15:46:06'),
(48, 9001, 'message', 'New message from Juice', 'Thanks', '/messages/2', 2, '{\"conversation_id\":2}', 0, NULL, '2026-10-16 15:52:49'),
(49, 2, 'gift', 'Wanjiru sent you a Rose 🌹', NULL, '/messages/2', 9001, '{\"gift_id\":3}', 0, NULL, '2026-10-16 15:51:07'),
(50, 9001, 'payment', 'Your gift is on its way', 'Your Rose 🌹 was delivered to Juice.', '/gifts?tab=sent', NULL, NULL, 0, NULL, '2026-10-16 15:51:07'),
(51, 9005, 'visit', 'Someone viewed your profile', 'Upgrade to see who has been looking.', '/visitors', NULL, NULL, 0, NULL, '2026-10-16 21:19:49'),
(52, 9001, 'booking_update', 'Booking reminder', 'You are meeting Juice on Sat 17 Oct, 1:00 pm.', '/bookings/26', NULL, '{\"booking_id\":26}', 0, NULL, '2026-10-16 21:20:20'),
(53, 2, 'booking_update', 'Booking reminder', 'You are meeting Wanjiru on Sat 17 Oct, 1:00 pm.', '/host/bookings/26', NULL, '{\"booking_id\":26}', 0, NULL, '2026-10-16 21:20:20'),
(54, 9005, 'visit', 'Someone viewed your profile', 'Upgrade to see who has been looking.', '/visitors', NULL, NULL, 0, NULL, '2026-10-18 10:00:15'),
(55, 9007, 'visit', 'Someone viewed your profile', 'Upgrade to see who has been looking.', '/visitors', NULL, NULL, 0, NULL, '2026-10-20 22:07:30'),
(56, 9001, 'booking_update', 'Marked as a no-show', 'Juice reported that you did not attend on Sat 17 Oct, 1:00 pm. The deposit is not refunded.', '/bookings/26', NULL, '{\"booking_id\":26}', 0, NULL, '2026-11-09 09:35:55'),
(57, 9030, 'superlike', 'Wanjiru super liked you', 'They really want you to notice. Take a look.', '/u/9001', 9001, '{\"swipe\":13}', 0, NULL, '2026-11-10 00:22:19'),
(58, 9030, 'visit', 'Someone viewed your profile', 'Upgrade to see who has been looking.', '/visitors', NULL, NULL, 0, NULL, '2026-11-10 00:22:47'),
(59, 9006, 'visit', 'Someone viewed your profile', 'Upgrade to see who has been looking.', '/visitors', NULL, NULL, 0, NULL, '2026-11-10 00:23:31'),
(60, 9004, 'visit', 'Someone viewed your profile', 'Upgrade to see who has been looking.', '/visitors', NULL, NULL, 0, NULL, '2026-11-12 09:26:53'),
(61, 9004, 'superlike', 'Wanjiru super liked you', 'They really want you to notice. Take a look.', '/u/9001', 9001, '{\"swipe\":14}', 0, NULL, '2026-11-12 09:27:17'),
(62, 9001, 'elite', 'Welcome to Elite', 'Your profile is live. People can now send you requests.', '/elite/me', NULL, NULL, 0, NULL, '2026-11-12 09:33:11'),
(63, 9033, 'elite', 'New Elite request', 'Wanjiru would like to connect (100% match).', '/elite/requests', 9001, NULL, 0, NULL, '2026-11-12 09:34:36'),
(64, 1, 'payment', 'Payment waiting for review', 'KSh 2,500 from a member (CH2611124B03FE).', '/admin/payments', NULL, NULL, 0, NULL, '2026-11-12 09:36:06'),
(65, 9001, 'payment', 'Your Elite pass is on', 'Every profile is open until 12 Dec 2026.', '/elite', NULL, NULL, 0, NULL, '2026-11-12 09:36:35'),
(66, 9001, 'payment', 'Your plan is active', 'Thank you. Your plan runs until 25 Nov 2026.', '/billing', NULL, NULL, 1, '2026-11-12 09:37:10', '2026-11-12 09:36:38'),
(67, 9034, 'elite', 'New Elite request', 'Wanjiru would like to connect (100% match).', '/elite/requests', 9001, NULL, 0, NULL, '2026-11-12 09:38:11'),
(68, 9035, 'elite', 'New Elite request', 'Wanjiru would like to connect (100% match).', '/elite/requests', 9001, NULL, 0, NULL, '2026-11-12 09:39:48'),
(69, 9007, 'visit', 'Someone viewed your profile', 'Upgrade to see who has been looking.', '/visitors', NULL, NULL, 0, NULL, '2026-11-12 09:40:05'),
(70, 9007, 'like', 'Someone likes you', 'Upgrade to see who, or keep swiping and you may find them.', '/likes', NULL, '{\"swipe\":15}', 0, NULL, '2026-11-12 09:40:07'),
(71, 9021, 'message', 'New message from Wanjiru', 'Hello', '/messages/3', 9001, '{\"conversation_id\":3}', 0, NULL, '2026-11-12 20:27:46'),
(72, 9030, 'visit', 'Someone viewed your profile', 'Upgrade to see who has been looking.', '/visitors', NULL, NULL, 0, NULL, '2026-11-12 23:00:48');

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `inapp_matches` tinyint(1) NOT NULL DEFAULT 1,
  `inapp_messages` tinyint(1) NOT NULL DEFAULT 1,
  `inapp_likes` tinyint(1) NOT NULL DEFAULT 1,
  `inapp_visits` tinyint(1) NOT NULL DEFAULT 1,
  `inapp_gifts` tinyint(1) NOT NULL DEFAULT 1,
  `email_matches` tinyint(1) NOT NULL DEFAULT 1,
  `email_messages` tinyint(1) NOT NULL DEFAULT 0,
  `email_likes` tinyint(1) NOT NULL DEFAULT 0,
  `email_news` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_preferences`
--

INSERT INTO `notification_preferences` (`user_id`, `inapp_matches`, `inapp_messages`, `inapp_likes`, `inapp_visits`, `inapp_gifts`, `email_matches`, `email_messages`, `email_likes`, `email_news`) VALUES
(2, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9001, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9002, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9003, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9004, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9005, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9006, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9007, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9008, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9009, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9010, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9011, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9012, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9013, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9014, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9015, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9016, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9017, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9018, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9019, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9020, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9021, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9022, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9023, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9024, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9025, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9026, 1, 1, 1, 1, 1, 1, 0, 0, 1),
(9029, 1, 1, 1, 1, 1, 1, 0, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `content` mediumtext DEFAULT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` varchar(300) DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `show_in_footer` tinyint(1) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `meta_title`, `meta_description`, `og_image`, `show_in_footer`, `is_published`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'About us', 'about', '<p>ConnectHub helps people meet others who live nearby, for dating, friendship or good company. We built it because most apps show you people from everywhere and nowhere. Here, everyone is shown by how close they are, so a good conversation can turn into a coffee this week.</p><h2>What we care about</h2><ul><li><strong>Real people.</strong> Verified badges, moderated photos and a quick way to report anything that feels wrong.</li><li><strong>Privacy you control.</strong> You decide who sees your photos, your distance and your contact details.</li><li><strong>Built for Kenya.</strong> All 47 counties, M-Pesa payments and a design that works on any phone.</li></ul><h2>Get in touch</h2><p>Questions, feedback or a safety concern? <a href=\"/contact\">Send us a message</a> and a real person will reply.</p>', 'About ConnectHub', 'Meet the people behind ConnectHub and why we built it.', NULL, 0, 1, 1, '2026-10-16 06:16:42', '2026-10-16 06:16:42'),
(2, 'Terms of service', 'terms-of-service', '<p><em>Last updated: this page is yours to edit under Pages in the admin panel. Have it reviewed by a lawyer before launch.</em></p><h2>1. Using the service</h2><p>You must be at least 18 years old. You agree to provide accurate information, keep your password private and not share your account. We may suspend or remove accounts that break these terms.</p><h2>2. Your content</h2><p>You keep ownership of the photos and text you upload. You give us permission to display them on the service. You must own or have permission to use everything you upload. Do not upload nudity, violence, or images of other people without their consent.</p><h2>3. Behaviour</h2><p>Be respectful. Harassment, hate speech, scams, spam, impersonation and requests for money are not allowed. Do not use the service for anything illegal, including soliciting sexual services.</p><h2>4. Paid features</h2><p>Premium plans, boosts, gifts and Host bookings are paid in advance. Plans renew only when you choose to renew them. Refunds are given at our discretion unless the law requires otherwise.</p><h2>5. Hosts and bookings</h2><p>Hosts are independent members, not employees. They offer companionship services only. We take a commission on bookings made through the platform. Hosts and clients must follow the Host guidelines.</p><h2>6. Safety</h2><p>We do not run criminal background checks. Use your judgement when meeting people and follow our safety tips.</p><h2>7. Liability</h2><p>We provide the service as it is. To the extent the law allows, we are not responsible for what members do or say, or for losses from using the service.</p><h2>8. Changes and contact</h2><p>We may update these terms. If you keep using the service after a change you accept it. Questions? Use the <a href=\"/contact\">contact page</a>.</p>', 'Terms of service', 'The rules for using ConnectHub.', NULL, 1, 1, 2, '2026-10-16 06:16:42', '2026-10-16 06:16:42'),
(3, 'Privacy policy', 'privacy-policy', '<p><em>This page is yours to edit under Pages in the admin panel. Have it reviewed by a lawyer and align it with the Kenya Data Protection Act before launch.</em></p><h2>What we collect</h2><p>Information you give us (name, date of birth, gender, email, phone, photos, bio and preferences), your approximate location, messages you send, payment records (we never see your M-Pesa PIN), and technical data such as your device and IP address.</p><h2>How we use it</h2><p>To run the service, show you relevant people, keep the community safe, process payments, send notices you ask for, and improve the product.</p><h2>Who sees your information</h2><p>Other members see your profile according to your privacy settings and the site rules. Visitors who are not signed in may see limited, blurred profiles. We share data with payment providers and email services only as needed to run the service, and with authorities when the law requires it.</p><h2>Your choices</h2><p>You can edit or hide parts of your profile, pause your account, download your data or delete your account from Settings. Deleting your account removes your profile, photos and messages.</p><h2>Security</h2><p>Passwords are stored as one-way hashes. Connections use HTTPS. No system is perfectly secure, so please use a strong, unique password.</p><h2>Contact</h2><p>For privacy requests use the <a href=\"/contact\">contact page</a>.</p>', 'Privacy policy', 'How ConnectHub collects, uses and protects your information.', NULL, 1, 1, 3, '2026-10-16 06:16:42', '2026-10-16 06:16:42'),
(4, 'Cookie policy', 'cookie-policy', '<p>We use a small number of cookies. Essential cookies keep you signed in and protect forms from forgery. Optional analytics cookies are only set if you accept them.</p><ul><li><strong>Session cookie:</strong> keeps you signed in during your visit.</li><li><strong>Remember me:</strong> keeps you signed in for longer if you ask us to.</li><li><strong>Preferences:</strong> remembers your theme and cookie choice.</li></ul>', 'Cookie policy', 'What cookies ConnectHub uses.', NULL, 1, 1, 4, '2026-10-16 06:16:42', '2026-10-16 06:16:42'),
(5, 'Safety tips', 'safety-tips', '<p>Most people you meet here are exactly who they say they are. These habits keep it that way.</p><h2>Before you meet</h2><ul><li>Chat for a while first. Move to a video call to check they match their photos.</li><li>Look for the verified badge, but remember it is not a guarantee.</li><li>Never send money, even if the story is urgent or sad.</li><li>Keep your phone number, address and workplace private until you trust them.</li></ul><h2>When you meet</h2><ul><li>Choose a busy public place for the first meeting.</li><li>Tell a friend who you are meeting, where and when.</li><li>Arrange your own transport so you can leave when you want.</li><li>Keep an eye on your drink and trust your instincts.</li></ul><h2>If something feels wrong</h2><p>Leave, contact someone you trust, and <a href=\"/contact\">report the profile</a>. In an emergency call 999 or 112.</p>', 'Safety tips for meeting people', 'Practical advice for staying safe when you meet someone from the internet.', NULL, 1, 1, 5, '2026-10-16 06:16:42', '2026-10-16 06:16:42'),
(6, 'Community guidelines', 'community-guidelines', '<ul><li>Be kind and treat people with respect.</li><li>Use your own, recent photos. No fake or borrowed identities.</li><li>No nudity, sexual content or sexual solicitation.</li><li>No hate speech, threats or harassment.</li><li>No asking for money, gifts or financial help.</li><li>No spam, advertising or links to other platforms in your bio.</li><li>Respect a no. If someone is not interested, move on.</li></ul><p>Members who break these rules may be warned, suspended or removed. You can report anyone from their profile.</p>', 'Community guidelines', 'What we expect from every member.', NULL, 1, 1, 6, '2026-10-16 06:16:42', '2026-10-16 06:16:42'),
(7, 'Host guidelines', 'host-guidelines', '<p>Hosts offer social company: dinners, events, city tours, travel and conversation. The service is non-sexual and the following rules apply to everyone.</p><h2>For Hosts</h2><ul><li>You must be 18 or over and pass ID verification before your profile goes live.</li><li>Describe your services and rates honestly. Do not offer or imply sexual services.</li><li>Do not take payment outside the platform for bookings made on it.</li><li>Keep to the agreed time and place. Cancel early if you cannot attend.</li><li>You can decline any booking and leave any situation you feel unsafe in.</li></ul><h2>For clients</h2><ul><li>Treat Hosts with respect. Companionship does not include physical or sexual contact.</li><li>Pay the deposit through the platform and the balance as agreed.</li><li>Do not pressure a Host to change the agreed plan or location.</li></ul><h2>Breaking the rules</h2><p>Accounts that offer or request sexual services are removed immediately and may be reported to the authorities.</p>', 'Host guidelines', 'Rules for Hosts and the people who book them.', NULL, 1, 1, 7, '2026-10-16 06:16:42', '2026-10-16 06:16:42');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `payment_type` varchar(20) NOT NULL,
  `payment_method` varchar(20) NOT NULL,
  `gateway` varchar(20) DEFAULT NULL,
  `gateway_ref` varchar(120) DEFAULT NULL,
  `gateway_txn` varchar(120) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(5) NOT NULL DEFAULT 'KES',
  `status` enum('pending','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `reference` varchar(40) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `metadata` text DEFAULT NULL,
  `mpesa_phone` varchar(20) DEFAULT NULL,
  `mpesa_checkout_id` varchar(100) DEFAULT NULL,
  `mpesa_merchant_id` varchar(100) DEFAULT NULL,
  `mpesa_receipt` varchar(40) DEFAULT NULL,
  `mpesa_result_desc` varchar(255) DEFAULT NULL,
  `bank_reference` varchar(80) DEFAULT NULL,
  `bank_paid_on` date DEFAULT NULL,
  `admin_notes` varchar(255) DEFAULT NULL,
  `processed_by` int(10) UNSIGNED DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `fulfilled_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `payment_type`, `payment_method`, `gateway`, `gateway_ref`, `gateway_txn`, `amount`, `currency`, `status`, `reference`, `description`, `metadata`, `mpesa_phone`, `mpesa_checkout_id`, `mpesa_merchant_id`, `mpesa_receipt`, `mpesa_result_desc`, `bank_reference`, `bank_paid_on`, `admin_notes`, `processed_by`, `processed_at`, `fulfilled_at`, `created_at`, `updated_at`) VALUES
(1, 9001, 'boost', 'manual', NULL, NULL, NULL, 50.00, 'KES', 'completed', 'CH2610162267DE', 'Profile boost, 30 minutes', '{\"submitted_at\":1792141136,\"review\":true,\"minutes\":30,\"return_url\":\"\\/boost\"}', '254758256440', NULL, NULL, 'QGHTRBOPRSHU', NULL, 'QGHTRBOPRSHU', NULL, 'Approved by admin@example.com', 1, '2026-10-16 11:59:35', '2026-10-16 11:59:35', '2026-10-16 11:58:30', '2026-10-16 11:59:35'),
(2, 9001, 'gift', 'manual', NULL, NULL, NULL, 50.00, 'KES', 'completed', 'CH261016C58328', 'Gift: Rose for Victor', '{\"submitted_at\":1792141282,\"review\":true,\"gift_type_id\":1,\"to\":9011,\"message\":\"\",\"gift_name\":\"Rose\",\"to_name\":\"Victor\",\"return_url\":\"\\/u\\/9011\"}', '254758256440', NULL, NULL, 'PRQGYBCWLM', NULL, 'PRQGYBCWLM', NULL, 'Approved by admin@example.com', 1, '2026-10-16 12:01:35', '2026-10-16 12:01:35', '2026-10-16 12:01:02', '2026-10-16 12:01:35'),
(3, 9001, 'gift', 'manual', NULL, NULL, NULL, 50.00, 'KES', 'completed', 'CH2610161B22B0', 'Gift: Rose for lewis', '{\"submitted_at\":1792142175,\"review\":true,\"gift_type_id\":1,\"to\":9025,\"message\":\"\",\"gift_name\":\"Rose\",\"to_name\":\"lewis\",\"return_url\":\"\\/messages\\/1\"}', '254113385456', NULL, NULL, 'GGYUJFRHCCG', NULL, 'GGYUJFRHCCG', NULL, 'Approved by admin@example.com', 1, '2026-10-16 12:16:27', '2026-10-16 12:16:27', '2026-10-16 12:16:02', '2026-10-16 12:16:27'),
(4, 9001, 'featured', 'manual', NULL, NULL, NULL, 299.00, 'KES', 'completed', 'CH261016677803', 'Featured profile, 7 days', '{\"days\":7,\"return_url\":\"\\/boost\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved by admin@example.com', 1, '2026-10-16 13:02:47', '2026-10-16 13:02:47', '2026-10-16 12:40:24', '2026-10-16 13:02:47'),
(5, 9001, 'subscription', 'manual', NULL, NULL, NULL, 200.00, 'KES', 'completed', 'CH26101665021F', 'Premium plan, 1 month', '{\"submitted_at\":1792144892,\"review\":true,\"plan_id\":2,\"period\":\"monthly\",\"plan_slug\":\"premium\",\"plan_name\":\"Premium\"}', '254113385456', NULL, NULL, 'QPGRACYRLF', NULL, 'QPGRACYRLF', NULL, 'Approved by admin@example.com', 1, '2026-10-16 13:01:42', '2026-10-16 13:01:42', '2026-10-16 13:01:17', '2026-10-16 13:01:42'),
(6, 9001, 'subscription', 'manual', NULL, NULL, NULL, 170.00, 'KES', 'completed', 'CH26101644C46E', 'VIP plan, 1 week', '{\"plan_id\":3,\"period\":\"weekly\",\"plan_slug\":\"vip\",\"plan_name\":\"VIP\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved by admin@example.com', 1, '2026-11-12 09:36:38', '2026-11-12 09:36:38', '2026-10-16 13:21:49', '2026-11-12 09:36:38'),
(7, 9001, 'booking', 'manual', NULL, NULL, NULL, 2000.00, 'KES', 'completed', 'CH2610161B5612', 'Booking deposit - Juice on 17 Oct 2026', '{\"submitted_at\":1792154570,\"review\":true,\"booking_id\":26,\"host_id\":2,\"return_url\":\"\\/bookings\\/26\"}', '254758256440', NULL, NULL, 'HHAGDKSNHDIS', NULL, 'HHAGDKSNHDIS', NULL, 'Approved by admin@example.com', 1, '2026-10-16 15:43:02', '2026-10-16 15:43:02', '2026-10-16 15:42:38', '2026-10-16 15:43:02'),
(8, 9001, 'gift', 'manual', NULL, NULL, NULL, 50.00, 'KES', 'completed', 'CH261016D6E93B', 'Gift: Rose for Juice', '{\"submitted_at\":1792154766,\"review\":true,\"gift_type_id\":1,\"to\":2,\"message\":\"\",\"gift_name\":\"Rose\",\"to_name\":\"Juice\",\"return_url\":\"\\/messages\\/2\"}', '254758256440', NULL, NULL, 'QPKDHSNMSY', NULL, 'QPKDHSNMSY', NULL, 'Approved by admin@example.com', 1, '2026-10-16 15:51:07', '2026-10-16 15:51:07', '2026-10-16 15:45:53', '2026-10-16 15:51:07'),
(9, 9001, 'elite', 'manual', NULL, NULL, NULL, 2500.00, 'KES', 'completed', 'CH2611124B03FE', 'Elite pass, 30 days', '{\"submitted_at\":1794465366,\"review\":true,\"kind\":\"pass\",\"days\":30,\"return_url\":\"\\/elite\\/9033\"}', '254758256440', NULL, NULL, 'QJFUBSLNDVSK', NULL, 'QJFUBSLNDVSK', NULL, 'Approved by admin@example.com', 1, '2026-11-12 09:36:35', '2026-11-12 09:36:35', '2026-11-12 09:35:24', '2026-11-12 09:36:35');

-- --------------------------------------------------------

--
-- Table structure for table `payment_events`
--

CREATE TABLE `payment_events` (
  `id` int(10) UNSIGNED NOT NULL,
  `gateway` varchar(20) NOT NULL,
  `event_id` varchar(120) NOT NULL,
  `event_type` varchar(80) DEFAULT NULL,
  `payment_id` int(10) UNSIGNED DEFAULT NULL,
  `outcome` varchar(160) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pay_saved_methods`
--

CREATE TABLE `pay_saved_methods` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `gateway` varchar(20) NOT NULL,
  `token` varchar(160) NOT NULL,
  `customer` varchar(160) DEFAULT NULL,
  `fingerprint` varchar(160) NOT NULL,
  `brand` varchar(30) DEFAULT NULL,
  `last4` varchar(4) DEFAULT NULL,
  `exp_month` tinyint(3) UNSIGNED DEFAULT NULL,
  `exp_year` smallint(5) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profile_views`
--

CREATE TABLE `profile_views` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `viewer_id` int(10) UNSIGNED NOT NULL,
  `viewed_id` int(10) UNSIGNED NOT NULL,
  `view_count` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `dwell_ms` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `scroll_depth` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `photos_viewed` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `first_viewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_viewed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `profile_views`
--

INSERT INTO `profile_views` (`id`, `viewer_id`, `viewed_id`, `view_count`, `dwell_ms`, `scroll_depth`, `photos_viewed`, `first_viewed_at`, `last_viewed_at`) VALUES
(1, 9001, 9008, 1, 14455, 100, 1, '2026-10-16 11:56:09', '2026-10-16 11:56:09'),
(2, 9001, 9011, 1, 0, 0, 0, '2026-10-16 11:59:55', '2026-10-16 11:59:55'),
(3, 9001, 9006, 2, 226146, 100, 1, '2026-10-16 12:04:07', '2026-11-10 00:23:31'),
(4, 9001, 9025, 1, 67659, 100, 2, '2026-10-16 12:13:01', '2026-10-16 12:13:01'),
(5, 9001, 9012, 1, 112816, 100, 2, '2026-10-16 12:28:26', '2026-10-16 12:28:26'),
(6, 9001, 9016, 1, 4427, 36, 1, '2026-10-16 12:41:48', '2026-10-16 12:41:48'),
(7, 9025, 9005, 1, 3072, 0, 1, '2026-10-16 21:19:49', '2026-10-16 21:19:49'),
(8, 9001, 9005, 1, 5766, 0, 1, '2026-10-18 10:00:15', '2026-10-18 10:00:15'),
(9, 9001, 9007, 2, 0, 0, 0, '2026-10-20 22:07:30', '2026-11-12 09:40:05'),
(10, 9001, 9030, 2, 36975, 100, 2, '2026-11-10 00:22:47', '2026-11-12 23:00:48'),
(11, 9001, 9004, 1, 0, 0, 0, '2026-11-12 09:26:53', '2026-11-12 09:26:53');

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `bucket` varchar(64) NOT NULL,
  `window_start` int(10) UNSIGNED NOT NULL,
  `hits` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rate_limits`
--

INSERT INTO `rate_limits` (`bucket`, `window_start`, `hits`) VALUES
('07d3a4d9227972f511b2d68c7e2b2518c37950f2', 1794465000, 1),
('07d3a4d9227972f511b2d68c7e2b2518c37950f2', 1794513000, 1),
('13fe734956462c8ab33fb74f29e6b61a033d35e2', 1794465600, 1),
('13fe734956462c8ab33fb74f29e6b61a033d35e2', 1794466200, 1),
('1cc9079b2a38810b4dc9924a2e296afbb4377265', 1794465600, 1),
('202b5d7f48b51dd2692c2324b370c643fd50e093', 1794492600, 1),
('202b5d7f48b51dd2692c2324b370c643fd50e093', 1794504000, 3),
('202b5d7f48b51dd2692c2324b370c643fd50e093', 1794505200, 2),
('202b5d7f48b51dd2692c2324b370c643fd50e093', 1794505800, 11),
('211bc363468fb6cfa007ff8b430037d2aba7e541', 1794504420, 1),
('21a7b4847f1da9310d239eb625c54bcfbe2ed5e7', 1794504420, 1),
('21b2c479d2f1693c84f60b6d112645e3b1a83e7c', 1794465600, 2),
('23aee01cf34291819a1c2e56fe719f4f2be7fc57', 1794466800, 1),
('2730783804ce52a33f5816fd6728fea0f2708ddb', 1794464400, 1),
('324c59da76ea47ff96abc183f6eb19a77bae9b8b', 1794464820, 1),
('324c59da76ea47ff96abc183f6eb19a77bae9b8b', 1794465600, 1),
('344c7bade4c559b2bef870ec2bfac03e4f24227b', 1794465600, 2),
('36f58eb5329a5fd8c90167800c986690427a23f5', 1794513000, 1),
('3a40e52cb6236570401ff365b30c0ca5691c8208', 1794464400, 11),
('3a40e52cb6236570401ff365b30c0ca5691c8208', 1794465000, 1),
('3a40e52cb6236570401ff365b30c0ca5691c8208', 1794507600, 1),
('3a40e52cb6236570401ff365b30c0ca5691c8208', 1794508800, 1),
('3a40e52cb6236570401ff365b30c0ca5691c8208', 1794510000, 4),
('3a40e52cb6236570401ff365b30c0ca5691c8208', 1794510600, 2),
('3a40e52cb6236570401ff365b30c0ca5691c8208', 1794513000, 3),
('3a40e52cb6236570401ff365b30c0ca5691c8208', 1794513600, 2),
('3a40e52cb6236570401ff365b30c0ca5691c8208', 1794514800, 3),
('3f94672ca2160fa435f64bebc8cafdac82d293b5', 1794505800, 3),
('4def2172c022eb8095db739fb180f0d3c839375a', 1794464400, 1),
('58bee8ef87e97e5c4aaedb3144371bff25266fa8', 1794464400, 1),
('58bee8ef87e97e5c4aaedb3144371bff25266fa8', 1794465600, 1),
('590e7055bd9b77326ff333fd0b9b0e23bd77a93f', 1794514800, 1),
('6148c1996d865320c39c5f0b62b1b06c3bed7a03', 1794465000, 3),
('668c1e9a196de358b64f4c2a7450f0383dca9962', 1794465000, 1),
('67703363771a33e4728b6b0a2b4d946e3247b09e', 1794492000, 1),
('67703363771a33e4728b6b0a2b4d946e3247b09e', 1794503700, 1),
('67703363771a33e4728b6b0a2b4d946e3247b09e', 1794505500, 5),
('6e4e8f6191a54de7f8e46aa9fcb2646e68a5e1ff', 1794505800, 1),
('71f67713421a2c629a624ae792a7b14b57ff04a3', 1794441600, 1),
('7fa475b365ea9ed82e587c306bf7edffda32dd3f', 1794464400, 3),
('7fa475b365ea9ed82e587c306bf7edffda32dd3f', 1794465000, 2),
('86f572b5f59628fb2f2b6f77822ed8abb556180e', 1794441600, 1),
('883f3ec71ec508a2fc246e4532f1d27c920c9ce8', 1794464400, 1),
('883f3ec71ec508a2fc246e4532f1d27c920c9ce8', 1794465600, 1),
('95a61f0c23337b7a147cc26eab4dc9489ad89224', 1794466800, 2),
('95a61f0c23337b7a147cc26eab4dc9489ad89224', 1794513600, 1),
('976503c200fde4cfdcc60b5440ab86e78dcc5730', 1794513600, 1),
('9adc106f7a773475eb475dd45f6707bf320f1a6f', 1794504000, 7),
('9adc106f7a773475eb475dd45f6707bf320f1a6f', 1794505800, 10),
('a2f5ed5ec5a98f0d9702989755d0cda28ab20c5e', 1794466200, 1),
('a2f5ed5ec5a98f0d9702989755d0cda28ab20c5e', 1794466800, 1),
('a2f5ed5ec5a98f0d9702989755d0cda28ab20c5e', 1794504000, 1),
('a2f5ed5ec5a98f0d9702989755d0cda28ab20c5e', 1794505200, 1),
('a2f5ed5ec5a98f0d9702989755d0cda28ab20c5e', 1794511200, 1),
('aa607fa1bee4134aa59cc38cf366b1dca1f5c442', 1794464100, 1),
('aa607fa1bee4134aa59cc38cf366b1dca1f5c442', 1794465000, 5),
('aa607fa1bee4134aa59cc38cf366b1dca1f5c442', 1794465900, 3),
('aa607fa1bee4134aa59cc38cf366b1dca1f5c442', 1794466800, 4),
('aa607fa1bee4134aa59cc38cf366b1dca1f5c442', 1794476700, 1),
('aa607fa1bee4134aa59cc38cf366b1dca1f5c442', 1794510900, 2),
('aa607fa1bee4134aa59cc38cf366b1dca1f5c442', 1794512700, 1),
('aa607fa1bee4134aa59cc38cf366b1dca1f5c442', 1794514500, 2),
('ad0e37f11392cbb1d36cfb68b530da43be676543', 1794510600, 4),
('ad0e37f11392cbb1d36cfb68b530da43be676543', 1794513000, 1),
('ad0e37f11392cbb1d36cfb68b530da43be676543', 1794514800, 2),
('b2407ff59a967ffebdb6de1c6d1072bce1d87e0d', 1794466800, 1),
('bb4ccee9f7c390d6112f0d8346e08cba9a84ffc6', 1794465600, 2),
('bcf551d8a2a4ae77883d3b965a9232021a38cd39', 1794466800, 1),
('be48c01c3eed26443d8bdb6266e6b48e05b10af5', 1794466200, 1),
('be48c01c3eed26443d8bdb6266e6b48e05b10af5', 1794507600, 1),
('be48c01c3eed26443d8bdb6266e6b48e05b10af5', 1794510600, 1),
('be48c01c3eed26443d8bdb6266e6b48e05b10af5', 1794514800, 1),
('c276bf3ac32303fd9a5fae8c2f5dc22f4208754f', 1794506400, 1),
('d3d50dc680adaebe55ff36c00696078f7b78e7b0', 1794464820, 1),
('d94c6e4460db79dff532b859514f00483a3bf57d', 1794464400, 1),
('d94c6e4460db79dff532b859514f00483a3bf57d', 1794465000, 8),
('d94c6e4460db79dff532b859514f00483a3bf57d', 1794465600, 2),
('d94c6e4460db79dff532b859514f00483a3bf57d', 1794466200, 7),
('d94c6e4460db79dff532b859514f00483a3bf57d', 1794466800, 7),
('d94c6e4460db79dff532b859514f00483a3bf57d', 1794476400, 1),
('d94c6e4460db79dff532b859514f00483a3bf57d', 1794510600, 1),
('d94c6e4460db79dff532b859514f00483a3bf57d', 1794511200, 2),
('d94c6e4460db79dff532b859514f00483a3bf57d', 1794513000, 2),
('d94c6e4460db79dff532b859514f00483a3bf57d', 1794514800, 4),
('de554727f8fe83d0a59a6528677cec242625bbe5', 1794465000, 1),
('de9a644ba3fc2bd1d28b37e333758e9449318b71', 1794465000, 2),
('de9a644ba3fc2bd1d28b37e333758e9449318b71', 1794465600, 2),
('f1c4c2a07e9c42df52e2ee897fd8e06fc1e679e8', 1794505800, 2),
('f26fd4455edca02b3ac063bb563cbc24ce91960f', 1794492000, 2),
('f26fd4455edca02b3ac063bb563cbc24ce91960f', 1794504000, 1),
('f26fd4455edca02b3ac063bb563cbc24ce91960f', 1794505800, 11),
('f26fd4455edca02b3ac063bb563cbc24ce91960f', 1794506400, 1),
('fa526fe8605eae91d44e60b239051c95bacfcd3c', 1794514800, 5);

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `reporter_id` int(10) UNSIGNED NOT NULL,
  `reported_id` int(10) UNSIGNED NOT NULL,
  `target_type` varchar(20) NOT NULL DEFAULT 'user',
  `target_id` int(10) UNSIGNED DEFAULT NULL,
  `reason` varchar(80) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewing','resolved','dismissed') NOT NULL DEFAULT 'pending',
  `action_taken` enum('none','warning','photo_removed','suspended','banned') DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `resolved_by` int(10) UNSIGNED DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `version` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schema_migrations`
--

INSERT INTO `schema_migrations` (`version`, `name`, `applied_at`) VALUES
(1, '0001_plans_weekly', '2026-10-16 06:16:42'),
(2, '0002_media_library', '2026-10-16 06:16:43'),
(3, '0003_nav_icons', '2026-10-16 06:16:43'),
(4, '0004_interest_icons', '2026-10-16 06:16:43'),
(5, '0005_location_cards', '2026-10-16 06:16:43'),
(6, '0006_host_services_listing', '2026-10-16 12:44:45'),
(7, '0007_message_edit', '2026-10-16 13:38:36'),
(8, '0008_gift_wallet', '2026-10-16 13:38:36'),
(9, '0009_boost_packs', '2026-10-16 13:38:36'),
(10, '0010_photo_limits', '2026-10-16 13:38:36'),
(11, '0011_direct_messages', '2026-10-16 13:38:36'),
(12, '0012_international', '2026-10-16 13:38:36'),
(13, '0013_landing_intl_order', '2026-10-16 13:38:36'),
(14, '0014_verification_methods', '2026-10-16 13:38:36'),
(15, '0015_seo', '2026-10-16 08:12:11'),
(16, '0016_blog_articles', '2026-10-16 08:12:11'),
(17, '0017_host_verification', '2026-10-16 08:12:11'),
(18, '0018_payment_gateways', '2026-10-16 08:12:11'),
(19, '0019_connection_profiles', '2026-10-23 03:12:00'),
(20, '0020_agent_showcase', '2026-11-08 14:24:37'),
(21, '0021_agent_stories', '2026-11-08 14:24:37'),
(22, '0022_visitor_tabbar', '2026-11-08 14:24:37'),
(23, '0023_choices_elite', '2026-11-08 14:24:37'),
(24, '0024_elite_shortlist', '2026-11-08 14:24:37'),
(25, '0025_agent_contact_mode', '2026-11-08 19:29:54'),
(26, '0026_experience_exclusive', '2026-11-09 08:02:22'),
(27, '0027_promotions', '2026-11-09 08:02:23'),
(28, '0028_seo_concierge', '2026-11-09 08:02:23'),
(29, '0029_service_options', '2026-11-09 08:36:09'),
(30, '0030_discover_swipe_default', '2026-11-09 14:32:20'),
(31, '0031_host_contact_default', '2026-11-12 20:20:04'),
(32, '0032_platform_upgrades', '2026-11-12 20:20:04'),
(33, '0033_fun_escort_sections_and_public_ratings', '2026-11-12 21:21:10'),
(34, '0034_stories_auto_publish', '2026-11-12 22:49:10'),
(35, '0035_elite_controls_spam_guard', '2026-11-12 23:20:33');

-- --------------------------------------------------------

--
-- Table structure for table `search_history`
--

CREATE TABLE `search_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `query_text` varchar(160) DEFAULT NULL,
  `filters` text DEFAULT NULL,
  `results` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sent_gifts`
--

CREATE TABLE `sent_gifts` (
  `id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `receiver_id` int(10) UNSIGNED NOT NULL,
  `gift_type_id` int(10) UNSIGNED NOT NULL,
  `payment_id` int(10) UNSIGNED DEFAULT NULL,
  `message` varchar(200) DEFAULT NULL,
  `is_seen` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','delivered') NOT NULL DEFAULT 'pending',
  `claimed_at` datetime DEFAULT NULL,
  `wallet_amount` decimal(10,2) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` int(10) UNSIGNED DEFAULT NULL,
  `verify_note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sent_gifts`
--

INSERT INTO `sent_gifts` (`id`, `sender_id`, `receiver_id`, `gift_type_id`, `payment_id`, `message`, `is_seen`, `status`, `claimed_at`, `wallet_amount`, `verified_at`, `verified_by`, `verify_note`, `created_at`) VALUES
(1, 9001, 9011, 1, 2, NULL, 0, 'delivered', NULL, NULL, NULL, NULL, NULL, '2026-10-16 12:01:35'),
(2, 9001, 9025, 1, 3, NULL, 0, 'delivered', NULL, NULL, NULL, NULL, NULL, '2026-10-16 12:16:27'),
(3, 9001, 2, 1, 8, NULL, 0, 'delivered', '2026-10-16 15:51:22', 35.00, NULL, NULL, NULL, '2026-10-16 15:51:07');

-- --------------------------------------------------------

--
-- Table structure for table `seo_404`
--

CREATE TABLE `seo_404` (
  `id` int(10) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `hits` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `referrer` varchar(255) DEFAULT NULL,
  `first_seen` datetime NOT NULL DEFAULT current_timestamp(),
  `last_seen` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `seo_404`
--

INSERT INTO `seo_404` (`id`, `path`, `hits`, `referrer`, `first_seen`, `last_seen`) VALUES
(1, '/admin/landing', 1, 'https://link.zurihub.co.ke/admin/landing', '2026-10-16 17:58:17', '2026-10-16 17:58:17'),
(2, '/phpinfo', 8, NULL, '2026-10-17 01:38:44', '2026-11-03 18:46:48'),
(3, '/info', 8, NULL, '2026-10-17 01:38:46', '2026-11-03 18:46:50'),
(4, '/_profiler/phpinfo', 8, NULL, '2026-10-17 01:38:48', '2026-11-03 18:46:52'),
(5, '/_environment', 8, NULL, '2026-10-17 01:38:48', '2026-11-03 18:46:52'),
(6, '/.well-known/security.txt', 2, NULL, '2026-10-18 14:25:24', '2026-11-08 11:59:48'),
(11, '/admin/analytics', 2, 'https://link.zurihub.co.ke/admin/users', '2026-10-19 10:47:23', '2026-10-19 23:35:07'),
(13, '/config.xml', 1, NULL, '2026-10-23 12:28:17', '2026-10-23 12:28:17'),
(14, '/_vti_pvt/service.pwd', 1, NULL, '2026-10-23 12:28:17', '2026-10-23 12:28:17'),
(15, '/actuator/heapdump', 1, NULL, '2026-10-23 12:28:17', '2026-10-23 12:28:17'),
(16, '/backup.zip', 1, NULL, '2026-10-23 12:28:17', '2026-10-23 12:28:17'),
(17, '/user_secrets.yml', 1, NULL, '2026-10-23 12:28:17', '2026-10-23 12:28:17'),
(18, '/docker-compose.yml', 1, NULL, '2026-10-23 12:28:17', '2026-10-23 12:28:17'),
(19, '/.ssh/id_rsa', 1, NULL, '2026-10-23 12:28:18', '2026-10-23 12:28:18'),
(20, '/.ssh/id_ed25519', 1, NULL, '2026-10-23 12:28:18', '2026-10-23 12:28:18'),
(21, '/backup.tar.gz', 1, NULL, '2026-10-23 12:28:18', '2026-10-23 12:28:18'),
(22, '/.npmrc', 1, NULL, '2026-10-23 12:28:18', '2026-10-23 12:28:18'),
(23, '/server.key', 1, NULL, '2026-10-23 12:28:18', '2026-10-23 12:28:18'),
(24, '/.bash_history', 1, NULL, '2026-10-23 12:28:18', '2026-10-23 12:28:18'),
(25, '/artisan', 2, NULL, '2026-10-24 06:18:22', '2026-10-24 06:18:22'),
(27, '/license.txt', 1, NULL, '2026-10-25 06:09:57', '2026-10-25 06:09:57'),
(28, '/public', 2, NULL, '2026-10-25 11:25:06', '2026-10-25 11:25:12'),
(29, '/public/login', 2, NULL, '2026-10-25 11:25:07', '2026-10-25 11:25:13'),
(30, '/public/contact', 2, NULL, '2026-10-25 11:25:07', '2026-10-25 11:25:14'),
(31, '/public/register', 2, NULL, '2026-10-25 11:25:08', '2026-10-25 11:25:13'),
(36, '/analytics/api/session/properties', 1, NULL, '2026-10-25 20:59:06', '2026-10-25 20:59:06'),
(37, '/reporting/api/session/properties', 1, NULL, '2026-10-25 20:59:06', '2026-10-25 20:59:06'),
(38, '/metabase/api/session/properties', 1, NULL, '2026-10-25 20:59:07', '2026-10-25 20:59:07'),
(39, '/insights/api/session/properties', 1, NULL, '2026-10-25 20:59:07', '2026-10-25 20:59:07'),
(40, '/bi/api/session/properties', 1, NULL, '2026-10-25 20:59:07', '2026-10-25 20:59:07'),
(41, '/data/api/session/properties', 1, NULL, '2026-10-25 20:59:07', '2026-10-25 20:59:07'),
(42, '/stats/api/session/properties', 1, NULL, '2026-10-25 20:59:07', '2026-10-25 20:59:07'),
(43, '/dashboard/api/session/properties', 1, NULL, '2026-10-25 20:59:07', '2026-10-25 20:59:07'),
(44, '/metrics/api/session/properties', 1, NULL, '2026-10-25 20:59:07', '2026-10-25 20:59:07'),
(45, '/blog/robots.txt', 1, 'https://www.link.linkslot.cc//blog//wp-login.php', '2026-10-26 00:27:29', '2026-10-26 00:27:29'),
(46, '/wordpress', 1, 'https://www.link.linkslot.cc//blog//wp-login.php', '2026-10-26 00:27:30', '2026-10-26 00:27:30'),
(47, '/wp', 1, 'https://www.link.linkslot.cc//blog//wp-login.php', '2026-10-26 00:27:30', '2026-10-26 00:27:30'),
(72, '/shell', 4, NULL, '2026-11-04 04:26:20', '2026-11-11 05:07:57'),
(73, '/webshell', 4, NULL, '2026-11-04 04:51:39', '2026-11-11 04:26:01'),
(79, '/admin', 3, 'https://link.zurihub.co.ke/login?next=%2Fadmin', '2026-11-09 09:40:46', '2026-11-12 09:32:52');

-- --------------------------------------------------------

--
-- Table structure for table `seo_pages`
--

CREATE TABLE `seo_pages` (
  `page_key` varchar(40) NOT NULL,
  `title` varchar(120) DEFAULT NULL,
  `description` varchar(300) DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `noindex` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `keywords` varchar(500) DEFAULT NULL,
  `heading` varchar(160) DEFAULT NULL,
  `intro` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `seo_pages`
--

INSERT INTO `seo_pages` (`page_key`, `title`, `description`, `og_image`, `noindex`, `updated_at`, `keywords`, `heading`, `intro`) VALUES
('hosts', NULL, NULL, 'landing/24a6f122b2b1e6b7.jpg', 0, '2026-11-10 00:39:42', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `seo_redirects`
--

CREATE TABLE `seo_redirects` (
  `id` int(10) UNSIGNED NOT NULL,
  `from_path` varchar(255) NOT NULL,
  `to_url` varchar(500) NOT NULL,
  `code` smallint(5) UNSIGNED NOT NULL DEFAULT 301,
  `hits` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` mediumtext DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `updated_by`, `updated_at`) VALUES
('_https_seen', '1', NULL, '2026-10-16 11:03:15'),
('admin_alert_advert', '1', NULL, '2026-11-09 08:02:23'),
('agent_default_bio', 'Meet {name} from {location}. {about}', NULL, '2026-11-08 14:24:37'),
('agent_join_place', 'menu', NULL, '2026-11-08 19:29:54'),
('agent_media_stories_enabled', '1', NULL, '2026-11-12 20:20:04'),
('agent_story_ads', '1', NULL, '2026-11-08 14:24:37'),
('agent_story_count', '12', NULL, '2026-11-08 14:24:37'),
('agent_story_drift', '1', NULL, '2026-11-08 14:24:37'),
('agent_story_enabled', '1', NULL, '2026-11-08 14:24:37'),
('agent_story_fill', '1', NULL, '2026-11-08 14:24:37'),
('agent_story_join', '1', NULL, '2026-11-08 14:24:37'),
('agent_story_max_active', '5', NULL, '2026-11-12 20:20:04'),
('agent_story_max_image_mb', '8', NULL, '2026-11-12 20:20:04'),
('agent_story_max_video_mb', '20', NULL, '2026-11-12 20:20:04'),
('agent_story_pages', 'landing,hosts,discover', NULL, '2026-11-12 20:20:04'),
('agent_story_promo', '1', NULL, '2026-11-08 14:24:37'),
('agent_story_require_approval', '0', 0, '2026-11-12 22:49:10'),
('agent_story_seconds', '5', NULL, '2026-11-08 14:24:37'),
('agent_story_tiers', 'vip,featured,premium,paid,boosted', NULL, '2026-11-08 14:24:37'),
('agent_story_viewer', '1', NULL, '2026-11-08 14:24:37'),
('autorenew_enabled', '0', NULL, '2026-10-16 08:12:11'),
('autorenew_max_tries', '3', NULL, '2026-10-16 08:12:11'),
('autorenew_remind_days', '3', NULL, '2026-10-16 08:12:11'),
('autorenew_retry_days', '2', NULL, '2026-10-16 08:12:11'),
('bank_enabled', '1', 1, '2026-10-16 11:11:17'),
('blur_guests_hosts', '0', NULL, '2026-11-08 14:24:37'),
('booking_enabled', '0', 1, '2026-11-12 09:45:10'),
('boost_credit_minutes', '30', NULL, '2026-10-16 13:38:36'),
('boost_daily_limit', '0', NULL, '2026-10-16 13:38:36'),
('boost_enabled', '1', NULL, '2026-10-16 13:38:36'),
('boost_stack', '1', NULL, '2026-10-16 13:38:36'),
('card_gateway', 'stripe', NULL, '2026-10-16 08:12:11'),
('choice_cards', '{\"dating\":{\"enabled\":true,\"in_picker\":true,\"in_switch\":true,\"order\":1,\"label\":\"Dating\",\"tagline\":\"Find someone special\",\"desc\":\"Real people near you who want something real. Free to join.\",\"points\":[\"Verified profiles\",\"Shown by distance\",\"Chat when you match\"],\"accent\":\"rose\",\"icon\":\"heart\",\"image\":\"\",\"go\":\"\",\"hero_h\":\"Meet someone worth meeting\",\"hero_s\":\"Real people near you who want something real. Join free to see who is here.\",\"cta_text\":\"Start dating\",\"cta_url\":\"/register\",\"lead\":[\"profiles\"],\"hide\":[]},\"fun\":{\"enabled\":true,\"in_picker\":true,\"in_switch\":true,\"order\":2,\"label\":\"Fun\",\"tagline\":\"Good company, good times\",\"desc\":\"Plans for tonight, events and easy-going company with verified Hosts.\",\"points\":[\"Verified Escorts\",\"Call or WhatsApp\",\"Near you\"],\"accent\":\"amber\",\"icon\":\"party-popper\",\"image\":\"\",\"go\":\"\",\"hero_h\":\"Good company, tonight\",\"hero_s\":\"Verified Hosts near you for dinners, events and nights out.\",\"cta_text\":\"See who is around\",\"cta_url\":\"/hosts\",\"lead\":[\"agents_vip\",\"agents_prime\",\"agents_top\",\"agents_new\"],\"hide\":[]},\"elite\":{\"enabled\":true,\"in_picker\":true,\"in_switch\":true,\"order\":3,\"label\":\"Elite\",\"tagline\":\"Meet established people\",\"desc\":\"Accomplished, successful people who know what they want. Private and by request.\",\"points\":[\"Verified members\",\"Private by request\",\"Serious intentions\"],\"accent\":\"ink\",\"icon\":\"gem\",\"image\":\"\",\"go\":\"\",\"hero_h\":\"Meet established, successful people\",\"hero_s\":\"A private circle with verified profiles. Every member decides who reaches them.\",\"cta_text\":\"Explore\",\"cta_url\":\"/elite\",\"lead\":[\"elite\"],\"hide\":[]},\"friends\":{\"enabled\":true,\"in_picker\":true,\"in_switch\":true,\"order\":4,\"label\":\"Friends\",\"tagline\":\"Make new friends\",\"desc\":\"People nearby to hang out with, try new places and share what you love.\",\"points\":[\"Shared interests\",\"Group plans\",\"Near you\"],\"accent\":\"teal\",\"icon\":\"users\",\"image\":\"\",\"go\":\"\",\"hero_h\":\"Your next good friend is nearby\",\"hero_s\":\"Meet people who like what you like, a short trip from home.\",\"cta_text\":\"Find friends\",\"cta_url\":\"/register\",\"lead\":[\"profiles\"],\"hide\":[]}}', NULL, '2026-11-12 21:21:10'),
('choice_default', 'dating', NULL, '2026-11-12 17:10:20'),
('choice_delay', '1', NULL, '2026-11-12 17:10:20'),
('choice_enabled', '1', NULL, '2026-11-12 17:10:20'),
('choice_exclusive', '1', NULL, '2026-11-09 08:02:22'),
('choice_members', '1', NULL, '2026-11-12 17:10:20'),
('choice_skip_text', 'Show me everything', NULL, '2026-11-12 17:10:20'),
('choice_subtitle', 'Choose one. We remember it on this device, and you can change it any time.', NULL, '2026-11-12 17:10:20'),
('choice_switcher', '1', NULL, '2026-11-12 17:10:20'),
('choice_title', 'What brings you here?', NULL, '2026-11-12 17:10:20'),
('daily_picks_featured', '1', NULL, '2026-11-12 20:20:04'),
('discover_elite_count', '8', NULL, '2026-11-08 19:29:54'),
('discover_elite_enabled', '1', NULL, '2026-11-08 19:29:54'),
('discover_focus_count', '24', NULL, '2026-11-09 08:02:22'),
('discover_mode_filters', '1', NULL, '2026-11-08 19:29:54'),
('discover_mode_switch', '1', NULL, '2026-11-08 19:29:54'),
('dm_daily_unlocks', '10', NULL, '2026-10-16 13:38:36'),
('dm_messages_per_unlock', '3', NULL, '2026-10-16 13:38:36'),
('dm_paid_enabled', '1', NULL, '2026-10-16 13:38:36'),
('dm_to_hosts', '0', NULL, '2026-10-16 13:38:36'),
('dm_welcome_credits', '0', NULL, '2026-10-16 13:38:36'),
('favicon', 'site/2c0cb6f89086df9f.png', 1, '2026-10-16 11:05:59'),
('featured_enabled', '1', NULL, '2026-10-16 13:38:36'),
('gift_claim_mode', 'auto', NULL, '2026-10-16 13:38:36'),
('gift_receiver_percent', '70', NULL, '2026-10-16 13:38:36'),
('hero_art_1', 'hero/8869e0254b0f9e81.jpg', 1, '2026-10-16 19:52:24'),
('hero_art_2', 'hero/9013919f50afddf1.jpg', 1, '2026-10-16 20:07:47'),
('hero_art_3', 'hero/d2861aa0d4a2cab6.jpg', 1, '2026-10-16 20:08:53'),
('hero_cta_member_mode', 'discover', NULL, '2026-10-16 13:38:36'),
('hero_cta_member_text', 'Open Discover', NULL, '2026-10-16 13:38:36'),
('hero_layout', 'collage', 1, '2026-10-16 15:24:01'),
('hero_mobile', 'compact', NULL, '2026-10-16 13:38:36'),
('host_area_slug', 'concierge', 1, '2026-10-16 15:17:15'),
('host_banned_terms', '', 1, '2026-11-12 09:50:51'),
('host_connections_allow_gallery', '1', NULL, '2026-11-08 14:24:37'),
('host_connections_card_style', 'compact', NULL, '2026-11-08 14:24:37'),
('host_connections_enabled', '1', 1, '2026-11-12 09:53:49'),
('host_connections_max_photos', '6', NULL, '2026-11-08 14:24:37'),
('host_connections_max_services', '6', NULL, '2026-11-08 14:24:37'),
('host_connections_menu_label', 'Escorts profiles', 1, '2026-11-12 09:54:38'),
('host_connections_page_subtitle', 'Browse adult profiles for friendly conversations, public events, shared interests and non-sexual companionship. Take your time, be respectful and keep safety in mind.', NULL, '2026-11-08 14:24:37'),
('host_connections_page_title', 'Escorts profiles', 1, '2026-11-12 09:54:38'),
('host_connections_require_approval', '1', NULL, '2026-10-23 03:12:00'),
('host_connections_show_contact', '1', NULL, '2026-10-23 03:12:00'),
('host_connections_show_in_footer', '1', NULL, '2026-11-08 14:24:37'),
('host_connections_show_in_header', '1', NULL, '2026-11-08 14:24:37'),
('host_connections_show_on_homepage', '0', NULL, '2026-11-08 14:24:37'),
('host_contact_headline', '', NULL, '2026-11-08 19:29:54'),
('host_contact_intro_enabled', '1', NULL, '2026-11-08 19:29:54'),
('host_listing_price_monthly', '300', 1, '2026-11-12 09:51:45'),
('host_listing_price_weekly', '100', 1, '2026-11-12 09:51:45'),
('host_listing_price_yearly', '3000', 1, '2026-11-12 09:51:45'),
('host_min_about_chars', '20', 1, '2026-11-12 09:47:55'),
('host_min_photos', '2', 1, '2026-11-12 09:53:27'),
('host_module_mode', 'contact', NULL, '2026-11-12 20:20:04'),
('host_rules_text', '- Escorts must be 18 or over and ID-verified.\n- Respect each other\'s boundaries. Either person can end a booking if they feel unsafe.\n- Use the report button if anything feels wrong.', 1, '2026-11-12 09:50:51'),
('host_rules_text_contact', '- Escorts must be 18 or over and ID-verified.\n- Nothing is booked or paid for on this platform. Anything you agree is between the two of you.\n- Respect each other\'s boundaries. Either person can walk away at any point.\n- Use the report button if anything feels wrong.', 1, '2026-11-12 09:50:51'),
('host_selfie_required', '0', 1, '2026-11-12 09:47:55'),
('host_seo_faq', '', NULL, '2026-11-09 08:02:23'),
('host_seo_faq_show', '1', NULL, '2026-11-09 08:02:23'),
('host_seo_index_gate', '1', NULL, '2026-11-09 08:02:23'),
('host_seo_keywords', 'escorts in Kenya, escorts near me, escorts in Kilimani, escorts in Karen, escorts in Kasarani', 1, '2026-11-10 00:40:16'),
('host_seo_profile_desc', '', NULL, '2026-11-09 08:02:23'),
('host_seo_profile_keywords', '1', NULL, '2026-11-09 08:02:23'),
('host_seo_profile_title', '{name}, {category} in {place}', NULL, '2026-11-09 08:02:23'),
('host_seo_schema', '1', NULL, '2026-11-09 08:02:23'),
('host_service_card_style', 'tint', NULL, '2026-11-08 19:29:54'),
('host_service_prices', '1', NULL, '2026-11-08 19:29:54'),
('host_show_rates', '0', NULL, '2026-11-10 00:42:10'),
('host_term_plural', 'Escorts', 1, '2026-11-10 00:30:02'),
('host_term_singular', 'Escort', 1, '2026-11-10 00:30:02'),
('host_url_slug', 'concierges', 1, '2026-10-16 15:17:15'),
('host_verify_id_enabled', '1', NULL, '2026-10-16 08:12:11'),
('host_verify_photo_enabled', '0', NULL, '2026-10-16 08:12:11'),
('host_verify_rule', 'any', NULL, '2026-10-16 08:12:11'),
('hosts_contact_visibility', 'everyone', NULL, '2026-11-08 14:24:37'),
('hosts_directory_access', 'everyone', 1, '2026-11-12 09:58:06'),
('hosts_hero_image', 'hero/0b33ec6247dda791.jpg', 1, '2026-11-12 09:58:34'),
('hosts_per_page', '48', 1, '2026-11-12 09:57:44'),
('intl_access_days', '30', 1, '2026-10-16 17:26:13'),
('intl_access_fee', '500', 1, '2026-10-16 17:26:13'),
('intl_approval', '1', 1, '2026-10-16 17:26:13'),
('intl_contact_types', '[\"whatsapp\",\"mobile\",\"telegram\"]', 1, '2026-10-16 17:26:13'),
('intl_enabled', '1', 1, '2026-10-16 17:26:13'),
('intl_intro', 'Friendships, language exchange and romance with people in other countries. Everyone here chose to be found.', 1, '2026-10-16 17:26:13'),
('intl_join_days', '30', 1, '2026-10-16 17:26:13'),
('intl_join_fee', '500', 1, '2026-10-16 17:26:13'),
('intl_mode', 'access', 1, '2026-10-16 17:26:13'),
('intl_msg_included', '1', 1, '2026-10-16 17:26:13'),
('intl_premium_free', 'premium', 1, '2026-10-16 17:26:13'),
('intl_preview', '1', 1, '2026-10-16 17:26:13'),
('intl_require_verified', '0', 1, '2026-10-16 17:26:13'),
('intl_title', 'Meet the world', 1, '2026-10-16 17:26:13'),
('launch_access_enabled', '0', NULL, '2026-11-12 20:20:04'),
('logo_auth', 'library/181f18a79ce9e25d.png', 1, '2026-11-10 00:35:59'),
('logo_auth_dark', 'site/1b230b87530cca84.png', 1, '2026-11-10 00:36:22'),
('logo_compact', 'site/11a1895a4bc60514.png', 1, '2026-10-16 11:05:42'),
('logo_dark', 'site/1b230b87530cca84.png', 1, '2026-10-16 11:07:52'),
('logo_footer', 'site/4ad34c0215314573.png', 1, '2026-10-16 11:07:33'),
('logo_footer_dark', 'site/1b230b87530cca84.png', 1, '2026-10-16 11:07:38'),
('logo_full', 'site/a5fcb4436effc3e4.png', 1, '2026-10-16 11:05:35'),
('logo_h_admin', '50', 1, '2026-10-16 17:34:04'),
('logo_h_app_header', '50', 1, '2026-10-16 17:32:04'),
('logo_h_header', '54', 1, '2026-10-16 11:08:22'),
('logo_h_header_member', '44', 1, '2026-10-16 23:52:56'),
('logo_h_header_mobile', '50', 1, '2026-10-16 17:33:36'),
('logo_h_header_scrolled', '50', 1, '2026-10-16 11:08:22'),
('logo_header_member', 'library/181f18a79ce9e25d.png', 1, '2026-10-16 23:50:42'),
('logo_header_scrolled', 'site/4ad34c0215314573.png', 1, '2026-10-16 11:06:15'),
('logo_header_scrolled_dark', 'site/1b230b87530cca84.png', 1, '2026-10-16 11:07:00'),
('logo_icon', 'site/eb09ad23d2407863.png', 1, '2026-10-16 11:05:51'),
('logo_mobile', 'site/3dc7c95ead56a575.png', 1, '2026-10-16 13:18:06'),
('logo_mobile_dark', 'site/1b230b87530cca84.png', 1, '2026-10-16 13:18:20'),
('logo_mobile_white', 'site/4ad34c0215314573.png', 1, '2026-10-16 13:18:34'),
('logo_sidepanel', 'library/181f18a79ce9e25d.png', 1, '2026-10-16 23:52:12'),
('logo_w_footer', '155', 1, '2026-10-16 11:08:22'),
('logo_w_menu', '160', 1, '2026-10-16 17:31:04'),
('logo_white', 'site/1b230b87530cca84.png', 1, '2026-10-16 11:06:32'),
('manual_mpesa_details', 'Paybill 123456, account CONNECTHUB.', 1, '2026-10-16 11:10:44'),
('manual_mpesa_enabled', '1', 1, '2026-10-16 11:10:44'),
('mpesa_b2c_auto', '0', NULL, '2026-10-16 08:12:11'),
('mpesa_b2c_auto_limit', '5000', NULL, '2026-10-16 08:12:11'),
('mpesa_b2c_command', 'BusinessPayment', NULL, '2026-10-16 08:12:11'),
('mpesa_b2c_enabled', '0', NULL, '2026-10-16 08:12:11'),
('mpesa_b2c_max', '150000', NULL, '2026-10-16 08:12:11'),
('mpesa_c2b_api', 'v2', NULL, '2026-10-16 08:12:11'),
('mpesa_c2b_enabled', '0', NULL, '2026-10-16 08:12:11'),
('mpesa_c2b_response', 'Completed', NULL, '2026-10-16 08:12:11'),
('mpesa_c2b_validation', '0', NULL, '2026-10-16 08:12:11'),
('og_image', 'site/701baa472b6bc25c.jpg', 1, '2026-10-16 13:15:28'),
('passcode_enabled', '1', NULL, '2026-11-08 19:29:54'),
('passcode_idle_minutes', '30', NULL, '2026-11-08 19:29:54'),
('paypal_currency', 'USD', NULL, '2026-10-16 08:12:11'),
('paypal_enabled', '0', NULL, '2026-10-16 08:12:11'),
('paypal_env', 'sandbox', NULL, '2026-10-16 08:12:11'),
('paypal_rate', '129', NULL, '2026-10-16 08:12:11'),
('paystack_enabled', '0', NULL, '2026-10-16 08:12:11'),
('photo_cap_all', '30', NULL, '2026-10-16 13:38:36'),
('photo_uploads_per_day', '0', NULL, '2026-10-16 13:38:36'),
('promo_enabled', '1', NULL, '2026-11-09 08:02:23'),
('promo_max_shown', '6', NULL, '2026-11-09 08:02:23'),
('promo_review', '1', NULL, '2026-11-09 08:02:23'),
('promo_rules', 'No adult services, escorts or sexual content.\r\nNothing illegal, misleading or unsafe.\r\nThe picture must be yours to use, and sharp enough to look good small.\r\nThe link must go to a real website, shop or social page.\r\nWe may decline or remove an advert that breaks these rules, and refund it if it never ran.', NULL, '2026-11-09 08:02:23'),
('public_dock', '1', NULL, '2026-11-08 14:24:37'),
('public_ratings_enabled', '0', NULL, '2026-11-12 21:21:10'),
('pwa_icon', 'hero/1c38000c553fc0d4.jpg', 1, '2026-10-16 13:51:08'),
('seo_ai_crawlers', 'allow', NULL, '2026-10-16 08:12:11'),
('seo_bing_verification', '', NULL, '2026-10-16 08:12:11'),
('seo_blog_author', '', NULL, '2026-10-16 08:12:11'),
('seo_default_description', '', NULL, '2026-10-16 08:12:11'),
('seo_facebook_verification', '', NULL, '2026-10-16 08:12:11'),
('seo_force_https', '0', NULL, '2026-10-16 08:12:11'),
('seo_google_verification', '', NULL, '2026-10-16 08:12:11'),
('seo_gtm_id', '', NULL, '2026-10-16 08:12:11'),
('seo_indexnow_enabled', '1', NULL, '2026-10-16 08:12:11'),
('seo_indexnow_key', 'b325a8ad52fac49240aa8d6357b9086c', NULL, '2026-10-16 20:14:13'),
('seo_locale', 'en_KE', NULL, '2026-10-16 08:12:11'),
('seo_log_404', '1', NULL, '2026-10-16 08:12:11'),
('seo_noindex_members', '1', NULL, '2026-10-16 08:12:11'),
('seo_org_city', 'Nairobi', NULL, '2026-10-16 08:12:11'),
('seo_org_country', 'KE', NULL, '2026-10-16 08:12:11'),
('seo_org_email', '', NULL, '2026-10-16 08:12:11'),
('seo_org_legal_name', '', NULL, '2026-10-16 08:12:11'),
('seo_org_phone', '', NULL, '2026-10-16 08:12:11'),
('seo_org_region', 'Nairobi County', NULL, '2026-10-16 08:12:11'),
('seo_pinterest_verification', '', NULL, '2026-10-16 08:12:11'),
('seo_rss_enabled', '1', NULL, '2026-10-16 08:12:11'),
('seo_schema_breadcrumbs', '1', NULL, '2026-10-16 08:12:11'),
('seo_schema_org', '1', NULL, '2026-10-16 08:12:11'),
('seo_schema_sitelinks', '1', NULL, '2026-10-16 08:12:11'),
('seo_sitemap_hosts', '1', NULL, '2026-10-16 08:12:11'),
('seo_sitemap_images', '1', NULL, '2026-10-16 08:12:11'),
('seo_sitemap_locations', '1', NULL, '2026-10-16 08:12:11'),
('seo_title_template', '%page% · %site%', NULL, '2026-10-16 08:12:11'),
('seo_www', 'keep', NULL, '2026-10-16 08:12:11'),
('seo_yandex_verification', '', NULL, '2026-10-16 08:12:11'),
('verification_assign', 'choose', NULL, '2026-10-16 13:38:36'),
('wallet_enabled', '1', NULL, '2026-10-16 13:38:36'),
('wallet_max_numbers', '4', NULL, '2026-10-16 13:38:36'),
('wallet_max_open_payouts', '2', NULL, '2026-10-16 13:38:36'),
('wallet_min_payout', '500', NULL, '2026-10-16 13:38:36');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(40) NOT NULL,
  `name` varchar(60) NOT NULL,
  `tagline` varchar(160) DEFAULT NULL,
  `tier` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `price_monthly` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_weekly` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_yearly` decimal(10,2) NOT NULL DEFAULT 0.00,
  `features` mediumtext NOT NULL,
  `badge_color` varchar(9) DEFAULT NULL,
  `badge_icon` varchar(40) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `slug`, `name`, `tagline`, `tier`, `price_monthly`, `price_weekly`, `price_yearly`, `features`, `badge_color`, `badge_icon`, `is_featured`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'free', 'Free', 'Everything you need to start meeting people.', 0, 0.00, 0.00, 0.00, '{\"daily_swipes\":30,\"daily_superlikes\":0,\"daily_messages\":15,\"daily_rewinds\":0,\"rewind\":false,\"see_who_liked\":false,\"advanced_filters\":false,\"incognito\":false,\"read_receipts\":false,\"message_before_match\":false,\"max_photos\":6,\"boost_credits_monthly\":0,\"profile_visitors\":false,\"no_ads\":false,\"priority_likes\":false}', '#8A8095', 'user', 0, 1, 1, '2026-10-16 06:16:42'),
(2, 'premium', 'Premium', 'More likes, more messages, and see who is interested.', 1, 200.00, 70.00, 1920.00, '{\"daily_swipes\":-1,\"daily_superlikes\":5,\"daily_messages\":-1,\"daily_rewinds\":5,\"rewind\":true,\"see_who_liked\":true,\"advanced_filters\":true,\"incognito\":false,\"read_receipts\":true,\"message_before_match\":false,\"max_photos\":9,\"boost_credits_monthly\":1,\"profile_visitors\":true,\"no_ads\":true,\"priority_likes\":false}', '#F4A63A', 'sparkles', 1, 1, 2, '2026-10-16 06:16:42'),
(3, 'vip', 'VIP', 'Be seen first, browse privately and message anyone.', 2, 500.00, 170.00, 4800.00, '{\"daily_swipes\":-1,\"daily_superlikes\":15,\"daily_messages\":-1,\"daily_rewinds\":-1,\"rewind\":true,\"see_who_liked\":true,\"advanced_filters\":true,\"incognito\":true,\"read_receipts\":true,\"message_before_match\":true,\"max_photos\":12,\"boost_credits_monthly\":4,\"profile_visitors\":true,\"no_ads\":true,\"priority_likes\":true}', '#6C5CE7', 'crown', 0, 1, 3, '2026-10-16 06:16:42');

-- --------------------------------------------------------

--
-- Table structure for table `swipes`
--

CREATE TABLE `swipes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `swiper_id` int(10) UNSIGNED NOT NULL,
  `swiped_id` int(10) UNSIGNED NOT NULL,
  `direction` enum('like','nope','superlike') NOT NULL,
  `dwell_ms` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `swipes`
--

INSERT INTO `swipes` (`id`, `swiper_id`, `swiped_id`, `direction`, `dwell_ms`, `created_at`) VALUES
(1, 9001, 9011, 'superlike', 0, '2026-10-16 12:00:02'),
(2, 9001, 9016, 'like', 4962, '2026-10-16 11:56:32'),
(3, 9001, 9012, 'like', 87936, '2026-10-16 11:56:34'),
(4, 9001, 9005, 'like', 5766, '2026-10-16 11:57:13'),
(5, 9001, 9006, 'superlike', 192264, '2026-10-16 11:57:16'),
(6, 9001, 9008, 'superlike', 6579, '2026-10-16 12:12:38'),
(7, 9001, 9009, 'like', 3171, '2026-10-16 12:12:41'),
(8, 9001, 9014, 'like', 4467, '2026-10-16 12:12:45'),
(9, 9001, 9015, 'like', 2199, '2026-10-16 12:12:47'),
(10, 9001, 9025, 'like', 20410, '2026-10-16 12:13:06'),
(11, 9025, 9001, 'like', 56744, '2026-10-16 12:13:23'),
(12, 9025, 9011, 'like', 15829, '2026-10-16 15:00:00'),
(13, 9001, 9030, 'superlike', 26804, '2026-11-10 00:22:18'),
(14, 9001, 9004, 'superlike', 0, '2026-11-12 09:27:17'),
(15, 9001, 9007, 'like', 0, '2026-11-12 09:40:07');

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `location` varchar(80) DEFAULT NULL,
  `quote` text NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `photo_path` varchar(255) DEFAULT NULL,
  `show_landing` tinyint(1) NOT NULL DEFAULT 1,
  `show_auth` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `typing_status`
--

CREATE TABLE `typing_status` (
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','host','admin') NOT NULL DEFAULT 'user',
  `admin_role_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('active','pending','suspended','banned','deactivated') NOT NULL DEFAULT 'active',
  `status_reason` varchar(255) DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_active_at` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `login_attempts` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `totp_secret` varchar(64) DEFAULT NULL,
  `totp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `featured_until` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `phone`, `password_hash`, `role`, `admin_role_id`, `status`, `status_reason`, `email_verified_at`, `last_login_at`, `last_active_at`, `last_ip`, `login_attempts`, `locked_until`, `must_change_password`, `totp_secret`, `totp_enabled`, `featured_until`, `created_at`, `updated_at`) VALUES
(1, 'admin@example.com', NULL, '$2y$12$yoEEn.6D9G37hA0Pxmr2BOdOUeHPAgxv2Hyf/2Gg2E9UpZ8FpL9Cu', 'admin', 1, 'active', NULL, '2026-10-16 06:16:44', '2026-11-12 23:27:22', '2026-11-12 23:36:50', '41.209.14.76', 0, NULL, 0, NULL, 0, NULL, '2026-10-16 06:16:44', '2026-11-12 23:36:50'),
(2, 'juicemwangi7@gmail.com', '254758256440', '$2y$12$VS.Pyez6Yann1g7ZEdeo4.zhxRRbu0cW4U0Pvuivx86pLyBFWwXeu', 'host', NULL, 'active', NULL, '2026-10-16 11:27:53', '2026-11-12 23:24:35', '2026-11-12 23:35:51', '41.209.14.76', 0, NULL, 0, NULL, 0, NULL, '2026-10-16 11:27:53', '2026-11-12 23:35:51'),
(9001, 'wanjiru.njoroge@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-07-13 04:48:22', '2026-11-12 23:20:33', '2026-11-12 23:23:36', '41.209.14.76', 0, NULL, 0, NULL, 0, '2026-10-23 13:02:47', '2026-07-12 04:48:22', '2026-11-12 23:23:36'),
(9002, 'akinyi.ochieng@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-08-07 04:48:22', '2026-10-15 04:48:22', '2026-10-15 23:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-08-06 04:48:22', '2026-10-16 04:48:22'),
(9003, 'zawadi.mwakazi@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-09-03 04:48:22', '2026-10-15 04:48:22', '2026-10-16 03:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-09-02 04:48:22', '2026-10-16 04:48:22'),
(9004, 'chebet.kiplagat@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-06-09 04:48:22', '2026-10-15 04:48:22', '2026-10-15 19:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-06-08 04:48:22', '2026-10-16 04:48:22'),
(9005, 'mwende.musyoka@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-09-14 04:48:22', '2026-10-15 04:48:22', '2026-10-16 01:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-09-13 04:48:22', '2026-10-16 04:48:22'),
(9006, 'njeri.kariuki@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-05-10 04:48:22', '2026-10-15 04:48:22', '2026-10-16 00:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-05-09 04:48:22', '2026-10-16 04:48:22'),
(9007, 'halima.abdalla@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-08-20 04:48:22', '2026-10-15 04:48:22', '2026-10-15 14:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-08-19 04:48:22', '2026-10-16 04:48:22'),
(9008, 'purity.kinya@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-09-25 04:48:22', '2026-10-15 04:48:22', '2026-10-14 22:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-09-24 04:48:22', '2026-10-16 04:48:22'),
(9009, 'kevin.mutua@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-07-21 04:48:22', '2026-10-15 04:48:22', '2026-10-16 02:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-07-20 04:48:22', '2026-10-16 04:48:22'),
(9010, 'brian.kiprono@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-08-14 04:48:22', '2026-10-15 04:48:22', '2026-10-15 21:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-08-13 04:48:22', '2026-10-16 04:48:22'),
(9011, 'victor.omondi@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-05-30 04:48:22', '2026-10-15 04:48:22', '2026-10-15 22:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-05-29 04:48:22', '2026-10-16 04:48:22'),
(9012, 'samuel.karanja@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-07-04 04:48:22', '2026-10-15 04:48:22', '2026-10-15 16:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-07-03 04:48:22', '2026-10-16 04:48:22'),
(9013, 'hassan.abdi@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-09-08 04:48:22', '2026-10-15 04:48:22', '2026-10-15 20:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-09-07 04:48:22', '2026-10-16 04:48:22'),
(9014, 'dennis.wekesa@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-09-29 04:48:22', '2026-10-15 04:48:22', '2026-10-15 08:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-09-28 04:48:22', '2026-10-16 04:48:22'),
(9015, 'ian.macharia@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-08-26 04:48:22', '2026-10-15 04:48:22', '2026-10-15 02:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-08-25 04:48:22', '2026-10-16 04:48:22'),
(9016, 'felix.barasa@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'user', NULL, 'active', NULL, '2026-09-20 04:48:22', '2026-10-14 04:48:22', '2026-10-14 04:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-09-19 04:48:22', '2026-10-16 04:48:22'),
(9017, 'faith.wambui@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'host', NULL, 'active', NULL, '2026-05-20 04:48:22', '2026-10-15 04:48:22', '2026-10-15 23:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-05-19 04:48:22', '2026-10-16 04:48:22'),
(9018, 'naomi.chepkoech@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'host', NULL, 'active', NULL, '2026-04-30 04:48:22', '2026-10-15 04:48:22', '2026-10-15 23:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-04-29 04:48:22', '2026-10-16 04:48:22'),
(9019, 'amina.hassan@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'host', NULL, 'active', NULL, '2026-06-17 04:48:22', '2026-10-15 04:48:22', '2026-10-15 23:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-06-16 04:48:22', '2026-10-16 04:48:22'),
(9020, 'grace.nyambura@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'host', NULL, 'active', NULL, '2026-06-01 04:48:22', '2026-10-15 04:48:22', '2026-10-15 23:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-05-31 04:48:22', '2026-10-16 04:48:22'),
(9021, 'mercy.naserian@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'host', NULL, 'active', NULL, '2026-07-14 04:48:22', '2026-10-15 04:48:22', '2026-10-15 23:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-07-13 04:48:22', '2026-10-16 04:48:22'),
(9022, 'mwanaisha.juma@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'host', NULL, 'active', NULL, '2026-07-31 04:48:22', '2026-10-15 04:48:22', '2026-10-15 23:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-07-30 04:48:22', '2026-10-16 04:48:22'),
(9023, 'peter.njuguna@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'host', NULL, 'active', NULL, '2026-06-29 04:48:22', '2026-10-15 04:48:22', '2026-10-15 23:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-06-28 04:48:22', '2026-10-16 04:48:22'),
(9024, 'joseph.kimani@demo.connecthub.test', NULL, '$2y$12$30Otoee860.ZL6uMRizpiOcx1d/sM3TonBUMB4N/kNU1g7LtAlZiy', 'host', NULL, 'active', NULL, '2026-07-19 04:48:22', '2026-10-15 04:48:22', '2026-10-15 23:48:22', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-07-18 04:48:22', '2026-10-16 04:48:22'),
(9025, 'lewiskamunya@gmail.com', NULL, '$2y$12$6t6u3dtigCcvo0cccQYQ/OA.XKUlTFSk9gHn70OiIogQ1Q811lG/K', 'user', NULL, 'active', NULL, '2026-10-16 12:09:04', '2026-10-16 23:47:53', '2026-10-16 23:52:27', '41.209.14.76', 0, NULL, 0, NULL, 0, NULL, '2026-10-16 12:09:04', '2026-10-16 23:52:27'),
(9026, 'shee@connecthub.com', '254710896330', '$2y$12$0Y96ZWKANoZun.lUasjjFuc4r16VPhKW61bfRopOSfjCpzpLi4Q3y', 'host', NULL, 'active', NULL, '2026-10-16 12:49:57', '2026-10-17 22:20:38', '2026-10-17 22:21:45', '41.209.14.76', 0, NULL, 0, NULL, 0, NULL, '2026-10-16 12:49:57', '2026-10-17 22:21:45'),
(9027, 'nia.wambui@demo.connecthub.test', NULL, '$2y$12$JLqrNMiwAnrgz4xD/oqqIeJ5xMOnFdpzSZeZbmYPUXXVWN6BwhMMK', 'user', NULL, 'active', NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, 0, NULL, '2026-11-08 14:52:45', '2026-11-08 14:52:45'),
(9028, 'amara.achieng@demo.connecthub.test', NULL, '$2y$12$JLqrNMiwAnrgz4xD/oqqIeJ5xMOnFdpzSZeZbmYPUXXVWN6BwhMMK', 'user', NULL, 'active', NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, 0, NULL, '2026-11-08 14:52:45', '2026-11-08 14:52:45'),
(9029, 'denniszurihub@gmail.com', '254758256444', '$2y$12$INDBiUqDJy.AJteXSCOW3.pYwlF9o/JF9czAygOF8EqwIY4EyD.MC', 'host', NULL, 'active', NULL, '2026-11-09 09:33:44', '2026-11-12 20:55:52', '2026-11-12 20:55:52', '154.159.252.86', 0, NULL, 0, NULL, 0, NULL, '2026-11-09 09:33:44', '2026-11-12 20:55:52'),
(9030, 'amina.demo@demo.test', '+254700000101', '$2y$10$iYCXdJJcXK.m29pMuvIfv.noESRjdyzxWbfwAsaurrmYRtRqrG2/W', 'user', NULL, 'active', NULL, '2026-11-09 03:49:59', NULL, '2026-11-09 03:49:59', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(9031, 'brian.demo@demo.test', '+254700000102', '$2y$10$iYCXdJJcXK.m29pMuvIfv.noESRjdyzxWbfwAsaurrmYRtRqrG2/W', 'user', NULL, 'active', NULL, '2026-11-09 03:49:59', NULL, '2026-11-09 03:49:59', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(9032, 'cynthia.demo@demo.test', '+254700000103', '$2y$10$iYCXdJJcXK.m29pMuvIfv.noESRjdyzxWbfwAsaurrmYRtRqrG2/W', 'user', NULL, 'active', NULL, '2026-11-09 03:49:59', NULL, '2026-11-09 03:49:59', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(9033, 'daniel.demo@demo.test', '+254700000201', '$2y$10$iYCXdJJcXK.m29pMuvIfv.noESRjdyzxWbfwAsaurrmYRtRqrG2/W', 'user', NULL, 'active', NULL, '2026-11-09 03:49:59', NULL, '2026-11-09 03:49:59', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(9034, 'esther.demo@demo.test', '+254700000202', '$2y$10$iYCXdJJcXK.m29pMuvIfv.noESRjdyzxWbfwAsaurrmYRtRqrG2/W', 'user', NULL, 'active', NULL, '2026-11-09 03:49:59', NULL, '2026-11-09 03:49:59', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(9035, 'felix.demo@demo.test', '+254700000203', '$2y$10$iYCXdJJcXK.m29pMuvIfv.noESRjdyzxWbfwAsaurrmYRtRqrG2/W', 'user', NULL, 'active', NULL, '2026-11-09 03:49:59', NULL, '2026-11-09 03:49:59', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(9036, 'grace.demo@demo.test', '+254700000301', '$2y$10$iYCXdJJcXK.m29pMuvIfv.noESRjdyzxWbfwAsaurrmYRtRqrG2/W', 'host', NULL, 'active', NULL, '2026-11-09 03:49:59', NULL, '2026-11-09 03:49:59', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(9037, 'hassan.demo@demo.test', '+254700000302', '$2y$10$iYCXdJJcXK.m29pMuvIfv.noESRjdyzxWbfwAsaurrmYRtRqrG2/W', 'host', NULL, 'active', NULL, '2026-11-09 03:49:59', NULL, '2026-11-09 03:49:59', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(9038, 'irene.demo@demo.test', '+254700000303', '$2y$10$iYCXdJJcXK.m29pMuvIfv.noESRjdyzxWbfwAsaurrmYRtRqrG2/W', 'host', NULL, 'active', NULL, '2026-11-09 03:49:59', NULL, '2026-11-09 03:49:59', NULL, 0, NULL, 0, NULL, 0, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59');

-- --------------------------------------------------------

--
-- Table structure for table `user_interests`
--

CREATE TABLE `user_interests` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `interest_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_interests`
--

INSERT INTO `user_interests` (`user_id`, `interest_id`) VALUES
(9001, 2),
(9001, 16),
(9001, 24),
(9001, 31),
(9001, 33),
(9001, 51),
(9001, 56),
(9002, 12),
(9002, 13),
(9002, 21),
(9002, 26),
(9002, 30),
(9002, 43),
(9003, 1),
(9003, 5),
(9003, 19),
(9003, 25),
(9003, 30),
(9003, 33),
(9004, 2),
(9004, 6),
(9004, 8),
(9004, 11),
(9004, 13),
(9004, 31),
(9004, 44),
(9005, 16),
(9005, 22),
(9005, 37),
(9005, 38),
(9005, 39),
(9005, 54),
(9006, 13),
(9006, 17),
(9006, 31),
(9006, 44),
(9006, 46),
(9006, 48),
(9007, 1),
(9007, 5),
(9007, 13),
(9007, 33),
(9007, 35),
(9007, 36),
(9008, 2),
(9008, 3),
(9008, 4),
(9008, 33),
(9008, 46),
(9008, 58),
(9009, 7),
(9009, 9),
(9009, 16),
(9009, 39),
(9009, 40),
(9009, 48),
(9009, 56),
(9010, 6),
(9010, 9),
(9010, 11),
(9010, 15),
(9010, 44),
(9010, 46),
(9011, 1),
(9011, 17),
(9011, 29),
(9011, 31),
(9011, 42),
(9011, 52),
(9012, 11),
(9012, 13),
(9012, 33),
(9012, 48),
(9012, 51),
(9012, 60),
(9013, 1),
(9013, 5),
(9013, 12),
(9013, 19),
(9013, 33),
(9013, 57),
(9014, 7),
(9014, 9),
(9014, 15),
(9014, 21),
(9014, 25),
(9014, 41),
(9015, 3),
(9015, 18),
(9015, 48),
(9015, 49),
(9015, 50),
(9015, 59),
(9016, 24),
(9016, 25),
(9016, 27),
(9016, 28),
(9016, 30),
(9016, 53),
(9017, 1),
(9017, 16),
(9017, 21),
(9017, 24),
(9017, 30),
(9018, 1),
(9018, 2),
(9018, 16),
(9018, 33),
(9018, 51),
(9018, 55),
(9019, 1),
(9019, 19),
(9019, 33),
(9019, 36),
(9019, 55),
(9020, 1),
(9020, 17),
(9020, 29),
(9020, 31),
(9020, 52),
(9021, 2),
(9021, 3),
(9021, 6),
(9021, 7),
(9021, 8),
(9021, 11),
(9022, 1),
(9022, 5),
(9022, 12),
(9022, 30),
(9022, 55),
(9023, 1),
(9023, 7),
(9023, 9),
(9023, 13),
(9023, 24),
(9024, 3),
(9024, 4),
(9024, 33),
(9024, 48),
(9024, 57),
(9024, 58),
(9025, 1),
(9025, 3),
(9025, 6),
(9025, 17),
(9025, 21),
(9025, 22),
(9025, 25),
(9025, 31),
(9025, 35),
(9025, 50),
(9025, 51),
(9025, 56);

-- --------------------------------------------------------

--
-- Table structure for table `user_locations`
--

CREATE TABLE `user_locations` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `latitude` decimal(9,6) NOT NULL,
  `longitude` decimal(9,6) NOT NULL,
  `location_id` int(10) UNSIGNED DEFAULT NULL,
  `county_id` int(10) UNSIGNED DEFAULT NULL,
  `town_name` varchar(100) DEFAULT NULL,
  `county_name` varchar(100) DEFAULT NULL,
  `country` varchar(80) NOT NULL DEFAULT 'Kenya',
  `source` enum('gps','manual') NOT NULL DEFAULT 'manual',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_locations`
--

INSERT INTO `user_locations` (`user_id`, `latitude`, `longitude`, `location_id`, `county_id`, `town_name`, `county_name`, `country`, `source`, `updated_at`) VALUES
(2, -1.220000, 36.897000, 100, 47, 'Kasarani', 'Nairobi', 'Kenya', 'manual', '2026-10-16 14:17:13'),
(9001, -1.150000, 36.980000, 112, 22, 'Ruiru', 'Kiambu', 'Kenya', 'gps', '2026-10-16 15:48:43'),
(9002, -0.095900, 34.773100, 89, 42, 'Kisumu City', 'Kisumu', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9003, -4.017800, 39.716800, 107, 1, 'Nyali', 'Mombasa', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9004, 0.517900, 35.266500, 74, 27, 'Eldoret', 'Uasin Gishu', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9005, -1.269400, 36.815200, 95, 47, 'Westlands', 'Nairobi', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9006, -1.314900, 36.712900, 97, 47, 'Karen', 'Nairobi', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9007, -3.216500, 40.112300, 130, 3, 'Malindi', 'Kilifi', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9008, 0.013600, 37.069600, 78, 31, 'Nanyuki', 'Laikipia', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9009, -1.477600, 36.947900, 119, 34, 'Kitengela', 'Kajiado', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9010, 0.516200, 35.273600, 74, 27, 'Eldoret', 'Uasin Gishu', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9011, -1.294300, 36.788400, 96, 47, 'Kilimani', 'Nairobi', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9012, -1.197600, 36.779800, 115, 22, 'Ruaka', 'Kiambu', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9013, -4.021500, 39.719200, 107, 1, 'Nyali', 'Mombasa', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9014, -0.299800, 36.081100, 79, 32, 'Nakuru Town', 'Nakuru', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9015, -1.036100, 37.067500, 111, 22, 'Thika', 'Kiambu', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9016, -1.281700, 36.764000, 98, 47, 'Lavington', 'Nairobi', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9017, -1.266400, 36.812900, 95, 47, 'Westlands', 'Nairobi', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9018, -1.322800, 36.713300, 97, 47, 'Karen', 'Nairobi', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9019, -4.042100, 39.667000, 48, 1, 'Mombasa Island', 'Mombasa', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9020, -1.292100, 36.785100, 96, 47, 'Kilimani', 'Nairobi', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9021, -1.393900, 36.763300, 120, 34, 'Ongata Rongai', 'Kajiado', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9022, -4.315600, 39.580900, 132, 2, 'Diani Beach', 'Kwale', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9023, -1.286500, 36.787600, 96, 47, 'Kilimani', 'Nairobi', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9024, -0.719400, 36.436400, 124, 32, 'Naivasha', 'Nakuru', 'Kenya', 'manual', '2026-10-16 04:48:22'),
(9025, -1.150000, 36.970000, 112, 22, 'Ruiru', 'Kiambu', 'Kenya', 'gps', '2026-10-16 12:09:20'),
(9026, -1.150000, 36.960000, 112, 22, 'Ruiru', 'Kiambu', 'Kenya', 'manual', '2026-10-16 12:53:55'),
(9027, -1.269400, 36.815200, NULL, NULL, 'Westlands', 'Nairobi', 'Kenya', 'manual', '2026-11-08 14:52:46'),
(9028, -1.292100, 36.785100, NULL, NULL, 'Kilimani', 'Nairobi', 'Kenya', 'manual', '2026-11-08 14:52:46'),
(9029, -1.171400, 36.835600, 22, 22, NULL, 'Kiambu', 'Kenya', 'manual', '2026-11-09 09:33:44'),
(9030, -1.286389, 36.817223, 95, 47, 'Westlands', 'Nairobi', 'Kenya', 'manual', '2026-11-09 03:49:59'),
(9031, -1.300000, 36.780000, 96, 47, 'Kilimani', 'Nairobi', 'Kenya', 'manual', '2026-11-09 03:49:59'),
(9032, -4.043477, 39.668206, 107, 1, 'Nyali', 'Mombasa', 'Kenya', 'manual', '2026-11-09 03:49:59'),
(9033, -1.246000, 36.806000, NULL, 47, 'Runda', 'Nairobi', 'Kenya', 'manual', '2026-11-09 03:49:59'),
(9034, -1.265000, 36.792000, 98, 47, 'Lavington', 'Nairobi', 'Kenya', 'manual', '2026-11-09 03:49:59'),
(9035, -0.091702, 34.767956, 89, 42, 'Milimani', 'Kisumu', 'Kenya', 'manual', '2026-11-09 03:49:59'),
(9036, -1.292066, 36.821946, 96, 47, 'Kilimani', 'Nairobi', 'Kenya', 'manual', '2026-11-09 03:49:59'),
(9037, -4.061000, 39.675000, 48, 1, 'Old Town', 'Mombasa', 'Kenya', 'manual', '2026-11-09 03:49:59'),
(9038, -0.303099, 36.080026, 79, 32, 'Milimani', 'Nakuru', 'Kenya', 'manual', '2026-11-09 03:49:59');

-- --------------------------------------------------------

--
-- Table structure for table `user_photos`
--

CREATE TABLE `user_photos` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `large_path` varchar(255) NOT NULL,
  `medium_path` varchar(255) NOT NULL,
  `thumb_path` varchar(255) NOT NULL,
  `blur_path` varchar(255) NOT NULL,
  `blur_data_uri` text DEFAULT NULL,
  `width` smallint(5) UNSIGNED DEFAULT NULL,
  `height` smallint(5) UNSIGNED DEFAULT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_vip_only` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `moderation_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `moderated_by` int(10) UNSIGNED DEFAULT NULL,
  `moderated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_photos`
--

INSERT INTO `user_photos` (`id`, `user_id`, `large_path`, `medium_path`, `thumb_path`, `blur_path`, `blur_data_uri`, `width`, `height`, `file_size`, `is_primary`, `is_vip_only`, `sort_order`, `moderation_status`, `rejection_reason`, `moderated_by`, `moderated_at`, `created_at`) VALUES
(1, 9001, 'photos/2026/10/9001_34e116f56e1a_l.jpg', 'photos/2026/10/9001_34e116f56e1a_m.jpg', 'photos/2026/10/9001_34e116f56e1a_t.jpg', 'photos/2026/10/9001_34e116f56e1a_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A3dV1P7CFwhYsalinE0Sv0yKxL+/W6wAvQ1LFqKqgBGMVyuaO5UpH/9k=', 1080, 1350, 35667, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-07-13 04:48:22'),
(2, 9001, 'photos/2026/10/9001_2eaf90532bee_l.jpg', 'photos/2026/10/9001_2eaf90532bee_m.jpg', 'photos/2026/10/9001_2eaf90532bee_t.jpg', 'photos/2026/10/9001_2eaf90532bee_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A2NX1V7KRFRN2etWop/NiV8YyK5u/1aO5YYXOO9SR62qoAVximpalujK2iP/Z', 1080, 1350, 37635, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-07-13 04:48:22'),
(3, 9002, 'photos/2026/10/9002_11c940c304c0_l.jpg', 'photos/2026/10/9002_11c940c304c0_m.jpg', 'photos/2026/10/9002_11c940c304c0_t.jpg', 'photos/2026/10/9002_11c940c304c0_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6K+1GKz2+YeTT0mWVA69DXI6rqH22QFegq1Z6usUCo2ciuOVKdrpGqqwvuf/2Q==', 1080, 1350, 46320, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-08-07 04:48:22'),
(4, 9002, 'photos/2026/10/9002_343f358facdb_l.jpg', 'photos/2026/10/9002_343f358facdb_m.jpg', 'photos/2026/10/9002_343f358facdb_t.jpg', 'photos/2026/10/9002_343f358facdb_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A39Q1SKyZVfkmpEmWZA69DXLavdLezBk6CrVnqiwwKjDkVm6c+xurH//Z', 1080, 1350, 48323, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-08-07 04:48:22'),
(5, 9003, 'photos/2026/10/9003_c564939349c5_l.jpg', 'photos/2026/10/9003_c564939349c5_m.jpg', 'photos/2026/10/9003_c564939349c5_t.jpg', 'photos/2026/10/9003_c564939349c5_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6O/vRbAcZJoWXegb1Fczf+IEuCAqcA1LD4hj8sBlwRWetxNrlVtz/9k=', 1080, 1350, 49624, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-09-03 04:48:22'),
(6, 9003, 'photos/2026/10/9003_527b8cf33898_l.jpg', 'photos/2026/10/9003_527b8cf33898_m.jpg', 'photos/2026/10/9003_527b8cf33898_t.jpg', 'photos/2026/10/9003_527b8cf33898_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A39QvvsxUAZJqVJN6K3qKxr6/S4I2r0NSx6moQAjGK5OdXO9U3bY//9k=', 1080, 1350, 48087, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-09-03 04:48:22'),
(7, 9004, 'photos/2026/10/9004_01c1ba00c58a_l.jpg', 'photos/2026/10/9004_01c1ba00c58a_m.jpg', 'photos/2026/10/9004_01c1ba00c58a_t.jpg', 'photos/2026/10/9004_01c1ba00c58a_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A2tW1I2QTC5LGpI5hLGr+orC1DUku8ALwDUsWqqsYBGMVsou5Di7H/9k=', 1080, 1350, 34632, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-06-09 04:48:22'),
(8, 9004, 'photos/2026/10/9004_db06c2e1f212_l.jpg', 'photos/2026/10/9004_db06c2e1f212_m.jpg', 'photos/2026/10/9004_db06c2e1f212_t.jpg', 'photos/2026/10/9004_db06c2e1f212_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A2Na1V7J41jXO481binEsSv0yKwdTv4rsrtX7p61LFqirGF24xXBO7Wh0qx//2Q==', 1080, 1350, 34733, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-06-09 04:48:22'),
(9, 9005, 'photos/2026/10/9005_e98ad75f9252_l.jpg', 'photos/2026/10/9005_e98ad75f9252_m.jpg', 'photos/2026/10/9005_e98ad75f9252_t.jpg', 'photos/2026/10/9005_e98ad75f9252_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6O91CK1wH6mkWZZVDqeDXLatei+lBTIAqzaaksUCo2citlGVw54n/9k=', 1080, 1350, 38020, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-09-14 04:48:22'),
(10, 9005, 'photos/2026/10/9005_843cba7ce254_l.jpg', 'photos/2026/10/9005_843cba7ce254_m.jpg', 'photos/2026/10/9005_843cba7ce254_t.jpg', 'photos/2026/10/9005_843cba7ce254_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6C/1OO0ZVbkmnpOsqB16GuW1Sf7ZOHUkAVZtL/yYFjbJIrHmRq6E7XSP/9k=', 1080, 1350, 41165, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-09-14 04:48:22'),
(11, 9006, 'photos/2026/10/9006_bf83ca98e2f8_l.jpg', 'photos/2026/10/9006_bf83ca98e2f8_m.jpg', 'photos/2026/10/9006_bf83ca98e2f8_t.jpg', 'photos/2026/10/9006_bf83ca98e2f8_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6S8v4rbAc8mlWZZVDKeDXJ3+oLezbskAVPaausMIQ54rO+pv7KVtj//Z', 1080, 1350, 40115, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-05-10 04:48:22'),
(12, 9006, 'photos/2026/10/9006_76ed145a0c08_l.jpg', 'photos/2026/10/9006_76ed145a0c08_m.jpg', 'photos/2026/10/9006_76ed145a0c08_t.jpg', 'photos/2026/10/9006_76ed145a0c08_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6C91GO2YK3JNIsyyqHU8Guc1C5S8ud6sQAKltdRWCEIcnFaxUuZ32M5cvKrbn//Z', 1080, 1350, 44766, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-05-10 04:48:22'),
(13, 9007, 'photos/2026/10/9007_3c46ecff2974_l.jpg', 'photos/2026/10/9007_3c46ecff2974_m.jpg', 'photos/2026/10/9007_3c46ecff2974_t.jpg', 'photos/2026/10/9007_3c46ecff2974_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A3tRvxaKvGSTTkl8xFf1Fc/f6qt1gBOAami1hBGAVxilqaqtT7n//2Q==', 1080, 1350, 34886, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-08-20 04:48:22'),
(14, 9007, 'photos/2026/10/9007_29462a30ff5a_l.jpg', 'photos/2026/10/9007_29462a30ff5a_m.jpg', 'photos/2026/10/9007_29462a30ff5a_t.jpg', 'photos/2026/10/9007_29462a30ff5a_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A29U1L7IVULksanjl8yNX9RWBqWpR3JXaucHrUsesKqAFcYrVJ3NOh//Z', 1080, 1350, 37455, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-08-20 04:48:22'),
(15, 9008, 'photos/2026/10/9008_6bc0406f0d52_l.jpg', 'photos/2026/10/9008_6bc0406f0d52_m.jpg', 'photos/2026/10/9008_6bc0406f0d52_t.jpg', 'photos/2026/10/9008_6bc0406f0d52_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A0Nb1qWymWOKPce9WrG8N3bLKRgnqKx9QvIrtwQnI71Pb6hHFEEC4xUKauZu1j//Z', 1080, 1350, 39825, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-09-25 04:48:22'),
(16, 9008, 'photos/2026/10/9008_370aabad15f1_l.jpg', 'photos/2026/10/9008_370aabad15f1_m.jpg', 'photos/2026/10/9008_370aabad15f1_t.jpg', 'photos/2026/10/9008_370aabad15f1_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8Ata5rlzbXXlW6ZA6mtDTr03dqsrjDHqKydRnhupAyrz61LBfJFEEC4xWHtFc6PYTtsf/Z', 1080, 1350, 39909, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-09-25 04:48:22'),
(17, 9009, 'photos/2026/10/9009_5c222535f7de_l.jpg', 'photos/2026/10/9009_5c222535f7de_m.jpg', 'photos/2026/10/9009_5c222535f7de_t.jpg', 'photos/2026/10/9009_5c222535f7de_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6PUNRjswu8E5pFnWVA46Gudv9WivCAV4U9afFrMaoF24xWiTuQ2rH//Z', 1080, 1350, 34760, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-07-21 04:48:22'),
(18, 9009, 'photos/2026/10/9009_a339c584543b_l.jpg', 'photos/2026/10/9009_a339c584543b_m.jpg', 'photos/2026/10/9009_a339c584543b_t.jpg', 'photos/2026/10/9009_a339c584543b_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A3NQ1NLNlUqTuqRJllRXHcVhXt3HeEZH3TUiaiFUKBwKz51c6VY//2Q==', 1080, 1350, 37046, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-07-21 04:48:22'),
(19, 9010, 'photos/2026/10/9010_f2fbe840c297_l.jpg', 'photos/2026/10/9010_f2fbe840c297_m.jpg', 'photos/2026/10/9010_f2fbe840c297_t.jpg', 'photos/2026/10/9010_f2fbe840c297_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6HUNRjsgu8E7qckyyorjoRXN6hqYu8DbwDU0OrKsYUjGKwaZoqkO5//Z', 1080, 1350, 31726, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-08-14 04:48:22'),
(20, 9010, 'photos/2026/10/9010_b44fc5e58891_l.jpg', 'photos/2026/10/9010_b44fc5e58891_m.jpg', 'photos/2026/10/9010_b44fc5e58891_t.jpg', 'photos/2026/10/9010_b44fc5e58891_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A3dS1RLJlVlJ3VIkwlRXHQisHU76O7K7VztPWpotVVYwu3GBUc6uW8PO10j//2Q==', 1080, 1350, 33410, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-08-14 04:48:22'),
(21, 9011, 'photos/2026/10/9011_cca02153e826_l.jpg', 'photos/2026/10/9011_cca02153e826_m.jpg', 'photos/2026/10/9011_cca02153e826_t.jpg', 'photos/2026/10/9011_cca02153e826_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6K91CKzC+YetPSVZUDr0Nchqutx3xUBOFOc1atvEcSQqrLjHFYuLN1JXP//Z', 1080, 1350, 33260, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-05-30 04:48:22'),
(22, 9011, 'photos/2026/10/9011_879480fc9db7_l.jpg', 'photos/2026/10/9011_879480fc9db7_m.jpg', 'photos/2026/10/9011_879480fc9db7_t.jpg', 'photos/2026/10/9011_879480fc9db7_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6DUNUisiqydWp6TLKgdehrmNX1CG+Zdq/dPWrEGtxxxKm3GBimpK5boztsf/2Q==', 1080, 1350, 35674, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-05-30 04:48:22'),
(23, 9012, 'photos/2026/10/9012_d613799439e3_l.jpg', 'photos/2026/10/9012_d613799439e3_m.jpg', 'photos/2026/10/9012_d613799439e3_t.jpg', 'photos/2026/10/9012_d613799439e3_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A3dU1I2W0BNxapoZvOiWTGMisC/1VLogBOAanh1mNYwpXGK4ZRlbY6U0f/9k=', 1080, 1350, 40171, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-07-04 04:48:22'),
(24, 9012, 'photos/2026/10/9012_94b83bb3a557_l.jpg', 'photos/2026/10/9012_94b83bb3a557_m.jpg', 'photos/2026/10/9012_94b83bb3a557_t.jpg', 'photos/2026/10/9012_94b83bb3a557_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A2dX1R7ORERN2etTwziWJXPGRWHqGpRXTDavTvTo9VVUA24xWbpzfQ6FY/9k=', 1080, 1350, 40469, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-07-04 04:48:22'),
(25, 9013, 'photos/2026/10/9013_6c4014e6520a_l.jpg', 'photos/2026/10/9013_6c4014e6520a_m.jpg', 'photos/2026/10/9013_6c4014e6520a_t.jpg', 'photos/2026/10/9013_6c4014e6520a_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A3NX1Q2OwKm4tUkNwJolk6ZHSsPUNRS7wAvQ9aki1NFjC4xilyS7GTqQsf//Z', 1080, 1350, 32802, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-09-08 04:48:22'),
(26, 9013, 'photos/2026/10/9013_18fc97944c81_l.jpg', 'photos/2026/10/9013_18fc97944c81_m.jpg', 'photos/2026/10/9013_18fc97944c81_t.jpg', 'photos/2026/10/9013_18fc97944c81_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A1tX1aSykRI03Z61cgn86FZCMZFYWoXaXZUheh61PFqISMLjGK5XNHaqUj//Z', 1080, 1350, 36108, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-09-08 04:48:22'),
(27, 9014, 'photos/2026/10/9014_db36129ec517_l.jpg', 'photos/2026/10/9014_db36129ec517_m.jpg', 'photos/2026/10/9014_db36129ec517_t.jpg', 'photos/2026/10/9014_db36129ec517_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6C/1BLQLvBOaFmWRQw6GsC+1AXWBt6GpI9RAQAjGKaqRvuU8PU5b2P/Z', 1080, 1350, 34592, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-09-29 04:48:22'),
(28, 9014, 'photos/2026/10/9014_58342496f408_l.jpg', 'photos/2026/10/9014_58342496f408_m.jpg', 'photos/2026/10/9014_58342496f408_t.jpg', 'photos/2026/10/9014_58342496f408_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6DUdTSyZVZSd1SpKJUVx0IrA1LUI7ortXO09ami1ZVjClcYrgle2h0qx/9k=', 1080, 1350, 33212, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-09-29 04:48:22'),
(29, 9015, 'photos/2026/10/9015_0dcda49fcb0f_l.jpg', 'photos/2026/10/9015_0dcda49fcb0f_m.jpg', 'photos/2026/10/9015_0dcda49fcb0f_t.jpg', 'photos/2026/10/9015_0dcda49fcb0f_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6W7vI7YDf3pBIHAYdDXOX+rJdEAJwDU8WsoIwCuMVor3Kuj/2Q==', 1080, 1350, 30001, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-08-26 04:48:22'),
(30, 9015, 'photos/2026/10/9015_63b866ea3a33_l.jpg', 'photos/2026/10/9015_63b866ea3a33_m.jpg', 'photos/2026/10/9015_63b866ea3a33_t.jpg', 'photos/2026/10/9015_63b866ea3a33_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6O9v47UgP3pRKJFDDoawdR1CO6K7V+6etSR6sqoAVxisebU1dCdrpH//2Q==', 1080, 1350, 33641, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-08-26 04:48:22'),
(31, 9016, 'photos/2026/10/9016_3db25e23b840_l.jpg', 'photos/2026/10/9016_3db25e23b840_m.jpg', 'photos/2026/10/9016_3db25e23b840_t.jpg', 'photos/2026/10/9016_3db25e23b840_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A19a1g2DIqLuJqzbXIuYFl6ZFcrq18t9MGUcCrljq6QW4jYdKXJIfMj//2Q==', 1080, 1350, 44653, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-09-20 04:48:22'),
(32, 9016, 'photos/2026/10/9016_637191fd2eb2_l.jpg', 'photos/2026/10/9016_637191fd2eb2_m.jpg', 'photos/2026/10/9016_637191fd2eb2_t.jpg', 'photos/2026/10/9016_637191fd2eb2_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A0Nc1qSznWOJc+tWba6FxAsh4JFczq12l5cb07VPaaksEAQg8VUZa6jlRlyqyP//Z', 1080, 1350, 48906, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-09-20 04:48:22'),
(33, 9017, 'photos/2026/10/9017_7b4a4d817c35_l.jpg', 'photos/2026/10/9017_7b4a4d817c35_m.jpg', 'photos/2026/10/9017_7b4a4d817c35_t.jpg', 'photos/2026/10/9017_7b4a4d817c35_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6G9v4rXAfqaasyyKHU8GuZ1LUo71wQCMVLb6skcQQg8VOtzrUWf/2Q==', 1080, 1350, 37020, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-05-20 04:48:22'),
(34, 9017, 'photos/2026/10/9017_0d3851901bf0_l.jpg', 'photos/2026/10/9017_0d3851901bf0_m.jpg', 'photos/2026/10/9017_0d3851901bf0_t.jpg', 'photos/2026/10/9017_0d3851901bf0_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A377Uo7Rgrck1Ik6yoHXoa5jUbpLyUMCRip7fUlhiCYPFapSvqaW0P//Z', 1080, 1350, 38733, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-05-20 04:48:22'),
(35, 9017, 'photos/2026/10/9017_68898f84ae1c_l.jpg', 'photos/2026/10/9017_68898f84ae1c_m.jpg', 'photos/2026/10/9017_68898f84ae1c_t.jpg', 'photos/2026/10/9017_68898f84ae1c_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6W9vo7UAuetNSZZUDqeDXN6lfi9IypAFWbTU444AmOlJwl2IbVj/2Q==', 1080, 1350, 39497, 0, 0, 3, 'approved', NULL, NULL, NULL, '2026-05-20 04:48:22'),
(36, 9018, 'photos/2026/10/9018_10748be81b7c_l.jpg', 'photos/2026/10/9018_10748be81b7c_m.jpg', 'photos/2026/10/9018_10748be81b7c_t.jpg', 'photos/2026/10/9018_10748be81b7c_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A29V1P7DswhbcaminE0SvjGRWBeail3gFelSR6qqoFxjFK+pm7WP/2Q==', 1080, 1350, 38730, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-04-30 04:48:22'),
(37, 9018, 'photos/2026/10/9018_cd570ccbeb44_l.jpg', 'photos/2026/10/9018_cd570ccbeb44_m.jpg', 'photos/2026/10/9018_cd570ccbeb44_t.jpg', 'photos/2026/10/9018_cd570ccbeb44_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A2NY1Z7GRERN2etW4ZvOiWTGMisa/u4bwrgfdPWpI9TREChelYe0R0exnbY//2Q==', 1080, 1350, 40640, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-04-30 04:48:22'),
(38, 9018, 'photos/2026/10/9018_38e17c0980df_l.jpg', 'photos/2026/10/9018_38e17c0980df_m.jpg', 'photos/2026/10/9018_38e17c0980df_t.jpg', 'photos/2026/10/9018_38e17c0980df_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A3dU1EWUYO0tk0RXAniWTGMisq8vVugFZeAafFfKqBQMYojVje1ypYefLex//2Q==', 1080, 1350, 43023, 0, 0, 3, 'approved', NULL, NULL, NULL, '2026-04-30 04:48:22'),
(39, 9019, 'photos/2026/10/9019_daed101910be_l.jpg', 'photos/2026/10/9019_daed101910be_m.jpg', 'photos/2026/10/9019_daed101910be_t.jpg', 'photos/2026/10/9019_daed101910be_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6O/vRbAcZJoWXegb1Fc3e68lwQAnAqWLX49gDLjFaJO5m2rH/9k=', 1080, 1350, 37545, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-06-17 04:48:22');
INSERT INTO `user_photos` (`id`, `user_id`, `large_path`, `medium_path`, `thumb_path`, `blur_path`, `blur_data_uri`, `width`, `height`, `file_size`, `is_primary`, `is_vip_only`, `sort_order`, `moderation_status`, `rejection_reason`, `moderated_by`, `moderated_at`, `created_at`) VALUES
(40, 9019, 'photos/2026/10/9019_79f78792ae34_l.jpg', 'photos/2026/10/9019_79f78792ae34_m.jpg', 'photos/2026/10/9019_79f78792ae34_t.jpg', 'photos/2026/10/9019_79f78792ae34_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A3tQvvsxUAZzTlk3oG9RWNe6jHcEbV6HrT01RQoBXpWfOrnSrJ3uf/9k=', 1080, 1350, 39005, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-06-17 04:48:22'),
(41, 9019, 'photos/2026/10/9019_b02dc72af853_l.jpg', 'photos/2026/10/9019_b02dc72af853_m.jpg', 'photos/2026/10/9019_b02dc72af853_t.jpg', 'photos/2026/10/9019_b02dc72af853_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6S+vFtkBIzmmJMJEDetc7e655/y+XwKkg1qMxgEYxWqTuHNHuf/Z', 1080, 1350, 37825, 0, 0, 3, 'approved', NULL, NULL, NULL, '2026-06-17 04:48:22'),
(42, 9020, 'photos/2026/10/9020_02fd610b2f69_l.jpg', 'photos/2026/10/9020_02fd610b2f69_m.jpg', 'photos/2026/10/9020_02fd610b2f69_t.jpg', 'photos/2026/10/9020_02fd610b2f69_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6O9v4rTAfqaFlWVQ6ng1x2q6ut9MCmQBVqy1+OK3VHByKxcZGykj/9k=', 1080, 1350, 37186, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-06-01 04:48:22'),
(43, 9020, 'photos/2026/10/9020_e1dd64171b19_l.jpg', 'photos/2026/10/9020_e1dd64171b19_m.jpg', 'photos/2026/10/9020_e1dd64171b19_t.jpg', 'photos/2026/10/9020_e1dd64171b19_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6C/1KO0ZVbkmlWdZVDqeDXNarOL2cOhwBU9rfCGBUOTio51ct4efLdI//9k=', 1080, 1350, 38147, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-06-01 04:48:22'),
(44, 9020, 'photos/2026/10/9020_f380bcc60cac_l.jpg', 'photos/2026/10/9020_f380bcc60cac_m.jpg', 'photos/2026/10/9020_f380bcc60cac_t.jpg', 'photos/2026/10/9020_f380bcc60cac_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6W8vo7UAyHrSJMsqB16GuT1PUGvXHVQKtWWqrHbhGzkVnc39lKx//9k=', 1080, 1350, 36723, 0, 0, 3, 'approved', NULL, NULL, NULL, '2026-06-01 04:48:22'),
(45, 9021, 'photos/2026/10/9021_5b3bd6d7330b_l.jpg', 'photos/2026/10/9021_5b3bd6d7330b_m.jpg', 'photos/2026/10/9021_5b3bd6d7330b_t.jpg', 'photos/2026/10/9021_5b3bd6d7330b_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6K+1GKzx5nU0qTJKgdTwa5zVLgX0gK8AVZtL1YYFQ9q5XNHaqUj/2Q==', 1080, 1350, 47133, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-07-14 04:48:22'),
(46, 9021, 'photos/2026/10/9021_ccf90b7375cf_l.jpg', 'photos/2026/10/9021_ccf90b7375cf_m.jpg', 'photos/2026/10/9021_ccf90b7375cf_t.jpg', 'photos/2026/10/9021_ccf90b7375cf_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A39Q1SKzZVfkmpEnWZA69DXI6rfC8mDL0FWbTVxDAEYHimpalujK10j//2Q==', 1080, 1350, 47497, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-07-14 04:48:22'),
(47, 9021, 'photos/2026/10/9021_32b642c03d12_l.jpg', 'photos/2026/10/9021_32b642c03d12_m.jpg', 'photos/2026/10/9021_32b642c03d12_t.jpg', 'photos/2026/10/9021_32b642c03d12_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6K9vorRQZD1pqTLKgdTwa5vVLw3rDjAFWbPUFjgVD1FTc2jONz//2Q==', 1080, 1350, 46341, 0, 0, 3, 'approved', NULL, NULL, NULL, '2026-07-14 04:48:22'),
(48, 9022, 'photos/2026/10/9022_228c6b341ac4_l.jpg', 'photos/2026/10/9022_228c6b341ac4_m.jpg', 'photos/2026/10/9022_228c6b341ac4_t.jpg', 'photos/2026/10/9022_228c6b341ac4_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6O91GK0wJDyackyyoHXoa5PVbk30oZSQBVyz1EQ26xtnIrllRna6Roq1O+5//9k=', 1080, 1350, 38286, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-07-31 04:48:22'),
(49, 9022, 'photos/2026/10/9022_220249452a2f_l.jpg', 'photos/2026/10/9022_220249452a2f_m.jpg', 'photos/2026/10/9022_220249452a2f_t.jpg', 'photos/2026/10/9022_220249452a2f_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A37/VIrNlV+SakjnWZA6ng1ymr3AvbkPGSAKt2WprBbrGwJIrN059jdWP/9k=', 1080, 1350, 37907, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-07-31 04:48:22'),
(50, 9022, 'photos/2026/10/9022_dcddd984fc3e_l.jpg', 'photos/2026/10/9022_dcddd984fc3e_m.jpg', 'photos/2026/10/9022_dcddd984fc3e_t.jpg', 'photos/2026/10/9022_dcddd984fc3e_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6O9v4rQDzD1pqTLMgdTwa5vVrhr51IyoWrdjerDbqh6ioU1clw0uf//Z', 1080, 1350, 38419, 0, 0, 3, 'approved', NULL, NULL, NULL, '2026-07-31 04:48:22'),
(51, 9023, 'photos/2026/10/9023_7ad12d06032a_l.jpg', 'photos/2026/10/9023_7ad12d06032a_m.jpg', 'photos/2026/10/9023_7ad12d06032a_t.jpg', 'photos/2026/10/9023_7ad12d06032a_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6LUNRjswu8E5pVmEqBx0Irm9R1eO8IAThTU0OtxrGFK4xS5ZENqx/9k=', 1080, 1350, 33276, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-06-29 04:48:22'),
(52, 9023, 'photos/2026/10/9023_8a7ccf45a590_l.jpg', 'photos/2026/10/9023_8a7ccf45a590_m.jpg', 'photos/2026/10/9023_8a7ccf45a590_t.jpg', 'photos/2026/10/9023_8a7ccf45a590_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A3tQ1NLJlVlJ3VMkolRXHQisLUruO7K7V+6etTxamqRhduMCuVzR2qlI//9k=', 1080, 1350, 35975, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-06-29 04:48:22'),
(53, 9023, 'photos/2026/10/9023_edb999a49216_l.jpg', 'photos/2026/10/9023_edb999a49216_m.jpg', 'photos/2026/10/9023_edb999a49216_t.jpg', 'photos/2026/10/9023_edb999a49216_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6S/v47RAX701J1lQOvQ1zuo6qLsBSmADUtvqsYiCkYxVq9zNyjY//9k=', 1080, 1350, 33645, 0, 0, 3, 'approved', NULL, NULL, NULL, '2026-06-29 04:48:22'),
(54, 9024, 'photos/2026/10/9024_7b150a45fcca_l.jpg', 'photos/2026/10/9024_7b150a45fcca_m.jpg', 'photos/2026/10/9024_7b150a45fcca_t.jpg', 'photos/2026/10/9024_7b150a45fcca_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6O7vY7YDeetR+aHAYdDWDqN+l5tAXhTU0WpKsYXGMU1Ujfchx0P/2Q==', 1080, 1350, 32440, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-07-19 04:48:22'),
(55, 9024, 'photos/2026/10/9024_40bccf96fa86_l.jpg', 'photos/2026/10/9024_40bccf96fa86_m.jpg', 'photos/2026/10/9024_40bccf96fa86_t.jpg', 'photos/2026/10/9024_40bccf96fa86_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6S91CK0Kh+ppVlWRQwPBrndVvIr0rtB+U9amh1NUjC7egrz5Xa0OlWP/2Q==', 1080, 1350, 31011, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-07-19 04:48:22'),
(56, 9024, 'photos/2026/10/9024_89fcdd69ef83_l.jpg', 'photos/2026/10/9024_89fcdd69ef83_m.jpg', 'photos/2026/10/9024_89fcdd69ef83_t.jpg', 'photos/2026/10/9024_89fcdd69ef83_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6a7u47ZQZD1pqzLIoZehrm9Q1E3YClcAGp7fU1WIL0xxWbpztexurH//2Q==', 1080, 1350, 32775, 0, 0, 3, 'approved', NULL, NULL, NULL, '2026-07-19 04:48:22'),
(58, 9026, 'photos/2026/10/9026_306d3b4d4b1a2ea153fc_l.jpg', 'photos/2026/10/9026_306d3b4d4b1a2ea153fc_m.jpg', 'photos/2026/10/9026_306d3b4d4b1a2ea153fc_t.jpg', 'photos/2026/10/9026_306d3b4d4b1a2ea153fc_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAMAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6XU2ZJowp71fjBMYJ64rP1VGeSMr2q9AxMKk+lIfQ//Z', 1195, 1600, 181280, 0, 0, 0, 'approved', NULL, NULL, NULL, '2026-10-16 12:55:34'),
(59, 9026, 'photos/2026/10/9026_fd8c8802bc76b7c5705a_l.jpg', 'photos/2026/10/9026_fd8c8802bc76b7c5705a_m.jpg', 'photos/2026/10/9026_fd8c8802bc76b7c5705a_t.jpg', 'photos/2026/10/9026_fd8c8802bc76b7c5705a_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAMAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AXWA0uojLYA6VvWgZbdAWzxWBqhMl+MdjW9AcQoPanFasJP3Uf//Z', 1071, 1469, 133380, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-10-16 12:55:34'),
(60, 9026, 'photos/2026/10/9026_f1486f8e1e565f67468a_l.jpg', 'photos/2026/10/9026_f1486f8e1e565f67468a_m.jpg', 'photos/2026/10/9026_f1486f8e1e565f67468a_t.jpg', 'photos/2026/10/9026_f1486f8e1e565f67468a_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAMAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A6W5Us64OADVpQCBWfdTKSu1qsxXCCMZNNXuJ7H//2Q==', 864, 1184, 60466, 1, 0, 2, 'approved', NULL, NULL, NULL, '2026-10-16 12:55:35'),
(61, 9026, 'photos/2026/10/9026_57c220d2a5a3a047c8c9_l.jpg', 'photos/2026/10/9026_57c220d2a5a3a047c8c9_m.jpg', 'photos/2026/10/9026_57c220d2a5a3a047c8c9_t.jpg', 'photos/2026/10/9026_57c220d2a5a3a047c8c9_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A2teYrZcHvS6IXNkN/wCFS6rA01rtAzTNKDpbeWwxg0Af/9k=', 1254, 1254, 116266, 0, 0, 3, 'approved', NULL, NULL, NULL, '2026-10-16 12:55:35'),
(62, 2, 'photos/2026/10/2_4af9219275ed36619884_l.jpg', 'photos/2026/10/2_4af9219275ed36619884_m.jpg', 'photos/2026/10/2_4af9219275ed36619884_t.jpg', 'photos/2026/10/2_4af9219275ed36619884_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AzFRn0ne55zwKy3ZmPzNtxxWzDLC2h7SfmHSuZbfI7EMeDQB//9k=', 735, 893, 59804, 1, 0, 0, 'approved', NULL, NULL, NULL, '2026-10-16 14:17:27'),
(63, 2, 'photos/2026/10/2_b2ae7566e98618e0105c_l.jpg', 'photos/2026/10/2_b2ae7566e98618e0105c_m.jpg', 'photos/2026/10/2_b2ae7566e98618e0105c_t.jpg', 'photos/2026/10/2_b2ae7566e98618e0105c_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAMAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AzLi0vQxKfKlWYLOV4gWJzVwQXrwMSRjFVI7qaNdpPIrNtmiR/9k=', 736, 981, 105071, 0, 0, 1, 'approved', NULL, NULL, NULL, '2026-10-16 14:17:40'),
(64, 2, 'photos/2026/10/2_e4b2f5106be45b58cce7_l.jpg', 'photos/2026/10/2_e4b2f5106be45b58cce7_m.jpg', 'photos/2026/10/2_e4b2f5106be45b58cce7_t.jpg', 'photos/2026/10/2_e4b2f5106be45b58cce7_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAMAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8ArQi3MQMv3hTW1AKdsY+UdKzriTB2qelVg7CsG5WsWrtaH//Z', 736, 981, 141200, 0, 0, 2, 'approved', NULL, NULL, NULL, '2026-10-16 14:17:49'),
(65, 9001, 'photos/2026/10/9001_3db7eacb6eab97b7d67d_l.jpg', 'photos/2026/10/9001_3db7eacb6eab97b7d67d_m.jpg', 'photos/2026/10/9001_3db7eacb6eab97b7d67d_t.jpg', 'photos/2026/10/9001_3db7eacb6eab97b7d67d_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AbdXjXEghZCg75qv9pja0eEHIQ1X1PUDFd5Zcg8VVsQtzM/lghTzWa3v0NWklbqf/2Q==', 1254, 1254, 173359, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:28:51', '2026-10-16 18:28:51'),
(66, 9024, 'photos/2026/10/9024_9c570f97f28b59dbaeb5_l.jpg', 'photos/2026/10/9024_9c570f97f28b59dbaeb5_m.jpg', 'photos/2026/10/9024_9c570f97f28b59dbaeb5_t.jpg', 'photos/2026/10/9024_9c570f97f28b59dbaeb5_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AoDa92xV8Y71vadfQKgSSUFq4a5d7W4ZA5NT6W5a4BY5HvUJdS3tbqf/Z', 1254, 1254, 131122, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:30:38', '2026-10-16 18:30:38'),
(67, 9023, 'photos/2026/10/9023_2b4bfbb9ddbb94127380_l.jpg', 'photos/2026/10/9023_2b4bfbb9ddbb94127380_m.jpg', 'photos/2026/10/9023_2b4bfbb9ddbb94127380_t.jpg', 'photos/2026/10/9023_2b4bfbb9ddbb94127380_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADwAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8ANSuHFsrT/e9KztNNtNOfMH4VLqtvcXDBdw4qPS9IcTbnYfgamO2u43sf/9k=', 1312, 1199, 183828, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:34:07', '2026-10-16 18:34:07'),
(68, 9022, 'photos/2026/10/9022_d00db44b097f6a8f4f1b_l.jpg', 'photos/2026/10/9022_d00db44b097f6a8f4f1b_m.jpg', 'photos/2026/10/9022_d00db44b097f6a8f4f1b_t.jpg', 'photos/2026/10/9022_d00db44b097f6a8f4f1b_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8Ama483hOQByKzruJrbEhGQ1aNhCyMx3Aik1L9+ojAAxWUbJGltLH/2Q==', 1363, 1154, 205367, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:36:54', '2026-10-16 18:36:54'),
(69, 9021, 'photos/2026/10/9021_002034b9cc7747d37c15_l.jpg', 'photos/2026/10/9021_002034b9cc7747d37c15_m.jpg', 'photos/2026/10/9021_002034b9cc7747d37c15_t.jpg', 'photos/2026/10/9021_002034b9cc7747d37c15_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8Aqaheo1zHjgd6sWt8ZGJjGAOKx7OEXd1tkPAroVtIrBFKDIPas1G2hWrR/9k=', 1363, 1154, 235471, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:39:32', '2026-10-16 18:39:32'),
(70, 9020, 'photos/2026/10/9020_e71e23f21118ecd2ae54_l.jpg', 'photos/2026/10/9020_e71e23f21118ecd2ae54_m.jpg', 'photos/2026/10/9020_e71e23f21118ecd2ae54_t.jpg', 'photos/2026/10/9020_e71e23f21118ecd2ae54_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AhldpLhCp4HWtWzkMMu4n74rEkhIu9obg1qvFugVFbBA4NZJt9TRxsj//2Q==', 1363, 1154, 189583, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:42:43', '2026-10-16 18:42:43'),
(71, 9019, 'photos/2026/10/9019_de8b948f1503404a5e39_l.jpg', 'photos/2026/10/9019_de8b948f1503404a5e39_m.jpg', 'photos/2026/10/9019_de8b948f1503404a5e39_t.jpg', 'photos/2026/10/9019_de8b948f1503404a5e39_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A51CUYBhtUHJrTtZRO4dPurVW6tNz4LcVBCTE2xCQKjRlNW0P/9k=', 1363, 1154, 216504, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:45:05', '2026-10-16 18:45:05'),
(72, 9018, 'photos/2026/10/9018_3186d460f2fdbc4a4723_l.jpg', 'photos/2026/10/9018_3186d460f2fdbc4a4723_m.jpg', 'photos/2026/10/9018_3186d460f2fdbc4a4723_t.jpg', 'photos/2026/10/9018_3186d460f2fdbc4a4723_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8Az5JgkKQnjnmq/wBq8jd5Y3A1Jb2P26ESySEE1Yjs47Y7B83uaiS5dWWve0R//9k=', 1363, 1154, 202330, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:46:56', '2026-10-16 18:46:56'),
(73, 9017, 'photos/2026/10/9017_3d56696614c911ba6cb7_l.jpg', 'photos/2026/10/9017_3d56696614c911ba6cb7_m.jpg', 'photos/2026/10/9017_3d56696614c911ba6cb7_t.jpg', 'photos/2026/10/9017_3d56696614c911ba6cb7_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AoW96yxqucDvWta3TNGMLlR3rIFtHK0jDjHQVoWHEYReBSlTe7K5layP/2Q==', 1363, 1154, 219144, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:49:54', '2026-10-16 18:49:54'),
(74, 9002, 'photos/2026/10/9002_19be99a11878ed6a397b_l.jpg', 'photos/2026/10/9002_19be99a11878ed6a397b_m.jpg', 'photos/2026/10/9002_19be99a11878ed6a397b_t.jpg', 'photos/2026/10/9002_19be99a11878ed6a397b_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8Aia9BZXfqOlZ15LvnZh3rXs4YW3RuoLCsu7tZPtDbRxnis4Kxb2P/2Q==', 1128, 1394, 241053, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:51:43', '2026-10-16 18:51:43'),
(75, 9003, 'photos/2026/10/9003_cd758bdcfb37519c728e_l.jpg', 'photos/2026/10/9003_cd758bdcfb37519c728e_m.jpg', 'photos/2026/10/9003_cd758bdcfb37519c728e_t.jpg', 'photos/2026/10/9003_cd758bdcfb37519c728e_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AyB5k82wk4B61ordP5EkEMv3R1rNjufKlJYZBFJawytK7RfdJ6Vmk2zaSSR//2Q==', 1254, 1254, 225974, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:52:38', '2026-10-16 18:52:38'),
(76, 9004, 'photos/2026/10/9004_49c6b29f30748161f994_l.jpg', 'photos/2026/10/9004_49c6b29f30748161f994_m.jpg', 'photos/2026/10/9004_49c6b29f30748161f994_t.jpg', 'photos/2026/10/9004_49c6b29f30748161f994_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8Ar3KLpd4m1hmtS21OS6iyr1h+JCTdDJ7VJooKwFs1L0V0aRipOzP/2Q==', 1363, 1154, 206627, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:53:22', '2026-10-16 18:53:22'),
(77, 9005, 'photos/2026/10/9005_50f69b4401268dcf2f43_l.jpg', 'photos/2026/10/9005_50f69b4401268dcf2f43_m.jpg', 'photos/2026/10/9005_50f69b4401268dcf2f43_t.jpg', 'photos/2026/10/9005_50f69b4401268dcf2f43_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AoTXSDbg9TzWnYSfa48R/w8Vl2+mRyWwLklj3rY0q2FrCVU5pewstQ51sf//Z', 1363, 1154, 246904, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:56:02', '2026-10-16 18:56:02'),
(78, 9006, 'photos/2026/10/9006_023cd74688a4783e93e2_l.jpg', 'photos/2026/10/9006_023cd74688a4783e93e2_m.jpg', 'photos/2026/10/9006_023cd74688a4783e93e2_t.jpg', 'photos/2026/10/9006_023cd74688a4783e93e2_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AybQFJyo5ArpLWWDyR8mD3rM0rS3fdIxwcdK0Yo1VSpHQ1mo3epT2R//Z', 1128, 1394, 228853, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:57:08', '2026-10-16 18:57:08');
INSERT INTO `user_photos` (`id`, `user_id`, `large_path`, `medium_path`, `thumb_path`, `blur_path`, `blur_data_uri`, `width`, `height`, `file_size`, `is_primary`, `is_vip_only`, `sort_order`, `moderation_status`, `rejection_reason`, `moderated_by`, `moderated_at`, `created_at`) VALUES
(79, 9007, 'photos/2026/10/9007_c0aae72ad0ad96c0e53e_l.jpg', 'photos/2026/10/9007_c0aae72ad0ad96c0e53e_m.jpg', 'photos/2026/10/9007_c0aae72ad0ad96c0e53e_t.jpg', 'photos/2026/10/9007_c0aae72ad0ad96c0e53e_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AZ/ZksSiTsDWzDNIkKgLkYp6x7ozGTwajQGBdmc1MW4lSSkf/2Q==', 1363, 1154, 239272, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:57:50', '2026-10-16 18:57:50'),
(80, 9008, 'photos/2026/10/9008_2c16a208606aea205836_l.jpg', 'photos/2026/10/9008_2c16a208606aea205836_m.jpg', 'photos/2026/10/9008_2c16a208606aea205836_t.jpg', 'photos/2026/10/9008_2c16a208606aea205836_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AoW0ht5w44IrZi1eVxlRVbT7OK53FxTJI1s7konINZ87itC3FNH//2Q==', 1363, 1154, 251536, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:58:27', '2026-10-16 18:58:27'),
(81, 9009, 'photos/2026/10/9009_562232942a4e84aca46b_l.jpg', 'photos/2026/10/9009_562232942a4e84aca46b_m.jpg', 'photos/2026/10/9009_562232942a4e84aca46b_t.jpg', 'photos/2026/10/9009_562232942a4e84aca46b_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AqJfxuYyrYKCtux1iMxfvD0rgndopSp4q1Y3DLJy2FPY1LXVF3VrM/9k=', 1254, 1254, 169599, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 18:59:30', '2026-10-16 18:59:30'),
(82, 9010, 'photos/2026/10/9010_3563c0d8a5db4ff88c77_l.jpg', 'photos/2026/10/9010_3563c0d8a5db4ff88c77_m.jpg', 'photos/2026/10/9010_3563c0d8a5db4ff88c77_t.jpg', 'photos/2026/10/9010_3563c0d8a5db4ff88c77_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AvQRt5JjR/lqU36xLtxkrWXc6gdPkwqbg1JE/nx+ZjG6pvK2g2kf/2Q==', 1363, 1154, 232779, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 19:00:28', '2026-10-16 19:00:28'),
(83, 9011, 'photos/2026/10/9011_0907d56880fd2908fbb7_l.jpg', 'photos/2026/10/9011_0907d56880fd2908fbb7_m.jpg', 'photos/2026/10/9011_0907d56880fd2908fbb7_t.jpg', 'photos/2026/10/9011_0907d56880fd2908fbb7_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8Aybi8ChCRkCoP7Qy27y84qGziN1G6k4I7mtC109Vj2Mc+9TZFH//Z', 1363, 1154, 204201, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 19:01:02', '2026-10-16 19:01:02'),
(84, 9012, 'photos/2026/10/9012_c317ccec5204e26ae177_l.jpg', 'photos/2026/10/9012_c317ccec5204e26ae177_m.jpg', 'photos/2026/10/9012_c317ccec5204e26ae177_t.jpg', 'photos/2026/10/9012_c317ccec5204e26ae177_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AZbW4vYcE7cVJMIrS28pWDEVn3s8llDiJsH1psQc6cZnbcWNCiuVdxan/2Q==', 1363, 1154, 233417, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 19:01:44', '2026-10-16 19:01:44'),
(85, 9013, 'photos/2026/10/9013_e0706109bef80889ad1d_l.jpg', 'photos/2026/10/9013_e0706109bef80889ad1d_m.jpg', 'photos/2026/10/9013_e0706109bef80889ad1d_t.jpg', 'photos/2026/10/9013_e0706109bef80889ad1d_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8Ag02yhu8GEYI61qr5djcbpDxis7QD5MzKORVrU4/Oyc4qE7altXVj/9k=', 1363, 1154, 228249, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 19:03:13', '2026-10-16 19:03:13'),
(86, 9014, 'photos/2026/10/9014_ec679b688976968e188f_l.jpg', 'photos/2026/10/9014_ec679b688976968e188f_m.jpg', 'photos/2026/10/9014_ec679b688976968e188f_t.jpg', 'photos/2026/10/9014_ec679b688976968e188f_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8ANR1V4YiGOTVa11FbuM7yFIq1rNnHJDkDBrAjs9s3lByM96IptXY3pof/2Q==', 1363, 1154, 221843, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 19:03:54', '2026-10-16 19:03:54'),
(87, 9015, 'photos/2026/10/9015_569ab59d29cb25f387d7_l.jpg', 'photos/2026/10/9015_569ab59d29cb25f387d7_m.jpg', 'photos/2026/10/9015_569ab59d29cb25f387d7_t.jpg', 'photos/2026/10/9015_569ab59d29cb25f387d7_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8Ap3KGyZQw+lalrrreQoHaoL23F1ECTgioLGARFwcNWa2K1P/Z', 1363, 1154, 226241, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 19:04:49', '2026-10-16 19:04:49'),
(88, 9016, 'photos/2026/10/9016_5490e3117290be0a5f4b_l.jpg', 'photos/2026/10/9016_5490e3117290be0a5f4b_m.jpg', 'photos/2026/10/9016_5490e3117290be0a5f4b_t.jpg', 'photos/2026/10/9016_5490e3117290be0a5f4b_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AyprpolCmtTSr+SYFQORWZBCt5Koet2wtI7UfJSUU46juz//Z', 1363, 1154, 228611, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 19:05:50', '2026-10-16 19:05:50'),
(89, 9025, 'photos/2026/10/9025_aa68b0b1b70e8b2e8238_l.jpg', 'photos/2026/10/9025_aa68b0b1b70e8b2e8238_m.jpg', 'photos/2026/10/9025_aa68b0b1b70e8b2e8238_t.jpg', 'photos/2026/10/9025_aa68b0b1b70e8b2e8238_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AzZrhbYL5mQc1oyalBfaeF7j1qjqEaSABxmmS2ywWylOKlQ5dEO+h/9k=', 1363, 1154, 213576, 0, 0, 2, 'approved', NULL, 1, '2026-10-16 19:07:30', '2026-10-16 19:07:30'),
(90, 9025, 'photos/2026/10/9025_79d46a86017398797911_l.jpg', 'photos/2026/10/9025_79d46a86017398797911_m.jpg', 'photos/2026/10/9025_79d46a86017398797911_t.jpg', 'photos/2026/10/9025_79d46a86017398797911_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgADgAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8Ax2vjbIVK53VY0ZYZZssuCaisLVZ4CZDkitSygRDuA5FL2elgvZH/2Q==', 1363, 1154, 210046, 1, 0, 0, 'approved', NULL, 1, '2026-10-16 19:08:07', '2026-10-16 19:08:07'),
(91, 9030, 'photos/demo/amina_l.jpg', 'photos/demo/amina_m.jpg', 'photos/demo/amina_t.jpg', 'photos/demo/amina_b.jpg', NULL, 1000, 1000, 60000, 0, 0, 1, 'approved', NULL, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(92, 9031, 'photos/demo/brian_l.jpg', 'photos/demo/brian_m.jpg', 'photos/demo/brian_t.jpg', 'photos/demo/brian_b.jpg', NULL, 1000, 1000, 60000, 0, 0, 1, 'approved', NULL, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(93, 9032, 'photos/demo/cynthia_l.jpg', 'photos/demo/cynthia_m.jpg', 'photos/demo/cynthia_t.jpg', 'photos/demo/cynthia_b.jpg', NULL, 1000, 1000, 60000, 0, 0, 1, 'approved', NULL, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(94, 9033, 'photos/demo/daniel_l.jpg', 'photos/demo/daniel_m.jpg', 'photos/demo/daniel_t.jpg', 'photos/demo/daniel_b.jpg', NULL, 1000, 1000, 60000, 0, 0, 1, 'approved', NULL, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(95, 9034, 'photos/demo/esther_l.jpg', 'photos/demo/esther_m.jpg', 'photos/demo/esther_t.jpg', 'photos/demo/esther_b.jpg', NULL, 1000, 1000, 60000, 0, 0, 1, 'approved', NULL, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(96, 9035, 'photos/demo/felix_l.jpg', 'photos/demo/felix_m.jpg', 'photos/demo/felix_t.jpg', 'photos/demo/felix_b.jpg', NULL, 1000, 1000, 60000, 0, 0, 1, 'approved', NULL, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(97, 9036, 'photos/demo/grace_l.jpg', 'photos/demo/grace_m.jpg', 'photos/demo/grace_t.jpg', 'photos/demo/grace_b.jpg', NULL, 1000, 1000, 60000, 0, 0, 1, 'approved', NULL, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(98, 9037, 'photos/demo/hassan_l.jpg', 'photos/demo/hassan_m.jpg', 'photos/demo/hassan_t.jpg', 'photos/demo/hassan_b.jpg', NULL, 1000, 1000, 60000, 0, 0, 1, 'approved', NULL, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(99, 9038, 'photos/demo/irene_l.jpg', 'photos/demo/irene_m.jpg', 'photos/demo/irene_t.jpg', 'photos/demo/irene_b.jpg', NULL, 1000, 1000, 60000, 0, 0, 1, 'approved', NULL, NULL, '2026-11-09 03:49:59', '2026-11-09 03:49:59'),
(100, 9038, 'photos/2026/11/9038_d7408d6c4557c7a1b2d0_l.jpg', 'photos/2026/11/9038_d7408d6c4557c7a1b2d0_m.jpg', 'photos/2026/11/9038_d7408d6c4557c7a1b2d0_t.jpg', 'photos/2026/11/9038_d7408d6c4557c7a1b2d0_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A3r+RTbuFYZIrA0u2ENyTnO45qG7uDFdfPISrCn6Xd7rorjKjoax5tb9Dfl0t1P/Z', 1254, 1254, 184159, 1, 0, 0, 'approved', NULL, 1, '2026-11-09 12:12:30', '2026-11-09 12:12:30'),
(101, 9037, 'photos/2026/11/9037_5c11d8b0523c319ab561_l.jpg', 'photos/2026/11/9037_5c11d8b0523c319ab561_m.jpg', 'photos/2026/11/9037_5c11d8b0523c319ab561_t.jpg', 'photos/2026/11/9037_5c11d8b0523c319ab561_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A2Gcw2jfMAaxrid3tXBbNY13qF15pSRiPai0vJnYRlhtrNprU1TWx/9k=', 1254, 1254, 217609, 1, 0, 0, 'approved', NULL, 1, '2026-11-09 12:13:13', '2026-11-09 12:13:13'),
(102, 9036, 'photos/2026/11/9036_11e7e3a6507bec1a96d4_l.jpg', 'photos/2026/11/9036_11e7e3a6507bec1a96d4_m.jpg', 'photos/2026/11/9036_11e7e3a6507bec1a96d4_t.jpg', 'photos/2026/11/9036_11e7e3a6507bec1a96d4_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AktnlWxPknDZ60lxf3EOn7gdz5qDUblbSPZFwDVXSJxczGOQ8dQKz1b0NGrLzP//Z', 1254, 1254, 186868, 1, 0, 0, 'approved', NULL, 1, '2026-11-09 12:14:14', '2026-11-09 12:14:14'),
(103, 9034, 'photos/2026/11/9034_1d756c6199887b4b1655_l.jpg', 'photos/2026/11/9034_1d756c6199887b4b1655_m.jpg', 'photos/2026/11/9034_1d756c6199887b4b1655_t.jpg', 'photos/2026/11/9034_1d756c6199887b4b1655_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A03IS0ODziq2iSGGZjI+RnvXL2urytIwmkJU1ZgN5ebhan5fWs7O5rdcp/9k=', 1254, 1254, 176383, 1, 0, 0, 'approved', NULL, 1, '2026-11-09 12:14:54', '2026-11-09 12:14:54'),
(104, 9035, 'photos/2026/11/9035_a998f0e9b77a9e420ce5_l.jpg', 'photos/2026/11/9035_a998f0e9b77a9e420ce5_m.jpg', 'photos/2026/11/9035_a998f0e9b77a9e420ce5_t.jpg', 'photos/2026/11/9035_a998f0e9b77a9e420ce5_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AlkvFtyU/iFWrW785AMZY1LPbW16mQoB9RVG5jS1iDxtjbWbvuaq2x//Z', 1254, 1254, 194584, 1, 0, 0, 'approved', NULL, 1, '2026-11-09 12:16:26', '2026-11-09 12:16:26'),
(105, 9032, 'photos/2026/11/9032_9cf57eaa0193e9643a16_l.jpg', 'photos/2026/11/9032_9cf57eaa0193e9643a16_m.jpg', 'photos/2026/11/9032_9cf57eaa0193e9643a16_t.jpg', 'photos/2026/11/9032_9cf57eaa0193e9643a16_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A2dRm8u2xG4zVayuWe3cO2StZb7uEdyakicW6vjo1ZXuzTZWe5//Z', 1254, 1254, 209845, 1, 0, 0, 'approved', NULL, 1, '2026-11-09 12:17:12', '2026-11-09 12:17:12'),
(106, 9033, 'photos/2026/11/9033_a542907b71e05f21deb8_l.jpg', 'photos/2026/11/9033_a542907b71e05f21deb8_m.jpg', 'photos/2026/11/9033_a542907b71e05f21deb8_t.jpg', 'photos/2026/11/9033_a542907b71e05f21deb8_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAANAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8Ak1G4uzcJOp4B5ArQt9aSWPLMARxWU0M+DsYkHtVf7IYeH6nmpcmUoqx//9k=', 735, 906, 61770, 1, 0, 0, 'approved', NULL, 1, '2026-11-09 12:20:49', '2026-11-09 12:20:49'),
(107, 9031, 'photos/2026/11/9031_8ded531f152e4370ddb3_l.jpg', 'photos/2026/11/9031_8ded531f152e4370ddb3_m.jpg', 'photos/2026/11/9031_8ded531f152e4370ddb3_t.jpg', 'photos/2026/11/9031_8ded531f152e4370ddb3_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAMAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8ATWkdlREPBPardpbMluqlqZoz+bHif5ifWkudSitp2i5+WpGz/9k=', 736, 964, 75339, 1, 0, 0, 'approved', NULL, 1, '2026-11-09 12:21:08', '2026-11-09 12:21:08'),
(108, 9030, 'photos/2026/11/9030_7a1301c3502afef5a185_l.jpg', 'photos/2026/11/9030_7a1301c3502afef5a185_m.jpg', 'photos/2026/11/9030_7a1301c3502afef5a185_t.jpg', 'photos/2026/11/9030_7a1301c3502afef5a185_b.jpg', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AvamIxAPLI/CrGmTBrYIWGRUWnpb3VsIiDn1NSTWcdntZCc5qEjRvSz3P/9k=', 1254, 1254, 216848, 1, 0, 0, 'approved', NULL, 1, '2026-11-09 12:21:39', '2026-11-09 12:21:39');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `display_name` varchar(100) NOT NULL DEFAULT '',
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','non-binary','other') DEFAULT NULL,
  `interested_in` enum('male','female','everyone') NOT NULL DEFAULT 'everyone',
  `bio` text DEFAULT NULL,
  `occupation` varchar(150) DEFAULT NULL,
  `education` varchar(150) DEFAULT NULL,
  `height_cm` smallint(5) UNSIGNED DEFAULT NULL,
  `body_type` enum('slim','athletic','average','curvy','plus-size') DEFAULT NULL,
  `smoking` enum('never','occasionally','regularly') DEFAULT NULL,
  `drinking` enum('never','socially','regularly') DEFAULT NULL,
  `relationship_status` enum('single','divorced','widowed','separated','complicated') DEFAULT NULL,
  `children` enum('none','have_at_home','have_away') DEFAULT NULL,
  `want_children` enum('yes','no','maybe') DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `languages` varchar(255) DEFAULT NULL,
  `looking_for` varchar(255) DEFAULT NULL,
  `intent` varchar(30) DEFAULT NULL,
  `contact_phone` varchar(25) DEFAULT NULL,
  `contact_whatsapp` varchar(25) DEFAULT NULL,
  `contact_email` varchar(190) DEFAULT NULL,
  `contact_instagram` varchar(100) DEFAULT NULL,
  `profile_completion` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `onboarding_completed` tinyint(1) NOT NULL DEFAULT 0,
  `onboarding_step` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `show_online` tinyint(1) NOT NULL DEFAULT 1,
  `show_last_active` tinyint(1) NOT NULL DEFAULT 1,
  `show_distance` tinyint(1) NOT NULL DEFAULT 1,
  `show_age` tinyint(1) NOT NULL DEFAULT 1,
  `show_contact` tinyint(1) NOT NULL DEFAULT 0,
  `allow_visits_log` tinyint(1) NOT NULL DEFAULT 1,
  `is_paused` tinyint(1) NOT NULL DEFAULT 0,
  `is_incognito` tinyint(1) NOT NULL DEFAULT 0,
  `read_receipts` tinyint(1) NOT NULL DEFAULT 1,
  `theme_pref` varchar(10) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `photo_limit_override` tinyint(3) UNSIGNED DEFAULT NULL,
  `allow_paid_dm` tinyint(1) NOT NULL DEFAULT 1,
  `explore_mode` varchar(12) DEFAULT NULL,
  `elite_only` tinyint(1) NOT NULL DEFAULT 0,
  `passcode_hash` varchar(255) DEFAULT NULL,
  `passcode_set_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`user_id`, `display_name`, `date_of_birth`, `gender`, `interested_in`, `bio`, `occupation`, `education`, `height_cm`, `body_type`, `smoking`, `drinking`, `relationship_status`, `children`, `want_children`, `religion`, `languages`, `looking_for`, `intent`, `contact_phone`, `contact_whatsapp`, `contact_email`, `contact_instagram`, `profile_completion`, `is_verified`, `verified_at`, `onboarding_completed`, `onboarding_step`, `show_online`, `show_last_active`, `show_distance`, `show_age`, `show_contact`, `allow_visits_log`, `is_paused`, `is_incognito`, `read_receipts`, `theme_pref`, `created_at`, `updated_at`, `photo_limit_override`, `allow_paid_dm`, `explore_mode`, `elite_only`, `passcode_hash`, `passcode_set_at`) VALUES
(2, 'Juice', '2001-01-01', NULL, 'everyone', 'Easygoing, present, and always down for a proper night out or a relaxed evening. I appreciate people who value discretion and simplicity as much as I do. No games, no awkward small talk. Just pure comfort, attentive company, and an effortless date from start to finish.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'English, Kikuyu', NULL, NULL, NULL, NULL, NULL, NULL, 57, 1, '2026-10-16 14:19:14', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, 'light', '2026-10-16 11:27:53', '2026-10-16 14:19:14', NULL, 1, NULL, 0, NULL, NULL),
(9001, 'Wanjiru', '1999-03-14', 'female', 'everyone', 'Product designer who sketches in coffee shops and over-plans every weekend. Most Sundays I am at Karura Forest with a flask of tea, or at a gig I heard about three days earlier. I would like someone who can talk past \"how was your day\".', 'Product designer', 'Bachelor’s degree', 165, 'slim', 'never', 'socially', 'single', 'none', 'yes', 'Christian', 'English, Kiswahili, Kikuyu', 'Someone curious and kind, who plans the weekend but does not mind changing it.', 'relationship', NULL, NULL, NULL, NULL, 97, 1, '2026-07-15 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, 'light', '2026-07-12 04:48:22', '2026-11-12 23:01:10', NULL, 1, 'all', 1, NULL, NULL),
(9002, 'Akinyi', '1997-08-22', 'female', 'male', 'Nurse on rotating shifts, so my weekend sometimes lands on a Tuesday. I love Lake Victoria at sunset, fried omena and a proper laugh. Family matters a lot to me. I am not into games or people who disappear for days.', 'Registered nurse', 'Diploma or certificate', 168, 'average', 'never', 'never', 'single', 'none', 'yes', 'Christian', 'English, Kiswahili, Dholuo', 'A faithful, family-minded man who calls when he says he will.', 'relationship', NULL, NULL, NULL, NULL, 97, 1, '2026-08-09 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-08-06 04:48:22', '2026-10-16 18:51:43', NULL, 1, NULL, 0, NULL, NULL),
(9003, 'Zawadi', '2000-11-02', 'female', 'male', 'Born and raised in Mombasa. I work in hospitality, so I am good with people and slow at texting back. Swahili food is my love language: biryani, mahamri, kaimati. Show me your favourite corner of Old Town.', 'Guest relations officer', 'Diploma or certificate', 160, 'curvy', 'never', 'socially', 'single', 'none', 'maybe', 'Muslim', 'Kiswahili, English, Arabic', 'Someone grounded with good manners and a sense of humour.', 'dating', NULL, NULL, NULL, NULL, 92, 0, NULL, 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-09-02 04:48:22', '2026-10-16 18:52:38', NULL, 1, NULL, 0, NULL, NULL),
(9004, 'Chebet', '1995-01-30', 'female', 'male', 'Physio, Saturday park runner and slow-but-steady marathoner. Eldoret mornings are the best part of my day. I would like a partner who is active, or at least keen to carry the water bottle.', 'Physiotherapist', 'Bachelor’s degree', 172, 'athletic', 'never', 'socially', 'single', 'none', 'yes', 'Christian', 'English, Kiswahili, Kalenjin', 'An active, honest partner. Happy to wait at the finish line with snacks counts.', 'relationship', NULL, NULL, NULL, NULL, 97, 1, '2026-06-11 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-06-08 04:48:22', '2026-10-16 18:53:22', NULL, 1, NULL, 0, NULL, NULL),
(9005, 'Mwende', '2000-06-09', 'female', 'male', 'Backend engineer at a fintech. Gaming, anime and badly sung karaoke. I work from home on Tuesdays and Thursdays, so I know every good lunch spot within three kilometres of Westlands.', 'Backend engineer', 'Bachelor’s degree', 163, 'slim', 'never', 'socially', 'single', 'none', 'maybe', 'Christian', 'English, Kiswahili, Kikamba', 'Someone funny who can lose at Mario Kart gracefully.', 'dating', NULL, NULL, NULL, NULL, 92, 0, NULL, 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-09-13 04:48:22', '2026-10-16 18:56:02', NULL, 1, NULL, 0, NULL, NULL),
(9006, 'Njeri', '1992-09-17', 'female', 'male', 'Mum to one very energetic seven-year-old, chartered accountant, and a better cook than I admit. I am past small talk for its own sake. Honest, kind and patient is the combination I am after.', 'Chartered accountant', 'Master’s degree', 170, 'curvy', 'never', 'socially', 'divorced', 'have_at_home', 'maybe', 'Christian', 'English, Kiswahili, Kikuyu', 'Honest, kind and patient. Comfortable with a mum and a seven-year-old in the package.', 'relationship', NULL, NULL, NULL, NULL, 97, 1, '2026-05-12 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-05-09 04:48:22', '2026-10-16 18:57:08', NULL, 1, NULL, 0, NULL, NULL),
(9007, 'Halima', '1998-04-25', 'female', 'male', 'I run a small boutique in Malindi and style beach weddings on the side. I love old Swahili poetry, long walks on the sand and a good bargain. Looking for someone grounded who laughs easily.', 'Boutique owner', 'Diploma or certificate', 162, 'average', 'never', 'never', 'single', 'none', 'yes', 'Muslim', 'Kiswahili, English', 'A steady, respectful man with a sense of humour and plans for a family.', 'relationship', NULL, NULL, NULL, NULL, 97, 1, '2026-08-22 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-08-19 04:48:22', '2026-10-16 18:57:50', NULL, 1, NULL, 0, NULL, NULL),
(9008, 'Purity', '1995-12-05', 'female', 'male', 'I manage a lodge on a conservancy near Nanyuki, so my commute sometimes includes elephants. Early mornings, thick jumpers, bush breakfasts. I would rather talk about birds than work.', 'Lodge operations manager', 'Bachelor’s degree', 169, 'athletic', 'never', 'socially', 'single', 'none', 'yes', 'Christian', 'English, Kiswahili, Kimeru', 'Someone outdoorsy who is fine with early mornings and cold hands.', 'dating', NULL, NULL, NULL, NULL, 92, 0, NULL, 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-09-24 04:48:22', '2026-10-16 18:58:27', NULL, 1, NULL, 0, NULL, NULL),
(9009, 'Kevin', '1994-02-11', 'male', 'female', 'Product manager at a payments company, amateur barista and long-suffering Arsenal fan. I will make you a flat white and explain the offside rule whether you ask or not. Ready for something real.', 'Product manager', 'Master’s degree', 180, 'average', 'never', 'socially', 'single', 'none', 'yes', 'Christian', 'English, Kiswahili, Kikamba', 'A confident, warm woman who is as happy at a quiet dinner as at a concert.', 'relationship', NULL, NULL, NULL, NULL, 97, 1, '2026-07-23 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-07-20 04:48:22', '2026-10-16 18:59:30', NULL, 1, NULL, 0, NULL, NULL),
(9010, 'Brian', '1997-07-19', 'male', 'female', 'Agronomist who spends the week in maize and wheat fields around Uasin Gishu. I run a 10K most Saturdays and cook a decent ugali and sukuma. Looking for someone genuine to share the good weather with.', 'Agronomist', 'Bachelor’s degree', 183, 'athletic', 'never', 'socially', 'single', 'none', 'yes', 'Christian', 'English, Kiswahili, Kalenjin', 'Someone down to earth who enjoys the outdoors and a slow Sunday.', 'relationship', NULL, NULL, NULL, NULL, 92, 0, NULL, 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-08-13 04:48:22', '2026-10-16 19:00:28', NULL, 1, NULL, 0, NULL, NULL),
(9011, 'Victor', '1993-10-08', 'male', 'female', 'Advocate by day, jazz on vinyl by night. I read more than I admit and cook one very good pasta. I will argue happily about politics and never about the bill.', 'Advocate', 'Master’s degree', 186, 'average', 'never', 'socially', 'single', 'none', 'maybe', 'Christian', 'English, Kiswahili, Dholuo', 'Someone ambitious and fun. I like people who have their own opinions and share them.', 'dating', NULL, NULL, NULL, NULL, 97, 1, '2026-06-01 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-05-29 04:48:22', '2026-10-16 19:01:02', NULL, 1, NULL, 0, NULL, NULL),
(9012, 'Samuel', '1990-05-27', 'male', 'female', 'Architect and dad to a six-year-old who thinks I am funnier than I am. She lives with her mum and spends alternate weekends with me, so those are for Lego and pancakes. Looking for something calm and real.', 'Architect', 'Master’s degree', 178, 'average', 'never', 'socially', 'divorced', 'have_away', 'maybe', 'Christian', 'English, Kiswahili, Kikuyu', 'Warm, honest and secure. Kind to my daughter, kind to waiters.', 'relationship', NULL, NULL, NULL, NULL, 97, 1, '2026-07-06 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-07-03 04:48:22', '2026-10-16 19:01:44', NULL, 1, NULL, 0, NULL, NULL),
(9013, 'Hassan', '1998-09-03', 'male', 'female', 'Dive instructor and marine conservation officer on the north coast. I spend my days underwater and my evenings eating too much seafood. Ask me about the turtle project in Watamu.', 'Dive instructor', 'Diploma or certificate', 181, 'athletic', 'never', 'never', 'single', 'none', 'yes', 'Muslim', 'English, Kiswahili, Somali', 'Someone who loves the sea or is willing to try. Faith and family come first.', 'relationship', NULL, NULL, NULL, NULL, 92, 0, NULL, 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-09-07 04:48:22', '2026-10-16 19:03:13', NULL, 1, NULL, 0, NULL, NULL),
(9014, 'Dennis', '1998-12-21', 'male', 'female', 'Loan officer in Nakuru. Five-a-side on Thursdays, nyama choma on Sundays, and I have never once been on time for church. Looking for a good laugh and good company.', 'Loan officer', 'Bachelor’s degree', 175, 'average', 'never', 'socially', 'single', 'none', 'yes', 'Christian', 'English, Kiswahili, Luhya', 'A fun, easy-going woman who does not mind football on the TV.', 'dating', NULL, NULL, NULL, NULL, 92, 0, NULL, 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-09-28 04:48:22', '2026-10-16 19:03:54', NULL, 1, NULL, 0, NULL, NULL),
(9015, 'Ian', '1992-03-30', 'male', 'female', 'Mechanical engineer at a manufacturing plant and weekend mechanic. I will fix your bicycle and have opinions about your tyre pressure. Into road trips, old Land Cruisers and a slow braai.', 'Mechanical engineer', 'Bachelor’s degree', 177, 'average', 'occasionally', 'socially', 'single', 'none', 'maybe', 'Christian', 'English, Kiswahili, Kikuyu', 'A friend first. If it becomes more, wonderful.', 'friendship', NULL, NULL, NULL, NULL, 92, 0, NULL, 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-08-25 04:48:22', '2026-10-16 19:04:49', NULL, 1, NULL, 0, NULL, NULL),
(9016, 'Felix', '1991-08-14', 'male', 'female', 'Sound engineer who DJs on weekends, mostly benga, afrobeats and old rumba. If the speakers are good I will not stop talking about them. I keep the weekends busy but make room for the right person.', 'Sound engineer and DJ', 'Trade or vocational', 184, 'slim', 'occasionally', 'socially', 'single', 'none', 'no', 'Spiritual', 'English, Kiswahili, Luhya, Sheng', 'Good vibes, honesty and someone who will dance badly with me.', 'casual', NULL, NULL, NULL, NULL, 92, 0, NULL, 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-09-19 04:48:22', '2026-10-16 19:05:50', NULL, 1, NULL, 0, NULL, NULL),
(9017, 'Faith', '1998-05-03', 'female', 'everyone', 'I spent six years in hospitality and PR, so I am comfortable in any room and happy to listen. I enjoy a good dinner, live music and meeting people from different walks of life. I arrive on time, keep to the plan we agree in advance and always choose somewhere safe and well lit.', 'Event host and PR consultant', 'Bachelor’s degree', 167, 'slim', 'never', 'socially', 'single', 'none', NULL, 'Christian', 'English, Kiswahili, Kikuyu', NULL, NULL, NULL, NULL, NULL, NULL, 90, 1, '2026-05-22 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-05-19 04:48:22', '2026-10-16 18:49:54', NULL, 1, NULL, 0, NULL, NULL),
(9018, 'Naomi', '1996-10-12', 'female', 'everyone', 'Licensed guide with eight years of showing visitors and new residents around Nairobi. I plan routes around your interests, from the National Museum and the Giraffe Centre to the best nyama choma in Karen. Comfortable shoes, curiosity and a sense of humour are all you need.', 'Tour guide', 'Diploma or certificate', 170, 'athletic', 'never', 'socially', 'single', 'none', NULL, 'Christian', 'English, Kiswahili, Kalenjin, French', NULL, NULL, NULL, NULL, NULL, NULL, 90, 1, '2026-05-02 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-04-29 04:48:22', '2026-10-16 18:46:56', NULL, 1, NULL, 0, NULL, NULL),
(9019, 'Amina', '1997-01-18', 'female', 'everyone', 'Mombasa born and raised. I guide visitors through Old Town, Fort Jesus and the spice markets, and I know which stalls make the best kahawa and halwa. Modest dress, respectful company and plenty of local history.', 'Heritage guide', 'Bachelor’s degree', 161, 'average', 'never', 'socially', 'single', 'none', NULL, 'Muslim', 'Kiswahili, English, Arabic', NULL, NULL, NULL, NULL, NULL, NULL, 90, 1, '2026-06-19 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-06-16 04:48:22', '2026-10-16 18:45:05', NULL, 1, NULL, 0, NULL, NULL),
(9020, 'Grace', '1993-07-07', 'female', 'everyone', 'Ten years in corporate banking taught me how to make clients feel at ease. I am a good listener, comfortable with formal dinners and easy to brief on who is who beforehand. Discreet, punctual and always professional.', 'Former banker, now consultant', 'Master’s degree', 172, 'average', 'never', 'socially', 'single', 'none', NULL, 'Christian', 'English, Kiswahili, Kikuyu', NULL, NULL, NULL, NULL, NULL, NULL, 90, 1, '2026-06-03 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-05-31 04:48:22', '2026-10-16 18:42:43', NULL, 1, NULL, 0, NULL, NULL),
(9021, 'Mercy', '1998-11-23', 'female', 'everyone', 'Certified fitness coach who spends most weekends on the trails around Ngong and Kiserian. I match the pace to you and bring water, snacks and a first-aid kit. Great for hiking partners, early-morning runs or a first attempt at a longer trail.', 'Fitness coach', 'Diploma or certificate', 171, 'athletic', 'never', 'socially', 'single', 'none', NULL, 'Christian', 'English, Kiswahili, Maa', NULL, NULL, NULL, NULL, NULL, NULL, 90, 1, '2026-07-16 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-07-13 04:48:22', '2026-10-16 18:39:32', NULL, 1, NULL, 0, NULL, NULL),
(9022, 'Mwanaisha', '2000-02-27', 'female', 'everyone', 'I teach snorkelling and paddleboarding at a beach club in Diani, so I know the quiet stretches and the best times for calm water. Relaxed company for beach days and boat trips. I carry sunscreen for everyone.', 'Water sports instructor', 'Diploma or certificate', 164, 'athletic', 'never', 'socially', 'single', 'none', NULL, 'Muslim', 'Kiswahili, English, German', NULL, NULL, NULL, NULL, NULL, NULL, 90, 1, '2026-08-02 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-07-30 04:48:22', '2026-10-16 18:36:54', NULL, 1, NULL, 0, NULL, NULL),
(9023, 'Peter', '1994-06-21', 'male', 'everyone', 'Marketing manager who has been to a lot of weddings and enjoys every one. I dress well, mingle easily and stay with the plan we agree beforehand. Good for weddings, work events and parties where you would rather arrive with company.', 'Marketing manager', 'Bachelor’s degree', 182, 'athletic', 'never', 'socially', 'single', 'none', NULL, 'Christian', 'English, Kiswahili, Kikuyu', NULL, NULL, NULL, NULL, NULL, NULL, 90, 1, '2026-07-01 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-06-28 04:48:22', '2026-10-16 18:34:07', NULL, 1, NULL, 0, NULL, NULL),
(9024, 'Joseph', '1988-09-29', 'male', 'everyone', 'Safari guide with twelve years around Lake Naivasha, Hell’s Gate and Crescent Island. I drive carefully, carry a first-aid kit and know when the animals are most active. Ideal for travellers who would like a calm, informed companion for the day or the weekend.', 'Safari guide', 'Diploma or certificate', 179, 'average', 'never', 'socially', 'single', 'none', NULL, 'Christian', 'English, Kiswahili, Kikuyu, German', NULL, NULL, NULL, NULL, NULL, NULL, 90, 1, '2026-07-21 04:48:22', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-07-18 04:48:22', '2026-10-16 18:30:38', NULL, 1, NULL, 0, NULL, NULL),
(9025, 'lewis', '2003-09-10', 'male', 'everyone', 'I am at my best when I am moving around, checking out hidden spots, or taking off on a quick weekend trip. I bring a very relaxed, adaptable energy to everything I do. If you are looking for someone who can turn a simple evening into a memorable mini adventure or just keep you company while we discover a cool new venue, I am your person. Discretion and genuine fun come naturally to me. Weekend hiker, weekday coffee addict. Looking for good conversation and someone who laughs easily.', 'IT', NULL, 179, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dating', NULL, NULL, NULL, NULL, 79, 0, NULL, 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-10-16 12:09:04', '2026-10-16 19:08:16', NULL, 1, NULL, 0, NULL, NULL),
(9026, 'Shee', '2002-04-25', NULL, 'everyone', 'Confident and composed, but completely approachable. I bring a grounded vibe and pay attention to the little details that make a date feel comfortable. If you want a companion who is sharp, discreet, and easy to talk to about almost anything, we will get along just fine.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'English', NULL, NULL, NULL, NULL, NULL, NULL, 60, 1, '2026-10-16 12:58:31', 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-10-16 12:49:57', '2026-10-16 12:58:31', NULL, 1, NULL, 0, NULL, NULL),
(9027, 'Nia Wambui', '2000-04-18', 'female', 'male', 'A little adventurous, a little spontaneous, and always looking for something new to experience. I enjoy travelling, discovering beautiful restaurants, good music and staying active. I appreciate confidence, kindness and a man who knows what he wants. Looking for something genuine, with good conversations and a connection that feels natural.', 'Marketing Executive', 'Bachelor\'s degree', 165, 'slim', 'never', 'socially', 'single', 'none', 'yes', 'Christian', 'English, Kiswahili, Kikuyu', 'Someone confident, funny, ambitious and emotionally mature.', 'relationship', NULL, NULL, NULL, NULL, 95, 0, NULL, 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-11-08 14:52:46', '2026-11-08 14:52:46', NULL, 1, NULL, 0, NULL, NULL),
(9028, 'Amara Achieng', '1998-09-12', 'female', 'male', 'Ambitious, warm-hearted and a lover of the simple things in life. I enjoy cooking, weekend getaways, movies and spending quality time with people who matter. I believe attraction starts a conversation, but character keeps it going. Hoping to meet someone genuine, intentional and ready to build something meaningful.', 'Business Consultant', 'Bachelor\'s degree', 168, 'curvy', 'never', 'socially', 'single', 'none', 'maybe', 'Christian', 'English, Kiswahili, Dholuo', 'A mature, honest and thoughtful man who values communication and commitment.', 'relationship', NULL, NULL, NULL, NULL, 96, 0, NULL, 1, 7, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-11-08 14:52:46', '2026-11-08 14:52:46', NULL, 1, NULL, 0, NULL, NULL),
(9029, 'Juice', '2000-02-01', NULL, 'everyone', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0, 1, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-11-09 09:33:44', '2026-11-09 09:33:44', NULL, 1, NULL, 0, NULL, NULL),
(9030, 'Amina', '1997-04-12', 'female', 'male', 'Nurse by day, long walks and bad films by night. I like people who can hold a conversation and laugh at themselves.', 'Nurse at a city hospital', 'Kenyatta University, BSc Nursing', 167, 'average', 'never', 'socially', 'single', 'none', NULL, 'Christian', 'English, Swahili', 'Someone kind who is honest about what they want', 'relationship', NULL, NULL, NULL, NULL, 83, 1, '2026-11-09 03:49:59', 1, 9, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-11-09 03:49:59', '2026-11-09 12:21:39', NULL, 1, NULL, 0, NULL, NULL),
(9031, 'Brian', '1993-09-02', 'male', 'female', 'Software engineer, five-a-side on Saturdays, learning to cook something other than pasta. Ask me about road trips.', 'Backend engineer at a fintech', 'University of Nairobi, BSc Computer Science', 181, 'athletic', 'never', 'socially', 'single', 'none', NULL, 'Christian', 'English, Swahili, Kikuyu', 'Someone curious who likes being out as much as staying in', 'dating', NULL, NULL, NULL, NULL, 83, 1, '2026-11-09 03:49:59', 1, 9, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-11-09 03:49:59', '2026-11-09 12:21:08', NULL, 1, NULL, 0, NULL, NULL),
(9032, 'Cynthia', '2000-01-23', 'female', 'everyone', 'Graphic designer from the coast. Sunrise swimmer, terrible singer, very good at finding the best mandazi.', 'Freelance graphic designer', 'Technical University of Mombasa, Design', 162, 'slim', 'never', 'never', 'single', 'none', NULL, 'Muslim', 'English, Swahili', 'Friends first, and whatever grows from that', 'friendship', NULL, NULL, NULL, NULL, 78, 0, NULL, 1, 9, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-11-09 03:49:59', '2026-11-09 12:17:12', NULL, 1, NULL, 0, NULL, NULL),
(9033, 'Daniel', '1984-06-18', 'male', 'female', 'I build and manage property in and around Nairobi. Early mornings, long drives, and dinner with people worth listening to.', 'Property developer', 'Strathmore University, MBA', 184, 'average', 'never', 'socially', 'divorced', 'have_away', NULL, 'Christian', 'English, Swahili', NULL, 'relationship', '+254700000201', '+254700000201', NULL, NULL, 79, 1, '2026-11-09 03:49:59', 1, 9, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-11-09 03:49:59', '2026-11-09 12:20:49', NULL, 1, NULL, 1, NULL, NULL),
(9034, 'Esther', '1989-11-07', 'female', 'male', 'Banking by trade, galleries and good food by choice. I keep a small circle and I keep it well.', 'Head of corporate banking', 'University of Cape Town, MSc Finance', 170, 'slim', 'never', 'socially', 'single', 'none', NULL, 'Christian', 'English, Swahili, French', NULL, 'marriage', '+254700000202', '+254700000202', NULL, NULL, 79, 1, '2026-11-09 03:49:59', 1, 9, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-11-09 03:49:59', '2026-11-09 12:14:54', NULL, 1, NULL, 1, NULL, NULL),
(9035, 'Felix', '1987-02-14', 'male', 'everyone', 'I run a small software company and mentor far too many people. Lake sunsets, long books, strong coffee.', 'Founder, software company', 'Maseno University, BSc Software Engineering', 176, 'average', 'never', 'never', 'single', 'none', NULL, 'Christian', 'English, Swahili, Luo', NULL, 'relationship', '+254700000203', '+254700000203', NULL, NULL, 79, 1, NULL, 1, 9, 1, 1, 1, 1, 0, 1, 0, 0, 1, NULL, '2026-11-09 03:49:59', '2026-11-09 12:16:26', NULL, 1, NULL, 1, NULL, NULL),
(9036, 'Grace', '1995-07-30', 'female', 'everyone', 'Nairobi host. Dinners, gallery openings and company for people visiting the city.', 'Events host and city guide', NULL, 168, 'slim', NULL, NULL, NULL, NULL, NULL, NULL, 'English, Swahili, Italian', NULL, NULL, '+254700000301', '+254700000301', NULL, 'grace.nairobi', 68, 1, '2026-11-09 03:49:59', 1, 9, 1, 1, 1, 1, 1, 1, 0, 0, 1, NULL, '2026-11-09 03:49:59', '2026-11-09 12:14:14', NULL, 1, NULL, 0, NULL, NULL),
(9037, 'Hassan', '1991-03-09', 'male', 'everyone', 'Coast guide and travel company. Old Town, the islands, and everything between.', 'Travel host and guide', NULL, 179, 'athletic', NULL, NULL, NULL, NULL, NULL, NULL, 'English, Swahili, Arabic', NULL, NULL, '+254700000302', '+254700000302', NULL, NULL, 68, 1, '2026-11-09 03:49:59', 1, 9, 1, 1, 1, 1, 1, 1, 0, 0, 1, NULL, '2026-11-09 03:49:59', '2026-11-09 12:13:13', NULL, 1, NULL, 0, NULL, NULL),
(9038, 'Irene', '1998-12-05', 'female', 'everyone', 'Nakuru host. Weddings, parties and plus-one company for people who hate going alone.', 'Events companion', NULL, 165, 'curvy', NULL, NULL, NULL, NULL, NULL, NULL, 'English, Swahili', NULL, NULL, '+254700000303', '+254700000303', NULL, NULL, 63, 0, NULL, 1, 9, 1, 1, 1, 1, 1, 1, 0, 0, 1, NULL, '2026-11-09 03:49:59', '2026-11-09 12:12:30', NULL, 1, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_subscriptions`
--

CREATE TABLE `user_subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `billing_period` enum('weekly','monthly','yearly','custom') NOT NULL DEFAULT 'monthly',
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `starts_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 0,
  `renew_method` varchar(20) DEFAULT NULL,
  `renew_saved_id` int(10) UNSIGNED DEFAULT NULL,
  `renew_phone` varchar(20) DEFAULT NULL,
  `renew_tries` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `renew_next_at` datetime DEFAULT NULL,
  `renew_notified_for` datetime DEFAULT NULL,
  `granted_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_subscriptions`
--

INSERT INTO `user_subscriptions` (`id`, `user_id`, `plan_id`, `billing_period`, `status`, `starts_at`, `expires_at`, `cancelled_at`, `auto_renew`, `renew_method`, `renew_saved_id`, `renew_phone`, `renew_tries`, `renew_next_at`, `renew_notified_for`, `granted_by`, `created_at`) VALUES
(1, 9001, 2, 'monthly', 'cancelled', '2026-10-07 04:48:22', '2026-12-06 04:48:22', '2026-11-12 09:36:38', 0, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-10-16 04:48:22'),
(2, 9006, 3, 'monthly', 'active', '2026-09-28 04:48:22', '2026-10-28 04:48:22', NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-10-16 04:48:22'),
(3, 9009, 2, 'monthly', 'active', '2026-10-07 04:48:22', '2026-11-06 04:48:22', NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-10-16 04:48:22'),
(4, 9011, 3, 'monthly', 'active', '2026-09-28 04:48:22', '2026-10-28 04:48:22', NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-10-16 04:48:22'),
(5, 9015, 2, 'monthly', 'active', '2026-10-07 04:48:22', '2026-11-06 04:48:22', NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-10-16 04:48:22'),
(6, 9001, 3, 'weekly', 'active', '2026-11-12 09:36:38', '2026-11-25 09:36:38', NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-11-12 09:36:38');

-- --------------------------------------------------------

--
-- Table structure for table `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(20) NOT NULL,
  `selector` varchar(32) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_tokens`
--

INSERT INTO `user_tokens` (`id`, `user_id`, `type`, `selector`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(5, 1, 'remember', '155da5ba322960ff', '8d00114e45a3b38687b4e9105a72f6a4ebfe59e723558f19e3c2dd6a745b7fa1', '2026-11-15 12:44:58', NULL, '2026-10-16 12:44:58'),
(9, 1, 'remember', 'f4db3e45506a0f41', '6a33fe62476dee9f0f079485430bea263d8e9180f4d7f8ac686d4bd35ee690e1', '2026-11-15 13:38:45', NULL, '2026-10-16 13:38:45'),
(12, 1, 'remember', '1420627fac1cd494', '28168d71311a686a80578947126be935511d0bc26bd93f56241a648f022297b4', '2026-11-15 14:05:57', NULL, '2026-10-16 14:05:57'),
(19, 1, 'remember', 'cc6ecb99c33ba2b5', 'e9144a33d5de0ad64783c134a728ff5f28ef37f35ee5b4ead7c233a0e54facdd', '2026-11-15 15:16:28', NULL, '2026-10-16 15:16:28'),
(28, 1, 'remember', '5000070fa77bf695', 'b2702236ecaf49d03c3ef598b4f123a1bfd29d01f8112d6cbd373310070b1f49', '2026-11-15 15:53:16', NULL, '2026-10-16 15:53:16'),
(30, 1, 'remember', '42310a2728dcc40e', '7e553136b6cfdf5d95f5f7ac4ca1f918116de9c51b5bf418bb9e6320f66053bf', '2026-11-15 17:21:10', NULL, '2026-10-16 17:21:10'),
(34, 1, 'remember', '26adb7887717d746', '25752ff285ee1e26ed71eafe903a471ff96d93da60c87700f3d85c0ef3cf7923', '2026-11-15 18:10:41', NULL, '2026-10-16 18:10:41'),
(35, 1, 'remember', 'c18780963cf6a161', 'b38af26145677f37b26944476196f2d6f53916916c2add66b835018224e02f48', '2026-11-15 18:26:44', NULL, '2026-10-16 18:26:44'),
(38, 1, 'remember', '25caa867768a0a6e', '67f426e7d713a325eecc0f38b60a5681d8de921e2f04278efca10883c446286d', '2026-11-15 23:46:09', NULL, '2026-10-16 23:46:09'),
(39, 9025, 'remember', 'fe4ef24125cf3f35', '812d31bfaaf06c7630351015af0322662fb4804d8e0ee9a2713f6c75bdb736fc', '2026-11-15 23:47:53', NULL, '2026-10-16 23:47:53'),
(40, 9026, 'remember', 'aef5036c5fec0f6d', '5a04eef2e5faccc1a1475ee8afa212eed130e810a8908bc9e98f076d2ecd9fd1', '2026-11-16 00:04:34', NULL, '2026-10-17 00:04:34'),
(43, 1, 'remember', '8527495abe6c0e2d', '23bbc62d22911dc492d272ff87339c0fe124b0a3854cd9e464609de22e9ed624', '2026-11-16 22:22:32', NULL, '2026-10-17 22:22:32'),
(44, 1, 'remember', 'eac7600f83a08128', '7dc49f04becb21c50621674427e966a13fc965b14e7468e7400bec57c134b1a3', '2026-11-16 23:34:40', NULL, '2026-10-17 23:34:40'),
(45, 1, 'remember', 'f1739ab9c91bfdcd', '2c9e7427b3898e376c2130585e4491c8dbc5908dadf0edf27883431f3add5278', '2026-11-16 23:49:55', NULL, '2026-10-17 23:49:55'),
(46, 9001, 'remember', 'f30e9726c64d0c01', '288707bd43e54c1cebb9d06cceeb32ebce059ccde41e635c41ad43b705d11d65', '2026-11-17 00:23:54', NULL, '2026-10-18 00:23:54'),
(49, 9001, 'remember', 'cb9701c7e20a4772', '4c0f44427e4b55b548dae9758366926a393cdff50197d3d500e0919368176bb9', '2026-11-19 22:06:48', NULL, '2026-10-20 22:06:48'),
(51, 1, 'remember', 'dca4f8562e9cd181', '71298b7019368a839ef5b015bcdaef9399bbedcb79af1585eb8ff3249094a2aa', '2026-11-22 11:17:00', NULL, '2026-10-23 11:17:00'),
(53, 9001, 'remember', 'b306c1b56001f7f1', '2ad24e667dded89046d099835a874b0c9a9564d6eac5a2294ba1c7a443e7a16a', '2026-11-22 23:19:51', NULL, '2026-10-23 23:19:51'),
(55, 9001, 'remember', 'fb9a85bdbc867b8f', 'b7bbf6be7d27ba5a4b451b74bdabb811d48cc658bcf1e21687854e4a181f365f', '2026-11-30 22:47:26', NULL, '2026-10-31 22:47:26'),
(61, 1, 'remember', '03611ca52cfabcba', 'e05f2b3344794d29557f9e56bd4622c9ed215ca54a4f4b4f59a6f58e00a18806', '2027-05-08 03:33:02', NULL, '2026-11-08 23:22:57'),
(62, 1, 'remember', '87e59595e1125745', 'a040a616498fdcfee7395bc24b9978cc7f0990e5a50257ba1cae037897a067bb', '2027-05-08 03:33:02', NULL, '2026-11-09 03:33:02'),
(66, 1, 'remember', 'ca44387fe27f79ea', 'be88b0164a7217f7e8f74db58d9457c916397cfe9ca8dc837ea8f8f28e41b8e1', '2027-05-08 09:41:16', NULL, '2026-11-09 09:41:16'),
(67, 1, 'remember', '348ee6a03cb6f789', '8caa545cd370baa0b0f4f4b8bfb078693b8449719a85db86ab9e2e0308ab3aca', '2027-05-08 16:05:32', NULL, '2026-11-09 16:05:32'),
(68, 1, 'remember', '9c20e64e9cc95685', '3e101a758d22eb2035d3c7cbf76569576a69ebad06d08bb7d961e07400396454', '2027-05-08 16:39:44', NULL, '2026-11-09 16:39:44'),
(73, 1, 'remember', '082e9756959b955b', '1ffe02c6b4173990926cbaa7c002dae2de59683d43c323cb2d49e2d92f81da15', '2027-05-09 00:28:38', NULL, '2026-11-10 00:28:38'),
(87, 9001, 'remember', 'fb9cea9f01be70cc', '30dd6860acbf97b25bd77dff7739c04451f1eb178faae3e554c756bb3acbce4a', '2027-05-11 12:46:34', NULL, '2026-11-12 12:46:34'),
(90, 1, 'remember', 'c8be032f9b28d968', '390df09e4ac3392032c1d65b2fda9b52d27b0c03b63019f5cc9b909567c6e589', '2027-05-11 17:08:20', NULL, '2026-11-12 17:08:20'),
(106, 1, 'remember', '7a9f8a3a3de01ac3', '716ba3ac878be6241498c792f83362a9d1d6717e2489942a3ce0e994ca466d25', '2027-05-11 22:51:22', NULL, '2026-11-12 22:51:22'),
(108, 2, 'remember', '733cf94e4eae7864', 'b126e6b33e14e4bb695d2888c1d2e5b8b9ae9569c550d6166ba92ca48d8f90d3', '2027-05-11 23:24:35', NULL, '2026-11-12 23:24:35'),
(109, 1, 'remember', 'a7ecbbb0277ee9ff', '0ba61703e7215098aec06a5bd5643ece49718436e51f9555b527ade5ba792bfb', '2027-05-11 23:27:22', NULL, '2026-11-12 23:27:22');

-- --------------------------------------------------------

--
-- Table structure for table `user_videos`
--

CREATE TABLE `user_videos` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `video_path` varchar(255) NOT NULL,
  `poster_path` varchar(255) DEFAULT NULL,
  `duration_seconds` smallint(5) UNSIGNED DEFAULT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL,
  `moderation_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `verification_methods`
--

CREATE TABLE `verification_methods` (
  `id` int(10) UNSIGNED NOT NULL,
  `kind` varchar(10) NOT NULL DEFAULT 'pose',
  `name` varchar(60) NOT NULL,
  `instruction` varchar(400) DEFAULT NULL,
  `reference_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `verification_methods`
--

INSERT INTO `verification_methods` (`id`, `kind`, `name`, `instruction`, `reference_path`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'selfie', 'Selfie', NULL, NULL, 1, 1, '2026-10-16 13:38:36');

-- --------------------------------------------------------

--
-- Table structure for table `verification_requests`
--

CREATE TABLE `verification_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('photo','id','both') NOT NULL DEFAULT 'photo',
  `selfie_path` varchar(255) NOT NULL,
  `id_document_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `method_id` int(10) UNSIGNED DEFAULT NULL,
  `method_kind` varchar(10) NOT NULL DEFAULT 'selfie',
  `reference_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_accounts`
--

CREATE TABLE `wallet_accounts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `label` varchar(40) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `account_name` varchar(80) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wallet_accounts`
--

INSERT INTO `wallet_accounts` (`id`, `user_id`, `label`, `phone`, `account_name`, `created_at`) VALUES
(1, 2, NULL, '254758256440', 'Dennis Mwangi', '2026-10-16 15:51:42');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_payouts`
--

CREATE TABLE `wallet_payouts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `account_name` varchar(80) NOT NULL,
  `status` enum('requested','paid','rejected') NOT NULL DEFAULT 'requested',
  `payment_reference` varchar(30) DEFAULT NULL,
  `admin_note` varchar(255) DEFAULT NULL,
  `processed_by` int(10) UNSIGNED DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `kind` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `ref_type` varchar(20) DEFAULT NULL,
  `ref_id` int(10) UNSIGNED DEFAULT NULL,
  `note` varchar(200) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `user_id`, `kind`, `amount`, `ref_type`, `ref_id`, `note`, `created_at`) VALUES
(1, 2, 'gift', 35.00, 'gift', 3, 'Claimed Rose', '2026-10-16 15:51:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_log_admin` (`admin_id`,`created_at`),
  ADD KEY `idx_admin_log_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_admin_log_created` (`created_at`);

--
-- Indexes for table `admin_roles`
--
ALTER TABLE `admin_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admin_roles_slug` (`slug`);

--
-- Indexes for table `admin_user_notes`
--
ALTER TABLE `admin_user_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_notes_user` (`user_id`,`created_at`);

--
-- Indexes for table `advertisements`
--
ALTER TABLE `advertisements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ads_serve` (`status`,`placement`,`starts_at`,`expires_at`),
  ADD KEY `idx_ads_owner` (`user_id`,`status`),
  ADD KEY `idx_ads_draft` (`draft_token`);

--
-- Indexes for table `advertisement_events`
--
ALTER TABLE `advertisement_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ad_events_ad_date` (`advertisement_id`,`created_at`),
  ADD KEY `idx_ad_events_type_date` (`event_type`,`created_at`),
  ADD KEY `idx_ad_events_visitor` (`advertisement_id`,`event_type`,`visitor_key`);

--
-- Indexes for table `ad_packages`
--
ALTER TABLE `ad_packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ad_packages_show` (`is_active`,`sort_order`);

--
-- Indexes for table `agent_stories`
--
ALTER TABLE `agent_stories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_agent_stories_public` (`status`,`expires_at`,`created_at`),
  ADD KEY `idx_agent_stories_owner` (`user_id`,`status`,`expires_at`),
  ADD KEY `idx_agent_stories_expiry` (`expires_at`);

--
-- Indexes for table `agent_story_daily_stats`
--
ALTER TABLE `agent_story_daily_stats`
  ADD PRIMARY KEY (`stat_date`);

--
-- Indexes for table `agent_story_views`
--
ALTER TABLE `agent_story_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_agent_story_viewer` (`story_id`,`viewer_key`),
  ADD KEY `idx_agent_story_views_date` (`viewed_at`),
  ADD KEY `idx_agent_story_views_user` (`viewer_user_id`,`viewed_at`);

--
-- Indexes for table `algorithm_scores`
--
ALTER TABLE `algorithm_scores`
  ADD PRIMARY KEY (`user_id`,`target_id`),
  ADD KEY `idx_algorithm_scores_rank` (`user_id`,`total_score`),
  ADD KEY `idx_algorithm_scores_computed` (`computed_at`),
  ADD KEY `fk_algo_target` (`target_id`);

--
-- Indexes for table `analytics_daily`
--
ALTER TABLE `analytics_daily`
  ADD PRIMARY KEY (`stat_date`,`metric`);

--
-- Indexes for table `bio_suggestions`
--
ALTER TABLE `bio_suggestions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blocks`
--
ALTER TABLE `blocks`
  ADD PRIMARY KEY (`blocker_id`,`blocked_id`),
  ADD KEY `idx_blocks_blocked` (`blocked_id`);

--
-- Indexes for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_blog_categories_slug` (`slug`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_blog_posts_slug` (`slug`),
  ADD KEY `idx_blog_posts_status` (`status`,`published_at`),
  ADD KEY `idx_blog_posts_cat` (`category_id`);

--
-- Indexes for table `boosts`
--
ALTER TABLE `boosts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_boosts_user` (`user_id`,`expires_at`),
  ADD KEY `idx_boosts_active` (`expires_at`);

--
-- Indexes for table `boost_credits`
--
ALTER TABLE `boost_credits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_boost_credits_user` (`user_id`,`used_at`);

--
-- Indexes for table `boost_packs`
--
ALTER TABLE `boost_packs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_boost_packs` (`kind`,`is_active`,`sort_order`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_status` (`status`,`created_at`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_conversations_pair` (`user1_id`,`user2_id`),
  ADD KEY `idx_conversations_u1` (`user1_id`,`last_message_at`),
  ADD KEY `idx_conversations_u2` (`user2_id`,`last_message_at`);

--
-- Indexes for table `conversation_pins`
--
ALTER TABLE `conversation_pins`
  ADD PRIMARY KEY (`conversation_id`,`user_id`),
  ADD KEY `idx_conversation_pins_user` (`user_id`);

--
-- Indexes for table `daily_picks`
--
ALTER TABLE `daily_picks`
  ADD PRIMARY KEY (`user_id`,`picked_user_id`,`pick_date`),
  ADD KEY `idx_daily_picks_date` (`user_id`,`pick_date`),
  ADD KEY `fk_dp_picked` (`picked_user_id`);

--
-- Indexes for table `daily_usage`
--
ALTER TABLE `daily_usage`
  ADD PRIMARY KEY (`user_id`,`usage_date`),
  ADD KEY `idx_daily_usage_date` (`usage_date`);

--
-- Indexes for table `discovery_preferences`
--
ALTER TABLE `discovery_preferences`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `dm_ledger`
--
ALTER TABLE `dm_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dm_ledger_user` (`user_id`,`id`);

--
-- Indexes for table `dm_packs`
--
ALTER TABLE `dm_packs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dm_unlocks`
--
ALTER TABLE `dm_unlocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_dm_unlock` (`user_id`,`target_id`),
  ADD KEY `idx_dm_unlock_day` (`user_id`,`created_at`),
  ADD KEY `fk_dm_unlock_target` (`target_id`);

--
-- Indexes for table `elite_access`
--
ALTER TABLE `elite_access`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `elite_profiles`
--
ALTER TABLE `elite_profiles`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_elite_status` (`status`,`is_featured`);

--
-- Indexes for table `elite_requests`
--
ALTER TABLE `elite_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_elite_request` (`from_id`,`to_id`),
  ADD KEY `idx_elite_req_to` (`to_id`,`status`,`passed`),
  ADD KEY `idx_elite_req_from` (`from_id`,`status`);

--
-- Indexes for table `elite_saves`
--
ALTER TABLE `elite_saves`
  ADD PRIMARY KEY (`user_id`,`owner_id`),
  ADD KEY `idx_elite_saves_owner` (`owner_id`);

--
-- Indexes for table `elite_unlocks`
--
ALTER TABLE `elite_unlocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_elite_unlock` (`viewer_id`,`owner_id`),
  ADD KEY `idx_elite_unlock_owner` (`owner_id`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_faqs_cat` (`category`,`sort_order`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`user_id`,`target_id`),
  ADD KEY `idx_favorites_target` (`target_id`);

--
-- Indexes for table `gift_types`
--
ALTER TABLE `gift_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hero_slides`
--
ALTER TABLE `hero_slides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hero_slides_order` (`is_active`,`sort_order`);

--
-- Indexes for table `host_blocked_dates`
--
ALTER TABLE `host_blocked_dates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_host_blocked` (`host_id`,`blocked_on`);

--
-- Indexes for table `host_bookings`
--
ALTER TABLE `host_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bookings_host` (`host_id`,`booking_date`,`status`),
  ADD KEY `idx_bookings_client` (`client_id`,`status`),
  ADD KEY `idx_bookings_status` (`status`,`booking_date`);

--
-- Indexes for table `host_booking_events`
--
ALTER TABLE `host_booking_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_host_booking_events` (`booking_id`,`id`);

--
-- Indexes for table `host_booking_ext`
--
ALTER TABLE `host_booking_ext`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `idx_host_booking_ext_accept` (`accept_by`),
  ADD KEY `idx_host_booking_ext_settled` (`settled_at`),
  ADD KEY `idx_host_booking_ext_refund` (`refund_status`);

--
-- Indexes for table `host_categories`
--
ALTER TABLE `host_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_host_categories_name` (`name`);

--
-- Indexes for table `host_connection_profiles`
--
ALTER TABLE `host_connection_profiles`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_connection_public` (`enabled`,`approval_status`,`city`);

--
-- Indexes for table `host_connection_services`
--
ALTER TABLE `host_connection_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_connection_service_code` (`code`);

--
-- Indexes for table `host_offerings`
--
ALTER TABLE `host_offerings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_host_offerings_host` (`host_id`,`is_active`,`sort_order`);

--
-- Indexes for table `host_payouts`
--
ALTER TABLE `host_payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_host_payouts_host` (`host_id`,`status`);

--
-- Indexes for table `host_profiles`
--
ALTER TABLE `host_profiles`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_host_profiles_status` (`approval_status`,`is_featured`),
  ADD KEY `idx_host_profiles_rating` (`rating_avg`),
  ADD KEY `fk_host_profiles_cat` (`category_id`);

--
-- Indexes for table `host_profile_ext`
--
ALTER TABLE `host_profile_ext`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `host_reviews`
--
ALTER TABLE `host_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_host_reviews_booking` (`booking_id`),
  ADD KEY `idx_host_reviews_host` (`host_id`,`status`),
  ADD KEY `fk_host_reviews_reviewer` (`reviewer_id`);

--
-- Indexes for table `host_services`
--
ALTER TABLE `host_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_host_services_name` (`name`);

--
-- Indexes for table `ice_breakers`
--
ALTER TABLE `ice_breakers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `interests`
--
ALTER TABLE `interests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_interests_name` (`name`),
  ADD KEY `idx_interests_cat` (`category`,`sort_order`);

--
-- Indexes for table `intl_access`
--
ALTER TABLE `intl_access`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `intl_profiles`
--
ALTER TABLE `intl_profiles`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_intl_status` (`status`,`country`);

--
-- Indexes for table `landing_sections`
--
ALTER TABLE `landing_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_landing_sections_key` (`section_key`);

--
-- Indexes for table `launch_access_campaigns`
--
ALTER TABLE `launch_access_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_launch_campaign_active` (`is_enabled`,`starts_at`,`ends_at`);

--
-- Indexes for table `launch_access_grants`
--
ALTER TABLE `launch_access_grants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_launch_campaign_user` (`campaign_id`,`user_id`),
  ADD KEY `idx_launch_grant_user` (`user_id`,`status`,`expires_at`),
  ADD KEY `idx_launch_grant_expiry` (`status`,`expires_at`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_locations_slug` (`slug`),
  ADD KEY `idx_locations_type` (`type`,`is_active`),
  ADD KEY `idx_locations_parent` (`parent_id`),
  ADD KEY `idx_locations_popular` (`is_popular`,`sort_order`),
  ADD KEY `idx_locations_geo` (`latitude`,`longitude`),
  ADD KEY `idx_locations_landing` (`show_landing`,`is_active`,`sort_order`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_matches_pair` (`user1_id`,`user2_id`),
  ADD KEY `idx_matches_u1` (`user1_id`,`is_active`),
  ADD KEY `idx_matches_u2` (`user2_id`,`is_active`);

--
-- Indexes for table `media_files`
--
ALTER TABLE `media_files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_media_path` (`path`),
  ADD KEY `idx_media_kind` (`kind`,`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_messages_conv` (`conversation_id`,`id`),
  ADD KEY `idx_messages_sender` (`sender_id`,`created_at`),
  ADD KEY `idx_messages_created` (`created_at`);

--
-- Indexes for table `message_abuse_events`
--
ALTER TABLE `message_abuse_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_msg_abuse_user_time` (`user_id`,`created_at`),
  ADD KEY `idx_msg_abuse_reason_time` (`reason`,`created_at`);

--
-- Indexes for table `mpesa_b2c_requests`
--
ALTER TABLE `mpesa_b2c_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_b2c_originator` (`originator_id`),
  ADD KEY `idx_b2c_ref` (`kind`,`ref_id`,`status`),
  ADD KEY `idx_b2c_conv` (`conversation_id`);

--
-- Indexes for table `mpesa_c2b_transactions`
--
ALTER TABLE `mpesa_c2b_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_c2b_trans` (`trans_id`),
  ADD KEY `idx_c2b_status` (`status`,`created_at`),
  ADD KEY `idx_c2b_ref` (`bill_ref`);

--
-- Indexes for table `nav_items`
--
ALTER TABLE `nav_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nav_menu` (`menu`,`is_active`,`sort_order`);

--
-- Indexes for table `newsletter_campaigns`
--
ALTER TABLE `newsletter_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_newsletter_campaigns_status` (`status`,`created_at`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_newsletter_email` (`email`),
  ADD UNIQUE KEY `uq_newsletter_token` (`token`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`,`created_at`),
  ADD KEY `idx_notifications_type` (`user_id`,`type`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pages_slug` (`slug`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payments_reference` (`reference`),
  ADD KEY `idx_payments_user` (`user_id`,`created_at`),
  ADD KEY `idx_payments_status` (`status`,`payment_method`),
  ADD KEY `idx_payments_mpesa` (`mpesa_checkout_id`),
  ADD KEY `idx_payments_created` (`created_at`),
  ADD KEY `idx_payments_gateway_ref` (`gateway_ref`);

--
-- Indexes for table `payment_events`
--
ALTER TABLE `payment_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payment_event` (`gateway`,`event_id`),
  ADD KEY `idx_payment_events_created` (`created_at`);

--
-- Indexes for table `pay_saved_methods`
--
ALTER TABLE `pay_saved_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_saved_method` (`user_id`,`gateway`,`fingerprint`),
  ADD KEY `idx_saved_user` (`user_id`,`is_active`);

--
-- Indexes for table `profile_views`
--
ALTER TABLE `profile_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_profile_views_pair` (`viewer_id`,`viewed_id`),
  ADD KEY `idx_profile_views_viewed` (`viewed_id`,`last_viewed_at`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`bucket`,`window_start`),
  ADD KEY `idx_rate_limits_window` (`window_start`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reports_status` (`status`,`created_at`),
  ADD KEY `idx_reports_reported` (`reported_id`),
  ADD KEY `fk_reports_reporter` (`reporter_id`);

--
-- Indexes for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `search_history`
--
ALTER TABLE `search_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_search_history_user` (`user_id`,`created_at`);

--
-- Indexes for table `sent_gifts`
--
ALTER TABLE `sent_gifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sent_gifts_receiver` (`receiver_id`,`status`,`is_seen`),
  ADD KEY `idx_sent_gifts_sender` (`sender_id`),
  ADD KEY `fk_gifts_type` (`gift_type_id`);

--
-- Indexes for table `seo_404`
--
ALTER TABLE `seo_404`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_seo_404_path` (`path`),
  ADD KEY `idx_seo_404_hits` (`hits`);

--
-- Indexes for table `seo_pages`
--
ALTER TABLE `seo_pages`
  ADD PRIMARY KEY (`page_key`);

--
-- Indexes for table `seo_redirects`
--
ALTER TABLE `seo_redirects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_seo_redirect_from` (`from_path`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_plans_slug` (`slug`);

--
-- Indexes for table `swipes`
--
ALTER TABLE `swipes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_swipes_pair` (`swiper_id`,`swiped_id`),
  ADD KEY `idx_swipes_target` (`swiped_id`,`direction`,`created_at`),
  ADD KEY `idx_swipes_created` (`created_at`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `typing_status`
--
ALTER TABLE `typing_status`
  ADD PRIMARY KEY (`conversation_id`,`user_id`),
  ADD KEY `fk_typing_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_status` (`status`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_active` (`last_active_at`),
  ADD KEY `idx_users_created` (`created_at`),
  ADD KEY `idx_users_featured` (`featured_until`),
  ADD KEY `fk_users_admin_role` (`admin_role_id`);

--
-- Indexes for table `user_interests`
--
ALTER TABLE `user_interests`
  ADD PRIMARY KEY (`user_id`,`interest_id`),
  ADD KEY `idx_user_interests_interest` (`interest_id`);

--
-- Indexes for table `user_locations`
--
ALTER TABLE `user_locations`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_user_locations_geo` (`latitude`,`longitude`),
  ADD KEY `idx_user_locations_town` (`location_id`),
  ADD KEY `idx_user_locations_county` (`county_id`);

--
-- Indexes for table `user_photos`
--
ALTER TABLE `user_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_photos_user` (`user_id`,`is_primary`,`sort_order`),
  ADD KEY `idx_photos_moderation` (`moderation_status`,`created_at`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_profiles_dob` (`date_of_birth`),
  ADD KEY `idx_profiles_gender` (`gender`),
  ADD KEY `idx_profiles_onboarded` (`onboarding_completed`,`is_paused`);

--
-- Indexes for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subs_user` (`user_id`,`status`,`expires_at`),
  ADD KEY `idx_subs_expires` (`status`,`expires_at`),
  ADD KEY `fk_subs_plan` (`plan_id`),
  ADD KEY `idx_subs_renew` (`status`,`auto_renew`,`expires_at`);

--
-- Indexes for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_tokens_selector` (`selector`),
  ADD KEY `idx_user_tokens_user` (`user_id`,`type`);

--
-- Indexes for table `user_videos`
--
ALTER TABLE `user_videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_videos_user` (`user_id`),
  ADD KEY `idx_videos_moderation` (`moderation_status`);

--
-- Indexes for table `verification_methods`
--
ALTER TABLE `verification_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vmethod_active` (`is_active`,`sort_order`);

--
-- Indexes for table `verification_requests`
--
ALTER TABLE `verification_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_verification_status` (`status`,`created_at`),
  ADD KEY `idx_verification_user` (`user_id`);

--
-- Indexes for table `wallet_accounts`
--
ALTER TABLE `wallet_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wallet_acc_user` (`user_id`);

--
-- Indexes for table `wallet_payouts`
--
ALTER TABLE `wallet_payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wallet_pay_user` (`user_id`,`status`),
  ADD KEY `idx_wallet_pay_status` (`status`,`created_at`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wallet_tx_user` (`user_id`,`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=247;

--
-- AUTO_INCREMENT for table `admin_roles`
--
ALTER TABLE `admin_roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `admin_user_notes`
--
ALTER TABLE `admin_user_notes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `advertisements`
--
ALTER TABLE `advertisements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `advertisement_events`
--
ALTER TABLE `advertisement_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ad_packages`
--
ALTER TABLE `ad_packages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `agent_stories`
--
ALTER TABLE `agent_stories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=980008;

--
-- AUTO_INCREMENT for table `agent_story_views`
--
ALTER TABLE `agent_story_views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `bio_suggestions`
--
ALTER TABLE `bio_suggestions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `boosts`
--
ALTER TABLE `boosts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `boost_credits`
--
ALTER TABLE `boost_credits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `boost_packs`
--
ALTER TABLE `boost_packs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `dm_ledger`
--
ALTER TABLE `dm_ledger`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dm_packs`
--
ALTER TABLE `dm_packs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `dm_unlocks`
--
ALTER TABLE `dm_unlocks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `elite_requests`
--
ALTER TABLE `elite_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `elite_unlocks`
--
ALTER TABLE `elite_unlocks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `gift_types`
--
ALTER TABLE `gift_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `hero_slides`
--
ALTER TABLE `hero_slides`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `host_blocked_dates`
--
ALTER TABLE `host_blocked_dates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `host_bookings`
--
ALTER TABLE `host_bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `host_booking_events`
--
ALTER TABLE `host_booking_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `host_categories`
--
ALTER TABLE `host_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `host_connection_services`
--
ALTER TABLE `host_connection_services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `host_offerings`
--
ALTER TABLE `host_offerings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `host_payouts`
--
ALTER TABLE `host_payouts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `host_reviews`
--
ALTER TABLE `host_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `host_services`
--
ALTER TABLE `host_services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `ice_breakers`
--
ALTER TABLE `ice_breakers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `interests`
--
ALTER TABLE `interests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `landing_sections`
--
ALTER TABLE `landing_sections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `launch_access_campaigns`
--
ALTER TABLE `launch_access_campaigns`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `launch_access_grants`
--
ALTER TABLE `launch_access_grants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=152;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `media_files`
--
ALTER TABLE `media_files`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `message_abuse_events`
--
ALTER TABLE `message_abuse_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mpesa_b2c_requests`
--
ALTER TABLE `mpesa_b2c_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mpesa_c2b_transactions`
--
ALTER TABLE `mpesa_c2b_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nav_items`
--
ALTER TABLE `nav_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `newsletter_campaigns`
--
ALTER TABLE `newsletter_campaigns`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payment_events`
--
ALTER TABLE `payment_events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pay_saved_methods`
--
ALTER TABLE `pay_saved_methods`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profile_views`
--
ALTER TABLE `profile_views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `search_history`
--
ALTER TABLE `search_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sent_gifts`
--
ALTER TABLE `sent_gifts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `seo_404`
--
ALTER TABLE `seo_404`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `seo_redirects`
--
ALTER TABLE `seo_redirects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `swipes`
--
ALTER TABLE `swipes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9039;

--
-- AUTO_INCREMENT for table `user_photos`
--
ALTER TABLE `user_photos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `user_videos`
--
ALTER TABLE `user_videos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `verification_methods`
--
ALTER TABLE `verification_methods`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `verification_requests`
--
ALTER TABLE `verification_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallet_accounts`
--
ALTER TABLE `wallet_accounts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `wallet_payouts`
--
ALTER TABLE `wallet_payouts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_user_notes`
--
ALTER TABLE `admin_user_notes`
  ADD CONSTRAINT `fk_admin_notes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `algorithm_scores`
--
ALTER TABLE `algorithm_scores`
  ADD CONSTRAINT `fk_algo_target` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_algo_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blocks`
--
ALTER TABLE `blocks`
  ADD CONSTRAINT `fk_blocks_blocked` FOREIGN KEY (`blocked_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_blocks_blocker` FOREIGN KEY (`blocker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `fk_blog_posts_cat` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `boosts`
--
ALTER TABLE `boosts`
  ADD CONSTRAINT `fk_boosts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `boost_credits`
--
ALTER TABLE `boost_credits`
  ADD CONSTRAINT `fk_boost_credits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `fk_conv_u1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_conv_u2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversation_pins`
--
ALTER TABLE `conversation_pins`
  ADD CONSTRAINT `fk_conv_pins_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_conv_pins_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `daily_picks`
--
ALTER TABLE `daily_picks`
  ADD CONSTRAINT `fk_dp_picked` FOREIGN KEY (`picked_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `daily_usage`
--
ALTER TABLE `daily_usage`
  ADD CONSTRAINT `fk_daily_usage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `discovery_preferences`
--
ALTER TABLE `discovery_preferences`
  ADD CONSTRAINT `fk_discovery_prefs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dm_ledger`
--
ALTER TABLE `dm_ledger`
  ADD CONSTRAINT `fk_dm_ledger_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dm_unlocks`
--
ALTER TABLE `dm_unlocks`
  ADD CONSTRAINT `fk_dm_unlock_target` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dm_unlock_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_fav_target` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `host_blocked_dates`
--
ALTER TABLE `host_blocked_dates`
  ADD CONSTRAINT `fk_host_blocked_host` FOREIGN KEY (`host_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `host_bookings`
--
ALTER TABLE `host_bookings`
  ADD CONSTRAINT `fk_bookings_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookings_host` FOREIGN KEY (`host_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `host_booking_events`
--
ALTER TABLE `host_booking_events`
  ADD CONSTRAINT `fk_host_booking_events_booking` FOREIGN KEY (`booking_id`) REFERENCES `host_bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `host_booking_ext`
--
ALTER TABLE `host_booking_ext`
  ADD CONSTRAINT `fk_host_booking_ext_booking` FOREIGN KEY (`booking_id`) REFERENCES `host_bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `host_connection_profiles`
--
ALTER TABLE `host_connection_profiles`
  ADD CONSTRAINT `fk_connection_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `host_offerings`
--
ALTER TABLE `host_offerings`
  ADD CONSTRAINT `fk_host_offerings_host` FOREIGN KEY (`host_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `host_payouts`
--
ALTER TABLE `host_payouts`
  ADD CONSTRAINT `fk_host_payouts_host` FOREIGN KEY (`host_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `host_profiles`
--
ALTER TABLE `host_profiles`
  ADD CONSTRAINT `fk_host_profiles_cat` FOREIGN KEY (`category_id`) REFERENCES `host_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_host_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `host_profile_ext`
--
ALTER TABLE `host_profile_ext`
  ADD CONSTRAINT `fk_host_ext_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `host_reviews`
--
ALTER TABLE `host_reviews`
  ADD CONSTRAINT `fk_host_reviews_booking` FOREIGN KEY (`booking_id`) REFERENCES `host_bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_host_reviews_host` FOREIGN KEY (`host_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_host_reviews_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `intl_access`
--
ALTER TABLE `intl_access`
  ADD CONSTRAINT `fk_intl_access_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `intl_profiles`
--
ALTER TABLE `intl_profiles`
  ADD CONSTRAINT `fk_intl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `fk_matches_u1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_matches_u2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `fk_notification_prefs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pay_saved_methods`
--
ALTER TABLE `pay_saved_methods`
  ADD CONSTRAINT `fk_saved_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profile_views`
--
ALTER TABLE `profile_views`
  ADD CONSTRAINT `fk_pv_viewed` FOREIGN KEY (`viewed_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pv_viewer` FOREIGN KEY (`viewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_reported` FOREIGN KEY (`reported_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reports_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `search_history`
--
ALTER TABLE `search_history`
  ADD CONSTRAINT `fk_search_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sent_gifts`
--
ALTER TABLE `sent_gifts`
  ADD CONSTRAINT `fk_gifts_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gifts_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gifts_type` FOREIGN KEY (`gift_type_id`) REFERENCES `gift_types` (`id`);

--
-- Constraints for table `swipes`
--
ALTER TABLE `swipes`
  ADD CONSTRAINT `fk_swipes_swiped` FOREIGN KEY (`swiped_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_swipes_swiper` FOREIGN KEY (`swiper_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `typing_status`
--
ALTER TABLE `typing_status`
  ADD CONSTRAINT `fk_typing_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_typing_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_admin_role` FOREIGN KEY (`admin_role_id`) REFERENCES `admin_roles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_interests`
--
ALTER TABLE `user_interests`
  ADD CONSTRAINT `fk_ui_interest` FOREIGN KEY (`interest_id`) REFERENCES `interests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ui_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_locations`
--
ALTER TABLE `user_locations`
  ADD CONSTRAINT `fk_user_locations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_photos`
--
ALTER TABLE `user_photos`
  ADD CONSTRAINT `fk_photos_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `fk_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD CONSTRAINT `fk_subs_plan` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`),
  ADD CONSTRAINT `fk_subs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `fk_user_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_videos`
--
ALTER TABLE `user_videos`
  ADD CONSTRAINT `fk_videos_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `verification_requests`
--
ALTER TABLE `verification_requests`
  ADD CONSTRAINT `fk_verification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_accounts`
--
ALTER TABLE `wallet_accounts`
  ADD CONSTRAINT `fk_wallet_acc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_payouts`
--
ALTER TABLE `wallet_payouts`
  ADD CONSTRAINT `fk_wallet_pay_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `fk_wallet_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
