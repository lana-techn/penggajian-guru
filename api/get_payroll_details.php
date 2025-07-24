<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

// Validasi parameter
if (!isset($_GET['id_guru'], $_GET['bulan'], $_GET['tahun'])) {
    echo json_encode(['error' => 'Parameter tidak lengkap (membutuhkan id_guru, bulan, tahun).']);
    http_response_code(400);
    exit;
}

$conn = db_connect();
$id_guru = $_GET['id_guru'];
$bulan = $_GET['bulan'];
$tahun = $_GET['tahun'];

// 1. Ambil data master guru dan jabatan
$stmt = $conn->prepare("
    SELECT 
        g.id_jabatan,
        g.tgl_masuk,
        g.status_kawin, 
        g.jml_anak, 
        j.gaji_awal as gaji_pokok
    FROM Guru g
    JOIN Jabatan j ON g.id_jabatan = j.id_jabatan
    WHERE g.id_guru = ?
");
$stmt->bind_param('s', $id_guru);
$stmt->execute();
$guru_data = $stmt->get_result()->fetch_assoc();

if (!$guru_data) {
    echo json_encode(['error' => 'Data guru tidak ditemukan.']);
    http_response_code(404);
    exit;
}

$id_jabatan = $guru_data['id_jabatan'];

// 2. Ambil data tunjangan & potongan berdasarkan jabatan
$tunjangan_data = $conn->execute_query("SELECT * FROM Tunjangan WHERE id_jabatan = ?", [$id_jabatan])->fetch_assoc() ?? [];
$potongan_data = $conn->execute_query("SELECT * FROM Potongan WHERE id_jabatan = ?", [$id_jabatan])->fetch_assoc() ?? [];

// 3. Ambil data rekap kehadiran
$kehadiran_data = $conn->execute_query("SELECT jml_terlambat FROM Rekap_Kehadiran WHERE id_guru = ? AND bulan = ? AND tahun = ?", [$id_guru, $bulan, $tahun])->fetch_assoc();
$jml_terlambat = $kehadiran_data['jml_terlambat'] ?? 0;

// --- MULAI PERHITUNGAN ---

$response = [];

// Gaji Pokok
$gaji_pokok = (float)($guru_data['gaji_pokok'] ?? 0);
$response['gaji_pokok'] = $gaji_pokok;

// Tunjangan dari tabel Tunjangan
$response['tunjangan_beras'] = (float)($tunjangan_data['tunjangan_beras'] ?? 0);
$tunjangan_suami_istri = 0;
if (in_array($guru_data['status_kawin'], ['Kawin', 'Menikah'])) {
    $tunjangan_suami_istri = (float)($tunjangan_data['tunjangan_suami_istri'] ?? 0);
}
$response['tunjangan_suami_istri'] = $tunjangan_suami_istri;
$jml_anak_tunjangan = min((int)($guru_data['jml_anak'] ?? 0), 2); // Tetap batasi maksimal 2 anak
$response['tunjangan_anak'] = $jml_anak_tunjangan * (float)($tunjangan_data['tunjangan_anak'] ?? 0);

// Tunjangan Kehadiran (tetap dihitung otomatis)
$response['tunjangan_kehadiran'] = ($jml_terlambat > 5) ? 0 : (100000 - ($jml_terlambat * 5000));

// Potongan dari tabel Potongan (sekarang persentase)
$persentase_bpjs = (float)($potongan_data['potongan_bpjs'] ?? 0);
$persentase_infak = (float)($potongan_data['infak'] ?? 0);

$response['potongan_bpjs'] = $gaji_pokok * ($persentase_bpjs / 100);
$response['infak'] = $gaji_pokok * ($persentase_infak / 100);

// Masa Kerja
$tgl_masuk = new DateTime($guru_data['tgl_masuk']);
$tgl_proses = new DateTime("$tahun-$bulan-01");
$response['masa_kerja'] = $tgl_masuk->diff($tgl_proses)->y;

// Kirim response
echo json_encode($response);

$conn->close();
