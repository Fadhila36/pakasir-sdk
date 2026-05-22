<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir\Contracts;

use Fadhila36\Pakasir\DataObjects\TransactionCreateResponse;
use Fadhila36\Pakasir\DataObjects\TransactionDetailResponse;
use Fadhila36\Pakasir\DataObjects\WebhookPayload;
use Fadhila36\Pakasir\Enums\PaymentMethod;
use Fadhila36\Pakasir\Exceptions\ApiException;
use Fadhila36\Pakasir\Exceptions\WebhookValidationException;

interface PakasirInterface
{
    /**
     * Create a payment transaction via API.
     *
     * @throws ApiException
     */
    public function createPayment(
        string|PaymentMethod $paymentMethod,
        string $orderId,
        int|float $amount,
        ?string $redirectUrl = null
    ): TransactionCreateResponse;

    /**
     * Retrieve local and structural payment details (including payments URL).
     */
    public function getPaymentData(
        string|PaymentMethod $paymentMethod,
        string $orderId,
        int|float $amount,
        ?string $redirectUrl = null
    ): TransactionCreateResponse;

    /**
     * Simulate a successful payment in Sandbox environment.
     *
     * @throws ApiException
     */
    public function simulationPayment(string $orderId, int|float $amount): TransactionDetailResponse;

    /**
     * Cancel an existing pending payment.
     *
     * @throws ApiException
     */
    public function cancelPayment(string $orderId, int|float $amount): TransactionDetailResponse;

    /**
     * Retrieve current status of a payment directly from Pakasir server.
     *
     * @throws ApiException
     */
    public function detailPayment(string $orderId, int|float $amount): TransactionDetailResponse;

    /**
     * Parse and verify webhook payload. Double-checks against Pakasir API for strict security.
     *
     * @param array<string, mixed> $payload
     * @throws WebhookValidationException
     */
    public function verifyWebhook(array $payload, int|float $expectedAmount): WebhookPayload;
}
