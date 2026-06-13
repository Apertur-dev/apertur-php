<?php

declare(strict_types=1);

namespace Apertur\Sdk\Resource;

use Apertur\Sdk\HttpClient;

class Polling
{
    public function __construct(private readonly HttpClient $http) {}

    public function list(string $uuid): array
    {
        return $this->http->request('GET', "/api/v1/upload-sessions/{$uuid}/poll");
    }

    public function download(string $uuid, string $imageId): string
    {
        return $this->http->requestRaw('GET', "/api/v1/upload-sessions/{$uuid}/images/{$imageId}");
    }

    public function ack(string $uuid, string $imageId): array
    {
        return $this->http->request('POST', "/api/v1/upload-sessions/{$uuid}/images/{$imageId}/ack");
    }

    /**
     * @param callable(array $image, string $data): void $handler
     * @param array{interval?: int, timeout?: int} $options
     */
    public function pollAndProcess(string $uuid, callable $handler, array $options = []): void
    {
        $interval = $options['interval'] ?? 3;
        $timeout = $options['timeout'] ?? 0;
        $startTime = time();

        while (true) {
            if ($timeout > 0 && (time() - $startTime) >= $timeout) {
                return;
            }

            $result = $this->list($uuid);

            foreach ($result['images'] ?? [] as $image) {
                $data = $this->download($uuid, $image['id']);
                $handler($image, $data);
                $this->ack($uuid, $image['id']);
            }

            sleep($interval);
        }
    }
}
