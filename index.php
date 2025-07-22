<?php
$page_title = 'Dashboard Admin';
$current_page = 'dashboard';
require_once __DIR__ . '/includes/functions.php';
requireLogin('admin');

$conn = db_connect();

// Hitung jumlah pengguna
$total_pengguna = $conn->query("SELECT COUNT(id_user) as total FROM User")->fetch_assoc()['total'] ?? 0;

// Hitung jumlah guru aktif
$total_guru = $conn->query("SELECT COUNT(id_guru) as total FROM Guru")->fetch_assoc()['total'] ?? 0;

// Hitung jumlah jabatan
$total_jabatan = $conn->query("SELECT COUNT(id_jabatan) as total FROM Jabatan")->fetch_assoc()['total'] ?? 0;

$conn->close();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 p-6 sm:p-8 bg-gray-50">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 font-poppins">Dashboard Admin</h1>
            <p class="text-gray-500 mt-1">Selamat datang kembali, <?= e($_SESSION['username'] ?? 'Admin') ?>!</p>
        </div>
        <div class="text-sm text-gray-600 bg-white px-4 py-2 rounded-lg shadow-sm mt-4 sm:mt-0">
            <i class="fa-solid fa-calendar-day mr-2 text-green-600"></i>
            <?= date('l, d F Y') ?>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-6 rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-lg font-medium text-green-100">Total Pengguna</p>
                    <p class="text-4xl font-bold"><?= e($total_pengguna) ?></p>
                </div>
                <div class="bg-white/30 p-4 rounded-full">
                    <i class="fas fa-user-shield fa-2x text-white"></i>
                </div>
            </div>
            <a href="pages/users.php" class="inline-block mt-4 text-sm text-green-50 bg-green-900/30 px-3 py-1 rounded-full hover:bg-green-900/50 transition-colors">Kelola Pengguna <i class="fa-solid fa-arrow-right-long ml-1"></i></a>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-lg font-medium text-blue-100">Guru Aktif</p>
                    <p class="text-4xl font-bold"><?= e($total_guru) ?></p>
                </div>
                <div class="bg-white/30 p-4 rounded-full">
                    <i class="fas fa-users fa-2x text-white"></i>
                </div>
            </div>
             <a href="pages/guru.php" class="inline-block mt-4 text-sm text-blue-50 bg-blue-900/30 px-3 py-1 rounded-full hover:bg-blue-900/50 transition-colors">Kelola Guru <i class="fa-solid fa-arrow-right-long ml-1"></i></a>
        </div>
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white p-6 rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-lg font-medium text-indigo-100">Total Jabatan</p>
                    <p class="text-4xl font-bold"><?= e($total_jabatan) ?></p>
                </div>
                <div class="bg-white/30 p-4 rounded-full">
                     <i class="fas fa-briefcase fa-2x text-white"></i>
                </div>
            </div>
             <a href="pages/jabatan.php" class="inline-block mt-4 text-sm text-indigo-50 bg-indigo-900/30 px-3 py-1 rounded-full hover:bg-indigo-900/50 transition-colors">Kelola Jabatan <i class="fa-solid fa-arrow-right-long ml-1"></i></a>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-md">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Distribusi Guru per Jabatan</h3>
        <div class="h-80 relative">
            <canvas id="jabatanChart"></canvas>
        </div>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('jabatanChart').getContext('2d');
    fetch('api/get_chart_data.php?type=guru_per_jabatan')
        .then(response => response.json())
        .then(data => {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Jumlah Guru',
                        data: data.values,
                        backgroundColor: [
                            '#10B981', '#3B82F6', '#8B5CF6', '#F59E0B', 
                            '#EC4899', '#6366F1', '#14B8A6'
                        ],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        title: {
                            display: true,
                            text: 'Jumlah Guru Aktif Berdasarkan Jabatan'
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error fetching chart data:', error));
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>