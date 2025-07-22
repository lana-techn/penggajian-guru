<?php
include '../includes/navbar.php';
require_once '../config/koneksi.php';

// Ambil data guru dan jabatan
$sql = "SELECT g.*, j.nama_jabatan FROM guru g LEFT JOIN jabatan j ON g.jabatan_id = j.id ORDER BY g.status DESC, g.nama_lengkap ASC";
$result = mysqli_query($conn, $sql);
?>
<div class="p-8">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Data Guru</h1>
        <a href="guru_tambah.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">+ Tambah Guru</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border rounded shadow">
            <thead>
                <tr>
                    <th class="px-4 py-2 border">NIP</th>
                    <th class="px-4 py-2 border">Nama</th>
                    <th class="px-4 py-2 border">Jabatan</th>
                    <th class="px-4 py-2 border">Status</th>
                    <th class="px-4 py-2 border">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['nip']) ?></td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['nama_jabatan']) ?></td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['status']) ?></td>
                    <td class="px-4 py-2 border">
                        <a href="guru_detail.php?id=<?= $row['id'] ?>" class="text-blue-600 hover:underline mr-2">Detail</a>
                        <a href="guru_edit.php?id=<?= $row['id'] ?>" class="text-yellow-600 hover:underline mr-2">Edit</a>
                        <?php if ($row['status'] === 'Aktif'): ?>
                        <a href="guru_nonaktifkan.php?id=<?= $row['id'] ?>" class="text-red-600 hover:underline" onclick="return confirm('Nonaktifkan guru ini?')">Nonaktifkan</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div> 