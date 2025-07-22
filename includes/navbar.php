<?php
session_start();
$user = $_SESSION['user'] ?? null;
?>
<nav class="bg-blue-700 text-white px-6 py-3 flex items-center justify-between shadow-md">
    <div class="flex items-center space-x-4">
        <a href="/index.php" class="font-bold text-xl tracking-wide hover:text-blue-200 transition">Penggajian Guru</a>
        <a href="/pages/dashboard.php" class="hover:text-blue-200 transition">Dashboard</a>
        <a href="/pages/guru.php" class="hover:text-blue-200 transition">Data Guru</a>
    </div>
    <div class="flex items-center space-x-4">
        <?php if ($user): ?>
            <span class="bg-blue-900 px-3 py-1 rounded text-sm font-semibold mr-2">👤 <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['role']) ?>)</span>
            <a href="/auth/logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded text-white font-semibold transition">Logout</a>
        <?php else: ?>
            <a href="/auth/login.php" class="bg-white text-blue-700 px-4 py-2 rounded font-semibold hover:bg-blue-100 transition">Login</a>
        <?php endif; ?>
    </div>
</nav> 