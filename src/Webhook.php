<?php

declare(strict_types=1);

namespace Apertur\Sdk;

class Webhook
{
    /**
     * Verify an image delivery webhook signature.
     * Header: X-Apertur-Signature: sha256=<hex>
     */
    public static function verifySignature(string $body, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $body, $secret);
        $sig = str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature;
        return hash_equals($expected, $sig);
    }

    /**
     * Verify an event webhook signature (HMAC SHA256 method).
     * Headers: X-Apertur-Signature + X-Apertur-Timestamp
     */
    public static function verifyEventSignature(string $body, string $timestamp, string $signature, string $secret): bool
    {
        $signatureBase = "{$timestamp}.{$body}";
        $expected = hash_hmac('sha256', $signatureBase, $secret);
        $sig = str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature;
        return hash_equals($expected, $sig);
    }

    /**
     * Verify an event webhook signature (Svix method).
     * Headers: svix-id, svix-timestamp, svix-signature
     */
    public static function verifySvixSignature(string $body, string $svixId, string $timestamp, string $signature, string $secret): bool
    {
        $signatureBase = "{$svixId}.{$timestamp}.{$body}";
        $expected = base64_encode(hash_hmac('sha256', $signatureBase, hex2bin($secret), true));
        $sig = str_starts_with($signature, 'v1,') ? substr($signature, 3) : $signature;
        return hash_equals($expected, $sig);
    }
}
