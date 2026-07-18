<?php
/**
 * pages/rekap.php
 * Rekap absensi – filter tanggal, kelas, status
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$pageTitle = 'Rekap Absensi';
$db        = getDB();

// ---- Filter ----
$tglDari  = clean($_GET['dari']   ?? date('Y-m-01'));   // default: awal bulan
$tglSampai = clean($_GET['sampai'] ?? date('Y-m-d'));    // default: hari ini
$filterKelas  = clean($_GET['kelas']  ?? '');
$filterStatus = clean($_GET['status'] ?? '');

// Daftar kelas untuk dropdown
$kelasList = $db->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas")->fetch_all(MYSQLI_ASSOC);

// Bangun WHERE clause
$where = "WHERE a.tanggal BETWEEN '$tglDari' AND '$tglSampai'";
if ($filterKelas)  $where .= " AND s.kelas = '$filterKelas'";
if ($filterStatus) $where .= " AND a.status = '$filterStatus'";

// Paginasi
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;
$total   = (int)$db->query("
    SELECT COUNT(*) c FROM absensi a JOIN siswa s ON s.id=a.siswa_id $where
")->fetch_assoc()['c'];
$pages   = max(1, (int)ceil($total / $perPage));

$rekaps  = $db->query("
    SELECT a.*, s.nis, s.nama, s.kelas, s.jurusan, s.foto,
           u.nama AS pencatat
    FROM absensi a
    JOIN siswa s ON s.id = a.siswa_id
    LEFT JOIN users u ON u.id = a.dicatat_oleh
    $where
    ORDER BY a.tanggal DESC, a.jam_masuk DESC
    LIMIT $perPage OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-funnel text-primary"></i> Filter Data</div>
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
                    <option value="<?= clean($k['kelas']) ?>" <?= $filterKelas === $k['kelas'] ? 'selected' : '' ?>>
                        <?= clean($k['kelas']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="hadir"  <?= $filterStatus==='hadir'  ? 'selected':'' ?>>Hadir</option>
                    <option value="izin"   <?= $filterStatus==='izin'   ? 'selected':'' ?>>Izin</option>
                    <option value="sakit"  <?= $filterStatus==='sakit'  ? 'selected':'' ?>>Sakit</option>
                    <option value="alpha"  <?= $filterStatus==='alpha'  ? 'selected':'' ?>>Alpha</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Ringkasan periode -->
<?php
$summary = $db->query("
    SELECT
        COUNT(*)                AS total,
        SUM(a.status='hadir')  AS hadir,
        SUM(a.status='izin')   AS izin,
        SUM(a.status='sakit')  AS sakit,
        SUM(a.status='alpha')  AS alpha
    FROM absensi a JOIN siswa s ON s.id=a.siswa_id $where
")->fetch_assoc();
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card total">
            <div class="stat-icon bg-primary-soft"><i class="bi bi-collection"></i></div>
            <div><div class="stat-number"><?= $summary['total'] ?></div><div class="stat-label">Total</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card hadir">
            <div class="stat-icon bg-success-soft"><i class="bi bi-check-circle"></i></div>
            <div><div class="stat-number"><?= $summary['hadir'] ?></div><div class="stat-label">Hadir</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card izin">
            <div class="stat-icon bg-warning-soft"><i class="bi bi-clipboard"></i></div>
            <div><div class="stat-number"><?= $summary['izin'] + $summary['sakit'] ?></div><div class="stat-label">Izin/Sakit</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card alpha">
            <div class="stat-icon bg-danger-soft"><i class="bi bi-x-circle"></i></div>
            <div><div class="stat-number"><?= $summary['alpha'] ?></div><div class="stat-label">Alpha</div></div>
        </div>
    </div>
</div>

<!-- Tabel Rekap -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table text-primary"></i> Riwayat Absensi</span>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/pages/laporan.php?dari=<?= $tglDari ?>&sampai=<?= $tglSampai ?>&kelas=<?= $filterKelas ?>"
               class="btn btn-sm btn-outline-danger no-print">
                <i class="bi bi-file-pdf me-1"></i> PDF
            </a>
            <a href="<?= BASE_URL ?>/api/export_excel.php?dari=<?= $tglDari ?>&sampai=<?= $tglSampai ?>&kelas=<?= $filterKelas ?>&status=<?= $filterStatus ?>"
               class="btn btn-sm btn-outline-success no-print">
                <i class="bi bi-file-excel me-1"></i> Excel
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover" id="dataTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Jam Masuk</th>
                        <th>Status</th>
                        <th>Dicatat Oleh</th>
                        <th class="no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rekaps)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">Tidak ada data absensi</td></tr>
                <?php else: foreach ($rekaps as $i => $r): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td><?= formatTanggal($r['tanggal']) ?></td>
                        <td><code><?= clean($r['nis']) ?></code></td>
                        <td class="fw-semibold"><?= clean($r['nama']) ?></td>
                        <td><?= clean($r['kelas']) ?></td>
                        <td><?= $r['jam_masuk'] ? substr($r['jam_masuk'],0,5) . ' WIB' : '-' ?></td>
                        <td><?= statusBadge($r['status']) ?></td>
                        <td class="text-muted small"><?= clean($r['pencatat'] ?? 'System') ?></td>
                        <td class="no-print">
                            <!-- Edit status -->
                            <button class="btn btn-xs btn-outline-primary btn-sm"
                                    onclick="editAbsensi(<?= $r['id'] ?>,'<?= $r['status'] ?>','<?= clean($r['nama']) ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <!-- Hapus -->
                            <?php if (currentUser()['role'] === 'admin'): ?>
                            <form method="POST" action="<?= BASE_URL ?>/api/hapus_absensi.php" class="d-inline">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        data-confirm="Hapus absensi <?= clean($r['nama']) ?> tgl <?= $r['tanggal'] ?>?">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginasi -->
        <?php if ($pages > 1): ?>
        <div class="d-flex justify-content-center py-3 no-print">
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php
                    $q = http_build_query(['dari'=>$tglDari,'sampai'=>$tglSampai,'kelas'=>$filterKelas,'status'=>$filterStatus]);
                    for ($p = 1; $p <= $pages; $p++):
                    ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= $q ?>&page=<?= $p ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Edit Status Absensi -->
<div class="modal fade" id="modalEditAbsensi" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Edit Status Absensi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/api/update_absensi.php">
                <div class="modal-body">
                    <input type="hidden" name="id" id="editAbsensiId">
                    <p class="text-muted small" id="editAbsensiNama"></p>
                    <label class="form-label">Status</label>
                    <select name="status" id="editAbsensiStatus" class="form-select">
                        <option value="hadir">Hadir</option>
                        <option value="izin">Izin</option>
                        <option value="sakit">Sakit</option>
                        <option value="alpha">Alpha</option>
                    </select>
                    <input type="hidden" name="redirect" value="<?= BASE_URL ?>/pages/rekap.php?dari=<?= $tglDari ?>&sampai=<?= $tglSampai ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editAbsensi(id, status, nama) {
    document.getElementById('editAbsensiId').value    = id;
    document.getElementById('editAbsensiStatus').value = status;
    document.getElementById('editAbsensiNama').textContent = 'Siswa: ' + nama;
    new bootstrap.Modal(document.getElementById('modalEditAbsensi')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
