<?php

declare(strict_types=1);

namespace Apertur\Sdk;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Apertur\Sdk\Exception\AperturException;
use Apertur\Sdk\Exception\AuthenticationException;
use Apertur\Sdk\Exception\NotFoundException;
use Apertur\Sdk\Exception\RateLimitException;
use Apertur\Sdk\Exception\ValidationException;

class HttpClient
{
    private readonly Client $client;
    private readonly string $authHeader;

    public function __construct(string $baseUrl, ?string $apiKey = null, ?string $oauthToken = null)
    {
        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/'),
            'http_errors' => false,
        ]);

        if ($apiKey) {
            $this->authHeader = "Bearer {$apiKey}";
        } elseif ($oauthToken) {
            $this->authHeader = "Bearer {$oauthToken}";
        } else {
            $this->authHeader = '';
        }
    }

    public function request(string $method, string $path, array $options = []): array
    {
        $headers = $options['headers'] ?? [];
        if ($this->authHeader) {
            $headers['Authorization'] = $this->authHeader;
        }

        $reqOptions = ['headers' => $headers];

        if (isset($options['json'])) {
            $reqOptions['json'] = $options['json'];
        } elseif (isset($options['body'])) {
            $reqOptions['body'] = $options['body'];
        } elseif (isset($options['multipart'])) {
            $reqOptions['multipart'] = $options['multipart'];
        }

        if (isset($options['timeout'])) {
            $reqOptions[RequestOptions::TIMEOUT] = (float) $options['timeout'];
        }

        $response = $this->client->request($method, $path, $reqOptions);
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            $this->handleError($response);
        }

        if ($statusCode === 204) {
            return [];
        }

        $body = (string) $response->getBody();
        return json_decode($body, true) ?? [];
    }

    public function requestRaw(string $method, string $path, array $options = []): string
    {
        $headers = $options['headers'] ?? [];
        if ($this->authHeader) {
            $headers['Authorization'] = $this->authHeader;
        }

        $response = $this->client->request($method, $path, ['headers' => $headers]);
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            $this->handleError($response);
        }

        return (string) $response->getBody();
    }

    private function handleError(\Psr\Http\Message\ResponseInterface $response): never
    {
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true) ?? [];
        $message = $body['message'] ?? "HTTP {$statusCode}";
        $code = $body['code'] ?? null;

        match ($statusCode) {
            401 => throw new AuthenticationException($message),
            404 => throw new NotFoundException($message),
            429 => throw new RateLimitException(
                $message,
                ($retryAfter = $response->getHeaderLine('Retry-After')) ? (int) $retryAfter : null,
            ),
            400 => throw new ValidationException($message),
            default => throw new AperturException($statusCode, $message, $code),
        };
    }
}
