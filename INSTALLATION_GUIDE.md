# 📋 Panduan Instalasi Sistem Penggajian Guru

## 🎯 Daftar Isi
1. [Persyaratan Sistem](#persyaratan-sistem)
2. [Instalasi XAMPP](#instalasi-xampp)
3. [Setup Database](#setup-database)
4. [Instalasi Project](#instalasi-project)
5. [Konfigurasi](#konfigurasi)
6. [Menjalankan Aplikasi](#menjalankan-aplikasi)
7. [Login Default](#login-default)
8. [Troubleshooting](#troubleshooting)

---

## 🔧 Persyaratan Sistem

### Minimum Requirements:
- **PHP:** 7.4 atau lebih tinggi
- **MySQL:** 5.7 atau lebih tinggi (atau MariaDB 10.2+)
- **Web Server:** Apache 2.4+
- **RAM:** 2GB minimum
- **Storage:** 500MB ruang kosong

### Recommended:
- **PHP:** 8.0+
- **MySQL:** 8.0+
- **RAM:** 4GB+
- **Storage:** 1GB+

---

## 🚀 Instalasi XAMPP

### Windows:
1. Download XAMPP dari [https://www.apachefriends.org](https://www.apachefriends.org)
2. Jalankan installer sebagai Administrator
3. Pilih komponen: **Apache**, **MySQL**, **PHP**, **phpMyAdmin**
4. Install ke direktori `C:\xampp`
5. Jalankan XAMPP Control Panel
6. Start service **Apache** dan **MySQL**

### macOS:
1. Download XAMPP untuk macOS
2. Mount file `.dmg` dan drag XAMPP ke Applications
3. Buka Terminal dan jalankan:
   ```bash
   sudo /Applications/XAMPP/xamppfiles/xampp start
   ```
4. Atau gunakan XAMPP Manager untuk start/stop services

### Linux (Ubuntu/Debian):
```bash
# Download XAMPP
wget https://www.apachefriends.org/xampp-files/8.2.4/xampp-linux-x64-8.2.4-0-installer.run

# Beri permission execute
chmod +x xampp-linux-x64-8.2.4-0-installer.run

# Install
sudo ./xampp-linux-x64-8.2.4-0-installer.run

# Start services
sudo /opt/lampp/lampp start
```

---

## 🗄️ Setup Database

### 1. Akses phpMyAdmin
- Buka browser: `http://localhost/phpmyadmin`
- Login dengan user: `root`, password: (kosong)

### 2. Buat Database
```sql
CREATE DATABASE gaji_guru CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Import Database Structure
Jalankan query SQL berikut di phpMyAdmin:

```sql
USE gaji_guru;

-- Tabel User
CREATE TABLE User (
    id_user VARCHAR(10) PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    akses ENUM('Admin', 'Kepala Sekolah', 'Guru') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Jabatan
CREATE TABLE Jabatan (
    id_jabatan VARCHAR(10) PRIMARY KEY,
    nama_jabatan VARCHAR(100) NOT NULL,
    gaji_awal DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Guru
CREATE TABLE Guru (
    id_guru VARCHAR(10) PRIMARY KEY,
    id_user VARCHAR(10),
    id_jabatan VARCHAR(10),
    nipm VARCHAR(20) UNIQUE NOT NULL,
    nama_guru VARCHAR(100) NOT NULL,
    tempat_lahir VARCHAR(50),
    tgl_lahir DATE,
    jenis_kelamin ENUM('Laki-laki', 'Perempuan'),
    agama VARCHAR(20),
    status_kawin ENUM('Belum Kawin', 'Kawin', 'Cerai'),
    jml_anak INT DEFAULT 0,
    alamat TEXT,
    no_hp VARCHAR(15),
    email VARCHAR(100),
    tgl_masuk DATE NOT NULL,
    foto VARCHAR(255),
    status_aktif ENUM('Aktif', 'Non-Aktif') DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES User(id_user) ON DELETE SET NULL,
    FOREIGN KEY (id_jabatan) REFERENCES Jabatan(id_jabatan) ON DELETE SET NULL
);

-- Tabel Tunjangan
CREATE TABLE Tunjangan (
    id_tunjangan VARCHAR(10) PRIMARY KEY,
    id_jabatan VARCHAR(10),
    tunjangan_beras DECIMAL(15,2) DEFAULT 0,
    tunjangan_suami_istri DECIMAL(15,2) DEFAULT 0,
    tunjangan_anak DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_jabatan) REFERENCES Jabatan(id_jabatan) ON DELETE CASCADE
);

-- Tabel Potongan
CREATE TABLE Potongan (
    id_potongan VARCHAR(10) PRIMARY KEY,
    id_jabatan VARCHAR(10),
    potongan_bpjs DECIMAL(5,2) DEFAULT 0, -- dalam persen
    infak DECIMAL(5,2) DEFAULT 0, -- dalam persen
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_jabatan) REFERENCES Jabatan(id_jabatan) ON DELETE CASCADE
);

-- Tabel Rekap_Kehadiran
CREATE TABLE Rekap_Kehadiran (
    id_rekap VARCHAR(20) PRIMARY KEY,
    id_guru VARCHAR(10),
    bulan VARCHAR(2) NOT NULL,
    tahun YEAR NOT NULL,
    jml_hadir INT DEFAULT 0,
    jml_terlambat INT DEFAULT 0,
    jml_izin INT DEFAULT 0,
    jml_alfa INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_guru) REFERENCES Guru(id_guru) ON DELETE CASCADE
);

-- Tabel Penggajian
CREATE TABLE Penggajian (
    id_penggajian VARCHAR(20) PRIMARY KEY,
    no_slip_gaji VARCHAR(20),
    id_guru VARCHAR(10),
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
    FOREIGN KEY (id_guru) REFERENCES Guru(id_guru) ON DELETE CASCADE
);
```

### 4. Insert Data Default
```sql
-- Insert Users
INSERT INTO User (id_user, username, password, akses) VALUES 
('U2507', 'admin', '$2y$12$4wT2MIyig.MnfwaRvQomie03PFL2MXtKTiO9aXE1SLUR4X6q5MbEC', 'Admin'),
('U072575', 'administrator', '$2y$12$N8vF9K8/nE2T9j4k0C3dKOm7Z5tE6j5K4j5k8K8j5k8j5k8j5k8j5k8', 'Admin'),
('U2510', 'kepsek', '$2y$12$example_kepala_sekolah_hash', 'Kepala Sekolah'),
('U1001', 'guru1', '$2y$12$example_guru_hash', 'Guru');

-- Insert Jabatan
INSERT INTO Jabatan (id_jabatan, nama_jabatan, gaji_awal) VALUES 
('J001', 'Kepala Sekolah', 8000000),
('J002', 'Wakil Kepala Sekolah', 7000000),
('J003', 'Guru Kelas', 5000000),
('J004', 'Guru Mata Pelajaran', 4500000),
('J005', 'Guru Honorer', 3000000);

-- Insert Tunjangan
INSERT INTO Tunjangan (id_tunjangan, id_jabatan, tunjangan_beras, tunjangan_suami_istri, tunjangan_anak) VALUES 
('T001', 'J001', 500000, 400000, 200000),
('T002', 'J002', 400000, 350000, 150000),
('T003', 'J003', 300000, 300000, 100000),
('T004', 'J004', 250000, 250000, 100000),
('T005', 'J005', 200000, 200000, 75000);

-- Insert Potongan
INSERT INTO Potongan (id_potongan, id_jabatan, potongan_bpjs, infak) VALUES 
('P001', 'J001', 2.00, 2.00),
('P002', 'J002', 2.00, 2.00),
('P003', 'J003', 2.00, 2.00),
('P004', 'J004', 2.00, 2.00),
('P005', 'J005', 2.00, 2.00);
```

---

## 📂 Instalasi Project

### Metode 1: Download ZIP
1. Download project sebagai ZIP
2. Extract ke folder `C:\xampp\htdocs\penggajian-guru` (Windows) atau `/Applications/XAMPP/xamppfiles/htdocs/penggajian-guru` (macOS)

### Metode 2: Git Clone
```bash
# Windows
cd C:\xampp\htdocs
git clone [repository-url] penggajian-guru

# macOS/Linux
cd /Applications/XAMPP/xamppfiles/htdocs
git clone [repository-url] penggajian-guru
```

### 3. Set Permissions (Linux/macOS)
```bash
# Berikan permission yang tepat
chmod -R 755 /path/to/penggajian-guru
chmod -R 777 /path/to/penggajian-guru/assets/uploads
```

---

## ⚙️ Konfigurasi

### 1. File Konfigurasi Database
Edit file `config/koneksi.php`:

```php
<?php
// 1. PENGATURAN DATABASE
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', ''); // Kosong untuk XAMPP default
define('DB_NAME', 'gaji_guru');

// 2. PENGATURAN APLIKASI
define('APP_NAME', 'Sistem Penggajian Guru');

// Sesuaikan dengan setup Anda
define('BASE_URL', 'http://localhost/penggajian-guru'); 

// 3. PENGATURAN KEAMANAN & SESI
define('SESSION_TIMEOUT', 3600); // 1 jam

// 4. PENGATURAN ZONA WAKTU
date_default_timezone_set('Asia/Jakarta');

// 5. PENGATURAN ERROR REPORTING (matikan di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
```

### 2. Struktur Folder
Pastikan struktur folder seperti ini:
```
penggajian-guru/
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── uploads/
├── auth/
├── config/
├── includes/
├── pages/
├── reports/
├── api/
└── vendor/ (jika menggunakan Composer)
```

### 3. Install Dependencies (Opsional)
Jika menggunakan Composer untuk PDF generation:
```bash
cd penggajian-guru
composer install
```

---

## 🏃‍♂️ Menjalankan Aplikasi

### 1. Start XAMPP Services
- **Windows:** Buka XAMPP Control Panel → Start Apache & MySQL
- **macOS:** `sudo /Applications/XAMPP/xamppfiles/xampp start`
- **Linux:** `sudo /opt/lampp/lampp start`

### 2. Akses Aplikasi
Buka browser dan navigasi ke:
```
http://localhost/penggajian-guru
```

### 3. Verifikasi Installation
- Cek apakah halaman login muncul
- Cek apakah tidak ada error PHP
- Pastikan koneksi database berhasil

---

## 🔐 Login Default

### Admin Utama:
- **Username:** `admin`
- **Password:** `admin123`
- **Dashboard:** `/index.php`

### Admin Backup:
- **Username:** `administrator`
- **Password:** `admin2024`
- **Dashboard:** `/index.php`

### Kepala Sekolah:
- **Username:** `kepsek`
- **Password:** `kepsek123` (perlu direset)
- **Dashboard:** `/index_kepsek.php`

### Guru:
- **Username:** `guru1`
- **Password:** `guru123` (perlu direset)
- **Dashboard:** `/index_guru.php`

---

## 🔧 Troubleshooting

### Error Database Connection
```
Error: SQLSTATE[HY000] [1049] Unknown database 'gaji_guru'
```
**Solusi:**
1. Pastikan MySQL berjalan
2. Cek nama database di phpMyAdmin
3. Verifikasi kredensial di `config/koneksi.php`

### Error PHP Extension
```
Error: Class 'mysqli' not found
```
**Solusi:**
1. Pastikan PHP mysqli extension aktif
2. Edit `php.ini` → uncomment `extension=mysqli`
3. Restart Apache

### Permission Denied (Linux/macOS)
```
Error: Permission denied
```
**Solusi:**
```bash
sudo chown -R www-data:www-data /path/to/penggajian-guru
chmod -R 755 /path/to/penggajian-guru
```

### Login Gagal
**Solusi:**
1. Cek kredensial di dokumentasi
2. Reset password jika perlu:
```php
<?php
require_once 'config/koneksi.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$password = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("UPDATE User SET password = '$password' WHERE username = 'admin'");
echo "Password reset berhasil!";
?>
```

### Port Already in Use
```
Error: Port 80 already in use
```
**Solusi:**
1. Stop service yang menggunakan port 80
2. Atau ubah port Apache di `httpd.conf`

---

## 📞 Support

Jika mengalami masalah:
1. Cek file `LOGIN_INFO.md` untuk kredensial
2. Verifikasi semua langkah instalasi
3. Cek log error di `/xampp/apache/logs/error.log`
4. Pastikan semua service XAMPP berjalan

---

## 🎉 Selamat!

Jika semua langkah berhasil, sistem penggajian guru sudah siap digunakan!

**Next Steps:**
1. Login sebagai admin
2. Tambah data guru dan jabatan
3. Setup tunjangan dan potongan
4. Mulai proses penggajian

---

*Last Updated: 25 Juli 2025*
*Version: 1.0*
