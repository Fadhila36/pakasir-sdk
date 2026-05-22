<?php

declare(strict_types=1);

use Fadhila36\Pakasir\Enums\PaymentMethod;
use Fadhila36\Pakasir\Enums\TransactionStatus;
use Fadhila36\Pakasir\Events\PaymentCompleted;
use Fadhila36\Pakasir\Events\TransactionCanceled;
use Fadhila36\Pakasir\Events\TransactionCreated;
use Fadhila36\Pakasir\Events\WebhookReceived;
use Fadhila36\Pakasir\Exceptions\ApiException;
use Fadhila36\Pakasir\Exceptions\WebhookValidationException;
use Fadhila36\Pakasir\Facades\Pakasir;
use Fadhila36\Pakasir\Support\QRISHelper;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

test('it generates valid payment data structure', function () {
    $data = Pakasir::getPaymentData(PaymentMethod::QRIS, 'ORD-001', 50000);

    expect($data->orderId)->toBe('ORD-001')
        ->and($data->amount)->toBe(50000)
        ->and($data->fee)->toBe(660)
        ->and($data->totalPayment)->toBe(50660)
        ->and($data->paymentMethod)->toBe(PaymentMethod::QRIS)
        ->and($data->paymentUrl)->toContain('qris_only=1');
});

test('it handles string payment method input', function () {
    $data = Pakasir::getPaymentData('bni_va', 'ORD-002', 50000);

    expect($data->paymentMethod->value)->toBe('bni_va')
        ->and($data->fee)->toBe(3500);
});

test('it throws exception for invalid method string', function () {
    expect(fn () => Pakasir::getPaymentData('invalid_method', 'ORD-003', 50000))
        ->toThrow(InvalidArgumentException::class);
});

test('it generates correct paypal url', function () {
    $data = Pakasir::getPaymentData(PaymentMethod::PAYPAL, 'ORD-PP', 20000);

    expect($data->paymentUrl)->toContain('/paypal/');
});

test('it creates payment via API and dispatches event', function () {
    Http::fake([
        '*/api/transactioncreate/qris' => Http::response([
            'payment' => [
                'project' => 'test-project',
                'order_id' => 'ORD-001',
                'amount' => 50000,
                'fee' => 660,
                'total_payment' => 50660,
                'payment_method' => 'qris',
                'payment_number' => '0002010102122661...',
                'expired_at' => '2026-09-19T01:18:49.678Z',
            ],
        ], 200),
    ]);

    Event::fake();

    $response = Pakasir::createPayment(PaymentMethod::QRIS, 'ORD-001', 50000);

    expect($response->orderId)->toBe('ORD-001')
        ->and($response->paymentNumber)->toBe('0002010102122661...')
        ->and($response->totalPayment)->toBe(50660);

    Event::assertDispatched(TransactionCreated::class, function ($event) use ($response) {
        return $event->response->orderId === $response->orderId;
    });
});

test('it throws ApiException when create payment fails', function () {
    Http::fake([
        '*/api/transactioncreate/*' => Http::response('Internal Server Error', 500),
    ]);

    expect(fn () => Pakasir::createPayment(PaymentMethod::QRIS, 'ORD-001', 50000))
        ->toThrow(ApiException::class);
});

test('it fetches transaction detail and dispatches completed event', function () {
    Http::fake([
        '*/api/transactiondetail*' => Http::response([
            'transaction' => [
                'amount' => 50000,
                'order_id' => 'ORD-001',
                'project' => 'test-project',
                'status' => 'completed',
                'payment_method' => 'qris',
                'completed_at' => '2026-09-10T08:07:02.819Z',
            ],
        ], 200),
    ]);

    Event::fake();

    $detail = Pakasir::detailPayment('ORD-001', 50000);

    expect($detail->orderId)->toBe('ORD-001')
        ->and($detail->status)->toBe(TransactionStatus::COMPLETED)
        ->and($detail->completedAt)->toBe('2026-09-10T08:07:02.819Z')
        ->and($detail->isCompleted())->toBeTrue();

    Event::assertDispatched(PaymentCompleted::class, function ($event) use ($detail) {
        return $event->response->orderId === $detail->orderId;
    });
});

test('it simulates payment successfully', function () {
    Http::fake([
        '*/api/paymentsimulation' => Http::response([], 200),
        '*/api/transactiondetail*' => Http::response([
            'transaction' => [
                'amount' => 50000,
                'order_id' => 'ORD-001',
                'project' => 'test-project',
                'status' => 'completed',
                'payment_method' => 'qris',
                'completed_at' => '2026-09-10T08:07:02.819Z',
            ],
        ], 200),
    ]);

    $detail = Pakasir::simulationPayment('ORD-001', 50000);

    expect($detail->orderId)->toBe('ORD-001')
        ->and($detail->status)->toBe(TransactionStatus::COMPLETED);
});

test('it cancels payment and dispatches event', function () {
    Http::fake([
        '*/api/transactioncancel' => Http::response([], 200),
        '*/api/transactiondetail*' => Http::response([
            'transaction' => [
                'amount' => 50000,
                'order_id' => 'ORD-001',
                'project' => 'test-project',
                'status' => 'pending',
                'payment_method' => 'qris',
            ],
        ], 200),
    ]);

    Event::fake();

    $detail = Pakasir::cancelPayment('ORD-001', 50000);

    expect($detail->orderId)->toBe('ORD-001')
        ->and($detail->status)->toBe(TransactionStatus::CANCELED);

    Event::assertDispatched(TransactionCanceled::class, function ($event) use ($detail) {
        return $event->response->orderId === $detail->orderId;
    });
});

test('it validates webhook and performs detail verification', function () {
    $payload = [
        'amount' => 22000,
        'order_id' => 'ORD-999',
        'project' => 'test-project',
        'status' => 'completed',
        'payment_method' => 'qris',
        'completed_at' => '2026-09-10T08:07:02.819Z',
    ];

    Http::fake([
        '*/api/transactiondetail*' => Http::response([
            'transaction' => [
                'amount' => 22000,
                'order_id' => 'ORD-999',
                'project' => 'test-project',
                'status' => 'completed',
                'payment_method' => 'qris',
                'completed_at' => '2026-09-10T08:07:02.819Z',
            ],
        ], 200),
    ]);

    Event::fake();

    $verified = Pakasir::verifyWebhook($payload, 22000);

    expect($verified->orderId)->toBe('ORD-999')
        ->and($verified->status)->toBe(TransactionStatus::COMPLETED);

    Event::assertDispatched(WebhookReceived::class);
});

test('it throws exception if webhook details mismatch or verification fails', function () {
    $payload = [
        'amount' => 22000,
        'order_id' => 'ORD-999',
        'project' => 'wrong-project', // wrong project!
        'status' => 'completed',
        'payment_method' => 'qris',
    ];

    expect(fn () => Pakasir::verifyWebhook($payload, 22000))
        ->toThrow(WebhookValidationException::class);
});

test('qris helper checks emvco headers', function () {
    $validQris = '00020101021226610016ID.CO.SHOPEE.WWW01189360091800216005230208216005230303UME51440014ID.CO.QRIS.WWW0215ID10243228429300303UME5204792953033605409100003.005802ID5907Pakasir6012KAB. KEBUMEN61055439262230519SP25RZRATEQI2HQ65Q46304A079';

    expect(QRISHelper::isValid($validQris))->toBeTrue()
        ->and(QRISHelper::isValid('invalid-string'))->toBeFalse();
});
