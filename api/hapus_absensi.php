<?php
/**
 * api/hapus_absensi.php
 * Hapus record absensi – hanya admin
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)clean($_POST['id'] ?? 0);
    if ($id > 0) {
        getDB()->query("DELETE FROM absensi WHERE id=$id");
        setFlash('success', 'Data absensi berhasil dihapus.');
    }
}
redirect(BASE_URL . '/pages/rekap.php');
