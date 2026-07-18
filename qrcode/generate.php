<?php
/**
 * qrcode/generate.php
 * Script untuk generate QR Code via URL
 * Contoh: qrcode/generate.php?nis=2024001
 *
 * Juga bisa dipanggil via CLI untuk generate massal:
 * php generate.php --all
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$libPath = __DIR__ . '/lib/qrlib.php';

/* ---- Generate via URL (authenticated) ---- */
if (php_sapi_name() !== 'cli') {
    requireAdmin();

    $nis = clean($_GET['nis'] ?? '');
    if (empty($nis)) {
        die('NIS diperlukan. Contoh: generate.php?nis=2024001');
    }

    if (!file_exists($libPath)) {
        die('<b>Error:</b> Library phpqrcode tidak ditemukan di <code>' . $libPath . '</code><br>'
          . 'Download dari: <a href="https://sourceforge.net/projects/phpqrcode/">phpqrcode</a> '
          . 'dan ekstrak ke folder <code>qrcode/lib/</code>');
    }

    require_once $libPath;

    $genDir = __DIR__ . '/generated/';
    if (!is_dir($genDir)) mkdir($genDir, 0755, true);

    $outFile = $genDir . 'qr_' . $nis . '.png';
    QRcode::png($nis, $outFile, QR_ECLEVEL_M, 8, 2);

    // Update database
    $db = getDB();
    $f  = 'qr_' . $nis . '.png';
    $stmt = $db->prepare("UPDATE siswa SET qr_code=? WHERE nis=?");
    $stmt->bind_param('ss', $f, $nis);
    $stmt->execute();

    header('Content-Type: image/png');
    readfile($outFile);
    exit;
}

/* ---- Mode CLI: generate semua siswa ---- */
if (isset($argv[1]) && $argv[1] === '--all') {
    require_once $libPath;

    $db      = getDB();
    $siswas  = $db->query("SELECT id, nis FROM siswa WHERE aktif=1")->fetch_all(MYSQLI_ASSOC);
    $genDir  = __DIR__ . '/generated/';
    if (!is_dir($genDir)) mkdir($genDir, 0755, true);

    foreach ($siswas as $s) {
        $file = 'qr_' . $s['nis'] . '.png';
        QRcode::png($s['nis'], $genDir . $file, QR_ECLEVEL_M, 8, 2);
        $stmt = $db->prepare("UPDATE siswa SET qr_code=? WHERE id=?");
        $stmt->bind_param('si', $file, $s['id']);
        $stmt->execute();
        echo "Generated: $file\n";
    }
    echo "Selesai! Total: " . count($siswas) . " QR Code\n";
}
