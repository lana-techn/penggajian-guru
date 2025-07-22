<?php
include '../includes/navbar.php';
require_once '../config/koneksi.php';
$id = $_GET['id'] ?? 0;
$guru = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM guru WHERE id = $id"));
$jabatans = mysqli_query($conn, "SELECT * FROM jabatan ORDER BY nama_jabatan ASC");
if (!$guru) {
    echo '<div class="p-8">Data tidak ditemukan.</div>';
    exit;
}
?>
<div class="p-8 max-w-xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">Edit Guru</h1>
    <form action="guru_edit_proses.php" method="post" class="space-y-4">
        <input type="hidden" name="id" value="<?= $guru['id'] ?>">
        <div>
            <label class="block font-semibold mb-1">NIP</label>
            <input type="text" name="nip" class="w-full border px-3 py-2 rounded" value="<?= htmlspecialchars($guru['nip']) ?>" required>
        </div>
        <div>
            <label class="block font-semibold mb-1">Nama Lengkap</label>
            <input type="text" name="nama_lengkap" class="w-full border px-3 py-2 rounded" value="<?= htmlspecialchars($guru['nama_lengkap']) ?>" required>
        </div>
        <div>
            <label class="block font-semibold mb-1">Jabatan</label>
            <select name="jabatan_id" class="w-full border px-3 py-2 rounded" required>
                <option value="">-- Pilih Jabatan --</option>
                <?php while ($j = mysqli_fetch_assoc($jabatans)): ?>
                <option value="<?= $j['id'] ?>" <?= $guru['jabatan_id'] == $j['id'] ? 'selected' : '' ?>><?= htmlspecialchars($j['nama_jabatan']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label class="block font-semibold mb-1">Tanggal Masuk</label>
            <input type="date" name="tanggal_masuk" class="w-full border px-3 py-2 rounded" value="<?= htmlspecialchars($guru['tanggal_masuk']) ?>" required>
        </div>
        <div>
            <label class="block font-semibold mb-1">Status Pernikahan</label>
            <select name="status_pernikahan" class="w-full border px-3 py-2 rounded" required>
                <option value="menikah" <?= $guru['status_pernikahan'] == 'menikah' ? 'selected' : '' ?>>Menikah</option>
                <option value="belum_menikah" <?= $guru['status_pernikahan'] == 'belum_menikah' ? 'selected' : '' ?>>Belum Menikah</option>
            </select>
        </div>
        <div>
            <label class="block font-semibold mb-1">Jumlah Anak</label>
            <input type="number" name="jumlah_anak" min="0" class="w-full border px-3 py-2 rounded" value="<?= htmlspecialchars($guru['jumlah_anak']) ?>" required>
        </div>
        <div>
            <label class="block font-semibold mb-1">Alamat</label>
            <textarea name="alamat" class="w-full border px-3 py-2 rounded"><?= htmlspecialchars($guru['alamat']) ?></textarea>
        </div>
        <div>
            <label class="block font-semibold mb-1">No. Telepon</label>
            <input type="text" name="no_telepon" class="w-full border px-3 py-2 rounded" value="<?= htmlspecialchars($guru['no_telepon']) ?>">
        </div>
        <div>
            <label class="block font-semibold mb-1">Email</label>
            <input type="email" name="email" class="w-full border px-3 py-2 rounded" value="<?= htmlspecialchars($guru['email']) ?>">
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Simpan Perubahan</button>
        <a href="guru.php" class="ml-2 text-gray-600 hover:underline">Batal</a>
    </form>
</div> 