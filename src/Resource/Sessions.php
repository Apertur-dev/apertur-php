<?php

declare(strict_types=1);

namespace Apertur\Sdk\Resource;

use Apertur\Sdk\HttpClient;

class Sessions
{
    public function __construct(private readonly HttpClient $http) {}

    public function create(array $options = []): array
    {
        return $this->http->request('POST', '/api/v1/upload-sessions', ['json' => $options]);
    }

    public function get(string $uuid): array
    {
        return $this->http->request('GET', "/api/v1/upload/{$uuid}/session");
    }

    /**
     * Update an upload session's settings.
     *
     * @param array{
     *   expires_at?: string,
     *   max_images?: int,
     *   allowed_mime_types?: array<string>,
     *   max_image_dimension?: int,
     *   max_image_size_mb?: int,
     *   password?: string|null,
     * } $options
     */
    public function update(string $uuid, array $options): array
    {
        return $this->http->request('PATCH', "/api/v1/upload-sessions/{$uuid}", ['json' => $options]);
    }

    /**
     * Paginated list of sessions the caller can see.
     *
     * @param array{page?: int, pageSize?: int} $params
     */
    public function list(array $params = []): array
    {
        $query = http_build_query($params);
        $suffix = $query === '' ? '' : "?{$query}";
        return $this->http->request('GET', "/api/v1/sessions{$suffix}");
    }

    /**
     * @param array{limit?: int} $params
     */
    public function recent(array $params = []): array
    {
        $query = http_build_query($params);
        $suffix = $query === '' ? '' : "?{$query}";
        return $this->http->request('GET', "/api/v1/sessions/recent{$suffix}");
    }

    public function verifyPassword(string $uuid, string $password): array
    {
        return $this->http->request('POST', "/api/v1/upload/{$uuid}/verify-password", [
            'json' => ['password' => $password],
        ]);
    }

    /**
     * Retrieve the delivery status for a session.
     *
     * The response is an array with keys `status` (one of `pending`, `active`, `completed`,
     * `expired`), `files` (per-file delivery records), and `lastChanged` (ISO 8601 timestamp).
     *
     * When `$pollFrom` is provided the server holds the response for up to 5 minutes and
     * returns as soon as `lastChanged` advances past the supplied timestamp. Callers that
     * long-poll should pass a `$timeout` of at least 6 minutes (in seconds, e.g. `360.0`) so
     * the server releases first under the happy path.
     *
     * @param string      $uuid     the session UUID
     * @param string|null $pollFrom optional ISO 8601 timestamp for long polling
     * @param float|null  $timeout  optional per-request timeout in seconds (Guzzle TIMEOUT)
     */
    public function deliveryStatus(string $uuid, ?string $pollFrom = null, ?float $timeout = null): array
    {
        $path = "/api/v1/upload-sessions/{$uuid}/delivery-status";
        if ($pollFrom !== null && $pollFrom !== '') {
            $path .= '?pollFrom=' . rawurlencode($pollFrom);
        }

        $options = [];
        if ($timeout !== null) {
            $options['timeout'] = $timeout;
        }

        return $this->http->request('GET', $path, $options);
    }

    /**
     * Generate a QR code for the session.
     *
     * @param array{format?: string, size?: int, style?: string, fg?: string, bg?: string, borderSize?: int, borderColor?: string} $options
     * @return string Raw image data (PNG/SVG/JPEG)
     */
    public function qr(string $uuid, array $options = []): string
    {
        $query = http_build_query($options);
        $suffix = $query === '' ? '' : "?{$query}";
        return $this->http->requestRaw('GET', "/api/v1/upload-sessions/{$uuid}/qr{$suffix}");
    }
}
