

create DATABASE planwise_db
use DATABASE planwise_db

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(2, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-03-02 16:52:47'),
(3, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-02 17:03:50'),
(4, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-02 17:03:52'),
(5, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-02 17:04:37'),
(6, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-02 17:04:39'),
(7, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-02 17:12:03'),
(8, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-02 17:12:08'),
(9, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-02 17:13:20'),
(10, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-02 17:13:22'),
(11, 6, 'pdf_exported', 'Exported lesson plan ID: 1001 to PDF', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-02 17:13:47'),
(12, 6, 'pdf_exported', 'Exported lesson plan ID: 1001 to PDF', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-02 17:13:48'),
(13, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '2026-03-02 21:15:25'),
(14, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 14:21:40'),
(15, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 14:21:53'),
(16, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 14:22:03'),
(17, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 14:22:08'),
(18, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 14:54:21'),
(19, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 14:54:34'),
(20, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-09 14:55:06'),
(21, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 15:15:40'),
(22, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 15:17:09'),
(23, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 15:17:14'),
(24, 6, 'lesson_plan_deleted', 'Deleted lesson plan ID: 1001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 15:17:20'),
(25, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 15:17:58'),
(26, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 15:18:10'),
(27, 6, 'pdf_exported', 'Exported lesson plan ID: 1000 to PDF', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 15:19:28'),
(28, 6, 'pdf_exported', 'Exported lesson plan ID: 1000 to PDF', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 15:19:32'),
(29, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 15:22:11'),
(30, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 15:22:15'),
(31, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 15:23:03'),
(32, 7, 'user_registered', 'New user registered: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 15:23:27'),
(33, 7, 'user_login', 'User logged in: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 15:23:38'),
(34, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-21 23:09:35'),
(35, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-21 23:09:57'),
(36, 7, 'user_login', 'User logged in: josiahsdigitalservices@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-21 23:10:01'),
(37, 7, 'user_logout', 'User logged out: josiahsdigitalservices@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-21 23:10:03'),
(38, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-21 23:10:05'),
(39, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-23 13:58:11'),
(40, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-23 13:58:24'),
(41, 7, 'user_login', 'User logged in: josiahsdigitalservices@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-23 13:58:27'),
(42, 7, 'lesson_plan_created', 'Created lesson plan: guh suck yuh ma', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-23 13:59:19'),
(43, 7, 'pdf_exported', 'Exported lesson plan ID: 1002 to PDF', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-23 13:59:41'),
(44, 7, 'pdf_exported', 'Exported lesson plan ID: 1002 to PDF', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-23 13:59:44'),
(45, 7, 'user_logout', 'User logged out: josiahsdigitalservices@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-23 14:01:58'),
(46, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-23 14:02:05'),
(47, 7, 'user_login', 'User logged in: josiahsdigitalservices@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-23 15:22:51'),
(48, 7, 'user_logout', 'User logged out: josiahsdigitalservices@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-23 15:25:18'),
(49, 7, 'user_login', 'User logged in: josiahsdigitalservices@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-23 15:25:20'),
(50, 7, 'user_login', 'User logged in: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 13:39:54'),
(51, 7, 'user_logout', 'User logged out: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 13:40:35'),
(52, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 13:40:39'),
(53, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 13:41:54'),
(54, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 13:41:59'),
(55, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 13:42:05'),
(56, 7, 'user_login', 'User logged in: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 13:42:08'),
(57, 7, 'user_logout', 'User logged out: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 13:43:40'),
(58, 7, 'user_login', 'User logged in: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 13:59:57'),
(59, 7, 'user_logout', 'User logged out: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 14:00:16'),
(60, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 14:00:19'),
(61, 6, 'activity_logs_cleaned', 'Cleaned up activity logs older than 90 days. Deleted 0 records.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 14:16:38'),
(62, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 14:17:03'),
(63, 7, 'user_login', 'User logged in: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 14:17:08'),
(64, 7, 'user_login', 'User logged in: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 15:08:57'),
(65, 7, 'user_logout', 'User logged out: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 15:17:51'),
(66, 7, 'user_login', 'User logged in: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 16:20:37'),
(67, 7, 'user_logout', 'User logged out: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 16:21:54'),
(68, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-28 16:21:59'),
(69, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-29 21:04:20'),
(70, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-29 21:21:14'),
(71, 7, 'user_login', 'User logged in: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-29 21:21:22'),
(72, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 13:36:43'),
(73, 6, 'user_logout', 'User logged out: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 13:36:58'),
(74, 7, 'user_login', 'User logged in: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 13:37:02'),
(75, 7, 'user_logout', 'User logged out: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 13:59:27'),
(76, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 13:59:32'),
(77, 7, 'user_login', 'User logged in: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 16:10:14'),
(78, 7, 'user_logout', 'User logged out: josiahsdigitalservices@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 16:10:19'),
(79, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 16:10:24'),
(80, 6, 'user_login', 'User logged in: josiah.johnson6550@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-13 00:54:30');

CREATE TABLE `files` (
  `file_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `lesson_plans` (
  `lesson_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `materials` text DEFAULT NULL,
  `procedures` text DEFAULT NULL,
  `assessment` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `grade` varchar(50) NOT NULL,
  `theme` varchar(150) DEFAULT NULL,
  `attainment_target` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `lesson_plans` (`lesson_id`, `user_id`, `title`, `subject`, `grade_level`, `duration`, `objectives`, `materials`, `procedures`, `assessment`, `notes`, `status`, `grade`, `theme`, `attainment_target`, `created_at`, `updated_at`) VALUES
(1, 6, 'Test Lesson Plan', 'Mathematics', 'Grade 10', 60, 'Learn basic algebra', 'Whiteboard, markers', 'Introduction, main activity, conclusion', 'Quiz', 'Test notes', 'published', '', NULL, NULL, '2026-01-23 03:10:13', '2026-01-24 00:43:12'),
(3, 6, 'Lesson1', 'Math', '10', 60, 'jjjjjjjjjjjjjj', 'jjjjjjjjjjjjjj', 'jjjjjjjjjjjjjj', 'jjjjjjjjjjjjjj', 'jjjjjjjjjjjjjj', 'draft', '', NULL, NULL, '2026-01-23 20:21:30', '2026-01-23 20:21:30'),
(4, 6, 'Lesson 2', 'Mathematics', '2', 130, 'smth', 'books and pens', 'idk', 'kkk', 'kkk', 'draft', '', NULL, NULL, '2026-01-24 16:43:30', '2026-01-24 16:43:30'),
(999, 6, 'Test Lesson for QR Code', 'Test Subject', 'Grade 10', 60, 'Test objectives', 'Test materials', 'Test procedures', 'Test assessment', 'Test notes', 'draft', '10', NULL, NULL, '2026-02-01 00:55:56', NULL),
(1000, 6, 'Javascript gone', 'j', '10', 99, '9', '9', '9', '9', '9', 'draft', '', NULL, NULL, '2026-02-05 02:21:54', '2026-02-05 02:21:54'),
(1002, 7, 'guh suck yuh ma', 'deeply', '10', 679, 'fi find di one piece', 'yuh ma', 'suck it deeeeeeeeeeeeep', 'if yuh ma seh suh', '', 'published', '', NULL, NULL, '2026-03-23 13:59:18', '2026-03-23 13:59:18');

CREATE TABLE `lesson_sections` (
  `section_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `section_type` varchar(50) NOT NULL,
  `content` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `password_resets` (`reset_id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 6, 'c46be4ea76b7edca96ded706917353c1a895f6b17e4ae2ab0b37222a8240c89d', '2026-02-28 21:17:26', '2026-02-28 19:47:26'),
(2, 6, '1143bc562cc0dee58196737dcd84139f9ddf505fee5f9effdf2ec657f99b5c41', '2026-02-28 21:21:41', '2026-02-28 19:51:41'),
(3, 6, '6ca187477bec3e9e878bdaa85e325a74828c776f1570bdd64fc8e4517f1aadd7', '2026-02-28 21:24:10', '2026-02-28 19:54:10');

CREATE TABLE `qr_codes` (
  `qr_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `qr_path` varchar(255) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `qr_codes` (`qr_id`, `lesson_id`, `qr_path`, `generated_at`) VALUES
(1, 3, 'C:\\xampp\\htdocs\\planwise\\classes/../public/qr/qr_3_1769216293.png', '2026-01-24 00:58:13'),
(4, 1, 'C:\\xampp\\htdocs\\planwise\\classes/../public/qr/qr_1_1770258911.png', '2026-02-05 02:35:11'),
(5, 4, 'C:\\xampp\\htdocs\\planwise\\classes/../public/qr/qr_4_1769273010.png', '2026-01-24 16:43:32'),
(19, 999, 'C:\\xampp\\htdocs\\planwise\\classes/../public/qr/qr_999_1770258685.png', '2026-02-05 02:31:25'),
(20, 1000, 'C:\\xampp\\htdocs\\planwise\\classes/../public/qr/qr_1000_1770258114.png', '2026-02-05 02:21:56'),
(22, 1002, 'C:\\xampp\\htdocs\\planwise\\classes/../public/qr/qr_1002_1774274358.png', '2026-03-23 13:59:19');

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'Admin'),
(2, 'Teacher');

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL,
  `profile_thumbnail` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password_hash`, `role_id`, `status`, `profile_picture`, `profile_thumbnail`, `created_at`) VALUES
(6, 'Jamin', 'Johnson', 'josiah.johnson6550@gmail.com', '$2y$10$iZYvlSig9fVl1N2FU.KFMu7SRZTY8HyI66fzdv1zBTpOKEABCVaUi', 1, 'active', NULL, NULL, '2026-01-22 16:37:22'),
(7, 'Jamin', 'Johnson', 'josiahsdigitalservices@gmail.com', '$2y$10$5wjdPnxw7erOk0ls.bGlsuCtAP2JEOIShRckMg9SGJgbc/tGk5jwa', 2, 'active', 'uploads/avatars/user_7_1774710546.jpg', 'uploads/thumbnails/thumb_user_7_1774710546.jpg', '2026-03-16 15:23:25');
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_log_user` (`user_id`),
  ADD KEY `idx_activity_logs_user_id` (`user_id`),
  ADD KEY `idx_activity_logs_action` (`action`),
  ADD KEY `idx_activity_logs_created_at` (`created_at`);
ALTER TABLE `files`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `idx_file_lesson` (`lesson_id`);
ALTER TABLE `lesson_plans`
  ADD PRIMARY KEY (`lesson_id`),
  ADD KEY `idx_lesson_user` (`user_id`);
ALTER TABLE `lesson_sections`
  ADD PRIMARY KEY (`section_id`),
  ADD KEY `idx_section_lesson` (`lesson_id`);
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_password_resets_token` (`token`),
  ADD KEY `idx_password_resets_expires_at` (`expires_at`);
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`qr_id`),
  ADD UNIQUE KEY `lesson_id` (`lesson_id`);
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);
ALTER TABLE `users`
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_role` (`role_id`);
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;
ALTER TABLE `files`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `lesson_plans`
  MODIFY `lesson_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1003;
ALTER TABLE `lesson_sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `qr_codes`
  MODIFY `qr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lesson_plans` (`lesson_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `lesson_plans`
  ADD CONSTRAINT `lesson_plans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `lesson_sections`
  ADD CONSTRAINT `lesson_sections_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lesson_plans` (`lesson_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `qr_codes`
  ADD CONSTRAINT `qr_codes_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lesson_plans` (`lesson_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON UPDATE CASCADE;

CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_rt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
