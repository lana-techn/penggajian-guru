<?php
$page_title = 'Dashboard Kepala Sekolah';
$current_page = 'dashboard';
require_once __DIR__ . '/includes/functions.php';
requireLogin('kepala_sekolah');

$conn = db_connect();

// Hitung jumlah pengajuan gaji yang menunggu persetujuan
$pending_approvals = 0; // Kolom status_pembayaran tidak ada di tabel Penggajian

// Hitung jumlah guru aktif
$total_guru = $conn->query("SELECT COUNT(id_guru) as total FROM Guru WHERE 1")->fetch_assoc()['total'] ?? 0;

// Hitung total gaji bersih yang sudah dibayar bulan ini
$current_month = date('m');
$current_year = date('Y');
$total_gaji_bulan_ini = $conn->query("SELECT SUM(gaji_bersih) as total FROM Penggajian WHERE bulan_penggajian = '$current_month' AND YEAR(tgl_input) = $current_year")->fetch_assoc()['total'] ?? 0;

$conn->close();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 p-6 sm:p-8 bg-gray-50">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 font-poppins">Dashboard Kepala Sekolah</h1>
            <p class="text-gray-500 mt-1">Ringkasan data penggajian terkini.</p>
        </div>
         <div class="text-sm text-gray-600 bg-white px-4 py-2 rounded-lg shadow-sm mt-4 sm:mt-0">
            <i class="fa-solid fa-calendar-day mr-2 text-green-600"></i>
            <?= date('l, d F Y') ?>
        </div>
    </div>
    <?php display_flash_message(); ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-yellow-500 to-amber-500 text-white p-6 rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-lg font-medium text-yellow-100">Menunggu Persetujuan</p>
                    <p class="text-4xl font-bold"><?= e($pending_approvals) ?></p>
                </div>
                <div class="bg-white/30 p-4 rounded-full">
                    <i class="fas fa-hourglass-half fa-2x text-white"></i>
                </div>
            </div>
             <a href="pages/kepala_sekolah/penggajian_kepala_sekolah.php" class="inline-block mt-4 text-sm text-yellow-50 bg-yellow-900/30 px-3 py-1 rounded-full hover:bg-yellow-900/50 transition-colors">Lihat Pengajuan <i class="fa-solid fa-arrow-right-long ml-1"></i></a>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 text-white p-6 rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-lg font-medium text-green-100">Total Guru Aktif</p>
                    <p class="text-4xl font-bold"><?= e($total_guru) ?></p>
                </div>
                 <div class="bg-white/30 p-4 rounded-full">
                    <i class="fas fa-users fa-2x text-white"></i>
                </div>
            </div>
            <p class="text-sm mt-4 text-green-100 opacity-80">Sumber daya manusia sekolah.</p>
        </div>
        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 text-white p-6 rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-lg font-medium text-indigo-100">Total Gaji (<?= date('F Y') ?>)</p>
                    <p class="text-3xl font-bold">Rp <?= number_format($total_gaji_bulan_ini, 0, ',', '.') ?></p>
                </div>
                 <div class="bg-white/30 p-4 rounded-full">
                    <i class="fas fa-wallet fa-2x text-white"></i>
                </div>
            </div>
             <a href="pages/kepala_sekolah/laporan.php" class="inline-block mt-4 text-sm text-indigo-50 bg-indigo-900/30 px-3 py-1 rounded-full hover:bg-indigo-900/50 transition-colors">Lihat Laporan <i class="fa-solid fa-arrow-right-long ml-1"></i></a>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <div class="lg:col-span-3 bg-white p-6 rounded-xl shadow-md">
             <h3 class="text-xl font-bold text-gray-800 mb-4">Tren Pengeluaran Gaji (6 Bulan Terakhir)</h3>
            <div class="h-80 relative">
                <canvas id="trenGajiChart"></canvas>
            </div>
        </div>
        <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-md">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Akses Cepat</h3>
            <ul class="space-y-3">
                <li>
                    <a href="pages/kepala_sekolah/penggajian_kepala_sekolah.php" class="flex items-center p-4 rounded-lg bg-green-50 hover:bg-green-100 transition-colors group">
                        <i class="fa-solid fa-check-to-slot text-2xl text-green-600"></i>
                        <span class="ml-4 font-semibold text-green-800 group-hover:text-green-900">Persetujuan Gaji</span>
                        <i class="fa-solid fa-chevron-right ml-auto text-green-500"></i>
                    </a>
                </li>
                 <li>
                    <a href="pages/kepala_sekolah/laporan.php" class="flex items-center p-4 rounded-lg bg-blue-50 hover:bg-blue-100 transition-colors group">
                        <i class="fa-solid fa-file-invoice-dollar text-2xl text-blue-600"></i>
                        <span class="ml-4 font-semibold text-blue-800 group-hover:text-blue-900">Laporan Penggajian</span>
                        <i class="fa-solid fa-chevron-right ml-auto text-blue-500"></i>
                    </a>
                </li>
                <li>
                    <a href="pages/kepala_sekolah/tambah_admin.php" class="flex items-center p-4 rounded-lg bg-yellow-50 hover:bg-yellow-100 transition-colors group">
                        <i class="fa-solid fa-user-plus text-2xl text-yellow-600"></i>
                        <span class="ml-4 font-semibold text-yellow-800 group-hover:text-yellow-900">Tambah Admin</span>
                        <i class="fa-solid fa-chevron-right ml-auto text-yellow-500"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('trenGajiChart').getContext('2d');
    fetch('api/get_chart_data.php?type=gaji_per_bulan')
        .then(response => response.json())
        .then(data => {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Total Gaji Bersih',
                        data: data.values,
                        fill: true,
                        backgroundColor: 'rgba(22, 163, 74, 0.2)',
                        borderColor: '#16A34A',
                        tension: 0.3,
                        pointBackgroundColor: '#16A34A',
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value, index, values) {
                                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                         tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error fetching chart data:', error));
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>