<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir\Enums;

use InvalidArgumentException;

enum PaymentMethod: string
{
    case ALL = 'all';
    case QRIS = 'qris';
    case PAYPAL = 'paypal';

    // Virtual Accounts
    case CIMB_NIAGA_VA = 'cimb_niaga_va';
    case BNI_VA = 'bni_va';
    case BNC_VA = 'bnc_va';
    case MAYBANK_VA = 'maybank_va';
    case PERMATA_VA = 'permata_va';
    case ATM_BERSAMA_VA = 'atm_bersama_va';
    case BRI_VA = 'bri_va';

    // Other VAs
    case SAMPOERNA_VA = 'sampoerna_va';
    case ARTHA_GRAHA_VA = 'artha_graha_va';

    /**
     * Get the fee for the payment method.
     */
    public function calculateFee(int|float $amount): int
    {
        return match ($this) {
            self::QRIS => $amount > 105000
                ? (int) round(0.01 * $amount)
                : (int) round(0.007 * $amount + 310),

            self::PAYPAL => max((int) round(0.01 * $amount), 3000),

            self::CIMB_NIAGA_VA,
            self::BNI_VA,
            self::BNC_VA,
            self::MAYBANK_VA,
            self::PERMATA_VA,
            self::ATM_BERSAMA_VA,
            self::BRI_VA => 3500,

            self::SAMPOERNA_VA,
            self::ARTHA_GRAHA_VA => 2000,

            self::ALL => 0,
        };
    }

    /**
     * Check if the minimum amount requirement is met.
     *
     * @throws InvalidArgumentException
     */
    public function validateAmount(int|float $amount): void
    {
        if ($this === self::PAYPAL) {
            if ($amount < 10000) {
                throw new InvalidArgumentException('Amount must be at least Rp10.000 for PayPal!');
            }
        } elseif ($amount < 500) {
            throw new InvalidArgumentException('Amount must be at least Rp500!');
        }
    }
}
