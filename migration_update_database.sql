-- ======================================================
-- MIGRATION SCRIPT - UPDATE DATABASE TO NEW STRUCTURE
-- ======================================================
-- Dibuat: 20 Agustus 2025
-- Versi: 2.0
-- Deskripsi: Script untuk mengupdate database existing ke struktur baru
-- ======================================================

USE gaji_guru;

-- Backup existing data before migration
-- CREATE BACKUP TABLES
CREATE TABLE IF NOT EXISTS backup_user_old AS SELECT * FROM User;
CREATE TABLE IF NOT EXISTS backup_jabatan_old AS SELECT * FROM Jabatan;
CREATE TABLE IF NOT EXISTS backup_guru_old AS SELECT * FROM Guru;
CREATE TABLE IF NOT EXISTS backup_tunjangan_old AS SELECT * FROM Tunjangan;
CREATE TABLE IF NOT EXISTS backup_potongan_old AS SELECT * FROM Potongan;
CREATE TABLE IF NOT EXISTS backup_rekap_kehadiran_old AS SELECT * FROM Rekap_Kehadiran;
CREATE TABLE IF NOT EXISTS backup_penggajian_old AS SELECT * FROM Penggajian;

-- ======================================================
-- DISABLE FOREIGN KEY CHECKS
-- ======================================================
SET FOREIGN_KEY_CHECKS = 0;

-- ======================================================
-- UPDATE TABLE STRUCTURES
-- ======================================================

-- 1. UPDATE USER TABLE
ALTER TABLE User RENAME TO USER;
ALTER TABLE USER 
    MODIFY COLUMN id_user VARCHAR(15) NOT NULL,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD INDEX IF NOT EXISTS idx_username (username),
    ADD INDEX IF NOT EXISTS idx_akses (akses);

-- 2. UPDATE JABATAN TABLE
ALTER TABLE Jabatan RENAME TO JABATAN;
ALTER TABLE JABATAN 
    MODIFY COLUMN id_jabatan VARCHAR(15) NOT NULL,
    MODIFY COLUMN nama_jabatan VARCHAR(50) NOT NULL,
    ADD COLUMN IF NOT EXISTS kenaikan_pertahun DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER gaji_awal,
    ADD COLUMN IF NOT EXISTS status_aktif ENUM('Aktif', 'Non-Aktif') DEFAULT 'Aktif',
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD INDEX IF NOT EXISTS idx_nama_jabatan (nama_jabatan);

-- 3. UPDATE GURU TABLE
ALTER TABLE Guru RENAME TO GURU;
ALTER TABLE GURU 
    MODIFY COLUMN id_guru VARCHAR(15) NOT NULL,
    MODIFY COLUMN id_user VARCHAR(15) NULL,
    MODIFY COLUMN id_jabatan VARCHAR(15) NULL,
    MODIFY COLUMN nama_guru VARCHAR(50) NOT NULL,
    ADD COLUMN IF NOT EXISTS id_tunjangan VARCHAR(15) NULL AFTER jml_anak,
    ADD COLUMN IF NOT EXISTS status_aktif ENUM('Aktif', 'Non-Aktif') DEFAULT 'Aktif',
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD INDEX IF NOT EXISTS idx_nama_guru (nama_guru),
    ADD INDEX IF NOT EXISTS idx_nipm (nipm),
    ADD INDEX IF NOT EXISTS idx_status_aktif (status_aktif);

-- Remove old columns if they exist
ALTER TABLE GURU 
    DROP COLUMN IF EXISTS tempat_lahir,
    DROP COLUMN IF EXISTS tgl_lahir,
    DROP COLUMN IF EXISTS agama,
    DROP COLUMN IF EXISTS alamat,
    DROP COLUMN IF EXISTS foto;

-- 4. UPDATE TUNJANGAN TABLE
ALTER TABLE Tunjangan RENAME TO TUNJANGAN;
ALTER TABLE TUNJANGAN 
    MODIFY COLUMN id_tunjangan VARCHAR(15) NOT NULL,
    ADD COLUMN IF NOT EXISTS tunjangan_kehadiran DECIMAL(12,2) DEFAULT 0 AFTER tunjangan_beras,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Remove foreign key constraint from tunjangan if exists
ALTER TABLE TUNJANGAN DROP FOREIGN KEY IF EXISTS tunjangan_ibfk_1;
ALTER TABLE TUNJANGAN DROP COLUMN IF EXISTS id_jabatan;

-- 5. UPDATE REKAP_KEHADIRAN TABLE
ALTER TABLE Rekap_Kehadiran RENAME TO REKAP_KEHADIRAN;
ALTER TABLE REKAP_KEHADIRAN 
    CHANGE COLUMN id_rekap id_kehadiran VARCHAR(15) NOT NULL,
    MODIFY COLUMN id_guru VARCHAR(15) NOT NULL,
    MODIFY COLUMN bulan VARCHAR(15) NOT NULL,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD UNIQUE KEY IF NOT EXISTS unique_guru_bulan_tahun (id_guru, bulan, tahun),
    ADD INDEX IF NOT EXISTS idx_bulan_tahun (bulan, tahun);

-- Remove old columns if they exist
ALTER TABLE REKAP_KEHADIRAN 
    DROP COLUMN IF EXISTS jml_hadir;

-- 6. UPDATE PENGGAJIAN TABLE
ALTER TABLE Penggajian RENAME TO PENGGAJIAN;
ALTER TABLE PENGGAJIAN 
    MODIFY COLUMN id_penggajian VARCHAR(30) NOT NULL,
    MODIFY COLUMN id_guru VARCHAR(15) NOT NULL,
    MODIFY COLUMN masa_kerja INT(12) DEFAULT 0,
    MODIFY COLUMN bulan_penggajian VARCHAR(15) NOT NULL,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD UNIQUE KEY IF NOT EXISTS unique_guru_bulan_tahun_gaji (id_guru, bulan_penggajian, YEAR(tgl_input)),
    ADD INDEX IF NOT EXISTS idx_bulan_penggajian (bulan_penggajian),
    ADD INDEX IF NOT EXISTS idx_tgl_input (tgl_input);

-- Remove old columns if they exist
ALTER TABLE PENGGAJIAN 
    DROP COLUMN IF EXISTS no_slip_gaji,
    DROP COLUMN IF EXISTS tunjangan_beras,
    DROP COLUMN IF EXISTS tunjangan_kehadiran,
    DROP COLUMN IF EXISTS tunjangan_suami_istri,
    DROP COLUMN IF EXISTS tunjangan_anak;

-- 7. DROP POTONGAN TABLE (not in new ERD)
DROP TABLE IF EXISTS Potongan;

-- ======================================================
-- RE-ADD FOREIGN KEY CONSTRAINTS
-- ======================================================

-- GURU table foreign keys
ALTER TABLE GURU 
    ADD CONSTRAINT fk_guru_user FOREIGN KEY (id_user) REFERENCES USER(id_user) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_guru_jabatan FOREIGN KEY (id_jabatan) REFERENCES JABATAN(id_jabatan) ON DELETE SET NULL ON UPDATE CASCADE;

-- REKAP_KEHADIRAN table foreign keys
ALTER TABLE REKAP_KEHADIRAN 
    ADD CONSTRAINT fk_rekap_guru FOREIGN KEY (id_guru) REFERENCES GURU(id_guru) ON DELETE CASCADE ON UPDATE CASCADE;

-- PENGGAJIAN table foreign keys
ALTER TABLE PENGGAJIAN 
    ADD CONSTRAINT fk_penggajian_guru FOREIGN KEY (id_guru) REFERENCES GURU(id_guru) ON DELETE CASCADE ON UPDATE CASCADE;

-- ======================================================
-- UPDATE DATA TO MATCH NEW STRUCTURE
-- ======================================================

-- Update JABATAN with kenaikan_pertahun values
UPDATE JABATAN SET kenaikan_pertahun = 500000.00 WHERE id_jabatan = 'J001';
UPDATE JABATAN SET kenaikan_pertahun = 400000.00 WHERE id_jabatan = 'J002';
UPDATE JABATAN SET kenaikan_pertahun = 300000.00 WHERE id_jabatan = 'J003';
UPDATE JABATAN SET kenaikan_pertahun = 250000.00 WHERE id_jabatan = 'J004';
UPDATE JABATAN SET kenaikan_pertahun = 200000.00 WHERE id_jabatan = 'J005';
UPDATE JABATAN SET kenaikan_pertahun = 250000.00 WHERE id_jabatan = 'J006';
UPDATE JABATAN SET kenaikan_pertahun = 275000.00 WHERE id_jabatan = 'J007';

-- Update TUNJANGAN with tunjangan_kehadiran values
UPDATE TUNJANGAN SET tunjangan_kehadiran = 300000.00 WHERE id_tunjangan = 'T001';
UPDATE TUNJANGAN SET tunjangan_kehadiran = 250000.00 WHERE id_tunjangan = 'T002';
UPDATE TUNJANGAN SET tunjangan_kehadiran = 200000.00 WHERE id_tunjangan = 'T003';
UPDATE TUNJANGAN SET tunjangan_kehadiran = 180000.00 WHERE id_tunjangan = 'T004';
UPDATE TUNJANGAN SET tunjangan_kehadiran = 150000.00 WHERE id_tunjangan = 'T005';
UPDATE TUNJANGAN SET tunjangan_kehadiran = 170000.00 WHERE id_tunjangan = 'T006';
UPDATE TUNJANGAN SET tunjangan_kehadiran = 190000.00 WHERE id_tunjangan = 'T007';

-- Update GURU with id_tunjangan mapping
UPDATE GURU SET id_tunjangan = 'T001' WHERE id_jabatan = 'J001';
UPDATE GURU SET id_tunjangan = 'T002' WHERE id_jabatan = 'J002';
UPDATE GURU SET id_tunjangan = 'T003' WHERE id_jabatan = 'J003';
UPDATE GURU SET id_tunjangan = 'T004' WHERE id_jabatan = 'J004';
UPDATE GURU SET id_tunjangan = 'T005' WHERE id_jabatan = 'J005';
UPDATE GURU SET id_tunjangan = 'T006' WHERE id_jabatan = 'J006';
UPDATE GURU SET id_tunjangan = 'T007' WHERE id_jabatan = 'J007';

-- ======================================================
-- ENABLE FOREIGN KEY CHECKS
-- ======================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ======================================================
-- VERIFICATION QUERIES
-- ======================================================

-- Check table structures
DESCRIBE USER;
DESCRIBE JABATAN;
DESCRIBE GURU;
DESCRIBE TUNJANGAN;
DESCRIBE REKAP_KEHADIRAN;
DESCRIBE PENGGAJIAN;

-- Check data integrity
SELECT COUNT(*) as total_users FROM USER;
SELECT COUNT(*) as total_jabatan FROM JABATAN;
SELECT COUNT(*) as total_guru FROM GURU;
SELECT COUNT(*) as total_tunjangan FROM TUNJANGAN;
SELECT COUNT(*) as total_kehadiran FROM REKAP_KEHADIRAN;
SELECT COUNT(*) as total_penggajian FROM PENGGAJIAN;

-- ======================================================
-- CLEANUP BACKUP TABLES (OPTIONAL - RUN MANUALLY)
-- ======================================================
/*
-- Uncomment these lines to remove backup tables after verification
DROP TABLE IF EXISTS backup_user_old;
DROP TABLE IF EXISTS backup_jabatan_old;
DROP TABLE IF EXISTS backup_guru_old;
DROP TABLE IF EXISTS backup_tunjangan_old;
DROP TABLE IF EXISTS backup_potongan_old;
DROP TABLE IF EXISTS backup_rekap_kehadiran_old;
DROP TABLE IF EXISTS backup_penggajian_old;
*/

-- ======================================================
-- MIGRATION COMPLETED
-- ======================================================
SELECT 'Database migration completed successfully!' as status;
