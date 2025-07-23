<?php
$page_title = 'Laporan Gaji';
$current_page = 'laporan'; // Berguna untuk menandai menu aktif di sidebar
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('kepala_sekolah');

$conn = db_connect();

// Ambil data untuk filter
$jabatan_list = $conn->query("SELECT id_jabatan, nama_jabatan FROM Jabatan ORDER BY nama_jabatan ASC")->fetch_all(MYSQLI_ASSOC);

// Ambil filter dari GET request
$filter_bulan = $_GET['bulan'] ?? '';
$filter_tahun = $_GET['tahun'] ?? date('Y'); // Default tahun ini
$filter_jabatan = $_GET['jabatan'] ?? '';

// Bangun query dinamis berdasarkan filter
$sql = "
    SELECT 
        p.id_penggajian as Id_Gaji,
        g.nama_guru as Nama_Karyawan, 
        j.nama_jabatan as Nama_Jabatan, 
        p.tgl_input as Tgl_Gaji, 
        (p.tunjangan_beras + p.tunjangan_kehadiran + p.tunjangan_suami_istri + p.tunjangan_anak) as Total_Tunjangan,
        0 as Total_Lembur,
        p.gaji_pokok as Gaji_Pokok,
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
if (!empty($filter_jabatan)) {
    $sql .= " AND j.id_jabatan = ?";
    $params[] = $filter_jabatan;
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
$stmt->close();
$conn->close();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 font-poppins">Laporan Penggajian</h2>
            <p class="text-gray-500 text-sm">Lihat, filter, dan cetak laporan gaji yang telah disetujui.</p>
        </div>
        <div class="flex space-x-2 no-print">
            <button onclick="window.print()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                <i class="fa-solid fa-print"></i>Cetak
            </button>
            <button onclick="cetakPDF()" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                <i class="fa-solid fa-file-pdf"></i>Unduh PDF
            </button>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-lg no-print">
        <h3 class="text-lg font-bold text-gray-700 mb-4">Filter Laporan</h3>
        <form action="laporan.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="bulan" class="block text-sm font-medium text-gray-600 mb-1">Bulan</label>
                <select name="bulan" id="bulan" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                    <option value="">-- Semua Bulan --</option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $filter_bulan == $i ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $i, 10)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label for="tahun" class="block text-sm font-medium text-gray-600 mb-1">Tahun</label>
                <input type="number" name="tahun" id="tahun" placeholder="Cth: <?= date('Y') ?>" value="<?= e($filter_tahun) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label for="jabatan" class="block text-sm font-medium text-gray-600 mb-1">Jabatan</label>
                <select name="jabatan" id="jabatan" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                    <option value="">-- Semua Jabatan --</option>
                    <?php foreach ($jabatan_list as $jabatan): ?>
                        <option value="<?= e($jabatan['id_jabatan']) ?>" <?= $filter_jabatan == $jabatan['id_jabatan'] ? 'selected' : '' ?>><?= e($jabatan['nama_jabatan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-end space-x-2">
                <button type="submit" class="w-full bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 text-sm font-semibold transition-colors">Tampilkan</button>
                <a href="laporan.php" class="w-full text-center bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300 text-sm font-semibold transition-colors">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="text-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">
                LAPORAN GAJI 
                <?php 
                    if ($filter_jabatan) {
                        $nama_jabatan_terfilter = '';
                        foreach ($jabatan_list as $j) { if ($j['id_jabatan'] === $filter_jabatan) $nama_jabatan_terfilter = $j['nama_jabatan']; }
                        echo "PER JABATAN: " . strtoupper(e($nama_jabatan_terfilter));
                    } else {
                        echo "BULANAN";
                    }
                ?>
            </h2>
            <p class="text-gray-600">
                <?php
                if ($filter_bulan && $filter_tahun) echo "Periode: " . date('F', mktime(0, 0, 0, $filter_bulan, 10)) . " " . $filter_tahun;
                elseif ($filter_tahun) echo "Periode: Tahun " . $filter_tahun;
                ?>
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Id Gaji</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Nama Karyawan</th>
                        <th class="px-4 py-3">Jabatan</th>
                        <th class="px-4 py-3 text-right">Gaji Pokok</th>
                        <th class="px-4 py-3 text-right">Tunjangan</th>
                        <th class="px-4 py-3 text-right">Lembur</th>
                        <th class="px-4 py-3 text-right">Potongan</th>
                        <th class="px-4 py-3 text-right">Gaji Bersih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $total_gaji_pokok = 0;
                    $total_semua_tunjangan = 0;
                    $total_semua_lembur = 0;
                    $total_semua_potongan = 0;
                    $total_gaji_bersih = 0;

                    if (!empty($laporan_data)):
                        foreach ($laporan_data as $row):
                            $total_gaji_pokok += $row['Gaji_Pokok'];
                            $total_semua_tunjangan += $row['Total_Tunjangan'];
                            $total_semua_lembur += $row['Total_Lembur'];
                            $total_semua_potongan += $row['Total_Potongan'];
                            $total_gaji_bersih += $row['Gaji_Bersih'];
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-2 text-center"><?= $no++ ?></td>
                        <td class="px-4 py-2 font-mono text-xs"><?= e($row['Id_Gaji']) ?></td>
                        <td class="px-4 py-2"><?= e(date('d M Y', strtotime($row['Tgl_Gaji']))) ?></td>
                        <td class="px-4 py-2 font-medium text-gray-900"><?= e($row['Nama_Karyawan']) ?></td>
                        <td class="px-4 py-2"><?= e($row['Nama_Jabatan']) ?></td>
                        <td class="px-4 py-2 text-right">Rp <?= number_format($row['Gaji_Pokok'], 0, ',', '.') ?></td>
                        <td class="px-4 py-2 text-right">Rp <?= number_format($row['Total_Tunjangan'], 0, ',', '.') ?></td>
                        <td class="px-4 py-2 text-right">Rp <?= number_format($row['Total_Lembur'], 0, ',', '.') ?></td>
                        <td class="px-4 py-2 text-right text-red-600">- Rp <?= number_format($row['Total_Potongan'], 0, ',', '.') ?></td>
                        <td class="px-4 py-2 text-right font-bold text-green-700">Rp <?= number_format($row['Gaji_Bersih'], 0, ',', '.') ?></td>
                    </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="10" class="text-center py-10 text-gray-500">
                                <i class="fa-solid fa-folder-open text-3xl text-gray-400 mb-2"></i>
                                <p>Tidak ada data laporan yang cocok dengan kriteria filter Anda.</p>
                            </td>
                        </tr>
                    <?php 
                    endif;
                    ?>
                </tbody>
                <?php if (!empty($laporan_data)): ?>
                <tfoot class="font-bold bg-gray-50">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right">Total Keseluruhan:</td>
                        <td class="px-4 py-3 text-right">Rp <?= number_format($total_gaji_pokok, 0, ',', '.') ?></td>
                        <td class="px-4 py-3 text-right">Rp <?= number_format($total_semua_tunjangan, 0, ',', '.') ?></td>
                        <td class="px-4 py-3 text-right">Rp <?= number_format($total_semua_lembur, 0, ',', '.') ?></td>
                        <td class="px-4 py-3 text-right text-red-600">- Rp <?= number_format($total_semua_potongan, 0, ',', '.') ?></td>
                        <td class="px-4 py-3 text-right text-green-700">Rp <?= number_format($total_gaji_bersih, 0, ',', '.') ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
function cetakPDF() {
    // Ambil parameter filter saat ini
    const urlParams = new URLSearchParams(window.location.search);
    const bulan = urlParams.get('bulan') || '';
    const tahun = urlParams.get('tahun') || '';
    const jabatan = urlParams.get('jabatan') || '';
    
    // Buat URL untuk cetak PDF dengan parameter yang sama
    let pdfUrl = 'cetak_pdf_final.php?';
    if (bulan) pdfUrl += 'bulan=' + bulan + '&';
    if (tahun) pdfUrl += 'tahun=' + tahun + '&';
    if (jabatan) pdfUrl += 'jabatan=' + jabatan;
    
    // Buka di tab baru
    window.open(pdfUrl, '_blank');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>