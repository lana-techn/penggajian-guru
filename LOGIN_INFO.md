# Informasi Login Sistem Penggajian Guru

## Kredensial Login Admin

### Admin Utama
- **Username:** `admin`
- **Password:** `admin123`
- **Role:** Admin
- **ID User:** U2507

### Admin Backup
- **Username:** `administrator`
- **Password:** `admin2024`
- **Role:** Admin
- **ID User:** U072575

## Akun Lainnya

### Kepala Sekolah
- **Username:** `kepsek`
- **Password:** (belum diverifikasi)
- **Role:** Kepala Sekolah

### Guru
- **Username:** `guru1`
- **Password:** (belum diverifikasi)
- **Role:** Guru

## Troubleshooting Login

### Jika Login Admin Gagal:

1. **Pastikan Kredensial Benar**
   - Username: `admin`
   - Password: `admin123`

2. **Cek Database**
   ```sql
   SELECT id_user, username, akses FROM User WHERE akses = 'Admin';
   ```

3. **Reset Password** (jika diperlukan)
   ```php
   // Buat script PHP untuk reset password
   $new_password = 'password_baru';
   $hashed = password_hash($new_password, PASSWORD_DEFAULT);
   // Update ke database
   ```

### Role Mapping
- Database `akses = 'Admin'` → Session `role = 'admin'`
- Database `akses = 'Kepala Sekolah'` → Session `role = 'kepala_sekolah'`
- Database `akses = 'Guru'` → Session `role = 'guru'`

### Dashboard Redirect
- **Admin** → `/index.php`
- **Kepala Sekolah** → `/index_kepsek.php`
- **Guru** → `/index_guru.php`

## Update Password

Jika perlu mengubah password admin:

```php
<?php
require_once 'config/koneksi.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$new_password = 'password_baru';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE User SET password = ? WHERE username = 'admin'");
$stmt->bind_param("s", $hashed_password);
$stmt->execute();

echo "Password berhasil diupdate!";
?>
```

## Catatan Penting

- Password di-hash menggunakan `password_hash()` PHP
- Verifikasi menggunakan `password_verify()`
- Session menggunakan role yang dinormalisasi (lowercase, underscore)
- CSRF token digunakan untuk keamanan form
- Session timeout: 1 jam (3600 detik)

## Last Updated
Tanggal: 25 Juli 2025
Status: ✅ Verified Working
