<?php

declare(strict_types=1);

namespace Apertur\Sdk\Resource;

use Apertur\Sdk\HttpClient;

class Uploads
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * @param array{page?: int, pageSize?: int} $params
     */
    public function list(array $params = []): array
    {
        $query = http_build_query($params);
        $suffix = $query === '' ? '' : "?{$query}";
        return $this->http->request('GET', "/api/v1/uploads{$suffix}");
    }

    /**
     * @param array{limit?: int} $params
     */
    public function recent(array $params = []): array
    {
        $query = http_build_query($params);
        $suffix = $query === '' ? '' : "?{$query}";
        return $this->http->request('GET', "/api/v1/uploads/recent{$suffix}");
    }
}
