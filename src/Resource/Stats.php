<?php

declare(strict_types=1);

namespace Apertur\Sdk\Resource;

use Apertur\Sdk\HttpClient;

class Stats
{
    public function __construct(private readonly HttpClient $http) {}

    public function get(): array
    {
        return $this->http->request('GET', '/api/v1/stats');
    }
}
