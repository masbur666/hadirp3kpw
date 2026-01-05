-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 05, 2026 at 03:06 PM
-- Server version: 10.6.21-MariaDB-log
-- PHP Version: 8.3.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `beetvmyi_presensipw`
--

-- --------------------------------------------------------

--
-- Table structure for table `pegawai`
--

CREATE TABLE `pegawai` (
  `id` int(11) NOT NULL,
  `nip` varchar(30) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pegawai`
--

INSERT INTO `pegawai` (`id`, `nip`, `nama`, `jabatan`, `no_hp`, `created_at`) VALUES
(1, '198505122025211122', 'Burhan Syamnurosyid', 'Operator Layanan Operasional', '6281808032100', '2026-01-04 22:07:17'),
(2, '199506072025211097', 'Ahmad Dwi Muhariyono', 'Penata Layanan Oprasional', '082333928088', '2026-01-04 22:19:29'),
(3, '199610102025211087', 'Raka Hendriawan', 'Penata Layanan Operasi', '081380876267', '2026-01-04 22:27:55'),
(4, '198108162025211060', 'Agus Sigit Purnomo', 'Penata Layanan Operasional', '087839816189', '2026-01-04 22:38:12'),
(5, '199703122025212058', 'Nurma Puspitasari', 'PENATA LAYANAN OPERASIONAL', '082137173731', '2026-01-05 07:30:38'),
(6, '199407182025212082', 'Monica Caroline ', 'PENATA LAYANAN OPERASIONAL', '081227774665', '2026-01-05 07:33:35');

-- --------------------------------------------------------

--
-- Table structure for table `presensi`
--

CREATE TABLE `presensi` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `status` enum('Hadir','Dinas','TK','CT','CS','CBN') DEFAULT 'Hadir',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `presensi`
--

INSERT INTO `presensi` (`id`, `pegawai_id`, `tanggal`, `jam_masuk`, `jam_pulang`, `status`, `keterangan`, `created_at`) VALUES
(1, 1, '2026-01-05', '07:07:52', NULL, 'Hadir', '', '2026-01-04 22:07:52'),
(4, 2, '2026-01-05', '07:09:30', NULL, 'Hadir', '', '2026-01-05 07:18:30'),
(5, 3, '2026-01-05', '07:08:38', NULL, 'Hadir', '', '2026-01-05 07:18:38'),
(6, 4, '2026-01-05', '07:10:19', NULL, 'Hadir', '', '2026-01-05 07:20:19'),
(7, 5, '2026-01-05', '06:58:08', NULL, 'Hadir', '', '2026-01-05 07:31:08'),
(8, 6, '2026-01-05', '06:53:41', NULL, 'Hadir', '', '2026-01-05 07:33:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`);

--
-- Indexes for table `presensi`
--
ALTER TABLE `presensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_presensi` (`pegawai_id`,`tanggal`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `presensi`
--
ALTER TABLE `presensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `presensi`
--
ALTER TABLE `presensi`
  ADD CONSTRAINT `presensi_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
