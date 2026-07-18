<?php
/**
 * api/export_excel.php
 * Export rekap absensi ke file Excel (.csv yang bisa dibuka Excel)
 * Tanpa library eksternal – menggunakan output CSV sederhana
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db        = getDB();
$tglDari   = clean($_GET['dari']   ?? date('Y-m-01'));
$tglSampai = clean($_GET['sampai'] ?? date('Y-m-d'));
$kelas     = clean($_GET['kelas']  ?? '');
$status    = clean($_GET['status'] ?? '');

$where = "WHERE a.tanggal BETWEEN '$tglDari' AND '$tglSampai'";
if ($kelas)  $where .= " AND s.kelas='$kelas'";
if ($status) $where .= " AND a.status='$status'";

$rows = $db->query("
    SELECT a.tanggal, a.jam_masuk, a.status, a.keterangan,
           s.nis, s.nama, s.kelas, s.jurusan, s.jenis_kelamin
    FROM absensi a
    JOIN siswa s ON s.id = a.siswa_id
    $where
    ORDER BY s.kelas, s.nama, a.tanggal
")->fetch_all(MYSQLI_ASSOC);

// Header HTTP untuk download file Excel (CSV)
$filename = 'laporan_absensi_' . $tglDari . '_sd_' . $tglSampai . '.csv';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// BOM untuk Excel agar UTF-8 terbaca dengan benar
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Header kolom
fputcsv($output, [
    'No', 'Tanggal', 'NIS', 'Nama Siswa', 'Kelas', 'Jurusan',
    'Jenis Kelamin', 'Jam Masuk', 'Status', 'Keterangan'
], ';');   // Gunakan titik koma agar Excel Indonesia membaca dengan benar

// Data baris
foreach ($rows as $i => $row) {
    fputcsv($output, [
        $i + 1,
        $row['tanggal'],
        $row['nis'],
        $row['nama'],
        $row['kelas'],
        $row['jurusan'],
        $row['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan',
        $row['jam_masuk'] ? substr($row['jam_masuk'], 0, 5) : '-',
        ucfirst($row['status']),
        $row['keterangan'] ?? '',
    ], ';');
}

fclose($output);
exit;
