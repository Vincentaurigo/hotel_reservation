-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-05-19 09:23:39
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `hotel_reservation`
--

-- --------------------------------------------------------

--
-- 表的结构 `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `checkin_date` date NOT NULL,
  `checkout_date` date NOT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `room_id`, `checkin_date`, `checkout_date`, `status`) VALUES
(5, 11, 1, '2025-04-03', '2025-04-25', 'cancelled'),
(6, 11, 1, '2025-04-11', '2025-04-12', 'cancelled'),
(7, 13, 1, '2025-04-11', '2026-11-11', 'cancelled'),
(8, 13, 1, '2025-04-14', '2045-01-01', 'cancelled'),
(9, 13, 1, '2025-04-14', '2025-04-17', 'cancelled'),
(10, 14, 1, '2025-04-22', '2025-04-25', 'cancelled'),
(11, 11, 1, '2025-04-17', '2025-04-25', 'cancelled'),
(12, 15, 1, '2025-04-17', '2025-04-19', 'confirmed'),
(13, 11, 1, '2025-04-18', '2045-01-01', 'cancelled'),
(14, 17, 1, '2025-04-29', '2025-04-30', 'confirmed'),
(15, 11, 1, '2025-05-01', '2025-05-09', 'confirmed'),
(16, 11, 1, '2025-05-03', '2025-05-09', 'cancelled'),
(17, 18, 1, '2025-05-15', '2025-05-17', 'cancelled');

-- --------------------------------------------------------

--
-- 表的结构 `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `facilities` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `price`, `facilities`, `image`) VALUES
(1, 'Limited rooms', 100000.00, 'hdbfhabhdfasjfasd', '../uploads/img_slide_1.jpg');

-- --------------------------------------------------------

--
-- 表的结构 `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `ID_user` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `email` varchar(55) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `profile_picture` varchar(255) DEFAULT 'default_profile.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`ID_user`, `username`, `email`, `password`, `role`, `profile_picture`) VALUES
(1, 'vincent', 'admin@gmail.com', '$2y$10$wwVsxGzzg9JI/dhmxs/ktO00ZOhM3v1BZgnDWu/rROntO.zH9IH.q', 'user', 'default_profile.jpg'),
(2, 'balder', 'admin1@gmail.com', '$2y$10$V3znmR2ynJLddjCQZ5rmLu.oxihUC9WH7C6d4DE3RKdpEGElwLX9i', 'user', 'default_profile.jpg'),
(4, 'admin', 'adminutama@gmail.com', '$2y$10$2S.K/H8kbBXdofY7IjrGD.Q1kSebA9hHecJ6SiAFrOj0UBWs9aAXe', 'admin', 'default_profile.jpg'),
(11, 'shenfu', 'apaaja@gmail.com', '$2y$10$6Ttlebfzwtkyb.hb6qcateYqjnx025rrTOOHK.QInkawlZJKYKh/6', 'user', '11_1744993050.png'),
(12, 'kazuha', 'kazuha1@gmail.com', '$2y$10$wZpnVuuMlHj7892.27co6ui/UrunzCt/4cPjhfVqFKi/cKKIjYDgO', 'user', 'default_profile.jpg'),
(13, 'fu', 'fu@gmail.com', '$2y$10$nHH/mTgGmagVZESwMn5jTuih6hL9fXh5IbofxbXjvO526QsQf98UW', 'user', '13_1744599852.png'),
(14, 'jaja', 'jaja@gmail.com', '$2y$10$EwKzRlAttGithlw8G5lu5uXj1gN1ym8OnBN4fg4y6d8huTSKyeZ/O', 'user', 'default_profile.jpg'),
(15, 'shorekeep', 'shore@gmail.com', '$2y$10$NzUixsUKpVoMYuDZI.dhi.49JepxMy14HGzsDp1XCAT5BgqVkmYY.', 'user', 'default_profile.jpg'),
(16, 'julius', 'admin2@gmail.com', '$2y$10$uwIITgYk/uDZZSVaP/9I/.VCWUiw7GWAUxzliQbDQbcYkEtwFDH.q', 'admin', 'default_profile.jpg'),
(17, 'shanju', 'shanju11@gmail.com', '$2y$10$jBPlIYW5We5oZhfkLeU7GOMWC3ksZ9Bob7z40YctstcvdS0TQPMvS', 'user', 'default_profile.jpg'),
(18, 'waduh', 'asd@gmail.com', '$2y$10$Sma4udqbivVmCDqpIkVRA.pMUrjMRkFttqWvq/vfQjBS6Bro8VLge', 'user', '18_1747291522.jpg');

--
-- 转储表的索引
--

--
-- 表的索引 `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- 表的索引 `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- 使用表AUTO_INCREMENT `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `ID_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- 限制导出的表
--

--
-- 限制表 `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`ID_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- 限制表 `testimonials`
--
ALTER TABLE `testimonials`
  ADD CONSTRAINT `testimonials_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  ADD CONSTRAINT `testimonials_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`ID_user`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
