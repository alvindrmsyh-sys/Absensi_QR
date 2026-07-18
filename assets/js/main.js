/**
 * assets/js/main.js
 * JavaScript utama – sidebar toggle, utils, dan inisialisasi umum
 */

document.addEventListener('DOMContentLoaded', () => {

    /* =====================================================
       SIDEBAR TOGGLE (desktop & mobile)
    ===================================================== */
    const sidebar        = document.getElementById('sidebar');
    const mainWrapper    = document.getElementById('mainWrapper');
    const toggleBtn      = document.getElementById('sidebarToggle');

    // Buat overlay untuk mobile
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                // Mobile: slide-in dari kiri
                sidebar.classList.toggle('open');
                overlay.classList.toggle('show');
            } else {
                // Desktop: perkecil margin
                sidebar.classList.toggle('collapsed');
                mainWrapper.classList.toggle('expanded');
            }
        });
    }

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });

    /* =====================================================
       AUTO-DISMISS ALERT setelah 4 detik
    ===================================================== */
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 4000);
    });

    /* =====================================================
       KONFIRMASI DELETE sebelum submit form hapus
    ===================================================== */
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function(e) {
            const msg = this.dataset.confirm || 'Yakin ingin menghapus data ini?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    /* =====================================================
       PREVIEW FOTO SISWA sebelum upload
    ===================================================== */
    const fotoInput = document.getElementById('foto');
    const fotoPreview = document.getElementById('fotoPreview');
    if (fotoInput && fotoPreview) {
        fotoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => { fotoPreview.src = e.target.result; fotoPreview.style.display = 'block'; };
                reader.readAsDataURL(file);
            }
        });
    }

    /* =====================================================
       TOOLTIP Bootstrap – aktifkan di semua elemen
    ===================================================== */
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });

    /* =====================================================
       TABLE SEARCH – filter baris tabel secara real-time
    ===================================================== */
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#dataTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    }

    /* =====================================================
       ANIMASI COUNTER pada stat-card (dashboard)
    ===================================================== */
    document.querySelectorAll('.stat-number[data-count]').forEach(el => {
        const target = parseInt(el.dataset.count) || 0;
        let current  = 0;
        const step   = Math.ceil(target / 30);
        const timer  = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = current;
            if (current >= target) clearInterval(timer);
        }, 30);
    });

});

/* =====================================================
   FUNGSI GLOBAL: tampilkan modal QR Code (zoom)
===================================================== */
function showQRModal(qrUrl, nama, nis) {
    const modalHtml = `
    <div class="modal fade" id="qrModal" tabindex="-1">
      <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content text-center p-4">
          <h6 class="fw-bold mb-1">${nama}</h6>
          <small class="text-muted mb-3 d-block">NIS: ${nis}</small>
          <img src="${qrUrl}" alt="QR Code" class="img-fluid mb-3" style="max-width:200px;margin:auto;border:1px solid #e2e8f0;border-radius:8px;padding:8px">
          <a href="${qrUrl}" download="QR_${nis}.png" class="btn btn-primary btn-sm">
            <i class="bi bi-download me-1"></i>Download QR
          </a>
          <button class="btn btn-secondary btn-sm mt-2" data-bs-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>`;

    // Hapus modal lama jika ada
    const old = document.getElementById('qrModal');
    if (old) old.remove();

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    new bootstrap.Modal(document.getElementById('qrModal')).show();
}

/* =====================================================
   FUNGSI GLOBAL: format waktu real-time di dashboard
===================================================== */
function startClock(el) {
    if (!el) return;
    const update = () => {
        el.textContent = new Date().toLocaleTimeString('id-ID', { hour12: false });
    };
    update();
    setInterval(update, 1000);
}
document.addEventListener('DOMContentLoaded', () => startClock(document.getElementById('liveClock')));
