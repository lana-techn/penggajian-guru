-- ======================================================
-- MIGRATION: ADD POTONGAN_TERLAMBAT COLUMN
-- ======================================================
-- Date: 24 August 2025
-- Description: Add potongan_terlambat column to Penggajian table
-- ======================================================

USE gaji_guru;

-- Add potongan_terlambat column to Penggajian table
ALTER TABLE Penggajian 
ADD COLUMN IF NOT EXISTS potongan_terlambat DECIMAL(15,2) DEFAULT 0 AFTER infak;

-- Update existing records to calculate potongan_terlambat based on attendance data
UPDATE Penggajian p
LEFT JOIN Rekap_Kehadiran r ON p.id_guru = r.id_guru 
    AND p.bulan_penggajian = r.bulan 
    AND YEAR(p.tgl_input) = r.tahun
SET p.potongan_terlambat = CASE 
    WHEN r.jml_terlambat IS NULL OR r.jml_terlambat <= 0 THEN 0
    WHEN r.jml_terlambat <= 3 THEN r.jml_terlambat * 5000
    WHEN r.jml_terlambat = 4 THEN 20000
    WHEN r.jml_terlambat = 5 THEN 25000
    ELSE 100000
END;

-- Update total_potongan to include potongan_terlambat
UPDATE Penggajian 
SET total_potongan = potongan_bpjs + infak + potongan_terlambat;

-- Update gaji_bersih to reflect the new total_potongan
UPDATE Penggajian 
SET gaji_bersih = gaji_kotor - total_potongan;

-- Verify the migration
SELECT 'Migration completed successfully! Added potongan_terlambat column and updated calculations.' as status;

-- Show sample data after migration
SELECT 
    id_penggajian,
    id_guru,
    bulan_penggajian,
    potongan_bpjs,
    infak,
    potongan_terlambat,
    total_potongan,
    gaji_bersih
FROM Penggajian 
LIMIT 5;