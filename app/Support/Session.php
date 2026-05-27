<?php
declare(strict_types=1);

namespace App\Support;

use App\Core\Container;

final class Session
{
    public static function ensureStarted(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $config = Container::get('config');
        $sessionName = $config['app']['session_name'] ?? 'bisped_session';

        session_name($sessionName);
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_secure' => $isHttps,
        ]);
    }
}
