<?php
include '../includes/navbar.php';
require_once '../config/koneksi.php';
$id = $_GET['id'] ?? 0;
$sql = "SELECT g.*, j.nama_jabatan FROM guru g LEFT JOIN jabatan j ON g.jabatan_id = j.id WHERE g.id = $id";
$guru = mysqli_fetch_assoc(mysqli_query($conn, $sql));
if (!$guru) {
    echo '<div class="p-8">Data tidak ditemukan.</div>';
    exit;
}
?>
<div class="p-8 max-w-xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">Detail Guru</h1>
    <div class="bg-white rounded shadow p-6">
        <dl class="divide-y">
            <div class="py-2 flex justify-between"><dt class="font-semibold">NIP</dt><dd><?= htmlspecialchars($guru['nip']) ?></dd></div>
            <div class="py-2 flex justify-between"><dt class="font-semibold">Nama Lengkap</dt><dd><?= htmlspecialchars($guru['nama_lengkap']) ?></dd></div>
            <div class="py-2 flex justify-between"><dt class="font-semibold">Jabatan</dt><dd><?= htmlspecialchars($guru['nama_jabatan']) ?></dd></div>
            <div class="py-2 flex justify-between"><dt class="font-semibold">Tanggal Masuk</dt><dd><?= htmlspecialchars($guru['tanggal_masuk']) ?></dd></div>
            <div class="py-2 flex justify-between"><dt class="font-semibold">Status Pernikahan</dt><dd><?= htmlspecialchars($guru['status_pernikahan']) ?></dd></div>
            <div class="py-2 flex justify-between"><dt class="font-semibold">Jumlah Anak</dt><dd><?= htmlspecialchars($guru['jumlah_anak']) ?></dd></div>
            <div class="py-2 flex justify-between"><dt class="font-semibold">Alamat</dt><dd><?= htmlspecialchars($guru['alamat']) ?></dd></div>
            <div class="py-2 flex justify-between"><dt class="font-semibold">No. Telepon</dt><dd><?= htmlspecialchars($guru['no_telepon']) ?></dd></div>
            <div class="py-2 flex justify-between"><dt class="font-semibold">Email</dt><dd><?= htmlspecialchars($guru['email']) ?></dd></div>
            <div class="py-2 flex justify-between"><dt class="font-semibold">Status</dt><dd><?= htmlspecialchars($guru['status']) ?></dd></div>
        </dl>
        <div class="mt-6">
            <a href="guru.php" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300">Kembali</a>
        </div>
    </div>
</div> 