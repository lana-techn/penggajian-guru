<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');

$conn = db_connect();
$page_title = 'Proses Gaji Guru';

// Check if required tables exist
$required_tables = ['Penggajian', 'Guru', 'Jabatan', 'Tunjangan', 'Potongan'];
$missing_tables = [];
foreach ($required_tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check->num_rows == 0) {
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    set_flash_message('error', 'Tabel yang diperlukan tidak ditemukan: ' . implode(', ', $missing_tables) . '. Silakan jalankan database.sql terlebih dahulu.');
}

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
// Fungsi ini menggunakan helper functions untuk memastikan konsistensi perhitungan
function calculate_payroll_server($conn, $id_guru, $bulan, $tahun) {
    $guru_stmt = $conn->prepare("SELECT g.id_jabatan, g.tgl_masuk, g.status_kawin, g.jml_anak, j.gaji_awal as gaji_pokok FROM Guru g JOIN Jabatan j ON g.id_jabatan = j.id_jabatan WHERE g.id_guru = ?");
    $guru_stmt->bind_param('s', $id_guru);
    $guru_stmt->execute();
    $guru_data = $guru_stmt->get_result()->fetch_assoc();
    if (!$guru_data) return null;

    $id_jabatan = $guru_data['id_jabatan'];
    $tunjangan_stmt = $conn->prepare("SELECT * FROM Tunjangan WHERE id_jabatan = ?");
    $tunjangan_stmt->bind_param('s', $id_jabatan);
    $tunjangan_stmt->execute();
    $tunjangan_data = $tunjangan_stmt->get_result()->fetch_assoc() ?? [];
    
    $potongan_stmt = $conn->prepare("SELECT * FROM Potongan WHERE id_jabatan = ? ORDER BY id_potongan DESC LIMIT 1");
    $potongan_stmt->bind_param('s', $id_jabatan);
    $potongan_stmt->execute();
    $potongan_data = $potongan_stmt->get_result()->fetch_assoc() ?? [];
    
    $kehadiran_stmt = $conn->prepare("SELECT jml_terlambat FROM Rekap_Kehadiran WHERE id_guru = ? AND bulan = ? AND tahun = ?");
    $kehadiran_stmt->bind_param('sss', $id_guru, $bulan, $tahun);
    $kehadiran_stmt->execute();
    $kehadiran_data = $kehadiran_stmt->get_result()->fetch_assoc();
    $jml_terlambat = $kehadiran_data['jml_terlambat'] ?? 0;

    $gaji = [];
    
    // Masa Kerja dan Gaji Pokok (dengan kenaikan tahunan)
    $tgl_masuk = new DateTime($guru_data['tgl_masuk']);
    $tgl_proses = new DateTime("$tahun-$bulan-01");
    $masa_kerja = $tgl_masuk->diff($tgl_proses)->y;
    $gaji['masa_kerja'] = $masa_kerja;
    
    $gaji_awal = (float)($guru_data['gaji_pokok'] ?? 0);
    $kenaikan_tahunan = 50000;
    $gaji['gaji_pokok'] = $gaji_awal + ($masa_kerja * $kenaikan_tahunan);
    
    // Tunjangan Tetap
    $gaji['tunjangan_beras'] = 50000; // Nilai tetap
    
    // Tunjangan Suami/Istri
    $tunjangan_suami_istri = 0;
    if (in_array($guru_data['status_kawin'], ['Kawin', 'Menikah', 'menikah'])) {
        $tunjangan_suami_istri = (float)($tunjangan_data['tunjangan_suami_istri'] ?? 0);
    }
    $gaji['tunjangan_suami_istri'] = $tunjangan_suami_istri;
    
    // Tunjangan Anak (maks 2 anak)
    $gaji['tunjangan_anak'] = calculate_tunjangan_anak($guru_data['jml_anak'] ?? 0);
    
    // Tunjangan Kehadiran dan Potongan Terlambat
    $tunjangan_kehadiran_base = 100000;
    $gaji['tunjangan_kehadiran'] = $tunjangan_kehadiran_base;
    
    $potongan_terlambat = 0;
    if ($jml_terlambat > 5) {
        $potongan_terlambat = $tunjangan_kehadiran_base;
    } else {
        $potongan_terlambat = $jml_terlambat * 5000;
    }
    $gaji['potongan_terlambat'] = $potongan_terlambat;
    
    // Potongan - gunakan persentase dari database atau nilai default
    $persentase_bpjs = (float)($potongan_data['potongan_bpjs'] ?? 2); // Default 2% jika tidak ada di DB
    $persentase_infak = (float)($potongan_data['infak'] ?? 2); // Default 2% jika tidak ada di DB

    // Hitung potongan menggunakan fungsi helper
    $potongan = calculate_potongan($gaji['gaji_pokok'], $persentase_bpjs, $persentase_infak);
    $gaji['potongan_bpjs'] = $potongan['potongan_bpjs'];
    $gaji['infak'] = $potongan['infak'];

    $gaji['gaji_kotor'] = $gaji['gaji_pokok'] + $gaji['tunjangan_beras'] + $gaji['tunjangan_kehadiran'] + $gaji['tunjangan_suami_istri'] + $gaji['tunjangan_anak'];
    $gaji['total_potongan'] = $gaji['potongan_bpjs'] + $gaji['infak'] + $gaji['potongan_terlambat'];
    $gaji['gaji_bersih'] = $gaji['gaji_kotor'] - $gaji['total_potongan'];

    return $gaji;
}

// Function to create sample payroll data for testing
function createSamplePayrollData($conn) {
    // Get existing teachers
    $guru_list = $conn->query("SELECT id_guru FROM Guru LIMIT 3")->fetch_all(MYSQLI_ASSOC);
    
    if (empty($guru_list)) {
        return false;
    }
    
    $current_month = date('m');
    $current_year = date('Y');
    
    foreach ($guru_list as $index => $guru) {
        $id_penggajian = 'PG' . date('ymdHis') . $guru['id_guru'];
        $no_slip_gaji = 'SG' . date('ym') . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
        
        // Calculate sample payroll data
        $gaji_pokok = 5000000 + ($index * 500000);
        $tunjangan_beras = 50000;
        $tunjangan_kehadiran = calculate_tunjangan_kehadiran(0); // Tidak ada keterlambatan
        $tunjangan_suami_istri = 300000;
        $tunjangan_anak = calculate_tunjangan_anak(1); // 1 anak
        $potongan = calculate_potongan($gaji_pokok, 2, 2); // 2% BPJS dan 2% Infak
        $potongan_bpjs = $potongan['potongan_bpjs'];
        $infak = $potongan['infak'];
        $gaji_kotor = $gaji_pokok + $tunjangan_beras + $tunjangan_kehadiran + $tunjangan_suami_istri + $tunjangan_anak;
        $total_potongan = $potongan_bpjs + $infak;
        $gaji_bersih = $gaji_kotor - $total_potongan;
        
        $stmt = $conn->prepare("INSERT INTO Penggajian (id_penggajian, no_slip_gaji, id_guru, masa_kerja, gaji_pokok, tunjangan_beras, tunjangan_kehadiran, tunjangan_suami_istri, tunjangan_anak, potongan_bpjs, infak, gaji_kotor, total_potongan, gaji_bersih, tgl_input, bulan_penggajian) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $masa_kerja = 5 + $index;
        $tgl_input = date('Y-m-d');
        
        $stmt->bind_param('sssddddddddddsss', 
            $id_penggajian, $no_slip_gaji, $guru['id_guru'], $masa_kerja, 
            $gaji_pokok, $tunjangan_beras, $tunjangan_kehadiran, $tunjangan_suami_istri, 
            $tunjangan_anak, $potongan_bpjs, $infak, $gaji_kotor, $total_potongan, 
            $gaji_bersih, $tgl_input, $current_month);
        
        $stmt->execute();
    }
    
    return true;
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
            $no_slip_gaji = 'SG' . date('ym') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $stmt = $conn->prepare("INSERT INTO Penggajian (id_penggajian, no_slip_gaji, id_guru, masa_kerja, gaji_pokok, tunjangan_beras, tunjangan_kehadiran, tunjangan_suami_istri, tunjangan_anak, potongan_bpjs, infak, gaji_kotor, total_potongan, gaji_bersih, tgl_input, bulan_penggajian) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssddddddddddsss', $id_penggajian_baru, $no_slip_gaji, $id_guru, $calculated_gaji['masa_kerja'], $calculated_gaji['gaji_pokok'], $calculated_gaji['tunjangan_beras'], $calculated_gaji['tunjangan_kehadiran'], $calculated_gaji['tunjangan_suami_istri'], $calculated_gaji['tunjangan_anak'], $calculated_gaji['potongan_bpjs'], $calculated_gaji['infak'], $calculated_gaji['gaji_kotor'], $calculated_gaji['total_potongan'], $calculated_gaji['gaji_bersih'], $tgl_input, $bulan);
            if ($stmt->execute()) set_flash_message('success', 'Data gaji berhasil ditambahkan.');
            else set_flash_message('error', 'Gagal menambah data gaji: ' . $stmt->error);
        }
    }
    header('Location: proses_gaji.php');
    exit;
}



// --- PROSES HAPUS ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM Penggajian WHERE id_penggajian = ?");
    $stmt->bind_param('s', $id);
    if ($stmt->execute()) set_flash_message('success', 'Data gaji berhasil dihapus.');
    else set_flash_message('error', 'Gagal menghapus data gaji.');
    
    header('Location: proses_gaji.php');
    exit;
}

// --- FILTER DATA GAJI ---
$filter_guru = $_GET['guru'] ?? '';
$filter_bulan = $_GET['bulan'] ?? '';
$filter_tahun = $_GET['tahun'] ?? '';

// Debug: Check if there are any payroll records
$debug_count = $conn->query("SELECT COUNT(*) as total FROM Penggajian")->fetch_assoc();
if ($debug_count['total'] == 0) {
    set_flash_message('warning', 'Belum ada data penggajian. Silakan tambah data gaji terlebih dahulu.');
    
    // Optional: Create sample data for testing
    if (isset($_GET['create_sample']) && $_GET['create_sample'] === '1') {
        createSamplePayrollData($conn);
        set_flash_message('success', 'Data sample berhasil dibuat. Silakan refresh halaman.');
        header('Location: proses_gaji.php');
        exit;
    }
}

$sql = "SELECT p.*, g.nama_guru, YEAR(p.tgl_input) as payroll_year FROM Penggajian p JOIN Guru g ON p.id_guru = g.id_guru WHERE 1=1";
$params = [];
$types = '';
if ($filter_guru) { $sql .= " AND p.id_guru = ?"; $params[] = $filter_guru; $types .= 's'; }
if ($filter_bulan) { $sql .= " AND p.bulan_penggajian = ?"; $params[] = $filter_bulan; $types .= 's'; }
if ($filter_tahun) { $sql .= " AND YEAR(p.tgl_input) = ?"; $params[] = $filter_tahun; $types .= 's'; }
$sql .= " ORDER BY p.tgl_input DESC, g.nama_guru ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    set_flash_message('error', 'Error preparing query: ' . $conn->error);
    $result = null;
} else {
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        set_flash_message('error', 'Error executing query: ' . $stmt->error);
        $result = null;
    } else {
        $result = $stmt->get_result();
        if (!$result) {
            set_flash_message('error', 'Error getting result: ' . $stmt->error);
        } else {
            // Store the result data in an array to prevent consumption
            $payroll_data = [];
            while ($row = $result->fetch_assoc()) {
                $payroll_data[] = $row;
            }
            $result = null; // Clear the original result set
        }
    }
}

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
                            <div class="flex items-center space-x-2">
                                <label class="w-1/2 block text-sm font-medium text-gray-700">Potongan Terlambat</label>
                                <input type="text" :value="formatCurrency(formData.potongan_terlambat)" class="w-1/2 bg-gray-100 border-gray-300 rounded-lg shadow-sm" readonly>
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
        
        <!-- Ringkasan Data -->
        <?php if ($payroll_data && count($payroll_data) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <div class="flex items-center">
                    <i class="fas fa-users text-blue-600 text-xl mr-3"></i>
                    <div>
                        <p class="text-sm text-blue-600 font-medium">Total Data</p>
                        <p class="text-2xl font-bold text-blue-800"><?= count($payroll_data) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Filter -->
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 items-end bg-gray-50 p-4 rounded-lg border">
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
            <div class="flex items-end">
                <button type="submit" class="w-full bg-green-600 text-white px-4 py-2.5 rounded-lg shadow-md hover:bg-green-700 font-semibold flex items-center justify-center transition-colors duration-200">
                    <i class="fa fa-search mr-2"></i>Filter Data
                </button>
            </div>
        </form>

        <div class="overflow-x-auto bg-white rounded-lg shadow-sm border border-gray-200" style="max-width: 100%;">
            <table class="min-w-full text-sm text-left text-gray-600 divide-y divide-gray-200" style="min-width: 1200px;">
                <thead class="bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 uppercase font-poppins text-xs">
                    <tr>
                        <th class="px-2 py-4 text-center font-semibold">No</th>
                        <th class="px-2 py-4 text-center font-semibold">No Slip</th>
                        <th class="px-3 py-4 text-left font-semibold">Nama Guru</th>
                        <th class="px-2 py-4 text-center font-semibold">Periode</th>
                        <th class="px-2 py-4 text-center font-semibold">Gaji Pokok</th>
                        <th class="px-2 py-4 text-center font-semibold">Tunj. Beras</th>
                        <th class="px-2 py-4 text-center font-semibold">Tunj. Hadir</th>
                        <th class="px-2 py-4 text-center font-semibold">Tunj. Suami/Istri</th>
                        <th class="px-2 py-4 text-center font-semibold">Tunj. Anak</th>
                        <th class="px-2 py-4 text-center font-semibold">Gaji Kotor</th>
                        <th class="px-2 py-4 text-center font-semibold">BPJS</th>
                        <th class="px-2 py-4 text-center font-semibold">Infak</th>
                        <th class="px-2 py-4 text-center font-semibold">Pot. Terlambat</th>
                        <th class="px-2 py-4 text-center font-semibold">Total Potongan</th>
                        <th class="px-2 py-4 text-center font-semibold">Gaji Bersih</th>
                        <th class="px-3 py-4 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($payroll_data): ?>
                        <?php $no = 1; foreach ($payroll_data as $row): ?>
                            <tr class="border-b border-gray-200 hover:bg-blue-50 transition-colors duration-200">
                                <td class="px-2 py-4 text-center text-gray-600 font-medium"><?= $no++ ?></td>
                                <td class="px-2 py-4 text-center">
                                    <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded text-gray-700">
                                        <?= e($row['no_slip_gaji'] ?? 'SG' . date('ym') . str_pad($no-1, 4, '0', STR_PAD_LEFT)) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-4">
                                    <div class="font-semibold text-gray-800 text-sm"><?= e($row['nama_guru']) ?></div>
                                </td>
                                <td class="px-2 py-4 text-center text-gray-600">
                                    <span class="text-xs"><?= e($bulan_opsi[$row['bulan_penggajian']] ?? '') ?></span>
                                    <div class="text-xs text-gray-500"><?= e($row['payroll_year']) ?></div>
                                </td>
                                <td class="px-2 py-4 text-center">
                                    <span class="text-xs font-medium text-blue-600">
                                        <?= number_format($row['gaji_pokok'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-2 py-4 text-center">
                                    <span class="text-xs font-medium text-orange-600">
                                        <?= number_format($row['tunjangan_beras'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-2 py-4 text-center">
                                    <span class="text-xs font-medium text-purple-600">
                                        <?= number_format($row['tunjangan_kehadiran'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-2 py-4 text-center">
                                    <span class="text-xs font-medium text-indigo-600">
                                        <?= number_format($row['tunjangan_suami_istri'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-2 py-4 text-center">
                                    <span class="text-xs font-medium text-teal-600">
                                        <?= number_format($row['tunjangan_anak'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-2 py-4 text-center">
                                    <span class="text-xs font-semibold text-blue-600">
                                        <?= number_format($row['gaji_kotor'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-2 py-4 text-center">
                                    <span class="text-xs font-medium text-red-600">
                                        <?= number_format($row['potongan_bpjs'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-2 py-4 text-center">
                                    <span class="text-xs font-medium text-red-600">
                                        <?= number_format($row['infak'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-2 py-4 text-center">
                                    <span class="text-xs font-medium text-red-600">
                                        <?= number_format($row['total_potongan'] - $row['potongan_bpjs'] - $row['infak'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-2 py-4 text-center">
                                    <span class="text-xs font-medium text-red-600">
                                        <?= number_format($row['total_potongan'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-2 py-4 text-center">
                                    <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-1 rounded">
                                        <?= number_format($row['gaji_bersih'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-3 py-4">
                                    <div class="flex items-center justify-center space-x-1">
                                        <button onclick="printSlipGaji('<?= e($row['id_penggajian']) ?>')" 
                                                class="bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded-md text-xs font-medium transition-colors duration-200 shadow-sm hover:shadow-md" 
                                                title="Cetak Slip Gaji">
                                            <i class="fa-solid fa-print"></i>
                                        </button>
                                        <button onclick="downloadSlipGajiPDF('<?= e($row['id_penggajian']) ?>')" 
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded-md text-xs font-medium transition-colors duration-200 shadow-sm hover:shadow-md" 
                                                title="Download PDF">
                                            <i class="fa-solid fa-file-pdf"></i>
                                        </button>
                                        <button @click="editGaji(<?= htmlspecialchars(json_encode($row)) ?>)" 
                                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded-md text-xs font-medium transition-colors duration-200 shadow-sm hover:shadow-md" 
                                                title="Edit Data">
                                            <i class="fa-solid fa-pencil"></i>
                                        </button>
                                        <a href="?action=delete&id=<?= e($row['id_penggajian']) ?>&token=<?= $_SESSION['csrf_token'] ?>" 
                                           onclick="return confirm('Yakin ingin menghapus data ini?')" 
                                           class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded-md text-xs font-medium transition-colors duration-200 shadow-sm hover:shadow-md" 
                                           title="Hapus Data">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php elseif ($payroll_data === null): ?>
                        <tr>
                            <td colspan="16" class="text-center py-16 text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fa-solid fa-exclamation-triangle fa-4x mb-4 text-red-300"></i>
                                    <p class="text-lg font-medium text-red-600">Error dalam mengambil data</p>
                                    <p class="text-sm text-gray-400 mt-1">Terjadi kesalahan pada database. Silakan coba lagi atau hubungi administrator.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="16" class="text-center py-16 text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fa-solid fa-folder-open fa-4x mb-4 text-gray-300"></i>
                                    <p class="text-lg font-medium text-gray-600">Tidak ada data gaji yang ditemukan</p>
                                    <p class="text-sm text-gray-400 mt-1">Silakan tambah data gaji baru atau sesuaikan filter pencarian</p>
                                    <?php if ($debug_count['total'] == 0): ?>
                                        <div class="mt-4">
                                            <a href="?create_sample=1" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                                                <i class="fa-solid fa-plus mr-2"></i>Buat Data Sample
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Fungsi untuk mencetak slip gaji
function printSlipGaji(idPenggajian) {
    // Buka halaman cetak slip gaji di tab baru
    window.open(`../reports/slip_gaji.php?id=${idPenggajian}`, '_blank');
}

// Fungsi untuk download slip gaji PDF
function downloadSlipGajiPDF(idPenggajian) {
    // Buka halaman cetak PDF slip gaji
    window.open(`../reports/cetak_pdf_slip_gaji.php?id=${idPenggajian}`, '_blank');
}

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
            potongan_terlambat: 0,
        },
        
        get gajiKotor() {
            return (this.formData.gaji_pokok || 0) + (this.formData.tunjangan_beras || 0) + (this.formData.tunjangan_kehadiran || 0) + (this.formData.tunjangan_suami_istri || 0) + (this.formData.tunjangan_anak || 0);
        },

        get totalPotongan() {
            return (this.formData.potongan_bpjs || 0) + (this.formData.infak || 0) + (this.formData.potongan_terlambat || 0);
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
                tunjangan_anak: 0, potongan_bpjs: 0, infak: 0, potongan_terlambat: 0,
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
                    this.formData.potongan_terlambat = data.potongan_terlambat;
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
                potongan_terlambat: parseFloat(gajiData.total_potongan) - parseFloat(gajiData.potongan_bpjs) - parseFloat(gajiData.infak),
            };
            this.showForm = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            // Fetch fresh data in background to ensure accuracy, in case logic has changed
            this.fetchGuruData();
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>