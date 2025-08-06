<?php
require_once __DIR__ . 
'/../../includes/functions.php';
requireLogin();
requireRole('kepala_sekolah');

// Include DomPDF library
// Pastikan Anda telah menginstal DomPDF via Composer:
// composer require dompdf/dompdf
require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$conn = db_connect();

// Ambil filter dari GET request
$filter_bulan = $_GET['bulan'] ?? '';
$filter_tahun = $_GET['tahun'] ?? date('Y');
$filter_jabatan = $_GET['jabatan'] ?? '';

// Ambil data jabatan untuk nama jabatan
$jabatan_list = $conn->query("SELECT id_jabatan, nama_jabatan FROM Jabatan ORDER BY nama_jabatan ASC")->fetch_all(MYSQLI_ASSOC);

// Bangun query dinamis berdasarkan filter
$sql = "
    SELECT 
        p.id_penggajian as Id_Gaji,
        g.nama_guru as Nama_Karyawan, 
        j.nama_jabatan as Nama_Jabatan, 
        p.tgl_input as Tgl_Gaji, 
        (p.tunjangan_suami_istri + p.tunjangan_anak + p.tunjangan_beras + p.tunjangan_kehadiran) as Total_Tunjangan,
        0 as Total_Lembur, -- Tidak ada lembur di skema baru
        p.gaji_pokok as Gaji_Pokok,
        (p.potongan_bpjs + p.infak) as Total_Potongan, 
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

// Tentukan judul dan periode
$judul_laporan = 'LAPORAN GAJI ';
if ($filter_jabatan) {
    $nama_jabatan_terfilter = '';
    foreach ($jabatan_list as $j) { 
        if ($j['id_jabatan'] === $filter_jabatan) 
            $nama_jabatan_terfilter = $j['nama_jabatan']; 
    }
    $judul_laporan .= "PER JABATAN: " . strtoupper($nama_jabatan_terfilter);
} else {
    $judul_laporan .= "PER BULAN";
}

$periode = '';
if ($filter_bulan && $filter_tahun) {
    $periode = "Periode: " . date('F', mktime(0, 0, 0, $filter_bulan, 10)) . " " . $filter_tahun;
} elseif ($filter_tahun) {
    $periode = "Periode: Tahun " . $filter_tahun;
}

// Hitung total
$total_gaji_pokok = 0;
$total_semua_tunjangan = 0;
$total_semua_lembur = 0;
$total_semua_potongan = 0;
$total_gaji_bersih = 0;

foreach ($laporan_data as $row) {
    $total_gaji_pokok += $row['Gaji_Pokok'];
    $total_semua_tunjangan += $row['Total_Tunjangan'];
    $total_semua_lembur += $row['Total_Lembur'];
    $total_semua_potongan += $row['Total_Potongan'];
    $total_gaji_bersih += $row['Gaji_Bersih'];
}

// Buat HTML untuk PDF
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Gaji Karyawan</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 landscape;
        }
        body { 
            font-family: "Helvetica", Arial, sans-serif;
            font-size: 10px; 
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
            padding: 6px 5px; 
            text-align: left; 
        }
        .data-table th { 
            background-color: #4CAF50; 
            color: #ffffff;
            font-weight: bold; 
            text-align: center;
            font-size: 9.5px;
            text-transform: uppercase;
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .data-table tbody tr:hover {
            background-color: #f1f1f1;
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
        .signature-title {
            color: #555;
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
                    <th style="width: 3%;">No</th>
                    <th style="width: 10%;">ID Gaji</th>
                    <th style="width: 8%;">Tanggal</th>
                    <th>Nama Karyawan</th>
                    <th style="width: 13%;">Jabatan</th>
                    <th style="width: 11%;" class="text-right">Gaji Pokok</th>
                    <th style="width: 11%;" class="text-right">Tunjangan</th>
                    <th style="width: 11%;" class="text-right">Potongan</th>
                    <th style="width: 12%;" class="text-right">Gaji Bersih</th>
                </tr>
            </thead>
            <tbody>';

if (!empty($laporan_data)) {
    $no = 1;
    foreach ($laporan_data as $row) {
        $html .= '
            <tr class="' . ($no % 2 == 0 ? 'zebra-row' : '') . '">
                <td class="text-center">' . $no++ . '</td>
                <td class="text-center">' . htmlspecialchars($row['Id_Gaji']) . '</td>
                <td class="text-center">' . date('d-m-Y', strtotime($row['Tgl_Gaji'])) . '</td>
                <td>' . htmlspecialchars($row['Nama_Karyawan']) . '</td>
                <td>' . htmlspecialchars($row['Nama_Jabatan']) . '</td>
                <td class="text-right currency">' . number_format($row['Gaji_Pokok'], 0, ',', '.') . '</td>
                <td class="text-right currency">' . number_format($row['Total_Tunjangan'], 0, ',', '.') . '</td>
                <td class="text-right currency">' . number_format($row['Total_Potongan'], 0, ',', '.') . '</td>
                <td class="text-right currency" style="font-weight: bold; background-color: #f0f4f0;">' . number_format($row['Gaji_Bersih'], 0, ',', '.') . '</td>
            </tr>';
    }
} else {
    $html .= '
            <tr>
                <td colspan="9" class="no-data">Tidak ada data yang cocok dengan kriteria filter yang dipilih.</td>
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

// Uncomment kode di bawah ini untuk mengaktifkan DomPDF
// Pastikan path ke vendor/autoload.php sudah benar
// Pastikan Anda sudah menjalankan `php vendor/dompdf/dompdf/bin/load_font.php` jika ada masalah font

$options = new Options();
$options->set('defaultFont', 'Helvetica'); // Menggunakan Helvetica sebagai font default
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = 'Laporan_Gaji_' . date('Y-m-d_H-i-s') . '.pdf';
$dompdf->stream($filename, array("Attachment" => true));

// Untuk sementara, jika DomPDF belum diaktifkan, tampilkan HTML (untuk preview)
// echo $html;
?>

