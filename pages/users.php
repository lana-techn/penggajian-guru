<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen User';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$akses_filter = $_GET['akses'] ?? '';
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        if ($id == $_SESSION['user_id']) {
            set_flash_message('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        } else {
            $stmt = $conn->prepare("DELETE FROM User WHERE id_user = ?");
            $stmt->bind_param('s', $id);
            if ($stmt->execute()) {
                set_flash_message('success', 'Data user berhasil dihapus.');
            } else {
                set_flash_message('error', 'Gagal menghapus data user.');
            }
            $stmt->close();
        }
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: users.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');
    $id_user = $_POST['id_user'] ?? null;
    $username = trim($_POST['username'] ?? '');
    $akses = $_POST['akses'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($akses)) {
        set_flash_message('error', 'Username dan akses wajib diisi.');
    } else {
        if ($id_user) { // Edit
            $stmt = $conn->prepare("UPDATE User SET username=?, akses=? WHERE id_user=?");
            $stmt->bind_param('sss', $username, $akses, $id_user);
            $action_text = 'diperbarui';
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt_pass = $conn->prepare("UPDATE User SET password=? WHERE id_user=?");
                $stmt_pass->bind_param('ss', $password_hash, $id_user);
                $stmt_pass->execute();
                $stmt_pass->close();
            }
        } else { // Tambah
            if (empty($password)) {
                set_flash_message('error', 'Password wajib diisi untuk user baru.');
                header('Location: users.php?action=add');
                exit;
            }
            $id_user = 'U' . date('ymdHis');
            $stmt_cek = $conn->prepare("SELECT id_user FROM User WHERE username=?");
            $stmt_cek->bind_param('s', $username);
            $stmt_cek->execute();
            if ($stmt_cek->get_result()->num_rows > 0) {
                set_flash_message('error', 'Username sudah terdaftar.');
                header('Location: users.php?action=add');
                exit;
            }
            $stmt_cek->close();
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO User (id_user, username, password, akses) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $id_user, $username, $password_hash, $akses);
            $action_text = 'ditambahkan';
        }
        if ($stmt->execute()) {
            set_flash_message('success', "User berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data user.");
        }
        $stmt->close();
        header('Location: users.php?action=list');
        exit;
    }
}

$user_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit User';
    $stmt = $conn->prepare("SELECT * FROM User WHERE id_user = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user_data) {
        set_flash_message('error', 'Data user tidak ditemukan.');
        header('Location: users.php?action=list');
        exit;
    }
} elseif ($action === 'add') {
    $page_title = 'Tambah User';
}

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<?php display_flash_message(); ?>

<?php if ($action === 'list'): ?>
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 font-poppins">Daftar User</h2>
                <p class="text-gray-500 text-sm">Kelola akses dan peran user sistem.</p>
            </div>
            <a href="users.php?action=add" class="w-full sm:w-auto bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center justify-center">
                <i class="fa-solid fa-plus mr-2"></i>Tambah User
            </a>
        </div>
        <form method="get" action="users.php" class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="action" value="list">
            <div class="md:col-span-2">
                <div class="relative">
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari username..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>
            <div>
                <select name="akses" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Semua Akses</option>
                    <option value="Admin" <?= $akses_filter == 'Admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="Kepala Sekolah" <?= $akses_filter == 'Kepala Sekolah' ? 'selected' : '' ?>>Kepala Sekolah</option>
                    <option value="Guru" <?= $akses_filter == 'Guru' ? 'selected' : '' ?>>Guru</option>
                </select>
            </div>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Username</th>
                        <th class="px-6 py-3">Akses</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $count_params = [];
                    $types_string_count = '';
                    $count_sql = "SELECT COUNT(id_user) as total FROM User WHERE username LIKE ?";
                    $search_param = "%" . $search . "%";
                    array_push($count_params, $search_param);
                    $types_string_count .= 's';
                    if ($akses_filter) {
                        $count_sql .= " AND akses = ?";
                        array_push($count_params, $akses_filter);
                        $types_string_count .= 's';
                    }
                    $stmt_count = $conn->prepare($count_sql);
                    if (!empty($types_string_count)) $stmt_count->bind_param($types_string_count, ...$count_params);
                    $stmt_count->execute();
                    $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
                    $total_pages = ceil($total_records / $records_per_page);
                    $stmt_count->close();
                    $data_params = $count_params;
                    $types_string_data = $types_string_count;
                    $sql = "SELECT * FROM User WHERE username LIKE ?";
                    if ($akses_filter) {
                        $sql .= " AND akses = ?";
                    }
                    $sql .= " ORDER BY akses, username ASC LIMIT ? OFFSET ?";
                    array_push($data_params, $records_per_page, $offset);
                    $types_string_data .= 'ii';
                    $stmt = $conn->prepare($sql);
                    if (!empty($types_string_data)) $stmt->bind_param($types_string_data, ...$data_params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()):
                    ?>
                            <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-mono text-xs"><?= e($row['id_user']) ?></td>
                                <td class="px-6 py-4 font-medium text-gray-900"><?= e($row['username']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full 
                                <?= $row['akses'] === 'Admin' ? 'bg-indigo-100 text-indigo-800' : '' ?>
                                <?= $row['akses'] === 'Kepala Sekolah' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                                <?= $row['akses'] === 'Guru' ? 'bg-green-100 text-green-800' : '' ?>
                            ">
                                        <?= e($row['akses']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-4">
                                        <a href="users.php?action=edit&id=<?= e($row['id_user']) ?>" class="text-blue-600 hover:text-blue-800" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                        <?php if ($row['id_user'] != $_SESSION['user_id']): ?>
                                            <a href="users.php?action=delete&id=<?= e($row['id_user']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Yakin ingin menghapus user ini?')" title="Hapus"><i class="fa-solid fa-trash-alt"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                    <?php endwhile;
                    } else {
                        echo '<tr><td colspan="4" class="text-center py-5 text-gray-500">Tidak ada data ditemukan.</td></tr>';
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
        <?php
        echo generate_pagination_links($page, $total_pages, ['action' => 'list', 'search' => $search, 'akses' => $akses_filter]);
        ?>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="bg-white p-8 rounded-xl shadow-lg max-w-lg mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-2 font-poppins"><?= $action === 'add' ? 'Tambah' : 'Edit' ?> User</h2>
        <p class="text-center text-gray-500 mb-8">Isi detail dan peran user baru.</p>
        <form method="POST" action="users.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id_user" value="<?= e($user_data['id_user'] ?? '') ?>">
            <div class="mb-5">
                <label for="username" class="block mb-2 text-sm font-medium text-gray-700">Username</label>
                <input type="text" id="username" name="username" value="<?= e($user_data['username'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            <div class="mb-5">
                <label for="akses" class="block mb-2 text-sm font-medium text-gray-700">Akses</label>
                <select id="akses" name="akses" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    <option value="Admin" <?= (isset($user_data) && $user_data['akses'] == 'Admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="Kepala Sekolah" <?= (isset($user_data) && $user_data['akses'] == 'Kepala Sekolah') ? 'selected' : '' ?>>Kepala Sekolah</option>
                    <option value="Guru" <?= (isset($user_data) && $user_data['akses'] == 'Guru') ? 'selected' : '' ?>>Guru</option>
                </select>
            </div>
            <div class="mb-8">
                <label for="password" class="block mb-2 text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" <?= ($action === 'add') ? 'required' : '' ?>>
                <?php if ($action === 'edit'): ?>
                    <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah password.</p>
                <?php endif; ?>
            </div>
            <div class="flex items-center justify-end space-x-4">
                <a href="users.php?action=list" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>