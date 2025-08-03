-- Migration script to remove status_validasi column from Penggajian table
-- Run this script on existing databases to remove the status_validasi column

USE gaji_guru;

-- Remove the status_validasi column from Penggajian table
ALTER TABLE Penggajian DROP COLUMN IF EXISTS status_validasi;

-- Remove the index on status_validasi if it exists
DROP INDEX IF EXISTS idx_status_validasi ON Penggajian;

-- Verify the column has been removed
DESCRIBE Penggajian; 