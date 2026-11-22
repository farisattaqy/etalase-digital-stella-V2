-- ============================================================
-- DATABASE DUMP UNTUK PHPMYADMIN
-- Project: Etalase Digital Stella
-- Hak Cipta: Faris Attaqy (Telkom Schools)
-- Target Database: db_etalase_stella
-- ============================================================

-- Petunjuk Penggunaan di Perangkat Lain / phpMyAdmin:
-- 1. Buka phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Klik menu "Import" di bagian atas (atau buat database baru bernama `db_etalase_stella` lalu Import).
-- 3. Pilih file ini (database.sql / db_etalase_stella.sql) lalu klik "Go" / "Kirim".
-- 4. Buka project di browser (http://localhost/etalase-digital-stella).
--
-- Daftar Akun Bawaan (Default Login):
-- +---------------+--------------------------+-------------+
-- | Role          | Email                    | Password    |
-- +---------------+--------------------------+-------------+
-- | SUPER ADMIN   | superadmin@stella.sch.id | password123 |
-- | ADMIN         | admin@stella.sch.id      | password123 |
-- | SISWA         | siswa@stella.sch.id      | password123 |
-- +---------------+--------------------------+-------------+

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_etalase_stella`
--
CREATE DATABASE IF NOT EXISTS `db_etalase_stella` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_etalase_stella`;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','siswa') DEFAULT 'siswa',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
-- Password default untuk semua akun bawaan adalah: password123
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`) VALUES
(1, 'Super Admin Stella', 'superadmin@stella.sch.id', '$2y$10$CjZO2PjNE89Gb3ShVU7MnuiAvvuKVDqaofiD3vmqX2qsERqf3jIKK', 'super_admin'),
(2, 'Admin Stella', 'admin@stella.sch.id', '$2y$10$CjZO2PjNE89Gb3ShVU7MnuiAvvuKVDqaofiD3vmqX2qsERqf3jIKK', 'admin'),
(3, 'Siswa Contoh', 'siswa@stella.sch.id', '$2y$10$CjZO2PjNE89Gb3ShVU7MnuiAvvuKVDqaofiD3vmqX2qsERqf3jIKK', 'siswa');

-- --------------------------------------------------------

--
-- Struktur dari tabel `projects`
--

CREATE TABLE `projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `nama_project` varchar(150) NOT NULL,
  `deskripsi` text NOT NULL,
  `gambar` varchar(255) NOT NULL,
  `link_project` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `projects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
