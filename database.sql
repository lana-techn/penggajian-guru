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