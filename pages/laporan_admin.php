<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');

$conn = db_connect();
$page_title = 'Laporan Gaji Guru';

// Ambil data untuk filter
$karyawan_list = $conn->query("SELECT id_guru, nama_guru FROM Guru ORDER BY nama_guru ASC")->fetch_all(MYSQLI_ASSOC);
$bulan_list = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 font-poppins">Laporan Gaji Guru</h2>
            <p class="text-gray-500 text-sm">Lihat dan cetak laporan penggajian guru.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="printReport()" class="bg-blue-600 text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center">
                <i class="fa-solid fa-print mr-2"></i>Cetak Laporan
            </button>
        </div>
    </div>

        <?php display_flash_message(); ?>

        <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 items-end">
            <input type="hidden" name="action" value="list">
            <div>
                <label for="filter_karyawan" class="text-sm font-medium text-gray-600">Nama Karyawan</label>
                <select name="karyawan" id="filter_karyawan" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                    <option value="">Semua Karyawan</option>
                    <?php foreach ($karyawan_list as $k): ?>
                        <option value="<?= e($k['id_guru']) ?>" <?= ($_GET['karyawan'] ?? '') == $k['id_guru'] ? 'selected' : '' ?>><?= e($k['nama_guru']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter_bulan" class="text-sm font-medium text-gray-600">Bulan</label>
                <select name="bulan" id="filter_bulan" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                    <option value="">Semua Bulan</option>
                    <?php foreach ($bulan_list as $num => $name): ?>
                        <option value="<?= $num ?>" <?= ($_GET['bulan'] ?? '') == $num ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter_tahun" class="text-sm font-medium text-gray-600">Tahun</label>
                <input type="number" name="tahun" id="filter_tahun" value="<?= e($_GET['tahun'] ?? date('Y')) ?>" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-semibold">Tampilkan</button>
                <a href="laporan_admin.php" class="w-full text-center bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 font-semibold">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 font-poppins">
                    <tr>
                        <th class="px-4 py-4 text-center font-semibold">No</th>
                        <th class="px-4 py-4 text-center font-semibold">No Slip</th>
                        <th class="px-4 py-4 text-left font-semibold">Nama Guru</th>
                        <th class="px-4 py-4 text-center font-semibold">Periode</th>
                        <th class="px-4 py-4 text-center font-semibold">Gaji Bersih</th>
                        <th class="px-4 py-4 text-center font-semibold">Status</th>
                        <th class="px-4 py-4 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT p.id_penggajian, p.no_slip_gaji, g.nama_guru, p.tgl_input, p.bulan_penggajian, p.status_validasi, p.gaji_bersih 
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
                        $params[] = str_pad($_GET['bulan'], 2, '0', STR_PAD_LEFT);
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
                                <td class="px-4 py-4 text-center text-gray-600 font-medium"><?= $no++ ?></td>
                                <td class="px-4 py-4 text-center">
                                    <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded text-gray-700">
                                        <?= e($row['no_slip_gaji'] ?? 'SG' . date('ym') . str_pad($no-1, 4, '0', STR_PAD_LEFT)) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-gray-800"><?= e($row['nama_guru']) ?></div>
                                </td>
                                <td class="px-4 py-4 text-center text-gray-600">
                                    <span class="text-sm"><?= date('M Y', strtotime($row['tgl_input'])) ?></span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-sm font-bold text-green-600 bg-green-50 px-2 py-1 rounded">
                                        Rp <?= number_format($row['gaji_bersih'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <?php if (($row['status_validasi'] ?? 'Belum Valid') === 'Valid'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fa-solid fa-check-circle mr-1"></i> Valid
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fa-solid fa-clock mr-1"></i> Belum Valid
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <button onclick="printSlipGaji('<?= e($row['id_penggajian']) ?>')" 
                                            class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-md text-xs font-medium transition-colors duration-200 shadow-sm hover:shadow-md" 
                                            title="Cetak Slip Gaji">
                                        <i class="fa-solid fa-print mr-1"></i>Cetak
                                    </button>
                                </td>
                            </tr>
                    <?php endwhile;
                    else:
                        echo '<tr><td colspan="7" class="text-center py-16 text-gray-500">';
                        echo '<div class="flex flex-col items-center justify-center">';
                        echo '<i class="fa-solid fa-folder-open fa-4x mb-4 text-gray-300"></i>';
                        echo '<p class="text-lg font-medium text-gray-600">Tidak ada data gaji yang ditemukan</p>';
                        echo '<p class="text-sm text-gray-400 mt-1">Silakan tambah data gaji baru atau sesuaikan filter pencarian</p>';
                        echo '</div></td></tr>';
                    endif;
                    $stmt->close();
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
