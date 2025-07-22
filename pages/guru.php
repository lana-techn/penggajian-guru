<?php
include '../includes/navbar.php';
require_once '../config/koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Guru - Sistem Penggajian Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body>
<div class="p-8 min-h-screen bg-gradient-to-br from-blue-50 to-blue-200">
    <div class="max-w-5xl mx-auto bg-white rounded-xl shadow-lg p-8 mt-8 animate-fade-in">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-blue-700">Data Guru</h1>
            <a href="guru_tambah.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 font-semibold transition">+ Tambah Guru</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border rounded shadow">
                <thead class="bg-blue-100">
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
                    <tr class="border-b hover:bg-blue-50 transition">
                        <td class="px-4 py-2 border"><?= htmlspecialchars($row['nip']) ?></td>
                        <td class="px-4 py-2 border"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                        <td class="px-4 py-2 border"><?= htmlspecialchars($row['nama_jabatan']) ?></td>
                        <td class="px-4 py-2 border">
                            <span class="px-2 py-1 rounded text-xs font-semibold <?= $row['status'] === 'Aktif' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
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
</div>
</body>
</html> 