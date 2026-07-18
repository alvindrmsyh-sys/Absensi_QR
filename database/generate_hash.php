<?php
/**
 * database/generate_hash.php
 * Utility: Generate bcrypt hash untuk password baru
 * Jalankan sekali dari browser: http://localhost/absensi-qr/database/generate_hash.php
 * HAPUS file ini setelah digunakan!
 */

// Daftar password yang ingin di-hash
$passwords = [
    'admin123',
    'guru123',
    'password',
];

echo '<pre style="font-family:monospace;padding:20px;background:#1e293b;color:#e2e8f0;border-radius:12px">';
echo "<b style='color:#60a5fa'>// Password Hash Generator – AbsensiQR</b>\n\n";

foreach ($passwords as $pass) {
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    echo "Password : <b style='color:#fbbf24'>$pass</b>\n";
    echo "Hash     : <b style='color:#34d399'>$hash</b>\n\n";
}

echo "// Cara update password di database:\n";
echo "// UPDATE users SET password='[HASH_DI_ATAS]' WHERE username='admin';\n";
echo '</pre>';

echo '<p style="color:red;font-weight:bold">⚠️ Hapus file ini setelah digunakan!</p>';
