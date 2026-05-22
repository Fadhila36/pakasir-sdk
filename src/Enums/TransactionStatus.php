<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';
    case CANCELED = 'canceled';
    case FAILED = 'failed';
    case UNKNOWN = 'unknown';
}
