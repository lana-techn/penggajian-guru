<?php
// 1. SETUP & LOGIKA
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Guru';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;
$jabatan_list = $conn->query("SELECT id_jabatan, nama_jabatan FROM Jabatan ORDER BY nama_jabatan")->fetch_all(MYSQLI_ASSOC);
$query_users = "
    SELECT u.id_user, u.username 
    FROM User u
    LEFT JOIN Guru g ON u.id_user = g.id_user
    WHERE u.akses = 'Guru' AND g.id_guru IS NULL";
$users_list = $conn->query($query_users)->fetch_all(MYSQLI_ASSOC);

if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM Guru WHERE id_guru = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) set_flash_message('success', 'Data guru berhasil dihapus.');
        else set_flash_message('error', 'Gagal menghapus data guru.');
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: guru.php?action=list');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');
    $id_guru = $_POST['id_guru'] ?? null;
    $nama_guru = trim($_POST['nama_guru']);
    $nipm = trim($_POST['nipm']);
    $alamat = trim($_POST['alamat']);
    $no_hp = trim($_POST['no_hp']);
    $email = trim($_POST['email']);
    $tgl_masuk = $_POST['tgl_masuk'];
    $id_jabatan = $_POST['id_jabatan'];
    $status_kawin = $_POST['status_kawin'];
    $jml_anak = $_POST['jml_anak'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $id_user = $_POST['id_user'];

    if (empty($nama_guru) || empty($nipm) || empty($alamat) || empty($no_hp) || empty($tgl_masuk) || empty($id_jabatan) || empty($status_kawin) || $jml_anak === '' || empty($jenis_kelamin) || empty($id_user)) {
        set_flash_message('error', 'Semua kolom wajib diisi.');
    } else {
        if ($id_guru) {
            $stmt = $conn->prepare("UPDATE Guru SET id_user=?, id_jabatan=?, nama_guru=?, jenis_kelamin=?, no_hp=?, nipm=?, tgl_masuk=?, email=?, status_kawin=?, jml_anak=? WHERE id_guru=?");
            $stmt->bind_param("sssssssssis", $id_user, $id_jabatan, $nama_guru, $jenis_kelamin, $no_hp, $nipm, $tgl_masuk, $email, $status_kawin, $jml_anak, $id_guru);
            $action_text = 'diperbarui';
        } else {
            $id_guru = 'G' . date('ymdHis');
            $stmt = $conn->prepare("INSERT INTO Guru (id_guru, id_user, id_jabatan, nama_guru, jenis_kelamin, no_hp, nipm, tgl_masuk, email, status_kawin, jml_anak) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssi", $id_guru, $id_user, $id_jabatan, $nama_guru, $jenis_kelamin, $no_hp, $nipm, $tgl_masuk, $email, $status_kawin, $jml_anak);
            $action_text = 'ditambahkan';
        }
        if ($stmt->execute()) set_flash_message('success', "Data guru berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses data guru: " . $stmt->error);
        $stmt->close();
        header('Location: guru.php?action=list');
        exit;
    }
}

$guru_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Guru';
    $stmt = $conn->prepare("SELECT * FROM Guru WHERE id_guru = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $guru_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$guru_data) {
        set_flash_message('error', 'Data guru tidak ditemukan.');
        header('Location: guru.php?action=list');
        exit;
    }
    $stmt_user_edit = $conn->prepare("SELECT id_user, username FROM User WHERE id_user = ?");
    $stmt_user_edit->bind_param("s", $guru_data['id_user']);
    $stmt_user_edit->execute();
    $user_terpilih = $stmt_user_edit->get_result()->fetch_assoc();
    if ($user_terpilih && !in_array($user_terpilih, $users_list)) {
        array_unshift($users_list, $user_terpilih);
    }
    $stmt_user_edit->close();
} elseif ($action === 'add') {
    $page_title = 'Tambah Guru';
}

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<?php display_flash_message(); ?>

<?php if ($action === 'list'): ?>
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 font-poppins">Daftar Guru</h2>
                <p class="text-gray-500 text-sm">Kelola data, status, dan informasi guru.</p>
            </div>
            <a href="guru.php?action=add" class="w-full sm:w-auto bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center justify-center">
                <i class="fa-solid fa-plus mr-2"></i>Tambah
            </a>
        </div>
        
        <form method="get" action="guru.php" class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="action" value="list">
            <div class="md:col-span-2">
                <div class="relative"><input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari nama atau NIP guru..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fa-solid fa-search text-gray-400"></i></div></div>
            </div>
            <div>
                <select name="status" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Semua Status</option>
                    <option value="Aktif" <?= $status_filter == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="Tidak Aktif" <?= $status_filter == 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                </select>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3">No</th><th class="px-6 py-3">NIP</th><th class="px-6 py-3">Nama Lengkap</th><th class="px-6 py-3">Jabatan</th><th class="px-6 py-3">Tanggal Masuk</th><th class="px-6 py-3">No Telepon</th><th class="px-6 py-3">Email</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Perbaikan query list dan count agar sesuai database.sql
                    echo '<div class="overflow-x-auto">';
                    $count_params = []; $types_string_count = '';
                    $count_sql = "SELECT COUNT(g.id_guru) as total FROM Guru g WHERE (g.nama_guru LIKE ? OR g.nipm LIKE ? )";
                    $search_param = "%" . $search . "%";
                    array_push($count_params, $search_param, $search_param);
                    $types_string_count .= 'ss';
                    if ($status_filter) { $count_sql .= " AND g.status_kawin = ?"; array_push($count_params, $status_filter); $types_string_count .= 's'; }
                    $stmt_count = $conn->prepare($count_sql);
                    if(!empty($types_string_count)) $stmt_count->bind_param($types_string_count, ...$count_params);
                    $stmt_count->execute();
                    $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
                    $total_pages = ceil($total_records / $records_per_page);
                    $stmt_count->close();
                    $data_params = $count_params; $types_string_data = $types_string_count;
                    $sql = "SELECT g.*, j.nama_jabatan FROM Guru g LEFT JOIN Jabatan j ON g.id_jabatan = j.id_jabatan WHERE (g.nama_guru LIKE ? OR g.nipm LIKE ? )";
                    if ($status_filter) { $sql .= " AND g.status_kawin = ?"; }
                    $sql .= " ORDER BY g.nama_guru ASC LIMIT ? OFFSET ?";
                    array_push($data_params, $records_per_page, $offset);
                    $types_string_data .= 'ii';
                    $stmt = $conn->prepare($sql);
                    if(!empty($types_string_data)) $stmt->bind_param($types_string_data, ...$data_params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $no = $offset + 1;
                    if($result->num_rows > 0) { while ($row = $result->fetch_assoc()): ?>
                    <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-4"><?= $no++ ?></td>
                        <td class="px-6 py-4 font-mono text-xs"><?= e($row['nipm']) ?></td>
                        <td class="px-6 py-4 font-medium text-gray-900"><?= e($row['nama_guru']) ?></td>
                        <td class="px-6 py-4"><?= e($row['nama_jabatan']) ?? 'N/A' ?></td>
                        <td class="px-6 py-4"><?= e(date('d M Y', strtotime($row['tgl_masuk']))) ?></td>
                        <td class="px-6 py-4"><?= e($row['no_hp']) ?></td>
                        <td class="px-6 py-4"><?= e($row['email']) ?></td>
                        <td class="px-6 py-4"> <span class="px-2.5 py-1 text-xs font-semibold rounded-full"> <?= e($row['status_kawin']) ?> </span> </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-4">
                                <a href="guru.php?action=edit&id=<?= e($row['id_guru']) ?>" class="text-blue-600 hover:text-blue-800" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                <a href="guru.php?action=delete&id=<?= e($row['id_guru']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Yakin?')" title="Hapus"><i class="fa-solid fa-trash-alt"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; } else { echo '<tr><td colspan="9" class="text-center py-5 text-gray-500">Tidak ada data ditemukan.</td></tr>'; } $stmt->close(); $conn->close();
                    echo '</tbody></table></div>';
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php 
        echo generate_pagination_links($page, $total_pages, 'guru.php', ['action' => 'list', 'search' => $search, 'status' => $status_filter]);
        ?>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="bg-white p-8 rounded-xl shadow-lg max-w-4xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-2 font-poppins"><?= ucfirst($action) ?> Data Guru</h2>
        <p class="text-center text-gray-500 mb-8">Lengkapi semua informasi yang diperlukan.</p>
        
        <form method="POST" action="guru.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id" value="<?= e($guru_data['id_guru'] ?? '') ?>">
            <div class="grid md:grid-cols-2 gap-x-6 gap-y-5">
                <div>
                    <label for="nama_lengkap" class="block mb-2 text-sm font-medium text-gray-700">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?= e($guru_data['nama_guru'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>
                <div>
                    <label for="nip" class="block mb-2 text-sm font-medium text-gray-700">NIP</label>
                    <input type="text" id="nip" name="nip" value="<?= e($guru_data['nipm'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>
                <div>
                    <label for="jabatan_id" class="block mb-2 text-sm font-medium text-gray-700">Jabatan</label>
                    <select id="jabatan_id" name="jabatan_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <option value="">- Pilih Jabatan -</option>
                        <?php foreach($jabatan_list as $jabatan): ?>
                        <option value="<?= e($jabatan['id_jabatan']) ?>" <?= (isset($guru_data) && $guru_data['id_jabatan'] == $jabatan['id_jabatan']) ? 'selected' : '' ?>><?= e($jabatan['nama_jabatan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="status_pernikahan" class="block mb-2 text-sm font-medium text-gray-700">Status Pernikahan</label>
                    <select id="status_pernikahan" name="status_pernikahan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <option value="menikah" <?= (isset($guru_data) && $guru_data['status_kawin'] == 'menikah') ? 'selected' : '' ?>>Menikah</option>
                        <option value="belum_menikah" <?= (isset($guru_data) && $guru_data['status_kawin'] == 'belum_menikah') ? 'selected' : '' ?>>Belum Menikah</option>
                    </select>
                </div>
                <div>
                    <label for="jumlah_anak" class="block mb-2 text-sm font-medium text-gray-700">Jumlah Anak</label>
                    <input type="number" id="jumlah_anak" name="jumlah_anak" value="<?= e($guru_data['jml_anak'] ?? 0) ?>" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>
                <div>
                    <label for="no_telepon" class="block mb-2 text-sm font-medium text-gray-700">No. Telepon</label>
                    <input type="tel" id="no_telepon" name="no_telepon" value="<?= e($guru_data['no_hp'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>
                <div>
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" value="<?= e($guru_data['email'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>
                <div>
                    <label for="tanggal_masuk" class="block mb-2 text-sm font-medium text-gray-700">Tanggal Masuk</label>
                    <input type="date" id="tanggal_masuk" name="tanggal_masuk" value="<?= e($guru_data['tgl_masuk'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>
                <div>
                    <label for="user_id" class="block mb-2 text-sm font-medium text-gray-700">Akun User (Username)</label>
                    <select id="user_id" name="user_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <option value="">- Pilih Akun User -</option>
                         <?php foreach($users_list as $user): ?>
                        <option value="<?= e($user['id_user']) ?>" <?= (isset($guru_data) && $guru_data['id_user'] == $user['id_user']) ? 'selected' : '' ?>><?= e($user['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Hanya menampilkan akun 'guru' yang belum terikat.</p>
                </div>
                <div>
                    <label for="status" class="block mb-2 text-sm font-medium text-gray-700">Status Guru</label>
                    <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <option value="Aktif" <?= (!isset($guru_data) || (isset($guru_data) && $guru_data['status_kawin'] == 'Aktif')) ? 'selected' : '' ?>>Aktif</option>
                        <option value="Tidak Aktif" <?= (isset($guru_data) && $guru_data['status_kawin'] == 'Tidak Aktif') ? 'selected' : '' ?>>Tidak Aktif</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center justify-end space-x-4 mt-8">
                <a href="guru.php?action=list" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>