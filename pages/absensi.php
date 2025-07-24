<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole(['admin', 'kepala_sekolah']);

$conn = db_connect();
$page_title = 'Manajemen Absensi';

// Check if user is Kepala Sekolah (read-only mode)
$isReadOnlyMode = ($_SESSION['role'] === 'kepala_sekolah');

// --- LOGIKA PROSES (CREATE, UPDATE, DELETE) ---

// Proses Tambah & Update (only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isReadOnlyMode) {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_kehadiran = $_POST['id_kehadiran'] ?? null;
    $id_guru = $_POST['id_guru'];
    $bulan = $_POST['bulan'];
    $tahun = $_POST['tahun'];
    $jml_terlambat = (int)($_POST['jml_terlambat'] ?? 0);
    $jml_alfa = (int)($_POST['jml_alfa'] ?? 0);
    $jml_izin = (int)($_POST['jml_izin'] ?? 0);

    if (empty($id_guru) || empty($bulan) || empty($tahun)) {
        set_flash_message('error', 'Guru, Bulan, dan Tahun wajib diisi.');
    } else {
        if ($id_kehadiran) { // Update
            $stmt = $conn->prepare("UPDATE Rekap_Kehadiran SET id_guru=?, bulan=?, tahun=?, jml_terlambat=?, jml_alfa=?, jml_izin=? WHERE id_kehadiran=?");
            $stmt->bind_param('sssiiis', $id_guru, $bulan, $tahun, $jml_terlambat, $jml_alfa, $jml_izin, $id_kehadiran);
            $action_text = 'diperbarui';
        } else { // Tambah
            // Cek duplikat
            $cek_stmt = $conn->prepare("SELECT id_kehadiran FROM Rekap_Kehadiran WHERE id_guru = ? AND bulan = ? AND tahun = ?");
            $cek_stmt->bind_param('ssi', $id_guru, $bulan, $tahun);
            $cek_stmt->execute();
            if ($cek_stmt->get_result()->num_rows > 0) {
                set_flash_message('error', "Rekap absensi untuk guru ini pada periode tersebut sudah ada.");
                header('Location: absensi.php');
                exit;
            }
            $cek_stmt->close();

            $id_kehadiran = 'KH' . date('ymdHis');
            $stmt = $conn->prepare("INSERT INTO Rekap_Kehadiran (id_kehadiran, id_guru, bulan, tahun, jml_terlambat, jml_alfa, jml_izin) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiiii", $id_kehadiran, $id_guru, $bulan, $tahun, $jml_terlambat, $jml_alfa, $jml_izin);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) {
            set_flash_message('success', "Data absensi berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data absensi: " . $stmt->error);
        }
        $stmt->close();
    }
    header('Location: absensi.php');
    exit;
}

// Proses Hapus (only for admin)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && !$isReadOnlyMode) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $id_kehadiran = $_GET['id'];
        $stmt = $conn->prepare("DELETE FROM Rekap_Kehadiran WHERE id_kehadiran = ?");
        $stmt->bind_param("s", $id_kehadiran);
        if ($stmt->execute()) {
            set_flash_message('success', 'Data absensi berhasil dihapus.');
        } else {
            set_flash_message('error', 'Gagal menghapus data absensi.');
        }
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: absensi.php');
    exit;
}

// --- LOGIKA PENGAMBILAN DATA ---
$guru_list = $conn->query("SELECT id_guru, nama_guru FROM Guru ORDER BY nama_guru ASC")->fetch_all(MYSQLI_ASSOC);
$bulan_opsi = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', 
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', 
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$tahun_sekarang = (int)date('Y');

// Filter
$filter_guru = $_GET['guru'] ?? '';
$filter_tahun = $_GET['tahun'] ?? '';

$sql_where = " WHERE 1=1";
$params = [];
$types = '';
if ($filter_guru) { $sql_where .= " AND r.id_guru = ?"; $params[] = $filter_guru; $types .= 's'; }
if ($filter_tahun) { $sql_where .= " AND r.tahun = ?"; $params[] = $filter_tahun; $types .= 'i'; }

$absensi_result = $conn->execute_query("SELECT r.*, g.nama_guru FROM Rekap_Kehadiran r JOIN Guru g ON r.id_guru = g.id_guru" . $sql_where . " ORDER BY r.tahun DESC, r.bulan DESC, g.nama_guru ASC", $params);

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="crudPage()">
    <!-- Tombol Tambah dan Judul Halaman -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 font-poppins"><?= e($page_title) ?><?php if ($isReadOnlyMode): ?> <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full ml-2">Mode Tampilan</span><?php endif; ?></h1>
            <p class="text-gray-500 mt-1"><?= $isReadOnlyMode ? 'Lihat rekapitulasi absensi bulanan setiap guru.' : 'Kelola rekapitulasi absensi bulanan setiap guru.' ?></p>
        </div>
        <?php if (!$isReadOnlyMode): ?>
        <button @click="showForm = true; isEdit = false; resetForm()" class="bg-green-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center transition">
            <i class="fa-solid fa-plus mr-2"></i> Tambah Rekap
        </button>
        <?php endif; ?>
    </div>

    <?php display_flash_message(); ?>

    <!-- Form Tambah/Edit (Hidden by default, only for admin) -->
    <?php if (!$isReadOnlyMode): ?>
    <div x-show="showForm" x-transition class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg mb-8 border-t-4 border-green-500">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 font-poppins" x-text="isEdit ? 'Edit Rekap Absensi' : 'Tambah Rekap Baru'"></h2>
        <form method="POST" action="absensi.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id_kehadiran" x-model="formData.id_kehadiran">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label for="id_guru" class="block text-sm font-medium text-gray-700">Guru</label>
                    <select name="id_guru" x-model="formData.id_guru" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" required :disabled="isEdit">
                        <option value="">- Pilih Guru -</option>
                        <?php foreach ($guru_list as $g): ?>
                            <option value="<?= e($g['id_guru']) ?>"><?= e($g['nama_guru']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="bulan" class="block text-sm font-medium text-gray-700">Bulan</label>
                    <select name="bulan" x-model="formData.bulan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" required :disabled="isEdit">
                        <option value="">- Pilih Bulan -</option>
                        <?php foreach ($bulan_opsi as $num => $name): ?>
                            <option value="<?= e($num) ?>"><?= e($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="tahun" class="block text-sm font-medium text-gray-700">Tahun</label>
                    <input type="number" name="tahun" x-model="formData.tahun" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" required :disabled="isEdit">
                </div>
                <div>
                    <label for="jml_terlambat" class="block text-sm font-medium text-gray-700">Jumlah Terlambat</label>
                    <input type="number" name="jml_terlambat" x-model="formData.jml_terlambat" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>
                <div>
                    <label for="jml_alfa" class="block text-sm font-medium text-gray-700">Jumlah Alfa</label>
                    <input type="number" name="jml_alfa" x-model="formData.jml_alfa" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>
                <div>
                    <label for="jml_izin" class="block text-sm font-medium text-gray-700">Jumlah Izin</label>
                    <input type="number" name="jml_izin" x-model="formData.jml_izin" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
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

    <!-- Daftar Absensi -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800 font-poppins">Daftar Rekap Absensi</h3>
            <form method="GET" action="" class="flex items-center space-x-3">
                <select name="guru" class="w-full max-w-xs border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500">
                    <option value="">-- Semua Guru --</option>
                    <?php foreach ($guru_list as $g): ?>
                        <option value="<?= e($g['id_guru']) ?>" <?= ($filter_guru == $g['id_guru']) ? 'selected' : '' ?>><?= e($g['nama_guru']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="tahun" value="<?= e($filter_tahun) ?>" placeholder="Tahun..." class="w-full max-w-xs border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg shadow hover:bg-green-700 font-semibold">Filter</button>
                <a href="?" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 font-semibold">Reset</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-gray-600">
                <thead class="bg-gray-100 text-gray-700 uppercase font-poppins text-xs">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Nama Guru</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3 text-center">Terlambat</th>
                        <th class="px-4 py-3 text-center">Alfa</th>
                        <th class="px-4 py-3 text-center">Izin</th>
                        <?php if (!$isReadOnlyMode): ?><th class="px-4 py-3 text-center">Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($absensi_result->num_rows > 0): ?>
                        <?php $no = 1; while ($row = $absensi_result->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= $no++ ?></td>
                                <td class="px-4 py-3 font-semibold text-gray-900"><?= e($row['nama_guru']) ?></td>
                                <td class="px-4 py-3"><?= e($bulan_opsi[$row['bulan']]) ?> <?= e($row['tahun']) ?></td>
                                <td class="px-4 py-3 text-center"><?= e($row['jml_terlambat']) ?></td>
                                <td class="px-4 py-3 text-center"><?= e($row['jml_alfa']) ?></td>
                                <td class="px-4 py-3 text-center"><?= e($row['jml_izin']) ?></td>
                                <?php if (!$isReadOnlyMode): ?>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-3">
                                        <button @click="editAbsensi(<?= htmlspecialchars(json_encode($row)) ?>)" class="text-blue-600 hover:text-blue-800" title="Edit">
                                            <i class="fa-solid fa-pencil fa-fw"></i>
                                        </button>
                                        <a href="?action=delete&id=<?= e($row['id_kehadiran']) ?>&token=<?= $_SESSION['csrf_token'] ?>" onclick="return confirm('Yakin ingin menghapus rekap absensi ini?')" class="text-red-600 hover:text-red-800" title="Hapus">
                                            <i class="fa-solid fa-trash fa-fw"></i>
                                        </a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $isReadOnlyMode ? '6' : '7' ?>" class="text-center py-10 text-gray-500">
                                <i class="fa-solid fa-calendar-times fa-3x mb-3"></i>
                                <p>Tidak ada data absensi yang ditemukan.</p>
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
                id_kehadiran: null,
                id_guru: '',
                bulan: '',
                tahun: <?= $tahun_sekarang ?>,
                jml_terlambat: 0,
                jml_alfa: 0,
                jml_izin: 0
            };
        },

        editAbsensi(absensiData) {
            this.isEdit = true;
            this.formData = {
                id_kehadiran: absensiData.id_kehadiran,
                id_guru: absensiData.id_guru,
                bulan: absensiData.bulan,
                tahun: absensiData.tahun,
                jml_terlambat: absensiData.jml_terlambat,
                jml_alfa: absensiData.jml_alfa,
                jml_izin: absensiData.jml_izin
            };
            this.showForm = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
