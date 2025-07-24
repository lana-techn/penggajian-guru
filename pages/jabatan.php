<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole(['admin', 'kepala_sekolah']);

$conn = db_connect();
$page_title = 'Manajemen Jabatan';

// Check if user is Kepala Sekolah (read-only mode)
$isReadOnlyMode = ($_SESSION['role'] === 'kepala_sekolah');

// --- LOGIKA PROSES (CREATE, UPDATE, DELETE) ---

// Proses Tambah & Update (only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isReadOnlyMode) {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_jabatan = $_POST['id_jabatan'] ?? null;
    $nama_jabatan = trim($_POST['nama_jabatan']);
    $gaji_awal = filter_input(INPUT_POST, 'gaji_awal', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;
    $kenaikan_pertahun = filter_input(INPUT_POST, 'kenaikan_pertahun', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;

    if (empty($nama_jabatan) || $gaji_awal <= 0) {
        set_flash_message('error', 'Nama Jabatan dan Gaji Awal wajib diisi.');
    } else {
        if ($id_jabatan) { // Update
            $stmt = $conn->prepare("UPDATE Jabatan SET nama_jabatan=?, gaji_awal=?, kenaikan_pertahun=? WHERE id_jabatan=?");
            $stmt->bind_param('sdds', $nama_jabatan, $gaji_awal, $kenaikan_pertahun, $id_jabatan);
            $action_text = 'diperbarui';
        } else { // Tambah
            $id_jabatan = 'J' . date('ymdHis');
            $stmt = $conn->prepare("INSERT INTO Jabatan (id_jabatan, nama_jabatan, gaji_awal, kenaikan_pertahun) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssdd", $id_jabatan, $nama_jabatan, $gaji_awal, $kenaikan_pertahun);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) {
            set_flash_message('success', "Data jabatan berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data jabatan: " . $stmt->error);
        }
        $stmt->close();
    }
    header('Location: jabatan.php');
    exit;
}

// Proses Hapus (only for admin)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && !$isReadOnlyMode) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $id_jabatan = $_GET['id'];
        // Cek keterkaitan dengan guru
        $check_stmt = $conn->prepare("SELECT id_guru FROM Guru WHERE id_jabatan = ?");
        $check_stmt->bind_param('s', $id_jabatan);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            set_flash_message('error', 'Tidak dapat menghapus jabatan yang masih digunakan oleh data guru.');
        } else {
            $stmt = $conn->prepare("DELETE FROM Jabatan WHERE id_jabatan = ?");
            $stmt->bind_param("s", $id_jabatan);
            if ($stmt->execute()) {
                set_flash_message('success', 'Data jabatan berhasil dihapus.');
            } else {
                set_flash_message('error', 'Gagal menghapus jabatan.');
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: jabatan.php');
    exit;
}

// --- LOGIKA PENGAMBILAN DATA ---
$jabatan_result = $conn->query("SELECT * FROM Jabatan ORDER BY nama_jabatan ASC");

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="crudPage()">
    <!-- Tombol Tambah dan Judul Halaman -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 font-poppins"><?= e($page_title) ?><?php if ($isReadOnlyMode): ?> <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full ml-2">Mode Tampilan</span><?php endif; ?></h1>
            <p class="text-gray-500 mt-1"><?= $isReadOnlyMode ? 'Lihat data jabatan, gaji pokok, dan kenaikan gaji tahunan.' : 'Kelola jabatan, gaji pokok, dan kenaikan gaji tahunan.' ?></p>
        </div>
        <?php if (!$isReadOnlyMode): ?>
        <button @click="showForm = true; isEdit = false; resetForm()" class="bg-green-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center transition">
            <i class="fa-solid fa-plus mr-2"></i> Tambah Jabatan
        </button>
        <?php endif; ?>
    </div>

    <?php display_flash_message(); ?>

    <!-- Form Tambah/Edit (Hidden by default, only for admin) -->
    <?php if (!$isReadOnlyMode): ?>
    <div x-show="showForm" x-transition class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg mb-8 border-t-4 border-green-500">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 font-poppins" x-text="isEdit ? 'Edit Jabatan' : 'Tambah Jabatan Baru'"></h2>
        <form method="POST" action="jabatan.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id_jabatan" x-model="formData.id_jabatan">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-1">
                    <label for="nama_jabatan" class="block text-sm font-medium text-gray-700">Nama Jabatan</label>
                    <input type="text" name="nama_jabatan" x-model="formData.nama_jabatan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" required>
                </div>
                <div>
                    <label for="gaji_awal" class="block text-sm font-medium text-gray-700">Gaji Awal (Rp)</label>
                    <input type="number" name="gaji_awal" x-model="formData.gaji_awal" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" required>
                </div>
                <div>
                    <label for="kenaikan_pertahun" class="block text-sm font-medium text-gray-700">Kenaikan per Tahun (Rp)</label>
                    <input type="number" name="kenaikan_pertahun" x-model="formData.kenaikan_pertahun" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
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

    <!-- Daftar Jabatan -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg">
        <h3 class="text-xl font-bold text-gray-800 mb-4 font-poppins">Daftar Jabatan</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-gray-600">
                <thead class="bg-gray-100 text-gray-700 uppercase font-poppins text-xs">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Nama Jabatan</th>
                        <th class="px-4 py-3 text-right">Gaji Awal</th>
                        <th class="px-4 py-3 text-right">Kenaikan per Tahun</th>
                        <?php if (!$isReadOnlyMode): ?><th class="px-4 py-3 text-center">Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($jabatan_result->num_rows > 0): ?>
                        <?php $no = 1; while ($row = $jabatan_result->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= $no++ ?></td>
                                <td class="px-4 py-3 font-semibold text-gray-900"><?= e($row['nama_jabatan']) ?></td>
                                <td class="px-4 py-3 text-right">Rp <?= number_format($row['gaji_awal'], 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-right">Rp <?= number_format($row['kenaikan_pertahun'], 0, ',', '.') ?></td>
                                <?php if (!$isReadOnlyMode): ?>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-3">
                                        <button @click="editJabatan(<?= htmlspecialchars(json_encode($row)) ?>)" class="text-blue-600 hover:text-blue-800" title="Edit">
                                            <i class="fa-solid fa-pencil fa-fw"></i>
                                        </button>
                                        <a href="?action=delete&id=<?= e($row['id_jabatan']) ?>&token=<?= $_SESSION['csrf_token'] ?>" onclick="return confirm('Yakin ingin menghapus jabatan ini?')" class="text-red-600 hover:text-red-800" title="Hapus">
                                            <i class="fa-solid fa-trash fa-fw"></i>
                                        </a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $isReadOnlyMode ? '4' : '5' ?>" class="text-center py-10 text-gray-500">
                                <i class="fa-solid fa-briefcase fa-3x mb-3"></i>
                                <p>Tidak ada data jabatan yang ditemukan.</p>
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
                id_jabatan: null,
                nama_jabatan: '',
                gaji_awal: 0,
                kenaikan_pertahun: 0
            };
        },

        editJabatan(jabatanData) {
            this.isEdit = true;
            this.formData = {
                id_jabatan: jabatanData.id_jabatan,
                nama_jabatan: jabatanData.nama_jabatan,
                gaji_awal: parseFloat(jabatanData.gaji_awal),
                kenaikan_pertahun: parseFloat(jabatanData.kenaikan_pertahun)
            };
            this.showForm = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
