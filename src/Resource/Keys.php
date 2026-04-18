<?php

declare(strict_types=1);

namespace Apertur\Sdk\Resource;

use Apertur\Sdk\HttpClient;

class Keys
{
    public function __construct(private readonly HttpClient $http) {}

    public function list(string $projectId): array
    {
        return $this->http->request('GET', "/api/v1/projects/{$projectId}/keys");
    }

    public function create(string $projectId, array $options): array
    {
        return $this->http->request('POST', "/api/v1/projects/{$projectId}/keys", ['json' => $options]);
    }

    public function update(string $projectId, string $keyId, array $options): array
    {
        return $this->http->request('PATCH', "/api/v1/projects/{$projectId}/keys/{$keyId}", ['json' => $options]);
    }

    public function delete(string $projectId, string $keyId): void
    {
        $this->http->request('DELETE', "/api/v1/projects/{$projectId}/keys/{$keyId}");
    }

    public function setDestinations(string $keyId, array $destinationIds, bool $longPolling = false): array
    {
        $body = ['destination_ids' => $destinationIds];
        if ($longPolling) {
            $body['long_polling_enabled'] = true;
        }
        return $this->http->request('PUT', "/api/v1/keys/{$keyId}/destinations", ['json' => $body]);
    }
}
