-- ======================================================
-- DATABASE STRUCTURE UPDATE MIGRATION
-- ======================================================
-- Date: 24 August 2025
-- Description: Update database structure as requested
-- Changes:
-- 1. Remove id_jabatan from Tunjangan table and add tunjangan_kehadiran field
-- 2. Add id_tunjangan field to Guru table
-- 3. Remove no_slip_gaji, tunjangan_beras, tunjangan_kehadiran, tunjangan_suami_istri, tunjangan_anak from Penggajian table
-- ======================================================

USE gaji_guru;

-- Create backup tables before migration
CREATE TABLE IF NOT EXISTS backup_tunjangan_structure_update AS SELECT * FROM Tunjangan;
CREATE TABLE IF NOT EXISTS backup_guru_structure_update AS SELECT * FROM Guru;
CREATE TABLE IF NOT EXISTS backup_penggajian_structure_update AS SELECT * FROM Penggajian;

-- ======================================================
-- DISABLE FOREIGN KEY CHECKS
-- ======================================================
SET FOREIGN_KEY_CHECKS = 0;

-- ======================================================
-- STEP 1: MODIFY TUNJANGAN TABLE
-- ======================================================

-- Remove foreign key constraint from Tunjangan table if it exists
ALTER TABLE Tunjangan DROP FOREIGN KEY IF EXISTS tunjangan_ibfk_1;
ALTER TABLE Tunjangan DROP FOREIGN KEY IF EXISTS fk_tunjangan_jabatan;

-- Add tunjangan_kehadiran field if it doesn't exist
ALTER TABLE Tunjangan 
ADD COLUMN IF NOT EXISTS tunjangan_kehadiran DECIMAL(15,2) DEFAULT 100000.00 AFTER tunjangan_anak;

-- Update existing tunjangan_kehadiran values
UPDATE Tunjangan SET tunjangan_kehadiran = 100000.00 WHERE tunjangan_kehadiran = 0 OR tunjangan_kehadiran IS NULL;

-- Remove id_jabatan column from Tunjangan table
ALTER TABLE Tunjangan DROP COLUMN IF EXISTS id_jabatan;

-- ======================================================
-- STEP 2: MODIFY GURU TABLE
-- ======================================================

-- Add id_tunjangan field to Guru table if it doesn't exist
ALTER TABLE Guru 
ADD COLUMN IF NOT EXISTS id_tunjangan VARCHAR(15) NULL AFTER jml_anak;

-- Map existing guru to tunjangan based on jabatan (if id_tunjangan is empty)
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

-- Add foreign key constraint from Guru to Tunjangan (if not exists)
SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE 
                          WHERE CONSTRAINT_NAME = 'fk_guru_tunjangan' 
                          AND TABLE_NAME = 'Guru' 
                          AND TABLE_SCHEMA = 'gaji_guru');

SET @sql = IF(@constraint_exists = 0, 
              'ALTER TABLE Guru ADD CONSTRAINT fk_guru_tunjangan FOREIGN KEY (id_tunjangan) REFERENCES Tunjangan(id_tunjangan) ON DELETE SET NULL ON UPDATE CASCADE',
              'SELECT "Foreign key constraint fk_guru_tunjangan already exists" as message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ======================================================
-- STEP 3: MODIFY PENGGAJIAN TABLE
-- ======================================================

-- Remove specified fields from Penggajian table
ALTER TABLE Penggajian 
DROP COLUMN IF EXISTS no_slip_gaji,
DROP COLUMN IF EXISTS tunjangan_beras,
DROP COLUMN IF EXISTS tunjangan_kehadiran,
DROP COLUMN IF EXISTS tunjangan_suami_istri,
DROP COLUMN IF EXISTS tunjangan_anak;

-- ======================================================
-- ENABLE FOREIGN KEY CHECKS
-- ======================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ======================================================
-- VERIFICATION QUERIES
-- ======================================================

-- Check new table structures
SELECT 'Tunjangan table structure:' as info;
DESCRIBE Tunjangan;

SELECT 'Guru table structure:' as info;
DESCRIBE Guru;

SELECT 'Penggajian table structure:' as info;
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
    COUNT(*) as total_penggajian
FROM Penggajian;

-- Show sample data after migration
SELECT 'Sample Tunjangan data:' as info;
SELECT 
    t.id_tunjangan,
    t.tunjangan_beras,
    t.tunjangan_suami_istri,
    t.tunjangan_anak,
    t.tunjangan_kehadiran
FROM Tunjangan t
LIMIT 5;

SELECT 'Sample Guru data:' as info;
SELECT 
    g.id_guru,
    g.nama_guru,
    g.id_jabatan,
    g.id_tunjangan
FROM Guru g
LIMIT 5;

SELECT 'Sample Penggajian data:' as info;
SELECT 
    p.id_penggajian,
    p.id_guru,
    p.gaji_pokok,
    p.potongan_bpjs,
    p.infak,
    p.gaji_kotor,
    p.total_potongan,
    p.gaji_bersih
FROM Penggajian p
LIMIT 5;

-- ======================================================
-- MIGRATION COMPLETED
-- ======================================================
SELECT 'Database structure update migration completed successfully!' as status;

-- Show updated structure summary
SELECT 'Updated database structure:' as info;
SELECT '1. Tunjangan table: removed id_jabatan, added tunjangan_kehadiran' as change_1;
SELECT '2. Guru table: added id_tunjangan for direct relationship with Tunjangan' as change_2;
SELECT '3. Penggajian table: removed no_slip_gaji, tunjangan_beras, tunjangan_kehadiran, tunjangan_suami_istri, tunjangan_anak' as change_3;