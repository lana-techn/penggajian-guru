<?php
$page_title = 'Dashboard Kepala Sekolah';
$current_page = 'dashboard';
require_once __DIR__ . '/includes/functions.php';
requireLogin('kepala_sekolah');

$conn = db_connect();

// --- METRICS --- 

// 1. Total Guru Aktif
$total_guru = $conn->query("SELECT COUNT(id_guru) as total FROM Guru")->fetch_assoc()['total'] ?? 0;

// 2. Total Pengguna Sistem
$total_users = $conn->query("SELECT COUNT(id_user) as total FROM User")->fetch_assoc()['total'] ?? 0;

// 3. Jabatan Guru (untuk chart)
$jabatan_data = $conn->query("SELECT nama_jabatan, COUNT(g.id_guru) as jumlah 
                               FROM Jabatan j 
                               LEFT JOIN Guru g ON j.id_jabatan = g.id_jabatan
                               GROUP BY j.nama_jabatan 
                               ORDER BY jumlah DESC")->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 p-6 sm:p-8 bg-gray-50/50">
    <div class="flex flex-col sm:flex-row justify-between items-start mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 font-poppins">Dashboard Kepala Sekolah</h1>
            <p class="mt-1 text-gray-500">Ringkasan data strategis dan kepegawaian sekolah.</p>
        </div>
        <div class="mt-4 sm:mt-0 text-sm font-medium text-gray-600 bg-white px-4 py-2 rounded-lg border border-gray-200 shadow-sm">
            <i class="fa-solid fa-calendar-alt mr-2 text-indigo-500"></i>
            <?= date('l, d F Y') ?>
        </div>
    </div>

    <?php display_flash_message(); ?>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
            <div class="bg-green-100 p-4 rounded-full">
                <i class="fas fa-users fa-2x text-green-600"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Total Guru Aktif</p>
                <p class="text-3xl font-bold text-gray-800"><?= e($total_guru) ?></p>
            </div>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
            <div class="bg-purple-100 p-4 rounded-full">
                <i class="fas fa-user-shield fa-2x text-purple-600"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Total User</p>
                <p class="text-3xl font-bold text-gray-800"><?= e($total_users) ?></p>
            </div>
        </div>
        <a href="pages/kepala_sekolah/laporan.php" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4 hover:bg-blue-50 transition-colors">
            <div class="bg-blue-100 p-4 rounded-full">
                <i class="fa-solid fa-file-invoice-dollar fa-2x text-blue-600"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Laporan Gaji</p>
                <p class="text-lg font-bold text-blue-700">Akses Laporan</p>
            </div>
        </a>
    </div>

    <!-- Chart and Quick Access -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Chart Card -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Komposisi Jabatan Guru</h3>
            <div class="h-80 relative">
                <canvas id="jabatanChart"></canvas>
            </div>
        </div>

        <!-- Quick Access Column -->
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-full">
                <h3 class="text-xl font-bold text-gray-800 mb-5">Akses Cepat</h3>
                <div class="grid grid-cols-1 gap-4">
                    <a href="pages/kepala_sekolah/laporan.php" class="p-4 rounded-xl bg-gray-50 hover:bg-indigo-50 hover:border-indigo-200 border border-transparent transition-all group">
                        <i class="fa-solid fa-chart-line text-2xl text-indigo-600 mb-2"></i>
                        <p class="font-bold text-gray-800 group-hover:text-indigo-800">Laporan Penggajian</p>
                        <p class="text-xs text-gray-500">Lihat & unduh laporan gaji</p>
                    </a>
                    <a href="pages/guru.php" class="p-4 rounded-xl bg-gray-50 hover:bg-green-50 hover:border-green-200 border border-transparent transition-all group">
                        <i class="fa-solid fa-users-viewfinder text-2xl text-green-600 mb-2"></i>
                        <p class="font-bold text-gray-800 group-hover:text-green-800">Manajemen Guru</p>
                        <p class="text-xs text-gray-500">Lihat data guru</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data from PHP
    const jabatanData = <?= json_encode($jabatan_data) ?>;

    // --- Chart 1: Komposisi Jabatan --- 
    if (document.getElementById('jabatanChart')) {
        const ctx = document.getElementById('jabatanChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: jabatanData.map(d => d.nama_jabatan),
                datasets: [{
                    data: jabatanData.map(d => d.jumlah),
                    backgroundColor: ['#34D399', '#60A5FA', '#A78BFA', '#FBBF24', '#F87171'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 11 } }
                    }
                }
            }
        });
    }
});
</script>
