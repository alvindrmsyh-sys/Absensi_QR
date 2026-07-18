# Folder: qrcode/lib/

## Cara Install phpqrcode

Library **phpqrcode** tidak disertakan karena ukuran file.
Ikuti langkah berikut untuk menginstallnya:

### Cara 1 – Download Manual
1. Buka https://sourceforge.net/projects/phpqrcode/
2. Klik "Download"
3. Ekstrak file ZIP
4. Salin file `qrlib.php` dan semua file `.php` ke folder ini (`qrcode/lib/`)

### Cara 2 – Via Composer
```bash
composer require chillerlan/php-qrcode
```
Lalu sesuaikan `includes/functions.php` fungsi `generateQR()`.

### Cara 3 – Download Langsung (Recommended untuk Pemula)
```bash
# Dari terminal / command prompt di folder project:
cd qrcode/lib
curl -L "https://raw.githubusercontent.com/t0k4rt/phpqrcode/master/qrlib.php" -o qrlib.php
```

### Struktur folder setelah install:
```
qrcode/
├── lib/
│   ├── qrlib.php        ← file utama yang diperlukan
│   └── ...              ← file pendukung lainnya
├── generated/           ← QR Code yang sudah digenerate (auto-dibuat)
└── generate.php
```

## Fallback: Gunakan API QR Online

Jika phpqrcode tidak bisa diinstall, edit `includes/functions.php`
fungsi `generateQR()` ganti dengan:

```php
function generateQR(string $nis): string {
    $genDir = ROOT_PATH . '/qrcode/generated/';
    if (!is_dir($genDir)) mkdir($genDir, 0755, true);

    // Gunakan API Google Chart (tidak perlu library)
    $url      = "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=" . urlencode($nis);
    $filename = 'qr_' . $nis . '.png';
    $content  = @file_get_contents($url);
    
    if ($content !== false) {
        file_put_contents($genDir . $filename, $content);
        return $filename;
    }
    return '';
}
```
