-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 29, 2025 at 01:27 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbrias`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbadmin`
--

CREATE TABLE `tbadmin` (
  `idadmin` int(11) NOT NULL,
  `username` varchar(10) NOT NULL,
  `pwadmin` varchar(10) NOT NULL,
  `hashadmin` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbadmin`
--

INSERT INTO `tbadmin` (`idadmin`, `username`, `pwadmin`, `hashadmin`) VALUES
(1, 'admin', 'admin123', '$2y$10$BFCwg6zCyQYpUuRu01r9mOrotaTjIMe779VjMTBBNIJdAIvtsD9Ta');

-- --------------------------------------------------------

--
-- Table structure for table `tbkatalog`
--

CREATE TABLE `tbkatalog` (
  `idadat` int(2) NOT NULL,
  `nama_adat` varchar(30) NOT NULL,
  `foto_path` varchar(120) NOT NULL,
  `isi_desk` text NOT NULL,
  `harga` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbkatalog`
--

INSERT INTO `tbkatalog` (`idadat`, `nama_adat`, `foto_path`, `isi_desk`, `harga`) VALUES
(1, 'Pakaian Adat Minangkabau', 'uploads/adat/efe2a4681a7b2227.jpg', 'Adat Minangkabau Termasuk:\r\n\r\n-Rias pengantin perempuan full + Suntiang (Gadang/Standar).\r\n\r\n-Grooming pengantin pria (natural makeup + hair styling).\r\n\r\n-Sewa busana adat lengkap: baju kurung/songket, saluak/deta (pria), aksesori standar.\r\n\r\n-Penataan suntiang, selendang, dan songket sesuai pakem Minang.\r\n\r\n2 sesi acara: Akad/Pemberkatan & Resepsi/Prosesi Adat (retouch di antaranya).\r\n\r\n-Wardrobe assistant on-site + emergency kit.\r\n\r\n-Konsultasi konsep & warna + higienitas peralatan.\r\n\r\n-Add-on: Trial makeup • Rias keluarga • Sewa suntiang/busana tambahan • Standby extended • Early morning fee • Luar kota/travel fee.\r\nCatatan: DP 50% • Harga custom (cakupan & lokasi) • Pengembalian busana/aksesori H+1.', 11000000),
(2, 'Pakaian Adat Batak', 'uploads/adat/b2c0625284c622eb.jpg', 'Adat Batak Termasuk:\r\n\r\n-Rias pengantin perempuan full + tata rambut/sanggul Batak & aksesori kepala.\r\n\r\n-Grooming pengantin pria (makeup natural + hair styling).\r\n\r\n-Sewa busana adat lengkap (wanita & pria) termasuk ulos dan aksesori standar.\r\n\r\n-Penataan ulos sesuai pakem (Toba/Karo/Mandailing/Simalungun/Pakpak/Angkola).\r\n\r\n2 sesi acara: Akad/Pemberkatan & Resepsi/Prosesi Adat (retouch di antaranya).\r\n\r\n-Wardrobe assistant & emergency kit on-site.\r\n\r\n-Konsultasi konsep & warna + higienitas peralatan.\r\n\r\n-Durasi rias ±2–3 jam (pengantin perempuan).\r\n\r\n-Add-on (Opsional): Trial makeup • Rias keluarga • Sewa ulos/busana tambahan • Standby extended • Early morning fee • Luar kota/travel fee.\r\nCatatan: DP 50% • Harga custom (cakupan & lokasi) • Pengembalian busana/aksesori H+1.', 10000000),
(3, 'Pakaian Adat Betawi', 'uploads/adat/a24ab99300d6b50a.jpg', 'Adat Betawi Termasuk:\r\n\r\n-Rias pengantin perempuan full + sanggul Betawi & kembang goyang.\r\n\r\n-Grooming pengantin pria (natural makeup + hair styling).\r\n\r\n-Sewa busana adat lengkap: kebaya encim/kurung, kain, baju demang/pangsi, aksesori standar.\r\n\r\n-Penataan busana & selendang sesuai pakem Betawi.\r\n\r\n2 sesi acara: Akad/Pemberkatan & Resepsi/Prosesi (retouch di antaranya).\r\n\r\n-Wardrobe assistant on-site + emergency kit.\r\n\r\n-Konsultasi konsep & warna + higienitas peralatan.\r\n\r\n-Add-on: Trial makeup • Rias keluarga • Sewa busana tambahan • Standby extended • Early morning fee • Luar kota/travel fee.\r\nCatatan: DP 50% • Harga custom (cakupan & lokasi) • Pengembalian busana/aksesori H+1.', 6000000),
(4, 'Pakaian Adat Sunda', 'uploads/adat/bb1a3fa822e10922.png', 'Adat Sunda Termasuk:\r\n\r\n-Rias pengantin perempuan full + Sanggul Sunda Putri/Siger Sunda + ronce melati.\r\n\r\n-Grooming pengantin pria (natural makeup + hair styling).\r\n\r\n-Sewa busana adat lengkap: kebaya Sunda, kain/batik, beskap/pria, aksesori standar.\r\n\r\n-Penataan siger, selendang, dan ronce sesuai pakem Sunda.\r\n\r\n2 sesi acara: Akad/Pemberkatan & Resepsi/Prosesi (retouch di antaranya).\r\n\r\n-Wardrobe assistant on-site + emergency kit.\r\n\r\n-Konsultasi konsep & warna + higienitas peralatan.\r\n\r\n-Add-on: Trial makeup • Rias keluarga • Sewa busana/ronce tambahan • Standby extended • Early morning fee • Luar kota/travel fee.\r\nCatatan: DP 50% • Harga custom • Pengembalian H+1.', 6000000),
(5, 'Pakaian Adat Jawa', 'uploads/adat/f567532ff8a1112f.jpg', 'Adat Jawa Termasuk:\r\n\r\n-Rias pengantin perempuan full + Paes (Solo/Jogja), sanggul Jawa, cunduk mentul.\r\n\r\n-Grooming pengantin pria (natural makeup + hair styling).\r\n\r\n-Sewa busana adat lengkap: kebaya/busana paes, kain batik, beskap & blangkon, aksesori standar.\r\n\r\n-Penataan paes & jarik/batik sesuai pakem Solo/Jogja.\r\n\r\n2 sesi acara: Akad/Pemberkatan & Resepsi/Prosesi (retouch di antaranya).\r\n\r\n-Wardrobe assistant on-site + emergency kit.\r\n\r\nKonsultasi konsep & warna + higienitas peralatan.\r\n\r\n-Add-on: Trial paes • Rias keluarga • Sewa busana tambahan • Standby extended • Early morning fee • Luar kota/travel fee.\r\nCatatan: DP 50% • Harga custom • Pengembalian H+1.', 7500000);

-- --------------------------------------------------------

--
-- Table structure for table `tbsewa`
--

CREATE TABLE `tbsewa` (
  `idsewa` int(2) NOT NULL,
  `idadat` int(2) NOT NULL,
  `nama_cust` varchar(40) NOT NULL,
  `email` varchar(30) NOT NULL,
  `notelp` int(14) NOT NULL,
  `kota` varchar(20) NOT NULL,
  `tgl_sewa` date NOT NULL,
  `jenisbayar` varchar(4) NOT NULL,
  `harga_total` int(11) NOT NULL,
  `statusbayar` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbsewa`
--

INSERT INTO `tbsewa` (`idsewa`, `idadat`, `nama_cust`, `email`, `notelp`, `kota`, `tgl_sewa`, `jenisbayar`, `harga_total`, `statusbayar`) VALUES
(1, 4, 'Fikri', 'hamatrol9@gmail.com', 2147483647, 'Depok', '2025-10-31', 'Full', 6000000, 'Dibatalkan'),
(2, 3, 'Ilham', 'ilhamnoorhidayat29@gmail.com', 2147483647, 'Bogor', '2025-11-03', 'DP', 6000000, 'Lunas Full');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbadmin`
--
ALTER TABLE `tbadmin`
  ADD PRIMARY KEY (`idadmin`);

--
-- Indexes for table `tbkatalog`
--
ALTER TABLE `tbkatalog`
  ADD PRIMARY KEY (`idadat`);

--
-- Indexes for table `tbsewa`
--
ALTER TABLE `tbsewa`
  ADD PRIMARY KEY (`idsewa`),
  ADD KEY `idadat_fk` (`idadat`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbadmin`
--
ALTER TABLE `tbadmin`
  MODIFY `idadmin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbkatalog`
--
ALTER TABLE `tbkatalog`
  MODIFY `idadat` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbsewa`
--
ALTER TABLE `tbsewa`
  MODIFY `idsewa` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbsewa`
--
ALTER TABLE `tbsewa`
  ADD CONSTRAINT `fk_tbsewa_idadat` FOREIGN KEY (`idadat`) REFERENCES `tbkatalog` (`idadat`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
