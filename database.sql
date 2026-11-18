-- ==============================================================================
-- DATABASE SCHEMA: WALUYA - Platform Digital Posyandu Lansia
-- Database: waluya_db
-- Disesuaikan untuk 3 Role:
-- 1. Admin: CRUD Anggota Keluarga & Lansia, ACC Akun Keluarga Baru, CRUD Kader, RUD Riwayat Kesehatan
-- 2. Kader: CRUD Riwayat Kesehatan Lansia
-- 3. Anggota Keluarga: Registrasi Mandiri (Menunggu ACC Admin), Read Riwayat Kesehatan Lansia
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- ------------------------------------------------------------------------------
-- 1. Buat Database
-- ------------------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `waluya_db` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `waluya_db`;

-- ------------------------------------------------------------------------------
-- Hapus Tabel Lama Jika Ada (Urutan dari tabel anak ke tabel induk)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `riwayat_kesehatan`;
DROP TABLE IF EXISTS `pemeriksaan`;
DROP TABLE IF EXISTS `lansia`;
DROP TABLE IF EXISTS `keluarga`;
DROP TABLE IF EXISTS `kader`;
DROP TABLE IF EXISTS `posyandu`;
DROP TABLE IF EXISTS `users`;
DROP VIEW IF EXISTS `v_antrean_acc_keluarga`;
DROP VIEW IF EXISTS `v_riwayat_kesehatan_lengkap`;

-- ------------------------------------------------------------------------------
-- 2. Tabel: users
-- Menyimpan semua akun (Admin, Kader, Anggota Keluarga)
-- Fitur: Anggota keluarga daftar mandiri -> status_akun 'pending' nunggu ACC Admin
-- ------------------------------------------------------------------------------
CREATE TABLE `users` (
  `id_user` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `no_hp` VARCHAR(20) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL COMMENT 'Bcrypt Hash password',
  `role` ENUM('admin', 'kader', 'keluarga') NOT NULL DEFAULT 'keluarga',
  `status_akun` ENUM('pending', 'aktif', 'ditolak', 'nonaktif') NOT NULL DEFAULT 'pending' COMMENT 'Keluarga yang baru daftar statusnya pending hingga di-ACC admin',
  `id_admin_acc` INT UNSIGNED NULL COMMENT 'ID Admin yang menyetujui / memverifikasi akun',
  `tanggal_acc` DATETIME NULL COMMENT 'Waktu disetujui oleh admin',
  `catatan_acc` TEXT NULL COMMENT 'Catatan admin saat verifikasi atau alasan jika ditolak',
  `foto_profil` VARCHAR(255) DEFAULT 'default_avatar.png',
  `terakhir_login` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status_akun`),
  CONSTRAINT `fk_users_admin_acc` FOREIGN KEY (`id_admin_acc`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Data akun pengguna dan status approval admin';

-- ------------------------------------------------------------------------------
-- 3. Tabel: posyandu
-- Data master Posyandu Lansia
-- ------------------------------------------------------------------------------
CREATE TABLE `posyandu` (
  `id_posyandu` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_posyandu` VARCHAR(100) NOT NULL,
  `alamat` VARCHAR(255) NOT NULL,
  `rt` VARCHAR(5) NOT NULL,
  `rw` VARCHAR(5) NOT NULL,
  `kelurahan` VARCHAR(100) NOT NULL,
  `kecamatan` VARCHAR(100) NOT NULL,
  `kota` VARCHAR(100) NOT NULL,
  `puskesmas_pembina` VARCHAR(100) NOT NULL,
  `kontak_posyandu` VARCHAR(30) NULL,
  `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_posyandu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master data Posyandu Lansia';

-- ------------------------------------------------------------------------------
-- 4. Tabel: kader
-- Data detail kader posyandu (Admin memiliki akses CRUD pada data ini)
-- ------------------------------------------------------------------------------
CREATE TABLE `kader` (
  `id_kader` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` INT UNSIGNED NOT NULL UNIQUE,
  `id_posyandu` INT UNSIGNED NOT NULL,
  `nik` CHAR(16) NOT NULL UNIQUE,
  `jabatan` ENUM('Ketua', 'Sekretaris', 'Bendahara', 'Anggota') NOT NULL DEFAULT 'Anggota',
  `tanggal_bergabung` DATE NOT NULL,
  `status_kader` ENUM('aktif', 'cuti', 'nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kader`),
  CONSTRAINT `fk_kader_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_kader_posyandu` FOREIGN KEY (`id_posyandu`) REFERENCES `posyandu` (`id_posyandu`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Profil kader Posyandu';

-- ------------------------------------------------------------------------------
-- 5. Tabel: keluarga
-- Profil anggota keluarga / wali lansia (Admin memiliki akses CRUD dan ACC)
-- ------------------------------------------------------------------------------
CREATE TABLE `keluarga` (
  `id_keluarga` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` INT UNSIGNED NOT NULL UNIQUE,
  `nik` CHAR(16) NOT NULL UNIQUE,
  `alamat_domisili` TEXT NOT NULL,
  `rt` VARCHAR(5) NOT NULL,
  `rw` VARCHAR(5) NOT NULL,
  `pekerjaan` VARCHAR(100) NULL,
  `kontak_darurat` VARCHAR(20) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_keluarga`),
  CONSTRAINT `fk_keluarga_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Profil anggota keluarga penanggung jawab lansia';

-- ------------------------------------------------------------------------------
-- 6. Tabel: lansia
-- Master data lansia (Admin memiliki akses CRUD)
-- ------------------------------------------------------------------------------
CREATE TABLE `lansia` (
  `id_lansia` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_posyandu` INT UNSIGNED NOT NULL,
  `id_keluarga` INT UNSIGNED NULL COMMENT 'Penanggung jawab dari tabel keluarga',
  `nik` CHAR(16) NOT NULL UNIQUE,
  `no_kk` CHAR(16) NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `tempat_lahir` VARCHAR(60) NOT NULL,
  `tanggal_lahir` DATE NOT NULL,
  `jenis_kelamin` ENUM('L', 'P') NOT NULL,
  `golongan_darah` ENUM('A', 'B', 'AB', 'O', 'Tidak Tahu') NOT NULL DEFAULT 'Tidak Tahu',
  `agama` VARCHAR(30) NOT NULL DEFAULT 'Islam',
  `alamat` TEXT NOT NULL,
  `rt` VARCHAR(5) NOT NULL,
  `rw` VARCHAR(5) NOT NULL,
  `no_hp` VARCHAR(20) NULL,
  `hubungan_keluarga` VARCHAR(50) NULL COMMENT 'Contoh: Ayah Kandung, Ibu Mertua, Kakek, Nenek',
  `riwayat_penyakit` TEXT NULL COMMENT 'Hipertensi, Kolesterol, Diabetes, dll',
  `riwayat_alergi` TEXT NULL,
  `status_lansia` ENUM('aktif', 'pindah', 'meninggal') NOT NULL DEFAULT 'aktif',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_lansia`),
  CONSTRAINT `fk_lansia_posyandu` FOREIGN KEY (`id_posyandu`) REFERENCES `posyandu` (`id_posyandu`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_lansia_keluarga` FOREIGN KEY (`id_keluarga`) REFERENCES `keluarga` (`id_keluarga`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master data warga lansia';

-- ------------------------------------------------------------------------------
-- 7. Tabel: riwayat_kesehatan (Rekam Medis Lansia)
-- Hak Akses:
-- - Kader : CRUD (Create, Read, Update, Delete)
-- - Admin : RUD (Read, Update, Delete)
-- - Keluarga : Read (Melihat riwayat kesehatan lansianya)
-- ------------------------------------------------------------------------------
CREATE TABLE `riwayat_kesehatan` (
  `id_riwayat` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_lansia` INT UNSIGNED NOT NULL,
  `id_kader` INT UNSIGNED NULL COMMENT 'Kader yang melakukan pemeriksaan fisik',
  `tanggal_pemeriksaan` DATE NOT NULL,
  `waktu_pemeriksaan` TIME NOT NULL,
  
  -- Antropometri
  `berat_badan` DECIMAL(5,2) NOT NULL COMMENT 'kg',
  `tinggi_badan` DECIMAL(5,2) NOT NULL COMMENT 'cm',
  `imt` DECIMAL(5,2) NOT NULL COMMENT 'Indeks Massa Tubuh',
  `status_imt` ENUM('Kurus', 'Normal', 'Kelebihan BB', 'Obesitas') NOT NULL DEFAULT 'Normal',
  
  -- Tanda Vital
  `tensi_sistolik` INT NOT NULL COMMENT 'mmHg (contoh: 120)',
  `tensi_diastolik` INT NOT NULL COMMENT 'mmHg (contoh: 80)',
  `status_tensi` ENUM('Hipotensi', 'Normal', 'Pra-hipertensi', 'Hipertensi 1', 'Hipertensi 2') NOT NULL DEFAULT 'Normal',
  `detak_jantung` INT NOT NULL COMMENT 'bpm (contoh: 78)',
  `suhu_tubuh` DECIMAL(4,2) NOT NULL COMMENT 'Celcius (contoh: 36.50)',
  
  -- Pemeriksaan Darah / POCT
  `gula_darah` INT NULL COMMENT 'mg/dL (contoh: 110)',
  `status_gula_darah` ENUM('Rendah', 'Normal', 'Tinggi') NULL,
  `kolesterol` INT NULL COMMENT 'mg/dL (contoh: 180)',
  `status_kolesterol` ENUM('Normal', 'Tinggi') NULL,
  `asam_urat` DECIMAL(4,2) NULL COMMENT 'mg/dL (contoh: 5.2)',
  `status_asam_urat` ENUM('Normal', 'Tinggi') NULL,
  
  -- Keluhan & Catatan
  `keluhan` TEXT NULL COMMENT 'Keluhan fisik dari lansia',
  `catatan_kader` TEXT NULL COMMENT 'Tindakan atau saran gizi dari kader',
  `status_kondisi` ENUM('Aman / Sehat', 'Perlu Perhatian', 'Risiko Tinggi') NOT NULL DEFAULT 'Aman / Sehat',
  `rujukan_puskesmas` ENUM('Ya', 'Tidak') NOT NULL DEFAULT 'Tidak',
  
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_riwayat`),
  CONSTRAINT `fk_riwayat_lansia` FOREIGN KEY (`id_lansia`) REFERENCES `lansia` (`id_lansia`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_riwayat_kader` FOREIGN KEY (`id_kader`) REFERENCES `kader` (`id_kader`) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX `idx_riwayat_tanggal` (`tanggal_pemeriksaan`),
  INDEX `idx_riwayat_kondisi` (`status_kondisi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pencatatan riwayat kesehatan lansia oleh kader/admin';

-- ------------------------------------------------------------------------------
-- 8. View: v_antrean_acc_keluarga
-- Mempermudah Admin melihat daftar anggota keluarga yang menunggu ACC
-- ------------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_antrean_acc_keluarga` AS
SELECT 
  u.`id_user`,
  u.`nama_lengkap`,
  u.`email`,
  u.`no_hp`,
  u.`status_akun`,
  u.`created_at` AS `waktu_mendaftar`,
  k.`nik`,
  k.`alamat_domisili`,
  k.`rt`,
  k.`rw`,
  k.`kontak_darurat`
FROM `users` u
LEFT JOIN `keluarga` k ON u.`id_user` = k.`id_user`
WHERE u.`role` = 'keluarga' AND u.`status_akun` = 'pending';

-- ------------------------------------------------------------------------------
-- 9. View: v_riwayat_kesehatan_lengkap
-- Mempermudah query data rekam medis beserta nama lansia, kader, & penanggung jawab
-- ------------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_riwayat_kesehatan_lengkap` AS
SELECT 
  rk.`id_riwayat`,
  rk.`tanggal_pemeriksaan`,
  rk.`waktu_pemeriksaan`,
  l.`id_lansia`,
  l.`nama_lengkap` AS `nama_lansia`,
  l.`nik` AS `nik_lansia`,
  TIMESTAMPDIFF(YEAR, l.`tanggal_lahir`, CURDATE()) AS `usia_lansia`,
  l.`jenis_kelamin`,
  rk.`berat_badan`,
  rk.`tinggi_badan`,
  rk.`imt`,
  rk.`status_imt`,
  rk.`tensi_sistolik`,
  rk.`tensi_diastolik`,
  rk.`status_tensi`,
  rk.`detak_jantung`,
  rk.`suhu_tubuh`,
  rk.`gula_darah`,
  rk.`kolesterol`,
  rk.`asam_urat`,
  rk.`keluhan`,
  rk.`catatan_kader`,
  rk.`status_kondisi`,
  rk.`rujukan_puskesmas`,
  u_kader.`nama_lengkap` AS `nama_kader_pemeriksa`,
  u_kel.`id_user` AS `id_user_keluarga`,
  u_kel.`nama_lengkap` AS `nama_keluarga_wali`
FROM `riwayat_kesehatan` rk
JOIN `lansia` l ON rk.`id_lansia` = l.`id_lansia`
LEFT JOIN `kader` k ON rk.`id_kader` = k.`id_kader`
LEFT JOIN `users` u_kader ON k.`id_user` = u_kader.`id_user`
LEFT JOIN `keluarga` kel ON l.`id_keluarga` = kel.`id_keluarga`
LEFT JOIN `users` u_kel ON kel.`id_user` = u_kel.`id_user`;

-- ==============================================================================
-- 10. SEED DATA AWAL (DEMO & TESTING SIAP PAKAI)
-- Password default semua akun sampel: password123
-- Bcrypt Hash: $2y$10$aHUCaRf4BKDapdISrO4Ga.XZ262R0gBrWQy8pcG4mQT/gEfz/22CK
-- ==============================================================================

-- A. Akun Users
INSERT INTO `users` (`id_user`, `nama_lengkap`, `email`, `no_hp`, `password`, `role`, `status_akun`, `id_admin_acc`, `tanggal_acc`, `catatan_acc`) VALUES
-- 1. Admin Sistem
(1, 'Admin Utama Waluya', 'admin@waluya.id', '081234567890', '$2y$10$aHUCaRf4BKDapdISrO4Ga.XZ262R0gBrWQy8pcG4mQT/gEfz/22CK', 'admin', 'aktif', NULL, NULL, 'Akun Administrator Pusat'),

-- 2. Kader Posyandu
(2, 'Siti Rahmawati (Kader)', 'kader.siti@waluya.id', '081298765431', '$2y$10$aHUCaRf4BKDapdISrO4Ga.XZ262R0gBrWQy8pcG4mQT/gEfz/22CK', 'kader', 'aktif', 1, '2026-01-01 08:00:00', 'Disetujui Admin untuk Posyandu Melati'),

-- 3. Anggota Keluarga yang SUDAH DI-ACC Admin (Bisa langsung login dan melihat riwayat lansia)
(3, 'Budi Santoso', 'budi.santoso@gmail.com', '085712345678', '$2y$10$aHUCaRf4BKDapdISrO4Ga.XZ262R0gBrWQy8pcG4mQT/gEfz/22CK', 'keluarga', 'aktif', 1, '2026-02-01 10:00:00', 'Berkas NIK dan KK valid, disetujui'),

-- 4. Anggota Keluarga yang BARU DAFTAR & MENUNGGU ACC ADMIN (Bisa dicoba di fitur ACC Admin!)
(4, 'Dewi Lestari (Menunggu ACC)', 'dewi.lestari@gmail.com', '085787654321', '$2y$10$aHUCaRf4BKDapdISrO4Ga.XZ262R0gBrWQy8pcG4mQT/gEfz/22CK', 'keluarga', 'pending', NULL, NULL, NULL);

-- B. Data Posyandu
INSERT INTO `posyandu` (`id_posyandu`, `nama_posyandu`, `alamat`, `rt`, `rw`, `kelurahan`, `kecamatan`, `kota`, `puskesmas_pembina`, `kontak_posyandu`) VALUES
(1, 'Posyandu Lansia Mawar Melati', 'Balai Warga RW 05, Jl. Melati Indah No. 12', '03', '05', 'Sukamaju', 'Cibeunying', 'Bandung', 'Puskesmas Sukamaju', '022-7201991');

-- C. Data Profil Kader
INSERT INTO `kader` (`id_kader`, `id_user`, `id_posyandu`, `nik`, `jabatan`, `tanggal_bergabung`, `status_kader`) VALUES
(1, 2, 1, '3273016508850001', 'Ketua', '2022-01-10', 'aktif');

-- D. Data Profil Keluarga
INSERT INTO `keluarga` (`id_keluarga`, `id_user`, `nik`, `alamat_domisili`, `rt`, `rw`, `pekerjaan`, `kontak_darurat`) VALUES
(1, 3, '3273011405820003', 'Jl. Melati No. 24, RT 03 / RW 05', '03', '05', 'Karyawan Swasta', '081234567891'),
(2, 4, '3273016011860004', 'Jl. Mawar Asri No. 8, RT 03 / RW 05', '03', '05', 'Guru', '085787654329');

-- E. Data Lansia (Binaan Posyandu, di bawah asuhan keluarga)
INSERT INTO `lansia` (`id_lansia`, `id_posyandu`, `id_keluarga`, `nik`, `no_kk`, `nama_lengkap`, `tempat_lahir`, `tanggal_lahir`, `jenis_kelamin`, `golongan_darah`, `agama`, `alamat`, `rt`, `rw`, `no_hp`, `hubungan_keluarga`, `riwayat_penyakit`, `status_lansia`) VALUES
(1, 1, 1, '3273010107540001', '3273011405820003', 'Marto Suwiryo', 'Bandung', '1954-07-01', 'L', 'O', 'Islam', 'Jl. Melati No. 24', '03', '05', '081399887766', 'Ayah Kandung', 'Riwayat Hipertensi terkontrol', 'aktif'),
(2, 1, 1, '3273014503570002', '3273011405820003', 'Sumiati Wardani', 'Tasikmalaya', '1957-03-15', 'P', 'B', 'Islam', 'Jl. Melati No. 24', '03', '05', '081399887767', 'Ibu Kandung', 'Kolesterol & Asam Urat', 'aktif');

-- F. Data Riwayat Kesehatan Lansia
-- Input dari Kader (CRUD Kader, RUD Admin, Read Keluarga)
INSERT INTO `riwayat_kesehatan` (`id_riwayat`, `id_lansia`, `id_kader`, `tanggal_pemeriksaan`, `waktu_pemeriksaan`, `berat_badan`, `tinggi_badan`, `imt`, `status_imt`, `tensi_sistolik`, `tensi_diastolik`, `status_tensi`, `detak_jantung`, `suhu_tubuh`, `gula_darah`, `status_gula_darah`, `kolesterol`, `status_kolesterol`, `asam_urat`, `status_asam_urat`, `keluhan`, `catatan_kader`, `status_kondisi`, `rujukan_puskesmas`) VALUES
(1, 1, 1, '2026-10-15', '08:45:00', 62.50, 165.00, 22.96, 'Normal', 120, 80, 'Normal', 78, 36.50, 110, 'Normal', 175, 'Normal', 5.40, 'Normal', 'Tidak ada keluhan fisik', 'Pertahankan pola jalan pagi dan minum air putih', 'Aman / Sehat', 'Tidak'),
(2, 1, 1, '2026-11-15', '09:00:00', 62.00, 165.00, 22.77, 'Normal', 122, 82, 'Normal', 76, 36.40, 115, 'Normal', 180, 'Normal', 5.60, 'Normal', 'Sedikit pegal di leher', 'Dianjurkan istirahat cukup dan peregangan ringan', 'Aman / Sehat', 'Tidak'),
(3, 2, 1, '2026-11-15', '09:30:00', 58.00, 152.00, 25.10, 'Kelebihan BB', 138, 88, 'Pra-hipertensi', 82, 36.60, 128, 'Normal', 225, 'Tinggi', 6.80, 'Tinggi', 'Sendi jari tangan sering kaku pada pagi hari', 'Konseling pembatasan makanan santan dan jeroan', 'Perlu Perhatian', 'Tidak');

SET FOREIGN_KEY_CHECKS = 1;
