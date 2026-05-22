<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir;

use Fadhila36\Pakasir\Contracts\PakasirInterface;
use Fadhila36\Pakasir\DataObjects\TransactionCreateResponse;
use Fadhila36\Pakasir\DataObjects\TransactionDetailResponse;
use Fadhila36\Pakasir\DataObjects\WebhookPayload;
use Fadhila36\Pakasir\Enums\PaymentMethod;
use Fadhila36\Pakasir\Enums\TransactionStatus;
use Fadhila36\Pakasir\Events\PaymentCompleted;
use Fadhila36\Pakasir\Events\TransactionCanceled;
use Fadhila36\Pakasir\Events\TransactionCreated;
use Fadhila36\Pakasir\Events\WebhookReceived;
use Fadhila36\Pakasir\Exceptions\ApiException;
use Fadhila36\Pakasir\Exceptions\WebhookValidationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class Pakasir implements PakasirInterface
{
    protected string $baseUrl;

    public function __construct(
        protected string $project,
        protected string $apiKey,
        string $baseUrl,
        protected int $timeout = 30,
        protected int $retryAttempts = 3,
        protected int $retryDelay = 100,
        protected bool $loggingEnabled = false
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Sanitize string to be URL safe.
     */
    protected function sanitizeUrlSafe(string $s): string
    {
        return preg_replace('/[^\w\-_.~0-9]/', '', $s) ?? '';
    }

    /**
     * Retrieve local and structural payment details (including payments URL).
     */
    public function getPaymentData(
        string|PaymentMethod $paymentMethod,
        string $orderId,
        int|float $amount,
        ?string $redirectUrl = null
    ): TransactionCreateResponse {
        // Convert string to Enum if needed
        if (is_string($paymentMethod)) {
            $enum = PaymentMethod::tryFrom(strtolower($paymentMethod));
            if (! $enum) {
                throw new InvalidArgumentException("Invalid payment method: {$paymentMethod}");
            }
            $paymentMethod = $enum;
        }

        $orderId = $this->sanitizeUrlSafe($orderId);

        if (strlen($orderId) < 5) {
            throw new InvalidArgumentException('Order ID must be at least 5 characters long!');
        }

        // Delegate validation and fee calculation to Enum
        $paymentMethod->validateAmount($amount);
        $fee = $paymentMethod->calculateFee($amount);

        $expiredAt = date('c', time() + (24 * 60 * 60)); // +24 hours
        $webBaseUrl = str_replace('/api', '', $this->baseUrl);

        $redirectQuery = $redirectUrl ? '&redirect='.urlencode($redirectUrl) : '';
        $methodValue = $paymentMethod->value;

        // Construct URL
        if ($paymentMethod === PaymentMethod::PAYPAL) {
            $paymentUrl = "{$webBaseUrl}/paypal/{$this->project}/{$amount}?order_id={$orderId}{$redirectQuery}";
        } elseif ($paymentMethod === PaymentMethod::QRIS) {
            $paymentUrl = "{$webBaseUrl}/pay/{$this->project}/{$amount}?order_id={$orderId}{$redirectQuery}&qris_only=1";
        } elseif ($paymentMethod === PaymentMethod::ALL) {
            $paymentUrl = "{$webBaseUrl}/pay/{$this->project}/{$amount}?order_id={$orderId}{$redirectQuery}";
        } else {
            // Specific VA
            $paymentUrl = "{$webBaseUrl}/pay/{$this->project}/{$amount}?order_id={$orderId}{$redirectQuery}&payment_method={$methodValue}";
        }

        return TransactionCreateResponse::fromArray([
            'project' => $this->project,
            'order_id' => $orderId,
            'amount' => (int) $amount,
            'fee' => $fee,
            'total_payment' => (int) ($amount + $fee),
            'payment_method' => $methodValue,
            'payment_url' => $paymentUrl,
            'redirect_url' => $redirectUrl,
            'expired_at' => $expiredAt,
            'completed_at' => null,
            'status' => 'pending',
        ]);
    }

    /**
     * Create a payment transaction via API.
     */
    public function createPayment(
        string|PaymentMethod $paymentMethod,
        string $orderId,
        int|float $amount,
        ?string $redirectUrl = null
    ): TransactionCreateResponse {
        $data = $this->getPaymentData($paymentMethod, $orderId, $amount, $redirectUrl);

        $payload = [
            'project' => $this->project,
            'order_id' => $data->orderId,
            'amount' => $data->amount,
            'api_key' => $this->apiKey,
        ];

        $url = "{$this->baseUrl}/transactioncreate/{$data->paymentMethod->value}";

        if ($this->loggingEnabled) {
            Log::info("Pakasir API Request: [POST] to {$url}", ['payload' => $payload]);
        }

        $response = Http::timeout($this->timeout)
            ->retry($this->retryAttempts, $this->retryDelay)
            ->post($url, $payload);

        if ($this->loggingEnabled) {
            Log::info("Pakasir API Response: Status {$response->status()}", ['body' => $response->body()]);
        }

        if ($response->failed()) {
            throw new ApiException(
                'Pakasir API Error: '.$response->body(),
                $response->status(),
                $response->status(),
                $response->body()
            );
        }

        $json = $response->json();

        $paymentData = $json['payment'] ?? [];

        $createdResponse = TransactionCreateResponse::fromArray([
            'project' => $this->project,
            'order_id' => $data->orderId,
            'amount' => $data->amount,
            'fee' => $paymentData['fee'] ?? $data->fee,
            'total_payment' => $paymentData['total_payment'] ?? $data->totalPayment,
            'payment_method' => $data->paymentMethod->value,
            'payment_url' => $data->paymentUrl,
            'payment_number' => $paymentData['payment_number'] ?? null,
            'expired_at' => $paymentData['expired_at'] ?? $data->expiredAt,
            'completed_at' => $paymentData['completed_at'] ?? null,
            'status' => 'pending',
        ]);

        // Dispatch Event
        event(new TransactionCreated($createdResponse));

        return $createdResponse;
    }

    /**
     * Simulate a successful payment in Sandbox environment.
     */
    public function simulationPayment(string $orderId, int|float $amount): TransactionDetailResponse
    {
        $orderId = $this->sanitizeUrlSafe($orderId);

        $payload = [
            'project' => $this->project,
            'order_id' => $orderId,
            'amount' => (int) $amount,
            'api_key' => $this->apiKey,
        ];

        $url = "{$this->baseUrl}/paymentsimulation";

        if ($this->loggingEnabled) {
            Log::info("Pakasir API Request: [POST] to {$url}", ['payload' => $payload]);
        }

        $response = Http::timeout($this->timeout)
            ->retry($this->retryAttempts, $this->retryDelay)
            ->post($url, $payload);

        if ($this->loggingEnabled) {
            Log::info("Pakasir API Response: Status {$response->status()}", ['body' => $response->body()]);
        }

        if ($response->failed()) {
            throw new ApiException(
                'Pakasir Simulation Error: '.$response->body(),
                $response->status(),
                $response->status(),
                $response->body()
            );
        }

        return $this->detailPayment($orderId, $amount);
    }

    /**
     * Cancel an existing pending payment.
     */
    public function cancelPayment(string $orderId, int|float $amount): TransactionDetailResponse
    {
        $orderId = $this->sanitizeUrlSafe($orderId);

        $payload = [
            'project' => $this->project,
            'order_id' => $orderId,
            'amount' => (int) $amount,
            'api_key' => $this->apiKey,
        ];

        $url = "{$this->baseUrl}/transactioncancel";

        if ($this->loggingEnabled) {
            Log::info("Pakasir API Request: [POST] to {$url}", ['payload' => $payload]);
        }

        $response = Http::timeout($this->timeout)
            ->retry($this->retryAttempts, $this->retryDelay)
            ->post($url, $payload);

        if ($this->loggingEnabled) {
            Log::info("Pakasir API Response: Status {$response->status()}", ['body' => $response->body()]);
        }

        if ($response->failed()) {
            throw new ApiException(
                'Pakasir Cancel Error: '.$response->body(),
                $response->status(),
                $response->status(),
                $response->body()
            );
        }

        // Fetch detail and force status to canceled
        $detail = $this->detailPayment($orderId, $amount);

        $canceledDetail = new TransactionDetailResponse(
            project: $detail->project,
            orderId: $detail->orderId,
            amount: $detail->amount,
            fee: $detail->fee,
            totalPayment: $detail->totalPayment,
            paymentMethod: $detail->paymentMethod,
            status: TransactionStatus::CANCELED,
            paymentUrl: $detail->paymentUrl,
            paymentNumber: $detail->paymentNumber,
            completedAt: $detail->completedAt,
            expiredAt: $detail->expiredAt
        );

        // Dispatch Event
        event(new TransactionCanceled($canceledDetail));

        return $canceledDetail;
    }

    /**
     * Retrieve current status of a payment directly from Pakasir server.
     */
    public function detailPayment(string $orderId, int|float $amount): TransactionDetailResponse
    {
        $orderId = $this->sanitizeUrlSafe($orderId);

        $queryParams = [
            'project' => $this->project,
            'amount' => (int) $amount,
            'order_id' => $orderId,
            'api_key' => $this->apiKey,
        ];

        $url = "{$this->baseUrl}/transactiondetail";

        if ($this->loggingEnabled) {
            Log::info("Pakasir API Request: [GET] to {$url}", ['query' => $queryParams]);
        }

        $response = Http::timeout($this->timeout)
            ->retry($this->retryAttempts, $this->retryDelay)
            ->get($url, $queryParams);

        if ($this->loggingEnabled) {
            Log::info("Pakasir API Response: Status {$response->status()}", ['body' => $response->body()]);
        }

        if ($response->failed()) {
            throw new ApiException(
                'Pakasir Check Error: '.$response->body(),
                $response->status(),
                $response->status(),
                $response->body()
            );
        }

        $json = $response->json();
        $transaction = $json['transaction'] ?? $json['data'] ?? [];

        if (empty($transaction)) {
            // Fallback parsing
            $transaction = $json;
        }

        $paymentMethodVal = $transaction['payment_method'] ?? 'all';
        $paymentMethod = PaymentMethod::tryFrom((string) $paymentMethodVal) ?? PaymentMethod::ALL;

        $statusVal = $transaction['status'] ?? 'pending';
        $status = TransactionStatus::tryFrom((string) $statusVal) ?? TransactionStatus::PENDING;

        $detailResponse = new TransactionDetailResponse(
            project: $transaction['project'] ?? $this->project,
            orderId: $transaction['order_id'] ?? $orderId,
            amount: (int) ($transaction['amount'] ?? $amount),
            fee: isset($transaction['fee']) ? (int) $transaction['fee'] : null,
            totalPayment: isset($transaction['total_payment']) ? (int) $transaction['total_payment'] : null,
            paymentMethod: $paymentMethod,
            status: $status,
            paymentUrl: $transaction['payment_url'] ?? null,
            paymentNumber: $transaction['payment_number'] ?? null,
            completedAt: $transaction['completed_at'] ?? null,
            expiredAt: $transaction['expired_at'] ?? null
        );

        // Dispatch Event if complete
        if ($detailResponse->isCompleted()) {
            event(new PaymentCompleted($detailResponse));
        }

        return $detailResponse;
    }

    /**
     * Parse and verify webhook payload. Double-checks against Pakasir API for strict security.
     *
     * @param array<string, mixed> $payload
     */
    public function verifyWebhook(array $payload, int|float $expectedAmount): WebhookPayload
    {
        $webhook = WebhookPayload::fromArray($payload);

        // 1. Basic properties validation
        if (empty($webhook->orderId)) {
            throw new WebhookValidationException("Webhook verification failed: 'order_id' is missing.");
        }

        if ($webhook->project !== $this->project) {
            throw new WebhookValidationException(
                "Webhook verification failed: Project mismatch. Expected: {$this->project}, Received: {$webhook->project}"
            );
        }

        if ($webhook->amount !== (int) $expectedAmount) {
            throw new WebhookValidationException(
                "Webhook verification failed: Amount mismatch. Expected: {$expectedAmount}, Received: {$webhook->amount}"
            );
        }

        // Dispatch WebhookReceived event
        event(new WebhookReceived($webhook));

        // 2. SECURITY DOUBLE CHECK: Confirm the status from the Pakasir servers directly (as recommended in docs)
        try {
            $apiDetail = $this->detailPayment($webhook->orderId, $webhook->amount);

            if (! $apiDetail->isCompleted()) {
                throw new WebhookValidationException(
                    "Webhook verification failed: Server status check indicates transaction is NOT completed. Status: {$apiDetail->status->value}"
                );
            }
        } catch (\Exception $e) {
            throw new WebhookValidationException(
                'Webhook verification failed during double-check status fetch: '.$e->getMessage(),
                0,
                $e
            );
        }

        return $webhook;
    }

    /**
     * Generate payment URL.
     *
     * @deprecated Use getPaymentData() instead to get the full type-safe DTO.
     */
    public function getPaymentUrl(string|PaymentMethod $paymentMethod, string $orderId, int|float $amount, ?string $redirectUrl = null): string
    {
        return $this->getPaymentData($paymentMethod, $orderId, $amount, $redirectUrl)->paymentUrl;
    }
}
