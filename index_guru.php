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

<main class="flex-1 p-6 sm:p-8 bg-gray-50">
    <div class="bg-gradient-to-r from-green-600 to-emerald-700 text-white p-8 rounded-xl shadow-lg mb-8">
        <h1 class="text-3xl md:text-4xl font-bold font-poppins">Halo, <?= e(explode(' ', $nama_guru)[0]) ?>!</h1>
        <p class="mt-2 text-lg text-green-100">Selamat datang di dashboard pribadi Anda. Berikut adalah ringkasan informasi Anda.</p>
    </div>

    <?php display_flash_message(); ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-green-500 hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 bg-green-100 p-4 rounded-full">
                    <i class="fa-solid fa-money-bill-wave text-green-600 text-2xl"></i>
                </div>
                <div class="flex-1">
                    <p class="text-gray-500 text-sm font-medium">Gaji Terakhir Diterima</p>
                    <?php if ($gaji_terakhir): ?>
                        <p class="text-3xl font-bold text-gray-800 mt-1">Rp <?= number_format($gaji_terakhir['gaji_bersih'], 2, ',', '.') ?></p>
                        <p class="text-xs text-gray-500 mt-1">Periode: <?= e(date('F Y', strtotime($gaji_terakhir['bulan_penggajian']))) ?></p>
                    <?php else: ?>
                        <p class="text-lg font-semibold text-gray-700 mt-2">Belum ada data gaji yang dibayarkan.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-blue-500 hover:shadow-xl transition-shadow duration-300 flex flex-col justify-between">
            <div>
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0 bg-blue-100 p-4 rounded-full">
                        <i class="fa-solid fa-file-invoice-dollar text-blue-600 text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Akses Cepat</p>
                        <p class="text-xl font-bold text-gray-800 mt-1">Slip Gaji Anda</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-3">Lihat rincian pendapatan dan potongan gaji Anda kapan saja.</p>
            </div>
            <a href="pages/guru/slip_gaji.php" class="mt-4 block text-center bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-300">
                Lihat Detail Slip Gaji <i class="fa-solid fa-arrow-up-right-from-square ml-2"></i>
            </a>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-yellow-500 hover:shadow-xl transition-shadow duration-300">
             <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 bg-yellow-100 p-4 rounded-full">
                    <i class="fa-solid fa-circle-info text-yellow-600 text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm font-medium">Pusat Informasi</p>
                    <p class="text-xl font-bold text-gray-800 mt-1">Bantuan & Dukungan</p>
                    <p class="text-sm text-gray-600 mt-3">Hubungi admin atau bagian administrasi jika Anda memiliki pertanyaan terkait penggajian atau data pribadi.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>