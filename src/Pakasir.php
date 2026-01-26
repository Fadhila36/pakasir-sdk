<?php

namespace Fadhila36\Pakasir;

use Fadhila36\Pakasir\Enums\PaymentMethod;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Exception;

class Pakasir
{
    public function __construct(
        protected string $project,
        protected string $apiKey,
        protected string $baseUrl
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Sanitize string to be URL safe.
     */
    protected function sanitizeUrlSafe(string $s): string
    {
        return preg_replace('/[^\w\-_.~0-9]/', '', $s);
    }

    /**
     * Get payment data including URL, fees, and validation.
     *
     * @param string|PaymentMethod $paymentMethod
     * @param string $orderId
     * @param int|float $amount
     * @param string|null $redirectUrl
     * @return array
     * @throws Exception|InvalidArgumentException
     */
    public function getPaymentData(string|PaymentMethod $paymentMethod, string $orderId, int|float $amount, ?string $redirectUrl = null): array
    {
        // Convert string to Enum if needed
        if (is_string($paymentMethod)) {
            $enum = PaymentMethod::tryFrom(strtolower($paymentMethod));
            if (!$enum) {
                throw new InvalidArgumentException("Invalid payment method: {$paymentMethod}");
            }
            $paymentMethod = $enum;
        }

        $orderId = $this->sanitizeUrlSafe($orderId);

        if (strlen($orderId) < 5) {
            throw new InvalidArgumentException("Order ID must be at least 5 characters long!");
        }

        // Delegate validation and fee calculation to Enum
        $paymentMethod->validateAmount($amount);
        $fee = $paymentMethod->calculateFee($amount);
        
        $expiredAt = date('c', time() + (24 * 60 * 60)); // +24 hours
        $webBaseUrl = str_replace('/api', '', $this->baseUrl);

        $redirectQuery = $redirectUrl ? "&redirect=" . urlencode($redirectUrl) : "";
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

        return [
            'project' => $this->project,
            'order_id' => $orderId,
            'amount' => $amount,
            'fee' => $fee,
            'status' => 'pending',
            'total_payment' => $amount + $fee,
            'payment_method' => $methodValue,
            'payment_url' => $paymentUrl,
            'redirect_url' => $redirectUrl,
            'expired_at' => $expiredAt,
            'completed_at' => null,
        ];
    }

    /**
     * Create a new payment transaction via API.
     * 
     * @return array
     * @throws Exception
     */
    public function createPayment(string|PaymentMethod $paymentMethod, string $orderId, int|float $amount, ?string $redirectUrl = null): array
    {
        $data = $this->getPaymentData($paymentMethod, $orderId, $amount, $redirectUrl);

        $payload = [
            'project' => $this->project,
            'order_id' => $data['order_id'],
            'amount' => $data['amount'],
            'api_key' => $this->apiKey,
            'redirect_url' => $data['redirect_url'],
        ];
        
        $response = Http::post("{$this->baseUrl}/transactioncreate/{$data['payment_method']}", $payload);

        if ($response->failed()) {
            throw new Exception("Pakasir API Error: " . $response->body());
        }

        $json = $response->json();
        
        return array_merge($data, [
            'payment_number' => $json['payment']['payment_number'] ?? null,
            'expired_at' => $json['payment']['expired_at'] ?? $data['expired_at']
        ]);
    }

    /**
     * Simulate a successful payment.
     */
    public function simulationPayment(string $orderId, int|float $amount): array
    {
        $orderId = $this->sanitizeUrlSafe($orderId);

        $response = Http::post("{$this->baseUrl}/paymentsimulation", [
            'project' => $this->project,
            'order_id' => $orderId,
            'amount' => $amount,
            'api_key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            throw new Exception("Pakasir Simulation Error: " . $response->body());
        }
        
        return $this->detailPayment($orderId, $amount);
    }

    /**
     * Cancel an existing pending payment.
     */
    public function cancelPayment(string $orderId, int|float $amount): array
    {
        $orderId = $this->sanitizeUrlSafe($orderId);

        $response = Http::post("{$this->baseUrl}/transactioncancel", [
            'project' => $this->project,
            'order_id' => $orderId,
            'amount' => $amount,
            'api_key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            throw new Exception("Pakasir Cancel Error: " . $response->body());
        }

        $detail = $this->detailPayment($orderId, $amount);
        $detail['status'] = 'canceled';
        
        return $detail;
    }

    /**
     * Retrieve current status of a payment.
     */
    public function detailPayment(string $orderId, int|float $amount): array
    {
        $orderId = $this->sanitizeUrlSafe($orderId);

        $response = Http::get("{$this->baseUrl}/transactiondetail", [
            'project' => $this->project,
            'amount' => $amount,
            'order_id' => $orderId,
            'api_key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            throw new Exception("Pakasir Check Error: " . $response->body());
        }

        $json = $response->json();
        $transaction = $json['transaction'] ?? $json['data'] ?? [];

        if (empty($transaction)) {
             return $json;
        }
        
        // Reconstruct basic data
        $paymentMethod = $transaction['payment_method'] ?? 'all';
        $data = $this->getPaymentData($paymentMethod, $orderId, $amount);

        return array_merge($data, [
            'status' => $transaction['status'] ?? 'unknown',
            'completed_at' => $transaction['completed_at'] ?? null,
            'payment_number' => $transaction['payment_number'] ?? null,
        ]);
    }

    /**
     * Generate payment URL.
     * @deprecated Use getPaymentData() instead.
     */
    public function getPaymentUrl(string|PaymentMethod $paymentMethod, string $orderId, int|float $amount, ?string $redirectUrl = null): string
    {
        return $this->getPaymentData($paymentMethod, $orderId, $amount, $redirectUrl)['payment_url'];
    }
}
