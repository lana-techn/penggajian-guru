<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole(['admin', 'kepala_sekolah']);

$conn = db_connect();
$page_title = 'Manajemen Pengguna';

// Check if user is Kepala Sekolah (read-only mode)
$isReadOnlyMode = ($_SESSION['role'] === 'kepala_sekolah');

// --- LOGIKA PROSES (CREATE, UPDATE, DELETE) ---

// Proses Tambah Admin by Kepala Sekolah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isReadOnlyMode) {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $password2 = $_POST['password2'];
    $akses = 'Admin'; // Kepala Sekolah can only add Admin
    
    if (empty($username) || empty($password) || empty($password2)) {
        set_flash_message('error', 'Semua field wajib diisi.');
    } elseif ($password !== $password2) {
        set_flash_message('error', 'Konfirmasi password tidak cocok.');
    } else {
        // Check if username already exists
        $stmt_cek = $conn->prepare("SELECT id_user FROM User WHERE username=?");
        $stmt_cek->bind_param('s', $username);
        $stmt_cek->execute();
        if ($stmt_cek->get_result()->num_rows > 0) {
            set_flash_message('error', 'Username sudah terdaftar.');
        } else {
            $id_user = 'U' . date('ymdHis');
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO User (id_user, username, password, akses) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $id_user, $username, $hashed_password, $akses);
            if ($stmt->execute()) {
                set_flash_message('success', 'Admin baru berhasil ditambahkan.');
            } else {
                set_flash_message('error', 'Gagal menambah admin.');
            }
            $stmt->close();
        }
        $stmt_cek->close();
    }
    header('Location: users.php');
    exit;
}

// Proses Tambah & Update (only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isReadOnlyMode) {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_user = $_POST['id_user'] ?? null;
    $username = trim($_POST['username']);
    $akses = $_POST['akses'];
    $password = $_POST['password'];

    // Validasi dasar
    if (empty($username) || empty($akses)) {
        set_flash_message('error', 'Username dan Hak Akses wajib diisi.');
    } elseif (!$id_user && empty($password)) {
        set_flash_message('error', 'Password wajib diisi untuk pengguna baru.');
    } else {
        if ($id_user) { // Update
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE User SET username=?, password=?, akses=? WHERE id_user=?");
                $stmt->bind_param('ssss', $username, $hashed_password, $akses, $id_user);
            } else {
                $stmt = $conn->prepare("UPDATE User SET username=?, akses=? WHERE id_user=?");
                $stmt->bind_param('sss', $username, $akses, $id_user);
            }
            $action_text = 'diperbarui';
        } else { // Tambah
            $id_user = 'U' . date('ymdHis');
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO User (id_user, username, password, akses) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $id_user, $username, $hashed_password, $akses);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) {
            set_flash_message('success', "Data pengguna berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data pengguna: " . $stmt->error);
        }
        $stmt->close();
    }
    header('Location: users.php');
    exit;
}

// Proses Hapus (only for admin)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && !$isReadOnlyMode) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $id_user = $_GET['id'];
        // Cek apakah user terikat dengan guru
        $check_stmt = $conn->prepare("SELECT id_guru FROM Guru WHERE id_user = ?");
        $check_stmt->bind_param('s', $id_user);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            set_flash_message('error', 'Tidak dapat menghapus pengguna yang masih terikat dengan data guru.');
        } else {
            $stmt = $conn->prepare("DELETE FROM User WHERE id_user = ?");
            $stmt->bind_param("s", $id_user);
            if ($stmt->execute()) {
                set_flash_message('success', 'Data pengguna berhasil dihapus.');
            } else {
                set_flash_message('error', 'Gagal menghapus pengguna.');
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: users.php');
    exit;
}

// --- LOGIKA PENGAMBILAN DATA ---
$search = $_GET['search'] ?? '';
$search_param = "%{$search}%";
$users_result = $conn->execute_query("SELECT id_user, username, akses FROM User WHERE username LIKE ? ORDER BY username ASC", [$search_param]);

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="crudPage()">
    <!-- Tombol Tambah dan Judul Halaman -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 font-poppins"><?= e($page_title) ?><?php if ($isReadOnlyMode): ?> <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full ml-2">Mode Tampilan</span><?php endif; ?></h1>
            <p class="text-gray-500 mt-1"><?= $isReadOnlyMode ? 'Lihat daftar pengguna dan tambah admin baru.' : 'Kelola akun dan hak akses untuk sistem.' ?></p>
        </div>
        <?php if (!$isReadOnlyMode): ?>
        <button @click="showForm = true; isEdit = false; resetForm()" class="bg-green-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center transition">
            <i class="fa-solid fa-plus mr-2"></i> Tambah Pengguna
        </button>
        <?php endif; ?>
    </div>

    <?php display_flash_message(); ?>

    <!-- Form Tambah Admin for Kepala Sekolah -->
    <?php if ($isReadOnlyMode): ?>
    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg mb-8 border-t-4 border-blue-500">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 font-poppins">Tambah Admin</h2>
        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-4 rounded-md mb-6 text-sm">
            <p><strong>Info:</strong> Anda hanya dapat menambahkan pengguna dengan hak akses Admin.</p>
        </div>
        <form method="POST" action="users.php">
            <?php csrf_input(); ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" name="username" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
                <div>
                    <label for="password2" class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                    <input type="password" name="password2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-blue-700 font-semibold flex items-center transition">
                    <i class="fa fa-save mr-2"></i> Tambah Admin
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Form Tambah/Edit (Hidden by default, only for admin) -->
    <?php if (!$isReadOnlyMode): ?>
    <div x-show="showForm" x-transition class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg mb-8 border-t-4 border-green-500">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 font-poppins" x-text="isEdit ? 'Edit Pengguna' : 'Tambah Pengguna Baru'"></h2>
        <form method="POST" action="users.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id_user" x-model="formData.id_user">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" name="username" x-model="formData.username" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" required>
                </div>
                <div>
                    <label for="akses" class="block text-sm font-medium text-gray-700">Hak Akses</label>
                    <select name="akses" x-model="formData.akses" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" required>
                        <option value="">- Pilih Hak Akses -</option>
                        <option value="Admin">Admin</option>
                        <option value="Kepala Sekolah">Kepala Sekolah</option>
                        <option value="Guru">Guru</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" :required="!isEdit">
                    <p class="text-xs text-gray-500 mt-1" x-show="isEdit">Kosongkan jika tidak ingin mengubah password.</p>
                </div>
            </div>

            <!-- Tombol Aksi Form -->
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" @click="showForm = false" class="bg-gray-200 text-gray-700 px-5 py-2.5 rounded-lg hover:bg-gray-300 font-semibold transition">Batal</button>
                <button type="submit" class="bg-green-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center transition">
                    <i class="fa fa-save mr-2"></i> <span x-text="isEdit ? 'Simpan Perubahan' : 'Simpan Data'"></span>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Daftar Pengguna -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg">
         <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800 font-poppins">Daftar Pengguna</h3>
            <form method="GET" action="" class="w-full max-w-sm">
                <div class="relative">
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari username..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fa-solid fa-search text-gray-400"></i></div>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-gray-600">
                <thead class="bg-gray-100 text-gray-700 uppercase font-poppins text-xs">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Username</th>
                        <th class="px-4 py-3">Hak Akses</th>
                        <?php if (!$isReadOnlyMode): ?><th class="px-4 py-3 text-center">Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users_result->num_rows > 0): ?>
                        <?php $no = 1; while ($row = $users_result->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= $no++ ?></td>
                                <td class="px-4 py-3 font-semibold text-gray-900"><?= e($row['username']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                          :class="{
                                            'bg-blue-100 text-blue-800': '<?= $row['akses'] ?>' === 'Admin',
                                            'bg-purple-100 text-purple-800': '<?= $row['akses'] ?>' === 'Kepala Sekolah',
                                            'bg-green-100 text-green-800': '<?= $row['akses'] ?>' === 'Guru'
                                          }">
                                        <?= e($row['akses']) ?>
                                    </span>
                                </td>
                                <?php if (!$isReadOnlyMode): ?>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-3">
                                        <button @click="editUser(<?= htmlspecialchars(json_encode($row)) ?>)" class="text-blue-600 hover:text-blue-800" title="Edit">
                                            <i class="fa-solid fa-pencil fa-fw"></i>
                                        </button>
                                        <a href="?action=delete&id=<?= e($row['id_user']) ?>&token=<?= $_SESSION['csrf_token'] ?>" onclick="return confirm('Yakin ingin menghapus pengguna ini?')" class="text-red-600 hover:text-red-800" title="Hapus">
                                            <i class="fa-solid fa-trash fa-fw"></i>
                                        </a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $isReadOnlyMode ? '3' : '4' ?>" class="text-center py-10 text-gray-500">
                                <i class="fa-solid fa-users-slash fa-3x mb-3"></i>
                                <p>Tidak ada pengguna yang ditemukan.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function crudPage() {
    return {
        showForm: false,
        isEdit: false,
        formData: {},
        
        init() {
            this.resetForm();
        },

        resetForm() {
            this.formData = {
                id_user: null,
                username: '',
                akses: '',
                password: ''
            };
        },

        editUser(userData) {
            this.isEdit = true;
            this.formData = {
                id_user: userData.id_user,
                username: userData.username,
                akses: userData.akses,
                password: '' // Kosongkan password saat edit
            };
            this.showForm = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
