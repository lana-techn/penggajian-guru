-- ======================================================
-- COMPLETE DATABASE FIX MIGRATION
-- ======================================================
-- Date: 24 August 2025
-- Description: Apply all necessary database fixes
-- ======================================================

USE gaji_guru;

-- ======================================================
-- STEP 1: ENSURE POTONGAN TABLE EXISTS (temporary for migration)
-- ======================================================
CREATE TABLE IF NOT EXISTS Potongan (
    id_potongan VARCHAR(15) NOT NULL PRIMARY KEY,
    id_jabatan VARCHAR(15) NOT NULL,
    potongan_bpjs DECIMAL(5,2) DEFAULT 2.00 COMMENT 'Persentase BPJS (%)',
    infak DECIMAL(5,2) DEFAULT 2.00 COMMENT 'Persentase Infak (%)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default potongan data if not exists
INSERT IGNORE INTO Potongan (id_potongan, id_jabatan, potongan_bpjs, infak) VALUES 
('P001', 'J001', 2.00, 2.00),
('P002', 'J002', 2.00, 2.00),
('P003', 'J003', 2.00, 2.00),
('P004', 'J004', 2.00, 2.00),
('P005', 'J005', 2.00, 2.00),
('P006', 'J006', 2.00, 2.00),
('P007', 'J007', 2.00, 2.00);

-- ======================================================
-- STEP 2: ADD MISSING POTONGAN_TERLAMBAT COLUMN
-- ======================================================
ALTER TABLE Penggajian 
ADD COLUMN IF NOT EXISTS potongan_terlambat DECIMAL(15,2) DEFAULT 0 AFTER infak;

-- ======================================================
-- STEP 3: ADD MISSING TUNJANGAN COLUMNS BACK TO PENGGAJIAN
-- ======================================================
ALTER TABLE Penggajian 
ADD COLUMN IF NOT EXISTS tunjangan_beras DECIMAL(15,2) DEFAULT 0 AFTER gaji_pokok,
ADD COLUMN IF NOT EXISTS tunjangan_kehadiran DECIMAL(15,2) DEFAULT 0 AFTER tunjangan_beras,
ADD COLUMN IF NOT EXISTS tunjangan_suami_istri DECIMAL(15,2) DEFAULT 0 AFTER tunjangan_kehadiran,
ADD COLUMN IF NOT EXISTS tunjangan_anak DECIMAL(15,2) DEFAULT 0 AFTER tunjangan_suami_istri;

-- ======================================================
-- STEP 4: UPDATE EXISTING PENGGAJIAN RECORDS
-- ======================================================

-- Update tunjangan values from related tables
UPDATE Penggajian p
JOIN Guru g ON p.id_guru = g.id_guru
JOIN Jabatan j ON g.id_jabatan = j.id_jabatan
LEFT JOIN Tunjangan t ON j.id_jabatan = t.id_jabatan
SET 
    p.tunjangan_beras = COALESCE(t.tunjangan_beras, 0),
    p.tunjangan_kehadiran = COALESCE(t.tunjangan_kehadiran, 100000),
    p.tunjangan_suami_istri = CASE 
        WHEN g.status_kawin IN ('Kawin', 'Menikah') THEN COALESCE(t.tunjangan_suami_istri, 0)
        ELSE 0
    END,
    p.tunjangan_anak = CASE 
        WHEN g.jml_anak > 0 THEN COALESCE(t.tunjangan_anak, 0) * g.jml_anak
        ELSE 0
    END
WHERE p.tunjangan_beras = 0 OR p.tunjangan_beras IS NULL;

-- Update potongan_terlambat based on attendance data
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
END
WHERE p.potongan_terlambat = 0 OR p.potongan_terlambat IS NULL;

-- Recalculate gaji_kotor
UPDATE Penggajian 
SET gaji_kotor = gaji_pokok + tunjangan_beras + tunjangan_kehadiran + tunjangan_suami_istri + tunjangan_anak;

-- Recalculate total_potongan
UPDATE Penggajian 
SET total_potongan = potongan_bpjs + infak + COALESCE(potongan_terlambat, 0);

-- Recalculate gaji_bersih
UPDATE Penggajian 
SET gaji_bersih = gaji_kotor - total_potongan;

-- ======================================================
-- VERIFICATION
-- ======================================================
SELECT 'Database fix migration completed successfully!' as status;

-- Show current table structure
DESCRIBE Penggajian;

-- Show sample data
SELECT 
    p.id_penggajian,
    g.nama_guru,
    p.gaji_pokok,
    p.tunjangan_beras,
    p.tunjangan_kehadiran,
    p.tunjangan_suami_istri,
    p.tunjangan_anak,
    p.gaji_kotor,
    p.potongan_bpjs,
    p.infak,
    p.potongan_terlambat,
    p.total_potongan,
    p.gaji_bersih
FROM Penggajian p
JOIN Guru g ON p.id_guru = g.id_guru
LIMIT 3;