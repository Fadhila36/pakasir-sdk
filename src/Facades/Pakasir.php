<?php

namespace Fadhila36\Pakasir\Facades;

use Fadhila36\Pakasir\Enums\PaymentMethod;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array createPayment(string|PaymentMethod $paymentMethod, string $orderId, int|float $amount, ?string $redirectUrl = null)
 * @method static array getPaymentData(string|PaymentMethod $paymentMethod, string $orderId, int|float $amount, ?string $redirectUrl = null)
 * @method static array simulationPayment(string $orderId, int|float $amount)
 * @method static array cancelPayment(string $orderId, int|float $amount)
 * @method static array detailPayment(string $orderId, int|float $amount)
 * @method static string getPaymentUrl(string|PaymentMethod $paymentMethod, string $orderId, int|float $amount, ?string $redirectUrl = null)
 * 
 * @see \Fadhila36\Pakasir\Pakasir
 */
class Pakasir extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'pakasir';
    }
}
