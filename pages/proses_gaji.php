<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');

$conn = db_connect();
$page_title = 'Proses Gaji Guru';

// Ambil data guru untuk dropdown
$guru_list = $conn->query("SELECT id_guru, nama_guru, tgl_masuk, status_kawin, jml_anak FROM Guru ORDER BY nama_guru ASC")->fetch_all(MYSQLI_ASSOC);

$tahun_sekarang = (int)date('Y');
$bulan_sekarang = date('m');

$bulan_opsi = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', 
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', 
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// --- FUNGSI PERHITUNGAN GAJI DI SISI SERVER ---
function calculate_payroll_server($conn, $id_guru, $bulan, $tahun) {
    $guru_stmt = $conn->prepare("SELECT g.id_jabatan, g.tgl_masuk, g.status_kawin, g.jml_anak, j.gaji_awal as gaji_pokok FROM Guru g JOIN Jabatan j ON g.id_jabatan = j.id_jabatan WHERE g.id_guru = ?");
    $guru_stmt->bind_param('s', $id_guru);
    $guru_stmt->execute();
    $guru_data = $guru_stmt->get_result()->fetch_assoc();
    if (!$guru_data) return null;

    $id_jabatan = $guru_data['id_jabatan'];
    $tunjangan_data = $conn->execute_query("SELECT * FROM Tunjangan WHERE id_jabatan = ?", [$id_jabatan])->fetch_assoc() ?? [];
    $potongan_data = $conn->execute_query("SELECT * FROM Potongan WHERE id_jabatan = ?", [$id_jabatan])->fetch_assoc() ?? [];
    $kehadiran_data = $conn->execute_query("SELECT jml_terlambat FROM Rekap_Kehadiran WHERE id_guru = ? AND bulan = ? AND tahun = ?", [$id_guru, $bulan, $tahun])->fetch_assoc();
    $jml_terlambat = $kehadiran_data['jml_terlambat'] ?? 0;

    $gaji = [];
    $gaji['gaji_pokok'] = (float)($guru_data['gaji_pokok'] ?? 0);
    $gaji['tunjangan_beras'] = (float)($tunjangan_data['tunjangan_beras'] ?? 0);
    $tunjangan_suami_istri = 0;
    if (in_array($guru_data['status_kawin'], ['Kawin', 'Menikah'])) {
        $tunjangan_suami_istri = (float)($tunjangan_data['tunjangan_suami_istri'] ?? 0);
    }
    $gaji['tunjangan_suami_istri'] = $tunjangan_suami_istri;
    $jml_anak_tunjangan = min((int)($guru_data['jml_anak'] ?? 0), 2);
    $gaji['tunjangan_anak'] = $jml_anak_tunjangan * (float)($tunjangan_data['tunjangan_anak'] ?? 0);
    $gaji['tunjangan_kehadiran'] = ($jml_terlambat > 5) ? 0 : (100000 - ($jml_terlambat * 5000));
    
    $persentase_bpjs = (float)($potongan_data['potongan_bpjs'] ?? 0);
    $persentase_infak = (float)($potongan_data['infak'] ?? 0);

    $gaji['potongan_bpjs'] = $gaji['gaji_pokok'] * ($persentase_bpjs / 100);
    $gaji['infak'] = $gaji['gaji_pokok'] * ($persentase_infak / 100);

    $tgl_masuk = new DateTime($guru_data['tgl_masuk']);
    $tgl_proses = new DateTime("$tahun-$bulan-01");
    $gaji['masa_kerja'] = $tgl_masuk->diff($tgl_proses)->y;

    $gaji['gaji_kotor'] = $gaji['gaji_pokok'] + $gaji['tunjangan_beras'] + $gaji['tunjangan_kehadiran'] + $gaji['tunjangan_suami_istri'] + $gaji['tunjangan_anak'];
    $gaji['total_potongan'] = $gaji['potongan_bpjs'] + $gaji['infak'];
    $gaji['gaji_bersih'] = $gaji['gaji_kotor'] - $gaji['total_potongan'];

    return $gaji;
}

// --- PROSES TAMBAH & UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_guru'])) {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_penggajian = $_POST['id_penggajian'] ?? null;
    $id_guru = $_POST['id_guru'];
    $bulan = $_POST['bulan'];
    $tahun = $_POST['tahun'];

    // Lakukan perhitungan ulang di server untuk keamanan
    $calculated_gaji = calculate_payroll_server($conn, $id_guru, $bulan, $tahun);

    if (!$calculated_gaji) {
        set_flash_message('error', 'Gagal menghitung gaji. Data guru tidak ditemukan.');
        header('Location: proses_gaji.php');
        exit;
    }

    $tgl_input = date('Y-m-d');

    if ($id_penggajian) { // --- PROSES UPDATE ---
        $stmt = $conn->prepare("UPDATE Penggajian SET id_guru=?, masa_kerja=?, gaji_pokok=?, tunjangan_beras=?, tunjangan_kehadiran=?, tunjangan_suami_istri=?, tunjangan_anak=?, potongan_bpjs=?, infak=?, gaji_kotor=?, total_potongan=?, gaji_bersih=?, bulan_penggajian=?, tgl_input=? WHERE id_penggajian=?");
        $stmt->bind_param('sidddddddddssss', $id_guru, $calculated_gaji['masa_kerja'], $calculated_gaji['gaji_pokok'], $calculated_gaji['tunjangan_beras'], $calculated_gaji['tunjangan_kehadiran'], $calculated_gaji['tunjangan_suami_istri'], $calculated_gaji['tunjangan_anak'], $calculated_gaji['potongan_bpjs'], $calculated_gaji['infak'], $calculated_gaji['gaji_kotor'], $calculated_gaji['total_potongan'], $calculated_gaji['gaji_bersih'], $bulan, $tgl_input, $id_penggajian);
        if ($stmt->execute()) set_flash_message('success', 'Data gaji berhasil diupdate.');
        else set_flash_message('error', 'Gagal update data gaji: ' . $stmt->error);
    } else { // --- PROSES TAMBAH ---
        $cek = $conn->query("SELECT id_penggajian FROM Penggajian WHERE id_guru='$id_guru' AND bulan_penggajian='$bulan' AND YEAR(tgl_input)='$tahun'");
        if ($cek->num_rows > 0) {
            set_flash_message('error', "Data gaji untuk guru ini pada periode $bulan_opsi[$bulan] $tahun sudah ada.");
        } else {
            $id_penggajian_baru = 'PG' . date('ymdHis') . $id_guru;
            $status_validasi = 'Belum Valid';
            $stmt = $conn->prepare("INSERT INTO Penggajian (id_penggajian, id_guru, masa_kerja, gaji_pokok, tunjangan_beras, tunjangan_kehadiran, tunjangan_suami_istri, tunjangan_anak, potongan_bpjs, infak, gaji_kotor, total_potongan, gaji_bersih, tgl_input, bulan_penggajian, status_validasi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssddddddddddssss', $id_penggajian_baru, $id_guru, $calculated_gaji['masa_kerja'], $calculated_gaji['gaji_pokok'], $calculated_gaji['tunjangan_beras'], $calculated_gaji['tunjangan_kehadiran'], $calculated_gaji['tunjangan_suami_istri'], $calculated_gaji['tunjangan_anak'], $calculated_gaji['potongan_bpjs'], $calculated_gaji['infak'], $calculated_gaji['gaji_kotor'], $calculated_gaji['total_potongan'], $calculated_gaji['gaji_bersih'], $tgl_input, $bulan, $status_validasi);
            if ($stmt->execute()) set_flash_message('success', 'Data gaji berhasil ditambahkan.');
            else set_flash_message('error', 'Gagal menambah data gaji: ' . $stmt->error);
        }
    }
    header('Location: proses_gaji.php');
    exit;
}

// --- PROSES HAPUS, VALIDASI ---
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM Penggajian WHERE id_penggajian = ?");
        $stmt->bind_param('s', $id);
        if ($stmt->execute()) set_flash_message('success', 'Data gaji berhasil dihapus.');
        else set_flash_message('error', 'Gagal menghapus data gaji.');
    } elseif ($action === 'validasi') {
        $stmt = $conn->prepare("UPDATE Penggajian SET status_validasi='Valid' WHERE id_penggajian = ?");
        $stmt->bind_param('s', $id);
        if ($stmt->execute()) set_flash_message('success', 'Data gaji berhasil divalidasi.');
        else set_flash_message('error', 'Gagal validasi data gaji.');
    }
    header('Location: proses_gaji.php');
    exit;
}

// --- FILTER DATA GAJI ---
$filter_guru = $_GET['guru'] ?? '';
$filter_bulan = $_GET['bulan'] ?? '';
$filter_tahun = $_GET['tahun'] ?? '';
$filter_status = $_GET['status'] ?? '';

$sql = "SELECT p.*, g.nama_guru, YEAR(p.tgl_input) as payroll_year FROM Penggajian p JOIN Guru g ON p.id_guru = g.id_guru WHERE 1=1";
$params = [];
$types = '';
if ($filter_guru) { $sql .= " AND p.id_guru = ?"; $params[] = $filter_guru; $types .= 's'; }
if ($filter_bulan) { $sql .= " AND p.bulan_penggajian = ?"; $params[] = $filter_bulan; $types .= 's'; }
if ($filter_tahun) { $sql .= " AND YEAR(p.tgl_input) = ?"; $params[] = $filter_tahun; $types .= 's'; }
if ($filter_status) { $sql .= " AND p.status_validasi = ?"; $params[] = $filter_status; $types .= 's'; }
$sql .= " ORDER BY p.tgl_input DESC, g.nama_guru ASC";
$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="gajiPage()">
    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg mb-8">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 font-poppins"><?= e($page_title) ?></h1>
                <p class="text-gray-500 mt-1">Manajemen data penggajian guru secara lengkap.</p>
            </div>
            <button @click="showForm = !showForm; resetForm()" class="bg-green-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center transition">
                <i class="fa-solid fa-plus mr-2"></i>
                <span x-text="showForm ? 'Tutup Form' : 'Input Gaji Baru'"></span>
            </button>
        </div>

        <?php display_flash_message(); ?>

        <!-- Form Input/Edit Gaji -->
        <div x-show="showForm" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform -translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-4" class="border-t pt-6 mt-6" x-cloak>
            <form method="POST" action="">
                <?php csrf_input(); ?>
                <input type="hidden" name="id_penggajian" x-model="formData.id_penggajian">

                <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                    <!-- Kolom Kiri: Info Guru & Periode -->
                    <div class="md:col-span-4">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">Informasi Dasar</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="id_guru" class="block text-sm font-medium text-gray-700 mb-1">Guru</label>
                                <select name="id_guru" id="id_guru" x-model="formData.id_guru" @change="fetchGuruData" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500" required>
                                    <option value="">- Pilih Guru -</option>
                                    <?php foreach ($guru_list as $g): ?>
                                        <option value="<?= e($g['id_guru']) ?>"><?= e($g['nama_guru']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="bulan" class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                                    <select name="bulan" id="bulan" x-model="formData.bulan" @change="fetchGuruData" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500" required>
                                        <?php foreach ($bulan_opsi as $val => $nama): ?>
                                            <option value="<?= e($val) ?>"><?= e($nama) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="tahun" class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                                    <input type="number" name="tahun" id="tahun" x-model="formData.tahun" @change="fetchGuruData" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500" required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Masa Kerja</label>
                                <p class="text-gray-800 font-semibold text-lg" x-text="`${formData.masa_kerja} tahun`"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Tengah: Pendapatan & Potongan -->
                    <div class="md:col-span-4">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">Pendapatan</h3>
                        <div class="space-y-4">
                            <div class="flex items-center space-x-2">
                                <label class="w-1/2 block text-sm font-medium text-gray-700">Gaji Pokok</label>
                                <input type="text" :value="formatCurrency(formData.gaji_pokok)" class="w-1/2 bg-gray-100 border-gray-300 rounded-lg shadow-sm" readonly>
                            </div>
                            <div class="flex items-center space-x-2">
                                <label class="w-1/2 block text-sm font-medium text-gray-700">Tunj. Beras</label>
                                <input type="text" :value="formatCurrency(formData.tunjangan_beras)" class="w-1/2 bg-gray-100 border-gray-300 rounded-lg shadow-sm" readonly>
                            </div>
                            <div class="flex items-center space-x-2">
                                <label class="w-1/2 block text-sm font-medium text-gray-700">Tunj. Kehadiran</label>
                                <input type="text" :value="formatCurrency(formData.tunjangan_kehadiran)" class="w-1/2 bg-gray-100 border-gray-300 rounded-lg shadow-sm" readonly>
                            </div>
                            <div class="flex items-center space-x-2">
                                <label class="w-1/2 block text-sm font-medium text-gray-700">Tunj. Suami/Istri</label>
                                <input type="text" :value="formatCurrency(formData.tunjangan_suami_istri)" class="w-1/2 bg-gray-100 border-gray-300 rounded-lg shadow-sm" readonly>
                            </div>
                            <div class="flex items-center space-x-2">
                                <label class="w-1/2 block text-sm font-medium text-gray-700">Tunj. Anak</label>
                                <input type="text" :value="formatCurrency(formData.tunjangan_anak)" class="w-1/2 bg-gray-100 border-gray-300 rounded-lg shadow-sm" readonly>
                            </div>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mt-6 mb-4 border-b pb-2">Potongan</h3>
                        <div class="space-y-4">
                            <div class="flex items-center space-x-2">
                                <label class="w-1/2 block text-sm font-medium text-gray-700">BPJS (2%)</label>
                                <input type="text" :value="formatCurrency(formData.potongan_bpjs)" class="w-1/2 bg-gray-100 border-gray-300 rounded-lg shadow-sm" readonly>
                            </div>
                            <div class="flex items-center space-x-2">
                                <label class="w-1/2 block text-sm font-medium text-gray-700">Infak (2%)</label>
                                <input type="text" :value="formatCurrency(formData.infak)" class="w-1/2 bg-gray-100 border-gray-300 rounded-lg shadow-sm" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Rincian Gaji -->
                    <div class="md:col-span-4 bg-gray-50 p-6 rounded-xl">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">Rincian Gaji</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Gaji Kotor</span>
                                <span class="font-bold text-gray-800" x-text="formatCurrency(gajiKotor)"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Total Potongan</span>
                                <span class="font-bold text-red-600" x-text="formatCurrency(totalPotongan)"></span>
                            </div>
                            <hr class="my-2">
                            <div class="flex justify-between items-center text-xl">
                                <span class="font-bold text-gray-800">Gaji Bersih</span>
                                <span class="font-bold text-green-600" x-text="formatCurrency(gajiBersih)"></span>
                            </div>
                        </div>
                        <div class="mt-8 flex justify-end space-x-3">
                            <button type="button" @click="showForm = false; resetForm()" class="bg-gray-200 text-gray-700 px-5 py-2.5 rounded-lg hover:bg-gray-300 font-semibold transition">Batal</button>
                            <button type="submit" class="bg-green-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center transition" :disabled="!formData.id_guru">
                                <i class="fa fa-save mr-2"></i> Simpan Data
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Data Gaji -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg">
        <h3 class="text-xl font-bold text-gray-800 mb-4 font-poppins">Data Gaji Guru</h3>
        <!-- Filter -->
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6 items-end">
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Guru</label>
                <select name="guru" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500">
                    <option value="">Semua Guru</option>
                    <?php foreach ($guru_list as $g): ?>
                        <option value="<?= e($g['id_guru']) ?>" <?= ($filter_guru == $g['id_guru']) ? 'selected' : '' ?>><?= e($g['nama_guru']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <select name="bulan" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500">
                    <option value="">Semua Bulan</option>
                    <?php foreach ($bulan_opsi as $val => $nama): ?>
                        <option value="<?= e($val) ?>" <?= ($filter_bulan == $val) ? 'selected' : '' ?>><?= e($nama) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                <input type="number" name="tahun" value="<?= e($filter_tahun) ?>" placeholder="Cth: <?= $tahun_sekarang ?>" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500">
            </div>
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500">
                    <option value="">Semua Status</option>
                    <option value="Valid" <?= ($filter_status == 'Valid') ? 'selected' : '' ?>>Valid</option>
                    <option value="Belum Valid" <?= ($filter_status == 'Belum Valid') ? 'selected' : '' ?>>Belum Valid</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center justify-center"><i class="fa fa-search mr-2"></i>Filter</button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-gray-600">
                <thead class="bg-gray-100 text-gray-700 uppercase font-poppins">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Nama Guru</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3 text-right">Gaji Bersih</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= $no++ ?></td>
                                <td class="px-4 py-3 font-semibold"><?= e($row['nama_guru']) ?></td>
                                <td class="px-4 py-3"><?= e($bulan_opsi[$row['bulan_penggajian']] ?? '') . ' ' . e($row['payroll_year']) ?></td>
                                <td class="px-4 py-3 text-right font-semibold text-green-600">Rp <?= number_format($row['gaji_bersih'], 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                          :class="{ 'bg-green-100 text-green-800': '<?= $row['status_validasi'] ?>' === 'Valid', 'bg-yellow-100 text-yellow-800': '<?= $row['status_validasi'] ?>' !== 'Valid' }">
                                        <?= e($row['status_validasi']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button @click="editGaji(<?= htmlspecialchars(json_encode($row)) ?>)" class="text-blue-600 hover:text-blue-800" title="Edit">
                                            <i class="fa-solid fa-pencil"></i>
                                        </button>
                                        <a href="?action=delete&id=<?= e($row['id_penggajian']) ?>&token=<?= $_SESSION['csrf_token'] ?>" onclick="return confirm('Yakin ingin menghapus data ini?')" class="text-red-600 hover:text-red-800" title="Hapus">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                        <?php if ($row['status_validasi'] !== 'Valid'): ?>
                                            <a href="?action=validasi&id=<?= e($row['id_penggajian']) ?>&token=<?= $_SESSION['csrf_token'] ?>" onclick="return confirm('Yakin ingin memvalidasi gaji ini?')" class="text-green-600 hover:text-green-800" title="Validasi">
                                                <i class="fa-solid fa-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-10 text-gray-500">
                                <i class="fa-solid fa-folder-open fa-3x mb-3"></i>
                                <p>Tidak ada data gaji yang ditemukan.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function gajiPage() {
    return {
        showForm: false,
        isLoading: false,
        formData: {
            id_penggajian: null,
            id_guru: '',
            bulan: '<?= $bulan_sekarang ?>',
            tahun: <?= $tahun_sekarang ?>,
            masa_kerja: 0,
            gaji_pokok: 0,
            tunjangan_beras: 0,
            tunjangan_kehadiran: 0,
            tunjangan_suami_istri: 0,
            tunjangan_anak: 0,
            potongan_bpjs: 0,
            infak: 0,
        },
        
        get gajiKotor() {
            return (this.formData.gaji_pokok || 0) + (this.formData.tunjangan_beras || 0) + (this.formData.tunjangan_kehadiran || 0) + (this.formData.tunjangan_suami_istri || 0) + (this.formData.tunjangan_anak || 0);
        },

        get totalPotongan() {
            return (this.formData.potongan_bpjs || 0) + (this.formData.infak || 0);
        },

        get gajiBersih() {
            return this.gajiKotor - this.totalPotongan;
        },

        formatCurrency(value) {
            if (typeof value !== 'number') {
                value = 0;
            }
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
        },

        resetForm() {
            this.formData = {
                id_penggajian: null, id_guru: '', bulan: '<?= $bulan_sekarang ?>', tahun: <?= $tahun_sekarang ?>, masa_kerja: 0,
                gaji_pokok: 0, tunjangan_beras: 0, tunjangan_kehadiran: 0, tunjangan_suami_istri: 0,
                tunjangan_anak: 0, potongan_bpjs: 0, infak: 0,
            };
            document.getElementById('id_guru').value = '';
        },

        fetchGuruData() {
            if (!this.formData.id_guru || !this.formData.bulan || !this.formData.tahun) {
                return;
            }
            this.isLoading = true;
            
            fetch(`../api/get_payroll_details.php?id_guru=${this.formData.id_guru}&bulan=${this.formData.bulan}&tahun=${this.formData.tahun}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        this.resetForm();
                        return;
                    }
                    this.formData.gaji_pokok = data.gaji_pokok;
                    this.formData.tunjangan_beras = data.tunjangan_beras;
                    this.formData.tunjangan_kehadiran = data.tunjangan_kehadiran;
                    this.formData.tunjangan_suami_istri = data.tunjangan_suami_istri;
                    this.formData.tunjangan_anak = data.tunjangan_anak;
                    this.formData.potongan_bpjs = data.potongan_bpjs;
                    this.formData.infak = data.infak;
                    this.formData.masa_kerja = data.masa_kerja;
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Tidak dapat mengambil data gaji. Periksa koneksi atau hubungi administrator.');
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        editGaji(gajiData) {
            console.log(gajiData); // <-- DEBUGGING
            this.formData = {
                id_penggajian: gajiData.id_penggajian,
                id_guru: gajiData.id_guru,
                bulan: gajiData.bulan_penggajian,
                tahun: gajiData.payroll_year, // Menggunakan payroll_year dari data
                masa_kerja: gajiData.masa_kerja,
                gaji_pokok: parseFloat(gajiData.gaji_pokok),
                tunjangan_beras: parseFloat(gajiData.tunjangan_beras),
                tunjangan_kehadiran: parseFloat(gajiData.tunjangan_kehadiran),
                tunjangan_suami_istri: parseFloat(gajiData.tunjangan_suami_istri),
                tunjangan_anak: parseFloat(gajiData.tunjangan_anak),
                potongan_bpjs: parseFloat(gajiData.potongan_bpjs),
                infak: parseFloat(gajiData.infak),
            };
            this.showForm = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
