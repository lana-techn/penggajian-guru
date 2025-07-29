<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireAnyRole(['admin', 'kepala_sekolah']);


$conn = db_connect();
$id_gaji = $_GET['id'] ?? null;

if (!$id_gaji) {
    set_flash_message('error', 'ID Gaji tidak ditemukan.');
    header('Location: detail_gaji_guru.php');
    exit;
}

// 1. Ambil data ringkasan dari tabel gaji dan data guru
$stmt_gaji = $conn->prepare(
    "SELECT p.*, g.nama_guru, g.nipm, j.nama_jabatan, g.tgl_masuk
     FROM Penggajian p
     JOIN Guru g ON p.id_guru = g.id_guru
     JOIN Jabatan j ON g.id_jabatan = j.id_jabatan
     WHERE p.id_penggajian = ?"
);
$stmt_gaji->bind_param("i", $id_gaji);
$stmt_gaji->execute();
$gaji_data = $stmt_gaji->get_result()->fetch_assoc();
$stmt_gaji->close();

if (!$gaji_data) {
    set_flash_message('error', 'Data gaji dengan ID tersebut tidak ditemukan.');
    header('Location: detail_gaji_guru.php');
    exit;
}

// 2. Data bulan dan tahun untuk periode gaji
$bulan_gaji = $gaji_data['bulan_penggajian'];
$tahun_gaji = date('Y', strtotime($gaji_data['tgl_input']));

// 3. Ambil data kehadiran dari tabel Rekap_Kehadiran
$stmt_presensi = $conn->prepare("SELECT jml_hadir, jml_terlambat, jml_izin, jml_alfa FROM Rekap_Kehadiran WHERE id_guru = ? AND bulan = ? AND tahun = ?");
$stmt_presensi->bind_param('sss', $gaji_data['id_guru'], $bulan_gaji, $tahun_gaji);
$stmt_presensi->execute();
$presensi_data = $stmt_presensi->get_result()->fetch_assoc();
$stmt_presensi->close();

// Jika tidak ada data kehadiran, set default 0
if (!$presensi_data) {
    $presensi_data = ['jml_hadir' => 0, 'jml_terlambat' => 0, 'jml_izin' => 0, 'jml_alfa' => 0];
}
$conn->close();


// 4. Hitung ulang komponen untuk ditampilkan di detail
$gaji_pokok = $gaji_data['gaji_pokok'] ?? 0;

// Hitung masa kerja untuk ditampilkan
$tgl_awal_kerja = new DateTime($gaji_data['tgl_masuk']);
$tgl_gaji = new DateTime($gaji_data['tgl_input']);
$masa_kerja_text = $tgl_gaji->diff($tgl_awal_kerja)->format('%y tahun, %m bulan');

// Array bulan untuk display
$bulan_list = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$bulan_text = $bulan_list[$bulan_gaji] ?? '';

$page_title = 'Detail Gaji: ' . e($gaji_data['nama_guru']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-white p-8 rounded-xl shadow-lg max-w-3xl mx-auto border border-gray-200">
    <div class="text-center mb-8">
        <h2 class="text-3xl font-bold font-poppins text-gray-800">DETAIL GAJI</h2>
        <p class="text-gray-500 mt-1">Rincian perhitungan gaji untuk periode <?= e($bulan_text . ' ' . $tahun_gaji) ?></p>
    </div>

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 border border-gray-200 rounded-lg p-4">
            <div class="flex justify-between border-b pb-2"><span class="text-sm font-medium text-gray-500">Nama Karyawan</span><span class="text-sm font-semibold text-gray-800"><?= e($gaji_data['nama_guru']) ?></span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-sm font-medium text-gray-500">Jabatan</span><span class="text-sm font-semibold text-gray-800"><?= e($gaji_data['nama_jabatan']) ?></span></div>
            <div class="flex justify-between"><span class="text-sm font-medium text-gray-500">Tanggal Gaji</span><span class="text-sm font-semibold text-gray-800"><?= date('d F Y', strtotime($gaji_data['tgl_input'])) ?></span></div>
            <div class="flex justify-between"><span class="text-sm font-medium text-gray-500">Masa Kerja</span><span class="text-sm font-semibold text-gray-800"><?= e($masa_kerja_text) ?></span></div>
        </div>

        <div class="space-y-4">
            <div>
                <h3 class="font-bold text-lg text-green-700 mb-2">PENDAPATAN</h3>
                <div class="border rounded-md">
                    <div class="flex justify-between py-2.5 px-4 border-b"><span class="text-sm">Gaji Pokok</span><span class="font-semibold">Rp <?= number_format($gaji_pokok, 2, ',', '.') ?></span></div>
                    <div class="flex justify-between py-2.5 px-4 border-b"><span class="text-sm">Tunjangan Beras</span><span class="font-semibold">Rp <?= number_format($gaji_data['tunjangan_beras'] ?? 0, 2, ',', '.') ?></span></div>
                    <div class="flex justify-between py-2.5 px-4 border-b"><span class="text-sm">Tunjangan Kehadiran</span><span class="font-semibold">Rp <?= number_format($gaji_data['tunjangan_kehadiran'] ?? 0, 2, ',', '.') ?></span></div>
                    <div class="flex justify-between py-2.5 px-4 border-b"><span class="text-sm">Tunjangan Suami/Istri</span><span class="font-semibold">Rp <?= number_format($gaji_data['tunjangan_suami_istri'] ?? 0, 2, ',', '.') ?></span></div>
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Tunjangan Anak</span><span class="font-semibold">Rp <?= number_format($gaji_data['tunjangan_anak'] ?? 0, 2, ',', '.') ?></span></div>
                </div>
                <div class="flex justify-between py-2.5 px-4 bg-gray-100 rounded-b-md font-bold"><span>Total Pendapatan (Gaji Kotor)</span><span>Rp <?= number_format($gaji_data['gaji_kotor'] ?? 0, 2, ',', '.') ?></span></div>
            </div>

            <div>
                <h3 class="font-bold text-lg text-red-700 mb-2">POTONGAN</h3>
                <div class="border rounded-md">
                    <div class="flex justify-between py-2.5 px-4 border-b"><span class="text-sm">BPJS</span><span class="text-sm font-semibold text-red-600">- Rp <?= number_format($gaji_data['potongan_bpjs'] ?? 0, 2, ',', '.') ?></span></div>
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Infak</span><span class="text-sm font-semibold text-red-600">- Rp <?= number_format($gaji_data['infak'] ?? 0, 2, ',', '.') ?></span></div>
                </div>
                <div class="flex justify-between py-2.5 px-4 bg-gray-100 rounded-b-md font-bold"><span class="text-red-600">Total Potongan</span><span class="text-red-600">- Rp <?= number_format($gaji_data['total_potongan'] ?? 0, 2, ',', '.') ?></span></div>
            </div>

            <div>
                <h3 class="font-bold text-lg text-blue-700 mb-2">RINCIAN KEHADIRAN</h3>
                <div class="border rounded-md divide-y">
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Hadir</span><span class="font-semibold"><?= e($presensi_data['jml_hadir'] ?? 0) ?> hari</span></div>
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Terlambat</span><span class="font-semibold"><?= e($presensi_data['jml_terlambat'] ?? 0) ?> hari</span></div>
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Izin</span><span class="font-semibold"><?= e($presensi_data['jml_izin'] ?? 0) ?> hari</span></div>
                    <div class="flex justify-between py-2.5 px-4"><span class="text-sm">Alpha</span><span class="font-semibold"><?= e($presensi_data['jml_alfa'] ?? 0) ?> hari</span></div>
                </div>
            </div>
        </div>

        <div class="bg-green-100 border-l-4 border-green-500 text-green-800 p-4 rounded-lg flex justify-between items-center mt-6">
            <span class="text-lg font-bold font-poppins">GAJI BERSIH (TAKE HOME PAY)</span>
            <span class="text-xl font-bold">Rp <?= number_format($gaji_data['gaji_bersih'] ?? 0, 2, ',', '.') ?></span>
        </div>

        <div class="flex items-center justify-end pt-6">
            <a href="laporan_admin.php" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Kembali ke Daftar Gaji</a>
        </div>
    </div>
</div>