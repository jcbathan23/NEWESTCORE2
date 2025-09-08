-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 08, 2025 at 12:18 PM
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
-- Database: `core2`
--

USE `core2`;

-- --------------------------------------------------------

--
-- Table structure for table `cargo`
--

DROP TABLE IF EXISTS `cargo`;
CREATE TABLE IF NOT EXISTS `cargo` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `cargo_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_id` int UNSIGNED NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` decimal(10,2) DEFAULT NULL COMMENT 'in kg',
  `volume` decimal(10,2) DEFAULT NULL COMMENT 'in cubic meters',
  `value` decimal(12,2) DEFAULT NULL COMMENT 'cargo value',
  `dangerous_goods` tinyint(1) NOT NULL DEFAULT '0',
  `special_instructions` text COLLATE utf8mb4_unicode_ci,
  `loading_point` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unloading_point` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `loaded_at` timestamp NULL DEFAULT NULL,
  `unloaded_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','loaded','in_transit','delivered','damaged','lost') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `recipient_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cargo_code` (`cargo_code`),
  KEY `idx_service_id` (`service_id`),
  KEY `idx_status` (`status`),
  KEY `idx_tracking_number` (`tracking_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

DROP TABLE IF EXISTS `drivers`;
CREATE TABLE IF NOT EXISTS `drivers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `driver_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_id` int UNSIGNED NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `license_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `license_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `license_expiry` date NOT NULL,
  `medical_certificate` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medical_expiry` date DEFAULT NULL,
  `experience_years` int UNSIGNED DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL COMMENT '1-5 rating',
  `total_ratings` int UNSIGNED NOT NULL DEFAULT '0',
  `emergency_contact_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','suspended','on_leave') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `assigned_vehicle_id` int UNSIGNED DEFAULT NULL,
  `last_trip_date` date DEFAULT NULL,
  `total_trips` int UNSIGNED NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `driver_code` (`driver_code`),
  UNIQUE KEY `license_number` (`license_number`),
  KEY `assigned_vehicle_id` (`assigned_vehicle_id`),
  KEY `idx_provider_id` (`provider_id`),
  KEY `idx_license_number` (`license_number`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `providers`
--

DROP TABLE IF EXISTS `providers`;
CREATE TABLE IF NOT EXISTS `providers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_area` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `monthly_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contract_start` date NOT NULL,
  `contract_end` date NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `providers`
--

INSERT INTO `providers` (`id`, `name`, `type`, `contact_person`, `contact_email`, `contact_phone`, `service_area`, `monthly_rate`, `status`, `contract_start`, `contract_end`, `notes`, `created_at`, `updated_at`) VALUES
(3, 'RORO', 'Express', 'roro', 'roro@gmail.com', '09213531232', 'Manila', 999.00, 'Pending', '2025-09-03', '2026-09-03', 'qasd', '2025-09-03 08:01:07', '2025-09-03 08:01:07');

-- --------------------------------------------------------

--
-- Table structure for table `request_status_history`
--

DROP TABLE IF EXISTS `request_status_history`;
CREATE TABLE IF NOT EXISTS `request_status_history` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` int UNSIGNED NOT NULL,
  `previous_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by_user_id` int UNSIGNED DEFAULT NULL,
  `changed_by_role` enum('user','admin','provider') COLLATE utf8mb4_unicode_ci NOT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `changed_by_user_id` (`changed_by_user_id`),
  KEY `idx_request_id` (`request_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

DROP TABLE IF EXISTS `routes`;
CREATE TABLE IF NOT EXISTS `routes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_point` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `end_point` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `distance` decimal(10,1) NOT NULL DEFAULT '0.0',
  `frequency` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estimated_time` int NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`id`, `name`, `type`, `start_point`, `end_point`, `distance`, `frequency`, `status`, `estimated_time`, `notes`, `created_at`, `updated_at`) VALUES
(11, 'Bus Route 101', 'Secondary', 'City Center', 'Airport', 18.2, 'Every 30 min', 'Active', 35, 'Direct bus service to airport', '2025-09-04 03:53:35', '2025-09-04 03:53:35'),
(12, 'Express Route X1', 'Express', 'Downtown', 'Business District', 12.8, 'Every hour', 'Active', 20, 'Express service for business commuters', '2025-09-04 03:53:35', '2025-09-04 03:53:35'),
(13, 'Local Route L5', 'Local', 'Residential Area A', 'Shopping Mall', 8.4, 'Every 2 hours', 'Active', 25, 'Local service for residential areas', '2025-09-04 03:53:35', '2025-09-04 03:53:35'),
(14, 'Metro Line B', 'Primary', 'East Terminal', 'West Terminal', 22.1, 'Every 15 min', 'Maintenance', 40, 'Secondary metro line under maintenance', '2025-09-04 03:53:35', '2025-09-04 03:53:35'),
(15, 'Bus Route 202', 'Secondary', 'University', 'City Center', 15.6, 'Every 30 min', 'Planned', 30, 'New route serving university area', '2025-09-04 03:53:35', '2025-09-04 03:53:35');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
CREATE TABLE IF NOT EXISTS `schedules` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `route` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `departure` time NOT NULL,
  `arrival` time NOT NULL,
  `frequency` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `capacity` int NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `name`, `route`, `vehicle_type`, `departure`, `arrival`, `frequency`, `status`, `start_date`, `end_date`, `capacity`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'asd', 'East-West Connector', 'Bus', '15:40:00', '04:41:00', 'Weekdays', 'Maintenance', '2025-09-07', '2025-09-12', 12, 'asd', '2025-08-15 07:40:33', '2025-09-07 04:05:14'),
(14, '123', 'North-South Express', 'Metro', '12:17:00', '00:17:00', 'Daily', 'Active', '2025-09-08', '2025-09-13', 5, 'asd', '2025-09-06 04:17:54', '2025-09-08 03:31:09');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_id` int UNSIGNED NOT NULL,
  `service_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacity` int UNSIGNED NOT NULL DEFAULT '0',
  `current_passengers` int UNSIGNED NOT NULL DEFAULT '0',
  `status` enum('active','inactive','pending','maintenance','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `scheduled_start` datetime DEFAULT NULL,
  `scheduled_end` datetime DEFAULT NULL,
  `actual_start` datetime DEFAULT NULL,
  `actual_end` datetime DEFAULT NULL,
  `estimated_duration` int UNSIGNED DEFAULT NULL COMMENT 'in minutes',
  `base_fare` decimal(10,2) NOT NULL DEFAULT '0.00',
  `revenue` decimal(10,2) NOT NULL DEFAULT '0.00',
  `rating` decimal(3,2) DEFAULT NULL COMMENT 'average rating 1-5',
  `total_ratings` int UNSIGNED NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `service_data` json DEFAULT NULL COMMENT 'additional service specific data',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_code` (`service_code`),
  KEY `idx_provider_id` (`provider_id`),
  KEY `idx_status` (`status`),
  KEY `idx_service_type` (`service_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_history`
--

DROP TABLE IF EXISTS `service_history`;
CREATE TABLE IF NOT EXISTS `service_history` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_id` int UNSIGNED NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'started, completed, cancelled, etc',
  `previous_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `passenger_count` int UNSIGNED DEFAULT NULL,
  `revenue_amount` decimal(10,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `action_by_user_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `action_by_user_id` (`action_by_user_id`),
  KEY `idx_service_id` (`service_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_points`
--

DROP TABLE IF EXISTS `service_points`;
CREATE TABLE IF NOT EXISTS `service_points` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `services` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_points`
--

INSERT INTO `service_points` (`id`, `name`, `type`, `location`, `services`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(3, 'Central Hub Station', 'Transport Hub', 'City Center, Main Plaza', 'Bus Terminal, Metro Access, Ticketing', 'Active', 'Main transportation hub serving multiple routes', '2025-09-04 03:53:35', '2025-09-04 03:53:35'),
(4, 'North Terminal', 'Terminal', 'North District, Terminal Road', 'Bus Services, Parking, Waiting Area', 'Active', 'Primary terminal for northern routes', '2025-09-04 03:53:35', '2025-09-04 03:53:35'),
(5, 'Airport Transfer Point', 'Transfer Point', 'Airport Access Road', 'Airport Shuttle, Taxi Stand', 'Active', 'Connection point for airport services', '2025-09-04 03:53:35', '2025-09-04 03:53:35'),
(6, 'Business District Station', 'Station', 'Business District, Corporate Ave', 'Metro Platform, Express Services', 'Active', 'Serves business district with express connections', '2025-09-04 03:53:35', '2025-09-04 03:53:35'),
(7, 'University Hub', 'Station', 'University Campus, Education Blvd', 'Student Services, Multiple Bus Lines', 'Planned', 'New service point under construction', '2025-09-04 03:53:35', '2025-09-04 03:53:35'),
(8, 'Maintenance Depot', 'Depot', 'Industrial Area, Service Road', 'Vehicle Maintenance, Storage', 'Active', 'Central maintenance facility for all routes', '2025-09-04 03:53:35', '2025-09-04 03:53:35'),
(9, 'East Terminal', 'Terminal', 'East District, Harbor View', 'Ferry Connection, Bus Terminal', 'Maintenance', 'Currently undergoing renovation', '2025-09-04 03:53:35', '2025-09-04 03:53:35');

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

DROP TABLE IF EXISTS `shipments`;
CREATE TABLE IF NOT EXISTS `shipments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `waybill` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_id` int UNSIGNED DEFAULT NULL,
  `route_id` int UNSIGNED DEFAULT NULL,
  `origin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_lat` decimal(10,6) DEFAULT NULL,
  `current_lng` decimal(10,6) DEFAULT NULL,
  `status` enum('Created','In Transit','At Hub','Out for Delivery','Delivered','Delayed','Exception','Cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Created',
  `priority` enum('Standard','Express','Overnight') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Standard',
  `weight_kg` decimal(10,2) DEFAULT NULL,
  `volume_cbm` decimal(10,3) DEFAULT NULL,
  `pickup_date` datetime DEFAULT NULL,
  `eta` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `waybill` (`waybill`),
  KEY `idx_provider_id` (`provider_id`),
  KEY `idx_route_id` (`route_id`),
  KEY `idx_status` (`status`),
  KEY `idx_eta` (`eta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sops`
--

DROP TABLE IF EXISTS `sops`;
CREATE TABLE IF NOT EXISTS `sops` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `review_date` date NOT NULL,
  `purpose` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsibilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `procedures` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `equipment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `safety_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sops`
--

INSERT INTO `sops` (`id`, `title`, `category`, `department`, `version`, `status`, `review_date`, `purpose`, `scope`, `responsibilities`, `procedures`, `equipment`, `safety_notes`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'asd', 'Safety', 'Operations', '1.0', 'Approved', '2025-08-23', 'ad', 'adasdads', 'asdasd', 'asdasd', 'asdasd', 'asdasd', 'asdasda', '2025-08-15 07:32:06', '2025-09-03 00:46:32');

-- --------------------------------------------------------

--
-- Table structure for table `tariffs`
--

DROP TABLE IF EXISTS `tariffs`;
CREATE TABLE IF NOT EXISTS `tariffs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `per_km_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `per_hour_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `priority_multiplier` decimal(4,2) NOT NULL DEFAULT '1.00',
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Draft',
  `effective_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `source` enum('admin','user_submission') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `source_submission_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tariffs`
--

INSERT INTO `tariffs` (`id`, `name`, `category`, `base_rate`, `per_km_rate`, `per_hour_rate`, `priority_multiplier`, `status`, `effective_date`, `expiry_date`, `notes`, `source`, `source_submission_id`, `created_at`, `updated_at`) VALUES
(10, 'JNT', 'Transport', 199.00, 69.00, 120.00, 1.50, 'Active', '2025-09-06', '2026-09-06', 'Auto-created from approved user submission by jc', 'admin', NULL, '2025-09-06 04:47:02', '2025-09-06 04:47:02');

-- --------------------------------------------------------

--
-- Table structure for table `two_factor_auth`
--

DROP TABLE IF EXISTS `two_factor_auth`;
CREATE TABLE IF NOT EXISTS `two_factor_auth` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `secret_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `backup_codes` json DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `two_factor_auth`
--

INSERT INTO `two_factor_auth` (`id`, `user_id`, `secret_key`, `backup_codes`, `is_enabled`, `created_at`, `updated_at`) VALUES
(1, 1, 'GMZM6MUCYBCMERNBQBQ6DVJUSRWDPTOV', '[\"261968\", \"834101\", \"387541\", \"381460\", \"939217\", \"045932\", \"091571\", \"351879\"]', 0, '2025-08-15 13:07:14', '2025-08-15 13:07:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_area` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `experience` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `role` enum('admin','user','provider') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `email`, `name`, `profile_picture`, `phone`, `service_area`, `provider_type`, `experience`, `description`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$Ag.3ztDb5HbRgr1pG.9dV.FXVlgYKFySn8N/LjeC70z9gR0FPHziK', 'admin@slate.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin', 1, '2025-09-08 06:06:47', '2025-08-15 13:28:14', '2025-09-08 06:06:47'),
(23, 'Jnt', '$2y$10$MvooWS/uhl.mydUYP1yjHuoipkQYJVly/ZCgnRtnUhiwZfeESUzSK', 'jnt@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'provider', 1, '2025-09-08 06:08:45', '2025-09-03 02:31:45', '2025-09-08 06:08:45'),
(25, 'jc', '$2y$10$seGiCQupt1Z1eEG/Fslxu.mYbG0kionuzgad2hqU4CNtrtbfgxkJm', 'jc@gmail.com', NULL, 'uploads/profiles/profile_25_1757087191.png', NULL, NULL, NULL, NULL, NULL, 'user', 1, '2025-09-08 11:30:49', '2025-09-03 07:34:23', '2025-09-08 11:30:49'),
(26, 'bat', '$2y$10$9AbFMGAPVOgb7NbG.OYcdON.GcWMDs8o9F6zR6zmMnHNUus8lOXby', 'bathanronald19@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'user', 1, '2025-09-03 07:40:36', '2025-09-03 07:40:25', '2025-09-03 07:40:36'),
(27, 'pro', '$2y$10$z5JEnfzENPWOPt90NJzaP.bduOK2f/4YDxibyhozBCKSB0OWg8A.a', 'pro@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'user', 0, NULL, '2025-09-03 07:54:42', '2025-09-08 02:46:42'),
(29, 'slate', '$2y$10$Ds8X0oXoHOexqXdrqrbCr..xbo33dOnfDXWLpGTYvj1GjVQ.9SOqq', 'slate@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'user', 1, '2025-09-03 08:00:02', '2025-09-03 07:59:48', '2025-09-03 08:00:02'),
(30, 'Roro', '$2y$10$6k52WkMS/oR40xraTILjjePYMmdTYz6piSI7iULpM08.bdFbdtcHi', 'roro@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'provider', 1, '2025-09-03 08:01:20', '2025-09-03 08:01:07', '2025-09-03 08:01:20'),
(32, 'taena', '$2y$10$sRrvi8YiFnao/FeAZpkFhOBVK2kY5KhHY5lsoEDIeQJk4iPiD.60e', 'taena@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'user', 0, NULL, '2025-09-08 06:07:15', '2025-09-08 06:07:33');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
CREATE TABLE IF NOT EXISTS `user_profiles` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `first_name`, `last_name`, `phone`, `company`, `created_at`, `updated_at`) VALUES
(1, 29, 'slate', 'core', '0923121423', 'core2', '2025-09-03 07:59:48', '2025-09-03 07:59:48');

-- --------------------------------------------------------

--
-- Table structure for table `user_requests`
--

DROP TABLE IF EXISTS `user_requests`;
CREATE TABLE IF NOT EXISTS `user_requests` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `service_type` enum('freight','express','warehouse','logistics') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cargo_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL COMMENT 'in kg',
  `volume` decimal(10,2) DEFAULT NULL COMMENT 'in cubic meters',
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `final_cost` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','processing','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `priority` enum('normal','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `provider_id` int UNSIGNED DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` text COLLATE utf8mb4_unicode_ci,
  `tracking_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimated_duration` int UNSIGNED DEFAULT NULL COMMENT 'in minutes',
  `actual_duration` int UNSIGNED DEFAULT NULL COMMENT 'in minutes',
  `pickup_address` text COLLATE utf8mb4_unicode_ci,
  `delivery_address` text COLLATE utf8mb4_unicode_ci,
  `special_instructions` text COLLATE utf8mb4_unicode_ci,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL COMMENT '1-5 rating',
  `review_text` text COLLATE utf8mb4_unicode_ci,
  `review_date` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_id` (`request_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_provider_id` (`provider_id`),
  KEY `idx_status` (`status`),
  KEY `idx_service_type` (`service_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_tariff_submissions`
--

DROP TABLE IF EXISTS `user_tariff_submissions`;
CREATE TABLE IF NOT EXISTS `user_tariff_submissions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `per_km_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `per_hour_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `priority_multiplier` decimal(4,2) NOT NULL DEFAULT '1.00',
  `service_area` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `justification` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending Review',
  `submitted_by_user_id` int UNSIGNED NOT NULL,
  `submitted_by_username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reviewed_by_user_id` int UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `submitted_by_user_id` (`submitted_by_user_id`),
  KEY `reviewed_by_user_id` (`reviewed_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_tariff_submissions`
--

INSERT INTO `user_tariff_submissions` (`id`, `name`, `category`, `base_rate`, `per_km_rate`, `per_hour_rate`, `priority_multiplier`, `service_area`, `justification`, `notes`, `status`, `submitted_by_user_id`, `submitted_by_username`, `reviewed_by_user_id`, `reviewed_at`, `review_notes`, `created_at`, `updated_at`) VALUES
(1, 'JNT', 'Transport', 199.00, 69.00, 120.00, 1.50, 'Manila', 'adasdads', 'adaddsasd', 'Approved', 25, 'jc', 1, '2025-09-06 04:47:02', 'Approved by administrator', '2025-09-06 04:01:44', '2025-09-06 04:47:02'),
(5, 'LBC', 'Technology', 299.00, 55.00, 120.00, 1.00, 'Bulacan', 'taena', 'asd\n', 'Rejected', 25, 'jc', 1, '2025-09-06 04:46:43', 'asdsad', '2025-09-06 04:15:02', '2025-09-06 04:46:43');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `vehicle_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_id` int UNSIGNED NOT NULL,
  `vehicle_type` enum('truck','motor','boat','ship','bus','van','motorcycle') COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` year NOT NULL,
  `license_plate` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vin_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `engine_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fuel_type` enum('petrol','diesel','electric','hybrid','cng','lpg') COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacity_weight` decimal(10,2) DEFAULT NULL COMMENT 'in kg',
  `capacity_volume` decimal(10,2) DEFAULT NULL COMMENT 'in cubic meters',
  `capacity_passengers` int UNSIGNED DEFAULT NULL,
  `color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `registration_expiry` date DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL,
  `next_maintenance` date DEFAULT NULL,
  `gps_device_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','maintenance','retired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `current_location_lat` decimal(10,8) DEFAULT NULL,
  `current_location_lng` decimal(11,8) DEFAULT NULL,
  `last_location_update` timestamp NULL DEFAULT NULL,
  `odometer_reading` decimal(10,1) DEFAULT NULL COMMENT 'in km',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_code` (`vehicle_code`),
  UNIQUE KEY `license_plate` (`license_plate`),
  KEY `idx_provider_id` (`provider_id`),
  KEY `idx_vehicle_type` (`vehicle_type`),
  KEY `idx_status` (`status`),
  KEY `idx_license_plate` (`license_plate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_maintenance`
--

DROP TABLE IF EXISTS `vehicle_maintenance`;
CREATE TABLE IF NOT EXISTS `vehicle_maintenance` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `vehicle_id` int UNSIGNED NOT NULL,
  `maintenance_type` enum('routine','repair','inspection','emergency') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  `service_provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `odometer_reading` decimal(10,1) DEFAULT NULL COMMENT 'km at time of maintenance',
  `status` enum('scheduled','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `next_maintenance_km` decimal(10,1) DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vehicle_id` (`vehicle_id`),
  KEY `idx_scheduled_date` (`scheduled_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cargo`
--
ALTER TABLE `cargo`
  ADD CONSTRAINT `cargo_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `drivers`
--
ALTER TABLE `drivers`
  ADD CONSTRAINT `drivers_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `drivers_ibfk_2` FOREIGN KEY (`assigned_vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `request_status_history`
--
ALTER TABLE `request_status_history`
  ADD CONSTRAINT `request_status_history_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `user_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `request_status_history_ibfk_2` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_history`
--
ALTER TABLE `service_history`
  ADD CONSTRAINT `service_history_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_history_ibfk_2` FOREIGN KEY (`action_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `two_factor_auth`
--
ALTER TABLE `two_factor_auth`
  ADD CONSTRAINT `two_factor_auth_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_requests`
--
ALTER TABLE `user_requests`
  ADD CONSTRAINT `user_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_requests_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_tariff_submissions`
--
ALTER TABLE `user_tariff_submissions`
  ADD CONSTRAINT `user_tariff_submissions_ibfk_1` FOREIGN KEY (`submitted_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_tariff_submissions_ibfk_2` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicle_maintenance`
--
ALTER TABLE `vehicle_maintenance`
  ADD CONSTRAINT `vehicle_maintenance_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- Schema alignment with runtime definitions in db.php
-- Add missing columns/indexes/constraints in a non-destructive way

-- Ensure extended columns exist on services as used by application
ALTER TABLE `services`
  ADD COLUMN IF NOT EXISTS `transport_mode` enum('truck','motor','boat','ship','bus','van','motorcycle') COLLATE utf8mb4_unicode_ci NOT NULL AFTER `service_type`,
  ADD COLUMN IF NOT EXISTS `vehicle_id` int UNSIGNED DEFAULT NULL AFTER `provider_id`,
  ADD COLUMN IF NOT EXISTS `driver_id` int UNSIGNED DEFAULT NULL AFTER `vehicle_id`,
  ADD COLUMN IF NOT EXISTS `origin_lat` decimal(10,8) DEFAULT NULL AFTER `origin`,
  ADD COLUMN IF NOT EXISTS `origin_lng` decimal(11,8) DEFAULT NULL AFTER `origin_lat`,
  ADD COLUMN IF NOT EXISTS `destination_lat` decimal(10,8) DEFAULT NULL AFTER `destination`,
  ADD COLUMN IF NOT EXISTS `destination_lng` decimal(11,8) DEFAULT NULL AFTER `destination_lat`,
  ADD COLUMN IF NOT EXISTS `distance_km` decimal(10,2) DEFAULT NULL AFTER `destination_lng`,
  ADD COLUMN IF NOT EXISTS `capacity_weight` decimal(10,2) DEFAULT NULL COMMENT 'in kg' AFTER `capacity`,
  ADD COLUMN IF NOT EXISTS `capacity_volume` decimal(10,2) DEFAULT NULL COMMENT 'in cubic meters' AFTER `capacity_weight`,
  ADD COLUMN IF NOT EXISTS `capacity_passengers` int UNSIGNED DEFAULT NULL AFTER `capacity_volume`,
  ADD COLUMN IF NOT EXISTS `current_load_weight` decimal(10,2) NOT NULL DEFAULT '0.00' AFTER `capacity_passengers`,
  ADD COLUMN IF NOT EXISTS `per_km_rate` decimal(10,2) NOT NULL DEFAULT '0.00' AFTER `base_fare`,
  ADD COLUMN IF NOT EXISTS `per_weight_rate` decimal(10,2) NOT NULL DEFAULT '0.00' AFTER `per_km_rate`,
  ADD COLUMN IF NOT EXISTS `fuel_surcharge` decimal(10,2) NOT NULL DEFAULT '0.00' AFTER `per_weight_rate`,
  ADD COLUMN IF NOT EXISTS `total_fare` decimal(10,2) NOT NULL DEFAULT '0.00' AFTER `fuel_surcharge`,
  ADD COLUMN IF NOT EXISTS `fuel_consumed` decimal(8,2) DEFAULT NULL COMMENT 'liters' AFTER `revenue`,
  ADD COLUMN IF NOT EXISTS `current_location_lat` decimal(10,8) DEFAULT NULL AFTER `fuel_consumed`,
  ADD COLUMN IF NOT EXISTS `current_location_lng` decimal(11,8) DEFAULT NULL AFTER `current_location_lat`,
  ADD COLUMN IF NOT EXISTS `last_location_update` timestamp NULL DEFAULT NULL AFTER `current_location_lng`;

-- Indexes for new services columns
ALTER TABLE `services`
  ADD INDEX IF NOT EXISTS `idx_vehicle_id` (`vehicle_id`),
  ADD INDEX IF NOT EXISTS `idx_driver_id` (`driver_id`),
  ADD INDEX IF NOT EXISTS `idx_transport_mode` (`transport_mode`),
  ADD INDEX IF NOT EXISTS `idx_scheduled_start` (`scheduled_start`);

-- Foreign keys for services.vehicle_id and services.driver_id
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `services_ibfk_3` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL;

-- Ensure service_history has extended telemetry fields
ALTER TABLE `service_history`
  ADD COLUMN IF NOT EXISTS `cargo_weight` decimal(10,2) DEFAULT NULL AFTER `passenger_count`,
  ADD COLUMN IF NOT EXISTS `location_lat` decimal(10,8) DEFAULT NULL AFTER `revenue_amount`,
  ADD COLUMN IF NOT EXISTS `location_lng` decimal(11,8) DEFAULT NULL AFTER `location_lat`;

-- Ensure cargo has index on tracking_number
ALTER TABLE `cargo`
  ADD INDEX IF NOT EXISTS `idx_tracking_number` (`tracking_number`);

-- Ensure tariffs has extended source columns
ALTER TABLE `tariffs`
  ADD COLUMN IF NOT EXISTS `source` enum('admin','user_submission') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin' AFTER `notes`,
  ADD COLUMN IF NOT EXISTS `source_submission_id` int UNSIGNED DEFAULT NULL AFTER `source`;

-- Ensure users table includes extended profile fields
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `name`,
  ADD COLUMN IF NOT EXISTS `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `profile_picture`,
  ADD COLUMN IF NOT EXISTS `service_area` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `provider_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `service_area`,
  ADD COLUMN IF NOT EXISTS `experience` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `provider_type`,
  ADD COLUMN IF NOT EXISTS `description` text COLLATE utf8mb4_unicode_ci AFTER `experience`;

-- Seed default admin user if missing
INSERT IGNORE INTO `users` (`username`,`password_hash`,`email`,`role`) VALUES
('admin', '$2y$10$Ag.3ztDb5HbRgr1pG.9dV.FXVlgYKFySn8N/LjeC70z9gR0FPHziK', 'admin@slate.com', 'admin');

-- --------------------------------------------------------
-- Merged migrations from database-migration.sql

-- Ensure users.role includes 'provider'
ALTER TABLE `users` MODIFY COLUMN `role` enum('admin','user','provider') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user';

-- Ensure users.updated_at exists
ALTER TABLE `users` 
  ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Indexes on users table
ALTER TABLE `users` 
  ADD INDEX IF NOT EXISTS `idx_role` (`role`),
  ADD INDEX IF NOT EXISTS `idx_is_active` (`is_active`),
  ADD INDEX IF NOT EXISTS `idx_last_login` (`last_login`);

-- --------------------------------------------------------
-- Merged provider dashboard migrations (provider-dashboard-migration.sql)

-- Seed a test provider user if missing
INSERT IGNORE INTO `users` (`username`, `password_hash`, `email`, `role`) VALUES
('testprovider', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'provider@test.com', 'provider');

-- Reference the provider user id
SET @provider_user_id = (SELECT id FROM users WHERE username = 'testprovider' LIMIT 1);

-- Add provider_id to core tables used by provider dashboard
ALTER TABLE `routes` 
  ADD COLUMN IF NOT EXISTS `provider_id` INT UNSIGNED NULL,
  ADD CONSTRAINT IF NOT EXISTS `fk_routes_provider` FOREIGN KEY (`provider_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

ALTER TABLE `schedules` 
  ADD COLUMN IF NOT EXISTS `provider_id` INT UNSIGNED NULL,
  ADD CONSTRAINT IF NOT EXISTS `fk_schedules_provider` FOREIGN KEY (`provider_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

ALTER TABLE `service_points` 
  ADD COLUMN IF NOT EXISTS `provider_id` INT UNSIGNED NULL,
  ADD CONSTRAINT IF NOT EXISTS `fk_service_points_provider` FOREIGN KEY (`provider_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- Assign some existing seed data to the test provider if unassigned
UPDATE `routes` SET `provider_id` = @provider_user_id WHERE `provider_id` IS NULL AND `id` <= 3;
UPDATE `schedules` SET `provider_id` = @provider_user_id WHERE `provider_id` IS NULL AND `id` <= 3;
UPDATE `service_points` SET `provider_id` = @provider_user_id WHERE `provider_id` IS NULL AND `id` <= 3;

-- Additional sample provider data
INSERT IGNORE INTO `routes` (`name`, `type`, `start_point`, `end_point`, `distance`, `frequency`, `status`, `estimated_time`, `provider_id`, `notes`) VALUES 
('Provider Route A', 'Primary', 'Downtown', 'Airport', 15.5, 'Every 20 min', 'Active', 35, @provider_user_id, 'Main route for provider'),
('Provider Route B', 'Secondary', 'Mall', 'University', 8.2, 'Every 45 min', 'Active', 25, @provider_user_id, 'Secondary route for provider'),
('Provider Route C', 'Express', 'Business District', 'Residential Area', 12.1, 'Every hour', 'Planned', 30, @provider_user_id, 'Planned express route');

INSERT IGNORE INTO `schedules` (`name`, `route`, `vehicle_type`, `departure`, `arrival`, `frequency`, `status`, `start_date`, `end_date`, `capacity`, `provider_id`, `notes`) VALUES 
('Morning Express', 'Provider Route A', 'Bus', '06:00:00', '06:35:00', 'Daily', 'Active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 45, @provider_user_id, 'Morning commuter service'),
('Evening Return', 'Provider Route A', 'Bus', '17:30:00', '18:05:00', 'Daily', 'Active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 45, @provider_user_id, 'Evening return service'),
('Weekend Special', 'Provider Route B', 'Minivan', '09:00:00', '09:25:00', 'Weekends', 'Active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 MONTH), 25, @provider_user_id, 'Weekend shopping service');

INSERT IGNORE INTO `service_points` (`name`, `type`, `location`, `services`, `status`, `provider_id`, `notes`) VALUES 
('Provider Hub A', 'Station', 'Downtown Terminal, Main Street', 'Ticketing, Information, Parking', 'Active', @provider_user_id, 'Main service hub for provider'),
('Provider Hub B', 'Stop', 'Mall Entrance, Shopping District', 'Basic Services, Shelter', 'Active', @provider_user_id, 'Shopping district service point'),
('Provider Depot', 'Depot', 'Industrial Park, Service Road', 'Maintenance, Storage, Dispatch', 'Active', @provider_user_id, 'Provider maintenance facility');

-- Ensure a provider record exists and is aligned
INSERT IGNORE INTO `providers` (`name`, `type`, `contact_person`, `contact_email`, `contact_phone`, `service_area`, `monthly_rate`, `status`, `contract_start`, `contract_end`, `notes`) VALUES 
('Test Transport Provider', 'Transport', 'John Doe', 'provider@test.com', '555-0123', 'Metro Area', 25000.00, 'Active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'Test provider for dashboard');
UPDATE `providers` SET `contact_email` = 'provider@test.com' WHERE `id` = 1;

-- --------------------------------------------------------
-- Merged real-time tracking schema (create-tracking-tables.sql)

-- Route polyline coordinates
CREATE TABLE IF NOT EXISTS `route_coordinates` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `route_id` int(11) NOT NULL,
  `coordinates` text NOT NULL COMMENT 'JSON array of lat/lng coordinates',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`route_id`) REFERENCES `routes`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_route` (`route_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Service point coordinates
CREATE TABLE IF NOT EXISTS `service_point_coordinates` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `service_point_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`service_point_id`) REFERENCES `service_points`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_service_point` (`service_point_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alerts and incidents
CREATE TABLE IF NOT EXISTS `alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `type` enum('traffic', 'maintenance', 'incident', 'weather', 'emergency') NOT NULL,
  `severity` enum('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('active', 'resolved', 'dismissed') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Real-time vehicle tracking (aligned to vehicles.id INT UNSIGNED)
CREATE TABLE IF NOT EXISTS `vehicle_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` int UNSIGNED NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `status` enum('Active', 'Inactive', 'Maintenance', 'Emergency') NOT NULL DEFAULT 'Active',
  `speed` decimal(5,2) DEFAULT 0,
  `heading` int(11) DEFAULT 0,
  `fuel_level` decimal(5,2) DEFAULT 0,
  `passengers` int(11) DEFAULT 0,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_vehicle` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Performance indexes for tracking
CREATE INDEX IF NOT EXISTS `idx_vehicle_tracking_updated` ON `vehicle_tracking` (`updated_at`);
CREATE INDEX IF NOT EXISTS `idx_alerts_status_created` ON `alerts` (`status`, `created_at`);

-- Seed derived coordinates for existing service points
INSERT IGNORE INTO `service_point_coordinates` (`service_point_id`, `latitude`, `longitude`)
SELECT sp.id, 
  CASE 
    WHEN sp.location LIKE '%Manila%' THEN 14.5995
    WHEN sp.location LIKE '%Quezon%' THEN 14.6378
    WHEN sp.location LIKE '%Makati%' THEN 14.5547
    WHEN sp.location LIKE '%BGC%' THEN 14.5465
    WHEN sp.location LIKE '%Pasig%' THEN 14.5764
    WHEN sp.location LIKE '%Ortigas%' THEN 14.5866
    ELSE 14.5995
  END as latitude,
  CASE 
    WHEN sp.location LIKE '%Manila%' THEN 120.9842
    WHEN sp.location LIKE '%Quezon%' THEN 121.0342
    WHEN sp.location LIKE '%Makati%' THEN 121.0244
    WHEN sp.location LIKE '%BGC%' THEN 121.0511
    WHEN sp.location LIKE '%Pasig%' THEN 121.0851
    WHEN sp.location LIKE '%Ortigas%' THEN 121.0576
    ELSE 120.9842
  END as longitude
FROM service_points sp
WHERE sp.id NOT IN (SELECT service_point_id FROM service_point_coordinates);

-- Seed simple route polylines for existing routes
INSERT IGNORE INTO `route_coordinates` (`route_id`, `coordinates`)
SELECT r.id,
  CASE 
    WHEN r.name LIKE '%Manila%' AND r.name LIKE '%Quezon%' THEN 
      '[[14.5995,120.9842],[14.6042,120.9917],[14.6158,121.0117],[14.6378,121.0342]]'
    WHEN r.name LIKE '%Makati%' AND r.name LIKE '%BGC%' THEN 
      '[[14.5547,121.0244],[14.5578,121.0317],[14.5515,121.0453],[14.5465,121.0511]]'
    ELSE 
      '[[14.5995,120.9842],[14.6042,120.9917],[14.6158,121.0117],[14.6378,121.0342]]'
  END as coordinates
FROM routes r
WHERE r.id NOT IN (SELECT route_id FROM route_coordinates);

-- --------------------------------------------------------
-- Merged sample dashboard data (sample-dashboard-data.sql)

INSERT IGNORE INTO `routes` (`name`, `type`, `start_point`, `end_point`, `distance`, `frequency`, `status`, `estimated_time`, `notes`) VALUES
('Metro Line A', 'Primary', 'North Terminal', 'South Terminal', 25.5, 'Every 15 min', 'Active', 45, 'Main metro line connecting north and south districts'),
('Bus Route 101', 'Secondary', 'City Center', 'Airport', 18.2, 'Every 30 min', 'Active', 35, 'Direct bus service to airport'),
('Express Route X1', 'Express', 'Downtown', 'Business District', 12.8, 'Every hour', 'Active', 20, 'Express service for business commuters'),
('Local Route L5', 'Local', 'Residential Area A', 'Shopping Mall', 8.4, 'Every 2 hours', 'Active', 25, 'Local service for residential areas'),
('Metro Line B', 'Primary', 'East Terminal', 'West Terminal', 22.1, 'Every 15 min', 'Maintenance', 40, 'Secondary metro line under maintenance'),
('Bus Route 202', 'Secondary', 'University', 'City Center', 15.6, 'Every 30 min', 'Planned', 30, 'New route serving university area');

INSERT IGNORE INTO `service_points` (`name`, `type`, `location`, `services`, `status`, `notes`) VALUES
('Central Hub Station', 'Transport Hub', 'City Center, Main Plaza', 'Bus Terminal, Metro Access, Ticketing', 'Active', 'Main transportation hub serving multiple routes'),
('North Terminal', 'Terminal', 'North District, Terminal Road', 'Bus Services, Parking, Waiting Area', 'Active', 'Primary terminal for northern routes'),
('Airport Transfer Point', 'Transfer Point', 'Airport Access Road', 'Airport Shuttle, Taxi Stand', 'Active', 'Connection point for airport services'),
('Business District Station', 'Station', 'Business District, Corporate Ave', 'Metro Platform, Express Services', 'Active', 'Serves business district with express connections'),
('University Hub', 'Station', 'University Campus, Education Blvd', 'Student Services, Multiple Bus Lines', 'Planned', 'New service point under construction'),
('Maintenance Depot', 'Depot', 'Industrial Area, Service Road', 'Vehicle Maintenance, Storage', 'Active', 'Central maintenance facility for all routes'),
('East Terminal', 'Terminal', 'East District, Harbor View', 'Ferry Connection, Bus Terminal', 'Maintenance', 'Currently undergoing renovation'),
('Shopping Mall Stop', 'Station', 'Commercial District, Mall Entrance', 'Shopping Access, Local Routes', 'Active', 'Popular stop serving major shopping center');

-- Migration footer
SELECT 'Migration completed successfully!' as status;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
