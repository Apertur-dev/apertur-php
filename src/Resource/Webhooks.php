<?php

declare(strict_types=1);

namespace Apertur\Sdk\Resource;

use Apertur\Sdk\HttpClient;

class Webhooks
{
    public function __construct(private readonly HttpClient $http) {}

    public function list(string $projectId): array
    {
        return $this->http->request('GET', "/api/v1/projects/{$projectId}/webhooks");
    }

    public function create(string $projectId, array $config): array
    {
        return $this->http->request('POST', "/api/v1/projects/{$projectId}/webhooks", ['json' => $config]);
    }

    public function update(string $projectId, string $webhookId, array $config): array
    {
        return $this->http->request('PATCH', "/api/v1/projects/{$projectId}/webhooks/{$webhookId}", ['json' => $config]);
    }

    public function delete(string $projectId, string $webhookId): void
    {
        $this->http->request('DELETE', "/api/v1/projects/{$projectId}/webhooks/{$webhookId}");
    }

    public function test(string $projectId, string $webhookId): array
    {
        return $this->http->request('POST', "/api/v1/projects/{$projectId}/webhooks/{$webhookId}/test");
    }

    public function deliveries(string $projectId, string $webhookId, array $options = []): array
    {
        $query = http_build_query(array_filter([
            'page' => $options['page'] ?? null,
            'limit' => $options['limit'] ?? null,
        ], fn($v) => $v !== null));
        $path = "/api/v1/projects/{$projectId}/webhooks/{$webhookId}/deliveries";
        if ($query) {
            $path .= "?{$query}";
        }
        return $this->http->request('GET', $path);
    }

    public function retryDelivery(string $projectId, string $webhookId, string $deliveryId): array
    {
        return $this->http->request('POST', "/api/v1/projects/{$projectId}/webhooks/{$webhookId}/deliveries/{$deliveryId}/retry");
    }
}
