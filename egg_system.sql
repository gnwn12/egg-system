-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 14 Bulan Mei 2026 pada 08.07
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
-- Database: `egg_system`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `eggs`
--

CREATE TABLE `eggs` (
  `id` int(11) NOT NULL,
  `barcode` varchar(255) NOT NULL,
  `method` enum('bata','serbuk') NOT NULL,
  `production_date` date NOT NULL,
  `expired_date` date NOT NULL,
  `quantity` int(50) NOT NULL,
  `remaining` int(11) NOT NULL,
  `status` enum('CREATED','OUT_FARM','IN_STORE','SOLD','EXPIRED') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `eggs`
--

INSERT INTO `eggs` (`id`, `barcode`, `method`, `production_date`, `expired_date`, `quantity`, `remaining`, `status`, `created_at`) VALUES
(69, '9203fd28224fbac1ca00e4fc4ef80b02', 'bata', '2026-05-06', '2026-06-06', 30, 15, '', '2026-05-06 04:06:01'),
(70, '1e69ecfc56634f9bbf806b0a8f3e0b9d', 'serbuk', '2026-07-04', '2026-09-05', 10, 10, '', '2026-05-06 09:01:05'),
(73, 'd5ebfefa37560c7deedc30f9fe2d84d8', 'bata', '2026-05-07', '2026-06-06', 44, 14, 'OUT_FARM', '2026-05-06 09:09:03'),
(74, '89e046cf32b97e70570e7a61a5dce0c6', 'bata', '2026-05-06', '2026-05-30', 12, 12, 'OUT_FARM', '2026-05-06 09:46:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `egg_logs`
--

CREATE TABLE `egg_logs` (
  `id` int(11) NOT NULL,
  `egg_id` int(255) NOT NULL,
  `store_id` int(11) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `egg_logs`
--

INSERT INTO `egg_logs` (`id`, `egg_id`, `store_id`, `status`, `location`, `role`, `created_at`) VALUES
(12, 69, NULL, 'OUT_FARM', 'Peternakan', 'peternak', '2026-05-06 04:06:16'),
(13, 69, NULL, 'IN_STORE', 'Toko', 'toko', '2026-05-06 04:06:36'),
(14, 69, NULL, 'SOLD', 'Toko', 'toko', '2026-05-06 04:08:08'),
(15, 69, NULL, 'SOLD', 'Toko', 'toko', '2026-05-06 04:16:11'),
(16, 69, NULL, 'SOLD', 'Toko', 'toko', '2026-05-06 04:16:50'),
(17, 70, NULL, 'OUT_FARM', 'Peternakan', 'peternak', '2026-05-06 09:01:40'),
(18, 71, NULL, 'OUT_FARM', 'Peternakan', 'peternak', '2026-05-06 09:06:32'),
(19, 73, NULL, 'OUT_FARM', 'Peternakan', 'peternak', '2026-05-06 09:09:30'),
(20, 73, NULL, 'IN_STORE', 'Toko', 'toko', '2026-05-06 09:40:42'),
(21, 73, NULL, 'SOLD', 'Toko', 'toko', '2026-05-06 09:42:31'),
(22, 74, NULL, 'OUT_FARM', 'Peternakan', 'peternak', '2026-05-06 09:48:15'),
(23, 74, NULL, 'OUT_FARM', 'Peternakan', 'peternak', '2026-05-06 09:51:31');

-- --------------------------------------------------------

--
-- Struktur dari tabel `egg_stocks`
--

CREATE TABLE `egg_stocks` (
  `id` int(11) NOT NULL,
  `egg_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `sold` int(11) NOT NULL DEFAULT 0,
  `remaining` int(11) NOT NULL,
  `status` enum('IN_STORE','SOLD') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `egg_stocks`
--

INSERT INTO `egg_stocks` (`id`, `egg_id`, `store_id`, `quantity`, `sold`, `remaining`, `status`, `created_at`) VALUES
(2, 69, 2, 15, 8, 7, 'IN_STORE', '2026-05-06 04:06:36'),
(3, 73, 2, 30, 10, 20, 'IN_STORE', '2026-05-06 09:40:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('peternak','toko') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'samudra', '$2y$10$U7bi96FMT2jmzu8buxtjIOfuT6NO7ypOqvQgIcBEge3vmGYwBigri', 'peternak', '2026-05-06 02:51:29'),
(2, 'osa', '$2y$10$Bjty6gpSgcq9AFluEEW5..gFDQXlinXEi/LMgAdhCrXVI07LvjXw6', 'toko', '2026-05-06 03:18:56');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `eggs`
--
ALTER TABLE `eggs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `egg_logs`
--
ALTER TABLE `egg_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `egg_stocks`
--
ALTER TABLE `egg_stocks`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `eggs`
--
ALTER TABLE `eggs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT untuk tabel `egg_logs`
--
ALTER TABLE `egg_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `egg_stocks`
--
ALTER TABLE `egg_stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
