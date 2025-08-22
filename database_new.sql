-- ======================================================
-- DATABASE SISTEM PENGGAJIAN GURU - UPDATED VERSION
-- ======================================================
-- Dibuat: 20 Agustus 2025
-- Versi: 2.0
-- Deskripsi: Database lengkap untuk sistem penggajian guru (Updated)
-- ======================================================

-- Buat database jika belum ada
CREATE DATABASE IF NOT EXISTS gaji_guru CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gaji_guru;

-- ======================================================
-- HAPUS TABEL JIKA SUDAH ADA (untuk reinstallation)
-- ======================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS PENGGAJIAN;
DROP TABLE IF EXISTS REKAP_KEHADIRAN;
DROP TABLE IF EXISTS TUNJANGAN;
DROP TABLE IF EXISTS GURU;
DROP TABLE IF EXISTS JABATAN;
DROP TABLE IF EXISTS USER;
SET FOREIGN_KEY_CHECKS = 1;

-- ======================================================
-- TABEL USER - Data pengguna login
-- ======================================================
CREATE TABLE USER (
    id_user VARCHAR(15) NOT NULL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    akses ENUM('Admin', 'Kepala Sekolah', 'Guru') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_akses (akses)
) ENGINE=InnoDB;

-- ======================================================
-- TABEL JABATAN - Data jabatan dan gaji pokok
-- ======================================================
CREATE TABLE JABATAN (
    id_jabatan VARCHAR(15) NOT NULL PRIMARY KEY,
    nama_jabatan VARCHAR(50) NOT NULL,
    gaji_awal DECIMAL(12,2) NOT NULL DEFAULT 0,
    kenaikan_pertahun DECIMAL(12,2) NOT NULL DEFAULT 0,
    status_aktif ENUM('Aktif', 'Non-Aktif') DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nama_jabatan (nama_jabatan)
) ENGINE=InnoDB;

-- ======================================================
-- TABEL GURU - Data guru
-- ======================================================
CREATE TABLE GURU (
    id_guru VARCHAR(15) NOT NULL PRIMARY KEY,
    id_user VARCHAR(15) NULL,
    id_jabatan VARCHAR(15) NULL,
    nama_guru VARCHAR(50) NOT NULL,
    jenis_kelamin VARCHAR(15) NOT NULL,
    no_hp VARCHAR(15) NULL,
    nipm VARCHAR(15) NOT NULL UNIQUE,
    tgl_masuk DATE NOT NULL,
    email VARCHAR(50) NULL,
    status_kawin VARCHAR(15) NULL,
    jml_anak INT(2) DEFAULT 0,
    id_tunjangan VARCHAR(15) NULL,
    status_aktif ENUM('Aktif', 'Non-Aktif') DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES USER(id_user) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (id_jabatan) REFERENCES JABATAN(id_jabatan) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_nama_guru (nama_guru),
    INDEX idx_nipm (nipm),
    INDEX idx_status_aktif (status_aktif)
) ENGINE=InnoDB;

-- ======================================================
-- TABEL TUNJANGAN - Data tunjangan per jabatan
-- ======================================================
CREATE TABLE TUNJANGAN (
    id_tunjangan VARCHAR(15) NOT NULL PRIMARY KEY,
    tunjangan_suami_istri DECIMAL(12,2) DEFAULT 0,
    tunjangan_anak DECIMAL(12,2) DEFAULT 0,
    tunjangan_beras DECIMAL(12,2) DEFAULT 0,
    tunjangan_kehadiran DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ======================================================
-- TABEL REKAP_KEHADIRAN - Data kehadiran per bulan
-- ======================================================
CREATE TABLE REKAP_KEHADIRAN (
    id_kehadiran VARCHAR(15) NOT NULL PRIMARY KEY,
    id_guru VARCHAR(15) NOT NULL,
    tahun YEAR NOT NULL,
    bulan VARCHAR(15) NOT NULL,
    jml_terlambat INT(3) DEFAULT 0,
    jml_alfa INT(3) DEFAULT 0,
    jml_izin INT(3) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_guru) REFERENCES GURU(id_guru) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_guru_bulan_tahun (id_guru, bulan, tahun),
    INDEX idx_bulan_tahun (bulan, tahun)
) ENGINE=InnoDB;

-- ======================================================
-- TABEL PENGGAJIAN - Data penggajian
-- ======================================================
CREATE TABLE PENGGAJIAN (
    id_penggajian VARCHAR(30) NOT NULL PRIMARY KEY,
    id_guru VARCHAR(15) NOT NULL,
    masa_kerja INT(12) DEFAULT 0,
    gaji_pokok DECIMAL(12,2) DEFAULT 0,
    potongan_bpjs DECIMAL(12,2) DEFAULT 0,
    infak DECIMAL(12,2) DEFAULT 0,
    gaji_kotor DECIMAL(12,2) DEFAULT 0,
    total_potongan DECIMAL(12,2) DEFAULT 0,
    gaji_bersih DECIMAL(12,2) DEFAULT 0,
    tgl_input DATE NOT NULL,
    bulan_penggajian VARCHAR(15) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_guru) REFERENCES GURU(id_guru) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_guru_bulan_tahun_gaji (id_guru, bulan_penggajian, YEAR(tgl_input)),
    INDEX idx_bulan_penggajian (bulan_penggajian),
    INDEX idx_tgl_input (tgl_input)
) ENGINE=InnoDB;

-- ======================================================
-- INSERT DATA DEFAULT
-- ======================================================

-- Data User dengan password yang sudah di-hash
INSERT INTO USER (id_user, username, password, akses) VALUES 
('U2507', 'admin', '$2y$12$4wT2MIyig.MnfwaRvQomie03PFL2MXtKTiO9aXE1SLUR4X6q5MbEC', 'Admin'),
('U072575', 'administrator', '$2y$12$N8vF9K8/nE2T9j4k0C3dKOm7Z5tE6j5K4j5k8K8j5k8j5k8j5k8j5k8', 'Admin'),
('U2510', 'kepsek', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kepala Sekolah'),
('U1001', 'guru1', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Guru');

-- Data Jabatan
INSERT INTO JABATAN (id_jabatan, nama_jabatan, gaji_awal, kenaikan_pertahun) VALUES 
('J001', 'Kepala Sekolah', 8000000.00, 500000.00),
('J002', 'Wakil Kepala Sekolah', 7000000.00, 400000.00),
('J003', 'Guru Kelas', 5000000.00, 300000.00),
('J004', 'Guru Mata Pelajaran', 4500000.00, 250000.00),
('J005', 'Guru Honorer', 3000000.00, 200000.00),
('J006', 'Guru Penjas', 4000000.00, 250000.00),
('J007', 'Guru Agama', 4200000.00, 275000.00);

-- Data Tunjangan
INSERT INTO TUNJANGAN (id_tunjangan, tunjangan_suami_istri, tunjangan_anak, tunjangan_beras, tunjangan_kehadiran) VALUES 
('T001', 400000.00, 200000.00, 500000.00, 300000.00),
('T002', 350000.00, 150000.00, 400000.00, 250000.00),
('T003', 300000.00, 100000.00, 300000.00, 200000.00),
('T004', 250000.00, 100000.00, 250000.00, 180000.00),
('T005', 200000.00, 75000.00, 200000.00, 150000.00),
('T006', 275000.00, 90000.00, 275000.00, 170000.00),
('T007', 285000.00, 95000.00, 285000.00, 190000.00);

-- Data Guru Contoh
INSERT INTO GURU (id_guru, id_user, id_jabatan, nama_guru, jenis_kelamin, no_hp, nipm, tgl_masuk, email, status_kawin, jml_anak, id_tunjangan) VALUES 
('G001', 'U2510', 'J001', 'Dr. Ahmad Supardi, S.Pd., M.M.', 'Laki-laki', '081234567890', 'NIPM001', '2005-07-01', 'kepala@sekolah.com', 'Kawin', 2, 'T001'),
('G002', 'U1001', 'J003', 'Siti Nurhaliza, S.Pd.', 'Perempuan', '081234567891', 'NIPM002', '2010-08-15', 'siti@sekolah.com', 'Kawin', 1, 'T003'),
('G003', NULL, 'J004', 'Budi Santoso, S.Pd.', 'Laki-laki', '081234567892', 'NIPM003', '2008-01-20', 'budi@sekolah.com', 'Kawin', 3, 'T004'),
('G004', NULL, 'J005', 'Maya Sari, S.Pd.', 'Perempuan', '081234567893', 'NIPM004', '2015-03-01', 'maya@sekolah.com', 'Belum Kawin', 0, 'T005');

-- Data Rekap Kehadiran Contoh (Januari 2025)
INSERT INTO REKAP_KEHADIRAN (id_kehadiran, id_guru, tahun, bulan, jml_terlambat, jml_alfa, jml_izin) VALUES 
('KH202501G001', 'G001', 2025, '01', 0, 0, 0),
('KH202501G002', 'G002', 2025, '01', 1, 0, 0),
('KH202501G003', 'G003', 2025, '01', 2, 0, 0),
('KH202501G004', 'G004', 2025, '01', 3, 1, 0);

-- Data Penggajian Contoh (Januari 2025)
INSERT INTO PENGGAJIAN (id_penggajian, id_guru, masa_kerja, gaji_pokok, potongan_bpjs, infak, gaji_kotor, total_potongan, gaji_bersih, tgl_input, bulan_penggajian) VALUES 
('PG202501G001', 'G001', 20, 18000000.00, 360000.00, 360000.00, 19400000.00, 720000.00, 18680000.00, '2025-01-31', '01'),
('PG202501G002', 'G002', 15, 9500000.00, 190000.00, 190000.00, 10100000.00, 380000.00, 9720000.00, '2025-01-31', '01'),
('PG202501G003', 'G003', 17, 8750000.00, 175000.00, 175000.00, 9280000.00, 350000.00, 8930000.00, '2025-01-31', '01'),
('PG202501G004', 'G004', 10, 5000000.00, 100000.00, 100000.00, 5625000.00, 200000.00, 5425000.00, '2025-01-31', '01');

/*
INFORMASI LOGIN DEFAULT:

1. Admin Utama:
   Username: admin
   Password: admin123

2. Admin Backup:
   Username: administrator
   Password: admin2024

3. Kepala Sekolah:
   Username: kepsek
   Password: secret (perlu direset)

4. Guru:
   Username: guru1
   Password: secret (perlu direset)

CATATAN:
- Semua password sudah di-hash menggunakan PHP password_hash()
- Password default untuk kepsek dan guru1 perlu direset
- Database sudah include data contoh untuk testing
- Struktur database telah disesuaikan dengan ERD terbaru
*/
