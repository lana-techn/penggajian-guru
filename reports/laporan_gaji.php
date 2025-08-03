<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');

$conn = db_connect();

// Ambil parameter filter
$filter_karyawan = $_GET['karyawan'] ?? '';
$filter_bulan = $_GET['bulan'] ?? '';
$filter_tahun = $_GET['tahun'] ?? date('Y');

$bulan_list = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

// Build query
$sql = "SELECT p.id_penggajian, p.no_slip_gaji, g.nama_guru, j.nama_jabatan, p.tgl_input, p.bulan_penggajian,
               p.gaji_pokok, p.tunjangan_beras, p.tunjangan_kehadiran, p.tunjangan_suami_istri, p.tunjangan_anak,
               p.gaji_kotor, p.potongan_bpjs, p.infak, p.total_potongan, p.gaji_bersih
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

// Build title berdasarkan filter
$title_filter = '';
if (!empty($filter_karyawan)) {
    $guru_name = $conn->query("SELECT nama_guru FROM Guru WHERE id_guru = '$filter_karyawan'")->fetch_assoc()['nama_guru'];
    $title_filter .= $guru_name . ' - ';
}
if (!empty($filter_bulan)) {
    $title_filter .= $bulan_list[intval($filter_bulan)] . ' ';
}
if (!empty($filter_tahun)) {
    $title_filter .= $filter_tahun;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Gaji Guru - <?= $title_filter ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
            font-size: 12px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            font-weight: normal;
        }
        .header .address {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .report-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .report-info div {
            flex: 1;
        }
        .summary-box {
            background-color: #e8f5e8;
            border: 2px solid #28a745;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary-box h3 {
            margin: 0 0 10px 0;
            color: #155724;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-label {
            font-size: 11px;
            color: #155724;
            margin-bottom: 5px;
        }
        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #155724;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .status-valid { 
            background-color: #d4edda; 
            color: #155724; 
            padding: 2px 6px; 
            border-radius: 3px; 
            font-size: 9px;
        }
        .status-pending { 
            background-color: #fff3cd; 
            color: #856404; 
            padding: 2px 6px; 
            border-radius: 3px; 
            font-size: 9px;
        }
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .signature {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 80px;
            padding-top: 5px;
        }
        .print-info {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        @media print {
            body { margin: 0; padding: 10px; }
            .no-print { display: none; }
        }
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">
            📄 Cetak Laporan
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            ❌ Tutup
        </button>
    </div>

    <div class="header">
        <h1>Laporan Gaji Guru</h1>
        <h2>SD Negeri Example</h2>
        <div class="address">Jl. Pendidikan No. 123, Kota, Provinsi</div>
    </div>

    <div class="report-info">
        <div>
            <strong>Periode Laporan:</strong> <?= $title_filter ?: 'Semua Data' ?>
        </div>
        <div>
            <strong>Tanggal Cetak:</strong> <?= date('d F Y, H:i:s') ?>
        </div>
        <div>
            <strong>Total Data:</strong> <?= count($data_rows) ?> record
        </div>
    </div>

    <?php if (count($data_rows) > 0): ?>
        <div class="summary-box">
            <h3>Ringkasan Penggajian</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Total Gaji Kotor</div>
                    <div class="summary-value">Rp <?= number_format($total_gaji_kotor, 0, ',', '.') ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Potongan</div>
                    <div class="summary-value">Rp <?= number_format($total_potongan, 0, ',', '.') ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Gaji Bersih</div>
                    <div class="summary-value">Rp <?= number_format($total_gaji_bersih, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 3%;">No</th>
                    <th style="width: 8%;">No Slip</th>
                    <th style="width: 12%;">Nama Guru</th>
                    <th style="width: 8%;">Periode</th>
                    <th style="width: 8%;">Gaji Pokok</th>
                    <th style="width: 8%;">Tunj. Beras</th>
                    <th style="width: 8%;">Tunj. Hadir</th>
                    <th style="width: 8%;">Tunj. Suami/Istri</th>
                    <th style="width: 8%;">Tunj. Anak</th>
                    <th style="width: 8%;">Gaji Kotor</th>
                    <th style="width: 6%;">BPJS</th>
                    <th style="width: 6%;">Infak</th>
                    <th style="width: 8%;">Total Potongan</th>
                    <th style="width: 8%;">Gaji Bersih</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($data_rows as $row): 
                ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center" style="font-family: monospace; font-size: 10px;">
                            <?= e($row['no_slip_gaji'] ?? 'SG' . date('ym') . str_pad($no-1, 4, '0', STR_PAD_LEFT)) ?>
                        </td>
                        <td><?= e($row['nama_guru']) ?></td>
                        <td class="text-center"><?= $bulan_list[intval($row['bulan_penggajian'])] ?? $row['bulan_penggajian'] ?> <?= date('Y', strtotime($row['tgl_input'])) ?></td>
                        <td class="text-right">Rp <?= number_format($row['gaji_pokok'], 0, ',', '.') ?></td>
                        <td class="text-right">Rp <?= number_format($row['tunjangan_beras'], 0, ',', '.') ?></td>
                        <td class="text-right">Rp <?= number_format($row['tunjangan_kehadiran'], 0, ',', '.') ?></td>
                        <td class="text-right">Rp <?= number_format($row['tunjangan_suami_istri'], 0, ',', '.') ?></td>
                        <td class="text-right">Rp <?= number_format($row['tunjangan_anak'], 0, ',', '.') ?></td>
                        <td class="text-right">Rp <?= number_format($row['gaji_kotor'], 0, ',', '.') ?></td>
                        <td class="text-right">Rp <?= number_format($row['potongan_bpjs'], 0, ',', '.') ?></td>
                        <td class="text-right">Rp <?= number_format($row['infak'], 0, ',', '.') ?></td>
                        <td class="text-right">Rp <?= number_format($row['total_potongan'], 0, ',', '.') ?></td>
                        <td class="text-right font-bold">Rp <?= number_format($row['gaji_bersih'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td colspan="4" class="text-center">TOTAL</td>
                    <td class="text-right">Rp <?= number_format(array_sum(array_column($data_rows, 'gaji_pokok')), 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format(array_sum(array_column($data_rows, 'tunjangan_beras')), 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format(array_sum(array_column($data_rows, 'tunjangan_kehadiran')), 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format(array_sum(array_column($data_rows, 'tunjangan_suami_istri')), 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format(array_sum(array_column($data_rows, 'tunjangan_anak')), 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format($total_gaji_kotor, 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format(array_sum(array_column($data_rows, 'potongan_bpjs')), 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format(array_sum(array_column($data_rows, 'infak')), 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format($total_potongan, 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format($total_gaji_bersih, 0, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align: center; padding: 50px; color: #666;">
            <h3>Tidak ada data yang ditemukan</h3>
            <p>Silakan sesuaikan filter pencarian atau tambah data gaji baru.</p>
        </div>
    <?php endif; ?>

    <div class="footer">
        <div class="signature">
            <p>Mengetahui,<br>Kepala Sekolah</p>
            <div class="signature-line">
                (.................................)
            </div>
        </div>
        <div class="signature">
            <p>Dibuat oleh,<br>Admin</p>
            <div class="signature-line">
                <?= e($_SESSION['username'] ?? 'Administrator') ?>
            </div>
        </div>
    </div>

    <div class="print-info">
        <p><strong>Laporan ini dicetak pada:</strong> <?= date('d F Y, H:i:s') ?> WIB</p>
        <p><em>Dokumen ini adalah hasil cetak sistem dan tidak memerlukan tanda tangan basah</em></p>
    </div>
</body>
</html>
