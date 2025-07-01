-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Май 23 2025 г., 23:07
-- Версия сервера: 8.0.30
-- Версия PHP: 8.0.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `hotel`
--

-- --------------------------------------------------------

--
-- Структура таблицы `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int NOT NULL,
  `user_id` int NOT NULL,
  `room_id` int NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `guests` int NOT NULL DEFAULT '1',
  `total_price` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `room_id`, `check_in`, `check_out`, `guests`, `total_price`, `status`, `created_at`) VALUES
(1, 1, 2, '2025-05-16', '2025-05-18', 2, '13000.00', 'confirmed', '2025-05-16 12:08:30'),
(2, 5, 2, '2025-05-16', '2025-05-18', 1, '13000.00', 'cancelled', '2025-05-17 16:36:27'),
(4, 6, 2, '2025-05-22', '2025-05-24', 2, '13000.00', 'pending', '2025-05-22 15:14:44'),
(5, 6, 3, '2025-05-25', '2025-05-31', 2, '42000.00', 'pending', '2025-05-22 15:21:40');

-- --------------------------------------------------------

--
-- Структура таблицы `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(1, '123@gmail.com', '799596bb7ffd06811fc362f5824fd3515d9538eedf58ecaa852b7e47c28dfde2', '2025-05-14 18:43:06', '2025-05-14 14:43:06'),
(2, '123@gmail.com', 'bb2a62892c9c0a2322607e4c8439977f5b8f765b738622eae3384d88a8830090', '2025-05-14 19:16:15', '2025-05-14 15:16:15'),
(3, 'lollkapolka@mail.ru', '94d3987d4d9bf0a02df3298710324d2194f2a3f0df510e8c2f41ea3c6fb317ef', '2025-05-14 19:19:57', '2025-05-14 15:19:57'),
(4, 'lollkapolka@mail.ru', '32cc3efbcce3ce74342210afc0bcdb1e5be0dc15b62ade04037369b91a5c733f', '2025-05-19 02:02:14', '2025-05-18 22:02:14');

-- --------------------------------------------------------

--
-- Структура таблицы `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `reviews`
--

INSERT INTO `reviews` (`review_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 5, 'йцвйцв', '2025-05-16 17:09:25'),
(3, 6, 2, 'фу', '2025-05-18 13:47:33'),
(4, 5, 5, 'qew', '2025-05-18 21:57:44'),
(5, 5, 1, 'говна ', '2025-05-21 20:21:46');

-- --------------------------------------------------------

--
-- Структура таблицы `role`
--

CREATE TABLE `role` (
  `id_role` int NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `role`
--

INSERT INTO `role` (`id_role`, `role_name`) VALUES
(1, 'admin'),
(2, 'manager'),
(3, 'client');

-- --------------------------------------------------------

--
-- Структура таблицы `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `id_type` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `capacity` int NOT NULL,
  `is_available` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_number`, `id_type`, `price`, `capacity`, `is_available`) VALUES
(1, '1', 1, '6000.00', 2, 2),
(2, '2', 2, '6500.00', 2, 2),
(3, '3', 3, '7000.00', 3, 2),
(4, '4', 4, '7500.00', 4, 2);

-- --------------------------------------------------------

--
-- Структура таблицы `type_rooms`
--

CREATE TABLE `type_rooms` (
  `id_type` int NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `type_rooms`
--

INSERT INTO `type_rooms` (`id_type`, `name`) VALUES
(1, 'НОМЕР С КРОВАТЬЮ РАЗМЕРА \"QUEEN-SIZE\"'),
(2, 'СТАНДАРТНЫЙ ДВУХМЕСТНЫЙ НОМЕР'),
(3, 'НОМЕР С КРОВАТЬЮ \"QUEEN-SIZE\" И ДИВАН-КРОВАТЬЮ'),
(4, 'НОМЕР-СТУДИО');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `last_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `last_name`, `first_name`, `phone`) VALUES
(1, 'eqwdsa@gmail.com', '$2y$10$Zj.gRWDOE4rLJ43OEwCmB.e6ObKhDqLneSgOVjOSrDSXVVITg0M1y', 'qew', 'qwert', '+79000000000'),
(3, 'admin@gmail.com', '$2y$10$zj3RC3LFuw6o/ypqXKHALuNG4hOnRP4JzZf40i0yg8PkYtgpsSZRm', 'admin', 'admin', '9000000001'),
(4, '123@gmail.com', '$2y$10$qwTPRTMYt0cGYTk3ENxK5eeVV9AxxJohc7.Gayf5kpQ1rIvdVcGBW', '123', '123', '9000000001'),
(5, 'lollkapolka@mail.ru', '$2y$10$lSftNRIYWuEWK.uJxAoGm.An2sg5cqArD42kyBpVZFhTxobtZE40u', 'r', 'd', '+79000000001'),
(6, 'poi@gmail.com', '$2y$10$MjF79yoTRFAaB1HbSSMxUOFu.k1zVeLmLlU.xDyfgWrFAMpLlugSy', 'poi', 'poi', '+79000000011');

-- --------------------------------------------------------

--
-- Структура таблицы `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(3, 1),
(1, 3),
(4, 3),
(5, 3),
(6, 3);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Индексы таблицы `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_password_resets_users` (`email`);

--
-- Индексы таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id_role`);

--
-- Индексы таблицы `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `id_type` (`id_type`);

--
-- Индексы таблицы `type_rooms`
--
ALTER TABLE `type_rooms`
  ADD PRIMARY KEY (`id_type`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Индексы таблицы `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);

--
-- Ограничения внешнего ключа таблицы `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_users` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Ограничения внешнего ключа таблицы `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`id_type`) REFERENCES `type_rooms` (`id_type`);

--
-- Ограничения внешнего ключа таблицы `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role` (`id_role`),
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
