# Panduan Pengembangan (Development Guide)

Dokumen ini ditujukan bagi pengembang (developers) yang ingin berkontribusi, memodifikasi, atau melakukan debugging pada _core source code_ library `pakasir-sdk`.

---

## 1. Setup Lingkungan Pengembangan

Untuk memastikan efisiensi dan isolasi yang baik, disarankan untuk menghubungkan paket ini secara lokal ke sebuah proyek Laravel utama (_host application_).

### Struktur Direktori yang Disarankan

Atur struktur direktori kerja Anda agar proyek library dan proyek aplikasi berada dalam satu level direktori induk:

```
/Workplace/
├── pakasir-sdk/       (Repositori Package ini)
└── laravel-playground/ (Aplikasi Laravel untuk testing)
```

### Konfigurasi Composer Lokal

Pada proyek `laravel-playground` (aplikasi host), buka file `composer.json` dan tambahkan definisi repositori lokal. Ini akan menginstruksikan Composer untuk memprioritaskan paket dari folder lokal dibanding Packagist.

```json
"repositories": [
    {
        "type": "path",
        "url": "../pakasir-sdk",
        "options": {
            "symlink": true
        }
    }
],
```

### Instalasi Package (Mode Dev)

Jalankan perintah berikut di terminal aplikasi host (`laravel-playground`) untuk menginstal paket dari source lokal:

```bash
composer require fadhila36/pakasir-sdk @dev
```

Dengan konfigurasi `symlink: true`, setiap perubahan yang Anda simpan di editor pada folder `pakasir-sdk` akan secara instan terefleksi di aplikasi host tanpa perlu menjalankan `composer update` berulang kali.

---

## 2. Arsitektur Kode

Pemahaman struktur direktori sangat penting sebelum melakukan modifikasi:

```
pakasir-sdk/
├── config/
│   └── pakasir.php           // Definisi parameter konfigurasi default
├── src/
│   ├── Facades/
│   │   └── Pakasir.php       // Static Proxy (Facade) untuk akses mudah
│   ├── Pakasir.php           // Core Business Logic & API Client
│   └── PakasirServiceProvider.php // Bootstrapping & Container Binding
├── docs/                     // Dokumentasi Teknis
├── composer.json             // Metadata Dependensi
└── README.md                 // Halaman Muka Repositori
```

---

## 3. Alur Kerja Pengembangan (Workflow)

1.  **Modifikasi**: Lakukan perubahan logika atau penambahan fitur pada direktori `src/`.
2.  **Konfigurasi**: Jika Anda memodifikasi struktur file `config/pakasir.php`, Anda perlu mempublikasikan ulang konfigurasinya di aplikasi host:
    ```bash
    php artisan vendor:publish --tag=config --force
    ```
3.  **Verifikasi Manual**: Gunakan Controller atau Artisan Command di aplikasi host untuk memanggil method yang baru Anda ubah dan pastikan outputnya sesuai harapan.

---

## 4. Standar Kode & Kontribusi

Kami menjunjung tinggi kode yang bersih dan terstandarisasi.

-   **PSR-12**: Pastikan gaya penulisan kode (coding style) mengikuti standar PSR-12.
-   **Type Hinting**: Gunakan _Type Hinting_ dan _Return Types_ secara ketat pada setiap method PHP.
-   **Dokumentasi**: Lengkapi setiap method publik dengan PHPDoc block yang deskriptif.

---

## 5. Rilis Versi (Publishing)

Langkah-langkah untuk merilis versi terbaru ke publik:

1.  **Commit & Push**: Pastikan semua perubahan telah di-commit ke repositori Git utama.
2.  **Versioning**: Buat tag versi baru mengikuti kaidah [Semantic Versioning](https://semver.org/).
    ```bash
    git tag v1.0.x
    git push origin v1.0.x
    ```
3.  **Packagist**: Packagist akan secara otomatis mendeteksi tag baru jika Webhook GitHub telah dikonfigurasi. Jika tidak, lakukan update manual di dashboard Packagist.
