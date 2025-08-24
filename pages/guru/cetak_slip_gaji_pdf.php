<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once '../../includes/functions.php';
requireLogin();
requireRole('guru');

require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$conn = db_connect();
$slip_data = null;
$presensi_data = null;
$id_gaji = $_GET['id'] ?? null; // Ambil ID Gaji dari URL

// Jika tidak ada ID Gaji, hentikan proses
if (!$id_gaji) {
    die("Error: ID Gaji tidak valid atau tidak ditemukan.");
}

// Ambil data slip gaji utama berdasarkan ID dari URL
$stmt_gaji = $conn->prepare(
    "SELECT p.*, g.nama_guru, g.nipm, g.tgl_masuk, g.status_kawin, g.jml_anak, j.nama_jabatan,
            t.tunjangan_beras, t.tunjangan_kehadiran, t.tunjangan_suami_istri, t.tunjangan_anak
     FROM Penggajian p 
     JOIN Guru g ON p.id_guru = g.id_guru 
     JOIN Jabatan j ON g.id_jabatan = j.id_jabatan
     LEFT JOIN Tunjangan t ON g.id_tunjangan = t.id_tunjangan
     WHERE p.id_penggajian = ?"
);
$stmt_gaji->bind_param("s", $id_gaji);
$stmt_gaji->execute();
$slip_data = $stmt_gaji->get_result()->fetch_assoc();
$stmt_gaji->close();

if (!$slip_data) {
    die("Data slip gaji dengan ID tersebut tidak ditemukan.");
}

// Calculate tunjangan values based on guru data
$tunjangan_suami_istri_calculated = 0;
if (in_array($slip_data['status_kawin'], ['Kawin', 'Menikah', 'menikah'])) {
    $tunjangan_suami_istri_calculated = (float)($slip_data['tunjangan_suami_istri'] ?? 0);
}

$tunjangan_anak_calculated = 0;
$jml_anak = min((int)($slip_data['jml_anak'] ?? 0), 2);
if ($jml_anak > 0) {
    $tunjangan_anak_calculated = $jml_anak * (float)($slip_data['tunjangan_anak'] ?? 0);
}

// Store calculated values for display
$slip_data['tunjangan_suami_istri_display'] = $tunjangan_suami_istri_calculated;
$slip_data['tunjangan_anak_display'] = $tunjangan_anak_calculated;

// Mapping bulan
$bulan_map = [
    'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April',
    'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus',
    'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
];

// Fungsi untuk format tanggal Indonesia
function formatTanggalIndonesia($tanggal) {
    $bulan_map = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April',
        'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus',
        'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    
    $bulan_en = date('F', strtotime($tanggal));
    $bulan_id = $bulan_map[$bulan_en] ?? $bulan_en;
    
    return date('d', strtotime($tanggal)) . ' ' . $bulan_id . ' ' . date('Y', strtotime($tanggal));
}

// Perbaiki perhitungan periode gaji
$bulan_gaji = '-';
$tahun_gaji = '-';

if (isset($slip_data['bulan_penggajian']) && !empty($slip_data['bulan_penggajian'])) {
    // Coba format YYYY-MM
    if (preg_match('/^\d{4}-\d{2}$/', $slip_data['bulan_penggajian'])) {
        $bulan_nama_db = date('F', strtotime($slip_data['bulan_penggajian'] . '-01'));
        $bulan_gaji = $bulan_map[$bulan_nama_db] ?? $bulan_nama_db;
        $tahun_gaji = date('Y', strtotime($slip_data['bulan_penggajian'] . '-01'));
    } else {
        // Fallback ke tgl_input
        $bulan_nama_db = date('F', strtotime($slip_data['tgl_input']));
        $bulan_gaji = $bulan_map[$bulan_nama_db] ?? $bulan_nama_db;
        $tahun_gaji = date('Y', strtotime($slip_data['tgl_input']));
    }
} else {
    // Jika tidak ada bulan_penggajian, gunakan tgl_input
    $bulan_nama_db = date('F', strtotime($slip_data['tgl_input']));
    $bulan_gaji = $bulan_map[$bulan_nama_db] ?? $bulan_nama_db;
    $tahun_gaji = date('Y', strtotime($slip_data['tgl_input']));
}

// Ambil data presensi untuk periode gaji terkait (ubah ke Rekap_Kehadiran)
$bulan_presensi = $slip_data['bulan_penggajian'] ?? date('Y-m', strtotime($slip_data['tgl_input']));
$tahun_presensi = $tahun_gaji;

$stmt_presensi = $conn->prepare("SELECT jml_terlambat, jml_izin, jml_alfa FROM Rekap_Kehadiran WHERE id_guru = ? AND bulan = ? AND tahun = ?");
$stmt_presensi->bind_param("ssi", $slip_data['id_guru'], $bulan_presensi, $tahun_presensi);
$stmt_presensi->execute();
$presensi_data = $stmt_presensi->get_result()->fetch_assoc();
$stmt_presensi->close();
$conn->close();
$kehadiran_hari = ($presensi_data['jml_terlambat'] ?? 0) + ($presensi_data['jml_izin'] ?? 0) + ($presensi_data['jml_alfa'] ?? 0);

// Header teks di tengah tanpa logo

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji Guru</title>
    <style>
        body { font-family: "Arial", sans-serif; font-size: 11px; }
        .slip-container { border: 2px solid #000; padding: 0; max-width: 600px; margin: 0 auto; }
        .kop { text-align: center; border-bottom: 2px solid #000; padding: 8px 8px 2px 8px; }
        .kop-logo { float: left; width: 70px; height: 70px; margin-right: 10px; object-fit: contain; }
        .kop-title { font-size: 14px; font-weight: bold; }
        .kop-sub { font-size: 12px; }
        .kop-contact { font-size: 10px; margin-top: 2px; }
        .clear { clear: both; }
        .slip-title { text-align: center; font-weight: bold; font-size: 13px; margin: 10px 0 4px 0; text-decoration: underline; }
        .info-table { width: 100%; font-size: 11px; margin-bottom: 8px; }
        .info-table td { padding: 2px 4px; }
        .section-table { width: 100%; font-size: 11px; margin-bottom: 6px; }
        .section-table td { padding: 2px 4px; vertical-align: top; }
        .section-title { font-weight: bold; text-decoration: underline; }
        .ttd-table { width: 100%; margin-top: 24px; font-size: 11px; }
        .ttd-table td { text-align: center; padding: 18px 4px 4px 4px; }
        .ttd-label { font-size: 10px; }
        .ttd-space { height: 40px; }
        .bordered { border: 1px solid #000; }
    </style>
</head>
<body>
    <div class="slip-container">
        <div class="kop">
            <div style="text-align:center;">
                <div class="kop-title">Yayasan Pimpinan Daerah Muhammadiyah (PDM) Kab. Bantul</div>
                <div class="kop-sub">SD Unggulan Muhammadiyah Kretek</div>
                <div class="kop-contact">Email : sdumuhkretek@gmail.com | Miryan Donotirto Kretek Bantul 55772<br>Website : http://www.sdmuhkretek.sch.id.</div>
            </div>
        </div>
        <div class="slip-title">SLIP GAJI GURU</div>
        <table class="info-table">
            <tr>
                <td>Nama</td><td>: '.e($slip_data['nama_guru']).'</td>
                <td>Bulan Penggajian</td><td>: '.e($bulan_gaji.' '.$tahun_gaji).'</td>
            </tr>
            <tr>
                <td>NIPM</td><td>: '.e($slip_data['nipm']).'</td>
                <td>Masa Kerja</td><td>: '.(isset($slip_data['tgl_masuk']) ? (date('Y', strtotime($slip_data['tgl_input'])) - date('Y', strtotime($slip_data['tgl_masuk']))) . ' tahun' : '-').'</td>
            </tr>
            <tr>
                <td>Jabatan</td><td>: '.e($slip_data['nama_jabatan']).'</td>
                <td>id penggajian/Tanggal</td><td>: '.e($slip_data['id_penggajian']).' / '.formatTanggalIndonesia($slip_data['tgl_input']).'</td>
            </tr>
        </table>
        <table class="section-table">
            <tr>
                <td class="section-title" colspan="2">PENGHASILAN:</td>
                <td class="section-title" colspan="2">POTONGAN:</td>
            </tr>
            <tr>
                <td>• Gaji Pokok</td><td>: Rp '.number_format($slip_data['gaji_pokok'] ?? 0, 0, ',', '.').'</td>
                <td>• BPJS</td><td>: Rp '.number_format($slip_data['potongan_bpjs'] ?? 0, 0, ',', '.').'</td>
            </tr>
            <tr>
                <td>• Tunjangan Beras</td><td>: Rp '.number_format($slip_data['tunjangan_beras'] ?? 0, 0, ',', '.').'</td>
                <td>• Infak</td><td>: Rp '.number_format($slip_data['infak'] ?? 0, 0, ',', '.').'</td>
            </tr>
            <tr>
                <td>• Tunjangan Kehadiran</td><td>: Rp '.number_format($slip_data['tunjangan_kehadiran'] ?? 0, 0, ',', '.').'</td>
                <td></td><td></td>
            </tr>
            <tr>
                <td>• Tunjangan Suami/Istri</td><td>: Rp '.number_format($slip_data['tunjangan_suami_istri_display'] ?? 0, 0, ',', '.').'</td>
                <td></td><td></td>
            </tr>
            <tr>
                <td>• Tunjangan Anak</td><td>: Rp '.number_format($slip_data['tunjangan_anak_display'] ?? 0, 0, ',', '.').'</td>
                <td></td><td></td>
            </tr>
            <tr>
                <td colspan="2" style="border-top:1px solid #000;">Gaji Kotor</td>
                <td colspan="2" style="border-top:1px solid #000;">Total Potongan</td>
            </tr>
            <tr>
                <td colspan="2">: Rp '.number_format(($slip_data['gaji_pokok']+$slip_data['tunjangan_beras']+$slip_data['tunjangan_kehadiran']+$slip_data['tunjangan_suami_istri_display']+$slip_data['tunjangan_anak_display']), 0, ',', '.').'</td>
                <td colspan="2">: Rp '.number_format(($slip_data['potongan_bpjs']+$slip_data['infak']), 0, ',', '.').'</td>
            </tr>
            <tr>
                <td colspan="4" style="border-top:1px solid #000;"></td>
            </tr>
            <tr>
                <td colspan="2"><b>Gaji Bersih</b></td>
                <td colspan="2"><b>: Rp '.number_format($slip_data['gaji_bersih'], 0, ',', '.').'</b></td>
            </tr>
        </table>
        <table class="ttd-table">
            <tr>
                <td class="ttd-label">Diterima</td>
                <td></td>
                <td class="ttd-label">Tempat dan Tanggal<br>Pembuatan</td>
            </tr>
            <tr class="ttd-space"><td></td><td></td><td></td></tr>
            <tr>
                <td class="ttd-label">Tertanda<br><br>Nama Penerima</td>
                <td></td>
                <td class="ttd-label">Bendahara Sekolah<br><br>Nama Bendahara</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td class="ttd-label">Bantul, '.formatTanggalIndonesia($slip_data['tgl_input']).'</td>
            </tr>
        </table>
    </div>
</body>
</html>';

// Generate PDF
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

ob_end_clean();
$filename = 'Slip_Gaji_' . str_replace(' ', '_', $slip_data['nama_guru']) . '_' . date('Y_m', strtotime($slip_data['tgl_input'])) . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);
?>