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
<aside class="fixed inset-y-0 left-0 z-50 h-screen w-64 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
    <div class="flex h-full flex-col bg-gradient-to-br from-white via-gray-50 to-green-50 shadow-2xl">
        <!-- Header -->
        <div class="relative flex items-center justify-between px-6 py-5 border-b border-gray-100">
            <div class="flex items-center space-x-3">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo" class="h-12 w-auto transform transition-transform hover:scale-105">
                <div>
                    <h1 class="font-bold text-xl text-gray-800 tracking-wide">SDUMK</h1>
                    <div class="mt-1 inline-flex items-center rounded-full bg-gradient-to-r from-green-100 to-emerald-100 px-3 py-1 text-xs font-medium text-green-800 ring-1 ring-inset ring-green-200/40 shadow-sm">
                        <?= ucfirst(str_replace('_', ' ', $user_role)) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-4 py-6">
            <ul class="space-y-2">
                <?php foreach ($navigation as $item): ?>
                    <li>
                        <a href="<?= $item['href'] ?>"
                            class="group relative flex items-center gap-3 rounded-xl px-4 py-3 font-medium transition-all duration-200
                                  <?= $item['active']
                                        ? 'bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg shadow-green-200'
                                        : 'text-gray-700 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 hover:text-green-700' ?>">
                            <?= $item['icon'] ?>
                            <span class="flex-1"><?= e($item['name']) ?></span>
                            <?php if ($item['active']): ?>
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 h-2 w-2 rounded-full bg-white shadow-inner"></span>
                            <?php endif; ?>

                            <!-- Hover Effect -->
                            <span class="absolute bottom-0 left-0 h-0.5 w-0 bg-green-500 transition-all duration-200 
                                       <?= $item['active'] ? 'w-full' : 'group-hover:w-full' ?>"></span>
                        </a>
                    </li>
                <?php endforeach; ?>

                <!-- Logout Button -->
                <li class="mt-6 px-3">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/auth/logout.php"
                        class="mt-6 flex items-center gap-3 rounded-xl px-4 py-3 font-medium text-red-600 hover:bg-red-50 transition-all duration-200">
                        <svg class="size-5" ...></svg>
                        <span>Keluar</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Footer -->
        <div class="mt-auto border-t border-gray-100 p-4">
            <div class="flex flex-col items-center space-y-2 text-center">
                <p class="text-xs text-gray-500">
                    &copy; <?= date('Y') ?> SD Unggulan<br>Muhammadiyah Kretek
                </p>
            </div>
        </div>
    </div>
</aside>
<!-- End Sidebar -->