<?php
// Jalankan: php tools/hash_password.php admin123
if ($argc < 2) {
    echo "Usage: php hash_password.php <password>\n";
    exit(1);
}
$password = $argv[1];
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Hash untuk password '$password':\n$hash\n"; 