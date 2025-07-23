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
        $new_status = ($action === 'approve') ? 'sudah_dibayar' : 'belum_dibayar';
        $message = ($action === 'approve') ? 'disetujui' : 'ditolak';

        $stmt = $conn->prepare("UPDATE gaji SET status_pembayaran = ? WHERE id = ? AND status_pembayaran = 'belum_dibayar'");
        $stmt->bind_param("si", $new_status, $id_gaji);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            set_flash_message('success', "Penggajian berhasil {$message}.");
        } else {
            set_flash_message('error', "Gagal memproses penggajian. Mungkin sudah diproses sebelumnya.");
        }
        $stmt->close();
    }

    // PERUBAHAN: Proses Hapus untuk Gaji yang sudah Disetujui
    if ($action === 'delete_approved' && $id_gaji) {
        $conn->begin_transaction();
        try {
            // Hapus detailnya terlebih dahulu
            $stmt_detail = $conn->prepare("DELETE FROM gaji_detail WHERE gaji_id = ?");
            $stmt_detail->bind_param("i", $id_gaji);
            $stmt_detail->execute();
            $stmt_detail->close();

            // Hapus data gaji utamanya
            $stmt_gaji = $conn->prepare("DELETE FROM gaji WHERE id = ? AND status_pembayaran = 'sudah_dibayar'");
            $stmt_gaji->bind_param("i", $id_gaji);
            $stmt_gaji->execute();

            if ($stmt_gaji->affected_rows > 0) {
                set_flash_message('success', 'Data gaji yang disetujui berhasil dihapus.');
            } else {
                set_flash_message('error', 'Gagal menghapus data.');
            }
            $stmt_gaji->close();
            $conn->commit();
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            set_flash_message('error', 'Terjadi kesalahan database saat menghapus.');
        }
    }

    header('Location: penggajian_pemilik.php');
    exit;
}

// Logika Pagination & Pencarian
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

generate_csrf_token();
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 font-poppins">Persetujuan Gaji Karyawan</h2>
            <p class="text-gray-500 text-sm">Tinjau dan proses pengajuan gaji yang masuk dari admin.</p>
        </div>
    </div>

    <?php display_flash_message(); ?>

    <form method="get" action="penggajian_pemilik.php" class="mb-6">
        <div class="relative">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari berdasarkan nama karyawan..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fa-solid fa-search text-gray-400"></i>
            </div>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700">
            <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                <tr>
                    <th class="px-6 py-3">ID Gaji</th>
                    <th class="px-6 py-3">Nama Karyawan</th>
                    <th class="px-6 py-3">Jabatan</th>
                    <th class="px-6 py-3">Periode Gaji</th>
                    <th class="px-6 py-3 text-right">Gaji Bersih</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // PERUBAHAN: Query diubah untuk mengambil status 'Diajukan' DAN 'Disetujui'
                $sql_base = "FROM GAJI g JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan JOIN JABATAN j ON k.Id_Jabatan = j.Id_Jabatan WHERE (g.Status = 'Diajukan' OR g.Status = 'Disetujui') AND k.Nama_Karyawan LIKE ?";

                $count_sql = "SELECT COUNT(g.Id_Gaji) as total " . $sql_base;
                $stmt_count = $conn->prepare($count_sql);
                $search_param = "%" . $search . "%";
                $stmt_count->bind_param("s", $search_param);
                $stmt_count->execute();
                $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
                $total_pages = ceil($total_records / $records_per_page);
                $stmt_count->close();

                $sql = "SELECT g.Id_Gaji, k.Nama_Karyawan, j.Nama_Jabatan, g.Tgl_Gaji, g.Gaji_Bersih, g.Status " . $sql_base . " ORDER BY FIELD(g.Status, 'Diajukan', 'Disetujui'), g.Tgl_Gaji DESC LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sii", $search_param, $records_per_page, $offset);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        // Logika untuk warna status
                        $status_class = '';
                        if ($row['Status'] == 'Diajukan') $status_class = 'bg-yellow-100 text-yellow-800';
                        if ($row['Status'] == 'Disetujui') $status_class = 'bg-blue-100 text-blue-800';
                ?>
                        <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs"><?= e($row['Id_Gaji']) ?></td>
                            <td class="px-6 py-4 font-medium text-gray-900"><?= e($row['Nama_Karyawan']) ?></td>
                            <td class="px-6 py-4"><?= e($row['Nama_Jabatan']) ?></td>
                            <td class="px-6 py-4"><?= e(date('F Y', strtotime($row['Tgl_Gaji']))) ?></td>
                            <td class="px-6 py-4 text-right font-semibold text-green-700">Rp <?= number_format($row['Gaji_Bersih'], 2, ',', '.') ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?= $status_class ?>">
                                    <?= e($row['Status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="detail_gaji_pemilik.php?id=<?= e($row['Id_Gaji']) ?>" class="text-sm text-gray-600 bg-gray-200 px-3 py-1 rounded-md hover:bg-gray-300">Detail</a>
                                    <?php if ($row['Status'] == 'Diajukan'): ?>
                                        <a href="penggajian_pemilik.php?action=approve&id=<?= e($row['Id_Gaji']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-sm text-white bg-green-500 px-3 py-1 rounded-md hover:bg-green-600" onclick="return confirm('Apakah Anda yakin ingin menyetujui penggajian ini?')">Setujui</a>
                                        <a href="penggajian_pemilik.php?action=reject&id=<?= e($row['Id_Gaji']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-sm text-white bg-red-500 px-3 py-1 rounded-md hover:bg-red-600" onclick="return confirm('Apakah Anda yakin ingin menolak penggajian ini?')">Tolak</a>
                                    <?php elseif ($row['Status'] == 'Disetujui'): ?>
                                        <a href="penggajian_pemilik.php?action=delete_approved&id=<?= e($row['Id_Gaji']) ?>&token=<?= e($_SESSION['csrf_token']) ?>" class="text-sm text-white bg-red-500 px-3 py-1 rounded-md hover:bg-red-600" onclick="return confirm('Yakin ingin menghapus data yang sudah disetujui ini?')">Hapus</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php
                    endwhile;
                else:
                    ?>
                    <tr>
                        <td colspan="7" class="text-center py-10 text-gray-500">
                            <i class="fa-solid fa-folder-open text-2xl text-gray-400 mb-2"></i>
                            <p>Tidak ada data gaji yang perlu diproses saat ini.</p>
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
    echo generate_pagination_links($page, $total_pages, 'penggajian_pemilik.php', ['search' => $search]);
    ?>
</div>