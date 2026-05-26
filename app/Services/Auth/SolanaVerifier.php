<?php
declare(strict_types=1);

namespace App\Services\Auth;

final class SolanaVerifier
{
    public function verifySignature(string $address, string $message, string $signature): bool
    {
        if (!extension_loaded('sodium')) {
            return false;
        }

        $publicKey = Base58::decode($address);
        if ($publicKey === null || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return false;
        }

        $signatureBytes = Base58::decode($signature);
        if ($signatureBytes === null && preg_match('/^[a-f0-9]{128}$/i', $signature)) {
            $signatureBytes = hex2bin($signature) ?: null;
        }

        if ($signatureBytes === null || strlen($signatureBytes) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($signatureBytes, $message, $publicKey);
    }
}
