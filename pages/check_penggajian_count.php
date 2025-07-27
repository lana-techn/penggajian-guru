<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireAnyRole(['admin', 'kepala_sekolah']);

// Set header untuk JSON response
header('Content-Type: application/json');

// Validasi parameter
if (!isset($_GET['id_guru']) || empty(trim($_GET['id_guru']))) {
    echo json_encode([
        'success' => false,
        'error' => 'ID guru tidak valid'
    ]);
    exit;
}

$id_guru = trim($_GET['id_guru']);
$conn = db_connect();

try {
    // Cek apakah guru ada di database
    $check_guru = $conn->prepare("SELECT nama_guru FROM Guru WHERE id_guru = ?");
    $check_guru->bind_param("s", $id_guru);
    $check_guru->execute();
    $guru_result = $check_guru->get_result();
    
    if ($guru_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Guru tidak ditemukan'
        ]);
        exit;
    }
    
    $guru_data = $guru_result->fetch_assoc();
    $check_guru->close();
    
    // Hitung jumlah data penggajian
    $count_penggajian_stmt = $conn->prepare("SELECT COUNT(*) as total FROM penggajian WHERE id_guru = ?");
    $count_penggajian_stmt->bind_param("s", $id_guru);
    $count_penggajian_stmt->execute();
    $penggajian_result = $count_penggajian_stmt->get_result();
    $penggajian_count = $penggajian_result->fetch_assoc()['total'];
    $count_penggajian_stmt->close();
    
    // Hitung jumlah data rekap kehadiran
    $count_kehadiran_stmt = $conn->prepare("SELECT COUNT(*) as total FROM rekap_kehadiran WHERE id_guru = ?");
    $count_kehadiran_stmt->bind_param("s", $id_guru);
    $count_kehadiran_stmt->execute();
    $kehadiran_result = $count_kehadiran_stmt->get_result();
    $kehadiran_count = $kehadiran_result->fetch_assoc()['total'];
    $count_kehadiran_stmt->close();
    
    // Return hasil
    echo json_encode([
        'success' => true,
        'count' => (int)$penggajian_count,  // Untuk backward compatibility
        'penggajian_count' => (int)$penggajian_count,
        'kehadiran_count' => (int)$kehadiran_count,
        'total_related_data' => (int)$penggajian_count + (int)$kehadiran_count,
        'nama_guru' => $guru_data['nama_guru']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
