# Sistem Penggajian Guru

Aplikasi web penggajian guru berbasis PHP Native, MySQL, dan TailwindCSS. Mengelola data guru, jabatan, absensi, tunjangan, potongan, proses penggajian, dan slip gaji PDF. Struktur dan fitur mirip dengan [lana-techn/tugas-akhir](https://github.com/lana-techn/tugas-akhir).

## Struktur Folder
```
gaji-guru/
├── api/
├── assets/
│   └── images/
├── auth/
├── config/
├── includes/
├── pages/
├── public/
├── sql/
├── index.php
├── .htaccess
└── README.md
```

## Setup Awal
1. Import database dari `sql/gaji_guru.sql` ke MySQL.
2. Atur koneksi database di `config/koneksi.php`.
3. Build TailwindCSS ke `public/tailwind.css` atau gunakan CDN.
4. Akses aplikasi via browser.

---

## Fitur Utama
- Manajemen User & Role (Admin, Kepala Sekolah, Guru)
- Manajemen Guru, Jabatan, Tunjangan, Potongan
- Manajemen Absensi
- Proses Penggajian Otomatis
- Laporan Rekap & Slip Gaji (PDF)
- RBAC (Role Based Access Control)
- Styling dengan TailwindCSS 