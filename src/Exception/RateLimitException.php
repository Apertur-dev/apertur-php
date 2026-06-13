<?php

declare(strict_types=1);

namespace Apertur\Sdk\Exception;

class RateLimitException extends AperturException
{
    public function __construct(
        string $message,
        private readonly ?int $retryAfter = null,
    ) {
        parent::__construct(429, $message, 'RATE_LIMIT');
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
