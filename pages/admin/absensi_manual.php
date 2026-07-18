<?php
/**
 * pages/admin/absensi_manual.php
 * Input absensi manual (tanpa scan QR) – untuk Izin / Sakit / Alpha
 * Bisa diakses admin dan guru
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
$pageTitle = 'Absensi Manual';
$db        = getDB();

/* ---- Proses POST simpan absensi manual ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswaId    = (int)$_POST['siswa_id'];
    $tanggal    = clean($_POST['tanggal']);
    $status     = clean($_POST['status']);
    $keterangan = clean($_POST['keterangan'] ?? '');
    $userId     = currentUser()['id'];
    $jamMasuk   = ($status === 'hadir') ? date('H:i:s') : null;

    // Upsert: jika sudah ada absensi hari itu, update; jika belum, insert
    $stmt = $db->prepare("
        INSERT INTO absensi (siswa_id, tanggal, jam_masuk, status, keterangan, dicatat_oleh)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            status        = VALUES(status),
            keterangan    = VALUES(keterangan),
            dicatat_oleh  = VALUES(dicatat_oleh)
    ");
    $stmt->bind_param('issssi', $siswaId, $tanggal, $jamMasuk, $status, $keterangan, $userId);

    if ($stmt->execute()) {
        setFlash('success', 'Absensi manual berhasil disimpan.');
    } else {
        setFlash('error', 'Gagal menyimpan: ' . $db->error);
    }
    redirect(BASE_URL . '/pages/admin/absensi_manual.php');
}

/* ---- Filter kelas ---- */
$filterKelas = clean($_GET['kelas'] ?? '');
$tanggal     = clean($_GET['tanggal'] ?? date('Y-m-d'));
$kelasList   = $db->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas")->fetch_all(MYSQLI_ASSOC);

$whereKelas = $filterKelas ? "AND s.kelas='$filterKelas'" : '';

// Ambil daftar siswa beserta status absensi hari ini
$siswas = $db->query("
    SELECT s.id, s.nis, s.nama, s.kelas, s.foto,
           a.status, a.keterangan, a.jam_masuk
    FROM siswa s
    LEFT JOIN absensi a ON a.siswa_id = s.id AND a.tanggal = '$tanggal'
    WHERE s.aktif = 1 $whereKelas
    ORDER BY s.kelas, s.nama
")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h5 class="fw-bold mb-0">Absensi Manual</h5>
    <a href="<?= BASE_URL ?>/pages/scan.php" class="btn btn-primary">
        <i class="bi bi-camera-video me-1"></i> Scan QR
    </a>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="<?= $tanggal ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Kelas</label>
                <select name="kelas" class="form-select">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelasList as $k): ?>
                    <option value="<?= clean($k['kelas']) ?>" <?= $filterKelas===$k['kelas']?'selected':'' ?>>
                        <?= clean($k['kelas']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<!-- Keterangan tanggal -->
<div class="alert alert-info py-2 mb-3">
    <i class="bi bi-calendar-event me-1"></i>
    Absensi untuk: <strong><?= formatTanggal($tanggal, true) ?></strong>
    <?php if ($filterKelas): ?> | Kelas: <strong><?= $filterKelas ?></strong><?php endif; ?>
    | Total: <strong><?= count($siswas) ?> siswa</strong>
</div>

<!-- Tabel absensi massal -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-list-check text-primary"></i> Daftar Siswa
        <small class="text-muted ms-2">Klik baris untuk ubah status</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Foto</th>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Jam Masuk</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($siswas as $i => $s): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <?php if ($s['foto']): ?>
                        <img src="<?= UPLOAD_URL . clean($s['foto']) ?>"
                             width="36" height="36" class="rounded-circle" style="object-fit:cover">
                        <?php else: ?>
                        <div class="user-avatar" style="width:36px;height:36px;font-size:.8rem">
                            <?= strtoupper(substr($s['nama'],0,1)) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><code><?= clean($s['nis']) ?></code></td>
                    <td class="fw-semibold"><?= clean($s['nama']) ?></td>
                    <td><?= clean($s['kelas']) ?></td>
                    <td><?= $s['jam_masuk'] ? substr($s['jam_masuk'],0,5) : '-' ?></td>
                    <td>
                        <?php if ($s['status']): ?>
                            <?= statusBadge($s['status']) ?>
                        <?php else: ?>
                            <span class="badge bg-secondary">Belum Absen</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Tombol cepat tanpa modal -->
                        <div class="d-flex gap-1 flex-wrap">
                            <?php
                            $statusList = ['hadir'=>'success','izin'=>'warning','sakit'=>'info','alpha'=>'danger'];
                            foreach ($statusList as $st => $color):
                            ?>
                            <form method="POST">
                                <input type="hidden" name="siswa_id" value="<?= $s['id'] ?>">
                                <input type="hidden" name="tanggal"  value="<?= $tanggal ?>">
                                <input type="hidden" name="status"   value="<?= $st ?>">
                                <button type="submit"
                                    class="btn btn-sm btn-<?= $s['status']===$st ? '' : 'outline-' ?><?= $color ?>"
                                    title="<?= ucfirst($st) ?>">
                                    <?= strtoupper(substr($st,0,1)) ?>
                                </button>
                            </form>
                            <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
