<?php
declare(strict_types=1);

if (defined('APP_BOOTSTRAPPED')) {
    return;
}
define('APP_BOOTSTRAPPED', true);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/Autoloader.php';

\App\Core\Autoloader::register();

require_once BASE_PATH . '/app/Support/icons.php';

$configPath = BASE_PATH . '/.env.php';
if (!file_exists($configPath)) {
    throw new RuntimeException('Missing .env.php configuration file. Copy .env.example.php and update credentials.');
}

$config = require $configPath;

if ((bool)($config['app']['debug'] ?? false)) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

\App\Core\Container::set('config', $config);

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        $value = \App\Core\Container::get('config', []);
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

$GLOBALS['config'] = $config;

if (PHP_SAPI !== 'cli') {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

    header_remove('X-Powered-By');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $sessionName = $config['app']['session_name'] ?? 'bisped_session';
    session_name($sessionName);
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https',
    ]);
}

\App\Core\Logger::init(BASE_PATH . '/storage/logs/app.log', (bool)($config['app']['debug'] ?? false));

if (!defined('BISPED_SKIP_DB_BOOTSTRAP')) {
    try {
    $GLOBALS['pdo'] = \App\Core\Database::connection();
    } catch (\Throwable $e) {
        \App\Core\Logger::error('Database bootstrap failure', ['error' => $e->getMessage()]);
        http_response_code(503);
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, 'Database unavailable' . PHP_EOL);
            exit(1);
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Servizio momentaneamente non disponibile</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#050505;color:#fff;font-family:system-ui,sans-serif}.box{max-width:620px;padding:32px;border:1px solid rgba(255,255,255,.14);border-radius:24px;background:linear-gradient(135deg,rgba(229,9,20,.18),rgba(255,255,255,.05))}h1{margin:0 0 12px;font-size:32px}p{color:#c9c9c9;line-height:1.6}</style></head><body><main class="box"><h1>Servizio momentaneamente non disponibile</h1><p>Stiamo ripristinando la connessione ai servizi interni. Riprova tra poco o contatta Bisped se hai una richiesta urgente.</p></main></body></html>';
        exit;
    }
}
