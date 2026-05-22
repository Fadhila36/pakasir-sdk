<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir\Exceptions;

use Throwable;

class ApiException extends PakasirException
{
    protected ?int $statusCode;

    protected ?string $responseBody;

    public function __construct(
        string $message,
        int $code = 0,
        ?int $statusCode = null,
        ?string $responseBody = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
}
