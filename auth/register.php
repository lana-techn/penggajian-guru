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
    <title>Registrasi Akun - Sistem Penggajian Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-100 to-blue-300 min-h-screen flex items-center justify-center">
    <form action="proses_register.php" method="post" class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md animate-fade-in">
        <h1 class="text-3xl font-bold mb-6 text-center text-blue-700">Registrasi Akun</h1>
        <?php if (isset($_GET['error'])): ?>
        <div class="mb-4 p-2 bg-red-100 text-red-700 rounded text-center">
            <?php
            $err = $_GET['error'];
            if ($err == 1) echo 'Semua field wajib diisi!';
            elseif ($err == 2) echo 'Password dan konfirmasi tidak sama!';
            elseif ($err == 3) echo 'Username sudah terdaftar!';
            else echo 'Registrasi gagal, silakan coba lagi!';
            ?>
        </div>
        <?php endif; ?>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Username</label>
            <input type="text" name="username" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring focus:border-blue-400" required autocomplete="username">
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Password</label>
            <input type="password" name="password" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring focus:border-blue-400" required autocomplete="new-password">
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Konfirmasi Password</label>
            <input type="password" name="password_confirm" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring focus:border-blue-400" required autocomplete="new-password">
        </div>
        <div class="mb-6">
            <label class="block mb-1 font-semibold">Role</label>
            <select name="role" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring focus:border-blue-400" required>
                <option value="">-- Pilih Role --</option>
                <option value="guru">Guru</option>
                <option value="kepala_sekolah">Kepala Sekolah</option>
            </select>
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 font-semibold transition">Daftar</button>
        <p class="mt-4 text-center text-gray-600">Sudah punya akun? <a href="login.php" class="text-blue-600 hover:underline">Login di sini</a></p>
    </form>
    <script>
    // Animasi fade-in sederhana
    document.querySelector('form').classList.add('opacity-0');
    setTimeout(() => {
        document.querySelector('form').classList.remove('opacity-0');
        document.querySelector('form').classList.add('opacity-100');
    }, 100);
    </script>
</body>
</html> 