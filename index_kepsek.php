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

// 4. Status Penerima Tunjangan (untuk chart)
$penerima_tunjangan_data = $conn->query("SELECT 
                                            CASE 
                                                WHEN status_kawin = 'Menikah' OR jml_anak > 0 THEN 'Penerima Tunjangan'
                                                ELSE 'Non-Penerima' 
                                            END as kategori, 
                                            COUNT(id_guru) as jumlah 
                                        FROM Guru 
                                        GROUP BY kategori")->fetch_all(MYSQLI_ASSOC);

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
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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
                <p class="text-sm text-gray-500 font-medium">Total Pengguna Sistem</p>
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
         <a href="pages/users.php" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4 hover:bg-orange-50 transition-colors">
            <div class="bg-orange-100 p-4 rounded-full">
                <i class="fa-solid fa-user-gear fa-2x text-orange-600"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Manajemen Pengguna</p>
                <p class="text-lg font-bold text-orange-700">Kelola User</p>
            </div>
        </a>
    </div>

    <!-- Charts and Quick Access -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
        <!-- Charts Column -->
        <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Komposisi Jabatan Guru</h3>
                <div class="h-64 relative">
                    <canvas id="jabatanChart"></canvas>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Distribusi Penerima Tunjangan</h3>
                <div class="h-64 relative">
                    <canvas id="penerimaTunjanganChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Access Column -->
        <div class="lg:col-span-2">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-full">
                <h3 class="text-xl font-bold text-gray-800 mb-5">Akses Cepat & Manajemen</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-4">
                    <a href="pages/kepala_sekolah/laporan.php" class="p-4 rounded-xl bg-gray-50 hover:bg-indigo-50 hover:border-indigo-200 border border-transparent transition-all group">
                        <i class="fa-solid fa-chart-line text-2xl text-indigo-600 mb-2"></i>
                        <p class="font-bold text-gray-800 group-hover:text-indigo-800">Laporan Penggajian</p>
                        <p class="text-xs text-gray-500">Lihat & unduh laporan gaji</p>
                    </a>
                    <a href="pages/guru.php" class="p-4 rounded-xl bg-gray-50 hover:bg-green-50 hover:border-green-200 border border-transparent transition-all group">
                        <i class="fa-solid fa-users-viewfinder text-2xl text-green-600 mb-2"></i>
                        <p class="font-bold text-gray-800 group-hover:text-green-800">Manajemen Guru</p>
                        <p class="text-xs text-gray-500">Kelola data master guru</p>
                    </a>
                    <a href="pages/users.php" class="p-4 rounded-xl bg-gray-50 hover:bg-purple-50 hover:border-purple-200 border border-transparent transition-all group">
                        <i class="fa-solid fa-users-gear text-2xl text-purple-600 mb-2"></i>
                        <p class="font-bold text-gray-800 group-hover:text-purple-800">Manajemen Pengguna</p>
                        <p class="text-xs text-gray-500">Atur hak akses sistem</p>
                    </a>
                     <a href="pages/kepala_sekolah/ubah_password.php" class="p-4 rounded-xl bg-gray-50 hover:bg-orange-50 hover:border-orange-200 border border-transparent transition-all group">
                        <i class="fa-solid fa-key text-2xl text-orange-600 mb-2"></i>
                        <p class="font-bold text-gray-800 group-hover:text-orange-800">Keamanan Akun</p>
                        <p class="text-xs text-gray-500">Ubah password Anda</p>
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
    const penerimaTunjanganData = <?= json_encode($penerima_tunjangan_data) ?>;

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

    // --- Chart 2: Penerima Tunjangan ---
    if (document.getElementById('penerimaTunjanganChart')) {
        const ctx = document.getElementById('penerimaTunjanganChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: penerimaTunjanganData.map(d => d.kategori),
                datasets: [{
                    label: 'Jumlah Guru',
                    data: penerimaTunjanganData.map(d => d.jumlah),
                    backgroundColor: '#60A5FA',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>
