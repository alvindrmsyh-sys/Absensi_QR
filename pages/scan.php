<?php
/**
 * pages/scan.php
 * Halaman scan QR Code absensi menggunakan html5-qrcode
 * Kamera otomatis aktif saat halaman dibuka
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$pageTitle = 'Scan QR Absensi';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- html5-qrcode library dari CDN -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<div class="row g-4">

    <!-- ===== KOLOM KIRI: SCANNER ===== -->
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-camera-video text-primary"></i>
                Scanner QR Code
                <span class="badge bg-success ms-auto pulse" id="statusBadge">AKTIF</span>
            </div>
            <div class="card-body p-0">
                <!-- Elemen target scanner html5-qrcode -->
                <div id="reader" style="width:100%"></div>
            </div>
            <div class="card-footer bg-transparent">
                <div class="row g-2">
                    <div class="col-6">
                        <button class="btn btn-danger w-100" id="btnStop">
                            <i class="bi bi-stop-circle me-1"></i> Stop Kamera
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-success w-100" id="btnStart">
                            <i class="bi bi-play-circle me-1"></i> Mulai Kamera
                        </button>
                    </div>
                </div>
                <p class="text-muted small mt-2 mb-0 text-center">
                    <i class="bi bi-info-circle"></i>
                    Arahkan QR Code ke kamera. Absensi tersimpan otomatis.
                </p>
            </div>
        </div>
    </div>

    <!-- ===== KOLOM KANAN: HASIL SCAN ===== -->
    <div class="col-12 col-lg-6">
        <!-- Panel default sebelum scan -->
        <div class="card h-100" id="defaultPanel">
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-5">
                <div style="font-size:4rem;color:#e2e8f0"><i class="bi bi-qr-code-scan"></i></div>
                <h5 class="fw-bold mt-3 text-muted">Menunggu Scan...</h5>
                <p class="text-muted small">Scan QR Code siswa untuk mencatat absensi</p>
                <div class="mt-3">
                    <span class="badge bg-success me-1">Hadir</span>
                    <span class="badge bg-warning me-1">Izin</span>
                    <span class="badge bg-info me-1">Sakit</span>
                    <span class="badge bg-danger">Alpha</span>
                </div>
            </div>
        </div>

        <!-- Panel hasil scan -->
        <div id="resultPanel" style="display:none">

            <!-- Loading spinner -->
            <div id="loadingPanel" class="card">
                <div class="card-body text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3 text-muted">Memproses absensi...</p>
                </div>
            </div>

            <!-- Hasil sukses / error -->
            <div id="resultCard" class="scan-result-card" style="display:none">
                <!-- konten diisi JavaScript -->
            </div>

            <!-- Form ubah status (hadir/izin/sakit/alpha) -->
            <div id="statusForm" class="card mt-3" style="display:none">
                <div class="card-header">
                    <i class="bi bi-pencil-square text-primary"></i>
                    Ubah Status Absensi
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">Jika siswa tidak scan sendiri, pilih status manual:</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-success btn-sm"  onclick="ubahStatus('hadir')">
                            <i class="bi bi-check-circle me-1"></i>Hadir
                        </button>
                        <button class="btn btn-warning btn-sm text-dark" onclick="ubahStatus('izin')">
                            <i class="bi bi-clipboard me-1"></i>Izin
                        </button>
                        <button class="btn btn-info btn-sm text-white" onclick="ubahStatus('sakit')">
                            <i class="bi bi-heart-pulse me-1"></i>Sakit
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="ubahStatus('alpha')">
                            <i class="bi bi-x-circle me-1"></i>Alpha
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log scan hari ini -->
<div class="card mt-4">
    <div class="card-header">
        <i class="bi bi-list-check text-primary"></i>
        Log Absensi Hari Ini
        <span class="badge bg-primary ms-auto" id="logCount">0</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm" id="logTable">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="logBody">
                    <tr><td colspan="5" class="text-center text-muted py-3">Belum ada scan hari ini</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
/* ============================================================
   QR Scanner dengan html5-qrcode
============================================================ */
let html5QrcodeScanner = null;
let lastScanned        = '';       // Cegah scan ganda dalam 3 detik
let currentSiswaId     = null;     // Untuk ubah status manual

// ---- Inisialisasi & mulai scanner ----
function startScanner() {
    if (html5QrcodeScanner) return;   // Sudah running

    html5QrcodeScanner = new Html5Qrcode("reader");

    const config = {
        fps:            10,
        qrbox:          { width: 250, height: 250 },
        aspectRatio:    1.0,
        showTorchButtonIfSupported: true,
    };

    html5QrcodeScanner.start(
        { facingMode: "environment" },  // kamera belakang (HP) atau webcam
        config,
        onScanSuccess,
        (msg) => { /* diabaikan – error saat scan berlangsung */ }
    ).catch(err => {
        console.warn('Kamera belakang gagal, coba kamera default:', err);
        // Fallback ke kamera default (laptop webcam)
        html5QrcodeScanner.start(
            { facingMode: "user" },
            config,
            onScanSuccess,
            () => {}
        ).catch(err2 => alert('Gagal akses kamera: ' + err2));
    });

    document.getElementById('statusBadge').textContent = 'AKTIF';
    document.getElementById('statusBadge').className   = 'badge bg-success ms-auto pulse';
}

// ---- Stop scanner ----
function stopScanner() {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.stop().then(() => {
            html5QrcodeScanner = null;
            document.getElementById('statusBadge').textContent = 'NONAKTIF';
            document.getElementById('statusBadge').className   = 'badge bg-secondary ms-auto';
        }).catch(console.warn);
    }
}

// ---- Callback saat QR berhasil dibaca ----
function onScanSuccess(decodedText) {
    if (decodedText === lastScanned) return;   // sama dengan scan sebelumnya
    lastScanned = decodedText;
    setTimeout(() => lastScanned = '', 3000);  // reset setelah 3 detik

    // Kirim NIS ke server via fetch
    prosesAbsensi(decodedText, 'hadir');
}

// ---- Proses absensi ke server (AJAX) ----
function prosesAbsensi(nis, status) {
    // Tampilkan loading
    document.getElementById('defaultPanel').style.display = 'none';
    document.getElementById('resultPanel').style.display  = 'block';
    document.getElementById('loadingPanel').style.display = 'block';
    document.getElementById('resultCard').style.display   = 'none';
    document.getElementById('statusForm').style.display   = 'none';

    const formData = new FormData();
    formData.append('nis',    nis);
    formData.append('status', status);

    fetch('<?= BASE_URL ?>/api/absensi.php', {
        method: 'POST',
        body:   formData,
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('loadingPanel').style.display = 'none';
        document.getElementById('resultCard').style.display   = 'block';

        if (data.success) {
            currentSiswaId = data.siswa_id;
            renderResult(data);
            tambahLog(data);
            playBeep(true);
        } else {
            renderError(data.message);
            playBeep(false);
        }

        // Tampilkan form ubah status jika scan berhasil
        if (data.success || data.can_update) {
            document.getElementById('statusForm').dataset.nis    = nis;
            document.getElementById('statusForm').style.display  = 'block';
        }
    })
    .catch(err => {
        document.getElementById('loadingPanel').style.display = 'none';
        document.getElementById('resultCard').style.display   = 'block';
        renderError('Terjadi kesalahan jaringan: ' + err.message);
    });
}

// ---- Render kartu hasil berhasil ----
function renderResult(data) {
    const badgeColor = { hadir:'success', izin:'warning', sakit:'info', alpha:'danger' };
    const foto = data.foto
        ? `<img src="<?= UPLOAD_URL ?>${data.foto}" class="scan-avatar me-3">`
        : `<div class="user-avatar me-3" style="width:80px;height:80px;font-size:1.5rem">${data.nama.charAt(0)}</div>`;

    document.getElementById('resultCard').innerHTML = `
        <div class="d-flex align-items-center mb-3">
            ${foto}
            <div>
                <h5 class="fw-bold mb-1">${data.nama}</h5>
                <div class="text-muted small">NIS: ${data.nis} · ${data.kelas}</div>
                <div class="text-muted small">${data.jurusan}</div>
            </div>
        </div>
        <div class="d-flex gap-3 flex-wrap mb-3">
            <div><span class="text-muted small">Status</span><br>
                <span class="badge bg-${badgeColor[data.status]} fs-6">${data.status.toUpperCase()}</span></div>
            <div><span class="text-muted small">Jam Masuk</span><br>
                <strong class="fs-5">${data.jam_masuk || '-'}</strong></div>
            <div><span class="text-muted small">Tanggal</span><br>
                <strong>${data.tanggal}</strong></div>
        </div>
        <div class="alert alert-success py-2 mb-0">
            <i class="bi bi-check-circle-fill me-1"></i>
            <strong>Absensi berhasil dicatat!</strong>
        </div>`;
}

// ---- Render pesan error ----
function renderError(msg) {
    document.getElementById('resultCard').innerHTML = `
        <div class="alert alert-danger mb-0">
            <i class="bi bi-x-circle-fill me-2"></i>
            <strong>Gagal:</strong> ${msg}
        </div>`;
}

// ---- Tambah baris ke log tabel ----
function tambahLog(data) {
    const tbody = document.getElementById('logBody');
    // Hapus baris "belum ada scan"
    if (tbody.querySelector('td[colspan]')) tbody.innerHTML = '';

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>${data.jam_masuk || new Date().toLocaleTimeString('id-ID')}</td>
        <td><code>${data.nis}</code></td>
        <td>${data.nama}</td>
        <td>${data.kelas}</td>
        <td><span class="badge bg-success">Hadir</span></td>`;
    tr.style.animation = 'fadeInUp .3s ease';
    tbody.prepend(tr);

    // Update counter
    const logCount = document.getElementById('logCount');
    logCount.textContent = parseInt(logCount.textContent || 0) + 1;
}

// ---- Ubah status absensi secara manual ----
function ubahStatus(status) {
    const nis = document.getElementById('statusForm').dataset.nis;
    if (!nis) return;
    prosesAbsensi(nis, status);
}

// ---- Beep feedback audio ----
function playBeep(success) {
    try {
        const ctx  = new (window.AudioContext || window.webkitAudioContext)();
        const osc  = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = success ? 880 : 440;
        osc.type            = 'sine';
        gain.gain.setValueAtTime(.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(.001, ctx.currentTime + .3);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + .3);
    } catch {}
}

// ---- Event listeners tombol ----
document.getElementById('btnStart').addEventListener('click', startScanner);
document.getElementById('btnStop').addEventListener('click', stopScanner);

// ---- Auto start saat halaman load ----
window.addEventListener('load', () => setTimeout(startScanner, 500));
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
