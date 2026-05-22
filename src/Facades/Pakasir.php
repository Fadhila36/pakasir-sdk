<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir\Facades;

use Fadhila36\Pakasir\DataObjects\TransactionCreateResponse;
use Fadhila36\Pakasir\DataObjects\TransactionDetailResponse;
use Fadhila36\Pakasir\DataObjects\WebhookPayload;
use Fadhila36\Pakasir\Enums\PaymentMethod;
use Illuminate\Support\Facades\Facade;

/**
 * @method static TransactionCreateResponse createPayment(string|PaymentMethod $paymentMethod, string $orderId, int|float $amount, ?string $redirectUrl = null)
 * @method static TransactionCreateResponse getPaymentData(string|PaymentMethod $paymentMethod, string $orderId, int|float $amount, ?string $redirectUrl = null)
 * @method static TransactionDetailResponse simulationPayment(string $orderId, int|float $amount)
 * @method static TransactionDetailResponse cancelPayment(string $orderId, int|float $amount)
 * @method static TransactionDetailResponse detailPayment(string $orderId, int|float $amount)
 * @method static WebhookPayload verifyWebhook(array $payload, int|float $expectedAmount)
 * @method static string getPaymentUrl(string|PaymentMethod $paymentMethod, string $orderId, int|float $amount, ?string $redirectUrl = null)
 *
 * @see \Fadhila36\Pakasir\Pakasir
 */
class Pakasir extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'pakasir';
    }
}
