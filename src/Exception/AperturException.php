<?php

declare(strict_types=1);

namespace Apertur\Sdk\Exception;

class AperturException extends \RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        string $message,
        private readonly ?string $errorCode = null,
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}
