<?php

declare(strict_types=1);

namespace Apertur\Sdk;

use Apertur\Sdk\Resource\Sessions;
use Apertur\Sdk\Resource\Upload;
use Apertur\Sdk\Resource\Polling;
use Apertur\Sdk\Resource\Destinations;
use Apertur\Sdk\Resource\Keys;
use Apertur\Sdk\Resource\Webhooks;
use Apertur\Sdk\Resource\Encryption;
use Apertur\Sdk\Resource\Uploads;
use Apertur\Sdk\Resource\Stats;

class Apertur
{
    private readonly HttpClient $http;
    /** The environment this client targets. */
    public readonly string $env;

    public readonly Sessions $sessions;
    public readonly Upload $upload;
    public readonly Polling $polling;
    public readonly Destinations $destinations;
    public readonly Keys $keys;
    public readonly Webhooks $webhooks;
    public readonly Encryption $encryption;
    public readonly Uploads $uploads;
    public readonly Stats $stats;

    /**
     * @param array{apiKey?: string, oauthToken?: string, baseUrl?: string, env?: string} $config
     */
    public function __construct(array $config)
    {
        if (empty($config['apiKey']) && empty($config['oauthToken'])) {
            throw new \InvalidArgumentException('Either apiKey or oauthToken must be provided');
        }

        // Resolve env from key prefix or explicit config
        $token = $config['apiKey'] ?? $config['oauthToken'] ?? '';
        $detectedEnv = str_starts_with($token, 'aptr_test_') ? 'test' : 'live';
        $this->env = $config['env'] ?? $detectedEnv;

        // Auto-select sandbox URL for test keys unless baseUrl is explicitly set
        $defaultUrl = $this->env === 'test'
            ? 'https://sandbox.api.aptr.ca'
            : 'https://api.aptr.ca';
        $baseUrl = $config['baseUrl'] ?? $defaultUrl;

        $this->http = new HttpClient(
            $baseUrl,
            $config['apiKey'] ?? null,
            $config['oauthToken'] ?? null,
        );

        $this->sessions = new Sessions($this->http);
        $this->upload = new Upload($this->http);
        $this->polling = new Polling($this->http);
        $this->destinations = new Destinations($this->http);
        $this->keys = new Keys($this->http);
        $this->webhooks = new Webhooks($this->http);
        $this->encryption = new Encryption($this->http);
        $this->uploads = new Uploads($this->http);
        $this->stats = new Stats($this->http);
    }
}
