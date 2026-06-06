-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 06 Jun 2026 pada 19.22
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `projekmini2_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `campaigns`
--

CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL,
  `manager_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `category` varchar(100) NOT NULL,
  `location` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `target_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `collected_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `deadline` datetime NOT NULL,
  `image` varchar(255) NOT NULL,
  `bank_info` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `campaigns`
--

INSERT INTO `campaigns` (`id`, `manager_id`, `title`, `category`, `location`, `description`, `target_amount`, `collected_amount`, `deadline`, `image`, `bank_info`, `created_at`) VALUES
(3, 4, 'Panti Asuhan', 'Panti asuhan', 'Yogyakarta', '-', 20000000.00, 11070000.00, '2026-06-26 00:00:00', '1780403981_8094.png', 'BCA = 149998898 Ewallet = 0888888', '2026-05-29 00:57:52'),
(4, 4, 'Beasiswa Kebutuhan', 'Pendidikan', 'Bantul', 'oke', 15000000.00, 15000000.00, '2026-05-29 00:00:00', '1780036258_9475.jpeg', 'BCA = 149998898 Ewallet = 0897777777', '2026-05-29 06:30:58'),
(6, 4, 'w', 'bencana', 'jogja', 'www', 10000000.00, 5000000.00, '2026-05-29 21:00:00', '1780062713_1359.jpeg', 'BCA = 149998898 Ewallet = 0897777777', '2026-05-29 13:51:53'),
(7, 7, 'Bantuan Bencana Alam Sleman', 'Bencana Alam', 'Sleman, Yogyakarta', 'Penggalang dana untuk korban bencana alam di Sleman Yogyakarta pada tanggal 30 Maret 2026', 10000000.00, 0.00, '2026-07-30 12:59:00', '1780403362_6492.jpeg', 'BNI 71231001 a.n. Yayasan Sleman Peduli', '2026-06-02 12:29:22'),
(8, 4, 'Beasiswa Pendidikan S1 untuk Yatim dan Dhuafa', 'Beasiswa Pendidikan', 'Bantul, Yogyakarta', 'Donasi beasiswa ini bertujuan untuk membantu anak-anak Bantul yang ingin lanjut tingkat yang lebih tinggi untuk pendidikan mereka', 19000000000.00, 0.00, '2027-04-06 23:59:00', '1780419438_9762.jpg', 'BCA = 72230654 a.n Ragio Rachel', '2026-06-02 16:57:18'),
(9, 7, 'Program Makan Begizi Tidak Beracun', 'Berbagi Makan', 'Gondokusuman, Yogyakarta', 'Program makan ini bertujuan untuk siapapun bisa mendapatkan makan bergizi dengan gratis, makanan diolah oleh chef bintang 4 Hotel Ambarukmo yang siap melayani memasak untuk masyarakat Gondokusuman, Yogyakarta.', 25000000.00, 20000000.00, '2026-06-11 14:04:00', '1780470378_4087.jpg', 'BNI 71231001 a.n. Yayasan MBG tidak Beracun', '2026-06-03 07:06:18'),
(10, 4, 'Bantuan Donasi Saluran Air Bersih Papua', 'Bantuan Saluran Air', 'Papua, Jayapura', 'Air Bersih di Sekolah Papua, Awal Mula Pendidikan dan Masa Depan yang Lebih Baik', 50000000.00, 0.00, '2026-08-21 05:07:00', '1780639798_4970.jpg', 'BCA = 72230654 a.n Ragio Rachel', '2026-06-05 06:09:58'),
(11, 4, 'Sumbangan Masjid untuk Yatim', 'Sumbangan Yatim', 'Bantul, Yogyakarta', 'Sumbangan donasi ini diperuntukan untuk kaum Yatim sekitar Bantul yang akan dikelola oleh Masjid Bantul', 20000000.00, 0.00, '2026-06-26 01:18:00', '1780639974_8895.jpg', 'BCA = 1234567810 Andreas', '2026-06-05 06:12:54'),
(12, 4, 'Beasiswa Sekolah Dasar Papua', 'Beasiswa Pendidikan', 'Papua', 'Beasiswa Sekolah Dasar Papua, membantu anak anak yang belum pernah mengikuti sekolah', 50000000.00, 0.00, '2026-06-19 16:21:00', '1780640467_9740.jpg', 'BCA = 8627332', '2026-06-05 06:21:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `message` text DEFAULT NULL,
  `proof_file` varchar(255) NOT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `donations`
--

INSERT INTO `donations` (`id`, `campaign_id`, `donor_id`, `amount`, `payment_method`, `message`, `proof_file`, `status`, `created_at`) VALUES
(2, 3, 3, 10000.00, 'Transfer Bank', '-', '1780033371_8589.jpeg', 'verified', '2026-05-29 05:42:51'),
(3, 3, 3, 5000000.00, 'Transfer Bank', 'y', '1780034730_6046.jpeg', 'verified', '2026-05-29 06:05:30'),
(4, 4, 3, 15000000.00, 'E-Wallet', '-', '1780036305_7333.jpeg', 'verified', '2026-05-29 06:31:45'),
(5, 3, 3, 10000.00, 'E-Wallet', '-', '1780053796_7287.jpeg', 'rejected', '2026-05-29 11:23:16'),
(6, 3, 3, 50000.00, 'Transfer Bank', '-', '1780056301_4429.jpg', 'verified', '2026-05-29 12:05:01'),
(7, 3, 3, 10000.00, 'Transfer Bank', '-', '1780056439_5093.jpg', 'verified', '2026-05-29 12:07:19'),
(8, 6, 3, 5000000.00, 'Transfer Bank', '-', '1780062761_8660.jpg', 'verified', '2026-05-29 13:52:41'),
(9, 9, 6, 20000000.00, 'Transfer Bank', 'Semangat masaknya sayangku', '1780470961_9137.jpg', 'verified', '2026-06-03 07:16:01'),
(10, 3, 6, 5000000.00, 'Transfer Bank', 'semangat', '1780472581_4275.jpg', 'verified', '2026-06-03 07:43:01'),
(11, 3, 6, 1000000.00, 'Transfer Bank', 'semangat', '1780472687_2715.jpg', 'verified', '2026-06-03 07:44:47'),
(12, 8, 6, 50000.00, 'Transfer Bank', 'HEIIIIIIIIII ANTEK ANTEK ASING', '1780474666_9620.jpeg', 'rejected', '2026-06-03 08:17:46');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('donor','manager') NOT NULL DEFAULT 'donor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `address`, `avatar`, `password`, `role`, `created_at`) VALUES
(1, 'Admin Kampanye', 'sekedapnggeh@gmail.com', '081234567890', 'Yogyakarta', NULL, '123456', 'manager', '2026-05-28 08:09:40'),
(2, 'Donatur Contoh', 'donor@example.com', '081111111111', 'Yogyakarta', NULL, '$2y$10$e0NR3qWn0g9oN6v8YQ1B1O8gFvWjZ6D9Q6KQY7wC4uF3pV5x5kq9S', 'donor', '2026-05-28 08:09:40'),
(3, 'Arseen 45', 'sadrakhwibowo@gmail.com', '087777', '-', NULL, '$2y$10$9I0b987cGGw0kabNlcFwguBahvV9UDyiL6.GFDxpVuxWzkb4Snvlm', 'donor', '2026-05-28 08:11:58'),
(4, 'admin2', 'admin2@gmail.com', '08666666', '-', NULL, '$2y$10$BvchyA4O0Y04V0Z.OZN4wuyrv6ZKv5jjnkLsEuUm3d8VzJJyA2Dsq', 'manager', '2026-05-28 08:19:31'),
(5, 'bencana jogja', 'bencanaJogja@gmail.com', '08977777777', '-', NULL, '$2y$10$dllJdBuw98GXDNMoXwuh/uUU0zfC22doR1hnfmgbqpqrl4QjV5oOu', 'manager', '2026-05-28 10:10:36'),
(6, 'Maxz Kebon', 'leonandreanleon@gmail.com', '+62 896 0888 9456', 'Yogyakarta', 'avatar_6_1780480606.png', '$2y$10$zz45cnwJ3527BuJ5U2HqYumwPJUWt25wLveLIt3aF/z9ZHUjqIbwC', 'donor', '2026-06-02 10:28:51'),
(7, 'LEONARDO ANDREAN', 'leonardo.andrean@ti.ukdw.ac.id', '0896088889456', 'Jogja', NULL, '$2y$10$jtj9FLUoIw93/qUkCfri/eRL3sxprdCV5yO5xS6xgK1i55u4P8Vxy', 'manager', '2026-06-02 10:42:27'),
(8, 'coba', 'coba@gmail.com', '089696969696', 'JOGJA', NULL, '$2y$10$BoYa7Ez2LizvV1w1fxo5MuDRhT83Za2IvlgsT2OhIvMJlRmifl6ey', 'donor', '2026-06-06 16:35:35'),
(9, 'Wowo Tedi', 'wowotedibersatu@gmail.com', '082122212221', 'Gondokusuman, Yogyakarta', NULL, '$2y$10$0j5KQehdIib2ZCKfAZQeEOeEvSdl.VQQdUjHtl9acvJcwqxpfu1tm', 'donor', '2026-06-06 16:51:12');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indeks untuk tabel `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `donor_id` (`donor_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `campaigns`
--
ALTER TABLE `campaigns`
  ADD CONSTRAINT `campaigns_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`donor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
