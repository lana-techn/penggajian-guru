<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$id_gaji = $_GET['id'] ?? null;
$page_title = 'Pengajuan Gaji';

// --- LOGIKA AKSI (HAPUS & BAYAR) ---
$token = $_GET['token'] ?? '';
if (hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    
    // PERBAIKAN: Logika Hapus dengan Transaksi Database
    if ($action === 'delete' && $id_gaji) {
        $conn->begin_transaction();
        try {
            // Langkah 1: Hapus data dari tabel anak (gaji_detail) terlebih dahulu
            $stmt_detail = $conn->prepare("DELETE FROM gaji_detail WHERE gaji_id = ?");
            $stmt_detail->bind_param("i", $id_gaji);
            $stmt_detail->execute();
            $stmt_detail->close();

            // Langkah 2: Hapus data dari tabel induk (gaji)
            $stmt_gaji = $conn->prepare("DELETE FROM gaji WHERE id = ? AND status_pembayaran = 'belum_dibayar'");
            $stmt_gaji->bind_param("i", $id_gaji);
            $stmt_gaji->execute();
            
            if ($stmt_gaji->affected_rows > 0) {
                set_flash_message('success', 'Data pengajuan gaji berhasil dihapus.');
            } else {
                // Ini terjadi jika statusnya bukan 'belum_dibayar' atau ID tidak ditemukan
                set_flash_message('error', 'Gagal menghapus data atau data tidak dalam status "belum_dibayar".');
            }
            $stmt_gaji->close();

            // Jika semua berhasil, simpan perubahan
            $conn->commit();

        } catch (mysqli_sql_exception $exception) {
            // Jika ada error, batalkan semua perubahan
            $conn->rollback();
            set_flash_message('error', 'Terjadi kesalahan pada database saat menghapus data.');
        }

        header('Location: pengajuan_gaji.php');
        exit;
    }

    // Logika untuk aksi 'Bayar' (tidak berubah)
    if ($action === 'pay' && $id_gaji) {
        $stmt = $conn->prepare("UPDATE gaji SET status_pembayaran = 'sudah_dibayar' WHERE id = ? AND status_pembayaran = 'belum_dibayar'");
        $stmt->bind_param("i", $id_gaji);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            set_flash_message('success', 'Status gaji berhasil diubah menjadi "Dibayarkan".');
        } else {
            set_flash_message('error', 'Gagal memproses pembayaran atau gaji belum disetujui.');
        }
        $stmt->close();
        header('Location: pengajuan_gaji.php');
        exit;
    }
}

// Ambil data untuk filter
$karyawan_list = $conn->query("SELECT id, nama_lengkap FROM guru ORDER BY nama_lengkap ASC")->fetch_all(MYSQLI_ASSOC);
$bulan_list = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($action === 'list'): ?>
<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 font-poppins">Daftar Gaji Karyawan</h2>
            <p class="text-gray-500 text-sm">Tinjau, proses, dan kelola semua data penggajian.</p>
        </div>
        <a href="pengajuan_gaji.php?action=add" class="w-full sm:w-auto bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center justify-center">
            <i class="fa-solid fa-plus mr-2"></i>Tambah Pengajuan
        </a>
    </div>

    <?php display_flash_message(); ?>

    <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 items-end">
        <input type="hidden" name="action" value="list">
        <div>
            <label for="filter_karyawan" class="text-sm font-medium text-gray-600">Nama Karyawan</label>
            <select name="karyawan" id="filter_karyawan" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                <option value="">Semua Karyawan</option>
                <?php foreach($karyawan_list as $k): ?>
                <option value="<?= e($k['Id_Karyawan']) ?>" <?= ($_GET['karyawan'] ?? '') == $k['Id_Karyawan'] ? 'selected' : '' ?>><?= e($k['Nama_Karyawan']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter_bulan" class="text-sm font-medium text-gray-600">Bulan</label>
            <select name="bulan" id="filter_bulan" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                <option value="">Semua Bulan</option>
                <?php foreach($bulan_list as $num => $name): ?>
                <option value="<?= $num ?>" <?= ($_GET['bulan'] ?? '') == $num ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter_tahun" class="text-sm font-medium text-gray-600">Tahun</label>
            <input type="number" name="tahun" id="filter_tahun" value="<?= e($_GET['tahun'] ?? date('Y')) ?>" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
        </div>
        <div class="flex space-x-2">
            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-semibold">Tampilkan</button>
            <a href="pengajuan_gaji.php" class="w-full text-center bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 font-semibold">Reset</a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700">
            <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                <tr>
                    <th class="px-6 py-3">ID Gaji</th>
                    <th class="px-6 py-3">Nama Karyawan</th>
                    <th class="px-6 py-3">Tanggal Gaji</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $sql = "SELECT g.Id_Gaji, k.Nama_Karyawan, g.Tgl_Gaji, g.Status 
                            FROM GAJI g 
                            JOIN KARYAWAN k ON g.Id_Karyawan = k.Id_Karyawan WHERE 1=1";
                    $params = [];
                    $types = '';
                    if (!empty($_GET['karyawan'])) { $sql .= " AND g.Id_Karyawan = ?"; $params[] = $_GET['karyawan']; $types .= 's'; }
                    if (!empty($_GET['bulan'])) { $sql .= " AND MONTH(g.Tgl_Gaji) = ?"; $params[] = $_GET['bulan']; $types .= 'i'; }
                    if (!empty($_GET['tahun'])) { $sql .= " AND YEAR(g.Tgl_Gaji) = ?"; $params[] = $_GET['tahun']; $types .= 'i'; }
                    $sql .= " ORDER BY g.Tgl_Gaji DESC";

                    $stmt = $conn->prepare($sql);
                    if (!empty($types)) { $stmt->bind_param($types, ...$params); }
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0):
                        while($row = $result->fetch_assoc()):
                            $status_class = '';
                            switch ($row['Status']) {
                                case 'Diajukan': $status_class = 'bg-yellow-100 text-yellow-800'; break;
                                case 'Disetujui': $status_class = 'bg-blue-100 text-blue-800'; break;
                                case 'Ditolak': $status_class = 'bg-red-100 text-red-800'; break;
                                case 'Dibayarkan': $status_class = 'bg-green-100 text-green-800'; break;
                            }
                ?>
                <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4 font-mono text-xs"><?= e($row['Id_Gaji']) ?></td>
                    <td class="px-6 py-4 font-medium text-gray-900"><?= e($row['Nama_Karyawan']) ?></td>
                    <td class="px-6 py-4"><?= date('d F Y', strtotime($row['Tgl_Gaji'])) ?></td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?= $status_class ?>">
                            <?= e($row['Status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                        <?php
                            $id_gaji_enc = e($row['Id_Gaji']);
                            $token_enc = e($_SESSION['csrf_token']);
                            switch ($row['Status']) {
                                case 'Diajukan':
                                    echo "<a href='detail_gaji.php?id={$id_gaji_enc}' class='text-sm text-gray-600 bg-gray-200 px-3 py-1 rounded-md hover:bg-gray-300'>Detail</a>";
                                    echo "<a href='pengajuan_gaji.php?action=delete&id={$id_gaji_enc}&token={$token_enc}' onclick='return confirm(\"Yakin ingin menghapus pengajuan ini?\")' class='text-sm text-white bg-red-500 px-3 py-1 rounded-md hover:bg-red-600'>Hapus</a>";
                                    break;
                                case 'Disetujui':
                                    echo "<a href='detail_gaji.php?id={$id_gaji_enc}' class='text-sm text-gray-600 bg-gray-200 px-3 py-1 rounded-md hover:bg-gray-300'>Detail</a>";
                                    echo "<a href='pengajuan_gaji.php?action=pay&id={$id_gaji_enc}&token={$token_enc}' onclick='return confirm(\"Konfirmasi pembayaran gaji ini?\")' class='text-sm text-white bg-blue-500 px-3 py-1 rounded-md hover:bg-blue-600'>Bayar</a>";
                                    break;
                                case 'Ditolak':
                                    echo "<a href='detail_gaji.php?id={$id_gaji_enc}' class='text-sm text-gray-600 bg-gray-200 px-3 py-1 rounded-md hover:bg-gray-300'>Detail</a>";
                                    break;
                                case 'Dibayarkan':
                                    echo "<a href='cetak_slip.php?id={$id_gaji_enc}' target='_blank' class='text-sm text-white bg-green-500 px-3 py-1 rounded-md hover:bg-green-600'>Slip Gaji</a>";
                                    break;
                            }
                        ?>
                        </div>
                    </td>
                </tr>
                <?php   endwhile;
                    else:
                        echo '<tr><td colspan="5" class="text-center py-10 text-gray-500">Tidak ada data gaji yang ditemukan.</td></tr>';
                    endif;
                    $stmt->close();
                ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($action === 'Tambah'): ?>
<div class="max-w-xl mx-auto bg-white p-8 rounded-xl shadow-lg">
    <div class="text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100"><i class="fa-solid fa-file-invoice-dollar text-2xl text-green-600"></i></div>
        <h2 class="mt-4 text-2xl font-bold text-gray-800 font-poppins">Tambah Pengajuan Gaji</h2>
        <p class="mt-2 text-sm text-gray-500">Pilih karyawan dan periode untuk memulai perhitungan gaji.</p>
    </div>
    
    <form method="POST" action="payroll_process.php">
        <?php csrf_input(); ?>
        <div class="space-y-6 mt-8">
            <div>
                <label for="Id_Karyawan" class="block mb-2 text-sm font-medium text-gray-700">Nama Karyawan</label>
                <select name="Id_Karyawan" id="Id_Karyawan" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    <option value="" disabled selected>- Pilih Karyawan -</option>
                    <?php foreach($karyawan_list as $karyawan): ?>
                        <option value="<?= e($karyawan['Id_Karyawan']) ?>"><?= e($karyawan['Nama_Karyawan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="periode" class="block mb-2 text-sm font-medium text-gray-700">Periode Gaji</label>
                <input type="month" name="periode" id="periode" value="<?= date('Y-m') ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">Sertakan Tunjangan Hari Raya (THR)?</label>
                <div class="flex items-center space-x-6 rounded-lg border border-gray-300 p-4">
                    <div class="flex items-center">
                        <input id="tunjangan_ya" name="sertakan_tunjangan" value="1" type="radio" class="h-4 w-4 border-gray-300 text-green-600 focus:ring-green-500">
                        <label for="tunjangan_ya" class="ml-3 block text-sm font-medium text-gray-700">Ya</label>
                    </div>
                    <div class="flex items-center">
                        <input id="tunjangan_tidak" name="sertakan_tunjangan" value="0" type="radio" checked class="h-4 w-4 border-gray-300 text-green-600 focus:ring-green-500">
                        <label for="tunjangan_tidak" class="ml-3 block text-sm font-medium text-gray-700">Tidak</label>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Pilih "Ya" untuk menambahkan THR sebesar 1x Gaji Pokok.</p>
            </div>
        </div>
        <div class="flex items-center justify-end space-x-4 mt-8">
            <a href="pengajuan_gaji.php" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Batal</a>
            <button type="submit" class="w-full sm:w-auto bg-green-600 text-white px-8 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2">
                <i class="fa-solid fa-calculator"></i>
                Hitung & Tampilkan Detail
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>