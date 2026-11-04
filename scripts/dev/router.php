<?php
declare(strict_types=1);

$path = rawurldecode((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'));
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: no-store');
if ($path === '/_dev/ai') {
    require __DIR__ . '/ai-settings.php';
    return true;
}
if (preg_match('~(?:^|/)(?:\.|install\.php|_setup-check\.php)|\\\\|\.(?:pem|key|sql|bak|dump|ini|env|log)(?:$|/)~i', $path)) {
    http_response_code(404);
    echo 'Not found';
    return true;
}
return require dirname(__DIR__, 2) . '/public/router.php';
