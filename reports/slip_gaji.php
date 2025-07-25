<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - <?= e($data['nama_guru']) ?></title>
    <style>
        body { font-family: "Arial", sans-serif; font-size: 11px; margin: 0; padding: 20px; background: white; }
        .slip-container { border: 2px solid #000; padding: 0; max-width: 600px; margin: 0 auto; }
        .kop { text-align: center; border-bottom: 2px solid #000; padding: 8px 8px 2px 8px; }
        .kop-logo { float: left; width: 70px; height: 70px; margin-right: 10px; border: 1px solid #888; display: inline-block; }
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
        @media print {
            body { margin: 0; padding: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: center;">
        <button onclick="window.print()" style="background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
            <i class="fa fa-print"></i> Cetak Slip Gaji
        </button>
        <button onclick="window.close()" style="background: #f44336; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
            <i class="fa fa-times"></i> Tutup
        </button>
    </div>

    <div class="slip-container">
        <div class="kop">
            <div class="kop-logo"></div>
            <div style="margin-left:80px;">
                <div class="kop-title">Yayasan Pimpinan Daerah Muhammadiyah (PDM) Kab. Bantul</div>
                <div class="kop-sub">SD Unggulan Muhammadiyah Kretek</div>
                <div class="kop-contact">Email : sdumuhkretek@gmail.com | Miryan Donotirto Kretek Bantul 55772<br>Website : http://www.sdmuhkretek.sch.id.</div>
            </div>
            <div class="clear"></div>
        </div>
        <div class="slip-title">SLIP GAJI GURU</div>
        <table class="info-table">
            <tr>
                <td>Nama</td><td>: <?= e($data['nama_guru']) ?></td>
                <td>Bulan Penggajian</td><td>: <?= e($bulan_text.' '.$tahun_text) ?></td>
            </tr>
            <tr>
                <td>NIPM</td><td>: <?= e($data['nipm']) ?></td>
                <td>Masa Kerja</td><td>: <?= e($data['masa_kerja']) ?> tahun</td>
            </tr>
            <tr>
                <td>Jabatan</td><td>: <?= e($data['nama_jabatan']) ?></td>
                <td>id penggajian/Tanggal</td><td>: <?= e($data['id_penggajian']) ?> / <?= date('d-m-Y', strtotime($data['tgl_input'])) ?></td>
            </tr>
        </table>
        <table class="section-table">
            <tr>
                <td class="section-title" colspan="2">PENGHASILAN:</td>
                <td class="section-title" colspan="2">POTONGAN:</td>
            </tr>
            <tr>
                <td>• Gaji Pokok</td><td>: Rp <?= number_format($data['gaji_pokok'] ?? 0, 2, ',', '.') ?></td>
                <td>• BPJS</td><td>: Rp <?= number_format($data['potongan_bpjs'] ?? 0, 2, ',', '.') ?></td>
            </tr>
            <tr>
                <td>• Tunjangan Beras</td><td>: Rp <?= number_format($data['tunjangan_beras'] ?? 0, 2, ',', '.') ?></td>
                <td>• Infak</td><td>: Rp <?= number_format($data['infak'] ?? 0, 2, ',', '.') ?></td>
            </tr>
            <tr>
                <td>• Tunjangan Kehadiran</td><td>: Rp <?= number_format($data['tunjangan_kehadiran'] ?? 0, 2, ',', '.') ?></td>
                <td></td><td></td>
            </tr>
            <tr>
                <td>• Tunjangan Suami/Istri</td><td>: Rp <?= number_format($data['tunjangan_suami_istri'] ?? 0, 2, ',', '.') ?></td>
                <td></td><td></td>
            </tr>
            <tr>
                <td>• Tunjangan Anak</td><td>: Rp <?= number_format($data['tunjangan_anak'] ?? 0, 2, ',', '.') ?></td>
                <td></td><td></td>
            </tr>
            <tr>
                <td colspan="2" style="border-top:1px solid #000;">Gaji Kotor</td>
                <td colspan="2" style="border-top:1px solid #000;">Total Potongan</td>
            </tr>
            <tr>
                <td colspan="2">: Rp <?= number_format($data['gaji_kotor'], 2, ',', '.') ?></td>
                <td colspan="2">: Rp <?= number_format($data['total_potongan'], 2, ',', '.') ?></td>
            </tr>
            <tr>
                <td colspan="4" style="border-top:1px solid #000;"></td>
            </tr>
            <tr>
                <td colspan="2"><b>Gaji Bersih</b></td>
                <td colspan="2"><b>: Rp <?= number_format($data['gaji_bersih'], 2, ',', '.') ?></b></td>
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
        </table>
    </div>

    <script>
        // Auto print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
