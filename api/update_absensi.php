<?php
/**
 * api/update_absensi.php
 * Update status absensi dari form rekap
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int)clean($_POST['id']     ?? 0);
    $status   = clean($_POST['status']      ?? 'hadir');
    $redirect = clean($_POST['redirect']    ?? BASE_URL . '/pages/rekap.php');

    $valid = ['hadir','izin','sakit','alpha'];
    if (!in_array($status, $valid)) $status = 'hadir';

    $db     = getDB();
    $userId = currentUser()['id'];

    $stmt = $db->prepare("UPDATE absensi SET status=?, dicatat_oleh=? WHERE id=?");
    $stmt->bind_param('sii', $status, $userId, $id);
    $stmt->execute();

    setFlash('success', 'Status absensi berhasil diperbarui.');
    redirect($redirect);
}

redirect(BASE_URL . '/pages/rekap.php');
