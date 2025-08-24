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

// 1. Ambil data master guru, jabatan, dan tunjangan dengan relasi langsung
$stmt = $conn->prepare("
    SELECT 
        g.id_jabatan,
        g.id_tunjangan,
        g.tgl_masuk,
        g.status_kawin, 
        g.jml_anak, 
        j.gaji_awal,
        t.tunjangan_beras,
        t.tunjangan_kehadiran,
        t.tunjangan_suami_istri,
        t.tunjangan_anak as tunjangan_anak_per_anak
    FROM Guru g
    JOIN Jabatan j ON g.id_jabatan = j.id_jabatan
    LEFT JOIN Tunjangan t ON g.id_tunjangan = t.id_tunjangan
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

// 2. Ambil data kehadiran
$kehadiran_stmt = $conn->prepare("SELECT jml_terlambat FROM Rekap_Kehadiran WHERE id_guru = ? AND bulan = ? AND tahun = ?");
$kehadiran_stmt->bind_param('sss', $id_guru, $bulan, $tahun);
$kehadiran_stmt->execute();
$kehadiran_data = $kehadiran_stmt->get_result()->fetch_assoc();
$jml_terlambat = $kehadiran_data['jml_terlambat'] ?? 0;

// --- MULAI PERHITUNGAN ---
// Menggunakan helper functions untuk memastikan konsistensi dengan proses_gaji.php

$response = [];

// Masa Kerja
$tgl_masuk = new DateTime($guru_data['tgl_masuk']);
$tgl_proses = new DateTime("$tahun-$bulan-01");
$masa_kerja_tahun = $tgl_masuk->diff($tgl_proses)->y;
$response['masa_kerja'] = $masa_kerja_tahun;

// Gaji Pokok (Gaji Awal + Kenaikan per Tahun)
$gaji_awal = (float)($guru_data['gaji_awal'] ?? 0);
$kenaikan_tahunan = 50000;
$gaji_pokok = $gaji_awal + ($masa_kerja_tahun * $kenaikan_tahunan);
$response['gaji_pokok'] = $gaji_pokok;

// Tunjangan dari database
// a) Tunjangan Beras
$response['tunjangan_beras'] = (float)($guru_data['tunjangan_beras'] ?? 50000);

// b) Tunjangan Kehadiran
$response['tunjangan_kehadiran'] = (float)($guru_data['tunjangan_kehadiran'] ?? 100000);

// c) Tunjangan Suami/Istri
$tunjangan_suami_istri = 0;
if (in_array($guru_data['status_kawin'], ['Kawin', 'Menikah', 'menikah'])) {
    $tunjangan_suami_istri = (float)($guru_data['tunjangan_suami_istri'] ?? 0);
}
$response['tunjangan_suami_istri'] = $tunjangan_suami_istri;

// d) Tunjangan Anak (dari database per anak, maksimal 2 anak)
$jml_anak = min((int)($guru_data['jml_anak'] ?? 0), 2);
$tunjangan_per_anak = (float)($guru_data['tunjangan_anak_per_anak'] ?? 100000);
$response['tunjangan_anak'] = $jml_anak * $tunjangan_per_anak;

// Potongan Terlambat
$response['potongan_terlambat'] = calculate_potongan_terlambat($jml_terlambat);

// Potongan BPJS dan Infak (default 2% each)
$persentase_bpjs = 2.0;
$persentase_infak = 2.0;
$potongan = calculate_potongan($gaji_pokok, $persentase_bpjs, $persentase_infak);
$response['potongan_bpjs'] = $potongan['potongan_bpjs'];
$response['infak'] = $potongan['infak'];

// Kirim response
echo json_encode($response);

$conn->close();