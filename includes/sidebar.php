<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_role = strtolower($_SESSION['role'] ?? '');
$current_page_filename = basename($_SERVER['PHP_SELF']);
$navigation = [];
if ($user_role === 'admin') {
    $navigation = [
        [
            'name' => 'Dashboard',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/index.php',
            'active' => $current_page_filename === 'index.php',
        ],
        [
            'name' => 'Data Guru',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/guru.php',
            'active' => $current_page_filename === 'guru.php',
        ],
        [
            'name' => 'Data User/Admin',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/users.php',
            'active' => $current_page_filename === 'users.php',
        ],
        [
            'name' => 'Jabatan',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/jabatan.php',
            'active' => $current_page_filename === 'jabatan.php',
        ],
        [
            'name' => 'Tunjangan',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/tunjangan.php',
            'active' => $current_page_filename === 'tunjangan.php',
        ],
        [
            'name' => 'Potongan',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/potongan.php',
            'active' => $current_page_filename === 'potongan.php',
        ],
        [
            'name' => 'Absensi',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/absensi.php',
            'active' => $current_page_filename === 'absensi.php',
        ],
        [
            'name' => 'Proses Gaji',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/proses_gaji.php',
            'active' => $current_page_filename === 'proses_gaji.php',
        ],
        [
            'name' => 'Laporan Gaji',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/detail_gaji_guru.php',
            'active' => $current_page_filename === 'detail_gaji_guru.php',
        ],
    ];
} elseif ($user_role === 'kepala_sekolah') {
    $navigation = [
        [
            'name' => 'Dashboard',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/index_kepsek.php',
            'active' => $current_page_filename === 'index_kepsek.php',
        ],
        [
            'name' => 'Kelola Admin',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/users.php',
            'active' => $current_page_filename === 'users.php',
        ],
        [
            'name' => 'Laporan Gaji',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/kepala_sekolah/laporan.php',
            'active' => $current_page_filename === 'laporan.php',
        ],
    ];
} elseif ($user_role === 'guru') {
    $navigation = [
        [
            'name' => 'Dashboard',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/index_guru.php',
            'active' => $current_page_filename === 'index_guru.php',
        ],
        [
            'name' => 'Slip Gaji Saya',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/guru/slip_gaji.php',
            'active' => $current_page_filename === 'slip_gaji.php',
        ],
    ];
}
?>
<!-- Sidebar -->
<aside class="h-screen w-64 bg-gradient-to-b from-white to-gray-50 border-r border-gray-200 flex flex-col shadow-lg">
    <div class="flex items-center h-20 px-6 font-bold text-xl text-black border-b border-gray-100 relative">
        <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo" class="h-10 w-auto mr-2">
        SDUMK
        <span class="absolute right-6 top-1/2 -translate-y-1/2 bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full border border-green-300 shadow-sm">
            <?= ucfirst(str_replace('_', ' ', $user_role)) ?>
        </span>
    </div>
    <nav class="flex-1 overflow-y-auto px-2 py-4">
        <ul class="space-y-1">
            <?php foreach ($navigation as $item): ?>
                <li>
                    <a href="<?= $item['href'] ?>"
                       class="flex items-center px-3 py-2 rounded-lg transition font-medium
                              <?= $item['active'] ? 'bg-green-600 text-white shadow-lg' : 'text-gray-800 hover:bg-green-100 hover:text-green-700' ?>
                              group border border-transparent <?= $item['active'] ? 'border-green-700' : '' ?>">
                        <?= $item['icon'] ?>
                        <span class="ml-3"> <?= e($item['name']) ?> </span>
                        <?php if ($item['active']): ?>
                            <span class="ml-auto inline-block w-2 h-2 bg-white rounded-full border border-green-700"></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <li class="mt-4">
                <a href="<?= BASE_URL ?>/auth/logout.php" class="flex items-center px-3 py-2 rounded-lg text-red-600 hover:bg-red-100 transition font-medium">
                    <svg class="size-5" ...></svg>
                    <span class="ml-3">Logout</span>
                </a>
            </li>
        </ul>
        <div class="mt-8 px-3 text-xs text-gray-400">
            &copy; <?= date('Y') ?> SD Unggulan Muhammadiyah Kretek
        </div>
    </nav>
</aside>
<!-- End Sidebar -->
