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
            'icon' => '<svg class="size-5" ...</svg>',
            'href' => BASE_URL . '/pages/laporan_admin.php',
            'active' => $current_page_filename === 'laporan_admin.php' || $current_page_filename === 'detail_gaji_guru.php',
        ],
    ];
} elseif ($user_role === 'kepala_sekolah') {
    $navigation = [
        [
            'name' => 'Dashboard',
            'icon' => '<svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 13H11V3H3V13ZM3 21H11V15H3V21ZM13 21H21V11H13V21ZM13 3V9H21V3H13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'href' => BASE_URL . '/index_kepsek.php',
            'active' => $current_page_filename === 'index_kepsek.php',
        ],
        [
            'name' => 'Data Guru',
            'icon' => '<svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'href' => BASE_URL . '/pages/guru.php',
            'active' => $current_page_filename === 'guru.php',
        ],
        [
            'name' => 'Data Jabatan',
            'icon' => '<svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 7H4C2.89543 7 2 7.89543 2 9V18C2 19.1046 2.89543 20 4 20H20C21.1046 20 22 19.1046 22 18V9C22 7.89543 21.1046 7 20 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 21V5C16 3.89543 15.1046 3 14 3H10C8.89543 3 8 3.89543 8 5V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'href' => BASE_URL . '/pages/jabatan.php',
            'active' => $current_page_filename === 'jabatan.php',
        ],
        [
            'name' => 'Data Tunjangan',
            'icon' => '<svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L15.09 8.26L22 9L17 14L18.18 21L12 17.77L5.82 21L7 14L2 9L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'href' => BASE_URL . '/pages/tunjangan.php',
            'active' => $current_page_filename === 'tunjangan.php',
        ],
        [
            'name' => 'Data Potongan',
            'icon' => '<svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 5H5C3.89543 5 3 5.89543 3 7V17C3 18.1046 3.89543 19 5 19H19C20.1046 19 21 18.1046 21 17V7C21 5.89543 20.1046 5 19 5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 7L12 13L21 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'href' => BASE_URL . '/pages/potongan.php',
            'active' => $current_page_filename === 'potongan.php',
        ],
        [
            'name' => 'Data Absensi',
            'icon' => '<svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V5C3.89543 5 5 3.89543 5 3H16L21 8V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'href' => BASE_URL . '/pages/absensi.php',
            'active' => $current_page_filename === 'absensi.php',
        ],
        [
            'name' => 'Data Pengguna',
            'icon' => '<svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 21V19C16 17.9391 15.5786 16.9217 14.8284 16.1716C14.0783 15.4214 13.0609 15 12 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="8.5" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 8V14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M23 11H17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'href' => BASE_URL . '/pages/users.php',
            'active' => $current_page_filename === 'users.php',
        ],
        [
            'name' => 'Laporan Gaji',
            'icon' => '<svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 2H6C4.89 2 4 2.89 4 4V20C4 21.11 4.89 22 6 22H18C19.11 22 20 21.11 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 9H9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
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
<!-- Mobile menu button -->
<button id="mobile-menu-toggle" class="fixed top-4 left-4 z-[60] lg:hidden p-2 rounded-lg bg-white shadow-lg border border-gray-200 hover:bg-gray-50 transition-colors">
    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>

<!-- Overlay for mobile -->
<div id="sidebar-overlay" class="fixed inset-0 z-40 bg-black bg-opacity-50 transition-opacity duration-300 ease-in-out lg:hidden opacity-0 pointer-events-none"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 h-screen w-64 transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
    <div class="flex h-full flex-col bg-gradient-to-br from-white via-gray-50 to-green-50 shadow-2xl lg:shadow-xl">
        <!-- Header -->
        <div class="relative flex items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b border-gray-100">
            <!-- Close button for mobile -->
            <button id="sidebar-close" class="lg:hidden absolute top-4 right-4 p-1 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div class="flex items-center space-x-3 pr-8 lg:pr-0">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo" class="h-10 sm:h-12 w-auto transform transition-transform hover:scale-105">
                <div class="min-w-0">
                    <h1 class="font-bold text-lg sm:text-xl text-gray-800 tracking-wide truncate">SDUMK</h1>
                    <div class="mt-1 inline-flex items-center rounded-full bg-gradient-to-r from-green-100 to-emerald-100 px-2 sm:px-3 py-1 text-xs font-medium text-green-800 ring-1 ring-inset ring-green-200/40 shadow-sm">
                        <span class="truncate"><?= ucfirst(str_replace('_', ' ', $user_role)) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-3 sm:px-4 py-4 sm:py-6">
            <ul class="space-y-1 sm:space-y-2">
                <?php foreach ($navigation as $item): ?>
                    <li>
                        <a href="<?= $item['href'] ?>"
                            class="group relative flex items-center gap-2 sm:gap-3 rounded-xl px-3 sm:px-4 py-2.5 sm:py-3 font-medium transition-all duration-200 text-sm sm:text-base
                                  <?= $item['active']
                                        ? 'bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg shadow-green-200'
                                        : 'text-gray-700 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 hover:text-green-700' ?>">
                            <span class="flex-shrink-0"><?= $item['icon'] ?></span>
                            <span class="flex-1 truncate"><?= e($item['name']) ?></span>
                            <?php if ($item['active']): ?>
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 h-2 w-2 rounded-full bg-white shadow-inner flex-shrink-0"></span>
                            <?php endif; ?>

                            <!-- Hover Effect -->
                            <span class="absolute bottom-0 left-0 h-0.5 w-0 bg-green-500 transition-all duration-200 
                                       <?= $item['active'] ? 'w-full' : 'group-hover:w-full' ?>"></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <!-- Footer -->
        <div class="mt-auto border-t border-gray-100 p-3 sm:p-4">
            <div class="flex flex-col items-center space-y-1 sm:space-y-2 text-center">
                <p class="text-xs text-gray-500 leading-tight">
                    &copy; <?= date('Y') ?> SD Unggulan<br class="hidden sm:inline">
                    <span class="sm:hidden"> </span>Muhammadiyah Kretek
                </p>
            </div>
        </div>
    </div>
</aside>

<!-- JavaScript for mobile menu -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const sidebarClose = document.getElementById('sidebar-close');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('opacity-0', 'pointer-events-none');
            document.body.classList.add('overflow-hidden');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('opacity-0', 'pointer-events-none');
            document.body.classList.remove('overflow-hidden');
        }

        // Toggle mobile menu
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', openSidebar);
        }

        // Close sidebar
        if (sidebarClose) {
            sidebarClose.addEventListener('click', closeSidebar);
        }

        // Close sidebar when clicking overlay
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }

        // Close sidebar when pressing escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSidebar();
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) { // lg breakpoint
                closeSidebar();
            }
        });
    });
</script>
<!-- End Sidebar -->