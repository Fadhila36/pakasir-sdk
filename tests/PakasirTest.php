<?php

use Fadhila36\Pakasir\Enums\PaymentMethod;
use Fadhila36\Pakasir\Facades\Pakasir;

test('it generates valid payment data structure', function () {
    $data = Pakasir::getPaymentData(PaymentMethod::QRIS, 'ORD-001', 50000);

    expect($data)->toBeArray()
        ->toHaveKey('order_id', 'ORD-001')
        ->toHaveKey('amount', 50000)
        ->toHaveKey('fee')
        ->toHaveKey('payment_url')
        ->toHaveKey('expired_at');
        
    expect($data['payment_url'])->toContain('qris_only=1');
});

test('it handles string payment method input', function () {
    $data = Pakasir::getPaymentData('bni_va', 'ORD-002', 50000);
    
    expect($data['payment_method'])->toBe('bni_va');
    expect($data['fee'])->toBe(3500);
});

test('it throws exception for invalid method string', function () {
    expect(fn() => Pakasir::getPaymentData('invalid_method', 'ORD-003', 50000))
        ->toThrow(InvalidArgumentException::class);
});

test('it generates correct paypal url', function () {
    $data = Pakasir::getPaymentData(PaymentMethod::PAYPAL, 'ORD-PP', 20000);
    
    expect($data['payment_url'])->toContain('/paypal/');
});
