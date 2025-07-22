<?php
session_start();
$user = $_SESSION['user'] ?? null;
?>
<nav class="bg-blue-600 p-4 text-white flex justify-between items-center">
    <div class="font-bold text-lg">Sistem Penggajian Guru</div>
    <div>
        <?php if ($user): ?>
            <span class="mr-4">Halo, <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['role']) ?>)</span>
            <a href="/auth/logout.php" class="bg-red-500 px-3 py-1 rounded hover:bg-red-600">Logout</a>
        <?php endif; ?>
    </div>
</nav> 