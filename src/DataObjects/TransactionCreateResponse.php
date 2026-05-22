<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir\DataObjects;

use Fadhila36\Pakasir\Enums\PaymentMethod;

readonly class TransactionCreateResponse
{
    public function __construct(
        public string $project,
        public string $orderId,
        public int $amount,
        public int $fee,
        public int $totalPayment,
        public PaymentMethod $paymentMethod,
        public string $paymentUrl,
        public ?string $paymentNumber,
        public string $expiredAt,
        public ?string $completedAt = null,
        public string $status = 'pending'
    ) {}

    /**
     * Create DTO from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $method = $data['payment_method'] ?? 'all';
        $paymentMethod = $method instanceof PaymentMethod
            ? $method
            : (PaymentMethod::tryFrom((string) $method) ?? PaymentMethod::ALL);

        return new self(
            project: (string) ($data['project'] ?? ''),
            orderId: (string) ($data['order_id'] ?? ''),
            amount: (int) ($data['amount'] ?? 0),
            fee: (int) ($data['fee'] ?? 0),
            totalPayment: (int) ($data['total_payment'] ?? 0),
            paymentMethod: $paymentMethod,
            paymentUrl: (string) ($data['payment_url'] ?? ''),
            paymentNumber: isset($data['payment_number']) ? (string) $data['payment_number'] : null,
            expiredAt: (string) ($data['expired_at'] ?? ''),
            completedAt: isset($data['completed_at']) ? (string) $data['completed_at'] : null,
            status: (string) ($data['status'] ?? 'pending')
        );
    }

    /**
     * Convert DTO back to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'project' => $this->project,
            'order_id' => $this->orderId,
            'amount' => $this->amount,
            'fee' => $this->fee,
            'total_payment' => $this->totalPayment,
            'payment_method' => $this->paymentMethod->value,
            'payment_url' => $this->paymentUrl,
            'payment_number' => $this->paymentNumber,
            'expired_at' => $this->expiredAt,
            'completed_at' => $this->completedAt,
            'status' => $this->status,
        ];
    }
}
