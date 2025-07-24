<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');

$conn = db_connect();
$page_title = 'Manajemen Potongan';

// --- LOGIKA PROSES (CREATE, UPDATE, DELETE) ---

// Proses Tambah & Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_potongan = $_POST['id_potongan'] ?? null;
    $id_jabatan = $_POST['id_jabatan'];
    // Ambil sebagai float, karena ini persentase
    $potongan_bpjs = filter_input(INPUT_POST, 'potongan_bpjs', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;
    $infak = filter_input(INPUT_POST, 'infak', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;

    if (empty($id_jabatan)) {
        set_flash_message('error', 'Jabatan wajib dipilih.');
    } else {
        if ($id_potongan) { // Update
            $stmt = $conn->prepare("UPDATE Potongan SET id_jabatan=?, potongan_bpjs=?, infak=? WHERE id_potongan=?");
            $stmt->bind_param('sdds', $id_jabatan, $potongan_bpjs, $infak, $id_potongan);
            $action_text = 'diperbarui';
        } else { // Tambah
            $id_potongan = 'P' . date('ymdHis');
            $stmt = $conn->prepare("INSERT INTO Potongan (id_potongan, id_jabatan, potongan_bpjs, infak) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssdd", $id_potongan, $id_jabatan, $potongan_bpjs, $infak);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) {
            set_flash_message('success', "Data potongan berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data potongan: " . $stmt->error);
        }
        $stmt->close();
    }
    header('Location: potongan.php');
    exit;
}

// Proses Hapus
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $id_potongan = $_GET['id'];
        $stmt = $conn->prepare("DELETE FROM Potongan WHERE id_potongan = ?");
        $stmt->bind_param("s", $id_potongan);
        if ($stmt->execute()) {
            set_flash_message('success', 'Data potongan berhasil dihapus.');
        } else {
            set_flash_message('error', 'Gagal menghapus data potongan.');
        }
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: potongan.php');
    exit;
}

// --- LOGIKA PENGAMBILAN DATA ---
$jabatan_list = $conn->query("SELECT id_jabatan, nama_jabatan FROM Jabatan ORDER BY nama_jabatan ASC")->fetch_all(MYSQLI_ASSOC);
$potongan_result = $conn->query("SELECT p.*, j.nama_jabatan FROM Potongan p JOIN Jabatan j ON p.id_jabatan = j.id_jabatan ORDER BY j.nama_jabatan ASC");

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="crudPage()">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 font-poppins"><?= e($page_title) ?></h1>
            <p class="text-gray-500 mt-1">Kelola besaran persentase potongan untuk setiap jabatan.</p>
        </div>
        <button @click="showForm = true; isEdit = false; resetForm()" class="bg-green-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center transition">
            <i class="fa-solid fa-plus mr-2"></i> Tambah Potongan
        </button>
    </div>

    <?php display_flash_message(); ?>

    <!-- Form Tambah/Edit -->
    <div x-show="showForm" x-transition class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg mb-8 border-t-4 border-green-500">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 font-poppins" x-text="isEdit ? 'Edit Potongan' : 'Tambah Potongan Baru'"></h2>
        <form method="POST" action="potongan.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id_potongan" x-model="formData.id_potongan">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="id_jabatan" class="block text-sm font-medium text-gray-700">Jabatan</label>
                    <select name="id_jabatan" x-model="formData.id_jabatan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" required>
                        <option value="">- Pilih Jabatan -</option>
                        <?php foreach ($jabatan_list as $j): ?>
                            <option value="<?= e($j['id_jabatan']) ?>"><?= e($j['nama_jabatan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="potongan_bpjs" class="block text-sm font-medium text-gray-700">Potongan BPJS (%)</label>
                    <input type="number" name="potongan_bpjs" x-model="formData.potongan_bpjs" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>
                <div>
                    <label for="infak" class="block text-sm font-medium text-gray-700">Infak (%)</label>
                    <input type="number" name="infak" x-model="formData.infak" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
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

    <!-- Daftar Potongan -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg">
        <h3 class="text-xl font-bold text-gray-800 mb-4 font-poppins">Daftar Potongan per Jabatan</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-gray-600">
                <thead class="bg-gray-100 text-gray-700 uppercase font-poppins text-xs">
                    <tr>
                        <th class="px-4 py-3">Jabatan</th>
                        <th class="px-4 py-3 text-right">Potongan BPJS</th>
                        <th class="px-4 py-3 text-right">Infak</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($potongan_result->num_rows > 0): ?>
                        <?php while ($row = $potongan_result->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-gray-900"><?= e($row['nama_jabatan']) ?></td>
                                <td class="px-4 py-3 text-right"><?= number_format($row['potongan_bpjs'], 2, ',', '.') ?>%</td>
                                <td class="px-4 py-3 text-right"><?= number_format($row['infak'], 2, ',', '.') ?>%</td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-3">
                                        <button @click="editPotongan(<?= htmlspecialchars(json_encode($row)) ?>)" class="text-blue-600 hover:text-blue-800" title="Edit">
                                            <i class="fa-solid fa-pencil fa-fw"></i>
                                        </button>
                                        <a href="?action=delete&id=<?= e($row['id_potongan']) ?>&token=<?= $_SESSION['csrf_token'] ?>" onclick="return confirm('Yakin ingin menghapus data ini?')" class="text-red-600 hover:text-red-800" title="Hapus">
                                            <i class="fa-solid fa-trash fa-fw"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-10 text-gray-500">
                                <i class="fa-solid fa-scissors fa-3x mb-3"></i>
                                <p>Tidak ada data potongan yang ditemukan.</p>
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
                id_potongan: null,
                id_jabatan: '',
                potongan_bpjs: 0,
                infak: 0
            };
        },

        editPotongan(data) {
            this.isEdit = true;
            this.formData = {
                id_potongan: data.id_potongan,
                id_jabatan: data.id_jabatan,
                potongan_bpjs: parseFloat(data.potongan_bpjs),
                infak: parseFloat(data.infak)
            };
            this.showForm = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
