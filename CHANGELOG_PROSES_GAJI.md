# Changelog - Refaktor pages/proses_gaji.php

## Perubahan yang dilakukan:

### 1. Penambahan Kolom di Tabel Database
- Menambahkan kolom `no_slip_gaji` VARCHAR(20) ke tabel Penggajian
- Menambahkan kolom `status_validasi` ENUM('Valid', 'Belum Valid') ke tabel Penggajian
- File SQL untuk perubahan database: `alter_penggajian_table.sql`

### 2. Perubahan pada Tabel Data Gaji
**Kolom Header Baru:**
- No Slip Gaji (menampilkan nomor slip gaji otomatis)
- Tunjangan Beras
- Kehadiran (Tunjangan Kehadiran)
- Gaji Kotor
- Potongan (Total Potongan)
- Gaji Bersih

### 3. Perubahan Aksi Tabel
**Aksi Lama:** Edit (icon), Hapus (icon), Validasi (icon)
**Aksi Baru:** 
- **Cetak** (button hijau dengan icon print)
- **Edit** (button biru dengan icon pencil)  
- **Hapus** (button merah dengan icon trash)

### 4. Fitur Baru
- **Nomor Slip Gaji Otomatis:** Format SG + YYMM + 4 digit random
- **Fungsi Cetak Slip Gaji:** Membuka halaman cetak slip gaji di tab baru
- **Slip Gaji Lengkap:** Template slip gaji profesional dengan semua detail

### 5. File Baru yang Dibuat
- `reports/slip_gaji.php` - Halaman untuk mencetak slip gaji
- `alter_penggajian_table.sql` - Script SQL untuk mengubah struktur tabel

### 6. Cara Menggunakan Perubahan

#### Langkah 1: Update Database
Jalankan script SQL berikut di database Anda:
```sql
-- Tambah kolom no_slip_gaji dan status_validasi ke tabel Penggajian
ALTER TABLE Penggajian 
ADD COLUMN no_slip_gaji VARCHAR(20) AFTER id_penggajian,
ADD COLUMN status_validasi ENUM('Valid', 'Belum Valid') DEFAULT 'Belum Valid' AFTER bulan_penggajian;

-- Update data yang sudah ada dengan nomor slip gaji otomatis
UPDATE Penggajian 
SET no_slip_gaji = CONCAT('SG', 
    LPAD(YEAR(tgl_input) - 2000, 2, '0'), 
    LPAD(bulan_penggajian, 2, '0'), 
    LPAD(SUBSTRING(id_penggajian, -4), 4, '0')
) 
WHERE no_slip_gaji IS NULL;

-- Update status validasi default untuk data yang sudah ada
UPDATE Penggajian 
SET status_validasi = 'Belum Valid' 
WHERE status_validasi IS NULL;
```

#### Langkah 2: Akses Fitur Baru
1. Buka halaman Proses Gaji
2. Lihat tabel dengan kolom-kolom baru yang menampilkan detail lengkap
3. Gunakan tombol "Cetak" untuk mencetak slip gaji
4. Gunakan tombol "Edit" untuk mengedit data gaji
5. Gunakan tombol "Hapus" untuk menghapus data gaji

### 7. Perubahan Teknis
- Menambahkan fungsi JavaScript `printSlipGaji()` untuk membuka halaman cetak
- Mengubah struktur tabel HTML dengan 11 kolom (sebelumnya 6 kolom)
- Menambahkan logika untuk generate nomor slip gaji otomatis
- Membuat template slip gaji dengan CSS yang responsive dan print-friendly

### 8. Fitur Slip Gaji
- Header sekolah yang dapat disesuaikan
- Informasi lengkap guru (nama, NIPM, jabatan, status kawin, dll)
- Rincian pendapatan dan potongan yang detail
- Total gaji kotor, potongan, dan gaji bersih
- Area tanda tangan untuk guru dan kepala sekolah
- Informasi tanggal cetak dan status validasi
- Tombol cetak dan tutup untuk kemudahan penggunaan

Semua perubahan telah diimplementasikan dan siap digunakan!
