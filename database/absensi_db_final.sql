-- ============================================================
-- DATABASE: absensi_db  (VERSI LENGKAP)
-- Website Absensi Siswa Berbasis QR Code
-- Password default: admin123
-- ============================================================

CREATE DATABASE IF NOT EXISTS absensi_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE absensi_db;

-- ============================================================
-- TABEL: users
-- ============================================================
DROP TABLE IF EXISTS absensi;
DROP TABLE IF EXISTS siswa;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    nama        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) DEFAULT NULL,
    role        ENUM('admin','guru') NOT NULL DEFAULT 'guru',
    aktif       TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL: siswa
-- ============================================================
CREATE TABLE siswa (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nis           VARCHAR(20)  NOT NULL UNIQUE,
    nama          VARCHAR(100) NOT NULL,
    kelas         VARCHAR(20)  NOT NULL,
    jurusan       VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L','P') NOT NULL,
    foto          VARCHAR(255) DEFAULT NULL,
    qr_code       VARCHAR(255) DEFAULT NULL,
    aktif         TINYINT(1) NOT NULL DEFAULT 1,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kelas (kelas),
    INDEX idx_nis   (nis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL: absensi
-- ============================================================
CREATE TABLE absensi (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    siswa_id     INT UNSIGNED NOT NULL,
    tanggal      DATE         NOT NULL,
    jam_masuk    TIME         DEFAULT NULL,
    status       ENUM('hadir','izin','sakit','alpha') NOT NULL DEFAULT 'alpha',
    keterangan   TEXT         DEFAULT NULL,
    dicatat_oleh INT UNSIGNED DEFAULT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_siswa_tanggal (siswa_id, tanggal),
    INDEX idx_tanggal (tanggal),
    INDEX idx_status  (status),
    FOREIGN KEY (siswa_id)     REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (dicatat_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DATA AWAL USERS
-- Username : admin  | Password : admin123
-- Username : guru1  | Password : admin123
-- ============================================================
INSERT INTO users (username, password, nama, email, role) VALUES
(
    'admin',
    '$2y$10$TKh8H1.PfEOeAc.MaQaqQuB5DFT1tH4Z9FTQnX4wJFIuuvH7rLeKO',
    'Administrator',
    'admin@sekolah.sch.id',
    'admin'
),
(
    'guru1',
    '$2y$10$TKh8H1.PfEOeAc.MaQaqQuB5DFT1tH4Z9FTQnX4wJFIuuvH7rLeKO',
    'Budi Santoso, S.Pd',
    'budi@sekolah.sch.id',
    'guru'
);

-- ============================================================
-- DATA AWAL SISWA
-- ============================================================
INSERT INTO siswa (nis, nama, kelas, jurusan, jenis_kelamin) VALUES
('2024001', 'Andi Pratama',         'X-A',  'Teknik Informatika', 'L'),
('2024002', 'Sari Dewi Lestari',    'X-A',  'Teknik Informatika', 'P'),
('2024003', 'Budi Setiawan',        'X-B',  'Akuntansi',          'L'),
('2024004', 'Rina Marlina',         'X-B',  'Akuntansi',          'P'),
('2024005', 'Doni Kurniawan',       'XI-A', 'Teknik Informatika', 'L'),
('2024006', 'Maya Sari Indah',      'XI-A', 'Teknik Informatika', 'P'),
('2024007', 'Fajar Nugraha',        'XI-B', 'Akuntansi',          'L'),
('2024008', 'Lestari Wulandari',    'XI-B', 'Akuntansi',          'P'),
('2024009', 'Rizal Firmansyah',     'XII-A','Teknik Informatika', 'L'),
('2024010', 'Dewi Rahayu',          'XII-A','Teknik Informatika', 'P');

-- ============================================================
-- DATA CONTOH ABSENSI (opsional – bisa dihapus)
-- ============================================================
-- Absensi hari ini (ganti tanggal sesuai kebutuhan)
INSERT INTO absensi (siswa_id, tanggal, jam_masuk, status, dicatat_oleh) VALUES
(1, CURDATE(), '07:15:00', 'hadir', 1),
(2, CURDATE(), '07:20:00', 'hadir', 1),
(3, CURDATE(), NULL,       'izin',  1),
(4, CURDATE(), '07:18:00', 'hadir', 1),
(5, CURDATE(), NULL,       'sakit', 1);

-- ============================================================
-- CATATAN PENTING
-- ============================================================
-- 1. Import file ini di phpMyAdmin: Database > Import > pilih file ini
-- 2. Password default semua user = "admin123"
-- 3. Untuk mengubah password, jalankan:
--    http://localhost/absensi-qr/database/generate_hash.php
--    Lalu UPDATE users SET password='[hash baru]' WHERE username='admin';
-- 4. Hapus file generate_hash.php setelah selesai digunakan
-- ============================================================
