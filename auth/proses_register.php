<?php
require_once '../config/koneksi.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$role = $_POST['role'] ?? '';

// Validasi
if (!$username || !$password || !$password_confirm || !$role) {
    header('Location: register.php?error=1');
    exit;
}
if ($password !== $password_confirm) {
    header('Location: register.php?error=2');
    exit;
}
// Cek username unik
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_fetch_assoc($result)) {
    header('Location: register.php?error=3');
    exit;
}
// Hash password
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'sss', $username, $hash, $role);
if (mysqli_stmt_execute($stmt)) {
    header('Location: login.php?registered=1');
    exit;
} else {
    header('Location: register.php?error=4');
    exit;
} 