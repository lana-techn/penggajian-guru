<?php
$page_title = 'Dashboard Admin';
$current_page = 'dashboard';
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: auth/login.php');
    exit;
}

// Redirect users to their appropriate dashboard based on role
$user_role = strtolower($_SESSION['role'] ?? '');
if ($user_role === 'kepala_sekolah') {
    header('Location: index_kepsek.php');
    exit;
} elseif ($user_role === 'guru') {
    header('Location: index_guru.php');
    exit;
} elseif ($user_role !== 'admin') {
    // If role is not recognized, logout and redirect to login
    header('Location: auth/logout.php');
    exit;
}

// Only admin should reach this point
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

<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-green-50 p-6 sm:p-8 rounded-lg shadow-lg">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 lg:mb-8 space-y-4 lg:space-y-0">
        <div class="flex-1">
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-800 font-poppins mb-2">
                <span class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">Dashboard Admin</span>
            </h1>
            <p class="text-gray-600 text-sm sm:text-base">Selamat datang kembali, <span class="font-semibold text-green-700"><?= e($_SESSION['username'] ?? 'Admin') ?></span>!</p>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">Kelola sistem penggajian guru dengan mudah dan efisien</p>
        </div>
        <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
            <div class="flex items-center text-sm text-gray-600 bg-white px-3 sm:px-4 py-2 rounded-lg shadow-sm border border-gray-100">
                <i class="fa-solid fa-calendar-day mr-2 text-green-600"></i>
                <span class="font-medium"><?= date('l, d F Y') ?></span>
            </div>
            <div class="flex items-center text-sm text-gray-600 bg-white px-3 sm:px-4 py-2 rounded-lg shadow-sm border border-gray-100">
                <i class="fa-solid fa-clock mr-2 text-blue-600"></i>
                <span id="current-time" class="font-medium"></span>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 lg:mb-8">
        <!-- Total Pengguna Card -->
        <div class="group bg-gradient-to-br from-green-500 to-green-600 text-white p-4 sm:p-6 rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 border border-green-400/20">
            <div class="flex justify-between items-start mb-4">
                <div class="flex-1 min-w-0">
                    <p class="text-sm sm:text-base font-medium text-green-100 mb-1">Total Pengguna</p>
                    <p class="text-2xl sm:text-3xl lg:text-4xl font-bold truncate"><?= e($total_pengguna) ?></p>
                    <p class="text-xs text-green-200 mt-1">Pengguna terdaftar</p>
                </div>
                <div class="bg-white/20 backdrop-blur-sm p-3 sm:p-4 rounded-full group-hover:bg-white/30 transition-colors flex-shrink-0">
                    <i class="fas fa-user-shield text-xl sm:text-2xl text-white"></i>
                </div>
            </div>
            <a href="<?= url('pages/users.php') ?>" class="inline-flex items-center text-xs sm:text-sm text-green-50 bg-green-900/30 px-3 py-1.5 rounded-full hover:bg-green-900/50 transition-colors group-hover:bg-green-900/40">
                <span>Kelola Pengguna</span>
                <i class="fa-solid fa-arrow-right-long ml-2 group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>

        <!-- Guru Aktif Card -->
        <div class="group bg-gradient-to-br from-blue-500 to-blue-600 text-white p-4 sm:p-6 rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 border border-blue-400/20">
            <div class="flex justify-between items-start mb-4">
                <div class="flex-1 min-w-0">
                    <p class="text-sm sm:text-base font-medium text-blue-100 mb-1">Guru Aktif</p>
                    <p class="text-2xl sm:text-3xl lg:text-4xl font-bold truncate"><?= e($total_guru) ?></p>
                    <p class="text-xs text-blue-200 mt-1">Guru terdaftar</p>
                </div>
                <div class="bg-white/20 backdrop-blur-sm p-3 sm:p-4 rounded-full group-hover:bg-white/30 transition-colors flex-shrink-0">
                    <i class="fas fa-users text-xl sm:text-2xl text-white"></i>
                </div>
            </div>
            <a href="pages/guru.php" class="inline-flex items-center text-xs sm:text-sm text-blue-50 bg-blue-900/30 px-3 py-1.5 rounded-full hover:bg-blue-900/50 transition-colors group-hover:bg-blue-900/40">
                <span>Kelola Guru</span>
                <i class="fa-solid fa-arrow-right-long ml-2 group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>

        <!-- Total Jabatan Card -->
        <div class="group bg-gradient-to-br from-indigo-500 to-indigo-600 text-white p-4 sm:p-6 rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 border border-indigo-400/20 sm:col-span-2 lg:col-span-1">
            <div class="flex justify-between items-start mb-4">
                <div class="flex-1 min-w-0">
                    <p class="text-sm sm:text-base font-medium text-indigo-100 mb-1">Total Jabatan</p>
                    <p class="text-2xl sm:text-3xl lg:text-4xl font-bold truncate"><?= e($total_jabatan) ?></p>
                    <p class="text-xs text-indigo-200 mt-1">Jabatan tersedia</p>
                </div>
                <div class="bg-white/20 backdrop-blur-sm p-3 sm:p-4 rounded-full group-hover:bg-white/30 transition-colors flex-shrink-0">
                    <i class="fas fa-briefcase text-xl sm:text-2xl text-white"></i>
                </div>
            </div>
            <a href="pages/jabatan.php" class="inline-flex items-center text-xs sm:text-sm text-indigo-50 bg-indigo-900/30 px-3 py-1.5 rounded-full hover:bg-indigo-900/50 transition-colors group-hover:bg-indigo-900/40">
                <span>Kelola Jabatan</span>
                <i class="fa-solid fa-arrow-right-long ml-2 group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4 mb-6 lg:mb-8">
        <a href="pages/guru.php" class="group flex flex-col items-center p-3 sm:p-4 bg-white rounded-xl shadow-sm hover:shadow-md border border-gray-100 hover:border-green-200 transition-all duration-200">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-xl flex items-center justify-center group-hover:bg-green-200 transition-colors mb-2 sm:mb-3">
                <i class="fas fa-users text-green-600 text-lg sm:text-xl"></i>
            </div>
            <span class="text-xs sm:text-sm font-medium text-gray-700 text-center">Data Guru</span>
        </a>

        <a href="pages/jabatan.php" class="group flex flex-col items-center p-3 sm:p-4 bg-white rounded-xl shadow-sm hover:shadow-md border border-gray-100 hover:border-blue-200 transition-all duration-200">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-xl flex items-center justify-center group-hover:bg-blue-200 transition-colors mb-2 sm:mb-3">
                <i class="fas fa-briefcase text-blue-600 text-lg sm:text-xl"></i>
            </div>
            <span class="text-xs sm:text-sm font-medium text-gray-700 text-center">Jabatan</span>
        </a>

        <a href="pages/tunjangan.php" class="group flex flex-col items-center p-3 sm:p-4 bg-white rounded-xl shadow-sm hover:shadow-md border border-gray-100 hover:border-indigo-200 transition-all duration-200">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-indigo-100 rounded-xl flex items-center justify-center group-hover:bg-indigo-200 transition-colors mb-2 sm:mb-3">
                <i class="fas fa-plus-circle text-indigo-600 text-lg sm:text-xl"></i>
            </div>
            <span class="text-xs sm:text-sm font-medium text-gray-700 text-center">Tunjangan</span>
        </a>

        <a href="pages/potongan.php" class="group flex flex-col items-center p-3 sm:p-4 bg-white rounded-xl shadow-sm hover:shadow-md border border-gray-100 hover:border-amber-200 transition-all duration-200">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-amber-100 rounded-xl flex items-center justify-center group-hover:bg-amber-200 transition-colors mb-2 sm:mb-3">
                <i class="fas fa-minus-circle text-amber-600 text-lg sm:text-xl"></i>
            </div>
            <span class="text-xs sm:text-sm font-medium text-gray-700 text-center">Potongan</span>
        </a>

        <a href="pages/absensi.php" class="group flex flex-col items-center p-3 sm:p-4 bg-white rounded-xl shadow-sm hover:shadow-md border border-gray-100 hover:border-purple-200 transition-all duration-200">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-100 rounded-xl flex items-center justify-center group-hover:bg-purple-200 transition-colors mb-2 sm:mb-3">
                <i class="fas fa-calendar-check text-purple-600 text-lg sm:text-xl"></i>
            </div>
            <span class="text-xs sm:text-sm font-medium text-gray-700 text-center">Absensi</span>
        </a>

        <a href="pages/proses_gaji.php" class="group flex flex-col items-center p-3 sm:p-4 bg-white rounded-xl shadow-sm hover:shadow-md border border-gray-100 hover:border-emerald-200 transition-all duration-200">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-emerald-100 rounded-xl flex items-center justify-center group-hover:bg-emerald-200 transition-colors mb-2 sm:mb-3">
                <i class="fas fa-calculator text-emerald-600 text-lg sm:text-xl"></i>
            </div>
            <span class="text-xs sm:text-sm font-medium text-gray-700 text-center">Proses Gaji</span>
        </a>
    </div>

    <!-- Chart Section -->
    <div class="bg-white/80 backdrop-blur-sm p-4 sm:p-6 lg:p-8 rounded-xl shadow-lg border border-gray-100/50">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 space-y-2 sm:space-y-0">
            <div>
                <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-1">Distribusi Guru per Jabatan</h3>
                <p class="text-xs sm:text-sm text-gray-600">Visualisasi data guru berdasarkan jabatan</p>
            </div>
            <div class="flex items-center space-x-2 text-xs sm:text-sm text-gray-500">
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                    <span>Data Real-time</span>
                </div>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="relative">
            <div class="h-64 sm:h-80 lg:h-96 relative">
                <canvas id="jabatanChart" class="w-full h-full"></canvas>
            </div>

            <!-- Loading State -->
            <div id="chart-loading" class="absolute inset-0 flex items-center justify-center bg-white/90 rounded-lg">
                <div class="flex flex-col items-center space-y-3">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
                    <p class="text-sm text-gray-600">Memuat data chart...</p>
                </div>
            </div>

            <!-- Error State -->
            <div id="chart-error" class="absolute inset-0 flex items-center justify-center bg-white/90 rounded-lg hidden">
                <div class="flex flex-col items-center space-y-3 text-center p-4">
                    <i class="fas fa-exclamation-triangle text-2xl text-amber-500"></i>
                    <p class="text-sm text-gray-600">Gagal memuat data chart</p>
                    <button onclick="reloadChart()" class="text-xs bg-green-600 text-white px-3 py-1 rounded-full hover:bg-green-700 transition-colors">
                        Coba Lagi
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Real-time clock
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            timeElement.textContent = timeString;
        }
    }

    // Update time every second
    setInterval(updateTime, 1000);
    updateTime(); // Initial call

    // Chart functionality
    let chartInstance = null;

    function reloadChart() {
        if (chartInstance) {
            chartInstance.destroy();
        }
        loadChart();
    }

    function showChartLoading() {
        document.getElementById('chart-loading').classList.remove('hidden');
        document.getElementById('chart-error').classList.add('hidden');
    }

    function hideChartLoading() {
        document.getElementById('chart-loading').classList.add('hidden');
    }

    function showChartError() {
        document.getElementById('chart-error').classList.remove('hidden');
        document.getElementById('chart-loading').classList.add('hidden');
    }

    function loadChart() {
        showChartLoading();

        const ctx = document.getElementById('jabatanChart').getContext('2d');

        fetch('api/get_chart_data.php?type=guru_per_jabatan')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                hideChartLoading();

                chartInstance = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Jumlah Guru',
                            data: data.values,
                            backgroundColor: [
                                '#10B981', '#3B82F6', '#8B5CF6', '#F59E0B',
                                '#EC4899', '#6366F1', '#14B8A6', '#F97316'
                            ],
                            borderColor: '#ffffff',
                            borderWidth: 2,
                            hoverOffset: 8,
                            hoverBorderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            animateScale: true,
                            animateRotate: true,
                            duration: 1000
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            title: {
                                display: true,
                                text: 'Jumlah Guru Aktif Berdasarkan Jabatan',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                padding: {
                                    bottom: 20
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: 'white',
                                bodyColor: 'white',
                                borderColor: 'rgba(255, 255, 255, 0.2)',
                                borderWidth: 1,
                                cornerRadius: 8,
                                displayColors: true,
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((context.parsed * 100) / total).toFixed(1);
                                        return `${context.label}: ${context.parsed} guru (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching chart data:', error);
                showChartError();
            });
    }

    // Initialize chart when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        loadChart();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>