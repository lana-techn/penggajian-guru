<?php
$page_title = 'Laporan Gaji';
$current_page = 'laporan'; // Berguna untuk menandai menu aktif di sidebar
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('kepala_sekolah');

$conn = db_connect();

// Ambil data untuk filter
$guru_list = $conn->query("SELECT id_guru, nama_guru FROM Guru ORDER BY nama_guru ASC")->fetch_all(MYSQLI_ASSOC);

// Array untuk nama bulan
$bulan_list = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Ambil filter dari GET request
$filter_bulan = $_GET['bulan'] ?? '';
$filter_tahun = $_GET['tahun'] ?? date('Y'); // Default tahun ini
$filter_guru = $_GET['id_guru'] ?? '';

// Bangun query dinamis berdasarkan filter
$sql = "
    SELECT 
        p.id_penggajian as Id_Gaji,
        g.nama_guru as Nama_guru, 
        j.nama_jabatan as Nama_Jabatan, 
        p.tgl_input as Tgl_Gaji, 
        p.bulan_penggajian as Bulan_Penggajian,
        p.gaji_pokok as Gaji_Pokok,
        (p.tunjangan_beras + p.tunjangan_kehadiran + p.tunjangan_suami_istri + p.tunjangan_anak) as Total_Tunjangan,
        p.gaji_kotor as Gaji_Kotor,
        p.total_potongan as Total_Potongan, 
        p.gaji_bersih as Gaji_Bersih
    FROM Penggajian p
    JOIN Guru g ON p.id_guru = g.id_guru
    JOIN Jabatan j ON g.id_jabatan = j.id_jabatan
    WHERE 1=1
";
$params = [];
$types = '';

if (!empty($filter_bulan)) {
    $sql .= " AND MONTH(p.tgl_input) = ?";
    $params[] = $filter_bulan;
    $types .= 'i';
}
if (!empty($filter_tahun)) {
    $sql .= " AND YEAR(p.tgl_input) = ?";
    $params[] = $filter_tahun;
    $types .= 'i';
}
if (!empty($filter_guru)) {
    $sql .= " AND g.id_guru = ?";
    $params[] = $filter_guru;
    $types .= 's';
}
$sql .= " ORDER BY p.tgl_input DESC, g.nama_guru ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$laporan_data = $result->fetch_all(MYSQLI_ASSOC);

// Menghitung data ringkasan
$total_data = count($laporan_data);
$total_gaji_bersih = array_sum(array_column($laporan_data, 'Gaji_Bersih'));
$total_potongan = array_sum(array_column($laporan_data, 'Total_Potongan'));
$rata_gaji = $total_data > 0 ? $total_gaji_bersih / $total_data : 0;

$stmt->close();
$conn->close();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 font-poppins">Laporan Penggajian</h2>
        <p class="text-gray-500 text-sm mt-1">Lihat, filter, dan cetak laporan gaji yang telah disetujui.</p>
    </div>

    <?php display_flash_message(); ?>

    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
        <form action="laporan.php" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 items-center">
            <div class="col-span-1 xl:col-span-1">
                <label for="filter_bulan" class="text-xs font-medium text-gray-500">Bulan</label>
                <select name="bulan" id="filter_bulan" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-green-500">
                    <option value="">Semua Bulan</option>
                    <?php foreach ($bulan_list as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $filter_bulan == $num ? 'selected' : '' ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-span-1 xl:col-span-1">
                <label for="filter_tahun" class="text-xs font-medium text-gray-500">Tahun</label>
                <input type="number" name="tahun" id="filter_tahun" placeholder="Cth: <?= date('Y') ?>" value="<?= e($filter_tahun) ?>" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-green-500">
            </div>

            <div class="col-span-1 xl:col-span-1">
                <label for="filter_guru" class="text-xs font-medium text-gray-500">Nama Guru</label>
                <select name="id_guru" id="filter_guru" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-green-500">
                    <option value="">Semua Guru</option>
                    <?php foreach ($guru_list as $guru): ?>
                        <option value="<?= e($guru['id_guru']) ?>" <?= $filter_guru == $guru['id_guru'] ? 'selected' : '' ?>><?= e($guru['nama_guru']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-span-1 flex space-x-2 pt-4">
                 <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 font-semibold text-sm transition-all">Terapkan</button>
                 <a href="laporan.php" class="p-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300" title="Reset Filter">
                    <i class="fa-solid fa-arrows-rotate"></i>
                 </a>
            </div>
            
            <div class="col-span-full md:col-span-2 lg:col-span-4 xl:col-span-2 flex items-center justify-start xl:justify-end space-x-2 pt-4 xl:pt-4 border-t xl:border-t-0 xl:border-l border-gray-200 mt-4 xl:mt-0 xl:pl-4">
                 <span class="text-sm font-medium text-gray-600 hidden lg:inline">Aksi Laporan:</span>
                 <button onclick="cetakLaporan()" class="bg-blue-600 text-white px-3 py-2 rounded-md hover:bg-blue-700 text-sm font-semibold transition-all flex items-center">
                    <i class="fa-solid fa-print mr-2"></i>Cetak
                </button>
                <button onclick="cetakPDF()" class="bg-red-600 text-white px-3 py-2 rounded-md hover:bg-red-700 text-sm font-semibold transition-all flex items-center">
                    <i class="fa-solid fa-file-pdf mr-2"></i>PDF
                </button>
            </div>
        </form>
    </div>

    <?php if ($total_data > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        <div class="bg-white p-4 rounded-lg border border-gray-200 flex items-center">
            <div class="flex-shrink-0 bg-blue-100 text-blue-600 rounded-full h-12 w-12 flex items-center justify-center"><i class="fas fa-receipt text-xl"></i></div>
            <div class="ml-4"><p class="text-sm text-gray-500 font-medium">Total Data Gaji</p><p class="text-2xl font-bold text-gray-800"><?= $total_data ?></p></div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 flex items-center">
            <div class="flex-shrink-0 bg-green-100 text-green-600 rounded-full h-12 w-12 flex items-center justify-center"><i class="fas fa-wallet text-xl"></i></div>
            <div class="ml-4"><p class="text-sm text-gray-500 font-medium">Total Gaji Bersih</p><p class="text-xl font-bold text-gray-800">Rp <?= number_format($total_gaji_bersih, 0, ',', '.') ?></p></div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 flex items-center">
            <div class="flex-shrink-0 bg-red-100 text-red-600 rounded-full h-12 w-12 flex items-center justify-center"><i class="fas fa-arrow-down-wide-short text-xl"></i></div>
            <div class="ml-4"><p class="text-sm text-gray-500 font-medium">Total Potongan</p><p class="text-xl font-bold text-gray-800">Rp <?= number_format($total_potongan, 0, ',', '.') ?></p></div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 flex items-center">
            <div class="flex-shrink-0 bg-purple-100 text-purple-600 rounded-full h-12 w-12 flex items-center justify-center"><i class="fas fa-chart-pie text-xl"></i></div>
            <div class="ml-4"><p class="text-sm text-gray-500 font-medium">Rata-rata Gaji</p><p class="text-xl font-bold text-gray-800">Rp <?= number_format($rata_gaji, 0, ',', '.') ?></p></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="overflow-x-auto" style="max-width: 100%;">
        <table class="w-full text-sm text-left text-gray-700" style="min-width: 1200px;">
            <thead class="text-xs uppercase bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 font-poppins">
                <tr>
                    <th class="px-3 py-4 text-center font-semibold">No</th>
                    <th class="px-3 py-4 text-center font-semibold">Id Gaji</th>
                    <th class="px-4 py-4 text-left font-semibold">Nama guru</th>
                    <th class="px-4 py-4 text-left font-semibold">Jabatan</th>
                    <th class="px-3 py-4 text-center font-semibold">Periode</th>
                    <th class="px-3 py-4 text-right font-semibold">Gaji Pokok</th>
                    <th class="px-3 py-4 text-right font-semibold">Tunjangan</th>
                    <th class="px-3 py-4 text-right font-semibold">Gaji Kotor</th>
                    <th class="px-3 py-4 text-right font-semibold">Potongan</th>
                    <th class="px-3 py-4 text-right font-semibold">Gaji Bersih</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($laporan_data)):
                    $no = 1;
                    foreach ($laporan_data as $row): ?>
                        <tr class="bg-white border-b border-gray-200 hover:bg-blue-50 transition-colors duration-200">
                            <td class="px-3 py-4 text-center text-gray-600 font-medium"><?= $no++ ?></td>
                            <td class="px-3 py-4 text-center"><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded text-gray-700"><?= e($row['Id_Gaji']) ?></span></td>
                            <td class="px-4 py-4"><div class="font-semibold text-gray-800 text-sm"><?= e($row['Nama_guru']) ?></div></td>
                            <td class="px-4 py-4 text-gray-600"><?= e($row['Nama_Jabatan']) ?></td>
                            <td class="px-3 py-4 text-center text-gray-600">
                                <span class="text-xs"><?= e($bulan_list[intval($row['Bulan_Penggajian'])] ?? 'N/A') ?></span>
                                <div class="text-xs text-gray-500"><?= date('Y', strtotime($row['Tgl_Gaji'] ?? 'now')) ?></div>
                            </td>
                            <td class="px-3 py-4 text-right"><span class="text-xs font-medium text-gray-700"><?= number_format($row['Gaji_Pokok'], 0, ',', '.') ?></span></td>
                            <td class="px-3 py-4 text-right"><span class="text-xs font-medium text-orange-600"><?= number_format($row['Total_Tunjangan'], 0, ',', '.') ?></span></td>
                            <td class="px-3 py-4 text-right"><span class="text-xs font-semibold text-blue-600"><?= number_format($row['Gaji_Kotor'], 0, ',', '.') ?></span></td>
                            <td class="px-3 py-4 text-right"><span class="text-xs font-medium text-red-600"><?= number_format($row['Total_Potongan'], 0, ',', '.') ?></span></td>
                            <td class="px-3 py-4 text-right"><span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-1 rounded"><?= number_format($row['Gaji_Bersih'], 0, ',', '.') ?></span></td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr><td colspan="10" class="text-center py-16 text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fa-solid fa-folder-open fa-4x mb-4 text-gray-300"></i>
                            <p class="text-lg font-medium text-gray-600">Tidak ada data laporan ditemukan</p>
                            <p class="text-sm text-gray-400 mt-1">Silakan sesuaikan filter pencarian Anda.</p>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function cetakLaporan() {
        const urlParams = new URLSearchParams(window.location.search);
        const bulan = urlParams.get('bulan') || '';
        const tahun = urlParams.get('tahun') || '';
        const id_guru = urlParams.get('id_guru') || '';

        let printUrl = 'laporan_cetak.php?';
        if (bulan) printUrl += 'bulan=' + bulan + '&';
        if (tahun) printUrl += 'tahun=' + tahun + '&';
        if (id_guru) printUrl += 'id_guru=' + id_guru;

        window.open(printUrl, '_blank');
    }

    function cetakPDF() {
        const urlParams = new URLSearchParams(window.location.search);
        const bulan = urlParams.get('bulan') || '';
        const tahun = urlParams.get('tahun') || '';
        const id_guru = urlParams.get('id_guru') || '';

        let pdfUrl = 'cetak_pdf_final.php?';
        if (bulan) pdfUrl += 'bulan=' + bulan + '&';
        if (tahun) pdfUrl += 'tahun=' + tahun + '&';
        if (id_guru) pdfUrl += 'id_guru=' + id_guru;

        window.open(pdfUrl, '_blank');
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
