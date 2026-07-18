-- ============================================================
-- DATABASE: absensi_db
-- Website Absensi Siswa Berbasis QR Code
-- ============================================================

CREATE DATABASE IF NOT EXISTS absensi_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE absensi_db;

-- ============================================================
-- TABEL: users (Admin & Guru)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,            -- bcrypt hash
    nama        VARCHAR(100) NOT NULL,
    email       VARCHAR(100),
    role        ENUM('admin','guru') NOT NULL DEFAULT 'guru',
    aktif       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: siswa
-- ============================================================
CREATE TABLE IF NOT EXISTS siswa (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nis         VARCHAR(20)  NOT NULL UNIQUE,     -- Nomor Induk Siswa
    nama        VARCHAR(100) NOT NULL,
    kelas       VARCHAR(20)  NOT NULL,
    jurusan     VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L','P') NOT NULL,
    foto        VARCHAR(255) DEFAULT NULL,        -- path foto
    qr_code     VARCHAR(255) DEFAULT NULL,        -- path file QR
    aktif       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: absensi
-- ============================================================
CREATE TABLE IF NOT EXISTS absensi (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id    INT          NOT NULL,
    tanggal     DATE         NOT NULL,
    jam_masuk   TIME         DEFAULT NULL,
    status      ENUM('hadir','izin','sakit','alpha') NOT NULL DEFAULT 'alpha',
    keterangan  TEXT         DEFAULT NULL,
    dicatat_oleh INT         DEFAULT NULL,        -- user_id guru/admin
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Satu siswa hanya boleh 1 absensi per hari
    UNIQUE KEY uq_siswa_tanggal (siswa_id, tanggal),
    FOREIGN KEY (siswa_id)      REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (dicatat_oleh)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- INDEX untuk performa query
-- ============================================================
CREATE INDEX idx_absensi_tanggal  ON absensi(tanggal);
CREATE INDEX idx_absensi_status   ON absensi(status);
CREATE INDEX idx_siswa_kelas      ON siswa(kelas);
CREATE INDEX idx_siswa_nis        ON siswa(nis);

-- ============================================================
-- DATA AWAL: Admin default
-- password: admin123  (bcrypt hash)
-- ============================================================
INSERT INTO users (username, password, nama, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@sekolah.sch.id', 'admin'),
('guru1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Santoso', 'budi@sekolah.sch.id', 'guru');

-- ============================================================
-- DATA AWAL: Contoh siswa
-- ============================================================
INSERT INTO siswa (nis, nama, kelas, jurusan, jenis_kelamin) VALUES
('2024001', 'Andi Pratama',      'X-A', 'Teknik Informatika', 'L'),
('2024002', 'Sari Dewi',         'X-A', 'Teknik Informatika', 'P'),
('2024003', 'Budi Setiawan',     'X-B', 'Akuntansi',          'L'),
('2024004', 'Rina Marlina',      'X-B', 'Akuntansi',          'P'),
('2024005', 'Doni Kurniawan',    'XI-A','Teknik Informatika', 'L'),
('2024006', 'Maya Sari',         'XI-A','Teknik Informatika', 'P'),
('2024007', 'Fajar Nugraha',     'XI-B','Akuntansi',          'L'),
('2024008', 'Lestari Wulandari', 'XI-B','Akuntansi',          'P');

-- ============================================================
-- CATATAN INSTALASI:
-- 1. Import file ini ke phpMyAdmin atau jalankan di MySQL CLI
-- 2. Password default admin & guru1 = "password"
--    (hash di atas adalah hash Laravel default untuk "password")
--    Untuk password "admin123" gunakan fungsi generateHash.php
-- ============================================================
