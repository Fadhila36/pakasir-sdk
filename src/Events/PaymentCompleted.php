<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir\Events;

use Fadhila36\Pakasir\DataObjects\TransactionDetailResponse;
use Illuminate\Queue\SerializesModels;

class PaymentCompleted
{
    use SerializesModels;

    public function __construct(
        public TransactionDetailResponse $response
    ) {}
}
