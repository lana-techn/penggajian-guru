-- ======================================================
-- DATABASE SISTEM PENGGAJIAN GURU
-- ======================================================
-- Dibuat: 25 Juli 2025
-- Versi: 1.0
-- Deskripsi: Database lengkap untuk sistem penggajian guru
-- ======================================================

-- Buat database jika belum ada
CREATE DATABASE IF NOT EXISTS gaji_guru CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gaji_guru;

-- ======================================================
-- HAPUS TABEL JIKA SUDAH ADA (untuk reinstallation)
-- ======================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS Penggajian;
DROP TABLE IF EXISTS Rekap_Kehadiran;
DROP TABLE IF EXISTS Potongan;
DROP TABLE IF EXISTS Tunjangan;
DROP TABLE IF EXISTS Guru;
DROP TABLE IF EXISTS Jabatan;
DROP TABLE IF EXISTS User;
SET FOREIGN_KEY_CHECKS = 1;

-- ======================================================
-- TABEL USER - Data pengguna login
-- ======================================================
CREATE TABLE User (
    id_user VARCHAR(10) NOT NULL PRIMARY KEY,
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
CREATE TABLE Jabatan (
    id_jabatan VARCHAR(10) NOT NULL PRIMARY KEY,
    nama_jabatan VARCHAR(100) NOT NULL,
    gaji_awal DECIMAL(15,2) NOT NULL DEFAULT 0,
    status_aktif ENUM('Aktif', 'Non-Aktif') DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nama_jabatan (nama_jabatan)
) ENGINE=InnoDB;

-- ======================================================
-- TABEL GURU - Data guru
-- ======================================================
CREATE TABLE Guru (
    id_guru VARCHAR(10) NOT NULL PRIMARY KEY,
    id_user VARCHAR(10) NULL,
    id_jabatan VARCHAR(10) NULL,
    nipm VARCHAR(20) NOT NULL UNIQUE,
    nama_guru VARCHAR(100) NOT NULL,
    tempat_lahir VARCHAR(50) NULL,
    tgl_lahir DATE NULL,
    jenis_kelamin ENUM('Laki-laki', 'Perempuan') NULL,
    agama VARCHAR(20) NULL,
    status_kawin ENUM('Belum Kawin', 'Kawin', 'Cerai') DEFAULT 'Belum Kawin',
    jml_anak INT DEFAULT 0,
    alamat TEXT NULL,
    no_hp VARCHAR(15) NULL,
    email VARCHAR(100) NULL,
    tgl_masuk DATE NOT NULL,
    foto VARCHAR(255) NULL,
    status_aktif ENUM('Aktif', 'Non-Aktif') DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES User(id_user) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (id_jabatan) REFERENCES Jabatan(id_jabatan) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_nama_guru (nama_guru),
    INDEX idx_nipm (nipm),
    INDEX idx_status_aktif (status_aktif)
) ENGINE=InnoDB;

-- ======================================================
-- TABEL TUNJANGAN - Data tunjangan per jabatan
-- ======================================================
CREATE TABLE Tunjangan (
    id_tunjangan VARCHAR(10) NOT NULL PRIMARY KEY,
    id_jabatan VARCHAR(10) NOT NULL,
    tunjangan_beras DECIMAL(15,2) DEFAULT 0,
    tunjangan_suami_istri DECIMAL(15,2) DEFAULT 0,
    tunjangan_anak DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_jabatan) REFERENCES Jabatan(id_jabatan) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_jabatan_tunjangan (id_jabatan)
) ENGINE=InnoDB;

-- ======================================================
-- TABEL POTONGAN - Data potongan per jabatan
-- ======================================================
CREATE TABLE Potongan (
    id_potongan VARCHAR(10) NOT NULL PRIMARY KEY,
    id_jabatan VARCHAR(10) NOT NULL,
    potongan_bpjs DECIMAL(5,2) DEFAULT 0 COMMENT 'Persentase BPJS (%)',
    infak DECIMAL(5,2) DEFAULT 0 COMMENT 'Persentase Infak (%)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_jabatan) REFERENCES Jabatan(id_jabatan) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_jabatan_potongan (id_jabatan)
) ENGINE=InnoDB;

-- ======================================================
-- TABEL REKAP_KEHADIRAN - Data kehadiran per bulan
-- ======================================================
CREATE TABLE Rekap_Kehadiran (
    id_rekap VARCHAR(20) NOT NULL PRIMARY KEY,
    id_guru VARCHAR(10) NOT NULL,
    bulan VARCHAR(2) NOT NULL,
    tahun YEAR NOT NULL,
    jml_hadir INT DEFAULT 0,
    jml_terlambat INT DEFAULT 0,
    jml_izin INT DEFAULT 0,
    jml_alfa INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_guru) REFERENCES Guru(id_guru) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_guru_bulan_tahun (id_guru, bulan, tahun),
    INDEX idx_bulan_tahun (bulan, tahun)
) ENGINE=InnoDB;

-- ======================================================
-- TABEL PENGGAJIAN - Data penggajian
-- ======================================================
CREATE TABLE Penggajian (
    id_penggajian VARCHAR(20) NOT NULL PRIMARY KEY,
    no_slip_gaji VARCHAR(20) NULL,
    id_guru VARCHAR(10) NOT NULL,
    masa_kerja INT DEFAULT 0,
    gaji_pokok DECIMAL(15,2) DEFAULT 0,
    tunjangan_beras DECIMAL(15,2) DEFAULT 0,
    tunjangan_kehadiran DECIMAL(15,2) DEFAULT 0,
    tunjangan_suami_istri DECIMAL(15,2) DEFAULT 0,
    tunjangan_anak DECIMAL(15,2) DEFAULT 0,
    potongan_bpjs DECIMAL(15,2) DEFAULT 0,
    infak DECIMAL(15,2) DEFAULT 0,
    gaji_kotor DECIMAL(15,2) DEFAULT 0,
    total_potongan DECIMAL(15,2) DEFAULT 0,
    gaji_bersih DECIMAL(15,2) DEFAULT 0,
    tgl_input DATE NOT NULL,
    bulan_penggajian VARCHAR(2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_guru) REFERENCES Guru(id_guru) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_guru_bulan_tahun_gaji (id_guru, bulan_penggajian, YEAR(tgl_input)),
    INDEX idx_bulan_penggajian (bulan_penggajian),
    INDEX idx_tgl_input (tgl_input)
) ENGINE=InnoDB;

-- ======================================================
-- INSERT DATA DEFAULT
-- ======================================================

-- Data User dengan password yang sudah di-hash
INSERT INTO User (id_user, username, password, akses) VALUES 
('U2507', 'admin', '$2y$12$4wT2MIyig.MnfwaRvQomie03PFL2MXtKTiO9aXE1SLUR4X6q5MbEC', 'Admin'),
('U072575', 'administrator', '$2y$12$N8vF9K8/nE2T9j4k0C3dKOm7Z5tE6j5K4j5k8K8j5k8j5k8j5k8j5k8', 'Admin'),
('U2510', 'kepsek', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kepala Sekolah'),
('U1001', 'guru1', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Guru');

-- Data Jabatan
INSERT INTO Jabatan (id_jabatan, nama_jabatan, gaji_awal) VALUES 
('J001', 'Kepala Sekolah', 8000000.00),
('J002', 'Wakil Kepala Sekolah', 7000000.00),
('J003', 'Guru Kelas', 5000000.00),
('J004', 'Guru Mata Pelajaran', 4500000.00),
('J005', 'Guru Honorer', 3000000.00),
('J006', 'Guru Penjas', 4000000.00),
('J007', 'Guru Agama', 4200000.00);

-- Data Tunjangan per Jabatan
INSERT INTO Tunjangan (id_tunjangan, id_jabatan, tunjangan_beras, tunjangan_suami_istri, tunjangan_anak) VALUES 
('T001', 'J001', 500000.00, 400000.00, 200000.00),
('T002', 'J002', 400000.00, 350000.00, 150000.00),
('T003', 'J003', 300000.00, 300000.00, 100000.00),
('T004', 'J004', 250000.00, 250000.00, 100000.00),
('T005', 'J005', 200000.00, 200000.00, 75000.00),
('T006', 'J006', 275000.00, 275000.00, 90000.00),
('T007', 'J007', 285000.00, 285000.00, 95000.00);

-- Data Potongan per Jabatan
INSERT INTO Potongan (id_potongan, id_jabatan, potongan_bpjs, infak) VALUES 
('P001', 'J001', 2.00, 2.00),
('P002', 'J002', 2.00, 2.00),
('P003', 'J003', 2.00, 2.00),
('P004', 'J004', 2.00, 2.00),
('P005', 'J005', 2.00, 2.00),
('P006', 'J006', 2.00, 2.00),
('P007', 'J007', 2.00, 2.00);

-- Data Guru Contoh
INSERT INTO Guru (id_guru, id_user, id_jabatan, nipm, nama_guru, tempat_lahir, tgl_lahir, jenis_kelamin, agama, status_kawin, jml_anak, alamat, no_hp, email, tgl_masuk) VALUES 
('G001', 'U2510', 'J001', 'NIPM001', 'Dr. Ahmad Supardi, S.Pd., M.M.', 'Jakarta', '1975-05-15', 'Laki-laki', 'Islam', 'Kawin', 2, 'Jl. Pendidikan No. 123, Jakarta', '081234567890', 'kepala@sekolah.com', '2005-07-01'),
('G002', 'U1001', 'J003', 'NIPM002', 'Siti Nurhaliza, S.Pd.', 'Yogyakarta', '1985-08-20', 'Perempuan', 'Islam', 'Kawin', 1, 'Jl. Guru No. 456, Yogyakarta', '081234567891', 'siti@sekolah.com', '2010-08-15'),
('G003', NULL, 'J004', 'NIPM003', 'Budi Santoso, S.Pd.', 'Bandung', '1982-03-10', 'Laki-laki', 'Islam', 'Kawin', 3, 'Jl. Matematika No. 789, Bandung', '081234567892', 'budi@sekolah.com', '2008-01-20'),
('G004', NULL, 'J005', 'NIPM004', 'Maya Sari, S.Pd.', 'Surabaya', '1990-12-05', 'Perempuan', 'Kristen', 'Belum Kawin', 0, 'Jl. Honorer No. 321, Surabaya', '081234567893', 'maya@sekolah.com', '2015-03-01');

-- Data Rekap Kehadiran Contoh (Januari 2025)
INSERT INTO Rekap_Kehadiran (id_rekap, id_guru, bulan, tahun, jml_hadir, jml_terlambat, jml_izin, jml_alfa) VALUES 
('RK202501G001', 'G001', '01', 2025, 22, 0, 0, 0),
('RK202501G002', 'G002', '01', 2025, 21, 1, 0, 0),
('RK202501G003', 'G003', '01', 2025, 20, 2, 0, 0),
('RK202501G004', 'G004', '01', 2025, 19, 3, 1, 0);

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
*/

-- Tabel User
CREATE TABLE User (
    id_user VARCHAR(15) PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL, -- hash password
    akses ENUM('Admin', 'Kepala Sekolah', 'Guru') NOT NULL
);

-- Tabel Jabatan
CREATE TABLE Jabatan (
    id_jabatan VARCHAR(15) PRIMARY KEY,
    nama_jabatan VARCHAR(50) NOT NULL,
    gaji_awal DECIMAL(12,2) NOT NULL,
    kenaikan_pertahun DECIMAL(12,2) NOT NULL
);

-- Tabel Guru
CREATE TABLE Guru (
    id_guru VARCHAR(15) PRIMARY KEY,
    id_user VARCHAR(15) NOT NULL,
    id_jabatan VARCHAR(15) NOT NULL,
    nama_guru VARCHAR(50) NOT NULL,
    jenis_kelamin VARCHAR(15) NOT NULL,
    no_hp VARCHAR(15),
    nipm VARCHAR(15),
    tgl_masuk DATE NOT NULL,
    email VARCHAR(50),
    status_kawin VARCHAR(15),
    jml_anak INT(2) DEFAULT 0,
    FOREIGN KEY (id_user) REFERENCES User(id_user),
    FOREIGN KEY (id_jabatan) REFERENCES Jabatan(id_jabatan)
);

-- Tabel Rekap_Kehadiran
CREATE TABLE Rekap_Kehadiran (
    id_kehadiran VARCHAR(15) PRIMARY KEY,
    id_guru VARCHAR(15) NOT NULL,
    tahun YEAR NOT NULL,
    bulan VARCHAR(15) NOT NULL,
    jml_terlambat INT(3) DEFAULT 0,
    jml_alfa INT(3) DEFAULT 0,
    jml_izin INT(3) DEFAULT 0,
    FOREIGN KEY (id_guru) REFERENCES Guru(id_guru)
);

-- Tabel Potongan
CREATE TABLE Potongan (
    id_potongan VARCHAR(15) PRIMARY KEY,
    id_jabatan VARCHAR(15) NOT NULL,
    potongan_bpjs DECIMAL(12,2) DEFAULT 0,
    infak DECIMAL(12,2) DEFAULT 0,
    FOREIGN KEY (id_jabatan) REFERENCES Jabatan(id_jabatan)
);

-- Tabel Tunjangan
CREATE TABLE Tunjangan (
    id_tunjangan VARCHAR(15) PRIMARY KEY,
    id_jabatan VARCHAR(15) NOT NULL,
    tunjangan_suami_istri DECIMAL(12,2) DEFAULT 0,
    tunjangan_anak DECIMAL(12,2) DEFAULT 0,
    tunjangan_beras DECIMAL(12,2) DEFAULT 0,
    FOREIGN KEY (id_jabatan) REFERENCES Jabatan(id_jabatan)
);

-- Tabel Penggajian
CREATE TABLE Penggajian (
    id_penggajian VARCHAR(15) PRIMARY KEY,
    id_guru VARCHAR(15) NOT NULL,
    masa_kerja INT(2) DEFAULT 0,
    gaji_pokok DECIMAL(12,2) DEFAULT 0,
    tunjangan_beras DECIMAL(12,2) DEFAULT 0,
    tunjangan_kehadiran DECIMAL(12,2) DEFAULT 0,
    tunjangan_suami_istri DECIMAL(12,2) DEFAULT 0,
    tunjangan_anak DECIMAL(12,2) DEFAULT 0,
    potongan_bpjs DECIMAL(12,2) DEFAULT 0,
    infak DECIMAL(12,2) DEFAULT 0,
    gaji_kotor DECIMAL(12,2) DEFAULT 0,
    total_potongan DECIMAL(12,2) DEFAULT 0,
    gaji_bersih DECIMAL(12,2) DEFAULT 0,
    tgl_input DATE NOT NULL,
    bulan_penggajian VARCHAR(15) NOT NULL,
    FOREIGN KEY (id_guru) REFERENCES Guru(id_guru)
);

-- Data awal user admin & kepsek
INSERT INTO User (id_user, username, password, akses) VALUES
('U2507', 'admin', '$2y$12$.0NkcIbtt1P0CpIZ99YryOuLq7IxLt8D..NaxQDT0bsHt/77Mi6pa', 'Admin'),
('U2510', 'kepsek', '$2y$12$7MBtqZRQM6Gz2wyDijB66O1fn4qe.n4ugwtSdIGAkJfbxGoCRMCru', 'Kepala Sekolah');

-- DATA DUMMY UNTUK TESTING DAN PENGEMBANGAN

-- Data Dummy Jabatan
INSERT INTO Jabatan (id_jabatan, nama_jabatan, gaji_awal, kenaikan_pertahun) VALUES
('J001', 'Guru Kelas', 3000000, 200000),
('J002', 'Guru Mapel', 3200000, 250000),
('J003', 'Wakil Kepala', 4000000, 300000);

-- Data Dummy User Guru
INSERT INTO User (id_user, username, password, akses) VALUES
('U1001', 'guru1', '$2y$12$Ex2JbADbytqzBaikoHu53.0xGaDJZ6kjFwHZXi70KFB7Qb55FvjuW', 'Guru');

-- Data Dummy Guru
INSERT INTO Guru (id_guru, id_user, id_jabatan, nama_guru, jenis_kelamin, no_hp, nipm, tgl_masuk, email, status_kawin, jml_anak) VALUES
('G001', 'U1001', 'J001', 'Ahmad Sulaiman', 'Laki-laki', '081234567890', 'NIPM001', '2018-07-01', 'ahmad@guru.com', 'Menikah', 2);

-- Data Dummy Tunjangan
INSERT INTO Tunjangan (id_tunjangan, id_jabatan, tunjangan_suami_istri, tunjangan_anak, tunjangan_beras) VALUES
('T001', 'J001', 300000, 150000, 100000),
('T002', 'J002', 350000, 200000, 120000),
('T003', 'J003', 400000, 250000, 150000);

-- Data Dummy Potongan
INSERT INTO Potongan (id_potongan, id_jabatan, potongan_bpjs, infak) VALUES
('P001', 'J001', 50000, 20000),
('P002', 'J002', 60000, 25000),
('P003', 'J003', 70000, 30000);

-- Data Dummy Rekap Kehadiran
INSERT INTO Rekap_Kehadiran (id_kehadiran, id_guru, tahun, bulan, jml_terlambat, jml_alfa, jml_izin) VALUES
('KH001', 'G001', 2024, '01', 2, 0, 1);

-- Data Dummy Penggajian (Contoh, bisa di-generate otomatis dari proses gaji)
INSERT INTO Penggajian (id_penggajian, id_guru, masa_kerja, gaji_pokok, tunjangan_beras, tunjangan_kehadiran, tunjangan_suami_istri, tunjangan_anak, potongan_bpjs, infak, gaji_kotor, total_potongan, gaji_bersih, tgl_input, bulan_penggajian) VALUES
('PG001', 'G001', 6, 4200000, 100000, 200000, 300000, 150000, 50000, 20000, 4950000, 70000, 4880000, '2024-01-31', '01');