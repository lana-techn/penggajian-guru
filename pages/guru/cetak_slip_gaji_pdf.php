<?php
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
    "SELECT g.*, gu.nama_lengkap, gu.nip, gu.tanggal_masuk, j.nama_jabatan 
     FROM gaji g 
     JOIN guru gu ON g.guru_id = gu.id 
     JOIN jabatan j ON gu.jabatan_id = j.id
     WHERE g.id = ?"
);
$stmt_gaji->bind_param("i", $id_gaji);
$stmt_gaji->execute();
$slip_data = $stmt_gaji->get_result()->fetch_assoc();
$stmt_gaji->close();

if (!$slip_data) {
    die("Data slip gaji dengan ID tersebut tidak ditemukan.");
}

// Ambil data presensi untuk periode gaji terkait
$bulan_nama_db = date('F', strtotime($slip_data['periode_gaji']));
$bulan_map = [ "January" => "Januari", "February" => "Februari", "March" => "Maret", "April" => "April", "May" => "Mei", "June" => "Juni", "July" => "Juli", "August" => "Agustus", "September" => "September", "October" => "Oktober", "November" => "November", "December" => "Desember" ];
$bulan_gaji = $bulan_map[$bulan_nama_db];
$tahun_gaji = date('Y', strtotime($slip_data['periode_gaji']));

$stmt_presensi = $conn->prepare("SELECT COUNT(*) as hadir FROM absensi WHERE guru_id = ? AND status = 'hadir' AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
$stmt_presensi->bind_param("iii", $slip_data['guru_id'], date('m', strtotime($slip_data['periode_gaji'])), $tahun_gaji);
$stmt_presensi->execute();
$presensi_data = $stmt_presensi->get_result()->fetch_assoc();
$stmt_presensi->close();
$conn->close();

$kehadiran_hari = $presensi_data['hadir'] ?? 0;

// Buat HTML untuk PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji</title>
    <style>
        body { font-family: "Helvetica", sans-serif; font-size: 11px; line-height: 1.3; }
        .slip-container { border: 2px solid #000; padding: 0; }
        .header { text-align: center; padding: 10px; border-bottom: 2px solid #000; }
        .company-name { font-size: 18px; font-weight: bold; }
        .company-address { font-size: 10px; }
        .slip-title { text-align: center; padding: 8px; font-weight: bold; font-size: 14px; text-decoration: underline; }
        .info-section { padding: 8px 12px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 3px 0; font-size: 10px; }
        .section-header { background-color: #f2f2f2; font-weight: bold; text-align: center; padding: 6px; border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .gaji-table { width: 100%; border-collapse: collapse; }
        .gaji-table td { padding: 5px 12px; font-size: 10px; }
        .gaji-table .label-col { width: 70%; }
        .gaji-table .value-col { text-align: right; }
        .total-row td { border-top: 1px solid #000; font-weight: bold; }
        .final-total { background-color: #e0e0e0; font-weight: bold; font-size: 11px; }
    </style>
</head>
<body>
    <div class="slip-container">
        <div class="header">
            <div class="company-name">CV KARYA WAHANA SENTOSA</div>
            <div class="company-address">Jl. Imogiri Barat Km. 17, Bungas, Jetis, Bantul, Yogyakarta</div>
        </div>
        <div class="slip-title">SLIP GAJI GURU</div>
        <div class="info-section">
            <table class="info-table">
                <tr><td>Nama</td><td>: ' . e($slip_data['nama_lengkap']) . '</td><td>Periode</td><td>: ' . e(date('F Y', strtotime($slip_data['periode_gaji']))) . '</td></tr>
                <tr><td>Jabatan</td><td>: ' . e($slip_data['nama_jabatan']) . '</td><td>NIP</td><td>: ' . e($slip_data['nip']) . '</td></tr>
            </table>
        </div>
        <div class="section-header">A. PENDAPATAN</div>
        <table class="gaji-table">
            <tr><td class="label-col">Gaji Pokok</td><td class="value-col">Rp ' . number_format($slip_data['gaji_pokok'] ?? 0, 2, ',', '.') . '</td></tr>
            <tr><td class="label-col">Tunjangan</td><td class="value-col">Rp ' . number_format($slip_data['total_tunjangan'], 2, ',', '.') . '</td></tr>
        </table>
        <div class="section-header">B. POTONGAN</div>
        <table class="gaji-table">
            <tr><td class="label-col">Total Potongan</td><td class="value-col">- Rp ' . number_format($slip_data['total_potongan'], 2, ',', '.') . '</td></tr>
        </table>
        <div class="section-header">C. RINCIAN KEHADIRAN</div>
        <table class="gaji-table">
             <tr><td class="label-col">Hadir</td><td class="value-col">' . $kehadiran_hari . ' hari</td></tr>
        </table>
        <table class="gaji-table">
             <tr class="final-total"><td class="label-col">GAJI BERSIH DITERIMA (TAKE HOME PAY)</td><td class="value-col">Rp ' . number_format($slip_data['gaji_bersih'], 2, ',', '.') . '</td></tr>
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

$filename = 'Slip_Gaji_' . str_replace(' ', '_', $slip_data['nama_lengkap']) . '_' . date('Y_m', strtotime($slip_data['periode_gaji'])) . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);
?>