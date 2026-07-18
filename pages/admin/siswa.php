<?php
/**
 * pages/admin/siswa.php
 * Manajemen data siswa (CRUD + Generate QR)
 * Hanya bisa diakses oleh Admin
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();   // proteksi – hanya admin

$db        = getDB();
$pageTitle = 'Data Siswa';

/* ================================================================
   PROSES ACTION (POST)
================================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ---- TAMBAH SISWA ---- */
    if ($action === 'tambah') {
        $nis    = clean($_POST['nis']);
        $nama   = clean($_POST['nama']);
        $kelas  = clean($_POST['kelas']);
        $jurusan = clean($_POST['jurusan']);
        $jk     = clean($_POST['jenis_kelamin']);

        // Validasi NIS unik
        $cek = $db->prepare("SELECT id FROM siswa WHERE nis=?");
        $cek->bind_param('s', $nis);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            setFlash('error', "NIS $nis sudah terdaftar.");
        } else {
            // Upload foto (opsional)
            $fotoName = null;
            if (!empty($_FILES['foto']['name'])) {
                $fotoName = uploadFoto($_FILES['foto'], $nis);
                if (!$fotoName) setFlash('warning', 'Foto gagal diupload, siswa disimpan tanpa foto.');
            }

            // Insert siswa
            $stmt = $db->prepare("INSERT INTO siswa (nis,nama,kelas,jurusan,jenis_kelamin,foto) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('ssssss', $nis, $nama, $kelas, $jurusan, $jk, $fotoName);

            if ($stmt->execute()) {
                $siswaId = $db->insert_id;
                // Generate QR otomatis
                $qrFile = generateQR($nis);
                if ($qrFile) {
                    $up = $db->prepare("UPDATE siswa SET qr_code=? WHERE id=?");
                    $up->bind_param('si', $qrFile, $siswaId);
                    $up->execute();
                }
                setFlash('success', "Siswa $nama berhasil ditambahkan beserta QR Code.");
            } else {
                setFlash('error', 'Gagal menambahkan siswa: ' . $db->error);
            }
        }
        redirect(BASE_URL . '/pages/admin/siswa.php');
    }

    /* ---- EDIT SISWA ---- */
    if ($action === 'edit') {
        $id      = (int)$_POST['id'];
        $nis     = clean($_POST['nis']);
        $nama    = clean($_POST['nama']);
        $kelas   = clean($_POST['kelas']);
        $jurusan = clean($_POST['jurusan']);
        $jk      = clean($_POST['jenis_kelamin']);

        // Ambil data lama untuk foto
        $old  = $db->query("SELECT foto,qr_code FROM siswa WHERE id=$id")->fetch_assoc();
        $fotoName = $old['foto'];

        if (!empty($_FILES['foto']['name'])) {
            $newFoto = uploadFoto($_FILES['foto'], $nis);
            if ($newFoto) {
                // Hapus foto lama
                if ($fotoName && file_exists(UPLOAD_PATH . $fotoName)) unlink(UPLOAD_PATH . $fotoName);
                $fotoName = $newFoto;
            }
        }

        $stmt = $db->prepare("UPDATE siswa SET nis=?,nama=?,kelas=?,jurusan=?,jenis_kelamin=?,foto=? WHERE id=?");
        $stmt->bind_param('ssssssi', $nis, $nama, $kelas, $jurusan, $jk, $fotoName, $id);
        $stmt->execute()
            ? setFlash('success', "Data siswa $nama berhasil diperbarui.")
            : setFlash('error', 'Gagal memperbarui: ' . $db->error);

        redirect(BASE_URL . '/pages/admin/siswa.php');
    }

    /* ---- HAPUS SISWA ---- */
    if ($action === 'hapus') {
        $id   = (int)$_POST['id'];
        $row  = $db->query("SELECT nama,foto,qr_code FROM siswa WHERE id=$id")->fetch_assoc();
        if ($row) {
            // Hapus file fisik
            if ($row['foto']    && file_exists(UPLOAD_PATH . $row['foto']))    unlink(UPLOAD_PATH . $row['foto']);
            if ($row['qr_code'] && file_exists(QR_PATH . $row['qr_code']))      unlink(QR_PATH . $row['qr_code']);
            $db->query("DELETE FROM siswa WHERE id=$id");
            setFlash('success', "Siswa {$row['nama']} berhasil dihapus.");
        }
        redirect(BASE_URL . '/pages/admin/siswa.php');
    }

    /* ---- GENERATE ULANG QR ---- */
    if ($action === 'gen_qr') {
        $id  = (int)$_POST['id'];
        $row = $db->query("SELECT nis,nama FROM siswa WHERE id=$id")->fetch_assoc();
        if ($row) {
            $qrFile = generateQR($row['nis']);
            if ($qrFile) {
                $up = $db->prepare("UPDATE siswa SET qr_code=? WHERE id=?");
                $up->bind_param('si', $qrFile, $id);
                $up->execute();
                setFlash('success', "QR Code {$row['nama']} berhasil di-generate ulang.");
            } else {
                setFlash('error', 'Gagal generate QR (pastikan library phpqrcode terinstall).');
            }
        }
        redirect(BASE_URL . '/pages/admin/siswa.php');
    }
}

/* ================================================================
   AMBIL DATA SISWA dengan filter & paginasi
================================================================ */
$search  = clean($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$where = $search ? "WHERE nis LIKE '%$search%' OR nama LIKE '%$search%' OR kelas LIKE '%$search%'" : '';

$total   = (int)$db->query("SELECT COUNT(*) c FROM siswa $where")->fetch_assoc()['c'];
$pages   = (int)ceil($total / $perPage);
$siswas  = $db->query("SELECT * FROM siswa $where ORDER BY kelas,nama LIMIT $perPage OFFSET $offset")->fetch_all(MYSQLI_ASSOC);

// Daftar kelas & jurusan unik untuk dropdown
$kelasList   = $db->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas")->fetch_all(MYSQLI_ASSOC);
$jurusanList = $db->query("SELECT DISTINCT jurusan FROM siswa ORDER BY jurusan")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h5 class="fw-bold mb-0">Data Siswa</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-1"></i> Tambah Siswa
    </button>
</div>

<!-- Search & info -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <form method="GET" class="d-flex flex-grow-1">
                        <input type="text" name="search" class="form-control" placeholder="Cari NIS, nama, kelas..."
                               value="<?= clean($search) ?>">
                        <button class="btn btn-primary ms-1" type="submit">Cari</button>
                        <?php if ($search): ?>
                        <a href="?" class="btn btn-secondary ms-1">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="col-md-6 text-md-end text-muted small">
                Menampilkan <?= count($siswas) ?> dari <?= $total ?> siswa
            </div>
        </div>
    </div>
</div>

<!-- Tabel Siswa -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover" id="dataTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Foto</th>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Jurusan</th>
                        <th>JK</th>
                        <th>QR Code</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($siswas)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">Tidak ada data siswa</td></tr>
                <?php else: foreach ($siswas as $i => $s): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td>
                            <?php if ($s['foto']): ?>
                            <img src="<?= UPLOAD_URL . clean($s['foto']) ?>" width="40" height="40"
                                 class="rounded-circle" style="object-fit:cover">
                            <?php else: ?>
                            <div class="user-avatar" style="width:40px;height:40px;font-size:.9rem;margin:auto">
                                <?= strtoupper(substr($s['nama'],0,1)) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><code><?= clean($s['nis']) ?></code></td>
                        <td class="fw-semibold"><?= clean($s['nama']) ?></td>
                        <td><?= clean($s['kelas']) ?></td>
                        <td><?= clean($s['jurusan']) ?></td>
                        <td><?= $s['jenis_kelamin'] === 'L' ? '👦 L' : '👧 P' ?></td>
                        <td>
                            <?php if ($s['qr_code']): ?>
                            <img src="<?= QR_URL . clean($s['qr_code']) ?>" class="qr-preview"
                                 onclick="showQRModal('<?= QR_URL . clean($s['qr_code']) ?>','<?= clean($s['nama']) ?>','<?= clean($s['nis']) ?>')"
                                 title="Klik untuk perbesar" data-bs-toggle="tooltip">
                            <?php else: ?>
                            <span class="badge bg-warning text-dark">Belum ada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <!-- Edit -->
                                <button class="btn btn-sm btn-outline-primary"
                                        onclick="editSiswa(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)"
                                        title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <!-- Generate QR -->
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="gen_qr">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-info" title="Generate QR">
                                        <i class="bi bi-qr-code"></i>
                                    </button>
                                </form>
                                <!-- Hapus -->
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            data-confirm="Yakin hapus siswa <?= clean($s['nama']) ?>?">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginasi -->
        <?php if ($pages > 1): ?>
        <div class="d-flex justify-content-center py-3">
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ================================================================
     MODAL: Tambah Siswa
================================================================ -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i>Tambah Siswa Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">NIS *</label>
                            <input type="text" name="nis" class="form-control" placeholder="Contoh: 2024001" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="nama" class="form-control" placeholder="Nama siswa" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kelas *</label>
                            <input type="text" name="kelas" class="form-control" placeholder="Contoh: X-A" required list="listKelas">
                            <datalist id="listKelas">
                                <?php foreach ($kelasList as $k): ?>
                                <option value="<?= clean($k['kelas']) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jurusan *</label>
                            <input type="text" name="jurusan" class="form-control" placeholder="Jurusan" required list="listJurusan">
                            <datalist id="listJurusan">
                                <?php foreach ($jurusanList as $j): ?>
                                <option value="<?= clean($j['jurusan']) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jenis Kelamin *</label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Foto (opsional, max 2MB)</label>
                            <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
                            <img id="fotoPreview" src="" class="mt-2 rounded foto-siswa" style="display:none">
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                QR Code akan di-generate otomatis berdasarkan NIS setelah siswa disimpan.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan & Generate QR
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================================
     MODAL: Edit Siswa (diisi via JavaScript)
================================================================ -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2"></i>Edit Data Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">NIS *</label>
                            <input type="text" name="nis" id="editNis" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="nama" id="editNama" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kelas *</label>
                            <input type="text" name="kelas" id="editKelas" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jurusan *</label>
                            <input type="text" name="jurusan" id="editJurusan" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jenis Kelamin *</label>
                            <select name="jenis_kelamin" id="editJK" class="form-select" required>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ganti Foto (opsional)</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Isi modal Edit dengan data siswa yang dipilih
function editSiswa(s) {
    document.getElementById('editId').value      = s.id;
    document.getElementById('editNis').value     = s.nis;
    document.getElementById('editNama').value    = s.nama;
    document.getElementById('editKelas').value   = s.kelas;
    document.getElementById('editJurusan').value = s.jurusan;
    document.getElementById('editJK').value      = s.jenis_kelamin;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
