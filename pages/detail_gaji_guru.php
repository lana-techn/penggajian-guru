<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireAnyRole(['admin', 'kepala_sekolah']);


$conn = db_connect();
$id_gaji = $_GET['id'] ?? null;

if (!$id_gaji) {
    set_flash_message('error', 'ID Gaji tidak ditemukan.');
    header('Location: pengajuan_gaji_guru.php');
    exit;
}

// 1. Ambil data ringkasan dari tabel gaji dan data guru
$stmt_gaji = $conn->prepare(
    "SELECT g.*, k.nama_lengkap, j.nama_jabatan, k.tanggal_masuk 
     FROM gaji g 
     JOIN guru k ON g.guru_id = k.id 
     JOIN jabatan j ON k.jabatan_id = j.id 
     WHERE g.id = ?"
);
$stmt_gaji->bind_param("i", $id_gaji);
$stmt_gaji->execute();
$gaji_data = $stmt_gaji->get_result()->fetch_assoc();
$stmt_gaji->close();

if (!$gaji_data) {
    set_flash_message('error', 'Data gaji dengan ID tersebut tidak ditemukan.');
    header('Location: pengajuan_gaji.php');
    exit;
}

// 2. Ambil data rincian dari tabel gaji_detail
$stmt_detail = $conn->prepare("SELECT * FROM gaji_detail WHERE gaji_id = ?");
$stmt_detail->bind_param("i", $id_gaji);
$stmt_detail->execute();
$detail_data_result = $stmt_detail->get_result();
$detail_data = [];
while ($row = $detail_data_result->fetch_assoc()) {
    $detail_data[$row['komponen_jenis']][] = $row;
}
$stmt_detail->close();

// 3. Ambil data dari tabel absensi untuk rincian kehadiran dan potongan
$bulan_gaji = date('m', strtotime($gaji_data['periode_gaji']));
$tahun_gaji = date('Y', strtotime($gaji_data['periode_gaji']));

$stmt_presensi = $conn->prepare("SELECT COUNT(*) as hadir, (SELECT COUNT(*) FROM absensi WHERE guru_id = ? AND status = 'sakit' AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?) as sakit, (SELECT COUNT(*) FROM absensi WHERE guru_id = ? AND status = 'izin' AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?) as izin, (SELECT COUNT(*) FROM absensi WHERE guru_id = ? AND status = 'alpha' AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?) as alpha FROM absensi WHERE guru_id = ? AND status = 'hadir' AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
$stmt_presensi->bind_param("iiiiiiiiiiii", $gaji_data['guru_id'], $bulan_gaji, $tahun_gaji, $gaji_data['guru_id'], $bulan_gaji, $tahun_gaji, $gaji_data['guru_id'], $bulan_gaji, $tahun_gaji, $gaji_data['guru_id'], $bulan_gaji, $tahun_gaji);
$stmt_presensi->execute();
$presensi_data = $stmt_presensi->get_result()->fetch_assoc();
$stmt_presensi->close();
$conn->close();


// 4. Hitung ulang komponen untuk ditampilkan di detail
$gaji_pokok = $gaji_data['gaji_pokok'] ?? 0;
$jam_lembur = 0; // Tidak ada data lembur di skema baru

// Rincian Potongan
$detail_potongan_display = [];
if (isset($detail_data['potongan'])) {
    foreach ($detail_data['potongan'] as $p) {
        $detail_potongan_display[] = ['nama' => $p['komponen_nama'], 'jumlah' => $p['jumlah']];
    }
}

// Hitung masa kerja untuk ditampilkan
$tgl_awal_kerja = new DateTime($gaji_data['tanggal_masuk']);
$tgl_gaji = new DateTime($gaji_data['periode_gaji']);
$masa_kerja_text = $tgl_gaji->diff($tgl_awal_kerja)->format('%y tahun, %m bulan');

$page_title = 'Detail Gaji: ' . e($gaji_data['Nama_Karyawan']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-white p-8 rounded-xl shadow-lg max-w-3xl mx-auto border border-gray-200">
    <div class="text-center mb-8">
        <h2 class="text-3xl font-bold font-poppins text-gray-800">DETAIL GAJI</h2>
        <p class="text-gray-500 mt-1">Rincian perhitungan gaji untuk periode <?= date('F Y', strtotime($gaji_data['Tgl_Gaji'])) ?></p>
    </div>

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 border border-gray-200 rounded-lg p-4">
            <div class="flex justify-between border-b pb-2"><span class="text-sm font-medium text-gray-500">Nama Karyawan</span><span class="text-sm font-semibold text-gray-800"><?= e($gaji_data['Nama_Karyawan']) ?></span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-sm font-medium text-gray-500">Jabatan</span><span class="text-sm font-semibold text-gray-800"><?= e($gaji_data['Nama_Jabatan']) ?></span></div>
            <div class="flex justify-between"><span class="text-sm font-medium text-gray-500">Tanggal Gaji</span><span class="text-sm font-semibold text-gray-800"><?= date('d F Y', strtotime($gaji_data['Tgl_Gaji'])) ?></span></div>
            <div class="flex justify-between"><span class="text-sm font-medium text-gray-500">Masa Kerja</span><span class="text-sm font-semibold text-gray-800"><?= e($masa_kerja_text) ?></span></div>
        </div>

        <div class="space-y-4">
            <div>
                <h3 class="font-bold text-lg text-green-700 mb-2">PENDAPATAN</h3>
                <div class="border rounded-md">
                    <div class="flex justify-between py-2.5 px-4 border-b"><span class="text-sm">Gaji Pokok</span><span class="font-semibold">Rp <?= number_format($gaji_pokok, 2, ',', '.') ?></span></div>
                    <div class="flex justify-between py-2.5 px-4 border-b"><span class="text-sm">Tunjangan</span><span class="font-semibold">Rp <?= number_format($gaji_data['Total_Tunjangan'], 2, ',', '.') ?></span></div>
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Lembur (<?= e($jam_lembur) ?> jam)</span><span class="font-semibold">Rp <?= number_format($gaji_data['Total_Lembur'], 2, ',', '.') ?></span></div>
                </div>
                <div class="flex justify-between py-2.5 px-4 bg-gray-100 rounded-b-md font-bold"><span>Total Pendapatan (Gaji Kotor)</span><span>Rp <?= number_format($gaji_data['Gaji_Kotor'], 2, ',', '.') ?></span></div>
            </div>

            <div>
                <h3 class="font-bold text-lg text-red-700 mb-2">POTONGAN</h3>
                <div class="border rounded-md">
                    <?php if (!empty($detail_potongan_display)): ?>
                        <?php foreach ($detail_potongan_display as $p): ?>
                            <div class="flex justify-between py-2.5 px-4 border-b"><span class="text-sm"><?= e($p['nama']) ?></span><span class="text-sm font-semibold text-red-600">- Rp <?= number_format($p['jumlah'], 2, ',', '.') ?></span></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="flex justify-between py-2.5 px-4"><span class="text-sm text-gray-500">Tidak ada potongan</span><span class="font-semibold text-red-600">- Rp 0</span></div>
                    <?php endif; ?>
                </div>
                <div class="flex justify-between py-2.5 px-4 bg-gray-100 rounded-b-md font-bold"><span class="text-red-600">Total Potongan</span><span class="text-red-600">- Rp <?= number_format($gaji_data['Total_Potongan'], 2, ',', '.') ?></span></div>
            </div>

            <div>
                <h3 class="font-bold text-lg text-blue-700 mb-2">RINCIAN KEHADIRAN</h3>
                <div class="border rounded-md divide-y">
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Hadir</span><span class="font-semibold"><?= e($presensi_data['Hadir'] ?? 0) ?> hari</span></div>
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Sakit</span><span class="font-semibold"><?= e($presensi_data['Sakit'] ?? 0) ?> hari</span></div>
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Izin</span><span class="font-semibold"><?= e($presensi_data['Izin'] ?? 0) ?> hari</span></div>
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Alpha</span><span class="font-semibold"><?= e($presensi_data['Alpha'] ?? 0) ?> hari</span></div>
                </div>
            </div>
        </div>

        <div class="bg-green-100 border-l-4 border-green-500 text-green-800 p-4 rounded-lg flex justify-between items-center mt-6">
            <span class="text-lg font-bold font-poppins">GAJI BERSIH (TAKE HOME PAY)</span>
            <span class="text-xl font-bold">Rp <?= number_format($gaji_data['Gaji_Bersih'], 2, ',', '.') ?></span>
        </div>

        <div class="flex items-center justify-end pt-6">
            <a href="pengajuan_gaji.php" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Kembali ke Daftar Gaji</a>
        </div>
    </div>
</div>