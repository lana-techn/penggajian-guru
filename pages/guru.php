<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireAnyRole(['admin', 'kepala_sekolah']);

$is_kepala_sekolah = ($_SESSION['role'] === 'kepala_sekolah');

$conn = db_connect();
$page_title = 'Manajemen Guru';

// --- LOGIKA PROSES (CREATE, UPDATE, DELETE) ---

// Proses Tambah & Update (Only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_kepala_sekolah) {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');

    $id_guru = $_POST['id_guru'] ?? null;
    $id_user = $_POST['id_user'];
    $id_jabatan = $_POST['id_jabatan'];
    $id_tunjangan = $_POST['id_tunjangan'] ?? null; // Add tunjangan field
    $nama_guru = trim($_POST['nama_guru']);
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $no_hp = trim($_POST['no_hp']);
    $nipm = trim($_POST['nipm']);
    $tgl_masuk = $_POST['tgl_masuk'];
    $email = trim($_POST['email']);
    $status_kawin = $_POST['status_kawin'];
    $jml_anak = (int)$_POST['jml_anak'];

    // Validasi dasar
    if (empty($nama_guru) || empty($id_user) || empty($id_jabatan) || empty($tgl_masuk)) {
        set_flash_message('error', 'Kolom yang wajib diisi tidak boleh kosong.');
    } else {
        if ($id_guru) { // Update
            $stmt = $conn->prepare("UPDATE Guru SET id_user=?, id_jabatan=?, id_tunjangan=?, nama_guru=?, jenis_kelamin=?, no_hp=?, nipm=?, tgl_masuk=?, email=?, status_kawin=?, jml_anak=? WHERE id_guru=?");
            $stmt->bind_param('ssssssssssiss', $id_user, $id_jabatan, $id_tunjangan, $nama_guru, $jenis_kelamin, $no_hp, $nipm, $tgl_masuk, $email, $status_kawin, $jml_anak, $id_guru);
            $action_text = 'diperbarui';
        } else { // Tambah
            $id_guru = 'G' . date('ymdHis');
            $stmt = $conn->prepare("INSERT INTO Guru (id_guru, id_user, id_jabatan, id_tunjangan, nama_guru, jenis_kelamin, no_hp, nipm, tgl_masuk, email, status_kawin, jml_anak) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssi", $id_guru, $id_user, $id_jabatan, $id_tunjangan, $nama_guru, $jenis_kelamin, $no_hp, $nipm, $tgl_masuk, $email, $status_kawin, $jml_anak);
            $action_text = 'ditambahkan';
        }

        if ($stmt->execute()) {
            set_flash_message('success', "Data guru berhasil {$action_text}.");
        } else {
            set_flash_message('error', "Gagal memproses data guru: " . $stmt->error);
        }
        $stmt->close();
    }
    header('Location: guru.php');
    exit;
}

// Proses Hapus (Only for admin)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && !$is_kepala_sekolah) {
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $id_guru = trim($_GET['id']);
        
        // Validasi ID guru tidak kosong
        if (empty($id_guru)) {
            set_flash_message('error', 'ID guru tidak valid.');
            header('Location: guru.php');
            exit;
        }
        
        // Cek apakah guru benar-benar ada di database
        $check_stmt = $conn->prepare("SELECT nama_guru FROM Guru WHERE id_guru = ?");
        $check_stmt->bind_param("s", $id_guru);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            $check_stmt->close();
            set_flash_message('error', 'Data guru tidak ditemukan.');
            header('Location: guru.php');
            exit;
        }
        
        $guru_data = $check_result->fetch_assoc();
        $nama_guru = $guru_data['nama_guru'];
        $check_stmt->close();
        
        // Hitung jumlah data terkait yang akan terhapus
        $count_penggajian_stmt = $conn->prepare("SELECT COUNT(*) as total FROM penggajian WHERE id_guru = ?");
        $count_penggajian_stmt->bind_param("s", $id_guru);
        $count_penggajian_stmt->execute();
        $penggajian_result = $count_penggajian_stmt->get_result();
        $penggajian_count = $penggajian_result->fetch_assoc()['total'];
        $count_penggajian_stmt->close();
        
        $count_kehadiran_stmt = $conn->prepare("SELECT COUNT(*) as total FROM rekap_kehadiran WHERE id_guru = ?");
        $count_kehadiran_stmt->bind_param("s", $id_guru);
        $count_kehadiran_stmt->execute();
        $kehadiran_result = $count_kehadiran_stmt->get_result();
        $kehadiran_count = $kehadiran_result->fetch_assoc()['total'];
        $count_kehadiran_stmt->close();
        
        // Mulai transaksi untuk memastikan integritas data
        $conn->begin_transaction();
        
        try {
            // Step 1: Hapus semua data penggajian yang terkait dengan guru ini terlebih dahulu
            $stmt_penggajian = $conn->prepare("DELETE FROM penggajian WHERE id_guru = ?");
            $stmt_penggajian->bind_param("s", $id_guru);
            
            if (!$stmt_penggajian->execute()) {
                throw new Exception("Gagal menghapus data penggajian: " . $stmt_penggajian->error);
            }
            
            $deleted_penggajian = $stmt_penggajian->affected_rows;
            $stmt_penggajian->close();
            
            // Step 2: Hapus semua data rekap kehadiran yang terkait dengan guru ini
            $stmt_kehadiran = $conn->prepare("DELETE FROM rekap_kehadiran WHERE id_guru = ?");
            $stmt_kehadiran->bind_param("s", $id_guru);
            
            if (!$stmt_kehadiran->execute()) {
                throw new Exception("Gagal menghapus data rekap kehadiran: " . $stmt_kehadiran->error);
            }
            
            $deleted_kehadiran = $stmt_kehadiran->affected_rows;
            $stmt_kehadiran->close();
            
            // Step 3: Hapus data guru
            $stmt_guru = $conn->prepare("DELETE FROM Guru WHERE id_guru = ?");
            $stmt_guru->bind_param("s", $id_guru);
            
            if (!$stmt_guru->execute()) {
                throw new Exception("Gagal menghapus data guru: " . $stmt_guru->error);
            }
            
            if ($stmt_guru->affected_rows === 0) {
                throw new Exception("Tidak ada data guru yang dihapus. Data mungkin sudah tidak ada.");
            }
            
            $stmt_guru->close();
            
            // Step 4: Commit transaksi jika semua berhasil
            $conn->commit();
            
            // Step 5: Tampilkan pesan sukses yang detail
            $success_message = "✅ Data guru '{$nama_guru}' berhasil dihapus.";
            $deleted_items = [];
            
            if ($deleted_penggajian > 0) {
                $deleted_items[] = "{$deleted_penggajian} data penggajian";
            }
            
            if ($deleted_kehadiran > 0) {
                $deleted_items[] = "{$deleted_kehadiran} data kehadiran";
            }
            
            if (!empty($deleted_items)) {
                $success_message .= " (" . implode(", ", $deleted_items) . " terkait juga telah dihapus)";
            }
            
            set_flash_message('success', $success_message);
            
        } catch (Exception $e) {
            // Rollback jika ada error
            $conn->rollback();
            set_flash_message('error', '❌ Gagal menghapus data guru: ' . $e->getMessage());
        }
    } else {
        set_flash_message('error', '🔒 Token keamanan tidak valid. Silakan coba lagi.');
    }
    header('Location: guru.php');
    exit;
}

// --- LOGIKA PENGAMBILAN DATA ---

// Data untuk dropdown
$jabatan_list = $conn->query("SELECT id_jabatan, nama_jabatan FROM Jabatan ORDER BY nama_jabatan")->fetch_all(MYSQLI_ASSOC);
$tunjangan_list = $conn->query("SELECT id_tunjangan, tunjangan_suami_istri, tunjangan_anak, tunjangan_beras FROM Tunjangan ORDER BY id_tunjangan")->fetch_all(MYSQLI_ASSOC);
$users_list_query = "SELECT u.id_user, u.username FROM User u LEFT JOIN Guru g ON u.id_user = g.id_user WHERE u.akses = 'Guru' AND g.id_guru IS NULL";
$users_list = $conn->query($users_list_query)->fetch_all(MYSQLI_ASSOC);

// Filter dan Pagination
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Query data guru dengan filter dan pagination
$sql_where = " WHERE (g.nama_guru LIKE ? OR g.nipm LIKE ?)";
$search_param = "%{$search}%";
$params = [$search_param, $search_param];
$types = 'ss';

$total_records_stmt = $conn->prepare("SELECT COUNT(g.id_guru) as total FROM Guru g" . $sql_where);
$total_records_stmt->bind_param($types, ...$params);
$total_records_stmt->execute();
$total_records = $total_records_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

$guru_list_stmt = $conn->prepare("SELECT g.*, j.nama_jabatan, u.username FROM Guru g JOIN Jabatan j ON g.id_jabatan = j.id_jabatan JOIN User u ON g.id_user = u.id_user" . $sql_where . " ORDER BY g.nama_guru ASC LIMIT ? OFFSET ?");
$params[] = $records_per_page;
$params[] = $offset;
$types .= 'ii';
$guru_list_stmt->bind_param($types, ...$params);
$guru_list_stmt->execute();
$guru_result = $guru_list_stmt->get_result();

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="crudPage()">
    <!-- Tombol Tambah dan Judul Halaman -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 font-poppins"><?= e($page_title) ?></h1>
            <p class="text-gray-500 mt-1">Kelola data, akun, dan informasi pribadi guru.</p>
        </div>
        <?php if (!$is_kepala_sekolah): ?>
        <button @click="showForm = true; isEdit = false; resetForm()" class="bg-green-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center transition">
            <i class="fa-solid fa-plus mr-2"></i> Tambah Guru
        </button>
        <?php else: ?>
        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg text-sm font-medium">
            <i class="fa-solid fa-eye mr-2"></i> Mode Lihat Saja
        </div>
        <?php endif; ?>
    </div>

    <?php display_flash_message(); ?>

    <!-- Form Tambah/Edit (Hidden by default) - Only for admin -->
    <?php if (!$is_kepala_sekolah): ?>
    <div x-show="showForm" x-transition class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg mb-8 border-t-4 border-green-500">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 font-poppins" x-text="isEdit ? 'Edit Data Guru' : 'Tambah Guru Baru'"></h2>
        <form method="POST" action="guru.php">
            <?php csrf_input(); ?>
            <input type="hidden" name="id_guru" x-model="formData.id_guru">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Kolom 1 -->
                <div class="space-y-4">
                    <div>
                        <label for="nama_guru" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <input type="text" name="nama_guru" x-model="formData.nama_guru" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" required>
                    </div>
                    <div>
                        <label for="nipm" class="block text-sm font-medium text-gray-700">NIPM</label>
                        <input type="text" name="nipm" x-model="formData.nipm" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>
                    <div>
                        <label for="jenis_kelamin" class="block text-sm font-medium text-gray-700">Jenis Kelamin</label>
                        <select name="jenis_kelamin" x-model="formData.jenis_kelamin" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>

                <!-- Kolom 2 -->
                <div class="space-y-4">
                    <div>
                        <label for="id_jabatan" class="block text-sm font-medium text-gray-700">Jabatan</label>
                        <select name="id_jabatan" x-model="formData.id_jabatan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" required>
                            <option value="">- Pilih Jabatan -</option>
                            <?php foreach ($jabatan_list as $j): ?>
                                <option value="<?= e($j['id_jabatan']) ?>"><?= e($j['nama_jabatan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="id_tunjangan" class="block text-sm font-medium text-gray-700">Paket Tunjangan</label>
                        <select name="id_tunjangan" x-model="formData.id_tunjangan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option value="">- Pilih Paket Tunjangan -</option>
                            <?php foreach ($tunjangan_list as $t): ?>
                                <option value="<?= e($t['id_tunjangan']) ?>">
                                    <?= e($t['id_tunjangan']) ?> - Suami/Istri: Rp <?= number_format($t['tunjangan_suami_istri'], 0, ',', '.') ?>, Anak: Rp <?= number_format($t['tunjangan_anak'], 0, ',', '.') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Pilih paket tunjangan yang sesuai untuk guru ini</p>
                    </div>
                    <div>
                        <label for="tgl_masuk" class="block text-sm font-medium text-gray-700">Tanggal Masuk</label>
                        <input type="date" name="tgl_masuk" x-model="formData.tgl_masuk" min="2009-06-01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" required>
                        <p class="text-xs text-gray-500 mt-1">Minimal tanggal: 1 Juni 2009</p>
                    </div>
                    <div>
                        <label for="id_user" class="block text-sm font-medium text-gray-700">Akun User</label>
                        <select name="id_user" x-model="formData.id_user" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" required>
                            <option value="">- Pilih Akun -</option>
                            <template x-if="isEdit && formData.current_user">
                                <option :value="formData.current_user.id" x-text="formData.current_user.username"></option>
                            </template>
                            <?php foreach ($users_list as $u): ?>
                                <option value="<?= e($u['id_user']) ?>"><?= e($u['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Kolom 3 -->
                <div class="space-y-4">
                    <div>
                        <label for="status_kawin" class="block text-sm font-medium text-gray-700">Status Kawin</label>
                        <select name="status_kawin" x-model="formData.status_kawin" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option value="Belum Menikah">Belum Menikah</option>
                            <option value="Menikah">Menikah</option>
                        </select>
                    </div>
                    <div>
                        <label for="jml_anak" class="block text-sm font-medium text-gray-700">Jumlah Anak</label>
                        <input type="number" name="jml_anak" x-model="formData.jml_anak" min="0" max="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                        <p class="text-xs text-gray-500 mt-1">Maksimal 2 anak</p>
                    </div>
                     <div>
                        <label for="no_hp" class="block text-sm font-medium text-gray-700">No. HP</label>
                        <input type="tel" name="no_hp" x-model="formData.no_hp" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>
                     <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" x-model="formData.email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi Form -->
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" @click="showForm = false" class="bg-gray-200 text-gray-700 px-5 py-2.5 rounded-lg hover:bg-gray-300 font-semibold transition">Batal</button>
                <button type="submit" class="bg-green-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center transition">
                    <i class="fa fa-save mr-2"></i> <span x-text="isEdit ? 'Simpan Perubahan' : 'Simpan Data'"></span>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Daftar Guru -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800 font-poppins">Daftar Guru</h3>
            <form method="GET" action="" class="w-full max-w-sm">
                <div class="relative">
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari nama atau NIPM..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fa-solid fa-search text-gray-400"></i></div>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-gray-600">
                <thead class="bg-gray-100 text-gray-700 uppercase font-poppins text-xs">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Nama Guru</th>
                        <th class="px-4 py-3">NIPM</th>
                        <th class="px-4 py-3">Jabatan</th>
                        <th class="px-4 py-3">Tgl Masuk</th>
                        <th class="px-4 py-3">Kontak</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($guru_result->num_rows > 0): ?>
                        <?php $no = $offset + 1; while ($row = $guru_result->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= $no++ ?></td>
                                <td class="px-4 py-3 font-semibold text-gray-900"><?= e($row['nama_guru']) ?></td>
                                <td class="px-4 py-3"><?= e($row['nipm']) ?></td>
                                <td class="px-4 py-3"><?= e($row['nama_jabatan']) ?></td>
                                <td class="px-4 py-3"><?= date("d M Y", strtotime($row['tgl_masuk'])) ?></td>
                                <td class="px-4 py-3"><?= e($row['no_hp']) ?><br><span class="text-xs text-gray-500"><?= e($row['email']) ?></span></td>
                                <td class="px-4 py-3 text-center">
                                    <?php if (!$is_kepala_sekolah): ?>
                                    <div class="flex items-center justify-center space-x-3">
                                        <button @click="editGuru(<?= htmlspecialchars(json_encode($row)) ?>)" class="text-blue-600 hover:text-blue-800" title="Edit">
                                            <i class="fa-solid fa-pencil fa-fw"></i>
                                        </button>
                                        <a href="?action=delete&id=<?= e($row['id_guru']) ?>&token=<?= $_SESSION['csrf_token'] ?>" onclick="event.preventDefault(); handleDelete(this, '<?= e($row['nama_guru']) ?>', '<?= e($row['id_guru']) ?>')" class="text-red-600 hover:text-red-800" title="Hapus">
                                            <i class="fa-solid fa-trash fa-fw"></i>
                                        </a>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-gray-400 text-sm">Read Only</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-10 text-gray-500">
                                <i class="fa-solid fa-user-slash fa-3x mb-3"></i>
                                <p>Tidak ada data guru yang ditemukan.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="mt-6">
            <?= generate_pagination_links($page, $total_pages, 'guru.php', ['search' => $search]) ?>
        </div>
    </div>
</div>

<script>
// Function untuk handle delete dengan async confirmation
async function handleDelete(linkElement, namaGuru, idGuru) {
    const confirmed = await confirmDelete(namaGuru, idGuru);
    if (confirmed) {
        window.location.href = linkElement.href;
    }
}

// Function untuk konfirmasi delete dengan AJAX check
async function confirmDelete(namaGuru, idGuru) {
    try {
        // Ambil jumlah data penggajian yang akan terhapus menggunakan fetch
        const response = await fetch(`check_penggajian_count.php?id_guru=${encodeURIComponent(idGuru)}`);
        const data = await response.json();
        
        let message = `⚠️ PERINGATAN HAPUS DATA!\n\n`;
        message += `Guru: ${namaGuru}\n`;
        
        if (data.success) {
            const penggajianCount = data.penggajian_count || 0;
            const kehadiranCount = data.kehadiran_count || 0;
            const totalRelated = data.total_related_data || 0;
            
            if (totalRelated > 0) {
                message += `\n📊 DATA TERKAIT YANG AKAN IKUT TERHAPUS:\n`;
                if (penggajianCount > 0) {
                    message += `• ${penggajianCount} data penggajian\n`;
                }
                if (kehadiranCount > 0) {
                    message += `• ${kehadiranCount} data rekap kehadiran\n`;
                }
                message += `\n⚠️ DAMPAK:\n`;
                message += `• Data guru "${namaGuru}" akan dihapus permanen\n`;
                if (penggajianCount > 0) {
                    message += `• ${penggajianCount} riwayat penggajian akan hilang selamanya\n`;
                }
                if (kehadiranCount > 0) {
                    message += `• ${kehadiranCount} rekap kehadiran akan hilang selamanya\n`;
                }
            } else {
                message += `\n✅ Tidak ada data terkait (penggajian/kehadiran)\n`;
                message += `\n⚠️ DAMPAK:\n`;
                message += `• Hanya data guru "${namaGuru}" yang akan dihapus\n`;
            }
        } else {
            message += `\n❌ Tidak dapat memverifikasi data terkait\n`;
            message += `\n⚠️ DAMPAK:\n`;
            message += `• Data guru "${namaGuru}" akan dihapus\n`;
            message += `• Semua data terkait (penggajian/kehadiran) akan ikut terhapus\n`;
        }
        
        message += `\n🚨 TINDAKAN INI TIDAK DAPAT DIBATALKAN!\n`;
        message += `\nApakah Anda yakin ingin melanjutkan?`;
        
        return confirm(message);
        
    } catch (error) {
        console.error('Error checking penggajian data:', error);
        // Fallback ke konfirmasi standar jika AJAX gagal
        const message = `⚠️ PERINGATAN!\n\nMenghapus guru "${namaGuru}" akan secara otomatis menghapus SEMUA data penggajian yang terkait.\n\nApakah Anda yakin ingin melanjutkan?\nTindakan ini tidak dapat dibatalkan!`;
        return confirm(message);
    }
}

function crudPage() {
    return {
        showForm: false,
        isEdit: false,
        formData: {},
        
        init() {
            this.resetForm();
        },

        resetForm() {
            this.formData = {
                id_guru: null,
                id_user: '',
                id_jabatan: '',
                id_tunjangan: '',
                nama_guru: '',
                jenis_kelamin: 'Laki-laki',
                no_hp: '',
                nipm: '',
                tgl_masuk: new Date().toISOString().slice(0, 10),
                email: '',
                status_kawin: 'Belum Menikah',
                jml_anak: 0,
                current_user: null
            };
        },

        editGuru(guruData) {
            this.isEdit = true;
            this.formData = {
                id_guru: guruData.id_guru,
                id_user: guruData.id_user,
                id_jabatan: guruData.id_jabatan,
                id_tunjangan: guruData.id_tunjangan || '',
                nama_guru: guruData.nama_guru,
                jenis_kelamin: guruData.jenis_kelamin,
                no_hp: guruData.no_hp,
                nipm: guruData.nipm,
                tgl_masuk: guruData.tgl_masuk,
                email: guruData.email,
                status_kawin: guruData.status_kawin,
                jml_anak: guruData.jml_anak,
                current_user: { id: guruData.id_user, username: guruData.username }
            };
            this.showForm = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
