<?php

declare(strict_types=1);

namespace Apertur\Sdk\Exception;

class AuthenticationException extends AperturException
{
    public function __construct(string $message = 'Authentication failed')
    {
        parent::__construct(401, $message, 'AUTHENTICATION_FAILED');
    }
}
