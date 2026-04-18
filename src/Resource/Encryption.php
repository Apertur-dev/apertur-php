<?php

declare(strict_types=1);

namespace Apertur\Sdk\Resource;

use Apertur\Sdk\HttpClient;

class Encryption
{
    public function __construct(private readonly HttpClient $http) {}

    public function getServerKey(): array
    {
        return $this->http->request('GET', '/api/v1/encryption/server-key');
    }
}
