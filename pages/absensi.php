<?php
// 1. SETUP & LOGIKA
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Absensi';

// Logika Pagination & Filter
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$guru_filter = $_GET['guru_id'] ?? '';
$tanggal_filter = $_GET['tanggal'] ?? '';
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Ambil data untuk dropdown dan filter
$guru_list = $conn->query("SELECT id_guru, nama_guru FROM Guru ORDER BY nama_guru ASC")->fetch_all(MYSQLI_ASSOC);
$status_list = ['hadir', 'izin', 'sakit', 'alpha'];

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM Rekap_Kehadiran WHERE id_kehadiran = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) set_flash_message('success', 'Data absensi berhasil dihapus.');
        else set_flash_message('error', 'Gagal menghapus data absensi.');
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: absensi.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_kehadiran = $_POST['id_kehadiran'] ?? null;
    $id_guru = $_POST['id_guru'];
    $bulan = $_POST['bulan'];
    $tahun = $_POST['tahun'];
    $jml_terlambat = $_POST['jml_terlambat'];
    $jml_alfa = $_POST['jml_alfa'];
    $jml_izin = $_POST['jml_izin'];

    if (empty($id_guru) || empty($bulan) || empty($tahun)) {
        set_flash_message('error', 'Semua kolom wajib diisi.');
    } else {
        if ($id_kehadiran) { // Edit
            $stmt = $conn->prepare("UPDATE Rekap_Kehadiran SET id_guru=?, bulan=?, tahun=?, jml_terlambat=?, jml_alfa=?, jml_izin=? WHERE id_kehadiran=?");
            $stmt->bind_param("siiisss", $id_guru, $bulan, $tahun, $jml_terlambat, $jml_alfa, $jml_izin, $id_kehadiran);
            $action_text = 'diperbarui';
        } else { // Tambah
            $id_kehadiran = 'KH' . date('ymdHis');
            $stmt_cek = $conn->prepare("SELECT id_kehadiran FROM Rekap_Kehadiran WHERE id_guru = ? AND bulan = ? AND tahun = ?");
            $stmt_cek->bind_param("sii", $id_guru, $bulan, $tahun);
            $stmt_cek->execute();
            if ($stmt_cek->get_result()->num_rows > 0) {
                set_flash_message('error', "Absensi untuk guru ini pada periode yang sama sudah ada.");
                header('Location: absensi.php?action=add');
                exit;
            }
            $stmt_cek->close();

            $stmt = $conn->prepare("INSERT INTO Rekap_Kehadiran (id_kehadiran, id_guru, bulan, tahun, jml_terlambat, jml_alfa, jml_izin) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiiii", $id_kehadiran, $id_guru, $bulan, $tahun, $jml_terlambat, $jml_alfa, $jml_izin);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) set_flash_message('success', "Data absensi berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses data absensi: " . $stmt->error);

        $stmt->close();
        header('Location: absensi.php?action=list');
        exit;
    }
}

$absensi_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Data Absensi';
    $stmt = $conn->prepare("SELECT * FROM Rekap_Kehadiran WHERE id_kehadiran = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $absensi_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$absensi_data) {
        set_flash_message('error', 'Data absensi tidak ditemukan.');
        header('Location: absensi.php?action=list');
        exit;
    }
} elseif ($action === 'add') {
    $page_title = 'Tambah Absensi';
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
                <h2 class="text-2xl font-bold text-gray-800 font-poppins">Daftar Absensi</h2>
                <p class="text-gray-500 text-sm">Kelola data kehadiran guru per hari.</p>
            </div>
            <a href="absensi.php?action=add" class="w-full sm:w-auto bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center justify-center">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Absensi
            </a>
        </div>

        <form method="get" action="absensi.php" class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="action" value="list">
            <div>
                <label for="guru_filter" class="sr-only">Nama Guru</label>
                <select id="guru_filter" name="guru_id" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">-- Semua Guru --</option>
                    <?php foreach ($guru_list as $g): ?>
                        <option value="<?= e($g['id_guru']) ?>" <?= $guru_filter == $g['id_guru'] ? 'selected' : '' ?>><?= e($g['nama_guru']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter_tanggal" class="sr-only">Tanggal</label>
                <input type="date" name="tanggal" id="filter_tanggal" placeholder="Filter Tanggal..." value="<?= e($tanggal_filter) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-semibold">Terapkan</button>
                <a href="absensi.php?action=list" class="w-full text-center bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 font-semibold">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-center text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left">Nama Guru</th>
                        <th class="px-4 py-3">Tahun</th>
                        <th class="px-4 py-3">Bulan</th>
                        <th class="px-4 py-3">Jml Terlambat</th>
                        <th class="px-4 py-3">Jml Alpha</th>
                        <th class="px-4 py-3">Jml Izin</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Build query dinamis
                    $count_params = [];
                    $types_string_count = '';
                    $count_sql = "SELECT COUNT(r.id_kehadiran) as total FROM Rekap_Kehadiran r WHERE 1=1";
                    if ($guru_filter) {
                        $count_sql .= " AND r.id_guru = ?";
                        array_push($count_params, $guru_filter);
                        $types_string_count .= 's';
                    }
                    if ($tanggal_filter) {
                        $count_sql .= " AND CONCAT(r.tahun, LPAD(r.bulan,2,'0')) = ?";
                        array_push($count_params, $tanggal_filter);
                        $types_string_count .= 's';
                    }
                    $stmt_count = $conn->prepare($count_sql);
                    if ($types_string_count) $stmt_count->bind_param($types_string_count, ...$count_params);
                    $stmt_count->execute();
                    $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
                    $total_pages = ceil($total_records / $records_per_page);
                    $stmt_count->close();

                    $data_params = $count_params;
                    $types_string_data = $types_string_count;
                    $sql = "SELECT r.*, g.nama_guru FROM Rekap_Kehadiran r JOIN Guru g ON r.id_guru = g.id_guru WHERE 1=1";
                    if ($guru_filter) $sql .= " AND r.id_guru = ?";
                    if ($tanggal_filter) $sql .= " AND CONCAT(r.tahun, LPAD(r.bulan,2,'0')) = ?";
                    $sql .= " ORDER BY r.tahun DESC, r.bulan DESC, g.nama_guru ASC LIMIT ? OFFSET ?";
                    array_push($data_params, $records_per_page, $offset);
                    $types_string_data .= 'ii';

                    $stmt = $conn->prepare($sql);
                    if ($types_string_data) $stmt->bind_param($types_string_data, ...$data_params);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                    ?>
                            <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-left text-gray-900"><?= e($row['nama_guru']) ?></td>
                                <td class="px-4 py-3"><?= e($row['tahun']) ?></td>
                                <td class="px-4 py-3"><?= e($row['bulan']) ?></td>
                                <td class="px-4 py-3"><?= e($row['jml_terlambat']) ?></td>
                                <td class="px-4 py-3"><?= e($row['jml_alfa']) ?></td>
                                <td class="px-4 py-3"><?= e($row['jml_izin']) ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-4">
                                        <a href="absensi.php?action=edit&id=<?= e($row['id_kehadiran']) ?>" class="text-blue-600 hover:text-blue-800" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                        <a href="absensi.php?action=delete&id=<?= e($row['id_kehadiran']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Yakin?')" title="Hapus"><i class="fa-solid fa-trash-alt"></i></a>
                                    </div>
                                </td>
                            </tr>
                    <?php endwhile;
                    else:
                        echo '<tr><td colspan="7" class="text-center py-5 text-gray-500">Tidak ada data ditemukan.</td></tr>';
                    endif;
                    $stmt->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>

        <?php
        echo generate_pagination_links($page, $total_pages, 'absensi.php', ['action' => 'list', 'guru_id' => $guru_filter, 'tanggal' => $tanggal_filter]);
        ?>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="bg-white p-8 rounded-xl shadow-lg max-w-2xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-2 font-poppins"><?= ucfirst($action) ?> Data Absensi</h2>
        <p class="text-center text-gray-500 mb-8">Masukkan data kehadiran untuk guru.</p>
        <form method="POST" action="absensi.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id" value="<?= e($absensi_data['id'] ?? '') ?>">
            <div class="grid md:grid-cols-2 gap-x-6 gap-y-5">
                <div class="md:col-span-2">
                    <label for="guru_id" class="block mb-2 text-sm font-medium text-gray-700">Nama Guru</label>
                    <select name="guru_id" id="guru_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required <?= $action === 'edit' ? 'disabled' : '' ?>>
                        <option value="">- Pilih Guru -</option>
                        <?php foreach ($guru_list as $g): ?>
                            <option value="<?= e($g['id_guru']) ?>" <?= (isset($absensi_data) && $absensi_data['guru_id'] == $g['id_guru']) ? 'selected' : '' ?>><?= e($g['nama_guru']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="guru_id" value="<?= e($absensi_data['guru_id']) ?>">
                    <?php endif; ?>
                </div>
                <div>
                    <label for="tanggal" class="block mb-2 text-sm font-medium text-gray-700">Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal" value="<?= e($absensi_data['tanggal'] ?? date('Y-m-d')) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required <?= $action === 'edit' ? 'readonly' : '' ?>>
                </div>
                <div>
                    <label for="status" class="block mb-2 text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <?php foreach ($status_list as $s): ?>
                            <option value="<?= e($s) ?>" <?= (isset($absensi_data) && $absensi_data['status'] == $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="keterangan" class="block mb-2 text-sm font-medium text-gray-700">Keterangan</label>
                    <input type="text" id="keterangan" name="keterangan" value="<?= e($absensi_data['keterangan'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
            </div>
            <div class="flex items-center justify-end space-x-4 mt-8">
                <a href="absensi.php?action=list" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Batal</a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all">Simpan</button>
            </div>
        </form>
    </div>
<?php endif; ?>