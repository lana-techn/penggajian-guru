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
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Gaji</title>
    <style>
        @page {
            margin: 20mm;
            size: A4 landscape;
        }
        body { 
            font-family: "Helvetica", Arial, sans-serif; /* Menggunakan Helvetica sebagai font default */
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
            width: 60px; 
            height: 60px; 
            /* Jika Anda memiliki logo gambar, uncomment baris di bawah dan sesuaikan path */
            /* background-image: url(\'path/to/your/logo.png\'); */
            /* background-size: contain; */
            /* background-repeat: no-repeat; */
            /* background-position: center; */
            
            /* Placeholder jika tidak ada gambar logo */
            /* border: 1px solid #ddd; */
            /* background-color: #f9f9f9; */
            display: inline-block;
            line-height: 60px;
            text-align: center;
            color: #666;
            font-size: 10px;
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
                    <div class="logo" style="font-family: serif; font-size: 24px; font-weight: bold; color: #2e7d32; line-height: 1;">SDUMK</div>
                    <div style="font-family: sans-serif; font-size: 10px; color: #2e7d32;">SD Unggulan</div>
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
    
    <div class="report-title">' . htmlspecialchars($judul_laporan) . '</div>
    <div class="report-period">' . htmlspecialchars($periode) . '</div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 12%;">ID Gaji</th>
                <th style="width: 9%;">Tanggal</th>
                <th style="width: 18%;">Nama Karyawan</th>
                <th style="width: 12%;">Jabatan</th>
                <th style="width: 11%;">Gaji Pokok</th>
                <th style="width: 10%;">Tunjangan</th>
                <th style="width: 8%;">Lembur</th>
                <th style="width: 8%;">Potongan</th>
                <th style="width: 8%;">Gaji Bersih</th>
            </tr>
        </thead>
        <tbody>';

if (!empty($laporan_data)) {
    $no = 1;
    foreach ($laporan_data as $row) {
        $html .= '
            <tr>
                <td class="text-center">' . $no++ . '</td>
                <td class="text-center" style="font-family: Helvetica; font-size: 8px;">' . htmlspecialchars($row['Id_Gaji']) . '</td>
                <td class="text-center">' . date('d/m/Y', strtotime($row['Tgl_Gaji'])) . '</td>
                <td>' . htmlspecialchars($row['Nama_Karyawan']) . '</td>
                <td>' . htmlspecialchars($row['Nama_Jabatan']) . '</td>
                <td class="text-right">Rp ' . number_format($row['Gaji_Pokok'], 0, ',', '.') . '</td>
                <td class="text-right">Rp ' . number_format($row['Total_Tunjangan'], 0, ',', '.') . '</td>
                <td class="text-right">Rp ' . number_format($row['Total_Lembur'], 0, ',', '.') . '</td>
                <td class="text-right">Rp ' . number_format($row['Total_Potongan'], 0, ',', '.') . '</td>
                <td class="text-right">Rp ' . number_format($row['Gaji_Bersih'], 0, ',', '.') . '</td>
            </tr>';
    }
    
    $html .= '
            <tr class="total-row">
                <td colspan="5" class="text-right" style="font-weight: bold;">TOTAL KESELURUHAN:</td>
                <td class="text-right" style="font-weight: bold;">Rp ' . number_format($total_gaji_pokok, 0, ',', '.') . '</td>
                <td class="text-right" style="font-weight: bold;">Rp ' . number_format($total_semua_tunjangan, 0, ',', '.') . '</td>
                <td class="text-right" style="font-weight: bold;">Rp ' . number_format($total_semua_lembur, 0, ',', '.') . '</td>
                <td class="text-right" style="font-weight: bold;">Rp ' . number_format($total_semua_potongan, 0, ',', '.') . '</td>
                <td class="text-right" style="font-weight: bold;">Rp ' . number_format($total_gaji_bersih, 0, ',', '.') . '</td>
            </tr>';
} else {
    $html .= '
            <tr>
                <td colspan="10" class="no-data">Tidak ada data yang cocok dengan kriteria filter.</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
    
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td style="width: 50%;">
                    <div class="print-info">
                        Dicetak pada: ' . date('d F Y H:i:s') . '<br>
                        Total data: ' . count($laporan_data) . ' record
                    </div>
                </td>
                <td style="width: 50%; text-align: right;">
                    <div class="signature">
                        <div>Mengetahui,</div>
                        <div class="signature-space"></div>
                        <div class="signature-line">
                            (...........................)<br>
                            Kepala Sekolah
                        </div>
                    </div>
                </td>
            </tr>
        </table>
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

