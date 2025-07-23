<?php
// 1. SETUP & LOGIKA
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Jabatan';

// Logika Pagination & Pencarian
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        // Cek apakah jabatan masih digunakan oleh guru
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM Guru WHERE id_jabatan = ?");
        $stmt_check->bind_param("s", $id);
        $stmt_check->execute();
        $count = $stmt_check->get_result()->fetch_row()[0];
        $stmt_check->close();

        if ($count > 0) {
            set_flash_message('error', 'Jabatan tidak bisa dihapus karena masih digunakan oleh guru.');
        } else {
            $stmt = $conn->prepare("DELETE FROM Jabatan WHERE id_jabatan = ?");
            $stmt->bind_param("s", $id);
            if ($stmt->execute()) set_flash_message('success', 'Data jabatan berhasil dihapus.');
            else set_flash_message('error', 'Gagal menghapus data jabatan.');
            $stmt->close();
        }
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: jabatan.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_jabatan = $_POST['id_jabatan'] ?? null;
    $nama_jabatan = trim($_POST['nama_jabatan'] ?? '');
    $gaji_awal = $_POST['gaji_awal'] ?? '';
    $kenaikan_pertahun = $_POST['kenaikan_pertahun'] ?? 0;

    if (empty($nama_jabatan) || $gaji_awal === '') {
        set_flash_message('error', 'Semua kolom wajib diisi.');
    } else {
        if ($id_jabatan) { // Edit
            $stmt = $conn->prepare("UPDATE Jabatan SET nama_jabatan = ?, gaji_awal = ?, kenaikan_pertahun = ? WHERE id_jabatan = ?");
            $stmt->bind_param("sdds", $nama_jabatan, $gaji_awal, $kenaikan_pertahun, $id_jabatan);
            $action_text = 'diperbarui';
        } else { // Tambah
            $id_jabatan = 'J' . date('ymdHis');
            $stmt = $conn->prepare("INSERT INTO Jabatan (id_jabatan, nama_jabatan, gaji_awal, kenaikan_pertahun) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssdd", $id_jabatan, $nama_jabatan, $gaji_awal, $kenaikan_pertahun);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) set_flash_message('success', "Jabatan berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses data jabatan: " . $stmt->error);

        $stmt->close();
        header('Location: jabatan.php?action=list');
        exit;
    }
}

$jabatan_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Jabatan';
    $stmt = $conn->prepare("SELECT * FROM Jabatan WHERE id_jabatan = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $jabatan_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$jabatan_data) {
        set_flash_message('error', 'Data jabatan tidak ditemukan.');
        header('Location: jabatan.php?action=list');
        exit;
    }
} elseif ($action === 'add') {
    $page_title = 'Tambah Jabatan';
}

generate_csrf_token();

// 2. MEMANGGIL TAMPILAN (VIEW)
require_once __DIR__ . '/../includes/header.php';
?>

<?php display_flash_message(); ?>

<?php if ($action === 'list'): ?>
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 font-poppins">Daftar Jabatan</h2>
                <p class="text-gray-500 text-sm">Kelola semua jabatan yang tersedia di sekolah.</p>
            </div>
            <a href="jabatan.php?action=add" class="w-full sm:w-auto bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 text-sm font-semibold shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Jabatan
            </a>
        </div>

        <form method="get" action="jabatan.php" class="mb-6">
            <input type="hidden" name="action" value="list">
            <div class="relative">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari nama jabatan..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-search text-gray-400"></i>
                </div>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Nama Jabatan</th>
                        <th class="px-6 py-3">Gaji Pokok</th>
                        <th class="px-6 py-3">Kenaikan Gaji Tahunan</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Query untuk mengambil data dengan pagination dan search
                    $count_sql = "SELECT COUNT(id_jabatan) as total FROM Jabatan WHERE nama_jabatan LIKE ?";
                    $stmt_count = $conn->prepare($count_sql);
                    $search_param = "%" . $search . "%";
                    $stmt_count->bind_param("s", $search_param);
                    $stmt_count->execute();
                    $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
                    $total_pages = ceil($total_records / $records_per_page);
                    $stmt_count->close();

                    $sql = "SELECT * FROM Jabatan WHERE nama_jabatan LIKE ? ORDER BY nama_jabatan ASC LIMIT ? OFFSET ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sii", $search_param, $records_per_page, $offset);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()):
                    ?>
                            <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-mono text-xs"><?= e($row['id_jabatan']) ?></td>
                                <td class="px-6 py-4 font-medium text-gray-900"><?= e($row['nama_jabatan']) ?></td>
                                <td class="px-6 py-4">Rp <?= number_format($row['gaji_awal'], 2, ',', '.') ?></td>
                                <td class="px-6 py-4">Rp <?= number_format($row['kenaikan_pertahun'], 2, ',', '.') ?></td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-4">
                                        <a href="jabatan.php?action=edit&id=<?= e($row['id_jabatan']) ?>" class="text-blue-600 hover:text-blue-800" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                        <a href="jabatan.php?action=delete&id=<?= e($row['id_jabatan']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Yakin ingin menghapus data ini?')" title="Hapus"><i class="fa-solid fa-trash-alt"></i></a>
                                    </div>
                                </td>
                            </tr>
                    <?php endwhile;
                    } else {
                        echo '<tr><td colspan="5" class="text-center py-5 text-gray-500">Tidak ada data ditemukan.</td></tr>';
                    }
                    $stmt->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>

        <?php
        // Menampilkan pagination
        echo generate_pagination_links($page, $total_pages, 'jabatan.php', ['action' => 'list', 'search' => $search]);
        ?>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="bg-white p-8 rounded-xl shadow-lg max-w-lg mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-2 font-poppins"><?= $action === 'add' ? 'Tambah' : 'Edit' ?> Jabatan</h2>
        <p class="text-center text-gray-500 mb-8">Isi detail jabatan pada form di bawah ini.</p>
        <form method="POST" action="jabatan.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id_jabatan" value="<?= e($jabatan_data['id_jabatan'] ?? '') ?>">

            <div class="mb-5">
                <label for="nama_jabatan" class="block mb-2 text-sm font-medium text-gray-700">Nama Jabatan</label>
                <input type="text" id="nama_jabatan" name="nama_jabatan" value="<?= e($jabatan_data['nama_jabatan'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            <div class="mb-5">
                <label for="gaji_awal" class="block mb-2 text-sm font-medium text-gray-700">Gaji Pokok</label>
                <input type="number" id="gaji_awal" name="gaji_awal" value="<?= e($jabatan_data['gaji_awal'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required min="0" step="0.01">
            </div>
            <div class="mb-8">
                <label for="kenaikan_pertahun" class="block mb-2 text-sm font-medium text-gray-700">Kenaikan Gaji Tahunan</label>
                <input type="number" id="kenaikan_pertahun" name="kenaikan_pertahun" value="<?= e($jabatan_data['kenaikan_pertahun'] ?? 0) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" min="0" step="0.01">
            </div>
            <div class="flex items-center justify-end space-x-4">
                <a href="jabatan.php?action=list" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>