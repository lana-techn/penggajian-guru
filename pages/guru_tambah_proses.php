<?php
require_once '../config/koneksi.php';

$nip = $_POST['nip'] ?? '';
$nama = $_POST['nama_lengkap'] ?? '';
$jabatan_id = $_POST['jabatan_id'] ?? '';
$tanggal_masuk = $_POST['tanggal_masuk'] ?? '';
$status_pernikahan = $_POST['status_pernikahan'] ?? '';
$jumlah_anak = $_POST['jumlah_anak'] ?? 0;
$alamat = $_POST['alamat'] ?? '';
$no_telepon = $_POST['no_telepon'] ?? '';
$email = $_POST['email'] ?? '';

// Validasi NIP unik
$data = mysqli_query($conn, "SELECT id FROM guru WHERE nip = '".mysqli_real_escape_string($conn, $nip)."'");
if (mysqli_num_rows($data) > 0) {
    echo "<script>alert('NIP sudah terdaftar!');window.location='guru_tambah.php';</script>";
    exit;
}

$sql = "INSERT INTO guru (nip, nama_lengkap, jabatan_id, tanggal_masuk, status_pernikahan, jumlah_anak, alamat, no_telepon, email, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Aktif')";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ssissssss', $nip, $nama, $jabatan_id, $tanggal_masuk, $status_pernikahan, $jumlah_anak, $alamat, $no_telepon, $email);
if (mysqli_stmt_execute($stmt)) {
    header('Location: guru.php');
    exit;
} else {
    echo "<script>alert('Gagal menambah data!');window.location='guru_tambah.php';</script>";
} 