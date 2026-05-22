<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir\DataObjects;

use Fadhila36\Pakasir\Enums\PaymentMethod;
use Fadhila36\Pakasir\Enums\TransactionStatus;

readonly class TransactionDetailResponse
{
    public function __construct(
        public string $project,
        public string $orderId,
        public int $amount,
        public ?int $fee,
        public ?int $totalPayment,
        public PaymentMethod $paymentMethod,
        public TransactionStatus $status,
        public ?string $paymentUrl,
        public ?string $paymentNumber,
        public ?string $completedAt,
        public ?string $expiredAt = null
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

        $statusVal = $data['status'] ?? 'pending';
        $status = $statusVal instanceof TransactionStatus
            ? $statusVal
            : (TransactionStatus::tryFrom((string) $statusVal) ?? TransactionStatus::PENDING);

        return new self(
            project: (string) ($data['project'] ?? ''),
            orderId: (string) ($data['order_id'] ?? ''),
            amount: (int) ($data['amount'] ?? 0),
            fee: isset($data['fee']) ? (int) $data['fee'] : null,
            totalPayment: isset($data['total_payment']) ? (int) $data['total_payment'] : null,
            paymentMethod: $paymentMethod,
            status: $status,
            paymentUrl: isset($data['payment_url']) ? (string) $data['payment_url'] : null,
            paymentNumber: isset($data['payment_number']) ? (string) $data['payment_number'] : null,
            completedAt: isset($data['completed_at']) ? (string) $data['completed_at'] : null,
            expiredAt: isset($data['expired_at']) ? (string) $data['expired_at'] : null
        );
    }

    /**
     * Helper to verify if the payment was successful.
     */
    public function isCompleted(): bool
    {
        return $this->status === TransactionStatus::COMPLETED;
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
            'status' => $this->status->value,
            'payment_url' => $this->paymentUrl,
            'payment_number' => $this->paymentNumber,
            'completed_at' => $this->completedAt,
            'expired_at' => $this->expiredAt,
        ];
    }
}
