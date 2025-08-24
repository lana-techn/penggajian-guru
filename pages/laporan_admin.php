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
$guru_list = $conn->query("SELECT id_guru, nama_guru FROM Guru ORDER BY nama_guru ASC")->fetch_all(MYSQLI_ASSOC);
$bulan_opsi = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', 
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', 
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];



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
                <label for="filter_guru" class="text-xs font-medium text-gray-500">guru</label>
                <select name="guru" id="filter_guru" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-green-500">
                    <option value="">Semua</option>
                    <?php foreach ($guru_list as $k): ?>
                        <option value="<?= e($k['id_guru']) ?>" <?= ($_GET['guru'] ?? '') == $k['id_guru'] ? 'selected' : '' ?>><?= e($k['nama_guru']) ?></option>
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

        <div class="overflow-x-auto" style="max-width: 100%;">
            <table class="w-full text-sm text-left text-gray-700" style="min-width: 1200px;">
                <thead class="text-xs uppercase bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 font-poppins">
                    <tr>
                        <th class="px-3 py-4 text-center font-semibold">No</th>
                        <th class="px-3 py-4 text-center font-semibold">ID Penggajian</th>
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
                        <th class="px-3 py-4 text-center font-semibold">Pot. Terlambat</th>
                        <th class="px-3 py-4 text-center font-semibold">Total Potongan</th>
                        <th class="px-3 py-4 text-center font-semibold">Gaji Bersih</th>
                        <th class="px-4 py-4 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT p.id_penggajian, g.nama_guru, g.status_kawin, g.jml_anak, p.tgl_input, p.bulan_penggajian, 
                                   p.gaji_pokok, p.gaji_kotor, p.potongan_bpjs, p.infak, p.potongan_terlambat, p.total_potongan, p.gaji_bersih,
                                   t.tunjangan_beras, t.tunjangan_kehadiran, t.tunjangan_suami_istri, t.tunjangan_anak
                            FROM Penggajian p 
                            JOIN Guru g ON p.id_guru = g.id_guru
                            LEFT JOIN Tunjangan t ON g.id_tunjangan = t.id_tunjangan WHERE 1=1";
                    $params = [];
                    $types = '';
                    if (!empty($_GET['guru'])) {
                        $sql .= " AND p.id_guru = ?";
                        $params[] = $_GET['guru'];
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
                            // Calculate tunjangan values based on guru data
                            $tunjangan_suami_istri_calculated = 0;
                            if (in_array($row['status_kawin'], ['Kawin', 'Menikah', 'menikah'])) {
                                $tunjangan_suami_istri_calculated = (float)($row['tunjangan_suami_istri'] ?? 0);
                            }

                            $tunjangan_anak_calculated = 0;
                            $jml_anak = min((int)($row['jml_anak'] ?? 0), 2);
                            if ($jml_anak > 0) {
                                $tunjangan_anak_calculated = $jml_anak * (float)($row['tunjangan_anak'] ?? 0);
                            }
                        ?>
                            <tr class="bg-white border-b border-gray-200 hover:bg-blue-50 transition-colors duration-200">
                                <td class="px-3 py-4 text-center text-gray-600 font-medium"><?= $no++ ?></td>
                                <td class="px-3 py-4 text-center">
                                    <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded text-gray-700">
                                        <?= e($row['id_penggajian']) ?>
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
                                        <?= number_format($tunjangan_suami_istri_calculated, 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-center">
                                    <span class="text-xs font-medium text-teal-600">
                                        <?= number_format($tunjangan_anak_calculated, 0, ',', '.') ?>
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
                                        <?= number_format($row['potongan_terlambat'] ?? 0, 0, ',', '.') ?>
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
                        echo '<tr><td colspan="16" class="text-center py-16 text-gray-500">';
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
    const filterguru = document.getElementById('filter_guru')?.value;
    const filterBulan = document.getElementById('filter_bulan')?.value;
    const filterTahun = document.getElementById('filter_tahun')?.value;
    
    if (filterguru) params.append('guru', filterguru);
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
    const filterguru = document.getElementById('filter_guru')?.value;
    const filterBulan = document.getElementById('filter_bulan')?.value;
    const filterTahun = document.getElementById('filter_tahun')?.value;
    
    if (filterguru) params.append('guru', filterguru);
    if (filterBulan) params.append('bulan', filterBulan);
    if (filterTahun) params.append('tahun', filterTahun);
    
    // Buka halaman cetak PDF laporan di tab baru
    window.open(`../reports/cetak_pdf_laporan_gaji.php?${params.toString()}`, '_blank');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
