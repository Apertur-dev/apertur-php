<?php

declare(strict_types=1);

namespace Apertur\Sdk\Exception;

class ValidationException extends AperturException
{
    public function __construct(string $message)
    {
        parent::__construct(400, $message, 'VALIDATION_ERROR');
    }
}
