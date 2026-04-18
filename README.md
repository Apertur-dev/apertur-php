# apertur/sdk

Official PHP SDK for the [Apertur](https://apertur.ca) API. Supports API key and OAuth token authentication, session management, image uploads (plain and encrypted), long polling, webhook verification, and full resource CRUD.

## Installation

Requires PHP 8.1+ and is installed via Composer.

```php
composer require apertur/sdk
```

## Quick Start

Create a client, open an upload session, and upload an image in a few lines. See the [API documentation](https://docs.apertur.ca) for a full overview.

```php
use Apertur\Sdk\Apertur;

$client = new Apertur(['apiKey' => 'aptr_live_...']);

$session = $client->sessions->create(['label' => 'My shoot']);
$image   = $client->upload->image($session['uuid'], '/path/to/photo.jpg');

echo $image['id'];
```

## Authentication

The client accepts either a long-lived API key or a short-lived OAuth bearer token. Only one is required; providing both will result in the API key being used. See [Authentication documentation](https://docs.apertur.ca/authentication).

```php
use Apertur\Sdk\Apertur;

// API key
$client = new Apertur(['apiKey' => 'aptr_live_...']);

// OAuth token (e.g. obtained via your auth server)
$client = new Apertur(['oauthToken' => $accessToken]);

// Custom base URL (sandbox)
$client = new Apertur([
    'apiKey'  => 'aptr_live_...',
    'baseUrl' => 'https://sandbox.api.aptr.ca',
]);
```

## Sessions

Upload sessions scope every image upload. You can create a session with optional settings, retrieve it, protect it with a password, and check delivery status. See [Sessions documentation](https://docs.apertur.ca/upload-sessions).

```php
use Apertur\Sdk\Apertur;

$client = new Apertur(['apiKey' => 'aptr_live_...']);

// Create a session
$session = $client->sessions->create([
    'label'    => 'Wedding reception',
    'password' => 's3cr3t',
    'maxImages' => 200,
]);

// Retrieve session details
$details = $client->sessions->get($session['uuid']);

// Verify a password-protected session before uploading
$result = $client->sessions->verifyPassword($session['uuid'], 's3cr3t');

// Check delivery status — snapshot
$status  = $client->sessions->deliveryStatus($session['uuid']);
$overall = $status['status'];       // pending|active|completed|expired
$files   = $status['files'];
$changed = $status['lastChanged'];  // ISO 8601

// Long-poll for the next change (server holds up to 5 min; use 6 min client timeout)
$next = $client->sessions->deliveryStatus($session['uuid'], $changed, 360.0);
```

## Uploading Images

Upload a plain image using a file path or a PHP stream resource. For end-to-end encrypted uploads, use `imageEncrypted` with the server's RSA public key. See [Upload documentation](https://docs.apertur.ca/upload-sessions).

```php
use Apertur\Sdk\Apertur;

$client = new Apertur(['apiKey' => 'aptr_live_...']);
$uuid   = 'session-uuid-here';

// Upload from a file path
$image = $client->upload->image($uuid, '/tmp/photo.jpg', [
    'filename' => 'photo.jpg',
    'mimeType' => 'image/jpeg',
    'source'   => 'my-app',
]);

// Upload from a stream resource
$stream = fopen('/tmp/photo.jpg', 'rb');
$image  = $client->upload->image($uuid, $stream);
fclose($stream);

// Upload to a password-protected session
$image = $client->upload->image($uuid, '/tmp/photo.jpg', [
    'password' => 's3cr3t',
]);

// Encrypted upload (fetch the server key first — see Encryption section)
$serverKey = $client->encryption->getServerKey();
$image = $client->upload->imageEncrypted($uuid, '/tmp/photo.jpg', $serverKey['publicKey'], [
    'filename' => 'photo.jpg',
    'mimeType' => 'image/jpeg',
]);
```

## Long Polling

Poll a session for new images, download each one, and acknowledge receipt to advance the queue. The `pollAndProcess` helper loops automatically and calls your handler for every image. See [Long Polling documentation](https://docs.apertur.ca/long-polling).

```php
use Apertur\Sdk\Apertur;

$client = new Apertur(['apiKey' => 'aptr_live_...']);
$uuid   = 'session-uuid-here';

// Manual poll / download / ack cycle
$result = $client->polling->list($uuid);
foreach ($result['images'] as $image) {
    $data = $client->polling->download($uuid, $image['id']); // raw binary string
    file_put_contents("/tmp/{$image['id']}.jpg", $data);
    $client->polling->ack($uuid, $image['id']);
}

// Automatic loop with 60-second timeout and 3-second interval
$client->polling->pollAndProcess(
    $uuid,
    function (array $image, string $data): void {
        file_put_contents("/tmp/{$image['id']}.jpg", $data);
        echo "Saved {$image['id']}\n";
    },
    ['interval' => 3, 'timeout' => 60],
);
```

## Receiving Webhooks

Apertur signs every webhook payload so you can verify it was not tampered with. Three verification methods are available: `verifySignature` for image delivery webhooks, `verifyEventSignature` for HMAC-signed event webhooks, and `verifySvixSignature` for Svix-signed event webhooks. See [Webhooks documentation](https://docs.apertur.ca/webhooks).

```php
use Apertur\Sdk\Webhook;

// Laravel middleware example
class VerifyAperturWebhook
{
    public function handle(Request $request, Closure $next): mixed
    {
        $body      = $request->getContent();
        $signature = $request->header('X-Apertur-Signature');
        $secret    = config('services.apertur.webhook_secret');

        if (!Webhook::verifySignature($body, $signature, $secret)) {
            abort(401, 'Invalid webhook signature');
        }

        return $next($request);
    }
}

// Event webhook — HMAC method
$valid = Webhook::verifyEventSignature(
    body:      $body,
    timestamp: $request->header('X-Apertur-Timestamp'),
    signature: $request->header('X-Apertur-Signature'),
    secret:    $secret,
);

// Event webhook — Svix method
$valid = Webhook::verifySvixSignature(
    body:      $body,
    svixId:    $request->header('svix-id'),
    timestamp: $request->header('svix-timestamp'),
    signature: $request->header('svix-signature'),
    secret:    $secret,
);
```

## Destinations

Destinations define where uploaded images are delivered (S3, webhook, long-poll queue, etc.). You can list, create, update, delete, and trigger a test delivery for any destination. See [Destinations documentation](https://docs.apertur.ca/destinations).

```php
use Apertur\Sdk\Apertur;

$client    = new Apertur(['apiKey' => 'aptr_live_...']);
$projectId = 'proj_...';

// List all destinations for a project
$list = $client->destinations->list($projectId);

// Create a new destination
$dest = $client->destinations->create($projectId, [
    'type'   => 's3',
    'label'  => 'Primary S3 bucket',
    'config' => ['bucket' => 'my-bucket', 'region' => 'us-east-1'],
]);

// Update a destination
$updated = $client->destinations->update($projectId, $dest['id'], [
    'label' => 'Primary S3 bucket (updated)',
]);

// Trigger a test delivery
$testResult = $client->destinations->test($projectId, $dest['id']);

// Delete a destination
$client->destinations->delete($projectId, $dest['id']);
```

## API Keys

API keys are scoped to a project and optionally restricted to specific destinations. You can list, create, update, delete, and reassign destinations for any key. See [API Keys documentation](https://docs.apertur.ca/api-keys).

```php
use Apertur\Sdk\Apertur;

$client    = new Apertur(['apiKey' => 'aptr_live_...']);
$projectId = 'proj_...';

// List keys
$keys = $client->keys->list($projectId);

// Create a key
$key = $client->keys->create($projectId, [
    'label' => 'Mobile app key',
]);

// Update a key
$client->keys->update($projectId, $key['id'], ['label' => 'Mobile app key v2']);

// Assign destinations (and optionally enable long polling)
$client->keys->setDestinations($key['id'], ['dest_abc', 'dest_def'], longPolling: true);

// Delete a key
$client->keys->delete($projectId, $key['id']);
```

## Event Webhooks

Event webhooks push real-time notifications to your endpoint for events such as image uploads and session state changes. You can manage webhooks, inspect delivery history, and retry failed deliveries. See [Event Webhooks documentation](https://docs.apertur.ca/event-webhooks).

```php
use Apertur\Sdk\Apertur;

$client    = new Apertur(['apiKey' => 'aptr_live_...']);
$projectId = 'proj_...';

// List webhooks
$webhooks = $client->webhooks->list($projectId);

// Create a webhook
$webhook = $client->webhooks->create($projectId, [
    'url'    => 'https://example-website.com/webhooks/apertur',
    'events' => ['image.uploaded', 'session.completed'],
]);

// Update a webhook
$client->webhooks->update($projectId, $webhook['id'], [
    'events' => ['image.uploaded'],
]);

// Trigger a test delivery
$client->webhooks->test($projectId, $webhook['id']);

// List delivery attempts (paginated)
$deliveries = $client->webhooks->deliveries($projectId, $webhook['id'], [
    'page'  => 1,
    'limit' => 25,
]);

// Retry a failed delivery
$client->webhooks->retryDelivery($projectId, $webhook['id'], $deliveries['data'][0]['id']);

// Delete a webhook
$client->webhooks->delete($projectId, $webhook['id']);
```

## Encryption

Apertur supports end-to-end encrypted uploads using RSA-OAEP + AES-256-GCM. Fetch the server's RSA public key, then pass it to `upload->imageEncrypted`. The `Crypto` class handles key wrapping and encryption automatically. See [Encryption documentation](https://docs.apertur.ca/encryption).

```php
use Apertur\Sdk\Apertur;

$client = new Apertur(['apiKey' => 'aptr_live_...']);

// Step 1: retrieve the server's RSA public key
$serverKey = $client->encryption->getServerKey();
// $serverKey['publicKey'] is a PEM-encoded RSA public key

// Step 2: upload an encrypted image — the SDK handles AES-256-GCM + RSA-OAEP wrapping
$image = $client->upload->imageEncrypted(
    'session-uuid-here',
    '/tmp/photo.jpg',
    $serverKey['publicKey'],
    ['filename' => 'photo.jpg', 'mimeType' => 'image/jpeg'],
);

echo $image['id'];
```

## Error Handling

All API errors throw typed exceptions that extend `AperturException`. Catch the specific subclass you care about, or catch `AperturException` as a fallback for any API error. See [Error Handling documentation](https://docs.apertur.ca/errors).

```php
use Apertur\Sdk\Apertur;
use Apertur\Sdk\Exception\AperturException;
use Apertur\Sdk\Exception\AuthenticationException;
use Apertur\Sdk\Exception\NotFoundException;
use Apertur\Sdk\Exception\RateLimitException;
use Apertur\Sdk\Exception\ValidationException;

$client = new Apertur(['apiKey' => 'aptr_live_...']);

try {
    $session = $client->sessions->create(['label' => 'My shoot']);
    $image   = $client->upload->image($session['uuid'], '/tmp/photo.jpg');
} catch (AuthenticationException $e) {
    // 401 — invalid or missing API key / token
    echo "Auth failed: {$e->getMessage()}\n";
} catch (NotFoundException $e) {
    // 404 — session or resource not found
    echo "Not found: {$e->getMessage()}\n";
} catch (RateLimitException $e) {
    // 429 — back off and retry
    $retryAfter = $e->getRetryAfter(); // seconds, or null
    echo "Rate limited. Retry after {$retryAfter}s\n";
} catch (ValidationException $e) {
    // 400 — invalid request payload
    echo "Validation error: {$e->getMessage()}\n";
} catch (AperturException $e) {
    // Any other API error
    echo "API error {$e->getStatusCode()}: {$e->getMessage()} [{$e->getErrorCode()}]\n";
}
```

## API Reference

Full API reference, guides, and changelog are available at [docs.apertur.ca](https://docs.apertur.ca).

## License

This package is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
