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
