<?php

declare(strict_types=1);

namespace Apertur\Sdk\Exception;

class NotFoundException extends AperturException
{
    public function __construct(string $message = 'Not found')
    {
        parent::__construct(404, $message, 'NOT_FOUND');
    }
}
