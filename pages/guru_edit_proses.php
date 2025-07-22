<?php
require_once '../config/koneksi.php';

$id = $_POST['id'] ?? 0;
$nip = $_POST['nip'] ?? '';
$nama = $_POST['nama_lengkap'] ?? '';
$jabatan_id = $_POST['jabatan_id'] ?? '';
$tanggal_masuk = $_POST['tanggal_masuk'] ?? '';
$status_pernikahan = $_POST['status_pernikahan'] ?? '';
$jumlah_anak = $_POST['jumlah_anak'] ?? 0;
$alamat = $_POST['alamat'] ?? '';
$no_telepon = $_POST['no_telepon'] ?? '';
$email = $_POST['email'] ?? '';

// Validasi NIP unik (kecuali milik sendiri)
$data = mysqli_query($conn, "SELECT id FROM guru WHERE nip = '".mysqli_real_escape_string($conn, $nip)."' AND id != $id");
if (mysqli_num_rows($data) > 0) {
    echo "<script>alert('NIP sudah terdaftar!');window.location='guru_edit.php?id=$id';</script>";
    exit;
}

$sql = "UPDATE guru SET nip=?, nama_lengkap=?, jabatan_id=?, tanggal_masuk=?, status_pernikahan=?, jumlah_anak=?, alamat=?, no_telepon=?, email=? WHERE id=?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ssissssssi', $nip, $nama, $jabatan_id, $tanggal_masuk, $status_pernikahan, $jumlah_anak, $alamat, $no_telepon, $email, $id);
if (mysqli_stmt_execute($stmt)) {
    header('Location: guru.php');
    exit;
} else {
    echo "<script>alert('Gagal mengubah data!');window.location='guru_edit.php?id=$id';</script>";
} 