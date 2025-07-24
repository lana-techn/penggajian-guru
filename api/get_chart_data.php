<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Memuat fungsi inti dan memulai sesi
require_once __DIR__ . '/../includes/functions.php';

// Pastikan sesi dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan pengguna sudah login
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $conn = db_connect();

    $type = $_GET['type'] ?? '';

    switch ($type) {
        case 'guru_per_jabatan':
            $query = "SELECT j.nama_jabatan, COUNT(g.id_guru) as jumlah_guru 
                     FROM jabatan j 
                     LEFT JOIN guru g ON j.id_jabatan = g.id_jabatan 
                     GROUP BY j.id_jabatan, j.nama_jabatan 
                     ORDER BY jumlah_guru DESC";

            $result = $conn->query($query);

            if (!$result) {
                throw new Exception('Query gagal: ' . $conn->error);
            }

            $labels = [];
            $values = [];

            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['nama_jabatan'];
                $values[] = (int)$row['jumlah_guru'];
            }

            // Jika tidak ada data, berikan data default
            if (empty($labels)) {
                $labels = ['Belum Ada Jabatan'];
                $values = [0];
            }

            echo json_encode([
                'labels' => $labels,
                'values' => $values,
                'success' => true
            ]);
            break;

        case 'gaji_per_bulan':
            // Query untuk statistik gaji per bulan (contoh untuk fitur masa depan)
            $query = "SELECT 
                        MONTH(tanggal_penggajian) as bulan, 
                        YEAR(tanggal_penggajian) as tahun,
                        SUM(total_gaji) as total_gaji,
                        COUNT(*) as jumlah_guru
                      FROM penggajian 
                      WHERE YEAR(tanggal_penggajian) = YEAR(CURDATE())
                      GROUP BY YEAR(tanggal_penggajian), MONTH(tanggal_penggajian)
                      ORDER BY tahun DESC, bulan DESC
                      LIMIT 12";

            $result = $conn->query($query);

            if (!$result) {
                throw new Exception('Query gagal: ' . $conn->error);
            }

            $labels = [];
            $values = [];

            $bulan_indo = [
                1 => 'Jan',
                2 => 'Feb',
                3 => 'Mar',
                4 => 'Apr',
                5 => 'Mei',
                6 => 'Jun',
                7 => 'Jul',
                8 => 'Ags',
                9 => 'Sep',
                10 => 'Okt',
                11 => 'Nov',
                12 => 'Des'
            ];

            while ($row = $result->fetch_assoc()) {
                $labels[] = $bulan_indo[(int)$row['bulan']] . ' ' . $row['tahun'];
                $values[] = (float)$row['total_gaji'];
            }

            if (empty($labels)) {
                $labels = ['Belum Ada Data'];
                $values = [0];
            }

            echo json_encode([
                'labels' => $labels,
                'values' => $values,
                'success' => true
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Tipe chart tidak valid']);
            break;
    }

    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Terjadi kesalahan server',
        'message' => $e->getMessage(),
        'success' => false
    ]);
}
