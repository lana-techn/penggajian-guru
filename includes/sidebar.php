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
        ],
        [
            'name' => 'Data Guru',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/guru.php',
        ],
        [
            'name' => 'Data User/Admin',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/users.php',
        ],
        [
            'name' => 'Jabatan',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/jabatan.php',
        ],
        [
            'name' => 'Tunjangan',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/tunjangan.php',
        ],
        [
            'name' => 'Potongan',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/potongan.php',
        ],
        [
            'name' => 'Absensi',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/absensi.php',
        ],
        [
            'name' => 'Proses Gaji',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/proses_gaji.php',
        ],
        [
            'name' => 'Laporan Gaji',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/detail_gaji_guru.php',
        ],
    ];
} elseif ($user_role === 'kepala_sekolah') {
    $navigation = [
        [
            'name' => 'Dashboard',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/index_kepsek.php',
        ],
        [
            'name' => 'Kelola Admin',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/users.php',
        ],
        [
            'name' => 'Laporan Gaji',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/kepala_sekolah/laporan.php',
        ],
    ];
} elseif ($user_role === 'guru') {
    $navigation = [
        [
            'name' => 'Dashboard',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/index_guru.php',
        ],
        [
            'name' => 'Slip Gaji Saya',
            'icon' => '<svg class="size-5" ...></svg>',
            'href' => BASE_URL . '/pages/guru/slip_gaji.php',
        ],
    ];
}
?>
<!-- Sidebar -->
<aside class="h-screen w-64 bg-white border-r border-gray-200 flex flex-col">
    <div class="flex items-center h-20 px-6 font-bold text-xl text-black border-b border-gray-100">
        <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo" class="h-10 w-auto mr-2">SDUMK
    </div>
    <nav class="flex-1 overflow-y-auto px-2 py-4">
        <ul class="space-y-1">
            <?php foreach ($navigation as $item): ?>
                <?php if (isset($item['children'])): ?>
                    <li x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open" class="flex items-center w-full px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100 focus:outline-none transition">
                            <?= $item['icon'] ?>
                            <span class="ml-3 flex-1 text-left"> <?= e($item['name']) ?> </span>
                            <svg :class="{'rotate-90': open}" class="size-4 ml-auto transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <ul x-show="open" x-transition class="pl-8 mt-1 space-y-1">
                            <?php foreach ($item['children'] as $child): ?>
                                <li>
                                    <a href="<?= $child['href'] ?>" class="block px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50 transition"> <?= e($child['name']) ?> </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="<?= $item['href'] ?>" class="flex items-center px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100 transition">
                            <?= $item['icon'] ?>
                            <span class="ml-3"> <?= e($item['name']) ?> </span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
            <li class="mt-4">
                <a href="<?= BASE_URL ?>/auth/logout.php" class="flex items-center px-3 py-2 rounded-lg text-red-600 hover:bg-red-100 transition">
                    <svg class="size-5" ...></svg>
                    <span class="ml-3">Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
<!-- End Sidebar -->
