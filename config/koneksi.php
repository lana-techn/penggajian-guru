<?php
// config/koneksi.php

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'gaji_guru';

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
} 