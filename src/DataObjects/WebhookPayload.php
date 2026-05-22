<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir\DataObjects;

use Fadhila36\Pakasir\Enums\PaymentMethod;
use Fadhila36\Pakasir\Enums\TransactionStatus;

readonly class WebhookPayload
{
    public function __construct(
        public string $project,
        public string $orderId,
        public int $amount,
        public PaymentMethod $paymentMethod,
        public TransactionStatus $status,
        public ?string $completedAt
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
            paymentMethod: $paymentMethod,
            status: $status,
            completedAt: isset($data['completed_at']) ? (string) $data['completed_at'] : null
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
            'payment_method' => $this->paymentMethod->value,
            'status' => $this->status->value,
            'completed_at' => $this->completedAt,
        ];
    }
}
