# Pakasir Payment Gateway

<img src="https://raw.githubusercontent.com/zeative/pakasir-sdk/main/media/pakasir-gap.png" width="200" alt="Pakasir Logo" align="right"/>

SDK Laravel ringan untuk integrasi pembayaran digital Indonesia [pakasir.com](https://app.pakasir.com).
Dukung QRIS, Virtual Account multi-bank & PayPal dengan kalkulasi fee otomatis.

<a href="https://packagist.org/packages/fadhila36/pakasir-sdk"><img src="https://img.shields.io/packagist/v/fadhila36/pakasir-sdk.svg" alt="Packagist Version"></a>
<a href="https://github.com/fadhila36/pakasir-sdk"><img src="https://img.shields.io/github/languages/code-size/fadhila36/pakasir-sdk" alt="GitHub Code Size"></a>
<a href="https://github.com/fadhila36/pakasir-sdk"><img src="https://img.shields.io/badge/PHP-8.1%2B-blue?style=flat-square&logo=php" alt="PHP"></a>
<a href="https://github.com/fadhila36/pakasir-sdk"><img src="https://img.shields.io/github/license/fadhila36/pakasir-sdk" alt="GitHub License"></a>
<a href="https://github.com/fadhila36/pakasir-sdk"><img src="https://img.shields.io/github/stars/fadhila36/pakasir-sdk" alt="GitHub Stars"></a>

[Installation](#-installation) · [Quick Start](#-quick-start) · [Configuration](#-configuration) · [Payment Methods](#-payment-methods) · [API Reference](#-api-reference) · [Documentation](#-documentation)

<br />

## 📚 Documentation

- [Panduan Integrasi Lengkap (Tutorial)](docs/INTEGRATION.md)
- [Panduan Development & Kontribusi](docs/DEVELOPMENT.md)

## 📦 Installation

Requires PHP 8.1+

Install `fadhila36/pakasir-sdk` using composer:

```bash
composer require fadhila36/pakasir-sdk
```

## ⚡ Quick Start

### Using Enum (Recommended)

```php
use Fadhila36\Pakasir\Enums\PaymentMethod;
use Fadhila36\Pakasir\Facades\Pakasir;

// Create QRIS Payment
$transaction = Pakasir::createPayment(
    PaymentMethod::QRIS, 
    'ORDER-' . time(), 
    10000, 
    'https://yourwebsite.com/callback' // Optional Redirect URL
);

print_r($transaction);
```

### Using String

```php
$transaction = Pakasir::createPayment('qris', 'ORDER-123', 10000);
```

## 🛠️ Configuration

Publish the config file:

```bash
php artisan vendor:publish --provider="Fadhila36\Pakasir\PakasirServiceProvider" --tag="config"
```

Then add your configuration to `.env`:

```env
PAKASIR_PROJECT=your-slug
PAKASIR_API_KEY=your-api-key
```

## 💰 Payment Methods

Supported methods in `Fadhila36\Pakasir\Enums\PaymentMethod`:

| Method         | Code             | Fee              |
| -------------- | ---------------- | ---------------- |
| All Methods    | `all`            | Varies           |
| QRIS           | `qris`           | 0.7% - 1%        |
| PayPal         | `paypal`         | 1% (min Rp3.000) |
| BNI VA         | `bni_va`         | Rp3.500          |
| BRI VA         | `bri_va`         | Rp3.500          |
| CIMB Niaga VA  | `cimb_niaga_va`  | Rp3.500          |
| Maybank VA     | `maybank_va`     | Rp3.500          |
| Permata VA     | `permata_va`     | Rp3.500          |
| BNC VA         | `bnc_va`         | Rp3.500          |
| ATM Bersama VA | `atm_bersama_va` | Rp3.500          |
| Sampoerna VA   | `sampoerna_va`   | Rp2.000          |
| Artha Graha VA | `artha_graha_va` | Rp2.000          |

*See `PaymentMethod` Enum for full list.*

## 📖 API Reference

### 1. Create Payment
Trigger API request to create a transaction.

```php
Pakasir::createPayment(
    string|PaymentMethod $method, 
    string $orderId, 
    int|float $amount, 
    ?string $redirectUrl = null
): array
```

### 2. Get Payment Data
Get full payment details locally (fee calculation, expiration, URL) **without** calling the API (except for `createPayment` usage).

```php
Pakasir::getPaymentData(
    string|PaymentMethod $method, 
    string $orderId, 
    int|float $amount,
    ?string $redirectUrl = null
): array
```

### 3. Get Payment URL
Shortcut to get just the payment URL.

```php
$url = Pakasir::getPaymentUrl(PaymentMethod::QRIS, 'ORD-01', 50000);
```

### 4. Check Status
Check payment status from API.

```php
$status = Pakasir::detailPayment('ORD-01', 50000);
```

### 5. Simulation (Sandbox)
Simulate logic for testing.

```php
Pakasir::simulationPayment('ORD-01', 50000);
```

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1.  Fork the repository.
2.  Create new branch: `git checkout -b feature/my-feature`.
3.  Commit your changes: `git commit -m 'Add some feature'`.
4.  Push to the branch: `git push origin feature/my-feature`.
5.  Open Pull Request.

## 📜 License

Distributed under the **MIT License**. See [`LICENSE`](LICENSE) for details.
