<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole(['admin', 'kepala_sekolah']);

$conn = db_connect();
$page_title = 'Manajemen Tunjangan';

// Check if user is Kepala Sekolah (read-only mode)
$isReadOnlyMode = ($_SESSION['role'] === 'kepala_sekolah');

// --- LOGIKA PROSES (CREATE, UPDATE, DELETE) ---

// Proses Tambah & Update (only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isReadOnlyMode) {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_tunjangan = $_POST['id_tunjangan'] ?? null;
    $id_jabatan = $_POST['id_jabatan'];
    $tunjangan_suami_istri = filter_input(INPUT_POST, 'tunjangan_suami_istri', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;
    $tunjangan_anak = filter_input(INPUT_POST, 'tunjangan_anak', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;
    $tunjangan_beras = filter_input(INPUT_POST, 'tunjangan_beras', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;

    if (empty($id_jabatan)) {
        set_flash_message('error', 'Jabatan wajib dipilih.');
    } else {
        if ($id_tunjangan) { // Update
            $stmt = $conn->prepare("UPDATE Tunjangan SET id_jabatan=?, tunjangan_suami_istri=?, tunjangan_anak=?, tunjangan_beras=? WHERE id_tunjangan=?");
            $stmt->bind_param('sddds', $id_jabatan, $tunjangan_suami_istri, $tunjangan_anak, $tunjangan_beras, $id_tunjangan);
            $action_text = 'diperbarui';
        } else { // Tambah
            // Cek dulu apakah data untuk jabatan ini sudah ada
            $cek_stmt = $conn->prepare("SELECT id_tunjangan FROM Tunjangan WHERE id_jabatan = ?");
            $cek_stmt->bind_param('s', $id_jabatan);
            $cek_stmt->execute();
            if ($cek_stmt->get_result()->num_rows > 0) {
                 set_flash_message('error', 'Pengaturan tunjangan untuk jabatan ini sudah ada. Silakan edit data yang sudah ada.');
                 header('Location: tunjangan.php');
                 exit;
            }
            $cek_stmt->close();

            $id_tunjangan = 'T' . date('ymdHis');
            $stmt = $conn->prepare("INSERT INTO Tunjangan (id_tunjangan, id_jabatan, tunjangan_suami_istri, tunjangan_anak, tunjangan_beras) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddd", $id_tunjangan, $id_jabatan, $tunjangan_suami_istri, $tunjangan_anak, $tunjangan_beras);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) {
            set_flash_message('success', "Data tunjangan berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data tunjangan: " . $stmt->error);
        }
        $stmt->close();
    }
    header('Location: tunjangan.php');
    exit;
}

// Proses Hapus (only for admin)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && !$isReadOnlyMode) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $id_tunjangan = $_GET['id'];
        $stmt = $conn->prepare("DELETE FROM Tunjangan WHERE id_tunjangan = ?");
        $stmt->bind_param("s", $id_tunjangan);
        if ($stmt->execute()) {
            set_flash_message('success', 'Data tunjangan berhasil dihapus.');
        } else {
            set_flash_message('error', 'Gagal menghapus data tunjangan.');
        }
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: tunjangan.php');
    exit;
}

// --- LOGIKA PENGAMBILAN DATA ---
$jabatan_list = $conn->query("SELECT id_jabatan, nama_jabatan FROM Jabatan ORDER BY nama_jabatan ASC")->fetch_all(MYSQLI_ASSOC);
$tunjangan_result = $conn->query("SELECT t.*, j.nama_jabatan FROM Tunjangan t JOIN Jabatan j ON t.id_jabatan = j.id_jabatan ORDER BY j.nama_jabatan ASC");

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="crudPage()">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 font-poppins"><?= e($page_title) ?><?php if ($isReadOnlyMode): ?> <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full ml-2">Mode Tampilan</span><?php endif; ?></h1>
            <p class="text-gray-500 mt-1"><?= $isReadOnlyMode ? 'Lihat besaran nominal tunjangan untuk setiap jabatan.' : 'Kelola besaran nominal tunjangan untuk setiap jabatan.' ?></p>
        </div>
        <?php if (!$isReadOnlyMode): ?>
        <button @click="showForm = true; isEdit = false; resetForm()" class="bg-green-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center transition">
            <i class="fa-solid fa-plus mr-2"></i> Tambah Tunjangan
        </button>
        <?php endif; ?>
    </div>

    <?php display_flash_message(); ?>

    <?php if (!$isReadOnlyMode): ?>
    <div x-show="showForm" x-transition class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg mb-8 border-t-4 border-green-500">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 font-poppins" x-text="isEdit ? 'Edit Tunjangan' : 'Tambah Tunjangan Baru'"></h2>
        
        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-4 rounded-md mb-6 text-sm">
            <b>Info Penting:</b>
            <ul class="list-disc list-inside mt-1">
                <li><b>Tunjangan Beras:</b> Nominal tetap yang diterima semua guru.</li>
                <li><b>Tunjangan Suami/Istri:</b> Hanya berlaku jika status guru "Menikah".</li>
                <li><b>Tunjangan per Anak:</b> Akan dikalikan dengan jumlah anak guru (maksimal 2 anak) saat proses gaji.</li>
                <li><b>Tunjangan Kehadiran:</b> Dihitung otomatis saat proses gaji berdasarkan data absensi.</li>
            </ul>
        </div>

        <form method="POST" action="tunjangan.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id_tunjangan" x-model="formData.id_tunjangan">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="lg:col-span-2">
                    <label for="id_jabatan" class="block text-sm font-medium text-gray-700">Jabatan</label>
                    <select name="id_jabatan" x-model="formData.id_jabatan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" required :disabled="isEdit">
                        <option value="">- Pilih Jabatan -</option>
                        <?php foreach ($jabatan_list as $j): ?>
                            <option value="<?= e($j['id_jabatan']) ?>"><?= e($j['nama_jabatan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                     <p x-show="isEdit" class="text-xs text-gray-500 mt-1">Jabatan tidak dapat diubah saat mengedit.</p>
                </div>
                <div>
                    <label for="tunjangan_beras" class="block text-sm font-medium text-gray-700">Tunjangan Beras (Rp)</label>
                    <input type="number" name="tunjangan_beras" x-model="formData.tunjangan_beras" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="Contoh: 50000">
                </div>
                <div>
                    <label for="tunjangan_suami_istri" class="block text-sm font-medium text-gray-700">T. Suami/Istri (Rp)</label>
                    <input type="number" name="tunjangan_suami_istri" x-model="formData.tunjangan_suami_istri" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="Contoh: 100000">
                </div>
                <div>
                    <label for="tunjangan_anak" class="block text-sm font-medium text-gray-700">T. per Anak (Rp)</label>
                    <input type="number" name="tunjangan_anak" x-model="formData.tunjangan_anak" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="Contoh: 100000">
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" @click="showForm = false" class="bg-gray-200 text-gray-700 px-5 py-2.5 rounded-lg hover:bg-gray-300 font-semibold transition">Batal</button>
                <button type="submit" class="bg-green-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center transition">
                    <i class="fa fa-save mr-2"></i> <span x-text="isEdit ? 'Simpan Perubahan' : 'Simpan Data'"></span>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg">
        <h3 class="text-xl font-bold text-gray-800 mb-4 font-poppins">Daftar Tunjangan per Jabatan</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-gray-600">
                <thead class="bg-gray-100 text-gray-700 uppercase font-poppins text-xs">
                    <tr>
                        <th class="px-4 py-3">Jabatan</th>
                        <th class="px-4 py-3 text-right">T. Beras</th>
                        <th class="px-4 py-3 text-right">T. Suami/Istri</th>
                        <th class="px-4 py-3 text-right">T. per Anak</th>
                        <?php if (!$isReadOnlyMode): ?><th class="px-4 py-3 text-center">Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tunjangan_result->num_rows > 0): ?>
                        <?php while ($row = $tunjangan_result->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-gray-900"><?= e($row['nama_jabatan']) ?></td>
                                <td class="px-4 py-3 text-right">Rp <?= number_format($row['tunjangan_beras'], 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-right">Rp <?= number_format($row['tunjangan_suami_istri'], 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-right">Rp <?= number_format($row['tunjangan_anak'], 0, ',', '.') ?></td>
                                <?php if (!$isReadOnlyMode): ?>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-3">
                                        <span class="text-gray-400 text-sm">Read Only</span>
                                        <a href="?action=delete&id=<?= e($row['id_tunjangan']) ?>&token=<?= $_SESSION['csrf_token'] ?>" onclick="return confirm('Yakin ingin menghapus data ini?')" class="text-red-600 hover:text-red-800" title="Hapus">
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
                                <i class="fa-solid fa-hand-holding-dollar fa-3x mb-3"></i>
                                <p>Tidak ada data tunjangan yang ditemukan.</p>
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
                id_tunjangan: null,
                id_jabatan: '',
                tunjangan_suami_istri: 0,
                tunjangan_anak: 0,
                tunjangan_beras: 0
            };
        },

        editTunjangan(data) {
            this.isEdit = true;
            this.formData = {
                id_tunjangan: data.id_tunjangan,
                id_jabatan: data.id_jabatan,
                tunjangan_suami_istri: parseFloat(data.tunjangan_suami_istri),
                tunjangan_anak: parseFloat(data.tunjangan_anak),
                tunjangan_beras: parseFloat(data.tunjangan_beras)
            };
            this.showForm = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>