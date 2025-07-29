<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Allow multiple roles to access slip gaji
$allowed_roles = ['kepala_sekolah', 'bendahara', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header('Location: /penggajian-guru/pages/auth/login.php');
    exit();
}

// Check if DomPDF is available
$vendor_autoload = __DIR__ . '/../vendor/autoload.php';
$use_pdf = file_exists($vendor_autoload);

if ($use_pdf) {
    require_once $vendor_autoload;
}

$conn = db_connect();

// Ambil ID penggajian dari parameter GET
$id_penggajian = $_GET['id'] ?? '';

if (empty($id_penggajian)) {
    die('ID Penggajian tidak ditemukan.');
}

// Ambil data penggajian beserta data guru
$sql = "SELECT p.*, g.nama_guru, g.nipm, g.no_hp, g.email, g.status_kawin, g.jml_anak, j.nama_jabatan, g.tgl_masuk
        FROM Penggajian p 
        JOIN Guru g ON p.id_guru = g.id_guru
        JOIN Jabatan j ON g.id_jabatan = j.id_jabatan
        WHERE p.id_penggajian = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $id_penggajian);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die('Data penggajian tidak ditemukan.');
}

$bulan_opsi = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', 
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', 
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

$bulan_text = $bulan_opsi[$data['bulan_penggajian']] ?? '';
$tahun_text = date('Y', strtotime($data['tgl_input']));
$conn->close();

// Check if we want PDF or HTML view
$view_pdf = isset($_GET['pdf']) && $_GET['pdf'] == '1';

// Buat HTML untuk slip gaji
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - ' . htmlspecialchars($data['nama_guru']) . '</title>
    <style>
        @page {
            margin: 20mm;
            size: A4 portrait;
        }
        body { 
            font-family: "Helvetica", Arial, sans-serif;
            font-size: 11px; 
            margin: 0; 
            padding: 0;
            line-height: 1.3;
        }
        .header { 
            text-align: center; 
            margin-bottom: 25px; 
            border-bottom: 2px solid #2e7d32; 
            padding-bottom: 15px; 
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .logo-cell {
            width: 80px;
            text-align: center;
            vertical-align: middle;
        }
        .logo { 
            font-family: serif; 
            font-size: 24px; 
            font-weight: bold; 
            color: #2e7d32; 
            line-height: 1;
        }
        .logo-subtitle {
            font-family: sans-serif; 
            font-size: 10px; 
            color: #2e7d32;
        }
        .company-info-cell {
            text-align: center;
            vertical-align: middle;
        }
        .company-name { 
            font-size: 18px; 
            font-weight: bold; 
            color: #2e7d32; 
            margin: 5px 0; 
        }
        .company-address { 
            font-size: 10px; 
            color: #666; 
            margin: 2px 0; 
        }
        .slip-title { 
            font-size: 16px; 
            font-weight: bold; 
            text-align: center; 
            margin: 20px 0 15px; 
            color: #2e7d32;
        }
        .info-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0; 
            font-size: 10px;
        }
        .info-table td { 
            padding: 4px 6px; 
            text-align: left; 
        }
        .salary-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0; 
            font-size: 10px;
            border: 1px solid #333;
        }
        .salary-table th, .salary-table td { 
            border: 1px solid #333; 
            padding: 6px 8px; 
            text-align: left; 
        }
        .salary-table th { 
            background-color: #f5f5f5; 
            font-weight: bold; 
            text-align: center;
            font-size: 11px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { 
            background-color: #f0f0f0; 
            font-weight: bold; 
        }
        .footer { 
            margin-top: 40px; 
            page-break-inside: avoid;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .print-info {
            font-size: 9px;
            color: #666;
            text-align: center;
            margin-top: 20px;
        }
        .signature { 
            text-align: center; 
            padding: 0 20px;
        }
        .signature-space {
            height: 60px;
        }
        .signature-line { 
            border-top: 1px solid #000; 
            padding-top: 5px; 
            font-size: 10px;
            width: 200px;
            margin: 0 auto;
        }
        @media print {
            .no-print { display: none !important; }
        }
        .no-print {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: #f5f5f5;
        }
        .btn {
            background: #2e7d32;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin: 0 5px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #1b5e20;
        }
        .btn-danger {
            background: #d32f2f;
        }
        .btn-danger:hover {
            background: #b71c1c;
        }
    </style>
</head>
<body>';

// Only show buttons if not generating PDF
if (!$view_pdf) {
    $html .= '
    <div class="no-print">
        <button onclick="window.print()" class="btn">🖨️ Cetak Slip Gaji</button>';
    
    if ($use_pdf) {
        $html .= '
        <a href="?id=' . htmlspecialchars($id_penggajian) . '&pdf=1" class="btn" style="background: #1976d2;">📄 Download PDF</a>';
    }
    
    $html .= '
        <button onclick="window.close()" class="btn btn-danger">✖️ Tutup</button>
    </div>';
}

$html .= '
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <div class="logo">SDUMK</div>
                    <div class="logo-subtitle">SD Unggulan</div>
                </td>
                <td class="company-info-cell">
                    <div class="company-name">SD UNGGULAN MUHAMMADIYAH KRETEK</div>
                    <div class="company-address">Jl. Raya Kretek, Bantul, Yogyakarta</div>
                    <div class="company-address">Telp: (0274) 123-4567 | Email: info@sdumkretek.sch.id</div>
                    <div class="company-address">Website: www.sdumkretek.sch.id</div>
                </td>
                <td class="logo-cell"></td>
            </tr>
        </table>
    </div>
    
    <div class="slip-title">SLIP GAJI GURU</div>
    
    <table class="info-table">
        <tr>
            <td width="20%"><strong>Nama</strong></td>
            <td width="30%">: ' . htmlspecialchars($data['nama_guru']) . '</td>
            <td width="20%"><strong>Periode</strong></td>
            <td width="30%">: ' . htmlspecialchars($bulan_text . ' ' . $tahun_text) . '</td>
        </tr>
        <tr>
            <td><strong>NIPM</strong></td>
            <td>: ' . htmlspecialchars($data['nipm']) . '</td>
            <td><strong>Masa Kerja</strong></td>
            <td>: ' . htmlspecialchars($data['masa_kerja']) . ' tahun</td>
        </tr>
        <tr>
            <td><strong>Jabatan</strong></td>
            <td>: ' . htmlspecialchars($data['nama_jabatan']) . '</td>
            <td><strong>ID Gaji</strong></td>
            <td>: ' . htmlspecialchars($data['id_penggajian']) . '</td>
        </tr>
    </table>
    
    <table class="salary-table">
        <thead>
            <tr>
                <th colspan="2">PENGHASILAN</th>
                <th colspan="2">POTONGAN</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Gaji Pokok</td>
                <td class="text-right">Rp ' . number_format($data['gaji_pokok'] ?? 0, 0, ',', '.') . '</td>
                <td>BPJS</td>
                <td class="text-right">Rp ' . number_format($data['potongan_bpjs'] ?? 0, 0, ',', '.') . '</td>
            </tr>
            <tr>
                <td>Tunjangan Beras</td>
                <td class="text-right">Rp ' . number_format($data['tunjangan_beras'] ?? 0, 0, ',', '.') . '</td>
                <td>Infak</td>
                <td class="text-right">Rp ' . number_format($data['infak'] ?? 0, 0, ',', '.') . '</td>
            </tr>
            <tr>
                <td>Tunjangan Kehadiran</td>
                <td class="text-right">Rp ' . number_format($data['tunjangan_kehadiran'] ?? 0, 0, ',', '.') . '</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Tunjangan Suami/Istri</td>
                <td class="text-right">Rp ' . number_format($data['tunjangan_suami_istri'] ?? 0, 0, ',', '.') . '</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Tunjangan Anak</td>
                <td class="text-right">Rp ' . number_format($data['tunjangan_anak'] ?? 0, 0, ',', '.') . '</td>
                <td></td>
                <td></td>
            </tr>
            <tr class="total-row">
                <td><strong>Total Penghasilan</strong></td>
                <td class="text-right"><strong>Rp ' . number_format($data['gaji_kotor'], 0, ',', '.') . '</strong></td>
                <td><strong>Total Potongan</strong></td>
                <td class="text-right"><strong>Rp ' . number_format($data['total_potongan'], 0, ',', '.') . '</strong></td>
            </tr>
            <tr style="background-color: #2e7d32; color: white;">
                <td colspan="3" style="text-align: right; padding: 10px;"><strong>GAJI BERSIH (TAKE HOME PAY)</strong></td>
                <td class="text-right" style="padding: 10px;"><strong>Rp ' . number_format($data['gaji_bersih'], 0, ',', '.') . '</strong></td>
            </tr>
        </tbody>
    </table>
    
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td width="50%" class="signature">
                    <div>Diterima oleh,</div>
                    <div class="signature-space"></div>
                    <div class="signature-line">
                        ' . htmlspecialchars($data['nama_guru']) . '
                    </div>
                </td>
                <td width="50%" class="signature">
                    <div>Bendahara Sekolah,</div>
                    <div class="signature-space"></div>
                    <div class="signature-line">
                        (...........................)
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="print-info">
        Dokumen ini dicetak pada: ' . date('d F Y, H:i:s') . ' WIB<br>
        <em>Slip gaji ini sah tanpa tanda tangan basah</em>
    </div>
</body>
</html>';

// If PDF is requested and DomPDF is available
if ($view_pdf && $use_pdf) {
    $options = new \Dompdf\Options();
    $options->set('defaultFont', 'Helvetica');
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $filename = 'Slip_Gaji_' . $data['nama_guru'] . '_' . $bulan_text . '_' . $tahun_text . '.pdf';
    $dompdf->stream($filename, ['Attachment' => false]);
} else {
    // Otherwise just display HTML
    echo $html;
}
?>
