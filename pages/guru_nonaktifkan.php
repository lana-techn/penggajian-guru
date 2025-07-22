<?php
require_once '../config/koneksi.php';
$id = $_GET['id'] ?? 0;
$sql = "UPDATE guru SET status='Tidak Aktif' WHERE id=?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
header('Location: guru.php');
exit; 