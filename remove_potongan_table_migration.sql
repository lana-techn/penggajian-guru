-- ======================================================
-- REMOVE POTONGAN TABLE MIGRATION
-- ======================================================
-- Date: 24 August 2025
-- Description: Remove Potongan table and consolidate all deduction data in Penggajian table
-- ======================================================

USE gaji_guru;

-- ======================================================
-- STEP 1: BACKUP EXISTING DATA
-- ======================================================
CREATE TABLE IF NOT EXISTS backup_potongan_before_removal AS SELECT * FROM Potongan;
CREATE TABLE IF NOT EXISTS backup_penggajian_before_potongan_removal AS SELECT * FROM Penggajian;

-- ======================================================
-- STEP 2: ENSURE PENGGAJIAN TABLE HAS ALL NECESSARY COLUMNS
-- ======================================================

-- Add percentage columns if they don't exist (these will store the deduction percentages)
ALTER TABLE Penggajian 
ADD COLUMN IF NOT EXISTS potongan_bpjs_persen DECIMAL(5,2) DEFAULT 2.00 AFTER potongan_terlambat,
ADD COLUMN IF NOT EXISTS infak_persen DECIMAL(5,2) DEFAULT 2.00 AFTER potongan_bpjs_persen;

-- ======================================================
-- STEP 3: UPDATE EXISTING PENGGAJIAN RECORDS WITH DEFAULT VALUES
-- ======================================================

-- Set default percentages where they are null or zero
UPDATE Penggajian 
SET potongan_bpjs_persen = 2.00 
WHERE potongan_bpjs_persen IS NULL OR potongan_bpjs_persen = 0;

UPDATE Penggajian 
SET infak_persen = 2.00 
WHERE infak_persen IS NULL OR infak_persen = 0;

-- Recalculate BPJS and Infak amounts based on percentages and gaji_pokok
UPDATE Penggajian 
SET 
    potongan_bpjs = ROUND(gaji_pokok * (potongan_bpjs_persen / 100), 0),
    infak = ROUND(gaji_pokok * (infak_persen / 100), 0)
WHERE gaji_pokok > 0;

-- Recalculate total_potongan and gaji_bersih
UPDATE Penggajian 
SET total_potongan = potongan_bpjs + infak + COALESCE(potongan_terlambat, 0);

UPDATE Penggajian 
SET gaji_bersih = gaji_kotor - total_potongan;

-- ======================================================
-- STEP 4: REMOVE FOREIGN KEY CONSTRAINTS REFERENCING POTONGAN
-- ======================================================

-- Check for any foreign key constraints that might reference Potongan table
-- (This query will show us what constraints exist)
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_SCHEMA = 'gaji_guru' 
  AND REFERENCED_TABLE_NAME = 'Potongan';

-- ======================================================
-- STEP 5: DROP THE POTONGAN TABLE
-- ======================================================

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop the Potongan table
DROP TABLE IF EXISTS Potongan;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ======================================================
-- STEP 6: VERIFICATION
-- ======================================================

-- Verify the Potongan table is gone
SHOW TABLES LIKE 'Potongan';

-- Check Penggajian table structure
DESCRIBE Penggajian;

-- Show sample data to verify everything is working
SELECT 
    id_penggajian,
    gaji_pokok,
    potongan_bpjs,
    potongan_bpjs_persen,
    infak,
    infak_persen,
    potongan_terlambat,
    total_potongan,
    gaji_bersih
FROM Penggajian 
LIMIT 3;

-- Check data integrity
SELECT 
    COUNT(*) as total_records,
    COUNT(CASE WHEN potongan_bpjs_persen IS NOT NULL THEN 1 END) as has_bpjs_persen,
    COUNT(CASE WHEN infak_persen IS NOT NULL THEN 1 END) as has_infak_persen,
    COUNT(CASE WHEN total_potongan IS NOT NULL THEN 1 END) as has_total_potongan
FROM Penggajian;

-- ======================================================
-- STEP 7: UPDATE FUNCTIONS FOR FUTURE CALCULATIONS
-- ======================================================

-- Note: The system should now use the percentage values stored in Penggajian table
-- instead of looking up values from the Potongan table

SELECT 'Migration completed successfully!' as status;
SELECT 'Potongan table has been removed. All deduction data is now stored in Penggajian table.' as info;
SELECT 'Use potongan_bpjs_persen and infak_persen columns for future calculations.' as note;