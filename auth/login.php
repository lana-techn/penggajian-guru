<?php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Penggajian Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <form action="proses_login.php" method="post" class="bg-white p-8 rounded shadow-md w-full max-w-sm">
        <h1 class="text-2xl font-bold mb-6 text-center">Login</h1>
        <?php if (isset($_GET['registered'])): ?>
        <div class="mb-4 p-2 bg-green-100 text-green-700 rounded text-center">Registrasi berhasil! Silakan login.</div>
        <?php endif; ?>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Username</label>
            <input type="text" name="username" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring" required>
        </div>
        <div class="mb-6">
            <label class="block mb-1 font-semibold">Password</label>
            <input type="password" name="password" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring" required>
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 font-semibold">Login</button>
        <p class="mt-4 text-center text-gray-600">Belum punya akun? <a href="register.php" class="text-blue-600 hover:underline">Daftar di sini</a></p>
    </form>
</body>
</html> 