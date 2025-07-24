<?php
$page_title = 'Persetujuan Gaji';
$current_page = 'penggajian_pemilik';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('kepala_sekolah');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id_gaji = $_GET['id'] ?? null;

// --- PROSES AKSI (SETUJUI, TOLAK, HAPUS) ---
if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {

    // Proses Setujui atau Tolak
    if (in_array($action, ['approve', 'reject']) && $id_gaji) {
        $new_status = ($action === 'approve') ? 'Disetujui' : 'Ditolak';
        $message = ($action === 'approve') ? 'disetujui' : 'ditolak';

        // First check if status_penggajian column exists, if not, create it
        $check_column = $conn->query("SHOW COLUMNS FROM Penggajian LIKE 'status_penggajian'");
        if ($check_column->num_rows == 0) {
            // Add the column if it doesn't exist
            $conn->query("ALTER TABLE Penggajian ADD COLUMN status_penggajian VARCHAR(20) DEFAULT 'Menunggu'");
        }

        $stmt = $conn->prepare("UPDATE Penggajian SET status_penggajian = ? WHERE id_penggajian = ?");
        $stmt->bind_param("ss", $new_status, $id_gaji);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            set_flash_message('success', "Penggajian berhasil {$message}.");
        } else {
            set_flash_message('error', "Gagal memproses penggajian. Mungkin sudah diproses sebelumnya.");
        }
        $stmt->close();
    }

    // Proses Hapus untuk Penggajian yang sudah Disetujui
    if ($action === 'delete_approved' && $id_gaji) {
        $conn->begin_transaction();
        try {
            // Hapus data penggajian
            $stmt_gaji = $conn->prepare("DELETE FROM Penggajian WHERE id_penggajian = ?");
            $stmt_gaji->bind_param("s", $id_gaji);
            $stmt_gaji->execute();

            if ($stmt_gaji->affected_rows > 0) {
                set_flash_message('success', 'Data penggajian berhasil dihapus.');
            } else {
                set_flash_message('error', 'Gagal menghapus data penggajian.');
            }
            $stmt_gaji->close();
            $conn->commit();
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            set_flash_message('error', 'Terjadi kesalahan database saat menghapus: ' . $exception->getMessage());
        }
    }

    header('Location: penggajian_kepala_sekolah.php');
    exit;
}

// Logika Pagination & Pencarian
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

generate_csrf_token();
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50">
    <div class="bg-white/80 backdrop-blur-sm p-4 sm:p-6 lg:p-8 rounded-xl shadow-lg border border-gray-100/50 mb-6">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 space-y-4 lg:space-y-0">
            <div class="flex-1">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 font-poppins mb-2">
                    <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Persetujuan Gaji Guru</span>
                </h2>
                <p class="text-gray-600 text-sm sm:text-base">Tinjau dan proses pengajuan gaji guru dari admin.</p>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Kelola persetujuan penggajian dengan mudah dan efisien</p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="flex items-center text-sm text-gray-600 bg-blue-50 px-3 sm:px-4 py-2 rounded-lg border border-blue-100">
                    <i class="fa-solid fa-user-tie mr-2 text-blue-600"></i>
                    <span class="font-medium">Kepala Sekolah</span>
                </div>
            </div>
        </div>

        <?php display_flash_message(); ?>

        <!-- Search Form -->
        <form method="get" action="penggajian_kepala_sekolah.php" class="mb-6">
            <div class="relative">
                <input type="text"
                    name="search"
                    value="<?= e($search) ?>"
                    placeholder="Cari berdasarkan nama guru, jabatan, periode, atau ID..."
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white/80 backdrop-blur-sm"
                    autocomplete="off">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-search text-gray-400"></i>
                </div>
                <button type="submit" class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <span class="px-4 py-1.5 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                        <i class="fa-solid fa-search mr-1"></i> Cari
                    </span>
                </button>
                <?php if (!empty($search)): ?>
                    <a href="penggajian_kepala_sekolah.php"
                        class="absolute inset-y-0 right-24 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                        title="Hapus pencarian">
                        <i class="fa-solid fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
            <div class="text-xs text-gray-500 mt-2">
                <i class="fa-solid fa-info-circle mr-1"></i>
                Anda dapat mencari berdasarkan nama guru, jabatan, periode gaji, atau ID penggajian
            </div>
        </form>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 text-white p-4 rounded-xl shadow-md">
                <div class="flex items-center">
                    <i class="fa-solid fa-clock text-2xl mr-3"></i>
                    <div>
                        <p class="text-yellow-100 text-sm">Menunggu Persetujuan</p>
                        <p class="text-2xl font-bold" id="pending-count">-</p>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-r from-green-400 to-green-500 text-white p-4 rounded-xl shadow-md">
                <div class="flex items-center">
                    <i class="fa-solid fa-check-circle text-2xl mr-3"></i>
                    <div>
                        <p class="text-green-100 text-sm">Sudah Disetujui</p>
                        <p class="text-2xl font-bold" id="approved-count">-</p>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-r from-red-400 to-red-500 text-white p-4 rounded-xl shadow-md">
                <div class="flex items-center">
                    <i class="fa-solid fa-times-circle text-2xl mr-3"></i>
                    <div>
                        <p class="text-red-100 text-sm">Ditolak</p>
                        <p class="text-2xl font-bold" id="rejected-count">-</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-700">
                    <thead class="text-xs uppercase bg-gradient-to-r from-gray-50 to-gray-100 text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="px-4 sm:px-6 py-4">ID Penggajian</th>
                            <th class="px-4 sm:px-6 py-4">Nama Guru</th>
                            <th class="px-4 sm:px-6 py-4 hidden sm:table-cell">Jabatan</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Periode Gaji</th>
                            <th class="px-4 sm:px-6 py-4 text-right">Gaji Bersih</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                            <th class="px-4 sm:px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Check if status_penggajian column exists, if not, add it
                        $check_column = $conn->query("SHOW COLUMNS FROM Penggajian LIKE 'status_penggajian'");
                        $has_status_column = $check_column->num_rows > 0;

                        if (!$has_status_column) {
                            // Add the column if it doesn't exist
                            $conn->query("ALTER TABLE Penggajian ADD COLUMN status_penggajian VARCHAR(20) DEFAULT 'Menunggu'");
                            $has_status_column = true;
                        }

                        // Query untuk mengambil data penggajian guru
                        $sql_base = "FROM Penggajian p 
                           JOIN Guru g ON p.id_guru = g.id_guru 
                           JOIN Jabatan j ON g.id_jabatan = j.id_jabatan";

                        // Add search condition if search is not empty
                        if (!empty($search)) {
                            $sql_base .= " WHERE (g.nama_guru LIKE ? OR j.nama_jabatan LIKE ? OR p.bulan_penggajian LIKE ? OR p.id_penggajian LIKE ?)";
                        }

                        $count_sql = "SELECT COUNT(p.id_penggajian) as total " . $sql_base;
                        $stmt_count = $conn->prepare($count_sql);
                        if (!empty($search)) {
                            $search_param = "%" . $search . "%";
                            $stmt_count->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
                        }
                        $stmt_count->execute();
                        $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
                        $total_pages = ceil($total_records / $records_per_page);
                        $stmt_count->close();

                        $sql = "SELECT p.id_penggajian, g.nama_guru, j.nama_jabatan, p.tgl_input, p.gaji_bersih, p.bulan_penggajian,
                              COALESCE(p.status_penggajian, 'Menunggu') as status_penggajian " .
                            $sql_base . " ORDER BY p.tgl_input DESC LIMIT ? OFFSET ?";
                        $stmt = $conn->prepare($sql);

                        if (!empty($search)) {
                            $search_param = "%" . $search . "%";
                            $stmt->bind_param("ssssii", $search_param, $search_param, $search_param, $search_param, $records_per_page, $offset);
                        } else {
                            $stmt->bind_param("ii", $records_per_page, $offset);
                        }
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0):
                            while ($row = $result->fetch_assoc()):
                                // Logika untuk warna status
                                $status_class = '';
                                $status_text = $row['status_penggajian'];
                                if ($status_text == 'Menunggu') $status_class = 'bg-yellow-100 text-yellow-800';
                                if ($status_text == 'Disetujui') $status_class = 'bg-green-100 text-green-800';
                                if ($status_text == 'Ditolak') $status_class = 'bg-red-100 text-red-800';
                        ?>
                                <tr class="bg-white border-b hover:bg-blue-50/30 transition-colors">
                                    <td class="px-4 sm:px-6 py-4 font-mono text-xs"><?= e($row['id_penggajian']) ?></td>
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-900"><?= e($row['nama_guru']) ?></td>
                                    <td class="px-4 sm:px-6 py-4 hidden sm:table-cell"><?= e($row['nama_jabatan']) ?></td>
                                    <td class="px-4 sm:px-6 py-4 hidden md:table-cell"><?= e($row['bulan_penggajian']) ?> <?= e(date('Y', strtotime($row['tgl_input']))) ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-right font-semibold text-green-700">Rp <?= number_format($row['gaji_bersih'], 0, ',', '.') ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?= $status_class ?>">
                                            <?= e($status_text) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <div class="flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2">
                                            <a href="detail_gaji_kepala_sekolah.php?id=<?= e($row['id_penggajian']) ?>" class="text-xs sm:text-sm text-gray-600 bg-gray-200 px-2 sm:px-3 py-1 rounded-md hover:bg-gray-300 w-full sm:w-auto text-center">Detail</a>
                                            <?php if ($status_text == 'Menunggu'): ?>
                                                <a href="penggajian_kepala_sekolah.php?action=approve&id=<?= e($row['id_penggajian']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-xs sm:text-sm text-white bg-green-500 px-2 sm:px-3 py-1 rounded-md hover:bg-green-600 w-full sm:w-auto text-center" onclick="return confirm('Apakah Anda yakin ingin menyetujui penggajian ini?')">Setujui</a>
                                                <a href="penggajian_kepala_sekolah.php?action=reject&id=<?= e($row['id_penggajian']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-xs sm:text-sm text-white bg-red-500 px-2 sm:px-3 py-1 rounded-md hover:bg-red-600 w-full sm:w-auto text-center" onclick="return confirm('Apakah Anda yakin ingin menolak penggajian ini?')">Tolak</a>
                                            <?php elseif ($status_text == 'Disetujui'): ?>
                                                <a href="penggajian_kepala_sekolah.php?action=delete_approved&id=<?= e($row['id_penggajian']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-xs sm:text-sm text-white bg-red-500 px-2 sm:px-3 py-1 rounded-md hover:bg-red-600 w-full sm:w-auto text-center" onclick="return confirm('Yakin ingin menghapus data yang sudah disetujui ini?')">Hapus</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php
                            endwhile;
                        else:
                            ?>
                            <tr>
                                <td colspan="7" class="text-center py-12 text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i class="fa-solid fa-folder-open text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-lg font-medium text-gray-600 mb-2">Tidak Ada Data Penggajian</p>
                                        <p class="text-sm text-gray-500">Tidak ada data penggajian guru yang perlu diproses saat ini.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php
                        endif;
                        $stmt->close();
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>

            <?php
            echo generate_pagination_links($page, $total_pages, 'penggajian_kepala_sekolah.php', ['search' => $search]);
            ?>
        </div>
    </div>
</div>

<script>
    // Function to update statistics
    function updateStatistics() {
        let pendingCount = 0;
        let approvedCount = 0;
        let rejectedCount = 0;

        // Count status from table rows
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const statusSpan = row.querySelector('td:nth-child(6) span');
            if (statusSpan) {
                const status = statusSpan.textContent.trim();
                if (status === 'Menunggu') pendingCount++;
                else if (status === 'Disetujui') approvedCount++;
                else if (status === 'Ditolak') rejectedCount++;
            }
        });

        // Update counts
        document.getElementById('pending-count').textContent = pendingCount;
        document.getElementById('approved-count').textContent = approvedCount;
        document.getElementById('rejected-count').textContent = rejectedCount;
    }

    // Update statistics when page loads
    document.addEventListener('DOMContentLoaded', function() {
        updateStatistics();

        // Add smooth scroll to top functionality
        const searchForm = document.querySelector('form[method="get"]');
        if (searchForm) {
            searchForm.addEventListener('submit', function() {
                setTimeout(() => {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }, 100);
            });
        }

        // Add loading state to action buttons
        const actionButtons = document.querySelectorAll('a[onclick*="confirm"]');
        actionButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (this.onclick && this.onclick()) {
                    this.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Memproses...';
                    this.classList.add('opacity-75', 'cursor-not-allowed');
                    this.style.pointerEvents = 'none';
                }
            });
        });
    });

    // Auto-refresh every 30 seconds
    setInterval(function() {
        if (document.hidden) return; // Don't refresh if tab is not active

        const currentUrl = window.location.href;
        if (!currentUrl.includes('search=') || document.querySelector('input[name="search"]').value === '') {
            window.location.reload();
        }
    }, 30000);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>