<?php
/**
 * pages/admin/users.php
 * Kelola user (admin & guru) – hanya admin
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();
$pageTitle = 'Kelola User';
$db        = getDB();

/* ---- Proses POST ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah') {
        $username = clean($_POST['username']);
        $nama     = clean($_POST['nama']);
        $email    = clean($_POST['email']);
        $role     = clean($_POST['role']);
        $pass     = $_POST['password'] ?? '';

        if (strlen($pass) < 6) {
            setFlash('error', 'Password minimal 6 karakter.');
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (username,password,nama,email,role) VALUES (?,?,?,?,?)");
            $stmt->bind_param('sssss', $username, $hash, $nama, $email, $role);
            $stmt->execute()
                ? setFlash('success', "User $nama berhasil ditambahkan.")
                : setFlash('error', 'Gagal: username mungkin sudah dipakai.');
        }
    }

    if ($action === 'hapus') {
        $id = (int)$_POST['id'];
        if ($id === currentUser()['id']) {
            setFlash('error', 'Tidak bisa menghapus akun sendiri.');
        } else {
            $db->query("DELETE FROM users WHERE id=$id");
            setFlash('success', 'User berhasil dihapus.');
        }
    }

    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $db->query("UPDATE users SET aktif = NOT aktif WHERE id=$id");
        setFlash('success', 'Status user diperbarui.');
    }

    redirect(BASE_URL . '/pages/admin/users.php');
}

$users = $db->query("SELECT * FROM users ORDER BY role, nama")->fetch_all(MYSQLI_ASSOC);
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Kelola User</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-person-plus me-1"></i> Tambah User
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover">
            <thead>
                <tr><th>#</th><th>Username</th><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><code><?= clean($u['username']) ?></code></td>
                    <td class="fw-semibold"><?= clean($u['nama']) ?></td>
                    <td class="text-muted small"><?= clean($u['email']) ?></td>
                    <td>
                        <span class="badge <?= $u['role']==='admin' ? 'bg-danger' : 'bg-primary' ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $u['aktif'] ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $u['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-warning"
                                    title="<?= $u['aktif']?'Nonaktifkan':'Aktifkan' ?>">
                                <i class="bi bi-toggle-<?= $u['aktif']?'on':'off' ?>"></i>
                            </button>
                        </form>
                        <?php if ($u['id'] !== currentUser()['id']): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="hapus">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    data-confirm="Hapus user <?= clean($u['nama']) ?>?">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i>Tambah User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body row g-3">
                    <div class="col-6">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="guru">Guru</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Password * (min 6 karakter)</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
