<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;

/**
 * Encrypts/decrypts a media path (relative to public/) into a URL-safe, expiring token.
 * The real file path never appears in the page — only the opaque token.
 */
class SecureMediaHelper
{
    /**
     * Build an expiring token for a public-relative path (e.g. "upload/Festival Frames/x.webp").
     */
    public static function encrypt(string $relativePath, int $ttlSeconds = 30): string
    {
        $payload = json_encode([
            'p' => $relativePath,
            'e' => time() + max(1, $ttlSeconds),
        ]);

        return Crypt::encryptString($payload);
    }

    /**
     * Return the public-relative path for a token, or null if expired / tampered / invalid.
     */
    public static function decrypt(string $token): ?string
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (\Throwable $e) {
            return null;
        }

        if (!is_array($payload) || !isset($payload['p'], $payload['e'])) {
            return null;
        }

        if (time() > (int) $payload['e']) {
            return null; // expired
        }

        return (string) $payload['p'];
    }
}
