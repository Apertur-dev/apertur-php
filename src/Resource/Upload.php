<?php

declare(strict_types=1);

namespace Apertur\Sdk\Resource;

use Apertur\Sdk\HttpClient;
use Apertur\Sdk\Crypto;

class Upload
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * @param string|resource $file File path or stream resource
     */
    public function image(string $uuid, mixed $file, array $options = []): array
    {
        $contents = $this->readFile($file);
        $filename = $options['filename'] ?? 'image.jpg';
        $mimeType = $options['mimeType'] ?? 'image/jpeg';

        $headers = [];
        if (isset($options['password'])) {
            $headers['x-session-password'] = $options['password'];
        }

        $multipart = [
            ['name' => 'file', 'contents' => $contents, 'filename' => $filename, 'headers' => ['Content-Type' => $mimeType]],
        ];
        if (isset($options['source'])) {
            $multipart[] = ['name' => 'source', 'contents' => $options['source']];
        }

        return $this->http->request('POST', "/api/v1/upload/{$uuid}/images", [
            'multipart' => $multipart,
            'headers' => $headers,
        ]);
    }

    /**
     * @param string|resource $file File path or stream resource
     */
    public function imageEncrypted(string $uuid, mixed $file, string $publicKey, array $options = []): array
    {
        $contents = $this->readFile($file);
        $filename = $options['filename'] ?? 'image.jpg';
        $mimeType = $options['mimeType'] ?? 'image/jpeg';

        $encrypted = Crypto::encryptImage($contents, $publicKey);

        $payload = array_merge($encrypted, [
            'filename' => $filename,
            'mimeType' => $mimeType,
            'source' => $options['source'] ?? 'sdk',
        ]);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Aptr-Encrypted' => 'default',
        ];
        if (isset($options['password'])) {
            $headers['x-session-password'] = $options['password'];
        }

        return $this->http->request('POST', "/api/v1/upload/{$uuid}/images", [
            'body' => json_encode($payload),
            'headers' => $headers,
        ]);
    }

    private function readFile(mixed $file): string
    {
        if (is_string($file)) {
            if (!file_exists($file)) {
                throw new \RuntimeException("File not found: {$file}");
            }
            return file_get_contents($file);
        }
        if (is_resource($file)) {
            return stream_get_contents($file);
        }
        throw new \InvalidArgumentException('File must be a string path or stream resource');
    }
}
