-- ======================================================
-- DATABASE RESTRUCTURE MIGRATION
-- ======================================================
-- Date: 24 August 2025
-- Description: Restructure database relationships and optimize tables
-- Changes:
-- 1. Remove id_jabatan from Tunjangan table
-- 2. Add tunjangan_kehadiran to Tunjangan table  
-- 3. Add id_tunjangan to Guru table
-- 4. Remove redundant tunjangan fields from Penggajian table
-- 5. Remove Potongan table (integrate into Penggajian)
-- ======================================================

USE gaji_guru;

-- Backup existing data before migration
CREATE TABLE IF NOT EXISTS backup_tunjangan_before_restructure AS SELECT * FROM Tunjangan;
CREATE TABLE IF NOT EXISTS backup_guru_before_restructure AS SELECT * FROM Guru;
CREATE TABLE IF NOT EXISTS backup_penggajian_before_restructure AS SELECT * FROM Penggajian;
CREATE TABLE IF NOT EXISTS backup_potongan_before_restructure AS SELECT * FROM Potongan;

-- ======================================================
-- DISABLE FOREIGN KEY CHECKS
-- ======================================================
SET FOREIGN_KEY_CHECKS = 0;

-- ======================================================
-- STEP 1: MODIFY TUNJANGAN TABLE
-- ======================================================

-- Add tunjangan_kehadiran field if it doesn't exist
ALTER TABLE Tunjangan 
ADD COLUMN IF NOT EXISTS tunjangan_kehadiran DECIMAL(15,2) DEFAULT 100000.00 AFTER tunjangan_anak;

-- Update existing tunjangan_kehadiran values
UPDATE Tunjangan SET tunjangan_kehadiran = 100000.00 WHERE tunjangan_kehadiran = 0 OR tunjangan_kehadiran IS NULL;

-- Remove foreign key constraint from Tunjangan table
ALTER TABLE Tunjangan DROP FOREIGN KEY IF EXISTS tunjangan_ibfk_1;
ALTER TABLE Tunjangan DROP FOREIGN KEY IF EXISTS fk_tunjangan_jabatan;

-- Remove id_jabatan column from Tunjangan table
ALTER TABLE Tunjangan DROP COLUMN IF EXISTS id_jabatan;

-- ======================================================
-- STEP 2: MODIFY GURU TABLE
-- ======================================================

-- Add id_tunjangan field to Guru table if it doesn't exist
ALTER TABLE Guru 
ADD COLUMN IF NOT EXISTS id_tunjangan VARCHAR(15) NULL AFTER jml_anak;

-- Map existing guru to tunjangan based on jabatan
UPDATE Guru g 
SET g.id_tunjangan = CASE 
    WHEN g.id_jabatan = 'J001' THEN 'T001'
    WHEN g.id_jabatan = 'J002' THEN 'T002'
    WHEN g.id_jabatan = 'J003' THEN 'T003'
    WHEN g.id_jabatan = 'J004' THEN 'T004'
    WHEN g.id_jabatan = 'J005' THEN 'T005'
    WHEN g.id_jabatan = 'J006' THEN 'T006'
    WHEN g.id_jabatan = 'J007' THEN 'T007'
    ELSE NULL
END
WHERE g.id_tunjangan IS NULL;

-- ======================================================
-- STEP 3: MODIFY PENGGAJIAN TABLE
-- ======================================================

-- Remove redundant tunjangan fields from Penggajian table
ALTER TABLE Penggajian 
DROP COLUMN IF EXISTS tunjangan_beras,
DROP COLUMN IF EXISTS tunjangan_kehadiran,
DROP COLUMN IF EXISTS tunjangan_suami_istri,
DROP COLUMN IF EXISTS tunjangan_anak;

-- Add potongan percentage fields to Penggajian (replacing Potongan table)
ALTER TABLE Penggajian 
ADD COLUMN IF NOT EXISTS potongan_bpjs_persen DECIMAL(5,2) DEFAULT 2.00 AFTER potongan_terlambat,
ADD COLUMN IF NOT EXISTS infak_persen DECIMAL(5,2) DEFAULT 2.00 AFTER potongan_bpjs_persen;

-- Update existing records with default percentage values
UPDATE Penggajian 
SET potongan_bpjs_persen = 2.00, infak_persen = 2.00 
WHERE potongan_bpjs_persen IS NULL OR infak_persen IS NULL;

-- ======================================================
-- STEP 4: ADD FOREIGN KEY CONSTRAINTS
-- ======================================================

-- Add foreign key from Guru to Tunjangan
ALTER TABLE Guru 
ADD CONSTRAINT fk_guru_tunjangan 
FOREIGN KEY (id_tunjangan) REFERENCES Tunjangan(id_tunjangan) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- ======================================================
-- STEP 5: DROP POTONGAN TABLE
-- ======================================================

-- Remove Potongan table as it's now integrated into Penggajian
DROP TABLE IF EXISTS Potongan;

-- ======================================================
-- STEP 6: ENABLE FOREIGN KEY CHECKS
-- ======================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ======================================================
-- VERIFICATION QUERIES
-- ======================================================

-- Check new table structures
DESCRIBE Tunjangan;
DESCRIBE Guru;
DESCRIBE Penggajian;

-- Verify data integrity
SELECT 
    COUNT(*) as total_tunjangan,
    COUNT(CASE WHEN tunjangan_kehadiran IS NOT NULL THEN 1 END) as has_tunjangan_kehadiran
FROM Tunjangan;

SELECT 
    COUNT(*) as total_guru,
    COUNT(CASE WHEN id_tunjangan IS NOT NULL THEN 1 END) as has_id_tunjangan
FROM Guru;

SELECT 
    COUNT(*) as total_penggajian,
    COUNT(CASE WHEN potongan_bpjs_persen IS NOT NULL THEN 1 END) as has_potongan_persen
FROM Penggajian;

-- Show sample data after migration
SELECT 
    t.id_tunjangan,
    t.tunjangan_beras,
    t.tunjangan_suami_istri,
    t.tunjangan_anak,
    t.tunjangan_kehadiran
FROM Tunjangan t;

SELECT 
    g.id_guru,
    g.nama_guru,
    g.id_jabatan,
    g.id_tunjangan
FROM Guru g
LIMIT 5;

SELECT 
    p.id_penggajian,
    p.id_guru,
    p.gaji_pokok,
    p.potongan_bpjs,
    p.infak,
    p.potongan_terlambat,
    p.potongan_bpjs_persen,
    p.infak_persen
FROM Penggajian p
LIMIT 5;

-- ======================================================
-- MIGRATION COMPLETED
-- ======================================================
SELECT 'Database restructure migration completed successfully!' as status;

-- Show updated relationships
SELECT 'New database structure:' as info;
SELECT '1. Tunjangan table: removed id_jabatan, added tunjangan_kehadiran' as change_1;
SELECT '2. Guru table: added id_tunjangan for direct relationship' as change_2;
SELECT '3. Penggajian table: removed redundant tunjangan fields, added potongan percentages' as change_3;
SELECT '4. Potongan table: removed (integrated into Penggajian)' as change_4;