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
$filter_karyawan = $_GET['karyawan'] ?? '';
$filter_bulan = $_GET['bulan'] ?? '';
$filter_tahun = $_GET['tahun'] ?? date('Y');

$bulan_list = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
               5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
               9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

// Build query
$sql = "SELECT p.*, g.nama_guru, j.nama_jabatan
        FROM Penggajian p 
        JOIN Guru g ON p.id_guru = g.id_guru 
        JOIN Jabatan j ON g.id_jabatan = j.id_jabatan 
        WHERE 1=1";
$params = [];
$types = '';

if (!empty($filter_karyawan)) {
    $sql .= " AND p.id_guru = ?";
    $params[] = $filter_karyawan;
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

// Hitung total untuk summary
$total_gaji_kotor = 0;
$total_potongan = 0;
$total_gaji_bersih = 0;
$data_rows = [];

while ($row = $result->fetch_assoc()) {
    $data_rows[] = $row;
    $total_gaji_kotor += $row['gaji_kotor'];
    $total_potongan += $row['total_potongan'];
    $total_gaji_bersih += $row['gaji_bersih'];
}

$stmt->close();

// Get guru name if filtering by karyawan
$guru_name = '';
if (!empty($filter_karyawan) && count($data_rows) > 0) {
    $guru_name = $data_rows[0]['nama_guru'];
}

$conn->close();

// Build title berdasarkan filter
$title_filter = '';
if (!empty($filter_karyawan) && !empty($guru_name)) {
    $title_filter .= $guru_name . ' - ';
}
if (!empty($filter_bulan)) {
    $title_filter .= $bulan_list[$filter_bulan] . ' ';
}
if (!empty($filter_tahun)) {
    $title_filter .= $filter_tahun;
}

// Buat HTML untuk PDF
$html = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Gaji - ' . htmlspecialchars($title_filter) . '</title>
    <style>
        @page {
            margin: 20mm;
            size: A4 landscape;
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
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0 5px;
            color: #2e7d32;
        }
        .report-period {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-bottom: 20px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 9px;
        }
        .data-table th, .data-table td {
            border: 1px solid #333;
            padding: 4px 3px;
            text-align: left;
        }
        .data-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
            font-size: 8px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .footer {
            margin-top: 25px;
            page-break-inside: avoid;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .print-info {
            font-size: 9px;
            color: #666;
        }
        .signature {
            text-align: center;
            width: 200px;
        }
        .signature-space {
            height: 50px;
        }
        .signature-line {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-size: 10px;
        }
        .no-data {
            text-align: center;
            padding: 30px;
            font-style: italic;
            color: #666;
        }
    </style>
</head>
<body>
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

    <div class="report-title">LAPORAN GAJI</div>
    <div class="report-period">Periode: ' . htmlspecialchars($title_filter) . '</div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 12%;">No Slip</th>
                <th style="width: 20%;">Nama Guru</th>
                <th style="width: 15%;">Jabatan</th>
                <th style="width: 8%;">Periode</th>
                <th style="width: 12%;">Gaji Kotor</th>
                <th style="width: 12%;">Potongan</th>
                <th style="width: 12%;">Gaji Bersih</th>
            </tr>
        </thead>
        <tbody>';

if (count($data_rows) > 0) {
    $no = 1;
    foreach ($data_rows as $row) {
        $html .= '<tr>
                      <td class="text-center">' . $no++ . '</td>
                      <td class="text-center">' . htmlspecialchars($row['no_slip_gaji'] ?? 'SG' . date('ym') . str_pad($no-1, 4, '0', STR_PAD_LEFT)) . '</td>
                      <td>' . htmlspecialchars($row['nama_guru']) . '</td>
                      <td>' . htmlspecialchars($row['nama_jabatan']) . '</td>
                      <td class="text-center">' . date('m/Y', strtotime($row['tgl_input'])) . '</td>
                      <td class="text-right">Rp ' . number_format($row['gaji_kotor'], 0, ',', '.') . '</td>
                      <td class="text-right">Rp ' . number_format($row['total_potongan'], 0, ',', '.') . '</td>
                      <td class="text-right font-bold">Rp ' . number_format($row['gaji_bersih'], 0, ',', '.') . '</td>
                  </tr>';
    }
    $html .= '<tr class="total-row">
                  <td colspan="5" class="text-center">TOTAL</td>
                  <td class="text-right">Rp ' . number_format($total_gaji_kotor, 0, ',', '.') . '</td>
                  <td class="text-right">Rp ' . number_format($total_potongan, 0, ',', '.') . '</td>
                  <td class="text-right">Rp ' . number_format($total_gaji_bersih, 0, ',', '.') . '</td>
              </tr>';
} else {
    $html .= '<tr><td colspan="8" class="no-data">Tidak ada data yang ditemukan</td></tr>';
}

$html .= '</tbody>
    </table>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="signature">Mengetahui,<br>Kepala Sekolah<br><div class="signature-space"></div><div class="signature-line">(.................................)</div></td>
                <td class="signature">Dibuat oleh,<br>Admin<br><div class="signature-space"></div><div class="signature-line">' . htmlspecialchars($_SESSION['username'] ?? 'Administrator') . '</div></td>
            </tr>
        </table>
    </div>

    <div class="print-info">Dokumen ini dicetak pada: ' . date('d F Y, H:i:s') . ' WIB<br><em>Laporan ini adalah hasil cetak sistem dan tidak memerlukan tanda tangan basah</em></div>
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

$filename = 'Laporan_Gaji_' . date('Y-m-d_H-i-s') . '.pdf';
$dompdf->stream($filename, array("Attachment" => false));
?>

