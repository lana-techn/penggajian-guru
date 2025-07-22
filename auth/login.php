<?php
require_once __DIR__ . '/../includes/functions.php';

// Jika sudah login, arahkan ke dashboard yang sesuai
if (isset($_SESSION['user_id'])) {
    $redirect_url = '../index.php';
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'kepala_sekolah') {
            $redirect_url = '../index_pemilik.php';
        } elseif ($_SESSION['role'] === 'guru') {
            $redirect_url = '../index_karyawan.php';
        }
    }
    header('Location: ' . $redirect_url);
    exit;
}

// LOGIC HANDLING
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
                $_SESSION['role'] = strtolower(str_replace(' ', '_', $user['akses']));
                $redirect_url = '../index.php';
                if ($user['akses'] === 'Kepala Sekolah') {
                    $redirect_url = '../index_kepsek.php';
                } elseif ($user['akses'] === 'Guru') {
                    $redirect_url = '../index_guru.php';
                }
                header('Location: ' . $redirect_url);
                exit;
            }
        }
        set_flash_message('error', 'Username atau password salah.');
    }
    $conn->close();
    header('Location: login.php');
    exit;
}

$page_title = 'Login';
generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - SD Unggulan Muhammadiyah Kretek</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .login-container { max-width: 350px; margin: 60px auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 16px #0001; padding: 2rem 2.5rem 2.5rem 2.5rem; border: 2px solid #222; }
        .login-container img { display: block; margin: 0 auto 1.2rem auto; width: 90px; }
        .login-container h2 { text-align: center; font-size: 1.5rem; font-weight: bold; margin-bottom: 0.2rem; letter-spacing: 1px; }
        .login-container p { text-align: center; color: #444; margin-bottom: 1.5rem; font-size: 1rem; }
        .login-container form { display: flex; flex-direction: column; gap: 1.1rem; }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%; padding: 0.7rem 1rem; border-radius: 8px; border: 1.5px solid #bbb; font-size: 1rem; outline: none; transition: border 0.2s; }
        .login-container input[type="text"]:focus,
        .login-container input[type="password"]:focus { border: 1.5px solid #0074d9; }
        .login-container button { width: 100%; background: #1769aa; color: #fff; font-weight: bold; border: none; border-radius: 8px; padding: 0.7rem 0; font-size: 1rem; margin-top: 0.5rem; cursor: pointer; transition: background 0.2s; }
        .login-container button:hover { background: #0d4e7a; }
        .login-container .register-link { display: block; text-align: center; margin-top: 1.2rem; color: #1769aa; text-decoration: none; font-size: 0.97rem; }
        .login-container .register-link:hover { text-decoration: underline; }
        .notif { padding: 0.7rem 1rem; border-radius: 0.5rem; margin-bottom: 1.2rem; border-left: 4px solid #ef4444; background: #fef2f2; color: #b91c1c; font-size: 0.97rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="../assets/images/logo.png" alt="Logo SDUMK">
        <h2>SELAMAT DATANG</h2>
        <p>Pada SD Unggulan Muhammadiyah Kretek</p>
        <?php if(function_exists('display_flash_message')) display_flash_message(); ?>
        <form method="POST" action="login.php" autocomplete="off">
            <?php if(function_exists('csrf_input')) csrf_input(); ?>
            <input type="text" name="username" placeholder="Username" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <a href="register.php" class="register-link">Belum punya akun? Daftar di sini</a>
    </div>
</body>
</html>
