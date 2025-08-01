<?php
// Memuat fungsi inti dan memulai sesi
if (file_exists(__DIR__ . '/functions.php')) {
    require_once __DIR__ . '/functions.php';
}

// Pastikan sesi dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek jika pengguna belum login, arahkan ke halaman login
$current_script = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user_id']) && !in_array($current_script, ['login.php', 'logout.php'])) {
    // Tambahkan pengecualian untuk API
    if (strpos($_SERVER['REQUEST_URI'], '/api/') === false) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

// Ambil detail pengguna dari sesi
$current_user_level = $_SESSION['role'] ?? 'Pengguna';
$current_user_name = $_SESSION['username'] ?? 'Pengguna';

// Ambil email dari database berdasarkan user_id
$current_user_email = 'email@example.com'; // default
if (isset($_SESSION['user_id'])) {
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT g.email FROM Guru g WHERE g.id_user = ?");
    $stmt->bind_param("s", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $current_user_email = $row['email'] ?: 'email@example.com';
    } else {
        // Jika tidak ada di tabel Guru, coba ambil dari tabel User
        $stmt_user = $conn->prepare("SELECT username FROM User WHERE id_user = ?");
        $stmt_user->bind_param("s", $_SESSION['user_id']);
        $stmt_user->execute();
        $user_result = $stmt_user->get_result();
        if ($user_row = $user_result->fetch_assoc()) {
            $current_user_email = $user_row['username'] . '@sdumuhkretek.sch.id';
        }
        $stmt_user->close();
    }
    $stmt->close();
    $conn->close();
}

// Judul halaman default
$page_title = $page_title ?? 'Dashboard';

?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - <?= e(APP_NAME) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-poppins { font-family: 'Poppins', sans-serif; }
        .notif { padding: 12px 15px; border-radius: 8px; font-size: 15px; color: #444; border-left-width: 5px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05 ); }
        .notif-success { background-color: #f0fdf4; border-left-color: #22c55e; color: #15803d; }
        .notif-error { background-color: #fef2f2; border-left-color: #ef4444; color: #b91c1c; }
        [x-cloak] { display: none !important; }

        @media print {
            .no-print { display: none !important; }
            main { padding: 0 !important; }
            body { background-color: white !important; }
        }
    </style>
</head>
<body class="h-full">
    <div x-data="{ sidebarOpen: false }" class="min-h-full">
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Mobile sidebar -->
            <div x-show="sidebarOpen" class="relative z-50 lg:hidden" x-cloak>
                <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/80"></div>
                <div class="fixed inset-0 flex">
                    <div x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative mr-16 flex w-full max-w-xs flex-1">
                        <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                            <button type="button" class="-m-2.5 p-2.5" @click="sidebarOpen = false">
                                <span class="sr-only">Tutup sidebar</span>
                                <i class="fa-solid fa-xmark h-6 w-6 text-white"></i>
                            </button>
                        </div>
                        <?php if (file_exists(__DIR__ . '/sidebar.php')) include __DIR__ . '/sidebar.php'; ?>
                    </div>
                </div>
            </div>

            <!-- Static sidebar for desktop -->
            <div class="hidden lg:fixed lg:inset-y-0 lg:z-40 lg:flex lg:w-64 lg:flex-col">
                 <?php if (file_exists(__DIR__ . '/sidebar.php')) include __DIR__ . '/sidebar.php'; ?>
            </div>

            <div class="lg:pl-64">
                <div class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between px-4 sm:px-6 lg:px-8 no-print">
                    <!-- Hamburger button -->
                    <button type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden" @click="sidebarOpen = true">
                        <span class="sr-only">Buka sidebar</span>
                        <i class="fa-solid fa-bars h-6 w-6"></i>
                    </button>

                    <!-- Spacer to push profile to the right -->
                    <div class="flex-1"></div>

                    <!-- Profile section -->
                    <div class="bg-white rounded-lg shadow-sm p-2 mt-4">
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" class="flex items-center rounded-md p-1" @click="open = !open">
                                <span class="sr-only">Buka menu pengguna</span>
                                <div class="h-9 w-9 rounded-full bg-green-600 flex items-center justify-center text-white font-bold text-sm">
                                    <?= e(strtoupper(substr($current_user_name, 0, 1))) ?>
                                </div>
                                <div class="hidden lg:flex lg:items-center ml-3">
                                    <div class="text-left">
                                        <span class="text-sm font-semibold leading-6 text-gray-900" aria-hidden="true"><?= e($current_user_name) ?></span>
                                        <span class="text-xs text-gray-500 block"><?= e($current_user_level) ?></span>
                                    </div>
                                    <i class="fa-solid fa-chevron-down ml-2 h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
                                </div>
                            </button>
                            
                            <div x-show="open"
                                 @click.away="open = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 z-10 mt-2 w-64 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                 x-cloak>
                                 <div class="px-4 py-3 border-b border-gray-200">
                                     <p class="text-sm font-semibold text-gray-900"><?= e($current_user_name) ?></p>
                                     <p class="truncate text-sm text-gray-500"><?= e($current_user_email) ?></p>
                                 </div>
                                 <div class="py-1">
                                    <a href="<?= BASE_URL ?>/auth/logout.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700">
                                        <i class="fa-solid fa-right-from-bracket w-5 h-5 mr-2 text-gray-400"></i>
                                        Logout
                                    </a>
                                 </div>
                            </div>
                        </div>
                    </div>
                </div>

                <main class="py-8">
                    <div class="px-4 sm:px-6 lg:px-8">
        <?php else: ?>
             <main>
        <?php endif; ?>
