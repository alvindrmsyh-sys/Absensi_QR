<?php
/**
 * includes/header.php
 * Template header & sidebar yang digunakan di semua halaman
 * Variabel yang harus diset sebelum include:
 *   $pageTitle (string) – judul halaman
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}
$user  = currentUser();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle ?? 'AbsensiQR') ?> – <?= APP_NAME ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="/absensi-qr/assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const sidebar = document.getElementById('sidebar');
    const wrapper = document.getElementById('mainWrapper');
    const toggle  = document.getElementById('sidebarToggle');

    toggle.addEventListener('click', function () {

        sidebar.classList.toggle('collapsed');
        wrapper.classList.toggle('expanded');

    });

});
</script>
<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-qr-code-scan me-2"></i>
        <span><?= APP_NAME ?></span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">MENU UTAMA</div>
        <a href="<?= BASE_URL ?>/dashboard.php"
           class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <?php if ($user['role'] === 'admin'): ?>
        <a href="<?= BASE_URL ?>/pages/admin/siswa.php"
           class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'siswa.php' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Data Siswa
        </a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/pages/scan.php"
           class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'scan.php' ? 'active' : '' ?>">
            <i class="bi bi-camera-video"></i> Scan QR Absensi
        </a>

        <a href="<?= BASE_URL ?>/pages/admin/absensi_manual.php"
           class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'absensi_manual.php' ? 'active' : '' ?>">
            <i class="bi bi-pencil-square"></i> Absensi Manual
        </a>

        <a href="<?= BASE_URL ?>/pages/rekap.php"
           class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'rekap.php' ? 'active' : '' ?>">
            <i class="bi bi-table"></i> Rekap Absensi
        </a>

        <a href="<?= BASE_URL ?>/pages/laporan.php"
           class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'laporan.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-bar-graph"></i> Laporan
        </a>

        <?php if ($user['role'] === 'admin'): ?>
        <div class="nav-section mt-3">PENGATURAN</div>
        <a href="<?= BASE_URL ?>/pages/admin/users.php"
           class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
            <i class="bi bi-person-gear"></i> Kelola User
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($user['nama'], 0, 1)) ?>
            </div>
            <div>
                <div class="fw-semibold small"><?= clean($user['nama']) ?></div>
                <div class="text-muted" style="font-size:.7rem"><?= ucfirst($user['role']) ?></div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/logout.php" class="btn btn-logout w-100 mt-2">
            <i class="bi bi-box-arrow-left me-1"></i> Logout
        </a>
    </div>
</div>

<!-- ===== MAIN CONTENT WRAPPER ===== -->
<div class="main-wrapper" id="mainWrapper">
    <!-- Topbar -->
    <div class="topbar">
        <button class="btn btn-icon" id="sidebarToggle">
            <i class="bi bi-list fs-4"></i>
        </button>
        <div class="topbar-title"><?= clean($pageTitle ?? '') ?></div>
        <div class="topbar-right">
            <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
            <span class="ms-2 small d-none d-md-inline"><?= clean($user['nama']) ?></span>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if (!empty($flash)): ?>
    <div class="container-fluid pt-3">
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
            <?= clean($flash['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Page Content starts here -->
    <div class="content-area">
