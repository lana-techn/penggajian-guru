<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole(['admin', 'kepala_sekolah']);

$conn = db_connect();
$page_title = 'Manajemen Persentase Potongan';

// Check if user is Kepala Sekolah (read-only mode)
$isReadOnlyMode = ($_SESSION['role'] === 'kepala_sekolah');

// ID unik untuk setting global.
$GLOBAL_POTONGAN_ID = 'GLOBAL_01';

// --- PROSES UPDATE (only for admin) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isReadOnlyMode) {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $potongan_bpjs = filter_input(INPUT_POST, 'potongan_bpjs', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;
    $infak = filter_input(INPUT_POST, 'infak', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;

    // Cek dulu apakah datanya sudah ada
    $stmt_cek = $conn->prepare("SELECT id_potongan FROM Potongan WHERE id_potongan = ?");
    $stmt_cek->bind_param('s', $GLOBAL_POTONGAN_ID);
    $stmt_cek->execute();
    $result_cek = $stmt_cek->get_result();
    $stmt_cek->close();

    if ($result_cek->num_rows > 0) { // Data ada, lakukan UPDATE
        $stmt = $conn->prepare("UPDATE Potongan SET potongan_bpjs=?, infak=? WHERE id_potongan=?");
        $stmt->bind_param('dds', $potongan_bpjs, $infak, $GLOBAL_POTONGAN_ID);
        
        if ($stmt->execute()) {
            set_flash_message('success', "Pengaturan persentase potongan berhasil diperbarui.");
        } else {
            set_flash_message('error', "Gagal memperbarui pengaturan: " . $stmt->error);
        }
        $stmt->close();

    } else { // Data tidak ada, lakukan INSERT
        // FIX: Ambil satu ID Jabatan yang valid untuk memenuhi foreign key constraint
        $jabatan_query = $conn->query("SELECT id_jabatan FROM Jabatan LIMIT 1");
        if ($jabatan_query && $jabatan_query->num_rows > 0) {
            $valid_jabatan_id = $jabatan_query->fetch_assoc()['id_jabatan'];

            $stmt = $conn->prepare("INSERT INTO Potongan (id_potongan, id_jabatan, potongan_bpjs, infak) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssdd", $GLOBAL_POTONGAN_ID, $valid_jabatan_id, $potongan_bpjs, $infak);
            
            if ($stmt->execute()) {
                set_flash_message('success', "Pengaturan persentase potongan berhasil dibuat.");
            } else {
                set_flash_message('error', "Gagal membuat pengaturan: " . $stmt->error);
            }
            $stmt->close();
        } else {
            // Kasus jika tabel Jabatan kosong, berikan pesan error yang jelas
            set_flash_message('error', 'Tidak dapat membuat pengaturan potongan karena tidak ada data Jabatan. Harap tambahkan data jabatan terlebih dahulu.');
        }
    }
    
    header('Location: potongan.php');
    exit;
}

// --- PENGAMBILAN DATA ---
$stmt = $conn->prepare("SELECT * FROM Potongan WHERE id_potongan = ?");
$stmt->bind_param("s", $GLOBAL_POTONGAN_ID);
$stmt->execute();
$potongan_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$potongan_data) {
    $potongan_data = [
        'potongan_bpjs' => 2.0,
        'infak' => 2.0
    ];
}

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="settingsPage()">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 font-poppins"><?= e($page_title) ?><?php if ($isReadOnlyMode): ?> <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full ml-2">Mode Tampilan</span><?php endif; ?></h1>
            <p class="text-gray-500 mt-1"><?= $isReadOnlyMode ? 'Lihat persentase potongan gaji yang berlaku untuk semua guru.' : 'Atur persentase potongan gaji yang akan berlaku untuk semua guru.' ?></p>
        </div>
        <?php if (!$isReadOnlyMode): ?>
        <button @click="showForm = true" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-blue-700 font-semibold flex items-center transition">
            <i class="fa-solid fa-pencil mr-2"></i> Edit Pengaturan
        </button>
        <?php endif; ?>
    </div>

    <?php display_flash_message(); ?>

    <?php if (!$isReadOnlyMode): ?>
    <div x-show="showForm" x-transition class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg mb-8 border-t-4 border-green-500">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 font-poppins">Edit Pengaturan Potongan Global</h2>
        
        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-4 rounded-md mb-6">
            <h4 class="font-bold">Penjelasan</h4>
            <p class="text-sm mt-1">Nilai yang Anda masukkan adalah <strong>persentase (%)</strong> yang akan dikalikan dengan <strong>gaji pokok</strong> setiap guru saat proses perhitungan gaji.</p>
        </div>

        <form method="POST" action="potongan.php">
            <?php csrf_input(); ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="potongan_bpjs" class="block text-sm font-medium text-gray-700">Potongan BPJS (%)</label>
                    <input type="number" name="potongan_bpjs" id="potongan_bpjs" value="<?= e($potongan_data['potongan_bpjs'] ?? 0) ?>" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="Contoh: 2" required>
                </div>
                <div>
                    <label for="infak" class="block text-sm font-medium text-gray-700">Infak (%)</label>
                    <input type="number" name="infak" id="infak" value="<?= e($potongan_data['infak'] ?? 0) ?>" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="Contoh: 2" required>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" @click="showForm = false" class="bg-gray-200 text-gray-700 px-5 py-2.5 rounded-lg hover:bg-gray-300 font-semibold transition">Batal</button>
                <button type="submit" class="bg-green-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center transition">
                    <i class="fa fa-save mr-2"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg">
        <h3 class="text-xl font-bold text-gray-800 mb-4 font-poppins">Pengaturan Saat Ini</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-lg">
            <div class="bg-gray-50 p-6 rounded-lg">
                <p class="text-sm font-medium text-gray-500">Potongan BPJS</p>
                <p class="text-2xl font-bold text-gray-800"><?= number_format($potongan_data['potongan_bpjs'], 2, ',', '.') ?>%</p>
            </div>
            <div class="bg-gray-50 p-6 rounded-lg">
                <p class="text-sm font-medium text-gray-500">Potongan Infak</p>
                <p class="text-2xl font-bold text-gray-800"><?= number_format($potongan_data['infak'], 2, ',', '.') ?>%</p>
            </div>
        </div>
    </div>
</div>

<script>
function settingsPage() {
    return {
        showForm: false,
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>