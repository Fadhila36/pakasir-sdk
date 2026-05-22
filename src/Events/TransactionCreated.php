<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir\Events;

use Fadhila36\Pakasir\DataObjects\TransactionCreateResponse;
use Illuminate\Queue\SerializesModels;

class TransactionCreated
{
    use SerializesModels;

    public function __construct(
        public TransactionCreateResponse $response
    ) {}
}
