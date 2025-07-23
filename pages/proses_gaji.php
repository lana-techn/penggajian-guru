<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');

$conn = db_connect();
$page_title = 'Input Data Gaji Guru';

// Ambil data guru untuk dropdown
$guru_list = $conn->query("SELECT id_guru, nama_guru FROM Guru ORDER BY nama_guru ASC")->fetch_all(MYSQLI_ASSOC);

$tahun_sekarang = (int)date('Y');
$tahun_min = $conn->query("SELECT MIN(YEAR(tgl_masuk)) as thn FROM Guru")->fetch_assoc()['thn'] ?? $tahun_sekarang;
$tahun_opsi = range($tahun_sekarang, $tahun_min);

$bulan_opsi = [
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maret',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember'
];

// --- PROSES TAMBAH DATA GAJI ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');
    $id_guru = $_POST['id_guru'] ?? '';
    $bulan = $_POST['bulan'] ?? '';
    $tahun = $_POST['tahun'] ?? '';
    $masa_kerja = $_POST['masa_kerja'] ?? 0;
    $gaji_pokok = $_POST['gaji_pokok'] ?? 0;
    $tunjangan_beras = $_POST['tunjangan_beras'] ?? 0;
    $tunjangan_kehadiran = $_POST['tunjangan_kehadiran'] ?? 0;
    $tunjangan_suami_istri = $_POST['tunjangan_suami_istri'] ?? 0;
    $tunjangan_anak = $_POST['tunjangan_anak'] ?? 0;
    $potongan_bpjs = $_POST['potongan_bpjs'] ?? 0;
    $infak = $_POST['infak'] ?? 0;
    $gaji_kotor = $gaji_pokok + $tunjangan_beras + $tunjangan_kehadiran + $tunjangan_suami_istri + $tunjangan_anak;
    $total_potongan = $potongan_bpjs + $infak;
    $gaji_bersih = $gaji_kotor - $total_potongan;
    $tgl_input = date('Y-m-d');
    $id_penggajian = 'PG' . date('ymdHis') . $id_guru;

    // Cek duplikat
    $cek = $conn->query("SELECT id_penggajian FROM Penggajian WHERE id_guru='" . $conn->real_escape_string($id_guru) . "' AND bulan_penggajian='" . $conn->real_escape_string($bulan) . "' AND YEAR(tgl_input)='" . $conn->real_escape_string($tahun) . "'");
    if ($cek->num_rows > 0) {
        set_flash_message('error', 'Data gaji untuk guru dan periode ini sudah ada.');
    } else {
        $stmt = $conn->prepare("INSERT INTO Penggajian (id_penggajian, id_guru, masa_kerja, gaji_pokok, tunjangan_beras, tunjangan_kehadiran, tunjangan_suami_istri, tunjangan_anak, potongan_bpjs, infak, gaji_kotor, total_potongan, gaji_bersih, tgl_input, bulan_penggajian) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssidddddddddsss', $id_penggajian, $id_guru, $masa_kerja, $gaji_pokok, $tunjangan_beras, $tunjangan_kehadiran, $tunjangan_suami_istri, $tunjangan_anak, $potongan_bpjs, $infak, $gaji_kotor, $total_potongan, $gaji_bersih, $tgl_input, $bulan);
        if ($stmt->execute()) set_flash_message('success', 'Data gaji berhasil ditambahkan.');
        else set_flash_message('error', 'Gagal menambah data gaji: ' . $stmt->error);
        $stmt->close();
    }
}

// --- PROSES EDIT DATA GAJI ---
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = $_GET['id'];
    $edit_data = $conn->query("SELECT * FROM Penggajian WHERE id_penggajian='" . $conn->real_escape_string($edit_id) . "'")->fetch_assoc();
    if (!$edit_data) {
        set_flash_message('error', 'Data gaji tidak ditemukan.');
        header('Location: proses_gaji.php');
        exit;
    }
}

// --- PROSES UPDATE DATA GAJI ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');
    $edit_id = $_POST['edit_id'];
    $id_guru = $_POST['id_guru'] ?? '';
    $bulan = $_POST['bulan'] ?? '';
    $tahun = $_POST['tahun'] ?? '';
    $masa_kerja = $_POST['masa_kerja'] ?? 0;
    $gaji_pokok = $_POST['gaji_pokok'] ?? 0;
    $tunjangan_beras = $_POST['tunjangan_beras'] ?? 0;
    $tunjangan_kehadiran = $_POST['tunjangan_kehadiran'] ?? 0;
    $tunjangan_suami_istri = $_POST['tunjangan_suami_istri'] ?? 0;
    $tunjangan_anak = $_POST['tunjangan_anak'] ?? 0;
    $potongan_bpjs = $_POST['potongan_bpjs'] ?? 0;
    $infak = $_POST['infak'] ?? 0;
    $gaji_kotor = $gaji_pokok + $tunjangan_beras + $tunjangan_kehadiran + $tunjangan_suami_istri + $tunjangan_anak;
    $total_potongan = $potongan_bpjs + $infak;
    $gaji_bersih = $gaji_kotor - $total_potongan;
    $tgl_input = date('Y-m-d');
    $stmt = $conn->prepare("UPDATE Penggajian SET id_guru=?, masa_kerja=?, gaji_pokok=?, tunjangan_beras=?, tunjangan_kehadiran=?, tunjangan_suami_istri=?, tunjangan_anak=?, potongan_bpjs=?, infak=?, gaji_kotor=?, total_potongan=?, gaji_bersih=?, tgl_input=?, bulan_penggajian=? WHERE id_penggajian=?");
    $stmt->bind_param('sidddddddddssss', $id_guru, $masa_kerja, $gaji_pokok, $tunjangan_beras, $tunjangan_kehadiran, $tunjangan_suami_istri, $tunjangan_anak, $potongan_bpjs, $infak, $gaji_kotor, $total_potongan, $gaji_bersih, $tgl_input, $bulan, $edit_id);
    if ($stmt->execute()) set_flash_message('success', 'Data gaji berhasil diupdate.');
    else set_flash_message('error', 'Gagal update data gaji: ' . $stmt->error);
    $stmt->close();
    header('Location: proses_gaji.php');
    exit;
}

// --- PROSES HAPUS DATA GAJI ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = $_GET['id'];
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $stmt = $conn->prepare("DELETE FROM Penggajian WHERE id_penggajian = ?");
        $stmt->bind_param('s', $del_id);
        if ($stmt->execute()) set_flash_message('success', 'Data gaji berhasil dihapus.');
        else set_flash_message('error', 'Gagal menghapus data gaji.');
        $stmt->close();
    } else {
        set_flash_message('error', 'Token keamanan tidak valid.');
    }
    header('Location: proses_gaji.php');
    exit;
}

// --- FILTER DATA GAJI ---
$filter_guru = $_GET['guru'] ?? '';
$filter_bulan = $_GET['bulan'] ?? '';
$filter_tahun = $_GET['tahun'] ?? '';

$sql = "SELECT p.*, g.nama_guru FROM Penggajian p JOIN Guru g ON p.id_guru = g.id_guru WHERE 1=1";
$params = [];
$types = '';
if ($filter_guru) {
    $sql .= " AND p.id_guru = ?";
    $params[] = $filter_guru;
    $types .= 's';
}
if ($filter_bulan) {
    $sql .= " AND p.bulan_penggajian = ?";
    $params[] = $filter_bulan;
    $types .= 's';
}
if ($filter_tahun) {
    $sql .= " AND YEAR(p.tgl_input) = ?";
    $params[] = $filter_tahun;
    $types .= 's';
}
$sql .= " ORDER BY p.tgl_input DESC";
$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-4xl mx-auto mt-8">
    <div class="bg-white p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-2 font-poppins flex items-center gap-2"><i class="fa-solid fa-calculator"></i> <?= e($page_title) ?></h2>
        <?php display_flash_message(); ?>
        <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <?php csrf_input(); ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Guru</label>
                <select name="id_guru" class="border px-3 py-2 rounded w-full" required>
                    <option value="">- Pilih Guru -</option>
                    <?php foreach ($guru_list as $g): ?>
                        <option value="<?= e($g['id_guru']) ?>" <?= ($edit_data['id_guru'] ?? '') == $g['id_guru'] ? 'selected' : '' ?>>
                            <?= e($g['nama_guru']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <select name="bulan" class="border px-3 py-2 rounded w-full" required>
                    <option value="">- Pilih Bulan -</option>
                    <?php foreach ($bulan_opsi as $val => $nama): ?>
                        <option value="<?= e($val) ?>" <?= ($edit_data['bulan_penggajian'] ?? '') == $val ? 'selected' : '' ?>>
                            <?= e($nama) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                <select name="tahun" class="border px-3 py-2 rounded w-full" required>
                    <option value="">- Pilih Tahun -</option>
                    <?php foreach ($tahun_opsi as $th): ?>
                        <option value="<?= e($th) ?>" <?= ($edit_data['tgl_input'] ?? '') == $th ? 'selected' : '' ?>>
                            <?= e($th) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Masa Kerja (tahun)</label>
                <input type="number" name="masa_kerja" class="border px-3 py-2 rounded w-full" min="0" value="<?= e($edit_data['masa_kerja'] ?? 0) ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gaji Pokok (Rp)</label>
                <input type="number" name="gaji_pokok" class="border px-3 py-2 rounded w-full" min="0" value="<?= e($edit_data['gaji_pokok'] ?? 0) ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tunjangan Beras (Rp)</label>
                <input type="number" name="tunjangan_beras" class="border px-3 py-2 rounded w-full" min="0" value="<?= e($edit_data['tunjangan_beras'] ?? 0) ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tunjangan Kehadiran (Rp)</label>
                <input type="number" name="tunjangan_kehadiran" class="border px-3 py-2 rounded w-full" min="0" value="<?= e($edit_data['tunjangan_kehadiran'] ?? 0) ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tunjangan Suami/Istri (Rp)</label>
                <input type="number" name="tunjangan_suami_istri" class="border px-3 py-2 rounded w-full" min="0" value="<?= e($edit_data['tunjangan_suami_istri'] ?? 0) ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tunjangan Anak (Rp)</label>
                <input type="number" name="tunjangan_anak" class="border px-3 py-2 rounded w-full" min="0" value="<?= e($edit_data['tunjangan_anak'] ?? 0) ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Potongan BPJS (Rp)</label>
                <input type="number" name="potongan_bpjs" class="border px-3 py-2 rounded w-full" min="0" value="<?= e($edit_data['potongan_bpjs'] ?? 0) ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Infak (Rp)</label>
                <input type="number" name="infak" class="border px-3 py-2 rounded w-full" min="0" value="<?= e($edit_data['infak'] ?? 0) ?>" required>
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center"><i class="fa fa-save mr-2"></i>Simpan</button>
            </div>
        </form>
    </div>
    <div class="bg-white p-8 rounded-xl shadow-lg mb-8">
        <h3 class="text-xl font-bold text-black mb-4" style="font-family: Arial, sans-serif;">Data Gaji Guru</h3>
        <form method="GET" action="" class="flex flex-col md:flex-row gap-4 items-end mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Guru</label>
                <select name="guru" class="border px-3 py-2 rounded w-full">
                    <option value="">Semua Guru</option>
                    <?php foreach ($guru_list as $g): ?>
                        <option value="<?= e($g['id_guru']) ?>" <?= ($filter_guru == $g['id_guru']) ? 'selected' : '' ?>>
                            <?= e($g['nama_guru']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <select name="bulan" class="border px-3 py-2 rounded w-full">
                    <option value="">Semua Bulan</option>
                    <?php foreach ($bulan_opsi as $val => $nama): ?>
                        <option value="<?= e($val) ?>" <?= ($filter_bulan == $val) ? 'selected' : '' ?>>
                            <?= e($nama) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                <select name="tahun" class="border px-3 py-2 rounded w-full">
                    <option value="">Semua Tahun</option>
                    <?php foreach ($tahun_opsi as $th): ?>
                        <option value="<?= e($th) ?>" <?= ($filter_tahun == $th) ? 'selected' : '' ?>>
                            <?= e($th) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center"><i class="fa fa-search mr-2"></i>Filter</button>
            </div>
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-center border border-black" style="font-family: Arial, sans-serif;">
                <thead style="background:#fff; color:#000;">
                    <tr style="border-bottom:2px solid #000;">
                        <th class="px-3 py-2 border border-black font-bold">NO</th>
                        <th class="px-3 py-2 border border-black font-bold">NAMA</th>
                        <th class="px-3 py-2 border border-black font-bold">BULAN</th>
                        <th class="px-3 py-2 border border-black font-bold">TAHUN</th>
                        <th class="px-3 py-2 border border-black font-bold">GAJI POKOK</th>
                        <th class="px-3 py-2 border border-black font-bold">TUNJANGAN BERAS</th>
                        <th class="px-3 py-2 border border-black font-bold">TUNJANGAN KEHADIRAN</th>
                        <th class="px-3 py-2 border border-black font-bold">TUNJANGAN SUAMI/ISTRI</th>
                        <th class="px-3 py-2 border border-black font-bold">TUNJANGAN ANAK</th>
                        <th class="px-3 py-2 border border-black font-bold">POTONGAN BPJS</th>
                        <th class="px-3 py-2 border border-black font-bold">INFAK</th>
                        <th class="px-3 py-2 border border-black font-bold">GAJI KOTOR</th>
                        <th class="px-3 py-2 border border-black font-bold">TOTAL POTONGAN</th>
                        <th class="px-3 py-2 border border-black font-bold">GAJI BERSIH</th>
                        <th class="px-3 py-2 border border-black font-bold">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    while ($row = $result->fetch_assoc()): ?>
                        <tr style="border-bottom:1px solid #000;">
                            <td class="px-3 py-2 border border-black text-center"><?= $no++ ?></td>
                            <td class="px-3 py-2 border border-black text-center"><?= e($row['nama_guru']) ?></td>
                            <td class="px-3 py-2 border border-black text-center"><?= e($bulan_opsi[$row['bulan_penggajian']] ?? $row['bulan_penggajian']) ?></td>
                            <td class="px-3 py-2 border border-black text-center"><?= e(date('Y', strtotime($row['tgl_input']))) ?></td>
                            <td class="px-3 py-2 border border-black text-center">Rp <?= number_format($row['gaji_pokok']) ?></td>
                            <td class="px-3 py-2 border border-black text-center">Rp <?= number_format($row['tunjangan_beras']) ?></td>
                            <td class="px-3 py-2 border border-black text-center">Rp <?= number_format($row['tunjangan_kehadiran']) ?></td>
                            <td class="px-3 py-2 border border-black text-center">Rp <?= number_format($row['tunjangan_suami_istri']) ?></td>
                            <td class="px-3 py-2 border border-black text-center">Rp <?= number_format($row['tunjangan_anak']) ?></td>
                            <td class="px-3 py-2 border border-black text-center">Rp <?= number_format($row['potongan_bpjs']) ?></td>
                            <td class="px-3 py-2 border border-black text-center">Rp <?= number_format($row['infak']) ?></td>
                            <td class="px-3 py-2 border border-black text-center">Rp <?= number_format($row['gaji_kotor']) ?></td>
                            <td class="px-3 py-2 border border-black text-center">Rp <?= number_format($row['total_potongan']) ?></td>
                            <td class="px-3 py-2 border border-black text-center">Rp <?= number_format($row['gaji_bersih']) ?></td>
                            <td class="px-3 py-2 border border-black text-center">
                                <a href="proses_gaji.php?action=edit&id=<?= e($row['id_penggajian']) ?>" class="text-blue-600 hover:underline mr-2"><i class="fa fa-edit"></i> Edit</a>
                                <a href="proses_gaji.php?action=delete&id=<?= e($row['id_penggajian']) ?>&token=<?= $_SESSION['csrf_token'] ?>" class="text-red-600 hover:underline" onclick="return confirm('Apakah Anda yakin ingin menghapus data gaji ini?')"><i class="fa fa-trash"></i> Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>