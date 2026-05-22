# Pakasir Payment Gateway Laravel SDK

<img src="https://raw.githubusercontent.com/zeative/pakasir-sdk/main/media/pakasir-gap.png" width="200" alt="Pakasir Logo" align="right"/>

Sebuah SDK Laravel modern, modular, dan type-safe untuk mengintegrasikan layanan gerbang pembayaran (payment gateway) **[Pakasir](https://app.pakasir.com)** ke aplikasi Anda. 

SDK ini mendukung QRIS, Virtual Account multi-bank (BNI, BRI, CIMB, Permata, dll), serta PayPal dengan kalkulasi biaya (fee) otomatis, timeout/retry otomatis, logging, Laravel Events, dan sistem verifikasi Webhook terproteksi.

<a href="https://packagist.org/packages/fadhila36/pakasir-sdk"><img src="https://img.shields.io/packagist/v/fadhila36/pakasir-sdk.svg" alt="Packagist Version"></a>
<a href="https://github.com/fadhila36/pakasir-sdk"><img src="https://img.shields.io/github/languages/code-size/fadhila36/pakasir-sdk" alt="GitHub Code Size"></a>
<a href="https://github.com/fadhila36/pakasir-sdk"><img src="https://img.shields.io/badge/PHP-8.3%2B-blue?style=flat-square&logo=php" alt="PHP"></a>
<a href="https://github.com/fadhila36/pakasir-sdk"><img src="https://img.shields.io/github/license/fadhila36/pakasir-sdk" alt="GitHub License"></a>
<a href="https://github.com/fadhila36/pakasir-sdk"><img src="https://img.shields.io/github/stars/fadhila36/pakasir-sdk" alt="GitHub Stars"></a>

---

## ⚡ Fitur Unggulan

- **Laravel 13+ & PHP 8.3+ Ready** dengan standard strict typing (`declare(strict_types=1)`).
- **Data Transfer Objects (DTO):** Semua respons API dipetakan secara type-safe ke objek DTO untuk autocompletion penuh di IDE Anda.
- **Webhook Spoofing Protection:** Metode `verifyWebhook()` secara otomatis memverifikasi payload dan melakukan *double-check query* langsung ke server Pakasir.
- **Enterprise Connection Handling:** Konfigurasi timeout, retry attempts, dan delay secara otomatis pada koneksi API.
- **Laravel Events Support:** Trigger event seperti `TransactionCreated`, `PaymentCompleted`, `TransactionCanceled`, dan `WebhookReceived` secara instan.
- **Out-of-the-box Notifications:** Kirim tagihan tautan pembayaran menggunakan Laravel Notification dengan template siap pakai.

---

## 📦 Instalasi

Pastikan server Anda menggunakan **PHP 8.3+** dan aplikasi Anda berbasis **Laravel 10.0 s/d 13.0+**.

Jalankan perintah Composer berikut untuk menginstal:

```bash
composer require fadhila36/pakasir-sdk
```

---

## 🛠️ Konfigurasi

1. Publikasikan file konfigurasi package ke aplikasi host Anda:

```bash
php artisan vendor:publish --provider="Fadhila36\Pakasir\PakasirServiceProvider" --tag="config"
```

2. Atur kredensial proyek Pakasir Anda di file `.env`:

```env
PAKASIR_PROJECT=slug-proyek-anda
PAKASIR_API_KEY=api-key-rahasia-anda
PAKASIR_TIMEOUT=30
PAKASIR_RETRY_ATTEMPTS=3
PAKASIR_LOGGING_ENABLED=true
```

---

## 🚀 Penggunaan Cepat

### 1. Membuat Transaksi Pembayaran

Gunakan `PaymentMethod` Enum untuk kenyamanan autocompletion dan validasi otomatis:

```php
use Fadhila36\Pakasir\Enums\PaymentMethod;
use Fadhila36\Pakasir\Facades\Pakasir;

// Membuat transaksi QRIS secara instan
$transaction = Pakasir::createPayment(
    paymentMethod: PaymentMethod::QRIS, 
    orderId: 'INV-' . time(), 
    amount: 50000, 
    redirectUrl: 'https://websitekamu.com/invoice/complete' // Opsional
);

// Respon berupa DTO yang type-safe & IDE Friendly
echo $transaction->paymentUrl;     // Tautan ke pembayaran
echo $transaction->paymentNumber;  // Kode QRIS atau nomor VA
echo $transaction->totalPayment;   // Nominal + Fee
```

---

### 2. Memproses & Memverifikasi Webhook Secara Aman

Untuk menghindari eksploitasi webhook palsu, gunakan fitur `verifyWebhook()` yang akan memvalidasi data dan memverifikasi langsung ke server resmi Pakasir:

```php
use Fadhila36\Pakasir\Facades\Pakasir;
use Fadhila36\Pakasir\Exceptions\WebhookValidationException;

public function handleWebhook(Request $request)
{
    try {
        // Ambil nominal tagihan asli dari database Anda
        $expectedAmount = $order->amount; 

        // Verifikasi & Double-Check ke server Pakasir secara otomatis
        $webhookData = Pakasir::verifyWebhook($request->all(), $expectedAmount);

        // Jika lolos verifikasi, ubah status transaksi di DB Anda
        $order->update([
            'status' => $webhookData->status->value, // e.g., 'completed'
            'completed_at' => $webhookData->completedAt,
        ]);

        return response()->json(['status' => 'ok']);
    } catch (WebhookValidationException $e) {
        // Log & tolak jika webhook mencurigakan atau tidak valid
        return response()->json(['message' => $e->getMessage()], 400);
    }
}
```

---

## 💰 Pilihan Metode Pembayaran (`PaymentMethod`)

SDK menyediakan Enum lengkap dengan kalkulasi biaya (fee) dan validasi nominal minimum terintegrasi:

| Method         | Enum Case                  | Code             | Estimasi Fee |
| -------------- | -------------------------- | ---------------- | ------------ |
| All Methods    | `PaymentMethod::ALL`       | `all`            | -            |
| QRIS           | `PaymentMethod::QRIS`      | `qris`           | 0.7% - 1%    |
| PayPal         | `PaymentMethod::PAYPAL`    | `paypal`         | 1% (min 3k)  |
| BNI VA         | `PaymentMethod::BNI_VA`    | `bni_va`         | Rp3.500      |
| BRI VA         | `PaymentMethod::BRI_VA`    | `bri_va`         | Rp3.500      |
| CIMB Niaga VA  | `PaymentMethod::CIMB_NIAGA_VA`| `cimb_niaga_va`  | Rp3.500      |
| Maybank VA     | `PaymentMethod::MAYBANK_VA`| `maybank_va`     | Rp3.500      |
| Permata VA     | `PaymentMethod::PERMATA_VA`| `permata_va`     | Rp3.500      |
| BNC VA         | `PaymentMethod::BNC_VA`    | `bnc_va`         | Rp3.500      |
| ATM Bersama VA | `PaymentMethod::ATM_BERSAMA_VA`| `atm_bersama_va`| Rp3.500      |
| Sampoerna VA   | `PaymentMethod::SAMPOERNA_VA`| `sampoerna_va`  | Rp2.000      |
| Artha Graha VA | `PaymentMethod::ARTHA_GRAHA_VA`| `artha_graha_va`| Rp2.000      |

---

## 📖 API Reference

### 1. `createPayment()`
Membuat transaksi baru secara realtime di server Pakasir.
```php
Pakasir::createPayment(
    string|PaymentMethod $paymentMethod,
    string $orderId,
    int|float $amount,
    ?string $redirectUrl = null
): TransactionCreateResponse;
```

### 2. `detailPayment()`
Mengambil status detail transaksi terkini dari server Pakasir.
```php
Pakasir::detailPayment(string $orderId, int|float $amount): TransactionDetailResponse;
```

### 3. `cancelPayment()`
Membatalkan tagihan transaksi yang sedang aktif/pending.
```php
Pakasir::cancelPayment(string $orderId, int|float $amount): TransactionDetailResponse;
```

### 4. `simulationPayment()`
Melakukan simulasi pembayaran sukses (khusus mode Sandbox/testing).
```php
Pakasir::simulationPayment(string $orderId, int|float $amount): TransactionDetailResponse;
```

### 5. `verifyWebhook()`
Memparse dan memvalidasi keabsahan data webhook masukan.
```php
Pakasir::verifyWebhook(array $payload, int|float $expectedAmount): WebhookPayload;
```

---

## 📜 Lisensi

Didistribusikan di bawah lisensi **MIT License**. Lihat dokumen [`LICENSE`](LICENSE) untuk informasi lebih lanjut.
