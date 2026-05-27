<?php
declare(strict_types=1);

namespace App\Services\Security;

use App\Support\Session;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';
    private const MAX_TOKENS = 20;

    public static function token(): string
    {
        Session::ensureStarted();

        $token = bin2hex(random_bytes(32));
        $tokens = $_SESSION[self::SESSION_KEY] ?? [];
        if (!is_array($tokens)) {
            $tokens = $tokens ? [(string)$tokens] : [];
        }

        $tokens[] = $token;
        $_SESSION[self::SESSION_KEY] = array_slice(array_values(array_unique($tokens)), -self::MAX_TOKENS);

        return $token;
    }

    public static function verify(?string $token): bool
    {
        Session::ensureStarted();

        $stored = $_SESSION[self::SESSION_KEY] ?? [];
        $tokens = is_array($stored) ? $stored : ($stored ? [(string)$stored] : []);

        if (!$tokens || !$token) {
            return false;
        }

        foreach ($tokens as $index => $storedToken) {
            if (is_string($storedToken) && hash_equals($storedToken, $token)) {
                unset($tokens[$index]);
                $_SESSION[self::SESSION_KEY] = array_values($tokens);
                return true;
            }
        }

        return false;
    }
}
