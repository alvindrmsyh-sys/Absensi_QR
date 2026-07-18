<?php
/**
 * api/absensi.php
 * API endpoint – menerima POST dari scanner QR, menyimpan absensi
 * Response: JSON
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Hanya izinkan user yang sudah login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Tidak terautentikasi.']);
    exit;
}

// Hanya metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit;
}

$nis    = clean($_POST['nis']    ?? '');
$status = clean($_POST['status'] ?? 'hadir');
$today  = date('Y-m-d');
$now    = date('H:i:s');

// Validasi status
$validStatus = ['hadir', 'izin', 'sakit', 'alpha'];
if (!in_array($status, $validStatus)) $status = 'hadir';

// Validasi NIS tidak kosong
if (empty($nis)) {
    echo json_encode(['success' => false, 'message' => 'NIS tidak valid.']);
    exit;
}

$db = getDB();

// Cari siswa berdasarkan NIS
$stmt = $db->prepare("SELECT * FROM siswa WHERE nis = ? AND aktif = 1 LIMIT 1");
$stmt->bind_param('s', $nis);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$siswa) {
    echo json_encode(['success' => false, 'message' => "Siswa dengan NIS '$nis' tidak ditemukan."]);
    exit;
}

// Cek apakah sudah absen hari ini
$cekStmt = $db->prepare("SELECT id, status, jam_masuk FROM absensi WHERE siswa_id = ? AND tanggal = ? LIMIT 1");
$cekStmt->bind_param('is', $siswa['id'], $today);
$cekStmt->execute();
$existing = $cekStmt->get_result()->fetch_assoc();
$cekStmt->close();

$userId = currentUser()['id'];

if ($existing) {
    // Jika sudah ada, update status (untuk ubah status manual)
    $upStmt = $db->prepare("UPDATE absensi SET status=?, dicatat_oleh=? WHERE id=?");
    $upStmt->bind_param('sii', $status, $userId, $existing['id']);
    $upStmt->execute();
    $upStmt->close();

    echo json_encode([
        'success'    => true,
        'updated'    => true,
        'siswa_id'   => $siswa['id'],
        'nis'        => $siswa['nis'],
        'nama'       => $siswa['nama'],
        'kelas'      => $siswa['kelas'],
        'jurusan'    => $siswa['jurusan'],
        'foto'       => $siswa['foto'],
        'status'     => $status,
        'jam_masuk'  => $existing['jam_masuk'],
        'tanggal'    => $today,
        'message'    => 'Status absensi diperbarui.',
        'can_update' => true,
    ]);
    exit;
}

// Insert absensi baru
$insStmt = $db->prepare(
    "INSERT INTO absensi (siswa_id, tanggal, jam_masuk, status, dicatat_oleh) VALUES (?,?,?,?,?)"
);
$insStmt->bind_param('isssi', $siswa['id'], $today, $now, $status, $userId);

if ($insStmt->execute()) {
    echo json_encode([
        'success'    => true,
        'updated'    => false,
        'siswa_id'   => $siswa['id'],
        'nis'        => $siswa['nis'],
        'nama'       => $siswa['nama'],
        'kelas'      => $siswa['kelas'],
        'jurusan'    => $siswa['jurusan'],
        'foto'       => $siswa['foto'],
        'status'     => $status,
        'jam_masuk'  => $now,
        'tanggal'    => $today,
        'message'    => 'Absensi berhasil disimpan.',
        'can_update' => true,
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyimpan ke database: ' . $db->error,
    ]);
}
$insStmt->close();
