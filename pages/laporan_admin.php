<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');

$page_title = 'Laporan Gaji Guru';
generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';

// Buat koneksi database setelah header
$conn = db_connect();

// Ambil data untuk filter
$karyawan_list = $conn->query("SELECT id_guru, nama_guru FROM Guru ORDER BY nama_guru ASC")->fetch_all(MYSQLI_ASSOC);
$bulan_opsi = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', 
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', 
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// Logika untuk mengambil data hasil filter untuk ringkasan
$sql_summary = "SELECT gaji_bersih, total_potongan FROM Penggajian p WHERE 1=1";
$params_summary = [];
$types_summary = '';
if (!empty($_GET['karyawan'])) {
    $sql_summary .= " AND p.id_guru = ?";
    $params_summary[] = $_GET['karyawan'];
    $types_summary .= 's';
}
if (!empty($_GET['bulan'])) {
    $sql_summary .= " AND p.bulan_penggajian = ?";
    $params_summary[] = $_GET['bulan'];
    $types_summary .= 's';
}
if (!empty($_GET['tahun'])) {
    $sql_summary .= " AND YEAR(p.tgl_input) = ?";
    $params_summary[] = $_GET['tahun'];
    $types_summary .= 'i';
}
$stmt_summary = $conn->prepare($sql_summary);
if (!empty($types_summary)) {
    $stmt_summary->bind_param($types_summary, ...$params_summary);
}
$stmt_summary->execute();
$result_summary = $stmt_summary->get_result();

$total_data = 0;
$total_gaji_bersih = 0;
$total_potongan = 0;
$rata_gaji = 0;

if ($result_summary && $result_summary->num_rows > 0) {
    $data_rows = $result_summary->fetch_all(MYSQLI_ASSOC);
    $total_data = count($data_rows);
    $total_gaji_bersih = array_sum(array_column($data_rows, 'gaji_bersih'));
    $total_potongan = array_sum(array_column($data_rows, 'total_potongan'));
    $rata_gaji = $total_data > 0 ? $total_gaji_bersih / $total_data : 0;
}
$stmt_summary->close();

?>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 font-poppins">Laporan Penggajian Guru</h2>
        <p class="text-gray-500 text-sm mt-1">Filter, analisis, dan cetak laporan penggajian untuk semua guru.</p>
    </div>

    <?php display_flash_message(); ?>

    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
        <form method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 items-center">
            <input type="hidden" name="action" value="list">
            
            <div class="col-span-1 xl:col-span-1">
                <label for="filter_karyawan" class="text-xs font-medium text-gray-500">Karyawan</label>
                <select name="karyawan" id="filter_karyawan" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-green-500">
                    <option value="">Semua</option>
                    <?php foreach ($karyawan_list as $k): ?>
                        <option value="<?= e($k['id_guru']) ?>" <?= ($_GET['karyawan'] ?? '') == $k['id_guru'] ? 'selected' : '' ?>><?= e($k['nama_guru']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-span-1 xl:col-span-1">
                <label for="filter_bulan" class="text-xs font-medium text-gray-500">Bulan</label>
                <select name="bulan" id="filter_bulan" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-green-500">
                    <option value="">Semua</option>
                    <?php foreach ($bulan_opsi as $val => $nama): ?>
                        <option value="<?= e($val) ?>" <?= ($_GET['bulan'] ?? '') == $val ? 'selected' : '' ?>><?= e($nama) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-span-1 xl:col-span-1">
                <label for="filter_tahun" class="text-xs font-medium text-gray-500">Tahun</label>
                <input type="number" name="tahun" id="filter_tahun" value="<?= e($_GET['tahun'] ?? date('Y')) ?>" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-green-500">
            </div>

            <div class="col-span-1 flex space-x-2 pt-4">
                 <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 font-semibold text-sm transition-all">Terapkan</button>
                 <a href="laporan_admin.php" class="p-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300" title="Reset Filter">
                    <i class="fa-solid fa-arrows-rotate"></i>
                 </a>
            </div>
            
            <div class="col-span-full md:col-span-2 lg:col-span-4 xl:col-span-2 flex items-center justify-start xl:justify-end space-x-2 pt-4 xl:pt-4 border-t xl:border-t-0 xl:border-l border-gray-200 mt-4 xl:mt-0 xl:pl-4">
                 <span class="text-sm font-medium text-gray-600 hidden lg:inline">Aksi Laporan:</span>
                 <button onclick="printReport()" class="bg-blue-600 text-white px-3 py-2 rounded-md hover:bg-blue-700 text-sm font-semibold transition-all flex items-center">
                    <i class="fa-solid fa-print mr-2"></i>Cetak
                </button>
                <button onclick="downloadReportPDF()" class="bg-red-600 text-white px-3 py-2 rounded-md hover:bg-red-700 text-sm font-semibold transition-all flex items-center">
                    <i class="fa-solid fa-file-pdf mr-2"></i>PDF
                </button>
            </div>
        </form>
    </div>

    <?php if (isset($_GET['action']) && $total_data > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        <div class="bg-white p-4 rounded-lg border border-gray-200 flex items-center">
            <div class="flex-shrink-0 bg-blue-100 text-blue-600 rounded-full h-12 w-12 flex items-center justify-center">
                <i class="fas fa-receipt text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500 font-medium">Total Data Gaji</p>
                <p class="text-2xl font-bold text-gray-800"><?= $total_data ?></p>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 flex items-center">
            <div class="flex-shrink-0 bg-green-100 text-green-600 rounded-full h-12 w-12 flex items-center justify-center">
                <i class="fas fa-wallet text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500 font-medium">Total Gaji Bersih</p>
                <p class="text-xl font-bold text-gray-800">Rp <?= number_format($total_gaji_bersih, 0, ',', '.') ?></p>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 flex items-center">
            <div class="flex-shrink-0 bg-red-100 text-red-600 rounded-full h-12 w-12 flex items-center justify-center">
                <i class="fas fa-arrow-down-wide-short text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500 font-medium">Total Potongan</p>
                <p class="text-xl font-bold text-gray-800">Rp <?= number_format($total_potongan, 0, ',', '.') ?></p>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 flex items-center">
            <div class="flex-shrink-0 bg-purple-100 text-purple-600 rounded-full h-12 w-12 flex items-center justify-center">
                <i class="fas fa-chart-pie text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500 font-medium">Rata-rata Gaji</p>
                <p class="text-xl font-bold text-gray-800">Rp <?= number_format($rata_gaji, 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
        <div class="overflow-x-auto" style="max-width: 100%;">
            <table class="w-full text-sm text-left text-gray-700" style="min-width: 1200px;">
                <thead class="text-xs uppercase bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 font-poppins">
                    <tr>
                        <th class="px-3 py-4 text-center font-semibold">No</th>
                        <th class="px-3 py-4 text-center font-semibold">No Slip</th>
                        <th class="px-4 py-4 text-left font-semibold">Nama Guru</th>
                        <th class="px-3 py-4 text-center font-semibold">Periode</th>
                        <th class="px-3 py-4 text-center font-semibold">Gaji Pokok</th>
                        <th class="px-3 py-4 text-center font-semibold">Tunj. Beras</th>
                        <th class="px-3 py-4 text-center font-semibold">Tunj. Hadir</th>
                        <th class="px-3 py-4 text-center font-semibold">Tunj. Suami/Istri</th>
                        <th class="px-3 py-4 text-center font-semibold">Tunj. Anak</th>
                        <th class="px-3 py-4 text-center font-semibold">Gaji Kotor</th>
                        <th class="px-3 py-4 text-center font-semibold">BPJS</th>
                        <th class="px-3 py-4 text-center font-semibold">Infak</th>
                        <th class="px-3 py-4 text-center font-semibold">Total Potongan</th>
                        <th class="px-3 py-4 text-center font-semibold">Gaji Bersih</th>
                        <th class="px-4 py-4 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT p.id_penggajian, p.no_slip_gaji, g.nama_guru, p.tgl_input, p.bulan_penggajian, 
                                   p.gaji_pokok, p.tunjangan_beras, p.tunjangan_kehadiran, p.tunjangan_suami_istri, p.tunjangan_anak,
                                   p.gaji_kotor, p.potongan_bpjs, p.infak, p.total_potongan, p.gaji_bersih
                            FROM Penggajian p 
                            JOIN Guru g ON p.id_guru = g.id_guru WHERE 1=1";
                    $params = [];
                    $types = '';
                    if (!empty($_GET['karyawan'])) {
                        $sql .= " AND p.id_guru = ?";
                        $params[] = $_GET['karyawan'];
                        $types .= 's';
                    }
                    if (!empty($_GET['bulan'])) {
                        $sql .= " AND p.bulan_penggajian = ?";
                        $params[] = $_GET['bulan'];
                        $types .= 's';
                    }
                    if (!empty($_GET['tahun'])) {
                        $sql .= " AND YEAR(p.tgl_input) = ?";
                        $params[] = $_GET['tahun'];
                        $types .= 'i';
                    }
                    $sql .= " ORDER BY p.tgl_input DESC";

                    $stmt = $conn->prepare($sql);
                    if (!empty($types)) {
                        $stmt->bind_param($types, ...$params);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();

                                        if ($result->num_rows > 0):
                        $no = 1;
                        while ($row = $result->fetch_assoc()):
                        ?>
                            <tr class="bg-white border-b border-gray-200 hover:bg-blue-50 transition-colors duration-200">
                                <td class="px-3 py-4 text-center text-gray-600 font-medium"><?= $no++ ?></td>
                                <td class="px-3 py-4 text-center">
                                    <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded text-gray-700">
                                        <?= e($row['no_slip_gaji'] ?? 'SG' . date('ym') . str_pad($no-1, 4, '0', STR_PAD_LEFT)) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-gray-800 text-sm"><?= e($row['nama_guru'] ?? 'N/A') ?></div>
                                </td>
                                <td class="px-3 py-4 text-center text-gray-600">
                                    <span class="text-xs"><?= e($bulan_opsi[$row['bulan_penggajian']] ?? 'N/A') ?></span>
                                    <div class="text-xs text-gray-500"><?= date('Y', strtotime($row['tgl_input'] ?? 'now')) ?></div>
                                </td>
                                <td class="px-3 py-4 text-center">
                                    <span class="text-xs font-medium text-blue-600">
                                        <?= number_format($row['gaji_pokok'] ?? 0, 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-center">
                                    <span class="text-xs font-medium text-orange-600">
                                        <?= number_format($row['tunjangan_beras'] ?? 0, 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-center">
                                    <span class="text-xs font-medium text-purple-600">
                                        <?= number_format($row['tunjangan_kehadiran'] ?? 0, 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-center">
                                    <span class="text-xs font-medium text-indigo-600">
                                        <?= number_format($row['tunjangan_suami_istri'] ?? 0, 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-center">
                                    <span class="text-xs font-medium text-teal-600">
                                        <?= number_format($row['tunjangan_anak'] ?? 0, 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-center">
                                    <span class="text-xs font-semibold text-blue-600">
                                        <?= number_format($row['gaji_kotor'] ?? 0, 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-center">
                                    <span class="text-xs font-medium text-red-600">
                                        <?= number_format($row['potongan_bpjs'] ?? 0, 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-center">
                                    <span class="text-xs font-medium text-red-600">
                                        <?= number_format($row['infak'] ?? 0, 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-center">
                                    <span class="text-xs font-medium text-red-600">
                                        <?= number_format($row['total_potongan'] ?? 0, 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-center">
                                    <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-1 rounded">
                                        <?= number_format($row['gaji_bersih'] ?? 0, 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <button onclick="printSlipGaji('<?= e($row['id_penggajian'] ?? '') ?>')" 
                                            class="bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded-md text-xs font-medium transition-colors duration-200 shadow-sm hover:shadow-md" 
                                            title="Cetak Slip Gaji">
                                        <i class="fa-solid fa-print"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else:
                        echo '<tr><td colspan="15" class="text-center py-16 text-gray-500">';
                        echo '<div class="flex flex-col items-center justify-center">';
                        echo '<i class="fa-solid fa-folder-open fa-4x mb-4 text-gray-300"></i>';
                        echo '<p class="text-lg font-medium text-gray-600">Tidak ada data gaji yang ditemukan</p>';
                        echo '<p class="text-sm text-gray-400 mt-1">Silakan tambah data gaji baru atau sesuaikan filter pencarian</p>';
                        echo '</div></td></tr>';
                    endif;
                    $stmt->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
</div>

<script>
// Fungsi untuk mencetak slip gaji
function printSlipGaji(idPenggajian) {
    // Buka halaman cetak slip gaji di tab baru
    window.open(`../reports/slip_gaji.php?id=${idPenggajian}`, '_blank');
}

// Fungsi untuk mencetak laporan lengkap
function printReport() {
    // Dapatkan parameter filter saat ini
    const params = new URLSearchParams();
    params.append('action', 'print');
    
    // Tambahkan filter yang sedang aktif
    const filterKaryawan = document.getElementById('filter_karyawan')?.value;
    const filterBulan = document.getElementById('filter_bulan')?.value;
    const filterTahun = document.getElementById('filter_tahun')?.value;
    
    if (filterKaryawan) params.append('karyawan', filterKaryawan);
    if (filterBulan) params.append('bulan', filterBulan);
    if (filterTahun) params.append('tahun', filterTahun);
    
    // Buka halaman cetak laporan di tab baru
    window.open(`../reports/laporan_gaji.php?${params.toString()}`, '_blank');
}

// Fungsi untuk download laporan PDF
function downloadReportPDF() {
    // Dapatkan parameter filter saat ini
    const params = new URLSearchParams();
    
    // Tambahkan filter yang sedang aktif
    const filterKaryawan = document.getElementById('filter_karyawan')?.value;
    const filterBulan = document.getElementById('filter_bulan')?.value;
    const filterTahun = document.getElementById('filter_tahun')?.value;
    
    if (filterKaryawan) params.append('karyawan', filterKaryawan);
    if (filterBulan) params.append('bulan', filterBulan);
    if (filterTahun) params.append('tahun', filterTahun);
    
    // Buka halaman cetak PDF laporan di tab baru
    window.open(`../reports/cetak_pdf_laporan_gaji.php?${params.toString()}`, '_blank');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
