<?php

declare(strict_types=1);

namespace Apertur\Sdk;

class Crypto
{
    /**
     * @return array{encryptedKey: string, iv: string, encryptedData: string, algorithm: string}
     */
    public static function encryptImage(string $imageData, string $publicKeyPem): array
    {
        $aesKey = random_bytes(32);
        $iv = random_bytes(12);

        $tag = '';
        $encrypted = openssl_encrypt($imageData, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $iv, $tag);
        if ($encrypted === false) {
            throw new \RuntimeException('AES-256-GCM encryption failed');
        }
        $encryptedWithTag = $encrypted . $tag;

        $pubKey = openssl_pkey_get_public($publicKeyPem);
        if ($pubKey === false) {
            throw new \RuntimeException('Invalid public key PEM');
        }
        $wrappedKey = '';
        $success = openssl_public_encrypt($aesKey, $wrappedKey, $pubKey, OPENSSL_PKCS1_OAEP_PADDING);
        if (!$success) {
            throw new \RuntimeException('RSA-OAEP encryption failed');
        }

        return [
            'encryptedKey' => base64_encode($wrappedKey),
            'iv' => base64_encode($iv),
            'encryptedData' => base64_encode($encryptedWithTag),
            'algorithm' => 'RSA-OAEP+AES-256-GCM',
        ];
    }
}
