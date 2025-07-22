<?php include '../includes/navbar.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Penggajian Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body>
<div class="p-8 min-h-screen bg-gradient-to-br from-blue-50 to-blue-200">
    <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-lg p-8 mt-8 animate-fade-in">
        <h1 class="text-3xl font-bold mb-4 text-blue-700">Dashboard</h1>
        <p class="mb-6 text-gray-700">Selamat datang di <span class="font-semibold">Sistem Penggajian Guru</span>. Kelola data guru, absensi, dan penggajian dengan mudah dan efisien.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-blue-100 rounded-lg p-6 flex flex-col items-center shadow hover:shadow-lg transition">
                <div class="text-4xl mb-2">👩‍🏫</div>
                <div class="font-bold text-lg mb-1">Data Guru</div>
                <div class="text-gray-600 text-sm mb-2">Lihat, tambah, edit, dan nonaktifkan data guru.</div>
                <a href="guru.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 font-semibold transition">Kelola Guru</a>
            </div>
            <div class="bg-blue-100 rounded-lg p-6 flex flex-col items-center shadow hover:shadow-lg transition">
                <div class="text-4xl mb-2">💰</div>
                <div class="font-bold text-lg mb-1">Penggajian</div>
                <div class="text-gray-600 text-sm mb-2">Kelola proses penggajian dan riwayat pembayaran.</div>
                <a href="#" class="bg-blue-600 text-white px-4 py-2 rounded font-semibold opacity-50 cursor-not-allowed">Segera Hadir</a>
            </div>
        </div>
    </div>
</div>
</body>
</html> 