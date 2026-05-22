<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir\Support;

class QRISHelper
{
    /**
     * Determine if a given string is a valid QRIS payload string based on EMVCo standards.
     * Most standard Indonesian QRIS payloads start with "000201" (payload format indicator).
     */
    public static function isValid(string $payload): bool
    {
        $payload = trim($payload);

        return str_starts_with($payload, '000201');
    }

    /**
     * Clean and format QRIS string.
     */
    public static function clean(string $payload): string
    {
        return trim($payload);
    }
}
