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

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50">
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 no-print mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-800 font-poppins flex items-center gap-3">
                    <i class="fa-solid fa-receipt text-green-600"></i>
                    Slip Gaji
                </h2>
                <p class="text-gray-600 text-sm mt-2">Rincian pendapatan dan potongan gaji Anda.</p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 mb-8">
            <div class="flex items-center gap-3 mb-6">
                <i class="fa-solid fa-history text-blue-600 text-xl"></i>
                <h3 class="text-xl font-bold text-gray-800">Riwayat Gaji Anda</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left font-semibold rounded-tl-lg">Periode</th>
                            <th class="px-6 py-4 text-center font-semibold">Gaji Bersih</th>
                            <th class="px-6 py-4 text-center font-semibold rounded-tr-lg">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (!empty($riwayat_gaji)): foreach ($riwayat_gaji as $index => $row): ?>
                                <?php
                                $bulan_map = [
                                    'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April',
                                    'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus',
                                    'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
                                ];
                                // Perbaiki perhitungan periode untuk riwayat
                                $periode_bulan_en = '-';
                                $periode_bulan = '-';
                                $periode_tahun = '-';
                                
                                if (isset($row['bulan_penggajian']) && !empty($row['bulan_penggajian'])) {
                                    // Coba format YYYY-MM
                                    if (preg_match('/^\d{4}-\d{2}$/', $row['bulan_penggajian'])) {
                                        $periode_bulan_en = date('F', strtotime($row['bulan_penggajian'] . '-01'));
                                        $periode_bulan = $bulan_map[$periode_bulan_en] ?? $periode_bulan_en;
                                        $periode_tahun = date('Y', strtotime($row['bulan_penggajian'] . '-01'));
                                    } else {
                                        // Fallback ke tgl_input
                                        $periode_bulan_en = date('F', strtotime($row['tgl_input']));
                                        $periode_bulan = $bulan_map[$periode_bulan_en] ?? $periode_bulan_en;
                                        $periode_tahun = date('Y', strtotime($row['tgl_input']));
                                    }
                                }
                                $is_selected = ($selected_id ? $selected_id : $riwayat_gaji[0]['id']) == $row['id'];
                                ?>
                                <tr class="hover:bg-blue-50 transition-colors duration-200 <?= $is_selected ? 'bg-green-100 border-l-4 border-green-500' : '' ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-2 h-2 bg-blue-500 rounded-full <?= $is_selected ? 'bg-green-500' : '' ?>"></div>
                                            <div>
                                                <div class="font-semibold text-gray-800"><?= e($periode_bulan . ' ' . $periode_tahun) ?></div>
                                                <div class="text-xs text-gray-500"><?= date('d M Y', strtotime($row['tgl_input'])) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-lg font-bold text-green-600">Rp <?= number_format($row['gaji_bersih'], 0, ',', '.') ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="slip_gaji.php?id=<?= e($row['id']) ?>" 
                                               class="inline-flex items-center gap-1 px-3 py-2 bg-blue-500 text-white text-xs font-semibold rounded-lg hover:bg-blue-600 transition-colors duration-200">
                                                <i class="fa fa-eye"></i> Lihat
                                            </a>
                                            <a href="cetak_slip_gaji_pdf.php?id=<?= e($row['id']) ?>" target="_blank" 
                                               class="inline-flex items-center gap-1 px-3 py-2 bg-red-500 text-white text-xs font-semibold rounded-lg hover:bg-red-600 transition-colors duration-200">
                                                <i class="fa fa-file-pdf"></i> PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else: ?>
                            <tr>
                                <td colspan="3" class="py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fa-solid fa-inbox text-6xl text-gray-300 mb-4"></i>
                                        <h4 class="text-lg font-semibold text-gray-600 mb-2">Belum Ada Riwayat Gaji</h4>
                                        <p class="text-gray-500">Riwayat gaji Anda akan muncul di sini setelah proses penggajian.</p>
                                    </div>
                                </td>
                            </tr>
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
        <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg border-t-8 border-green-600">
            <!-- Header Slip Gaji -->
            <div class="flex flex-col sm:flex-row justify-between items-start pb-6 border-b-2 border-gray-200 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-20 h-20 bg-green-100 flex items-center justify-center rounded-full">
                        <i class="fas fa-school text-4xl text-green-600"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 font-poppins">SLIP GAJI</h1>
                        <p class="text-gray-600">SD Unggulan Muhammadiyah Kretek</p>
                    </div>
                </div>
                <div class="text-left sm:text-right mt-4 sm:mt-0">
                    <p class="font-semibold text-gray-500">ID Penggajian: <span class="font-mono text-green-700"><?= e($slip_data['id_penggajian'] ?? 'N/A') ?></span></p>
                    <p class="font-semibold text-gray-500">Periode: <span class="text-green-700"><?= e($periode_bulan . ' ' . $periode_tahun) ?></span></p>
                </div>
            </div>

            <!-- Detail guru -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 mb-8 text-sm">
                <div class="space-y-3 p-4 bg-gray-50 rounded-lg">
                    <div class="flex justify-between"><span class="font-medium text-gray-500">Nama Guru:</span><span class="font-bold text-gray-800"><?= e($slip_data['nama_guru']) ?></span></div>
                    <div class="flex justify-between"><span class="font-medium text-gray-500">ID Guru:</span><span class="font-semibold text-gray-800"><?= e($id_guru_login) ?></span></div>
                </div>
                <div class="space-y-3 p-4 bg-gray-50 rounded-lg">
                    <div class="flex justify-between"><span class="font-medium text-gray-500">Jabatan:</span><span class="font-semibold text-gray-800"><?= e($slip_data['nama_jabatan']) ?></span></div>
                    <div class="flex justify-between"><span class="font-medium text-gray-500">Tanggal Bayar:</span><span class="font-semibold text-gray-800"><?= e(date('d F Y', strtotime($slip_data['tgl_input']))) ?></span></div>
                </div>
            </div>

            <!-- Rincian Gaji -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10">
                <!-- Kolom Pendapatan -->
                <div>
                    <h3 class="text-lg font-bold text-green-700 mb-3 pb-2 border-b border-green-200 flex items-center gap-2"><i class="fas fa-plus-circle"></i>PENDAPATAN</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Gaji Pokok</span>
                            <span class="font-semibold text-gray-800">Rp <?= number_format($slip_data['gaji_pokok'], 0, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Tunjangan Kehadiran</span>
                            <span class="font-semibold text-gray-800">Rp <?= number_format($slip_data['tunjangan_kehadiran'] ?? 0, 0, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Tunjangan Suami/Istri</span>
                            <span class="font-semibold text-gray-800">Rp <?= number_format($slip_data['tunjangan_suami_istri'] ?? 0, 0, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Tunjangan Anak</span>
                            <span class="font-semibold text-gray-800">Rp <?= number_format($slip_data['tunjangan_anak'] ?? 0, 0, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600">Tunjangan Beras</span>
                            <span class="font-semibold text-gray-800">Rp <?= number_format($slip_data['tunjangan_beras'] ?? 0, 0, ',', '.') ?></span>
                        </div>
                    </div>
                    <div class="flex justify-between mt-3 pt-3 border-t-2 border-gray-300 font-bold text-base">
                        <span>Total Pendapatan</span>
                        <span class="text-green-600">Rp <?= number_format($slip_data['gaji_kotor'], 0, ',', '.') ?></span>
                    </div>
                </div>

                <!-- Kolom Potongan -->
                <div>
                    <h3 class="text-lg font-bold text-red-700 mb-3 pb-2 border-b border-red-200 flex items-center gap-2"><i class="fas fa-minus-circle"></i>POTONGAN</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Potongan BPJS</span>
                            <span class="font-semibold text-red-600">- Rp <?= number_format($slip_data['potongan_bpjs'] ?? 0, 0, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600">Infak</span>
                            <span class="font-semibold text-red-600">- Rp <?= number_format($slip_data['infak'] ?? 0, 0, ',', '.') ?></span>
                        </div>
                    </div>
                    <div class="flex justify-between mt-3 pt-3 border-t-2 border-gray-300 font-bold text-base">
                        <span>Total Potongan</span>
                        <span class="text-red-600">- Rp <?= number_format($slip_data['total_potongan'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <!-- Total Gaji Bersih -->
            <div class="mt-10 bg-gradient-to-r from-green-600 to-teal-600 text-white p-6 rounded-lg flex justify-between items-center">
                <p class="text-lg font-bold uppercase">Gaji Bersih (Take Home Pay)</p>
                <p class="text-3xl font-extrabold">Rp <?= number_format($slip_data['gaji_bersih'], 0, ',', '.') ?></p>
            </div>

            <!-- Catatan -->
            <div class="mt-6 text-xs text-gray-500 text-center">
                <p>Ini adalah slip gaji yang dibuat secara otomatis oleh sistem. Jika ada pertanyaan, silakan hubungi bagian administrasi.</p>
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
</div>
