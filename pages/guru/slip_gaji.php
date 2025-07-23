<?php
$page_title = 'Slip Gaji';
$current_page = 'slip_gaji';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('guru');

$conn = db_connect();
$slip_data = null;
$id_guru_login = '';
$riwayat_gaji = [];
$selected_id = $_GET['id'] ?? null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $stmt_guru = $conn->prepare("SELECT id_guru FROM Guru WHERE id_user = ?");
    $stmt_guru->bind_param("s", $user_id);
    $stmt_guru->execute();
    $guru_data = $stmt_guru->get_result()->fetch_assoc();
    $stmt_guru->close();
    
    if ($guru_data) {
        $id_guru_login = $guru_data['id_guru'];
        // Ambil riwayat gaji
        $stmt_riwayat = $conn->prepare(
            "SELECT p.id_penggajian as id, p.bulan_penggajian, p.gaji_bersih, p.tgl_input
             FROM Penggajian p
             WHERE p.id_guru = ?
             ORDER BY p.tgl_input DESC"
        );
        $stmt_riwayat->bind_param("s", $id_guru_login);
        $stmt_riwayat->execute();
        $riwayat_gaji = $stmt_riwayat->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_riwayat->close();
        // Ambil slip yang dipilih (atau slip terakhir jika tidak ada id di GET)
        if ($selected_id) {
            $stmt_gaji = $conn->prepare(
                "SELECT p.*, g.nama_guru, j.nama_jabatan 
                 FROM Penggajian p 
                 JOIN Guru g ON p.id_guru = g.id_guru 
                 JOIN Jabatan j ON g.id_jabatan = j.id_jabatan
                 WHERE p.id_penggajian = ? AND p.id_guru = ?"
            );
            $stmt_gaji->bind_param("ss", $selected_id, $id_guru_login);
            $stmt_gaji->execute();
            $slip_data = $stmt_gaji->get_result()->fetch_assoc();
            $stmt_gaji->close();
        } else if (!empty($riwayat_gaji)) {
            $first_id = $riwayat_gaji[0]['id'];
            $stmt_gaji = $conn->prepare(
                "SELECT p.*, g.nama_guru, j.nama_jabatan 
                 FROM Penggajian p 
                 JOIN Guru g ON p.id_guru = g.id_guru 
                 JOIN Jabatan j ON g.id_jabatan = j.id_jabatan
                 WHERE p.id_penggajian = ? AND p.id_guru = ?"
            );
            $stmt_gaji->bind_param("ss", $first_id, $id_guru_login);
            $stmt_gaji->execute();
            $slip_data = $stmt_gaji->get_result()->fetch_assoc();
            $stmt_gaji->close();
        }
    }
}
$conn->close();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 no-print">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 font-poppins">Slip Gaji</h2>
            <p class="text-gray-500 text-sm">Rincian pendapatan dan potongan gaji Anda.</p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 mb-8">
        <h3 class="text-lg font-bold text-gray-700 mb-4">Riwayat Gaji</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-center border border-black">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2">Periode</th>
                        <th class="px-4 py-2">Gaji Bersih</th>
                        <th class="px-4 py-2">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($riwayat_gaji)): foreach ($riwayat_gaji as $row): ?>
                    <?php
                        $periode_bulan_en = isset($row['bulan_penggajian']) ? date('F', strtotime($row['bulan_penggajian'].'-01')) : '-';
                        $periode_bulan = $bulan_map[$periode_bulan_en] ?? $periode_bulan_en;
                        $periode_tahun = isset($row['bulan_penggajian']) ? date('Y', strtotime($row['bulan_penggajian'].'-01')) : '-';
                    ?>
                    <tr class="border-b <?= ($selected_id ? $selected_id : $riwayat_gaji[0]['id']) == $row['id'] ? 'bg-green-50 font-bold' : '' ?>">
                        <td class="px-4 py-2"><?= e($periode_bulan . ' ' . $periode_tahun) ?></td>
                        <td class="px-4 py-2">Rp <?= number_format($row['gaji_bersih'], 2, ',', '.') ?></td>
                        <td class="px-4 py-2">
                            <a href="slip_gaji.php?id=<?= e($row['id']) ?>" class="text-blue-600 hover:underline mr-2"><i class="fa fa-eye"></i> Lihat</a>
                            <a href="cetak_slip_gaji_pdf.php?id=<?= e($row['id']) ?>" target="_blank" class="text-red-600 hover:underline"><i class="fa fa-file-pdf"></i> PDF</a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="3" class="py-6 text-gray-500">Belum ada data gaji yang dibayarkan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    $total_tunjangan = 0;
    if ($slip_data) {
        $total_tunjangan =
            ($slip_data['tunjangan_beras'] ?? 0) +
            ($slip_data['tunjangan_kehadiran'] ?? 0) +
            ($slip_data['tunjangan_suami_istri'] ?? 0) +
            ($slip_data['tunjangan_anak'] ?? 0);
    }
    ?>
    <?php if ($slip_data): ?>
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg border border-gray-200">
            <div class="text-center mb-8 pb-6 border-b-2 border-dashed">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 font-poppins">SLIP GAJI GURU</h1>
                <?php
                // Mapping bulan Inggris ke Indonesia
                $bulan_map = [
                    'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April',
                    'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus',
                    'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
                ];
                $periode_bulan_en = isset($slip_data['bulan_penggajian']) ? date('F', strtotime($slip_data['bulan_penggajian'].'-01')) : '-';
                $periode_bulan = $bulan_map[$periode_bulan_en] ?? $periode_bulan_en;
                $periode_tahun = isset($slip_data['bulan_penggajian']) ? date('Y', strtotime($slip_data['bulan_penggajian'].'-01')) : '-';
                $tanggal_input = isset($slip_data['tgl_input']) ? date('d M Y', strtotime($slip_data['tgl_input'])) : '-';
                ?>
                <p class="text-gray-600">Periode: <?= e($periode_bulan . ' ' . $periode_tahun) ?> (Input: <?= e($tanggal_input) ?>)</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 mb-8 text-sm">
                <div class="space-y-2">
                    <div class="flex justify-between"><span class="font-medium text-gray-500">Nama Guru:</span><span class="font-semibold text-gray-800"><?= e($slip_data['nama_guru']) ?></span></div>
                    <div class="flex justify-between"><span class="font-medium text-gray-500">Jabatan:</span><span class="font-semibold text-gray-800"><?= e($slip_data['nama_jabatan']) ?></span></div>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between"><span class="font-medium text-gray-500">ID Guru:</span><span class="font-semibold text-gray-800"><?= e($id_guru_login) ?></span></div>
                    <div class="flex justify-between"><span class="font-medium text-gray-500">Tanggal Pembayaran:</span><span class="font-semibold text-gray-800"><?= e(date('d M Y', strtotime($slip_data['tgl_input']))) ?></span></div>
                </div>
            </div>

            <hr class="my-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                <div>
                    <h3 class="text-lg font-bold text-green-700 mb-3 flex items-center gap-2"><i class="fa-solid fa-arrow-down"></i>PENDAPATAN</h3>
                    <div class="space-y-2 text-sm border-t pt-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Gaji Pokok</span>
                            <span class="font-semibold text-gray-800">Rp <?= number_format($slip_data['gaji_pokok'], 2, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tunjangan</span>
                            <span class="font-semibold text-gray-800">Rp <?= number_format($total_tunjangan, 2, ',', '.') ?></span>
                        </div>
                        <div class="ml-4 mt-1 text-xs text-gray-500">
                            <ul class="list-disc list-inside">
                                <li>Tunjangan Beras: Rp <?= number_format($slip_data['tunjangan_beras'] ?? 0, 2, ',', '.') ?></li>
                                <li>Tunjangan Kehadiran: Rp <?= number_format($slip_data['tunjangan_kehadiran'] ?? 0, 2, ',', '.') ?></li>
                                <li>Tunjangan Suami/Istri: Rp <?= number_format($slip_data['tunjangan_suami_istri'] ?? 0, 2, ',', '.') ?></li>
                                <li>Tunjangan Anak: Rp <?= number_format($slip_data['tunjangan_anak'] ?? 0, 2, ',', '.') ?></li>
                            </ul>
                        </div>
                    </div>
                    <div class="flex justify-between mt-3 pt-3 border-t-2 font-bold">
                        <span>Total Pendapatan (Gaji Kotor)</span>
                        <span>Rp <?= number_format($slip_data['gaji_pokok'] + $total_tunjangan, 2, ',', '.') ?></span>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-red-700 mb-3 flex items-center gap-2"><i class="fa-solid fa-arrow-up"></i>POTONGAN</h3>
                    <div class="space-y-2 text-sm border-t pt-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Potongan</span>
                            <span class="font-semibold text-red-600">- Rp <?= number_format($slip_data['total_potongan'], 2, ',', '.') ?></span>
                        </div>
                        <div class="ml-4 mt-1 text-xs text-gray-500">
                            <ul class="list-disc list-inside">
                                <li>BPJS: Rp <?= number_format($slip_data['potongan_bpjs'] ?? 0, 2, ',', '.') ?></li>
                                <li>Infak: Rp <?= number_format($slip_data['infak'] ?? 0, 2, ',', '.') ?></li>
                            </ul>
                        </div>
                    </div>
                    <div class="flex justify-between mt-3 pt-3 border-t-2 font-bold">
                        <span>Total Potongan</span>
                        <span class="text-red-600">- Rp <?= number_format($slip_data['total_potongan'], 2, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <div class="mt-10 bg-green-50 p-4 rounded-lg text-center sm:text-right">
                <p class="text-sm font-semibold text-gray-600">GAJI BERSIH (TAKE HOME PAY)</p>
                <p class="text-3xl font-bold text-green-800">Rp <?= number_format($slip_data['gaji_bersih'], 2, ',', '.') ?></p>
            </div>

        </div>
    <?php else: ?>
        <div class="bg-white p-10 rounded-xl shadow-lg text-center border border-gray-200">
            <i class="fa-solid fa-folder-open text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-xl font-bold text-gray-700">Belum Ada Data</h3>
            <p class="text-gray-500 mt-2">Slip gaji Anda akan tersedia di sini setelah proses penggajian selesai dan disetujui/dibayarkan.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>