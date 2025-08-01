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
        j.gaji_awal
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

// 2. Ambil data tunjangan dan potongan
$tunjangan_stmt = $conn->prepare("SELECT * FROM Tunjangan WHERE id_jabatan = ?");
$tunjangan_stmt->bind_param('s', $id_jabatan);
$tunjangan_stmt->execute();
$tunjangan_data = $tunjangan_stmt->get_result()->fetch_assoc() ?? [];

$potongan_stmt = $conn->prepare("SELECT * FROM Potongan WHERE id_jabatan = ? ORDER BY id_potongan DESC LIMIT 1");
$potongan_stmt->bind_param('s', $id_jabatan);
$potongan_stmt->execute();
$potongan_data = $potongan_stmt->get_result()->fetch_assoc() ?? [];

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

// Tunjangan
// a) Tunjangan Beras (Nilai Tetap)
$response['tunjangan_beras'] = 50000;

// c) Tunjangan Suami/Istri (Dari DB berdasarkan Jabatan)
$tunjangan_suami_istri = 0;
if (in_array($guru_data['status_kawin'], ['Kawin', 'Menikah', 'menikah'])) {
    $tunjangan_suami_istri = (float)($tunjangan_data['tunjangan_suami_istri'] ?? 0);
}
$response['tunjangan_suami_istri'] = $tunjangan_suami_istri;

// d) Tunjangan Anak (Nilai Tetap per anak, maks 2 anak)
$response['tunjangan_anak'] = calculate_tunjangan_anak($guru_data['jml_anak'] ?? 0);

// b) Tunjangan Kehadiran (Dihitung Otomatis)
$response['tunjangan_kehadiran'] = calculate_tunjangan_kehadiran($jml_terlambat);

// Potongan (Ambil dari database dengan fallback ke default)
$persentase_bpjs = (float)($potongan_data['potongan_bpjs'] ?? 2); // Default 2% jika tidak ada di DB
$persentase_infak = (float)($potongan_data['infak'] ?? 2); // Default 2% jika tidak ada di DB

// Hitung potongan menggunakan fungsi helper
$potongan = calculate_potongan($gaji_pokok, $persentase_bpjs, $persentase_infak);
$response['potongan_bpjs'] = $potongan['potongan_bpjs'];
$response['infak'] = $potongan['infak'];

// Kirim response
echo json_encode($response);

$conn->close();