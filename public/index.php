<?php
declare(strict_types=1);

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (PHP_SAPI !== 'cli') {
    header_remove('X-Powered-By');
    $pathLocale = (($_GET['lang'] ?? '') === 'it')
        ? 'it'
        : (str_starts_with($requestPath, '/en') ? 'en' : (str_starts_with($requestPath, '/it') ? 'it' : null));
    if ($pathLocale !== null) {
        setcookie('bisped_locale', $pathLocale, [
            'expires' => time() + 31536000,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }
    // Nota: il sito parte sempre in italiano (target Piombino). L'inglese si
    // raggiunge solo via /en esplicito o dal toggle lingua, mai per auto-redirect.
}
if ($requestPath === '/health/db') {
    require __DIR__ . '/health-db.php';
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

if (str_starts_with($requestPath, '/admin')) {
    require __DIR__ . '/admin.php';
    exit;
}

// ── Agent API — CORS preflight ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS' && str_starts_with($requestPath, '/api/agent/')) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    http_response_code(204);
    exit;
}

use App\Core\Router;
use App\Controllers\PageController;
use App\Controllers\AuthController;
use App\Controllers\ContactController;
use App\Controllers\AppointmentController;
use App\Controllers\AiConciergeController;
use App\Controllers\Api\AgentApiController;

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
$router->get('/en/find-us', [PageController::class, 'dove']);
$router->get('/dove/', [PageController::class, 'dove']);
$router->get('/servizi', [PageController::class, 'servizi']);
$router->get('/it/servizi', [PageController::class, 'servizi']);
$router->get('/en/services', [PageController::class, 'servizi']);
$router->get('/servizi/', [PageController::class, 'servizi']);
$router->get('/sostenibilita', [PageController::class, 'sostenibilita']);
$router->get('/en/sustainability', [PageController::class, 'sostenibilita']);
$router->get('/sostenibilita/', [PageController::class, 'sostenibilita']);
$router->get('/contatti', [PageController::class, 'contact']);
$router->get('/en/contact', [PageController::class, 'contact']);
$router->get('/contatti/', [PageController::class, 'contact']);
$router->post('/contatti', [ContactController::class, 'submit']);
$router->post('/contatti/', [ContactController::class, 'submit']);
$router->post('/en/contact', [ContactController::class, 'submit']);
$router->get('/appuntamenti', [AppointmentController::class, 'show']);
$router->get('/appointments', [AppointmentController::class, 'show']);
$router->get('/en/appointments', [AppointmentController::class, 'show']);
$router->post('/appuntamenti', [AppointmentController::class, 'submit']);
$router->post('/appointments', [AppointmentController::class, 'submit']);
$router->post('/en/appointments', [AppointmentController::class, 'submit']);
$router->get('/ai/concierge/bootstrap', [AiConciergeController::class, 'bootstrap']);
$router->post('/ai/concierge/message', [AiConciergeController::class, 'message']);
$router->post('/ai/concierge/choice', [AiConciergeController::class, 'choice']);
$router->post('/ai/concierge/handoff/whatsapp', [AiConciergeController::class, 'whatsappHandoff']);
$router->get('/products', [PageController::class, 'products']);
$router->get('/products/load', [PageController::class, 'loadProducts']);
$router->get('/it/prodotti', [PageController::class, 'products']);
$router->get('/en/products', [PageController::class, 'products']);
$router->get('/products/{slug}/configurator-options', [PageController::class, 'productConfiguratorOptions']);
$router->get('/products/{slug}', [PageController::class, 'product']);
$router->get('/it/prodotti/{slug}', [PageController::class, 'product']);
$router->get('/en/products/{slug}', [PageController::class, 'product']);
$router->get('/legal', [PageController::class, 'legal']);
$router->get('/recesso', [PageController::class, 'withdrawal']);
$router->post('/recesso', [ContactController::class, 'withdrawal']);
$router->get('/faq', [PageController::class, 'faq']);
$router->get('/en/legal', [PageController::class, 'legal']);
$router->get('/en/faq', [PageController::class, 'faq']);
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
$router->get('/en/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'passwordLogin']);
$router->post('/en/login', [AuthController::class, 'passwordLogin']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->get('/en/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/en/register', [AuthController::class, 'register']);
$router->get('/area-clienti', [PageController::class, 'customerArea']);
$router->get('/it/area-clienti', [PageController::class, 'customerArea']);
$router->get('/en/customer-area', [PageController::class, 'customerArea']);

$router->get('/teleassistenza', [PageController::class, 'teleassistenza']);
$router->get('/teleassistenza/', [PageController::class, 'teleassistenza']);
$router->get('/it/teleassistenza', [PageController::class, 'teleassistenza']);
$router->get('/en/remote-support', [PageController::class, 'teleassistenza']);

$router->get('/sitemap.xml', [PageController::class, 'sitemap']);
$router->get('/sitemap', [PageController::class, 'sitemap']);

// ── Agent API v1 ────────────────────────────────────────────────────────────
$router->get('/api/agent/v1/ping', [AgentApiController::class, 'ping']);
$router->get('/api/agent/v1/stats', [AgentApiController::class, 'stats']);

$router->get('/api/agent/v1/products', [AgentApiController::class, 'listProducts']);
$router->get('/api/agent/v1/products/{id}', [AgentApiController::class, 'getProduct']);
$router->post('/api/agent/v1/products', [AgentApiController::class, 'createProduct']);
$router->match(['PUT', 'POST'], '/api/agent/v1/products/{id}', [AgentApiController::class, 'updateProduct']);
$router->match(['DELETE', 'POST'], '/api/agent/v1/products/{id}/delete', [AgentApiController::class, 'deleteProduct']);

$router->get('/api/agent/v1/blog', [AgentApiController::class, 'listBlog']);
$router->get('/api/agent/v1/blog/{id}', [AgentApiController::class, 'getPost']);
$router->post('/api/agent/v1/blog', [AgentApiController::class, 'createPost']);
$router->match(['PUT', 'POST'], '/api/agent/v1/blog/{id}', [AgentApiController::class, 'updatePost']);

$router->get('/api/agent/v1/leads', [AgentApiController::class, 'listLeads']);
$router->get('/api/agent/v1/leads/{id}', [AgentApiController::class, 'getLead']);

$router->get('/api/agent/v1/messages', [AgentApiController::class, 'listMessages']);
$router->get('/api/agent/v1/appointments', [AgentApiController::class, 'listAppointments']);

$router->post('/api/agent/v1/media/fetch', [AgentApiController::class, 'fetchMedia']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
