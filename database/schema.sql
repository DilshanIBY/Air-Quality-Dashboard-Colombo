-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2025 at 07:30 PM
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
-- Database: `air_quality_monitoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `alert_thresholds`
--

CREATE TABLE `alert_thresholds` (
  `id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `min_value` decimal(5,2) NOT NULL,
  `max_value` decimal(5,2) NOT NULL,
  `color` varchar(7) NOT NULL,
  `description` text NOT NULL,
  `alert_message` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alert_thresholds`
--

INSERT INTO `alert_thresholds` (`id`, `category`, `min_value`, `max_value`, `color`, `description`, `alert_message`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Good', 0.00, 50.00, '#66BB6A', 'Air quality is satisfactory, and air pollution poses little or no risk.', 'Air quality is good and healthy for outdoor activities.', 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(2, 'Moderate', 51.00, 100.00, '#FDD835', 'Air quality is acceptable. However, there may be a risk for some people, particularly those who are unusually sensitive to air pollution.', 'Moderate air quality. Sensitive individuals should consider limiting prolonged outdoor exposure.', 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(3, 'Unhealthy for Sensitive Groups', 101.00, 150.00, '#FB8C00', 'Members of sensitive groups may experience health effects. The general public is less likely to be affected.', 'Air quality is unhealthy for sensitive groups. Take precautions if you have respiratory issues.', 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(4, 'Unhealthy', 151.00, 200.00, '#E53935', 'Some members of the general public may experience health effects; members of sensitive groups may experience more serious health effects.', 'Unhealthy air quality. Avoid prolonged outdoor activities.', 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(5, 'Very Unhealthy', 201.00, 300.00, '#8E24AA', 'Health alert: The risk of health effects is increased for everyone.', 'Very unhealthy air quality. Stay indoors and keep windows closed.', 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(6, 'Hazardous', 301.00, 500.00, '#B71C1C', 'Health warning of emergency conditions: everyone is more likely to be affected.', 'HAZARDOUS air quality! Avoid all outdoor activities.', 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01');

-- --------------------------------------------------------

--
-- Table structure for table `aqi_readings`
--

CREATE TABLE `aqi_readings` (
  `id` int(11) NOT NULL,
  `sensor_id` varchar(50) DEFAULT NULL,
  `aqi_value` decimal(5,2) NOT NULL,
  `pm25_value` decimal(5,2) NOT NULL,
  `pm10_value` decimal(5,2) NOT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `humidity` decimal(4,1) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_simulated` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `aqi_readings`
--

INSERT INTO `aqi_readings` (`id`, `sensor_id`, `aqi_value`, `pm25_value`, `pm10_value`, `temperature`, `humidity`, `timestamp`, `is_simulated`) VALUES
(1, 'SEN001', 47.54, 38.03, 57.05, 24.1, 70.2, '2025-04-12 17:05:44', 1),
(2, 'SEN002', 59.14, 47.31, 70.97, 25.6, 72.2, '2025-04-12 17:05:44', 1),
(3, 'SEN003', 49.81, 39.85, 59.77, 34.1, 37.9, '2025-04-12 17:05:44', 1),
(4, 'SEN004', 34.61, 27.69, 41.53, 24.0, 61.0, '2025-04-12 17:05:44', 1),
(5, 'SEN005', 64.03, 51.22, 76.84, 30.9, 38.5, '2025-04-12 17:05:44', 1),
(6, 'SEN006', 69.63, 55.70, 83.56, 33.3, 71.8, '2025-04-12 17:05:44', 1),
(7, 'SEN007', 97.54, 78.03, 117.05, 23.1, 30.6, '2025-04-12 17:05:44', 1),
(8, 'SEN008', 53.90, 43.12, 64.68, 22.2, 45.1, '2025-04-12 17:05:44', 1),
(9, 'SEN009', 66.75, 53.40, 80.10, 21.7, 61.2, '2025-04-12 17:05:44', 1),
(10, 'SEN010', 82.83, 66.26, 99.40, 21.4, 78.6, '2025-04-12 17:05:44', 1),
(11, 'SEN001', 50.38, 40.30, 60.46, 22.3, 34.4, '2025-04-12 17:10:01', 1),
(12, 'SEN002', 67.94, 54.35, 81.53, 30.5, 74.7, '2025-04-12 17:10:01', 1),
(13, 'SEN003', 52.47, 41.98, 62.96, 26.4, 64.5, '2025-04-12 17:10:01', 1),
(14, 'SEN004', 40.90, 32.72, 49.08, 27.3, 32.6, '2025-04-12 17:10:01', 1),
(15, 'SEN005', 69.97, 55.98, 83.96, 33.2, 55.9, '2025-04-12 17:10:01', 1),
(16, 'SEN006', 90.18, 72.14, 108.22, 33.5, 38.2, '2025-04-12 17:10:01', 1),
(17, 'SEN007', 105.59, 84.47, 126.71, 29.8, 66.1, '2025-04-12 17:10:01', 1),
(18, 'SEN008', 54.33, 43.46, 65.20, 30.7, 50.0, '2025-04-12 17:10:01', 1),
(19, 'SEN009', 52.19, 41.75, 62.63, 20.9, 40.4, '2025-04-12 17:10:01', 1),
(20, 'SEN010', 51.12, 40.90, 61.34, 34.8, 55.0, '2025-04-12 17:10:01', 1),
(21, 'SEN001', 51.09, 40.87, 61.31, 27.9, 40.8, '2025-04-12 17:11:51', 1),
(22, 'SEN002', 49.34, 39.47, 59.21, 33.6, 45.1, '2025-04-12 17:11:51', 1),
(23, 'SEN003', 46.04, 36.83, 55.25, 34.2, 69.6, '2025-04-12 17:11:51', 1),
(24, 'SEN004', 42.93, 34.34, 51.52, 25.2, 65.8, '2025-04-12 17:11:51', 1),
(25, 'SEN005', 84.61, 67.69, 101.53, 21.9, 43.0, '2025-04-12 17:11:51', 1),
(26, 'SEN006', 107.49, 85.99, 128.99, 25.2, 48.7, '2025-04-12 17:11:51', 1),
(27, 'SEN007', 104.05, 83.24, 124.86, 24.2, 58.6, '2025-04-12 17:11:51', 1),
(28, 'SEN008', 30.66, 24.53, 36.79, 20.0, 53.5, '2025-04-12 17:11:51', 1),
(29, 'SEN009', 59.61, 47.69, 71.53, 30.4, 37.7, '2025-04-12 17:11:51', 1),
(30, 'SEN010', 71.70, 57.36, 86.04, 32.3, 33.1, '2025-04-12 17:11:51', 1),
(31, 'SEN001', 52.46, 41.97, 62.95, 34.7, 47.5, '2025-04-12 17:13:21', 1),
(32, 'SEN002', 75.93, 60.74, 91.12, 24.5, 66.0, '2025-04-12 17:13:21', 1),
(33, 'SEN003', 70.86, 56.69, 85.03, 29.1, 43.9, '2025-04-12 17:13:21', 1),
(34, 'SEN004', 30.67, 24.54, 36.80, 30.1, 53.9, '2025-04-12 17:13:21', 1),
(35, 'SEN005', 96.35, 77.08, 115.62, 25.0, 72.8, '2025-04-12 17:13:21', 1),
(36, 'SEN006', 95.50, 76.40, 114.60, 25.8, 53.9, '2025-04-12 17:13:21', 1),
(37, 'SEN007', 88.95, 71.16, 106.74, 34.2, 66.8, '2025-04-12 17:13:21', 1),
(38, 'SEN008', 43.30, 34.64, 51.96, 33.9, 48.8, '2025-04-12 17:13:21', 1),
(39, 'SEN009', 77.93, 62.34, 93.52, 30.1, 40.3, '2025-04-12 17:13:21', 1),
(40, 'SEN010', 71.20, 56.96, 85.44, 21.5, 53.1, '2025-04-12 17:13:21', 1),
(41, 'SEN001', 37.09, 29.67, 44.51, 31.9, 50.3, '2025-04-12 17:18:37', 1),
(42, 'SEN002', 60.10, 48.08, 72.12, 34.5, 55.0, '2025-04-12 17:18:37', 1),
(43, 'SEN003', 45.64, 36.51, 54.77, 33.0, 72.4, '2025-04-12 17:18:37', 1),
(44, 'SEN004', 42.15, 33.72, 50.58, 29.7, 55.1, '2025-04-12 17:18:37', 1),
(45, 'SEN005', 63.24, 50.59, 75.89, 22.2, 41.2, '2025-04-12 17:18:37', 1),
(46, 'SEN006', 102.67, 82.14, 123.20, 29.0, 75.9, '2025-04-12 17:18:37', 1),
(47, 'SEN007', 112.06, 89.65, 134.47, 27.2, 65.1, '2025-04-12 17:18:37', 1),
(48, 'SEN008', 49.78, 39.82, 59.74, 30.8, 44.0, '2025-04-12 17:18:37', 1),
(49, 'SEN009', 52.92, 42.34, 63.50, 24.6, 72.4, '2025-04-12 17:18:37', 1),
(50, 'SEN010', 53.74, 42.99, 64.49, 23.1, 32.8, '2025-04-12 17:18:37', 1),
(51, 'SEN001', 53.82, 43.06, 64.58, 21.0, 33.8, '2025-04-23 16:11:00', 1),
(52, 'SEN002', 64.03, 51.22, 76.84, 27.5, 32.8, '2025-04-23 16:11:00', 1),
(53, 'SEN003', 69.00, 55.20, 82.80, 26.3, 60.5, '2025-04-23 16:11:00', 1),
(54, 'SEN004', 31.85, 25.48, 38.22, 20.3, 46.5, '2025-04-23 16:11:00', 1),
(55, 'SEN005', 89.86, 71.89, 107.83, 27.1, 64.9, '2025-04-23 16:11:00', 1),
(56, 'SEN006', 104.82, 83.86, 125.78, 20.6, 48.4, '2025-04-23 16:11:00', 1),
(57, 'SEN007', 79.15, 63.32, 94.98, 34.6, 66.8, '2025-04-23 16:11:00', 1),
(58, 'SEN008', 44.45, 35.56, 53.34, 25.3, 50.9, '2025-04-23 16:11:00', 1),
(59, 'SEN009', 57.02, 45.62, 68.42, 32.0, 61.6, '2025-04-23 16:11:00', 1),
(60, 'SEN010', 60.43, 48.34, 72.52, 28.4, 77.4, '2025-04-23 16:11:00', 1),
(61, 'SEN001', 31.43, 25.14, 37.72, 21.3, 53.0, '2025-04-23 16:11:20', 1),
(62, 'SEN002', 56.90, 45.52, 68.28, 26.6, 55.6, '2025-04-23 16:11:20', 1),
(63, 'SEN003', 56.52, 45.22, 67.82, 21.0, 79.3, '2025-04-23 16:11:20', 1),
(64, 'SEN004', 39.89, 31.91, 47.87, 24.3, 68.3, '2025-04-23 16:11:20', 1),
(65, 'SEN005', 54.40, 43.52, 65.28, 23.4, 35.9, '2025-04-23 16:11:20', 1),
(66, 'SEN006', 55.88, 44.70, 67.06, 23.2, 54.7, '2025-04-23 16:11:20', 1),
(67, 'SEN007', 77.12, 61.70, 92.54, 26.8, 41.5, '2025-04-23 16:11:20', 1),
(68, 'SEN008', 36.85, 29.48, 44.22, 28.6, 42.5, '2025-04-23 16:11:20', 1),
(69, 'SEN009', 55.01, 44.01, 66.01, 31.1, 34.5, '2025-04-23 16:11:20', 1),
(70, 'SEN010', 51.97, 41.58, 62.36, 21.9, 49.7, '2025-04-23 16:11:20', 1),
(71, 'SEN001', 37.43, 29.94, 44.92, 21.1, 64.9, '2025-04-23 16:15:51', 1),
(72, 'SEN002', 59.90, 47.92, 71.88, 20.9, 57.6, '2025-04-23 16:15:51', 1),
(73, 'SEN003', 39.14, 31.31, 46.97, 26.4, 42.8, '2025-04-23 16:15:51', 1),
(74, 'SEN004', 41.56, 33.25, 49.87, 31.1, 59.5, '2025-04-23 16:15:51', 1),
(75, 'SEN005', 59.73, 47.78, 71.68, 26.3, 73.9, '2025-04-23 16:15:51', 1),
(76, 'SEN006', 88.48, 70.78, 106.18, 23.4, 32.1, '2025-04-23 16:15:51', 1),
(77, 'SEN007', 115.82, 92.66, 138.98, 28.5, 50.8, '2025-04-23 16:15:51', 1),
(78, 'SEN008', 48.35, 38.68, 58.02, 23.6, 56.7, '2025-04-23 16:15:51', 1),
(79, 'SEN009', 43.66, 34.93, 52.39, 29.6, 42.3, '2025-04-23 16:15:51', 1),
(80, 'SEN010', 63.48, 50.78, 76.18, 29.6, 33.2, '2025-04-23 16:15:51', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sensors`
--

CREATE TABLE `sensors` (
  `id` int(11) NOT NULL,
  `sensor_id` varchar(50) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `installation_date` date NOT NULL,
  `last_maintenance` date DEFAULT NULL,
  `maintenance_due` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sensors`
--

INSERT INTO `sensors` (`id`, `sensor_id`, `location_name`, `latitude`, `longitude`, `status`, `installation_date`, `last_maintenance`, `maintenance_due`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'SEN001', 'Fort Railway Station', 6.93440000, 79.84280000, 'active', '2025-04-12', NULL, NULL, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(2, 'SEN002', 'Colombo Port City', 6.93710000, 79.84690000, 'active', '2025-04-12', NULL, NULL, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(3, 'SEN003', 'Galle Face Green', 6.92710000, 79.84250000, 'active', '2025-04-12', NULL, NULL, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(4, 'SEN004', 'Viharamahadevi Park', 6.91470000, 79.85830000, 'active', '2025-04-12', NULL, NULL, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(5, 'SEN005', 'Colombo National Hospital', 6.91560000, 79.86450000, 'active', '2025-04-12', NULL, NULL, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(6, 'SEN006', 'Maradana Railway Station', 6.92840000, 79.86410000, 'active', '2025-04-12', NULL, NULL, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(7, 'SEN007', 'Pettah Market', 6.93670000, 79.84970000, 'active', '2025-04-12', NULL, NULL, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(8, 'SEN008', 'Beira Lake', 6.92730000, 79.85370000, 'active', '2025-04-12', NULL, NULL, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(9, 'SEN009', 'Colombo Town Hall', 6.91720000, 79.85870000, 'active', '2025-04-12', NULL, NULL, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(10, 'SEN010', 'World Trade Center', 6.93500000, 79.84420000, 'active', '2025-04-12', NULL, NULL, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01');

-- --------------------------------------------------------

--
-- Table structure for table `simulation_settings`
--

CREATE TABLE `simulation_settings` (
  `id` int(11) NOT NULL,
  `sensor_id` varchar(50) NOT NULL,
  `base_aqi` decimal(5,2) NOT NULL,
  `variation_min` decimal(5,2) NOT NULL,
  `variation_max` decimal(5,2) NOT NULL,
  `update_frequency` int(11) NOT NULL COMMENT 'Update frequency in minutes',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `simulation_settings`
--

INSERT INTO `simulation_settings` (`id`, `sensor_id`, `base_aqi`, `variation_min`, `variation_max`, `update_frequency`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'SEN001', 45.00, -10.00, 15.00, 5, 1, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(2, 'SEN002', 62.00, -15.00, 20.00, 5, 1, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(3, 'SEN003', 55.00, -12.00, 18.00, 5, 1, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(4, 'SEN004', 38.00, -8.00, 12.00, 5, 1, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(5, 'SEN005', 72.00, -20.00, 25.00, 5, 1, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(6, 'SEN006', 85.00, -25.00, 30.00, 5, 1, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(7, 'SEN007', 95.00, -30.00, 35.00, 5, 1, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(8, 'SEN008', 43.00, -10.00, 15.00, 5, 1, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(9, 'SEN009', 58.00, -15.00, 20.00, 5, 1, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01'),
(10, 'SEN010', 67.00, -18.00, 22.00, 5, 1, 1, '2025-04-12 16:48:01', '2025-04-12 16:48:01');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `log_type` enum('info','warning','error','security') NOT NULL,
  `message` text NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `log_type`, `message`, `user_id`, `full_name`, `ip_address`, `created_at`) VALUES
(30, 'security', 'Successful login', 1, 'Ranuka Gayesh', '::175.157.117.108', NULL),
(31, 'security', 'User logged out', 1, 'Ranuka Gayesh', '::175.157.117.108', NULL),
(32, 'security', 'Successful login', 2, 'Dilshan Irugal', '::175.157.157.172', NULL),
(33, 'security', 'User logged out', 2, 'Dilshan Irugal', '::175.157.157.172', NULL),
(34, 'security', 'Successful login', 1, 'Ranuka Gayesh', '::175.157.117.108', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('monitoring_admin','system_admin') NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `email`, `full_name`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2a$12$MwWZVBJFwHDo9EVxxaAgoe3L4QkNDFw4NVbGRuY.JTtmDgNIRSfha', 'system_admin', 'admin@airquality.lk', 'Ranuka Gayesh', 'active', '2025-04-23 17:27:12', '2025-04-12 16:48:01', '2025-04-23 17:27:12'),
(2, 'manager', '$2y$10$s6g8tvsY38pfGnYsra/wROP602UKl365efIevDB0KmvUjbu.QGNHy', 'monitoring_admin', 'dilshaniru@gmail.com', 'Dilshan Irugal', 'active', '2025-04-23 17:26:58', '2025-04-23 16:44:05', '2025-04-23 17:26:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alert_thresholds`
--
ALTER TABLE `alert_thresholds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `aqi_readings`
--
ALTER TABLE `aqi_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sensor_id` (`sensor_id`);

--
-- Indexes for table `sensors`
--
ALTER TABLE `sensors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sensor_id` (`sensor_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `simulation_settings`
--
ALTER TABLE `simulation_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sensor_id` (`sensor_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alert_thresholds`
--
ALTER TABLE `alert_thresholds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `aqi_readings`
--
ALTER TABLE `aqi_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `sensors`
--
ALTER TABLE `sensors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `simulation_settings`
--
ALTER TABLE `simulation_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alert_thresholds`
--
ALTER TABLE `alert_thresholds`
  ADD CONSTRAINT `alert_thresholds_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `aqi_readings`
--
ALTER TABLE `aqi_readings`
  ADD CONSTRAINT `aqi_readings_ibfk_1` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`sensor_id`);

--
-- Constraints for table `sensors`
--
ALTER TABLE `sensors`
  ADD CONSTRAINT `sensors_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `simulation_settings`
--
ALTER TABLE `simulation_settings`
  ADD CONSTRAINT `simulation_settings_ibfk_1` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`sensor_id`),
  ADD CONSTRAINT `simulation_settings_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
