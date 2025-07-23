<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('kepala_sekolah');

$conn = db_connect();
$page_title = 'Tambah Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $akses = 'Admin';

    if (empty($username) || empty($password) || empty($password2)) {
        set_flash_message('error', 'Semua field wajib diisi.');
    } elseif ($password !== $password2) {
        set_flash_message('error', 'Konfirmasi password tidak cocok.');
    } else {
        $stmt_cek = $conn->prepare("SELECT id_user FROM User WHERE username=?");
        $stmt_cek->bind_param('s', $username);
        $stmt_cek->execute();
        if ($stmt_cek->get_result()->num_rows > 0) {
            set_flash_message('error', 'Username sudah terdaftar.');
        } else {
            $id_user = 'U' . date('ymdHis');
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO User (id_user, username, password, akses) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $id_user, $username, $password_hash, $akses);
            if ($stmt->execute()) {
                set_flash_message('success', 'Admin baru berhasil ditambahkan.');
            } else {
                set_flash_message('error', 'Gagal menambah admin.');
            }
            $stmt->close();
        }
        $stmt_cek->close();
    }
}

generate_csrf_token();
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="max-w-lg mx-auto mt-10 bg-white p-8 rounded-xl shadow-lg">
    <h2 class="text-2xl font-bold text-gray-800 text-center mb-2 font-poppins">Tambah Admin</h2>
    <p class="text-center text-gray-500 mb-8">Isi data admin baru di bawah ini.</p>
    <?php display_flash_message(); ?>
    <form method="POST" action="">
        <?php csrf_input(); ?>
        <div class="mb-5">
            <label for="username" class="block mb-2 text-sm font-medium text-gray-700">Username</label>
            <input type="text" id="username" name="username" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
        </div>
        <div class="mb-5">
            <label for="password" class="block mb-2 text-sm font-medium text-gray-700">Password</label>
            <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
        </div>
        <div class="mb-8">
            <label for="password2" class="block mb-2 text-sm font-medium text-gray-700">Konfirmasi Password</label>
            <input type="password" id="password2" name="password2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
        </div>
        <div class="flex items-center justify-end space-x-4">
            <a href="../../index_kepsek.php" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Kembali</a>
            <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all">Simpan</button>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 