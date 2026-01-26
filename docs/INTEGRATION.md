# Panduan Integrasi Laravel (Lengkap)

Dokumen ini dirancang untuk **Pemula** maupun **Profesional**. Kami akan memandu Anda mengintegrasikan `fadhila36/pakasir-sdk` dengan standar keamanan tinggi (Secure by Default).

---

## 📋 Daftar Isi
1. [Persiapan Awal](#1-persiapan-awal)
2. [Instalasi & Konfigurasi](#2-instalasi--konfigurasi)
3. [Implementasi Pembayaran (Best Practice)](#3-implementasi-pembayaran-best-practice)
4. [Penanganan Webhook (Otomatisasi)](#4-penanganan-webhook-otomatisasi)

---

## 1. Persiapan Awal

Sebelum memulai, pastikan Anda memiliki:
-   **PHP 8.1+** dan **Laravel 10/11/12**.
-   **Akun Pakasir**: Daftar di [pakasir.com](https://app.pakasir.com).
-   **Kredensial**: Ambil `Project Slug` dan `API Key` dari dashboard.

---

## 2. Instalasi & Konfigurasi

### A. Install Library
Buka terminal dan jalankan perintah Composer ini. Ini akan mengunduh SDK Pakasir ke proyek Laravel Anda.

```bash
composer require fadhila36/pakasir-sdk
```

### B. Publish Konfigurasi
Kita perlu memunculkan file config agar bisa diedit.

```bash
php artisan vendor:publish --provider="Fadhila36\Pakasir\PakasirServiceProvider" --tag="config"
```

### C. Setup Environment (.env)
Buka file `.env` dan tambahkan kredensial Anda.
*Tips: Jangan pernah menulis API Key langsung di code (Hardcode), selalu gunakan .env.*

```env
PAKASIR_PROJECT=slug-proyek-anda
PAKASIR_API_KEY=api-key-anda-yang-rahasia
```

---

## 3. Implementasi Pembayaran (Best Practice)

Kasus: User membeli produk.
**Prinsip Keamanan**: Jangan pernah percaya nominal harga yang dikirim dari Frontend/User. Selalu ambil harga dari Database server Anda.

### Payment Controller
File: `app/Http/Controllers/PaymentController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;     // Model Produk Anda
use App\Models\Transaction; // Model Transaksi Anda
use Fadhila36\Pakasir\Facades\Pakasir;
use Fadhila36\Pakasir\Enums\PaymentMethod;

class PaymentController extends Controller
{
    public function checkout(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'payment_method' => 'required|string', 
        ]);

        // Gunakan Database Transaction agar data konsisten (Rollback jika gagal)
        DB::beginTransaction();

        try {
            // 2. AMBIL HARGA DARI DATABASE (Critical Security)
            // Jangan gunakan $request->price, user bisa memanipulasinya!
            $product = Product::findOrFail($request->product_id);
            
            // Validasi Metode Pembayaran menggunakan Enum
            $methodEnum = PaymentMethod::tryFrom($request->payment_method);
            if (!$methodEnum) {
                return response()->json(['message' => 'Metode pembayaran tidak valid'], 400);
            }

            // Generate No Invoice Unik
            $orderId = 'INV-' . time() . rand(100, 999);

            // 3. Simpan Transaksi 'Pending' di Database Lokal
            $transaction = Transaction::create([
                'order_id'       => $orderId,
                'product_id'     => $product->id,
                'amount'         => $product->price, // Harga asli dari DB
                'payment_method' => $methodEnum->value,
                'status'         => 'pending',
            ]);

            // 4. Request ke Pakasir API
            // Kita pakai Enum agar type-safe & otomatis validasi amount
            $response = Pakasir::createPayment(
                $methodEnum,
                $transaction->order_id,
                $transaction->amount
            );

            DB::commit(); // Simpan permanen

            // 5. Kirim data ke Frontend (berisi URL pembayaran)
            return response()->json([
                'status'  => 'success',
                'message' => 'Silahkan lakukan pembayaran',
                'data'    => [
                    'order_id'       => $orderId,
                    'amount'         => $transaction->amount,
                    'payment_detail' => $response, // Berisi 'payment_url' & 'payment_number' (VA)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua simpanan DB jika error
            
            // Log error untuk developer, jangan tampilkan error detail ke user
            \Log::error('Pakasir Error: ' . $e->getMessage());

            return response()->json(['message' => 'Gagal memproses pembayaran'], 500);
        }
    }
}
```

---

## 4. Penanganan Webhook (Otomatisasi)

Webhook digunakan agar status pembayaran user otomatis berubah menjadi `Paid` tanpa user harus konfirmasi manual.

### A. Setup Route
File: `routes/api.php`

```php
use App\Http\Controllers\WebhookController;

Route::post('/webhooks/pakasir', [WebhookController::class, 'handle']);
```

### B. Webhook Controller
File: `app/Http/Controllers/WebhookController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Fadhila36\Pakasir\Facades\Pakasir;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Terima Payload dari Pakasir
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? null;

        if (!$orderId) return response()->json(['message' => 'Invalid Payload'], 400);

        // 2. Cari Transaksi
        $transaction = Transaction::where('order_id', $orderId)->first();
        if (!$transaction) return response()->json(['message' => 'Not Found'], 404);

        if ($transaction->status === 'paid') {
            return response()->json(['message' => 'Already Paid']);
        }

        // 3. SECURITY CHECK: Verifikasi ulang ke Pakasir (Double Check)
        // Jangan langsung percaya payload webhook mentah, cek status aslinya ke server Pakasir
        try {
            $check = Pakasir::detailPayment($orderId, $transaction->amount);
            
            // Pastikan nominal yang dibayar sesuai tagihan
            $apiAmount = $check['amount'] ?? 0;
            if ($apiAmount != $transaction->amount) {
                 return response()->json(['message' => 'Invalid Amount'], 400); 
            }

            $status = $check['status'] ?? 'pending';
            
        } catch (\Exception $e) {
            return response()->json(['message' => 'Verification failed'], 500);
        }

        // 4. Update Status Transaksi
        if ($status === 'completed') {
            $transaction->update(['status' => 'paid', 'paid_at' => now()]);
            // TODO: Kirim email sukses / buka akses produk user disini
        } elseif (in_array($status, ['expired', 'failed', 'canceled'])) {
            $transaction->update(['status' => $status]);
        }

        return response()->json(['status' => 'ok']);
    }
}
```

### Tips Tambahan
1.  **Exclude CSRF**: Jika menggunakan `routes/web.php`, pastikan route webhook dikecualikan dari CSRF protection di `VerifyCsrfToken.php`.
2.  **Queue**: Untuk proses berat (kirim email, dll) setelah sukses bayar, gunakan Laravel Queue agar webhook merespon cepat.
