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

        $wrappedKey = self::rsaOaepSha256Encrypt($aesKey, $publicKeyPem);

        return [
            'encryptedKey' => base64_encode($wrappedKey),
            'iv' => base64_encode($iv),
            'encryptedData' => base64_encode($encryptedWithTag),
            'algorithm' => 'RSA-OAEP+AES-256-GCM',
        ];
    }

    /**
     * RSA-OAEP encryption with a SHA-256 hash.
     *
     * PHP's openssl_public_encrypt() with OPENSSL_PKCS1_OAEP_PADDING hard-codes the OAEP
     * hash to SHA-1 and offers no way to change it, but the Apertur server decrypts with
     * OAEP-SHA256 (oaepHash: "sha256"). To stay interoperable we build the OAEP padding
     * (RFC 8017 / PKCS#1 v2.2, EME-OAEP with MGF1-SHA256) by hand and then perform a raw
     * (unpadded) RSA encryption.
     */
    private static function rsaOaepSha256Encrypt(string $message, string $publicKeyPem): string
    {
        $pubKey = openssl_pkey_get_public($publicKeyPem);
        if ($pubKey === false) {
            throw new \RuntimeException('Invalid public key PEM');
        }

        $details = openssl_pkey_get_details($pubKey);
        if ($details === false || !isset($details['rsa']['n'])) {
            throw new \RuntimeException('Could not read RSA public key modulus');
        }

        $k = strlen($details['rsa']['n']); // modulus length in bytes
        $hLen = 32; // SHA-256 digest length

        if (strlen($message) > $k - 2 * $hLen - 2) {
            throw new \RuntimeException('Message too long for RSA-OAEP encryption');
        }

        $lHash = hash('sha256', '', true);
        $ps = str_repeat("\x00", $k - strlen($message) - 2 * $hLen - 2);
        $db = $lHash . $ps . "\x01" . $message;

        $seed = random_bytes($hLen);
        $dbMask = self::mgf1($seed, $k - $hLen - 1);
        $maskedDb = $db ^ $dbMask;
        $seedMask = self::mgf1($maskedDb, $hLen);
        $maskedSeed = $seed ^ $seedMask;

        $em = "\x00" . $maskedSeed . $maskedDb;

        $wrappedKey = '';
        if (!openssl_public_encrypt($em, $wrappedKey, $pubKey, OPENSSL_NO_PADDING)) {
            throw new \RuntimeException('RSA-OAEP encryption failed');
        }

        return $wrappedKey;
    }

    /**
     * MGF1 mask generation function based on SHA-256 (RFC 8017, B.2.1).
     */
    private static function mgf1(string $seed, int $length): string
    {
        $output = '';
        $counter = 0;
        while (strlen($output) < $length) {
            $output .= hash('sha256', $seed . pack('N', $counter), true);
            $counter++;
        }

        return substr($output, 0, $length);
    }
}
