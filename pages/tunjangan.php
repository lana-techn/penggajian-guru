<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$page_title = 'Manajemen Tunjangan';

$jabatan_list = $conn->query("SELECT id_jabatan, nama_jabatan FROM Jabatan ORDER BY nama_jabatan")->fetch_all(MYSQLI_ASSOC);

// --- PROSES HAPUS ---
if ($action === 'delete' && $id) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM Tunjangan WHERE id_tunjangan = ?");
        $stmt->bind_param('s', $id);
        if ($stmt->execute()) set_flash_message('success', 'Data tunjangan berhasil dihapus.');
        else set_flash_message('error', 'Gagal menghapus data tunjangan.');
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: tunjangan.php?action=list');
    exit;
}

// --- PROSES TAMBAH & EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');
    $id_tunjangan = $_POST['id_tunjangan'] ?? null;
    $id_jabatan = $_POST['id_jabatan'] ?? '';
    $tunjangan_suami_istri = $_POST['tunjangan_suami_istri'] ?? 0;
    $tunjangan_anak = $_POST['tunjangan_anak'] ?? 0;
    $tunjangan_beras = $_POST['tunjangan_beras'] ?? 0;

    if (empty($id_jabatan)) {
        set_flash_message('error', 'Jabatan wajib dipilih.');
    } else {
        if ($id_tunjangan) { // Edit
            $stmt = $conn->prepare("UPDATE Tunjangan SET id_jabatan=?, tunjangan_suami_istri=?, tunjangan_anak=?, tunjangan_beras=? WHERE id_tunjangan=?");
            $stmt->bind_param('siiii', $id_jabatan, $tunjangan_suami_istri, $tunjangan_anak, $tunjangan_beras, $id_tunjangan);
            $action_text = 'diperbarui';
        } else { // Tambah
            $id_tunjangan = 'T' . date('ymdHis');
            $stmt = $conn->prepare("INSERT INTO Tunjangan (id_tunjangan, id_jabatan, tunjangan_suami_istri, tunjangan_anak, tunjangan_beras) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('siiii', $id_tunjangan, $id_jabatan, $tunjangan_suami_istri, $tunjangan_anak, $tunjangan_beras);
            $action_text = 'ditambahkan';
        }
        if ($stmt->execute()) set_flash_message('success', "Tunjangan berhasil {$action_text}.");
        else set_flash_message('error', "Gagal memproses data tunjangan: " . $stmt->error);
        $stmt->close();
        header('Location: tunjangan.php?action=list');
        exit;
    }
}

$tunjangan_data = null;
if ($action === 'edit' && $id) {
    $page_title = 'Edit Tunjangan';
    $stmt = $conn->prepare("SELECT * FROM Tunjangan WHERE id_tunjangan = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $tunjangan_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$tunjangan_data) {
        set_flash_message('error', 'Data tunjangan tidak ditemukan.');
        header('Location: tunjangan.php?action=list');
        exit;
    }
} elseif ($action === 'add') {
    $page_title = 'Tambah Tunjangan';
}

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>
<?php display_flash_message(); ?>
<?php if ($action === 'list'): ?>
<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 font-poppins">Daftar Tunjangan</h2>
            <p class="text-gray-500 text-sm">Kelola besaran tunjangan untuk setiap jabatan.</p>
        </div>
        <a href="tunjangan.php?action=add" class="w-full sm:w-auto bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 text-sm font-semibold shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center">
            <i class="fa-solid fa-plus mr-2"></i>Tambah Tunjangan
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700">
            <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                <tr>
                    <th class="px-6 py-3">No</th>
                    <th class="px-6 py-3">Jabatan</th>
                    <th class="px-6 py-3">Suami/Istri</th>
                    <th class="px-6 py-3">Anak</th>
                    <th class="px-6 py-3">Beras</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $res = $conn->query("SELECT t.*, j.nama_jabatan FROM Tunjangan t JOIN Jabatan j ON t.id_jabatan = j.id_jabatan ORDER BY j.nama_jabatan"); $no=1; while($row = $res->fetch_assoc()): ?>
                <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 font-mono text-xs"><?= $no++ ?></td>
                    <td class="px-6 py-4 font-medium text-gray-900"><?= e($row['nama_jabatan']) ?></td>
                    <td class="px-6 py-4">Rp <?= number_format($row['tunjangan_suami_istri']) ?></td>
                    <td class="px-6 py-4">Rp <?= number_format($row['tunjangan_anak']) ?></td>
                    <td class="px-6 py-4">Rp <?= number_format($row['tunjangan_beras']) ?></td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-4">
                            <a href="tunjangan.php?action=edit&id=<?= e($row['id_tunjangan']) ?>" class="text-blue-600 hover:text-blue-800" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                            <a href="tunjangan.php?action=delete&id=<?= e($row['id_tunjangan']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Yakin hapus?')" title="Hapus"><i class="fa-solid fa-trash-alt"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php 
    // Pagination jika diperlukan bisa ditambahkan di sini
    ?>
</div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<div class="bg-white p-8 rounded-xl shadow-lg max-w-lg mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 text-center mb-2 font-poppins"><?= $action === 'add' ? 'Tambah' : 'Edit' ?> Tunjangan</h2>
    <p class="text-center text-gray-500 mb-8">Isi detail tunjangan pada form di bawah ini.</p>
    <form method="POST" action="tunjangan.php">
        <?php csrf_input(); ?>
        <input type="hidden" name="id_tunjangan" value="<?= e($tunjangan_data['id_tunjangan'] ?? '') ?>">
        <div class="mb-5">
            <label for="id_jabatan" class="block mb-2 text-sm font-medium text-gray-700">Jabatan</label>
            <select id="id_jabatan" name="id_jabatan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                <option value="">- Pilih Jabatan -</option>
                <?php foreach ($jabatan_list as $j): ?>
                <option value="<?= e($j['id_jabatan']) ?>" <?= (isset($tunjangan_data) && $tunjangan_data['id_jabatan'] == $j['id_jabatan']) ? 'selected' : '' ?>><?= e($j['nama_jabatan']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-5">
            <label for="tunjangan_suami_istri" class="block mb-2 text-sm font-medium text-gray-700">Tunjangan Suami/Istri (Rp)</label>
            <input type="number" id="tunjangan_suami_istri" name="tunjangan_suami_istri" value="<?= e($tunjangan_data['tunjangan_suami_istri'] ?? 0) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" min="0" required>
        </div>
        <div class="mb-5">
            <label for="tunjangan_anak" class="block mb-2 text-sm font-medium text-gray-700">Tunjangan Anak (Rp)</label>
            <input type="number" id="tunjangan_anak" name="tunjangan_anak" value="<?= e($tunjangan_data['tunjangan_anak'] ?? 0) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" min="0" required>
        </div>
        <div class="mb-8">
            <label for="tunjangan_beras" class="block mb-2 text-sm font-medium text-gray-700">Tunjangan Beras (Rp)</label>
            <input type="number" id="tunjangan_beras" name="tunjangan_beras" value="<?= e($tunjangan_data['tunjangan_beras'] ?? 0) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" min="0" required>
        </div>
        <div class="flex items-center justify-end space-x-4">
            <a href="tunjangan.php?action=list" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Batal</a>
            <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all">Simpan</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 