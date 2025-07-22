-- Tabel users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kepala_sekolah', 'guru') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel jabatan
CREATE TABLE jabatan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_jabatan VARCHAR(50) NOT NULL UNIQUE,
    gaji_pokok DECIMAL(10, 2) NOT NULL,
    kenaikan_gaji_tahunan DECIMAL(10,2) NOT NULL DEFAULT 0
);

-- Tabel guru
CREATE TABLE guru (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    nip VARCHAR(20) UNIQUE,
    nama_lengkap VARCHAR(100) NOT NULL,
    alamat TEXT,
    no_telepon VARCHAR(20),
    email VARCHAR(100) UNIQUE,
    tanggal_masuk DATE,
    jabatan_id INT,
    status_pernikahan ENUM('menikah', 'belum_menikah'),
    jumlah_anak INT DEFAULT 0,
    status ENUM('Aktif', 'Tidak Aktif') DEFAULT 'Aktif',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (jabatan_id) REFERENCES jabatan(id)
);

-- Tabel tunjangan
CREATE TABLE tunjangan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_tunjangan VARCHAR(100) NOT NULL UNIQUE,
    jumlah DECIMAL(10, 2) NOT NULL,
    jenis ENUM('tetap', 'variabel')
);

-- Tabel pivot guru_tunjangan
CREATE TABLE guru_tunjangan (
    guru_id INT,
    tunjangan_id INT,
    PRIMARY KEY (guru_id, tunjangan_id),
    FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE,
    FOREIGN KEY (tunjangan_id) REFERENCES tunjangan(id) ON DELETE CASCADE
);

-- Tabel potongan
CREATE TABLE potongan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_potongan VARCHAR(100) NOT NULL UNIQUE,
    jumlah DECIMAL(10, 2) NOT NULL,
    jenis ENUM('tetap', 'variabel')
);

-- Tabel pivot guru_potongan
CREATE TABLE guru_potongan (
    guru_id INT,
    potongan_id INT,
    PRIMARY KEY (guru_id, potongan_id),
    FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE,
    FOREIGN KEY (potongan_id) REFERENCES potongan(id) ON DELETE CASCADE
);

-- Tabel absensi
CREATE TABLE absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guru_id INT NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('hadir', 'izin', 'sakit', 'alpha') NOT NULL,
    keterangan TEXT,
    FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE
);

-- Tabel gaji
CREATE TABLE gaji (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guru_id INT NOT NULL,
    periode_gaji DATE NOT NULL,
    gaji_pokok DECIMAL(10, 2) NOT NULL,
    total_tunjangan DECIMAL(10, 2) NOT NULL,
    total_potongan DECIMAL(10, 2) NOT NULL,
    gaji_bersih DECIMAL(10, 2) NOT NULL,
    tanggal_pembayaran DATE,
    status_pembayaran ENUM('belum_dibayar', 'sudah_dibayar') DEFAULT 'belum_dibayar',
    FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE,
    UNIQUE (guru_id, periode_gaji)
);

-- Tabel gaji_detail
CREATE TABLE gaji_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gaji_id INT NOT NULL,
    komponen_nama VARCHAR(100) NOT NULL,
    komponen_jenis ENUM('tunjangan', 'potongan') NOT NULL,
    jumlah DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (gaji_id) REFERENCES gaji(id) ON DELETE CASCADE
); 