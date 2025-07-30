<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('kepala_sekolah');

$conn = db_connect();
$page_title = 'Ubah Password';

// Get current user info
$current_user_id = $_SESSION['user_id'] ?? null;
$current_user = null;

if ($current_user_id) {
    $stmt = $conn->prepare("SELECT id_user, username, akses FROM User WHERE id_user = ?");
    $stmt->bind_param('s', $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_user = $result->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) die('Validasi CSRF gagal.');
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        set_flash_message('error', 'Semua field wajib diisi.');
    } elseif ($new_password !== $confirm_password) {
        set_flash_message('error', 'Konfirmasi password baru tidak cocok.');
    } elseif (strlen($new_password) < 6) {
        set_flash_message('error', 'Password baru minimal 6 karakter.');
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM User WHERE id_user = ?");
        $stmt->bind_param('s', $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();

        if (!$user_data) {
            set_flash_message('error', 'Data pengguna tidak ditemukan.');
        } elseif (!password_verify($current_password, $user_data['password'])) {
            set_flash_message('error', 'Password saat ini salah.');
        } else {
            // Update password
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE User SET password = ? WHERE id_user = ?");
            $stmt->bind_param('ss', $new_password_hash, $current_user_id);
            
            if ($stmt->execute()) {
                set_flash_message('success', 'Password berhasil diubah.');
                // Clear form data
                $_POST = array();
            } else {
                set_flash_message('error', 'Gagal mengubah password.');
            }
            $stmt->close();
        }
    }
}

generate_csrf_token();
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="max-w-lg mx-auto mt-10 bg-white p-8 rounded-xl shadow-lg">
    <h2 class="text-2xl font-bold text-gray-800 text-center mb-2 font-poppins">Ubah Password</h2>
    <p class="text-center text-gray-500 mb-8">Ubah password akun kepala sekolah Anda.</p>
    
    <?php if ($current_user): ?>
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <i class="fas fa-user-circle text-blue-600 text-xl mr-3"></i>
            <div>
                <p class="font-semibold text-blue-800"><?= e($current_user['username']) ?></p>
                <p class="text-sm text-blue-600"><?= e($current_user['akses']) ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php display_flash_message(); ?>
    
    <form method="POST" action="">
        <?php csrf_input(); ?>
        
        <div class="mb-5">
            <label for="current_password" class="block mb-2 text-sm font-medium text-gray-700">
                <i class="fas fa-lock mr-2 text-gray-500"></i>Password Saat Ini
            </label>
            <input type="password" 
                   id="current_password" 
                   name="current_password" 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                   required>
        </div>
        
        <div class="mb-5">
            <label for="new_password" class="block mb-2 text-sm font-medium text-gray-700">
                <i class="fas fa-key mr-2 text-gray-500"></i>Password Baru
            </label>
            <input type="password" 
                   id="new_password" 
                   name="new_password" 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                   required>
            <p class="text-xs text-gray-500 mt-1">Minimal 6 karakter</p>
        </div>
        
        <div class="mb-8">
            <label for="confirm_password" class="block mb-2 text-sm font-medium text-gray-700">
                <i class="fas fa-check-circle mr-2 text-gray-500"></i>Konfirmasi Password Baru
            </label>
            <input type="password" 
                   id="confirm_password" 
                   name="confirm_password" 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                   required>
        </div>
        
        <div class="flex items-center justify-end space-x-4">
            <a href="../../index_kepsek.php" 
               class="px-6 py-2.5 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 font-semibold text-sm transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
            <button type="submit" 
                    class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 font-semibold text-sm shadow-md hover:shadow-lg transition-all">
                <i class="fas fa-save mr-2"></i>Ubah Password
            </button>
        </div>
    </form>
    
    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
            <div>
                <h4 class="font-semibold text-yellow-800 mb-1">Tips Keamanan</h4>
                <ul class="text-sm text-yellow-700 space-y-1">
                    <li>• Gunakan kombinasi huruf besar, huruf kecil, angka, dan simbol</li>
                    <li>• Jangan gunakan password yang mudah ditebak</li>
                    <li>• Jangan bagikan password dengan orang lain</li>
                    <li>• Ganti password secara berkala</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
        if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Password tidak cocok');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('input', validatePassword);
    confirmPassword.addEventListener('input', validatePassword);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 