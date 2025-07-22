<?php
// 1. PENGATURAN DATABASE

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gaji_guru');

// 2. PENGATURAN APLIKASI
define('APP_NAME', 'Sistem Penggajian Guru');


define('BASE_URL', 'http://localhost/penggajian-guru'); 

// 3. PENGATURAN KEAMANAN & SESI
define('SESSION_TIMEOUT', 3600); // Durasi sesi dalam detik (1 jam)

// 4. PENGATURAN ZONA WAKTU
date_default_timezone_set('Asia/Jakarta');

// 5. PENGATURAN ERROR REPORTING (matikan di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>