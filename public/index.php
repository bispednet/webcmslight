<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
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
$router->get('/azienda', [PageController::class, 'azienda']);
$router->get('/azienda/', [PageController::class, 'azienda']);
$router->get('/servizi', [PageController::class, 'servizi']);
$router->get('/servizi/', [PageController::class, 'servizi']);
$router->get('/sostenibilita', [PageController::class, 'sostenibilita']);
$router->get('/sostenibilita/', [PageController::class, 'sostenibilita']);
$router->get('/contatti', [PageController::class, 'contact']);
$router->get('/contatti/', [PageController::class, 'contact']);
$router->post('/contatti', [ContactController::class, 'submit']);
$router->post('/contatti/', [ContactController::class, 'submit']);
$router->get('/products', [PageController::class, 'products']);
$router->get('/products/{slug}', [PageController::class, 'product']);
$router->get('/legal', [PageController::class, 'legal']);
$router->get('/faq', [PageController::class, 'faq']);
$router->get('/contact', [PageController::class, 'contact']);
$router->post('/contact', [ContactController::class, 'submit']);
$router->get('/blog', [PageController::class, 'blog']);
$router->get('/blog/{slug}', [PageController::class, 'blogPost']);
$router->get('/auth/nonce', [AuthController::class, 'issueNonce']);
$router->post('/auth/verify', [AuthController::class, 'verify']);
$router->post('/auth/logout', [AuthController::class, 'logout']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'passwordLogin']);
$router->get('/area-clienti', [PageController::class, 'customerArea']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
