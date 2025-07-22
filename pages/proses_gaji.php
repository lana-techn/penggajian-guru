<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');

$conn = db_connect();
$page_title = 'Proses Penggajian Guru';

// Ambil tahun-tahun dari data guru masuk
$tahun_sekarang = (int)date('Y');
$tahun_min = $conn->query("SELECT MIN(YEAR(tgl_masuk)) as thn FROM Guru")->fetch_assoc()['thn'] ?? $tahun_sekarang;
$tahun_opsi = range($tahun_sekarang, $tahun_min);

$bulan_opsi = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

$proses = false;
$rekap = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bulan = $_POST['bulan'] ?? '';
    $tahun = $_POST['tahun'] ?? '';
    if (!$bulan || !$tahun) {
        set_flash_message('error', 'Bulan dan tahun wajib dipilih.');
    } else {
        $proses = true;
        // Ambil semua guru
        $guru_res = $conn->query("SELECT g.*, j.nama_jabatan, j.gaji_awal FROM Guru g JOIN Jabatan j ON g.id_jabatan = j.id_jabatan");
        while ($guru = $guru_res->fetch_assoc()) {
            $id_guru = $guru['id_guru'];
            $tgl_masuk = $guru['tgl_masuk'];
            $masa_kerja = max(0, $tahun - (int)date('Y', strtotime($tgl_masuk)));
            // Gaji Pokok sesuai jabatan + masa kerja
            $jabatan = $guru['nama_jabatan'];
            if (stripos($jabatan, 'kepala sekolah') !== false) {
                $gaji_awal = 1800000;
            } elseif (stripos($jabatan, 'guru kelas') !== false) {
                $gaji_awal = 1200000;
            } elseif (stripos($jabatan, 'mapel') !== false || stripos($jabatan, 'mata pelajaran') !== false) {
                $gaji_awal = 800000;
            } else {
                $gaji_awal = $guru['gaji_awal']; // fallback
            }
            $gaji_pokok = $gaji_awal + ($masa_kerja * 50000);

            // Tunjangan Beras
            $tunjangan_beras = 50000;

            // Kehadiran
            $kehadiran = $conn->query("SELECT * FROM Rekap_Kehadiran WHERE id_guru='".$conn->real_escape_string($id_guru)."' AND bulan='".$conn->real_escape_string($bulan)."' AND tahun='".$conn->real_escape_string($tahun)."' LIMIT 1")->fetch_assoc();
            $jml_terlambat = $kehadiran['jml_terlambat'] ?? 0;
            $tunjangan_kehadiran = ($jml_terlambat > 5) ? 0 : (100000 - ($jml_terlambat * 5000));
            if ($tunjangan_kehadiran < 0) $tunjangan_kehadiran = 0;

            // Tunjangan Suami/Istri
            $status_kawin = strtolower($guru['status_kawin'] ?? '');
            if ($status_kawin == 'menikah') {
                if (stripos($jabatan, 'kepala sekolah') !== false) {
                    $tunjangan_suami_istri = 100000;
                } elseif (stripos($jabatan, 'guru kelas') !== false) {
                    $tunjangan_suami_istri = 90000;
                } elseif (stripos($jabatan, 'mapel') !== false || stripos($jabatan, 'mata pelajaran') !== false) {
                    $tunjangan_suami_istri = 80000;
                } else {
                    $tunjangan_suami_istri = 0;
                }
            } else {
                $tunjangan_suami_istri = 0;
            }

            // Tunjangan Anak
            $jml_anak = (int)($guru['jml_anak'] ?? 0);
            $tunjangan_anak = ($status_kawin == 'menikah') ? min($jml_anak, 2) * 100000 : 0;

            // Potongan BPJS & Infak (2% dari gaji pokok)
            $potongan_bpjs = round($gaji_pokok * 0.02);
            $infak = round($gaji_pokok * 0.02);

            // Gaji Kotor & Bersih
            $gaji_kotor = $gaji_pokok + $tunjangan_beras + $tunjangan_kehadiran + $tunjangan_suami_istri + $tunjangan_anak;
            $total_potongan = $potongan_bpjs + $infak;
            $gaji_bersih = $gaji_kotor - $total_potongan;

            // Simpan ke Penggajian (insert/update)
            $id_penggajian = 'PG'.date('ymdHis').$id_guru;
            $cek = $conn->query("SELECT id_penggajian FROM Penggajian WHERE id_guru='".$conn->real_escape_string($id_guru)."' AND bulan_penggajian='".$conn->real_escape_string($bulan)."' AND YEAR(tgl_input)='".$conn->real_escape_string($tahun)."'");
            $tgl_input = date('Y-m-d');
            if ($cek->num_rows > 0) {
                // Update
                $row = $cek->fetch_assoc();
                $stmt = $conn->prepare("UPDATE Penggajian SET masa_kerja=?, gaji_pokok=?, tunjangan_beras=?, tunjangan_kehadiran=?, tunjangan_suami_istri=?, tunjangan_anak=?, potongan_bpjs=?, infak=?, gaji_kotor=?, total_potongan=?, gaji_bersih=?, tgl_input=? WHERE id_penggajian=?");
                $stmt->bind_param('idddddddddsss', $masa_kerja, $gaji_pokok, $tunjangan_beras, $tunjangan_kehadiran, $tunjangan_suami_istri, $tunjangan_anak, $potongan_bpjs, $infak, $gaji_kotor, $total_potongan, $gaji_bersih, $tgl_input, $row['id_penggajian']);
                $stmt->execute();
                $stmt->close();
                $id_penggajian = $row['id_penggajian'];
            } else {
                // Insert
                $stmt = $conn->prepare("INSERT INTO Penggajian (id_penggajian, id_guru, masa_kerja, gaji_pokok, tunjangan_beras, tunjangan_kehadiran, tunjangan_suami_istri, tunjangan_anak, potongan_bpjs, infak, gaji_kotor, total_potongan, gaji_bersih, tgl_input, bulan_penggajian) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssidddddddddsss', $id_penggajian, $id_guru, $masa_kerja, $gaji_pokok, $tunjangan_beras, $tunjangan_kehadiran, $tunjangan_suami_istri, $tunjangan_anak, $potongan_bpjs, $infak, $gaji_kotor, $total_potongan, $gaji_bersih, $tgl_input, $bulan);
                $stmt->execute();
                $stmt->close();
            }
            // Simpan ke array rekap untuk ditampilkan
            $rekap[] = [
                'nama_guru' => $guru['nama_guru'],
                'nipm' => $guru['nipm'],
                'jabatan' => $jabatan,
                'masa_kerja' => $masa_kerja,
                'gaji_pokok' => $gaji_pokok,
                'tunjangan_beras' => $tunjangan_beras,
                'tunjangan_kehadiran' => $tunjangan_kehadiran,
                'tunjangan_suami_istri' => $tunjangan_suami_istri,
                'tunjangan_anak' => $tunjangan_anak,
                'potongan_bpjs' => $potongan_bpjs,
                'infak' => $infak,
                'gaji_kotor' => $gaji_kotor,
                'total_potongan' => $total_potongan,
                'gaji_bersih' => $gaji_bersih
            ];
        }
        set_flash_message('success', 'Proses penggajian selesai. Data berhasil disimpan/diupdate.');
    }
}

generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';

$filter_nama = isset($_GET['nama']) ? trim($_GET['nama']) : '';
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';

if ($proses && $rekap) {
    // Terapkan filter pada $rekap
    $filtered_rekap = array_filter($rekap, function($r) use ($filter_nama, $filter_bulan, $filter_tahun, $bulan, $tahun) {
        $match = true;
        if ($filter_nama !== '' && stripos($r['nama_guru'], $filter_nama) === false) $match = false;
        if ($filter_bulan !== '' && $filter_bulan !== $bulan) $match = false;
        if ($filter_tahun !== '' && $filter_tahun !== $tahun) $match = false;
        return $match;
    });
} else {
    $filtered_rekap = $rekap;
}
?>
<div class="max-w-6xl mx-auto mt-8">
    <div class="bg-white p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-2 font-poppins flex items-center gap-2"><i class="fa-solid fa-calculator"></i> <?= e($page_title) ?></h2>
        <p class="text-gray-500 mb-4">Pilih bulan dan tahun, lalu klik <b>Proses Gaji</b> untuk menghitung dan menyimpan data penggajian seluruh guru.</p>
        <?php display_flash_message(); ?>
        <form method="POST" action="" class="flex flex-col md:flex-row gap-4 items-end mb-2">
            <?php csrf_input(); ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <select name="bulan" class="border px-3 py-2 rounded w-full" required>
                    <option value="">- Pilih Bulan -</option>
                    <?php foreach ($bulan_opsi as $val => $nama): ?>
                        <option value="<?= e($val) ?>" <?= (isset($bulan) && $bulan==$val) ? 'selected' : '' ?>><?= e($nama) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                <select name="tahun" class="border px-3 py-2 rounded w-full" required>
                    <option value="">- Pilih Tahun -</option>
                    <?php foreach ($tahun_opsi as $th): ?>
                        <option value="<?= e($th) ?>" <?= (isset($tahun) && $tahun==$th) ? 'selected' : '' ?>><?= e($th) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg shadow hover:bg-green-700 font-semibold flex items-center"><i class="fa fa-play mr-2"></i>Proses Gaji</button>
        </form>
    </div>

    <?php if ($proses && $rekap): ?>
    <div class="bg-white p-8 rounded-xl shadow-lg">
        <div class="flex flex-col md:flex-row justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-black mb-2 md:mb-0" style="font-family: Arial, sans-serif;">Data Gaji</h3>
            <form method="GET" action="" class="flex gap-2 items-center">
                <span class="text-sm mr-2" style="font-family: Arial, sans-serif;">Filter Berdasarkan</span>
                <input type="hidden" name="action" value="list">
                <input type="text" name="nama" placeholder="Nama" value="<?= e($filter_nama) ?>" style="border:2px solid #000; border-radius:20px; padding:2px 18px; font-size:14px; font-family:Arial,sans-serif; outline:none;">
                <select name="bulan" style="border:2px solid #000; border-radius:20px; padding:2px 18px; font-size:14px; font-family:Arial,sans-serif; outline:none;">
                    <option value="">Bulan</option>
                    <?php foreach ($bulan_opsi as $val => $nama): ?>
                        <option value="<?= e($val) ?>" <?= ($filter_bulan == $val) ? 'selected' : '' ?>><?= e($nama) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="tahun" style="border:2px solid #000; border-radius:20px; padding:2px 18px; font-size:14px; font-family:Arial,sans-serif; outline:none;">
                    <option value="">Tahun</option>
                    <?php foreach ($tahun_opsi as $th): ?>
                        <option value="<?= e($th) ?>" <?= ($filter_tahun == $th) ? 'selected' : '' ?>><?= e($th) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="hidden"></button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-center border border-black" style="font-family: Arial, sans-serif;">
                <thead style="background:#fff; color:#000;">
                    <tr style="border-bottom:2px solid #000;">
                        <th class="px-3 py-2 border border-black font-bold">NO</th>
                        <th class="px-3 py-2 border border-black font-bold">NO SLIP GAJI</th>
                        <th class="px-3 py-2 border border-black font-bold">NAMA</th>
                        <th class="px-3 py-2 border border-black font-bold">GAJI POKOK</th>
                        <th class="px-3 py-2 border border-black font-bold">TUNJANGAN BERAS</th>
                        <th class="px-3 py-2 border border-black font-bold">TUNJANGAN KEHADIRAN</th>
                        <th class="px-3 py-2 border border-black font-bold">GAJI KOTOR</th>
                        <th class="px-3 py-2 border border-black font-bold">POTONGAN</th>
                        <th class="px-3 py-2 border border-black font-bold">GAJI BERSIH</th>
                        <th class="px-3 py-2 border border-black font-bold">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; foreach($filtered_rekap as $r): ?>
                    <tr style="border-bottom:1px solid #000;">
                        <td class="px-3 py-2 border border-black text-center"><?= $no ?></td>
                        <td class="px-3 py-2 border border-black text-center">SLIP-<?= str_pad($no, 4, '0', STR_PAD_LEFT) ?></td>
                        <td class="px-3 py-2 border border-black text-center"><?= e($r['nama_guru']) ?></td>
                        <td class="px-3 py-2 border border-black text-center">Rp</td>
                        <td class="px-3 py-2 border border-black text-center">Rp</td>
                        <td class="px-3 py-2 border border-black text-center">Rp</td>
                        <td class="px-3 py-2 border border-black text-center">Rp</td>
                        <td class="px-3 py-2 border border-black text-center">Rp</td>
                        <td class="px-3 py-2 border border-black text-center">Rp</td>
                        <td class="px-3 py-2 border border-black text-center">
                            <button style="background:#888; color:#fff; border-radius:16px; padding:2px 16px; font-size:13px; margin-right:2px; border:none;">Cetak</button>
                            <button style="background:#FFD600; color:#fff; border-radius:16px; padding:2px 16px; font-size:13px; margin-right:2px; border:none;">Edit</button>
                            <button style="background:#F44336; color:#fff; border-radius:16px; padding:2px 16px; font-size:13px; border:none;">Hapus</button>
                        </td>
                    </tr>
                    <?php $no++; endforeach; ?>
                    <?php for($i=$no; $i<=4; $i++): ?>
                    <tr style="border-bottom:1px solid #000;">
                        <td class="px-3 py-2 border border-black text-center"><?= $i ?></td>
                        <td class="px-3 py-2 border border-black text-center"></td>
                        <td class="px-3 py-2 border border-black text-center"></td>
                        <td class="px-3 py-2 border border-black text-center"></td>
                        <td class="px-3 py-2 border border-black text-center"></td>
                        <td class="px-3 py-2 border border-black text-center"></td>
                        <td class="px-3 py-2 border border-black text-center"></td>
                        <td class="px-3 py-2 border border-black text-center"></td>
                        <td class="px-3 py-2 border border-black text-center"></td>
                        <td class="px-3 py-2 border border-black text-center"></td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?> 