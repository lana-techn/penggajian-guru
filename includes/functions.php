<?php
// Memulai sesi jika belum ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memuat file konfigurasi
require_once dirname(__DIR__) . '/config/koneksi.php';

/**
 * Helper function untuk generate URL yang aman
 * @param string $path Path relatif dari root aplikasi
 * @return string URL yang aman
 */
function url($path = '')
{
    $base_url = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    return $base_url . '/' . ltrim($path, '/');
}

/**
 * Helper function untuk redirect dengan validasi role
 * @param string $path Path tujuan
 * @param string $required_role Role yang dibutuhkan
 * @return void
 */
function redirect_with_role($path, $required_role = null)
{
    if ($required_role && (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role)) {
        $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman tersebut.';
        header('Location: ' . url('auth/login.php'));
        exit;
    }
    header('Location: ' . url($path));
    exit;
}

/**
 * ----------------------------------------------------------------
 * KONEKSI DATABASE (PDO - BARU)
 * ----------------------------------------------------------------
 */
class Database
{
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    public function getConnection()
    {
        $this->conn = null;
        try {
            // Menggunakan koneksi TCP/IP standar.
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name;

            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Kesalahan Koneksi: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

/**
 * ----------------------------------------------------------------
 * KONEKSI DATABASE (MySQLi - LEGACY)
 * ----------------------------------------------------------------
 * Dipertahankan untuk kompatibilitas dengan kode yang ada.
 * @return mysqli
 */
function db_connect()
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Koneksi database gagal: " . $conn->connect_error);
    }
    return $conn;
}

/**
 * ----------------------------------------------------------------
 * FUNGSI AUTENTIKASI & SESI
 * ----------------------------------------------------------------
 */

/**
 * Memeriksa apakah pengguna sudah login.
 * @return bool
 */
function isLoggedIn()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Mengharuskan pengguna untuk login.
 */
function requireLogin($requiredRole = null)
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }

    // Jika role tertentu dibutuhkan, cek role pengguna
    if ($requiredRole !== null) {
        $userRole = strtolower($_SESSION['role'] ?? '');
        $requiredRole = strtolower($requiredRole);

        if ($userRole !== $requiredRole) {
            // Redirect ke dashboard yang sesuai dengan role pengguna
            redirectToDashboard($userRole);
            exit();
        }
    }
}

/**
 * Redirect pengguna ke dashboard yang sesuai dengan role mereka
 */
function redirectToDashboard($userRole = null)
{
    if ($userRole === null) {
        $userRole = strtolower($_SESSION['role'] ?? '');
    }

    switch ($userRole) {
        case 'admin':
            header('Location: ' . BASE_URL . '/index.php');
            break;
        case 'kepala_sekolah':
            header('Location: ' . BASE_URL . '/index_kepsek.php');
            break;
        case 'guru':
            header('Location: ' . BASE_URL . '/index_guru.php');
            break;
        default:
            header('Location: ' . BASE_URL . '/auth/logout.php');
            break;
    }
}

/**
 * Mengambil data pengguna yang sedang login.
 * @return array|null
 */
function getCurrentUser()
{
    if (!isLoggedIn()) {
        return null;
    }

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT Id_Pengguna AS id_pengguna, Email AS email, Level AS jabatan FROM PENGGUNA WHERE Id_Pengguna = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Mengeluarkan pengguna (logout).
 */
function logout()
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
    header('Location: login.php');
    exit();
}

/**
 * Membatasi akses halaman hanya untuk role tertentu.
 * @param string|array $role Role yang diizinkan ('admin', 'kepala_sekolah', 'guru') atau array dari role
 */
function requireRole($role)
{
    if (!isLoggedIn() || !isset($_SESSION['role'])) {
        set_flash_message('error', 'Anda tidak memiliki hak akses ke halaman ini.');
        header('Location: ../index.php');
        exit();
    }
    
    $userRole = strtolower($_SESSION['role']);
    
    // Handle both string and array inputs
    if (is_array($role)) {
        $allowedRoles = array_map('strtolower', $role);
        if (!in_array($userRole, $allowedRoles)) {
            set_flash_message('error', 'Anda tidak memiliki hak akses ke halaman ini.');
            header('Location: ../index.php');
            exit();
        }
    } else {
        $requiredRole = strtolower($role);
        if ($userRole !== $requiredRole) {
            set_flash_message('error', 'Anda tidak memiliki hak akses ke halaman ini.');
            header('Location: ../index.php');
            exit();
        }
    }
}

/**
 * Membatasi akses halaman untuk beberapa role sekaligus.
 * @param array $roles Array role yang diizinkan
 */
function requireAnyRole($roles)
{
    if (!isLoggedIn() || !isset($_SESSION['role']) || !in_array($_SESSION['role'], array_map('strtolower', $roles))) {
        set_flash_message('error', 'Anda tidak memiliki hak akses ke halaman ini.');
        header('Location: ../index.php');
        exit();
    }
}


/**
 * ----------------------------------------------------------------
 * FUNGSI BANTU (Pesan Flash, CSRF, Pagination, dll.)
 * ----------------------------------------------------------------
 */

/**
 * Mengatur pesan flash.
 * @param string $type Jenis pesan ('success', 'error', 'info').
 * @param string $message Konten pesan.
 */
function set_flash_message($type, $message)
{
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

/**
 * Menampilkan pesan flash.
 */
function display_flash_message()
{
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_message']['type'];
        $message = htmlspecialchars($_SESSION['flash_message']['message'], ENT_QUOTES, 'UTF-8');

        $icon = '';
        if ($type === 'success') $icon = '<i class="fa-solid fa-check-circle mr-3"></i>';
        if ($type === 'error') $icon = '<i class="fa-solid fa-times-circle mr-3"></i>';

        $colors = [
            'success' => 'bg-green-50 border-green-400 text-green-800',
            'error'   => 'bg-red-50 border-red-400 text-red-800',
            'info'    => 'bg-blue-50 border-blue-400 text-blue-800'
        ];
        $alertClass = $colors[$type] ?? $colors['info'];

        echo "<div class='notif flex items-center p-4 mb-4 text-sm rounded-lg {$alertClass}'>{$icon}<span>{$message}</span></div>";
        unset($_SESSION['flash_message']);
    }
}

/**
 * Membersihkan string untuk output HTML yang aman.
 * @param string|null $string String yang akan dibersihkan.
 * @return string
 */
function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Menghasilkan token CSRF.
 */
function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Menghasilkan input field tersembunyi dengan token CSRF.
 */
function csrf_input()
{
    generate_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

/**
 * Memvalidasi token CSRF.
 * @return bool
 */
function validate_csrf_token()
{
    if (isset($_POST['csrf_token'])) {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            set_flash_message('error', 'Sesi tidak valid atau telah kedaluwarsa. Silakan coba lagi.');
            unset($_SESSION['csrf_token']);
            return false;
        }
        unset($_SESSION['csrf_token']);
        return true;
    }
    return true;
}


/**
 * Menghasilkan HTML untuk link pagination.
 * @param int $current_page Halaman aktif.
 * @param int $total_pages Jumlah total halaman.
 * @param string $base_url URL dasar.
 * @param array $params Parameter query tambahan.
 * @return string
 */
function generate_pagination_links($current_page, $total_pages, $base_url, $params = [])
{
    if ($total_pages <= 1) {
        return '';
    }

    $query_string = http_build_query($params);
    $base_url .= '?' . $query_string;

    $html = '<nav class="flex items-center justify-between mt-6">';
    $html .= '<span class="text-sm text-gray-500">Halaman <strong>' . $current_page . '</strong> dari <strong>' . $total_pages . '</strong></span>';
    $html .= '<ul class="inline-flex items-center -space-x-px">';

    // Tombol Previous
    $prev_class = ($current_page > 1)
        ? 'text-gray-500 bg-white hover:bg-gray-100 hover:text-gray-700'
        : 'text-gray-400 bg-gray-50 cursor-not-allowed';
    $prev_href = ($current_page > 1) ? 'href="' . $base_url . '&page=' . ($current_page - 1) . '"' : '';
    $html .= '<li><a ' . $prev_href . ' class="px-3 py-2 ml-0 leading-tight border border-gray-300 rounded-l-lg ' . $prev_class . '"><i class="fa-solid fa-chevron-left text-xs"></i></a></li>';

    // Nomor halaman
    $window = 2;
    if ($total_pages <= (2 * $window) + 3) {
        for ($i = 1; $i <= $total_pages; $i++) {
            $html .= render_page_number($i, $current_page, $base_url);
        }
    } else {
        $html .= render_page_number(1, $current_page, $base_url);

        if ($current_page > $window + 2) {
            $html .= '<li><span class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
        }

        for ($i = max(2, $current_page - $window); $i <= min($total_pages - 1, $current_page + $window); $i++) {
            $html .= render_page_number($i, $current_page, $base_url);
        }

        if ($current_page < $total_pages - $window - 1) {
            $html .= '<li><span class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
        }

        $html .= render_page_number($total_pages, $current_page, $base_url);
    }

    // Tombol Next
    $next_class = ($current_page < $total_pages)
        ? 'text-gray-500 bg-white hover:bg-gray-100 hover:text-gray-700'
        : 'text-gray-400 bg-gray-50 cursor-not-allowed';
    $next_href = ($current_page < $total_pages) ? 'href="' . $base_url . '&page=' . ($current_page + 1) . '"' : '';
    $html .= '<li><a ' . $next_href . ' class="px-3 py-2 leading-tight border border-gray-300 rounded-r-lg ' . $next_class . '"><i class="fa-solid fa-chevron-right text-xs"></i></a></li>';

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Merender satu tombol nomor halaman.
 * @param int $page_num Nomor halaman.
 * @param int $current_page Halaman aktif.
 * @param string $base_url URL dasar.
 * @return string
 */
function render_page_number($page_num, $current_page, $base_url)
{
    if ($page_num == $current_page) {
        return '<li><span aria-current="page" class="z-10 px-3 py-2 leading-tight text-white bg-green-600 border border-green-600">' . $page_num . '</span></li>';
    } else {
        return '<li><a href="' . $base_url . '&page=' . $page_num . '" class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">' . $page_num . '</a></li>';
    }
}

/**
 * ----------------------------------------------------------------
 * FUNGSI PERHITUNGAN GAJI
 * ----------------------------------------------------------------
 */

/**
 * Menghitung potongan berdasarkan persentase dari gaji pokok.
 * @param float $gaji_pokok Gaji pokok
 * @param float $persentase_bpjs Persentase BPJS (default: 2)
 * @param float $persentase_infak Persentase Infak (default: 2)
 * @return array Array berisi potongan_bpjs dan infak
 */
function calculate_potongan($gaji_pokok, $persentase_bpjs = 2, $persentase_infak = 2)
{
    return [
        'potongan_bpjs' => $gaji_pokok * ($persentase_bpjs / 100),
        'infak' => $gaji_pokok * ($persentase_infak / 100)
    ];
}

/**
 * Menghitung tunjangan kehadiran berdasarkan jumlah keterlambatan.
 * @param int $jml_terlambat Jumlah keterlambatan
 * @return float Tunjangan kehadiran
 */
function calculate_tunjangan_kehadiran($jml_terlambat)
{
    if ($jml_terlambat > 5) {
        return 0;
    }
    return 100000 - ($jml_terlambat * 5000);
}

/**
 * Menghitung tunjangan anak berdasarkan jumlah anak.
 * @param int $jml_anak Jumlah anak
 * @param int $max_anak Maksimal anak yang mendapat tunjangan (default: 2)
 * @param float $tunjangan_per_anak Tunjangan per anak (default: 100000)
 * @return float Tunjangan anak
 */
function calculate_tunjangan_anak($jml_anak, $max_anak = 2, $tunjangan_per_anak = 100000)
{
    $jml_anak_tunjangan = min((int)$jml_anak, $max_anak);
    return $jml_anak_tunjangan * $tunjangan_per_anak;
}
