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
    <link href="/public/tailwind.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <form action="proses_login.php" method="post" class="bg-white p-8 rounded shadow-md w-full max-w-sm">
        <h1 class="text-2xl font-bold mb-6 text-center">Login</h1>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Username</label>
            <input type="text" name="username" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring" required>
        </div>
        <div class="mb-6">
            <label class="block mb-1 font-semibold">Password</label>
            <input type="password" name="password" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring" required>
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 font-semibold">Login</button>
    </form>
</body>
</html> 