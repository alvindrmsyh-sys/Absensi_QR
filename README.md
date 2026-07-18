# 📱 Website Absensi Siswa Berbasis QR Code

**Stack:** PHP Native · MySQL · Bootstrap 5 · html5-qrcode · phpqrcode

---

## 📁 Struktur Folder

```
absensi-qr/
│
├── api/                         ← Endpoint AJAX / action backend
│   ├── absensi.php              ← Simpan absensi dari scan QR (JSON)
│   ├── update_absensi.php       ← Update status absensi
│   ├── hapus_absensi.php        ← Hapus record absensi (admin only)
│   └── export_excel.php         ← Export rekap ke CSV/Excel
│
├── assets/
│   ├── css/style.css            ← Stylesheet utama (modern blue/white)
│   └── js/main.js               ← JavaScript global (sidebar, counter, dll)
│
├── config/
│   ├── app.php                  ← Konstanta path, URL, timezone
│   └── database.php             ← Konfigurasi & koneksi MySQL (mysqli)
│
├── database/
│   ├── absensi_db_final.sql     ← ⭐ Import file ini ke phpMyAdmin
│   └── generate_hash.php        ← Utility generate bcrypt password hash
│
├── includes/
│   ├── auth.php                 ← Session, login, logout, proteksi halaman
│   ├── functions.php            ← Helper: clean, upload, generateQR, dll
│   ├── header.php               ← Template header + sidebar (include di tiap halaman)
│   └── footer.php               ← Template footer + script Bootstrap/Chart.js
│
├── pages/
│   ├── scan.php                 ← 📷 Scan QR Code (html5-qrcode, kamera otomatis)
│   ├── rekap.php                ← 📊 Rekap & riwayat absensi (filter tanggal/kelas)
│   ├── laporan.php              ← 📄 Cetak PDF & ringkasan per siswa
│   └── admin/
│       ├── siswa.php            ← 👥 CRUD siswa + generate QR (admin)
│       ├── users.php            ← 👤 Kelola user admin/guru (admin)
│       └── absensi_manual.php   ← ✏️ Input absensi manual per kelas
│
├── qrcode/
│   ├── lib/                     ← ⚠️ Letakkan phpqrcode di sini (lihat README)
│   ├── generated/               ← File PNG QR Code hasil generate (auto)
│   └── generate.php             ← Script generate QR via URL atau CLI
│
├── uploads/
│   └── foto/                    ← Foto siswa yang diupload (auto)
│
├── index.php                    ← Entry point (redirect ke dashboard/login)
├── login.php                    ← Halaman login
├── dashboard.php                ← Dashboard utama (stat cards + grafik)
└── logout.php                   ← Proses logout
```

---

## 🗄️ ERD Database

```
┌─────────────┐         ┌─────────────┐         ┌─────────────┐
│    users    │         │    siswa    │         │   absensi   │
├─────────────┤         ├─────────────┤         ├─────────────┤
│ id (PK)     │◄──┐     │ id (PK)     │◄──┐     │ id (PK)     │
│ username    │   │     │ nis (UNIQUE)│   │     │ siswa_id(FK)│──► siswa.id
│ password    │   │     │ nama        │   └──── │ tanggal     │
│ nama        │   │     │ kelas       │         │ jam_masuk   │
│ email       │   └──── │ jurusan     │         │ status      │
│ role        │ dicatat │ jenis_kelamin         │ keterangan  │
│ aktif       │  _oleh  │ foto        │         │ dicatat_oleh│──► users.id
└─────────────┘         │ qr_code     │         │ created_at  │
                        │ aktif       │         └─────────────┘
                        └─────────────┘
                        
CONSTRAINT: absensi(siswa_id, tanggal) UNIQUE
→ 1 siswa hanya boleh 1 absensi per hari
```

---

## ⚙️ Cara Instalasi di XAMPP

### Langkah 1 – Persiapan XAMPP
```
1. Download & install XAMPP dari https://www.apachefriends.org
2. Buka XAMPP Control Panel
3. Klik START pada Apache dan MySQL
```

### Langkah 2 – Copy Project
```
1. Copy folder absensi-qr ke:
   C:\xampp\htdocs\absensi-qr        (Windows)
   /opt/lampp/htdocs/absensi-qr      (Linux)
   /Applications/XAMPP/htdocs/...    (Mac)
```

### Langkah 3 – Import Database
```
1. Buka browser → http://localhost/phpmyadmin
2. Klik "New" di sidebar kiri → beri nama database: absensi_db → Create
3. Klik tab "Import"
4. Pilih file: database/absensi_db_final.sql
5. Klik "Go" / "Import"
```

### Langkah 4 – Konfigurasi (jika perlu)
Edit file `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // Kosong untuk XAMPP default
define('DB_NAME', 'absensi_db');
```

Edit file `config/app.php`:
```php
define('BASE_URL', 'http://localhost/absensi-qr');
```

### Langkah 5 – Install Library phpqrcode
**Cara termudah (download manual):**
```
1. Buka: https://sourceforge.net/projects/phpqrcode/
2. Download & ekstrak
3. Salin file qrlib.php (dan file .php lainnya) ke folder:
   absensi-qr/qrcode/lib/
```

**Cara alternatif (tanpa phpqrcode):**
Edit `includes/functions.php` → fungsi `generateQR()`:
```php
function generateQR(string $nis): string {
    $genDir = ROOT_PATH . '/qrcode/generated/';
    if (!is_dir($genDir)) mkdir($genDir, 0755, true);
    $url     = "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=" . urlencode($nis);
    $fname   = 'qr_' . $nis . '.png';
    $content = @file_get_contents($url);
    if ($content !== false) { file_put_contents($genDir . $fname, $content); return $fname; }
    return '';
}
```

### Langkah 6 – Permission Folder (Linux/Mac)
```bash
chmod 755 absensi-qr/uploads/foto/
chmod 755 absensi-qr/qrcode/generated/
```

---

## 🚀 Cara Menjalankan

1. Buka browser: **http://localhost/absensi-qr**
2. Login dengan:
   - **Admin:** username `admin` / password `admin123`
   - **Guru:**  username `guru1` / password `admin123`

---

## 🔑 Fitur Per Role

| Fitur | Admin | Guru |
|-------|:-----:|:----:|
| Dashboard | ✅ | ✅ |
| Scan QR Absensi | ✅ | ✅ |
| Absensi Manual | ✅ | ✅ |
| Rekap Absensi | ✅ | ✅ |
| Laporan + Cetak PDF | ✅ | ✅ |
| Export Excel | ✅ | ✅ |
| Data Siswa (CRUD) | ✅ | ❌ |
| Generate QR Code | ✅ | ❌ |
| Kelola User | ✅ | ❌ |
| Hapus Absensi | ✅ | ❌ |

---

## 📷 Cara Kerja Scan QR

```
Flowchart Scan QR:

[Buka halaman Scan QR]
        ↓
[Kamera otomatis aktif]
        ↓
[Arahkan QR Code siswa ke kamera]
        ↓
[html5-qrcode membaca QR → mendapat NIS]
        ↓
[Kirim NIS ke api/absensi.php via fetch/AJAX]
        ↓
    Sudah absen hari ini?
    ┌── YA ──→ Update status (jika ubah manual)
    └── TIDAK → Insert absensi baru (status: Hadir)
        ↓
[Tampilkan hasil: nama, kelas, jam masuk, status]
        ↓
[Notifikasi berhasil + bunyi beep]
        ↓
[Tambah ke log table di bawah]
```

---

## 🔐 Keamanan

- Password di-hash dengan **bcrypt** (tidak tersimpan plain text)
- Session PHP dengan `session_regenerate_id()` setelah login
- Semua input di-sanitasi dengan `htmlspecialchars()` (XSS prevention)
- CSRF token pada form login
- Proteksi halaman: setiap halaman cek `requireLogin()` / `requireAdmin()`
- Prepared statements untuk semua query database (SQL injection prevention)

---

## 📚 Library yang Digunakan

| Library | Versi | Fungsi |
|---------|-------|--------|
| Bootstrap | 5.3.2 | UI framework responsif |
| Bootstrap Icons | 1.11.3 | Icon SVG |
| html5-qrcode | 2.3.8 | Scan QR via webcam/kamera |
| Chart.js | 4.4.0 | Grafik absensi di dashboard |
| phpqrcode | 1.1.4 | Generate QR Code PNG di server |
| Plus Jakarta Sans | - | Font (Google Fonts) |

---

## 🛠️ Troubleshooting

**Kamera tidak muncul:**
- Pastikan akses https:// atau localhost (browser blokir kamera di http biasa)
- Klik "Izinkan" saat browser minta akses kamera
- Coba browser Chrome atau Firefox terbaru

**QR tidak ter-generate:**
- Pastikan folder `qrcode/lib/` berisi file `qrlib.php`
- Atau gunakan alternatif Google Chart API (lihat Langkah 5)

**Database error:**
- Pastikan MySQL sudah running di XAMPP
- Cek config di `config/database.php`
- Pastikan database `absensi_db` sudah di-import

**Password salah:**
- Jalankan `http://localhost/absensi-qr/database/generate_hash.php`
- Update hash di database via phpMyAdmin
- **Hapus file generate_hash.php setelah selesai!**
