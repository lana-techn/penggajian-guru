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
            // Hapus data dari tabel Penggajian
            $stmt_gaji = $conn->prepare("DELETE FROM Penggajian WHERE id_penggajian = ?");
            $stmt_gaji->bind_param("s", $id_gaji);
            $stmt_gaji->execute();

            if ($stmt_gaji->affected_rows > 0) {
                set_flash_message('success', 'Data pengajuan gaji berhasil dihapus.');
            } else {
                set_flash_message('error', 'Gagal menghapus data atau data tidak ditemukan.');
            }
            $stmt_gaji->close();

            // Jika semua berhasil, simpan perubahan
            $conn->commit();
        } catch (mysqli_sql_exception $exception) {
            // Jika ada error, batalkan semua perubahan
            $conn->rollback();
            set_flash_message('error', 'Terjadi kesalahan pada database saat menghapus data.');
        }

        header('Location: pengajuan_gaji_guru.php');
        exit;
    }

    // Logika untuk aksi 'Bayar' - tidak applicable untuk current schema
    if ($action === 'pay' && $id_gaji) {
        // Since the current Penggajian table doesn't have status fields,
        // this functionality would need to be redesigned
        set_flash_message('info', 'Fitur pembayaran akan segera tersedia.');
        header('Location: pengajuan_gaji_guru.php');
        exit;
    }
}

// Ambil data untuk filter
$karyawan_list = $conn->query("SELECT id_guru, nama_guru FROM Guru ORDER BY nama_guru ASC")->fetch_all(MYSQLI_ASSOC);
$bulan_list = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 font-poppins">Daftar Gaji Guru</h2>
                <p class="text-gray-500 text-sm">Tinjau, proses, dan kelola semua data penggajian guru.</p>
            </div>
            <a href="pengajuan_gaji_guru.php?action=add" class="w-full sm:w-auto bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 text-sm font-semibold shadow-md hover:shadow-lg transition-all flex items-center justify-center">
                <i class="fa-solid fa-plus mr-2"></i>Tambah Pengajuan
            </a>
        </div>

        <?php display_flash_message(); ?>

        <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 items-end">
            <input type="hidden" name="action" value="list">
            <div>
                <label for="filter_karyawan" class="text-sm font-medium text-gray-600">Nama Guru</label>
                <select name="karyawan" id="filter_karyawan" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                    <option value="">Semua Guru</option>
                    <?php foreach ($karyawan_list as $k): ?>
                        <option value="<?= e($k['id_guru']) ?>" <?= ($_GET['karyawan'] ?? '') == $k['id_guru'] ? 'selected' : '' ?>><?= e($k['nama_guru']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter_bulan" class="text-sm font-medium text-gray-600">Bulan</label>
                <select name="bulan" id="filter_bulan" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                    <option value="">Semua Bulan</option>
                    <?php foreach ($bulan_list as $num => $name): ?>
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
                <a href="pengajuan_gaji_guru.php" class="w-full text-center bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 font-semibold">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-6 py-3">ID Penggajian</th>
                        <th class="px-6 py-3">Nama Guru</th>
                        <th class="px-6 py-3">Tanggal Input</th>
                        <th class="px-6 py-3">Bulan Gaji</th>
                        <th class="px-6 py-3">Gaji Bersih</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT p.id_penggajian, g.nama_guru, p.tgl_input, p.bulan_penggajian, p.gaji_bersih 
                            FROM Penggajian p 
                            JOIN Guru g ON p.id_guru = g.id_guru WHERE 1=1";
                    $params = [];
                    $types = '';
                    if (!empty($_GET['karyawan'])) {
                        $sql .= " AND p.id_guru = ?";
                        $params[] = $_GET['karyawan'];
                        $types .= 's';
                    }
                    if (!empty($_GET['bulan'])) {
                        $sql .= " AND p.bulan_penggajian = ?";
                        $params[] = str_pad($_GET['bulan'], 2, '0', STR_PAD_LEFT);
                        $types .= 's';
                    }
                    if (!empty($_GET['tahun'])) {
                        $sql .= " AND YEAR(p.tgl_input) = ?";
                        $params[] = $_GET['tahun'];
                        $types .= 'i';
                    }
                    $sql .= " ORDER BY p.tgl_input DESC";

                    $stmt = $conn->prepare($sql);
                    if (!empty($types)) {
                        $stmt->bind_param($types, ...$params);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                    ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-mono text-xs"><?= e($row['id_penggajian']) ?></td>
                                <td class="px-6 py-4 font-medium text-gray-900"><?= e($row['nama_guru']) ?></td>
                                <td class="px-6 py-4"><?= date('d F Y', strtotime($row['tgl_input'])) ?></td>
                                <td class="px-6 py-4"><?= e($row['bulan_penggajian']) ?></td>
                                <td class="px-6 py-4 font-semibold text-green-600">Rp <?= number_format($row['gaji_bersih'], 0, ',', '.') ?></td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <?php
                                        $id_gaji_enc = e($row['id_penggajian']);
                                        $token_enc = e($_SESSION['csrf_token']);
                                        echo "<a href='detail_gaji_guru.php?id={$id_gaji_enc}' class='text-sm text-blue-600 bg-blue-100 px-3 py-1 rounded-md hover:bg-blue-200'>Detail</a>";
                                        echo "<a href='pengajuan_gaji_guru.php?action=delete&id={$id_gaji_enc}&token={$token_enc}' onclick='return confirm(\"Yakin ingin menghapus data gaji ini?\")' class='text-sm text-white bg-red-500 px-3 py-1 rounded-md hover:bg-red-600'>Hapus</a>";
                                        ?>
                                    </div>
                                </td>
                            </tr>
                    <?php endwhile;
                    else:
                        echo '<tr><td colspan="6" class="text-center py-10 text-gray-500">Tidak ada data gaji yang ditemukan.</td></tr>';
                    endif;
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($action === 'add'): ?>
    <div class="max-w-xl mx-auto bg-white p-8 rounded-xl shadow-lg">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100"><i class="fa-solid fa-file-invoice-dollar text-2xl text-green-600"></i></div>
            <h2 class="mt-4 text-2xl font-bold text-gray-800 font-poppins">Tambah Pengajuan Gaji</h2>
            <p class="mt-2 text-sm text-gray-500">Pilih guru dan periode untuk memulai perhitungan gaji.</p>
        </div>

        <form method="POST" action="proses_gaji.php">
            <?php csrf_input(); ?>
            <div class="space-y-6 mt-8">
                <div>
                    <label for="id_guru" class="block mb-2 text-sm font-medium text-gray-700">Nama Guru</label>
                    <select name="id_guru" id="id_guru" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <option value="" disabled selected>- Pilih Guru -</option>
                        <?php foreach ($karyawan_list as $guru): ?>
                            <option value="<?= e($guru['id_guru']) ?>"><?= e($guru['nama_guru']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="periode" class="block mb-2 text-sm font-medium text-gray-700">Periode Gaji</label>
                    <input type="month" name="periode" id="periode" value="<?= date('Y-m') ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>
            </div>
            <div class="flex items-center justify-end space-x-4 mt-8">
                <a href="pengajuan_gaji_guru.php" class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">Batal</a>
                <button type="submit" class="w-full sm:w-auto bg-green-600 text-white px-8 py-2.5 rounded-lg hover:bg-green-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-calculator"></i>
                    Hitung & Proses Gaji
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>