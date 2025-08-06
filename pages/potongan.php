<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole(['admin', 'kepala_sekolah']);

$page_title = 'Informasi Persentase Potongan';

// Nilai potongan sekarang bersifat tetap (hardcoded) dan tidak lagi diambil dari database.
$potongan_data = [
    'potongan_bpjs' => 2.0,
    'infak' => 2.0
];

// Tidak memerlukan koneksi database atau token CSRF untuk halaman ini.
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 font-poppins"><?= e($page_title) ?></h1>
            <p class="text-gray-500 mt-1">Lihat persentase potongan gaji tetap yang berlaku untuk semua guru.</p>
        </div>
    </div>

    <?php
    // Tetap tampilkan flash message jika ada redirect dari halaman lain
    display_flash_message(); 
    ?>

    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg">
        <h3 class="text-xl font-bold text-gray-800 mb-2 font-poppins">Pengaturan Potongan Tetap</h3>
        
        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-4 rounded-md mb-6">
            <h4 class="font-bold">Informasi</h4>
            <p class="text-sm mt-1">
                Nilai di bawah ini adalah <strong>persentase (%)</strong> tetap yang dikalikan dengan <strong>gaji pokok</strong> setiap guru saat proses perhitungan gaji. 
                <br>Nilai ini bersifat global dan tidak dapat diubah melalui sistem.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-lg">
            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <p class="text-sm font-medium text-gray-500">Potongan BPJS</p>
                <p class="text-3xl font-bold text-gray-800 mt-2"><?= number_format($potongan_data['potongan_bpjs'], 2, ',', '.') ?>%</p>
            </div>
            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <p class="text-sm font-medium text-gray-500">Potongan Infak</p>
                <p class="text-3xl font-bold text-gray-800 mt-2"><?= number_format($potongan_data['infak'], 2, ',', '.') ?>%</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
