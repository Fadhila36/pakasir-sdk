<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir\Events;

use Fadhila36\Pakasir\DataObjects\WebhookPayload;
use Illuminate\Queue\SerializesModels;

class WebhookReceived
{
    use SerializesModels;

    public function __construct(
        public WebhookPayload $payload
    ) {}
}
