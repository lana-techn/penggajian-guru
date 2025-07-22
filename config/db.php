<?php
// config/db.php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'gaji_guru';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die('Koneksi gagal: ' . $conn->connect_error);
} 