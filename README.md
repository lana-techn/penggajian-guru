# 💼 Sistem Penggajian Guru

Sistem informasi untuk mengelola penggajian guru berbasis web dengan PHP dan MySQL.

## 🚀 Quick Start

### 1. Install XAMPP
- Download dari [https://www.apachefriends.org](https://www.apachefriends.org)
- Install dan jalankan **Apache** + **MySQL**

### 2. Setup Project
```bash
# Clone ke htdocs XAMPP
cd C:\xampp\htdocs          # Windows
cd /Applications/XAMPP/xamppfiles/htdocs  # macOS

git clone [project-url] penggajian-guru
```

### 3. Setup Database
1. Buka phpMyAdmin: `http://localhost/phpmyadmin`
2. Import file `database.sql` yang ada di root project
3. Database `gaji_guru` akan otomatis terbuat dengan data sample

### 4. Konfigurasi
Edit `config/koneksi.php` jika perlu:
```php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gaji_guru');
define('BASE_URL', 'http://localhost/penggajian-guru');
```

### 5. Akses Aplikasi
Buka browser: `http://localhost/penggajian-guru`

## 🔐 Login Default

| Role | Username | Password |
|------|----------|----------|
| **Admin** | `admin` | `admin123` |
| **Admin Backup** | `administrator` | `admin2024` |
| **Kepala Sekolah** | `kepsek` | `secret` |
| **Guru** | `guru1` | `secret` |

> **Note:** Password untuk kepsek dan guru1 perlu direset saat pertama login

## 📋 Fitur Utama

### 👨‍💼 Admin
- ✅ Manajemen Data Guru
- ✅ Manajemen Jabatan & Gaji Pokok
- ✅ Setup Tunjangan & Potongan
- ✅ Input Rekap Kehadiran
- ✅ Proses Penggajian Otomatis
- ✅ Cetak Slip Gaji (PDF)
- ✅ Laporan Gaji Komprehensif

### 👨‍🏫 Kepala Sekolah
- ✅ Dashboard Overview
- ✅ Laporan Penggajian
- ✅ Validasi Gaji
- ✅ Export Reports

### 👩‍🏫 Guru
- ✅ Lihat Slip Gaji Personal
- ✅ Download PDF Slip Gaji
- ✅ Riwayat Gaji
- ✅ Profile Management

## 🛠️ Tech Stack

- **Backend:** PHP 7.4+
- **Database:** MySQL 8.0+
- **Frontend:** HTML5, CSS3, JavaScript
- **Framework CSS:** Tailwind CSS
- **PDF Generation:** DomPDF
- **Icons:** Font Awesome
- **Web Server:** Apache 2.4+

## 📁 Struktur Project

```
penggajian-guru/
├── auth/                 # Login & authentication
├── pages/               # Halaman aplikasi
│   ├── admin/          # Halaman admin
│   ├── guru/           # Halaman guru
│   └── kepsek/         # Halaman kepala sekolah
├── api/                # API endpoints
├── reports/            # Generate reports
├── includes/           # Functions & helpers
├── config/             # Konfigurasi database
├── assets/             # CSS, JS, images
├── vendor/             # Dependencies (Composer)
├── database.sql        # Database structure
├── INSTALLATION_GUIDE.md
├── LOGIN_INFO.md
└── README.md
```

## 🔧 Installation Lengkap

Untuk panduan instalasi detail, baca: **[INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)**

## 📞 Troubleshooting

### Error Database Connection
```
SQLSTATE[HY000] [1049] Unknown database 'gaji_guru'
```
**Solusi:** Import file `database.sql` ke phpMyAdmin

### Login Gagal
```
Username atau password salah
```
**Solusi:** Gunakan kredensial default di atas, atau reset password:
```php
<?php
require_once 'config/koneksi.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$password = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("UPDATE User SET password = '$password' WHERE username = 'admin'");
echo "Password reset berhasil!";
?>
```

### Port 80 Sudah Digunakan
**Solusi:** Stop service yang menggunakan port 80 atau ubah port Apache

## 🎯 Cara Penggunaan

### 1. Login sebagai Admin
- Username: `admin`, Password: `admin123`
- Masuk ke dashboard admin

### 2. Setup Data Master
1. **Jabatan:** Tambah jabatan dan gaji pokok
2. **Tunjangan:** Set tunjangan per jabatan
3. **Potongan:** Set potongan BPJS dan infak
4. **Guru:** Input data guru

### 3. Input Kehadiran
- Masuk ke menu "Rekap Kehadiran"
- Input data kehadiran per bulan untuk setiap guru

### 4. Proses Gaji
- Masuk ke menu "Proses Gaji"
- Pilih guru, bulan, dan tahun
- Sistem akan otomatis menghitung gaji
- Validasi dan cetak slip gaji

### 5. Generate Reports
- Akses menu "Laporan"
- Pilih periode dan filter yang diinginkan
- Export ke PDF atau Excel

## 📄 Dokumentasi

- **[INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)** - Panduan instalasi lengkap
- **[LOGIN_INFO.md](LOGIN_INFO.md)** - Informasi login dan troubleshooting

## 🤝 Kontribusi

1. Fork repository
2. Buat branch fitur (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## 📝 License

Project ini menggunakan lisensi MIT. Lihat file `LICENSE` untuk detail.

## 👨‍💻 Developer

Dikembangkan dengan ❤️ untuk SD Unggulan Muhammadiyah Kretek

---

**Happy Coding! 🎉**
