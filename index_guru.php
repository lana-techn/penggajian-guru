<?php
$page_title = 'Dashboard Guru';
$current_page = 'dashboard';
require_once __DIR__ . '/includes/functions.php';
requireLogin('guru');

$conn = db_connect();

// Ambil data guru yang login
$nama_guru = 'Guru';
$guru_id = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt_guru = $conn->prepare("SELECT id_guru, nama_guru FROM Guru WHERE id_user = ?");
    $stmt_guru->bind_param("s", $user_id);
    $stmt_guru->execute();
    $guru_data = $stmt_guru->get_result()->fetch_assoc();
    $stmt_guru->close();
    if ($guru_data) {
        $nama_guru = $guru_data['nama_guru'];
        $guru_id = $guru_data['id_guru'];
    }
}

// Ambil data gaji terakhir yang sudah dibayar
$gaji_terakhir = null;
if ($guru_id) {
    $stmt_gaji = $conn->prepare(
        "SELECT bulan_penggajian, gaji_bersih, tgl_input 
         FROM Penggajian 
         WHERE id_guru = ? 
         ORDER BY tgl_input DESC LIMIT 1"
    );
    $stmt_gaji->bind_param("s", $guru_id);
    $stmt_gaji->execute();
    $gaji_terakhir = $stmt_gaji->get_result()->fetch_assoc();
    $stmt_gaji->close();
}

$conn->close();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 p-6 sm:p-8 bg-gray-50/50">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 font-poppins">Selamat Datang, <?= e(explode(' ', $nama_guru)[0]) ?>!</h1>
        <p class="mt-1 text-gray-500">Berikut adalah ringkasan informasi penggajian Anda.</p>
    </div>

    <?php display_flash_message(); ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Gaji Terakhir Diterima</p>
                        <?php if ($gaji_terakhir): ?>
                            <?php
                            // Mapping bulan Inggris ke Indonesia
                            $bulan_map = [
                                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                                '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                                '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                            ];
                            $bulan_penggajian = $gaji_terakhir['bulan_penggajian'];
                            $tahun_penggajian = date('Y', strtotime($gaji_terakhir['tgl_input']));
                            $nama_bulan = $bulan_map[$bulan_penggajian] ?? $bulan_penggajian;
                            ?>
                            <p class="text-4xl font-bold text-green-600 mt-2">Rp <?= number_format($gaji_terakhir['gaji_bersih'], 0, ',', '.') ?></p>
                            <p class="text-sm text-gray-500 mt-1">Periode: <?= e($nama_bulan . ' ' . $tahun_penggajian) ?></p>
                        <?php else: ?>
                            <p class="text-xl font-semibold text-gray-700 mt-4">Belum ada data gaji.</p>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4 sm:mt-0">
                        <a href="pages/guru/slip_gaji.php" class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors duration-300 shadow-sm">
                            <i class="fa-solid fa-file-invoice-dollar mr-2"></i>
                            Lihat Semua Slip Gaji
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-full">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0 bg-blue-100 p-3 rounded-full">
                        <i class="fa-solid fa-user-shield text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-gray-800">Profil & Bantuan</p>
                        <p class="text-sm text-gray-500">Pusat informasi dan data diri</p>
                    </div>
                </div>
                <div class="mt-6 text-sm text-gray-700 space-y-3">
                     <p>
                        <span class="font-semibold">Nama:</span> <?= e($nama_guru) ?>
                    </p>
                    <p>
                        <span class="font-semibold">ID Guru:</span> <?= e($guru_id ?? 'N/A') ?>
                    </p>
                    <p class="pt-3 border-t border-gray-200 mt-4">
                        Jika ada pertanyaan terkait data gaji atau informasi lainnya, silakan hubungi bagian <span class="font-semibold text-blue-700">Administrasi Sekolah</span>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>