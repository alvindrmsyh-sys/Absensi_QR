<?php
/**
 * pages/laporan.php
 * Halaman laporan – cetak PDF & export Excel
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$pageTitle = 'Laporan Absensi';
$db        = getDB();

$tglDari   = clean($_GET['dari']   ?? date('Y-m-01'));
$tglSampai = clean($_GET['sampai'] ?? date('Y-m-d'));
$kelas     = clean($_GET['kelas']  ?? '');
$mode      = clean($_GET['mode']   ?? 'preview');  // preview | cetak

$kelasList = $db->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas")->fetch_all(MYSQLI_ASSOC);

$where  = "WHERE a.tanggal BETWEEN '$tglDari' AND '$tglSampai'";
if ($kelas) $where .= " AND s.kelas='$kelas'";

$data = $db->query("
    SELECT a.tanggal, a.jam_masuk, a.status, a.keterangan,
           s.nis, s.nama, s.kelas, s.jurusan
    FROM absensi a
    JOIN siswa s ON s.id = a.siswa_id
    $where
    ORDER BY s.kelas, s.nama, a.tanggal
")->fetch_all(MYSQLI_ASSOC);

// Ringkasan per siswa
$summaryData = [];
foreach ($data as $row) {
    $key = $row['nis'];
    if (!isset($summaryData[$key])) {
        $summaryData[$key] = [
            'nis'     => $row['nis'],
            'nama'    => $row['nama'],
            'kelas'   => $row['kelas'],
            'jurusan' => $row['jurusan'],
            'hadir'   => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0,
        ];
    }
    $summaryData[$key][$row['status']]++;
}

if ($mode === 'cetak') {
    // Mode cetak: tampilkan halaman print-friendly
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .kop { text-align: center; margin-bottom: 20px; }
        .kop h4 { font-size: 16px; font-weight: bold; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 5px 8px; }
        th { background: #1a56db; color: #fff; }
        tr:nth-child(even) { background: #f8f9fa; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="no-print mb-3 d-flex gap-2">
    <button onclick="window.print()" class="btn btn-primary btn-sm">
        <i class="bi bi-printer"></i> Cetak
    </button>
    <a href="javascript:history.back()" class="btn btn-secondary btn-sm">Kembali</a>
</div>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<div class="kop">
    <h4>LAPORAN ABSENSI SISWA</h4>
    <p>Periode: <?= formatTanggal($tglDari) ?> s/d <?= formatTanggal($tglSampai) ?>
       <?= $kelas ? " | Kelas: $kelas" : '' ?></p>
    <p>Dicetak: <?= formatTanggal(date('Y-m-d'), true) ?></p>
</div>

<h6 class="mt-3">Rekap Per Siswa</h6>
<table>
    <thead>
        <tr>
            <th>#</th><th>NIS</th><th>Nama</th><th>Kelas</th>
            <th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpha</th><th>Total</th>
        </tr>
    </thead>
    <tbody>
    <?php $no=1; foreach ($summaryData as $s): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= $s['nis'] ?></td>
            <td><?= $s['nama'] ?></td>
            <td><?= $s['kelas'] ?></td>
            <td align="center"><?= $s['hadir'] ?></td>
            <td align="center"><?= $s['izin'] ?></td>
            <td align="center"><?= $s['sakit'] ?></td>
            <td align="center"><?= $s['alpha'] ?></td>
            <td align="center"><?= $s['hadir']+$s['izin']+$s['sakit']+$s['alpha'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h6 class="mt-4">Detail Absensi</h6>
<table>
    <thead>
        <tr><th>#</th><th>Tanggal</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Jam Masuk</th><th>Status</th></tr>
    </thead>
    <tbody>
    <?php foreach ($data as $i => $row): ?>
        <tr>
            <td><?= $i+1 ?></td>
            <td><?= formatTanggal($row['tanggal']) ?></td>
            <td><?= $row['nis'] ?></td>
            <td><?= $row['nama'] ?></td>
            <td><?= $row['kelas'] ?></td>
            <td><?= $row['jam_masuk'] ? substr($row['jam_masuk'],0,5) : '-' ?></td>
            <td><?= strtoupper($row['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
<?php
    exit;
}

// Mode preview (default)
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Form filter -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-file-earmark-bar-graph text-primary"></i> Filter Laporan</div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="dari" class="form-control" value="<?= $tglDari ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="sampai" class="form-control" value="<?= $tglSampai ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Kelas</label>
                <select name="kelas" class="form-select">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelasList as $k): ?>
                    <option value="<?= clean($k['kelas']) ?>" <?= $kelas===$k['kelas']?'selected':'' ?>>
                        <?= clean($k['kelas']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Tampilkan
                </button>
                <a href="?dari=<?= $tglDari ?>&sampai=<?= $tglSampai ?>&kelas=<?= $kelas ?>&mode=cetak"
                   target="_blank" class="btn btn-danger me-2">
                    <i class="bi bi-printer me-1"></i>Cetak PDF
                </a>
                <a href="<?= BASE_URL ?>/api/export_excel.php?dari=<?= $tglDari ?>&sampai=<?= $tglSampai ?>&kelas=<?= $kelas ?>"
                   class="btn btn-success">
                    <i class="bi bi-file-excel me-1"></i>Export Excel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Rekap per siswa -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-person-lines-fill text-primary"></i> Rekap Per Siswa
        <small class="text-muted ms-2"><?= formatTanggal($tglDari) ?> – <?= formatTanggal($tglSampai) ?></small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th><th>NIS</th><th>Nama</th><th>Kelas</th>
                        <th class="text-success">Hadir</th>
                        <th class="text-warning">Izin</th>
                        <th class="text-info">Sakit</th>
                        <th class="text-danger">Alpha</th>
                        <th>%Hadir</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; foreach ($summaryData as $s):
                    $total = $s['hadir']+$s['izin']+$s['sakit']+$s['alpha'];
                    $pct   = $total > 0 ? round($s['hadir']/$total*100) : 0;
                    $barColor = $pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger');
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><code><?= $s['nis'] ?></code></td>
                        <td class="fw-semibold"><?= clean($s['nama']) ?></td>
                        <td><?= $s['kelas'] ?></td>
                        <td class="text-success fw-bold"><?= $s['hadir'] ?></td>
                        <td class="text-warning fw-bold"><?= $s['izin'] ?></td>
                        <td class="text-info fw-bold"><?= $s['sakit'] ?></td>
                        <td class="text-danger fw-bold"><?= $s['alpha'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:8px">
                                    <div class="progress-bar bg-<?= $barColor ?>" style="width:<?= $pct ?>%"></div>
                                </div>
                                <small><?= $pct ?>%</small>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
