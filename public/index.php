<?php
declare(strict_types=1);

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($requestPath === '/health/db') {
    require __DIR__ . '/health-db.php';
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

if (str_starts_with($requestPath, '/admin')) {
    require __DIR__ . '/admin.php';
    exit;
}

use App\Core\Router;
use App\Controllers\PageController;
use App\Controllers\AuthController;
use App\Controllers\ContactController;

$router = new Router();

$router->get('/', [PageController::class, 'home']);
$router->get('/it', [PageController::class, 'home']);
$router->get('/it/', [PageController::class, 'home']);
$router->get('/en', [PageController::class, 'home']);
$router->get('/en/', [PageController::class, 'home']);
$router->get('/azienda', [PageController::class, 'azienda']);
$router->get('/it/azienda', [PageController::class, 'azienda']);
$router->get('/en/company', [PageController::class, 'azienda']);
$router->get('/azienda/', [PageController::class, 'azienda']);
$router->get('/chi-siamo', [PageController::class, 'azienda']);
$router->get('/dove', [PageController::class, 'dove']);
$router->get('/dove-siamo', [PageController::class, 'dove']);
$router->get('/dove/', [PageController::class, 'dove']);
$router->get('/servizi', [PageController::class, 'servizi']);
$router->get('/it/servizi', [PageController::class, 'servizi']);
$router->get('/en/services', [PageController::class, 'servizi']);
$router->get('/servizi/', [PageController::class, 'servizi']);
$router->get('/sostenibilita', [PageController::class, 'sostenibilita']);
$router->get('/sostenibilita/', [PageController::class, 'sostenibilita']);
$router->get('/contatti', [PageController::class, 'contact']);
$router->get('/contatti/', [PageController::class, 'contact']);
$router->post('/contatti', [ContactController::class, 'submit']);
$router->post('/contatti/', [ContactController::class, 'submit']);
$router->get('/products', [PageController::class, 'products']);
$router->get('/it/prodotti', [PageController::class, 'products']);
$router->get('/en/products', [PageController::class, 'products']);
$router->get('/products/{slug}', [PageController::class, 'product']);
$router->get('/it/prodotti/{slug}', [PageController::class, 'product']);
$router->get('/en/products/{slug}', [PageController::class, 'product']);
$router->get('/legal', [PageController::class, 'legal']);
$router->get('/faq', [PageController::class, 'faq']);
$router->get('/contact', [PageController::class, 'contact']);
$router->post('/contact', [ContactController::class, 'submit']);
$router->get('/health/db', static function (): void {
    require __DIR__ . '/health-db.php';
});
$router->get('/blog', [PageController::class, 'blog']);
$router->get('/it/blog', [PageController::class, 'blog']);
$router->get('/en/blog', [PageController::class, 'blog']);
$router->get('/blog/{slug}', [PageController::class, 'blogPost']);
$router->get('/it/blog/{slug}', [PageController::class, 'blogPost']);
$router->get('/en/blog/{slug}', [PageController::class, 'blogPost']);
$router->get('/auth/nonce', [AuthController::class, 'issueNonce']);
$router->post('/auth/verify', [AuthController::class, 'verify']);
$router->get('/auth/google', [AuthController::class, 'googleRedirect']);
$router->get('/auth/google/callback', [AuthController::class, 'googleCallback']);
$router->get('/auth/wallet/nonce', [AuthController::class, 'issueWalletNonce']);
$router->post('/auth/wallet/verify', [AuthController::class, 'verifyWallet']);
$router->post('/auth/logout', [AuthController::class, 'logout']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'passwordLogin']);
$router->get('/area-clienti', [PageController::class, 'customerArea']);
$router->get('/it/area-clienti', [PageController::class, 'customerArea']);
$router->get('/en/customer-area', [PageController::class, 'customerArea']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
