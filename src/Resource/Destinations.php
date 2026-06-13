<?php

declare(strict_types=1);

namespace Apertur\Sdk\Resource;

use Apertur\Sdk\HttpClient;

class Destinations
{
    public function __construct(private readonly HttpClient $http) {}

    public function list(string $projectId): array
    {
        return $this->http->request('GET', "/api/v1/projects/{$projectId}/destinations");
    }

    public function create(string $projectId, array $config): array
    {
        return $this->http->request('POST', "/api/v1/projects/{$projectId}/destinations", ['json' => $config]);
    }

    public function update(string $projectId, string $destId, array $config): array
    {
        return $this->http->request('PATCH', "/api/v1/projects/{$projectId}/destinations/{$destId}", ['json' => $config]);
    }

    public function delete(string $projectId, string $destId): void
    {
        $this->http->request('DELETE', "/api/v1/projects/{$projectId}/destinations/{$destId}");
    }

    public function test(string $projectId, string $destId): array
    {
        return $this->http->request('POST', "/api/v1/projects/{$projectId}/destinations/{$destId}/test");
    }
}
