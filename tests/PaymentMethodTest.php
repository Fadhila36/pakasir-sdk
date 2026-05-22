<?php

declare(strict_types=1);

use Fadhila36\Pakasir\Enums\PaymentMethod;

test('calculates qris fee correctly', function () {
    // 0.7% + 310 for <= 105,000
    // 10,000 -> 70 + 310 = 380
    expect(PaymentMethod::QRIS->calculateFee(10000))->toBe(380);

    // 1% for > 105,000
    // 200,000 -> 2,000
    expect(PaymentMethod::QRIS->calculateFee(200000))->toBe(2000);
});

test('calculates paypal fee correctly', function () {
    // 1% min 3000
    // 100,000 * 1% = 1,000 -> min 3,000
    expect(PaymentMethod::PAYPAL->calculateFee(100000))->toBe(3000);

    // 500,000 * 1% = 5,000 -> 5,000
    expect(PaymentMethod::PAYPAL->calculateFee(500000))->toBe(5000);
});

test('calculates va fees correctly', function () {
    // BNI/BRI/etc = 3500
    expect(PaymentMethod::BNI_VA->calculateFee(100000))->toBe(3500);
    expect(PaymentMethod::BRI_VA->calculateFee(50000))->toBe(3500);

    // Sampoerna = 2000
    expect(PaymentMethod::SAMPOERNA_VA->calculateFee(100000))->toBe(2000);
});

test('validates minimum amount', function () {
    // Regular min 500
    expect(fn () => PaymentMethod::QRIS->validateAmount(499))->toThrow(InvalidArgumentException::class);
    expect(fn () => PaymentMethod::QRIS->validateAmount(500))->not->toThrow(Exception::class);

    // PayPal min 10,000
    expect(fn () => PaymentMethod::PAYPAL->validateAmount(9999))->toThrow(InvalidArgumentException::class);
    expect(fn () => PaymentMethod::PAYPAL->validateAmount(10000))->not->toThrow(Exception::class);
});
