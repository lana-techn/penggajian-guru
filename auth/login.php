<?php
// Memulai sesi di awal, ini adalah praktik yang baik
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/functions.php';

// LOGIKA PERBAIKAN: Pengalihan jika sudah login dan session lengkap
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $redirect_url = '../index.php'; // URL default
    if ($_SESSION['role'] === 'kepala_sekolah') {
        $redirect_url = '../index_kepsek.php'; 
    } elseif ($_SESSION['role'] === 'guru') {
        $redirect_url = '../index_guru.php';
    }
    header('Location: ' . $redirect_url);
    exit;
} elseif (isset($_SESSION['user_id']) || isset($_SESSION['role'])) {
    // Session tidak lengkap, hapus dan tampilkan form login
    session_unset();
    session_destroy();
    session_start();
}

// LOGIKA ASLI ANDA (BAGIAN 2): Proses login saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = db_connect();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        set_flash_message('error', 'Username dan password wajib diisi.');
    } else {
        $stmt = $conn->prepare("SELECT id_user, username, password, akses FROM User WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                // Standarisasi role agar konsisten di seluruh aplikasi
                $_SESSION['role'] = strtolower(str_replace(' ', '_', $user['akses']));
                
                $redirect_url = '../index.php'; // URL default
                if ($_SESSION['role'] === 'kepala_sekolah') {
                    $redirect_url = '../index_kepsek.php';
                } elseif ($_SESSION['role'] === 'guru') {
                    $redirect_url = '../index_guru.php';
                }
                header('Location: ' . $redirect_url);
                exit;
            }
        }
        // Jika username tidak ditemukan atau password salah
        set_flash_message('error', 'Username atau password salah.');
    }
    
    // Logika pengalihan setelah proses selesai (baik error atau tidak)
    // Ditutup koneksinya sebelum redirect
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();

    header('Location: login.php');
    exit;
}

// Persiapan untuk menampilkan halaman
$page_title = 'Login';
// Fungsi CSRF asli Anda tetap dipanggil, karena ini tidak menyebabkan error
generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SD Unggulan Muhammadiyah Kretek</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Menggunakan font Inter yang lebih modern untuk UI */
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="flex items-center justify-center min-h-screen bg-gradient-to-br from-gray-900 to-gray-700 p-4">
        
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8 space-y-6 transform transition-all hover:scale-102">
            
            <div class="text-center space-y-4">
                <img src="../assets/images/logo.png" alt="Logo SDUMK" class="w-20 h-20 mx-auto">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Selamat Datang</h1>
                    <p class="text-gray-500">Login ke akun Anda</p>
                </div>
            </div>

            <?php if(function_exists('display_flash_message')) { display_flash_message(); } ?>
            
            <form method="POST" action="login.php" autocomplete="off" class="space-y-6">
                <?php if(function_exists('csrf_input')) { csrf_input(); } ?>
                
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                    </div>
                    <input type="text" name="username" placeholder="Username" required autofocus class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow">
                </div>
                
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 0 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    </div>
                    <input id="password" type="password" name="password" placeholder="Password" required class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow">
                    <button type="button" id="password-toggle" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                        <svg id="eye-icon" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639l4.43-4.44a1.013 1.013 0 0 1 1.436 0l4.43 4.44a1.013 1.013 0 0 1 0 .639l-4.43 4.44a1.013 1.013 0 0 1-1.436 0l-4.43-4.44Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        <svg id="eye-slash-icon" class="w-5 h-5 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.243 4.243L6.228 6.228" /></svg>
                    </button>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 ease-in-out">
                    Login
                </button>
                
                <div class="text-center">
                    <a href="register.php" class="text-sm text-blue-600 hover:underline">
                        Belum punya akun? Daftar di sini
                    </a>
                </div>
            </form>

        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('password-toggle');
        const eyeIcon = document.getElementById('eye-icon');
        const eyeSlashIcon = document.getElementById('eye-slash-icon');

        passwordToggle.addEventListener('click', () => {
            // Cek tipe input saat ini
            const isPassword = passwordInput.type === 'password';
            
            // Ubah tipe input
            passwordInput.type = isPassword ? 'text' : 'password';
            
            // Tukar ikon yang ditampilkan
            eyeIcon.classList.toggle('hidden', isPassword);
            eyeSlashIcon.classList.toggle('hidden', !isPassword);
        });
    </script>

</body>
</html>