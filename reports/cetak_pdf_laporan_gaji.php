<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');

// Include DomPDF library
require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$conn = db_connect();

// Ambil parameter filter
$filter_guru = $_GET['guru'] ?? '';
$filter_bulan = $_GET['bulan'] ?? '';
$filter_tahun = $_GET['tahun'] ?? date('Y');

$bulan_list = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', 
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', 
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// Build query
$sql = "SELECT p.*, g.nama_guru, j.nama_jabatan
        FROM Penggajian p 
        JOIN Guru g ON p.id_guru = g.id_guru 
        JOIN Jabatan j ON g.id_jabatan = j.id_jabatan 
        WHERE 1=1";
$params = [];
$types = '';

if (!empty($filter_guru)) {
    $sql .= " AND p.id_guru = ?";
    $params[] = $filter_guru;
    $types .= 's';
}
if (!empty($filter_bulan)) {
    $sql .= " AND p.bulan_penggajian = ?";
    $params[] = str_pad($filter_bulan, 2, '0', STR_PAD_LEFT);
    $types .= 's';
}
if (!empty($filter_tahun)) {
    $sql .= " AND YEAR(p.tgl_input) = ?";
    $params[] = $filter_tahun;
    $types .= 'i';
}

$sql .= " ORDER BY p.tgl_input DESC, g.nama_guru ASC";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$laporan_data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// Tentukan judul dan periode
$judul_laporan = 'LAPORAN GAJI GURU';
$periode = '';
if ($filter_bulan && $filter_tahun) {
    $periode = "Periode: " . ($bulan_list[$filter_bulan] ?? '') . " " . $filter_tahun;
} elseif ($filter_tahun) {
    $periode = "Periode: Tahun " . $filter_tahun;
}

// Hitung total
$total_gaji_pokok = array_sum(array_column($laporan_data, 'gaji_pokok'));
$total_semua_tunjangan = array_sum(array_column($laporan_data, 'tunjangan_beras')) 
                        + array_sum(array_column($laporan_data, 'tunjangan_kehadiran')) 
                        + array_sum(array_column($laporan_data, 'tunjangan_suami_istri')) 
                        + array_sum(array_column($laporan_data, 'tunjangan_anak'));
$total_semua_potongan = array_sum(array_column($laporan_data, 'total_potongan'));
$total_gaji_bersih = array_sum(array_column($laporan_data, 'gaji_bersih'));

// Buat HTML untuk PDF
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Gaji guru</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 landscape;
        }
        body { 
            font-family: "Helvetica", Arial, sans-serif;
            font-size: 9px; 
            color: #333;
            line-height: 1.4;
        }
        .container {
            width: 100%;
        }
        .header {
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
            margin-bottom: 2px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .logo-cell {
            width: 90px;
            text-align: center;
            vertical-align: middle;
        }
        .logo-placeholder {
            width: 75px;
            height: 75px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            display: inline-block;
            text-align: center;
            line-height: 75px;
            color: #aaa;
        }
        .logo-text {
            font-family: "Times New Roman", serif;
            font-size: 32px;
            font-weight: bold;
            color: #4CAF50;
            line-height: 1;
        }
        .logo-subtext {
            font-size: 10px;
            color: #555;
        }
        .company-info-cell {
            text-align: center;
            vertical-align: middle;
            padding: 0 10px;
        }
        .company-name { 
            font-size: 22px; 
            font-weight: bold; 
            color: #2E7D32; 
            margin: 0;
        }
        .company-address { 
            font-size: 11px; 
            color: #555; 
            margin: 2px 0; 
        }
        .report-title-section {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 15px;
        }
        .report-title { 
            font-size: 18px; 
            font-weight: bold; 
            color: #333;
            text-transform: uppercase;
            margin: 0;
        }
        .report-period { 
            font-size: 14px; 
            color: #666; 
            margin-top: 4px;
        }
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px;
        }
        .data-table th, .data-table td { 
            border: 1px solid #ccc; 
            padding: 5px; 
            text-align: left; 
        }
        .data-table th { 
            background-color: #4CAF50; 
            color: #ffffff;
            font-weight: bold; 
            text-align: center;
            font-size: 8.5px;
            text-transform: uppercase;
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .currency {
            font-family: "Courier New", monospace;
        }
        .totals-table {
            width: 100%;
            margin-top: 5px;
            border-top: 2px solid #4CAF50;
            padding-top: 10px;
        }
        .totals-table td {
            padding: 5px;
            font-size: 11px;
        }
        .totals-label {
            font-weight: bold;
            text-align: right;
            padding-right: 10px;
        }
        .totals-value {
            font-weight: bold;
            text-align: right;
            background-color: #f0f0f0;
            width: 150px;
        }
        .footer { 
            margin-top: 30px; 
            page-break-inside: avoid;
            font-size: 10px;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .print-info {
            color: #888;
        }
        .signature-block { 
            text-align: center; 
        }
        .signature-space {
            height: 60px;
        }
        .signature-name {
            font-weight: bold;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            font-size: 14px;
            font-style: italic;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        <div class="logo-text">SDUMK</div>
                        <div class="logo-subtext">SD Unggulan</div>
                    </td>
                    <td class="company-info-cell">
                        <div class="company-name">SD UNGGULAN MUHAMMADIYAH KRETEK</div>
                        <div class="company-address">Jl. Raya Kretek, Bantul, Daerah Istimewa Yogyakarta</div>
                        <div class="company-address">Telp: (0274) 123-4567 | Email: info@sdumkretek.sch.id</div>
                    </td>
                    <td class="logo-cell">
                        <div class="logo-placeholder">Logo</div>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="report-title-section">
            <div class="report-title">' . htmlspecialchars($judul_laporan) . '</div>
            <div class="report-period">' . htmlspecialchars($periode) . '</div>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>No Slip</th>
                    <th>Nama Guru</th>
                    <th>Periode</th>
                    <th class="text-right">Gaji Pokok</th>
                    <th class="text-right">Tunj. Beras</th>
                    <th class="text-right">Tunj. Hadir</th>
                    <th class="text-right">Tunj. Suami/Istri</th>
                    <th class="text-right">Tunj. Anak</th>
                    <th class="text-right">Gaji Kotor</th>
                    <th class="text-right">BPJS</th>
                    <th class="text-right">Infak</th>
                    <th class="text-right">Total Potongan</th>
                    <th class="text-right">Gaji Bersih</th>
                </tr>
            </thead>
            <tbody>';

if (!empty($laporan_data)) {
    $no = 1;
    foreach ($laporan_data as $row) {
        $periode_row = ($bulan_list[$row['bulan_penggajian']] ?? '') . ' ' . date('Y', strtotime($row['tgl_input']));
        $html .= '
            <tr>
                <td class="text-center">' . $no++ . '</td>
                <td class="text-center">' . htmlspecialchars($row['no_slip_gaji']) . '</td>
                <td>' . htmlspecialchars($row['nama_guru']) . '</td>
                <td class="text-center">' . htmlspecialchars($periode_row) . '</td>
                <td class="text-right currency">' . number_format($row['gaji_pokok'], 0, ',', '.') . '</td>
                <td class="text-right currency">' . number_format($row['tunjangan_beras'], 0, ',', '.') . '</td>
                <td class="text-right currency">' . number_format($row['tunjangan_kehadiran'], 0, ',', '.') . '</td>
                <td class="text-right currency">' . number_format($row['tunjangan_suami_istri'], 0, ',', '.') . '</td>
                <td class="text-right currency">' . number_format($row['tunjangan_anak'], 0, ',', '.') . '</td>
                <td class="text-right currency">' . number_format($row['gaji_kotor'], 0, ',', '.') . '</td>
                <td class="text-right currency">' . number_format($row['potongan_bpjs'], 0, ',', '.') . '</td>
                <td class="text-right currency">' . number_format($row['infak'], 0, ',', '.') . '</td>
                <td class="text-right currency">' . number_format($row['total_potongan'], 0, ',', '.') . '</td>
                <td class="text-right currency" style="font-weight: bold; background-color: #f0f4f0;">' . number_format($row['gaji_bersih'], 0, ',', '.') . '</td>
            </tr>';
    }
} else {
    $html .= '
            <tr>
                <td colspan="14" class="no-data">Tidak ada data yang cocok dengan kriteria filter yang dipilih.</td>
            </tr>';
}

$html .= '
            </tbody>
        </table>';

if (!empty($laporan_data)) {
$html .= '
        <table class="totals-table">
            <tr>
                <td style="width: 60%;" class="totals-label">Total Gaji Pokok:</td>
                <td class="totals-value currency">Rp ' . number_format($total_gaji_pokok, 0, ',', '.') . '</td>
            </tr>
            <tr>
                <td class="totals-label">Total Tunjangan:</td>
                <td class="totals-value currency">Rp ' . number_format($total_semua_tunjangan, 0, ',', '.') . '</td>
            </tr>
            <tr>
                <td class="totals-label">Total Potongan:</td>
                <td class="totals-value currency">Rp ' . number_format($total_semua_potongan, 0, ',', '.') . '</td>
            </tr>
            <tr>
                <td class="totals-label" style="font-size: 14px; color: #2E7D32;">TOTAL GAJI BERSIH:</td>
                <td class="totals-value currency" style="font-size: 14px; background-color: #dff0d8; color: #2E7D32;">Rp ' . number_format($total_gaji_bersih, 0, ',', '.') . '</td>
            </tr>
        </table>';
}
    
$html .= '
        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td style="width: 65%; vertical-align: bottom;">
                        <div class="print-info">
                            Laporan ini dicetak pada: ' . date('d F Y, H:i:s') . ' | Jumlah Data: ' . count($laporan_data) . '
                        </div>
                    </td>
                    <td style="width: 35%; text-align: right;">
                        <div class="signature-block">
                            <div>Bantul, ' . date('d F Y') . '</div>
                            <div>Mengetahui,</div>
                            <div class="signature-space"></div>
                            <div class="signature-name">( ....................................... )</div>
                            <div class="signature-title">Kepala Sekolah</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>';

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = 'Laporan_Gaji_Guru_' . date('Y-m-d_H-i-s') . '.pdf';
$dompdf->stream($filename, array("Attachment" => false)); // Ubah ke false untuk preview

?>