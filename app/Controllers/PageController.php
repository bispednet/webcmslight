<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\Cms\ContentRepository;
use App\Services\Analytics\ProductMetricsService;
use App\Services\Catalog\PcCompatibilityService;
use App\Services\Security\Csrf;
use App\Support\Flash;
use App\Support\Session;

final class PageController extends Controller
{
    private ContentRepository $content;

    public function __construct()
    {
        $this->content = new ContentRepository();
    }

    public function home(): void
    {
        $settings = $this->content->getSettings();
        $products = $this->content->getProducts();

        $this->view('public/home', compact('settings', 'products'));
    }

    public function products(): void
    {
        $counts = $this->content->productCategoryCounts();
        $subcats = $this->content->productSubcategories();
        $this->view('public/products', compact('counts', 'subcats'));
    }

    /**
     * Endpoint AJAX: ritorna un blocco di card prodotto (HTML) per il lazy load.
     * GET /products/load?cat=&sub=&q=&page=
     */
    public function loadProducts(): void
    {
        $cat    = trim((string)($_GET['cat'] ?? 'all'));
        $sub    = trim((string)($_GET['sub'] ?? 'all'));
        $q      = trim((string)($_GET['q'] ?? ''));
        $sort   = trim((string)($_GET['sort'] ?? 'featured'));
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 30;
        $offset = ($page - 1) * $limit;

        $result = $this->content->searchProducts($cat, $sub, $q, $limit, $offset, $sort);

        header('Content-Type: text/html; charset=utf-8');
        ob_start();
        foreach ($result['items'] as $product) {
            echo '<a href="/products/' . htmlspecialchars((string)$product['slug'], ENT_QUOTES, 'UTF-8') . '"'
                . ' class="product-item" style="text-decoration:none">';
            \App\Core\View::renderPartial('public/partials/product-card', ['product' => $product]);
            echo '</a>';
        }
        $html = ob_get_clean();

        $loaded = $offset + count($result['items']);
        echo json_encode([
            'html'    => $html,
            'total'   => $result['total'],
            'hasMore' => $loaded < $result['total'],
            'loaded'  => $loaded,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function azienda(): void
    {
        $settings = $this->content->getSettings();
        $this->view('public/azienda', compact('settings'));
    }

    public function dove(): void
    {
        $settings = $this->content->getSettings();
        $this->view('public/dove', compact('settings'));
    }

    public function servizi(): void
    {
        $settings = $this->content->getSettings();
        $products = $this->content->getServiceShowcaseProducts(8);
        $this->view('public/servizi', compact('settings', 'products'));
    }

    public function sostenibilita(): void
    {
        $settings = $this->content->getSettings();
        $this->view('public/sostenibilita', compact('settings'));
    }

    public function product(string $slug): void
    {
        $product = $this->content->getProductBySlug($slug);

        if (!$product) {
            http_response_code(404);
            $this->view('public/not-found');
            return;
        }

        (new ProductMetricsService(\App\Core\Database::connection()))->recordView((int)$product['id']);

        $pcConfigurator = null;
        try {
            $pcConfigurator = (new PcCompatibilityService(\App\Core\Database::connection()))
                ->configuratorForProduct((int)$product['id']);
        } catch (\Throwable) {
            $pcConfigurator = null;
        }

        $this->view('public/product', compact('product', 'pcConfigurator'));
    }

    public function brandLanding(string $slug): void
    {
        $legacyProduct = $this->content->findLegacyShopProduct($slug);
        if ($legacyProduct !== null) {
            http_response_code(301);
            header('Location: /products/' . rawurlencode((string)$legacyProduct['slug']));
            exit;
        }

        $landing = $this->content->getBrandLanding($slug);
        if ($landing === null) {
            http_response_code(301);
            header('Location: /negozio');
            exit;
        }

        $this->view('public/brand-landing', compact('landing'));
    }

    public function productConfiguratorOptions(string $slug): void
    {
        $product = $this->content->getProductBySlug($slug);
        if (!$product) {
            Response::json(['ok' => false, 'error' => 'not_found'], 404);
            return;
        }

        try {
            $service = new PcCompatibilityService(\App\Core\Database::connection());
            $configurator = $service->configuratorForProduct((int)$product['id']);
            if ($configurator === null) {
                Response::json(['ok' => false, 'error' => 'not_configurable'], 404);
                return;
            }

            $selected = (array)($configurator['selected'] ?? []);
            foreach (PcCompatibilityService::SLOT_LABELS as $slot => $_label) {
                if (!array_key_exists($slot, $_GET)) {
                    continue;
                }
                $value = (int)($_GET[$slot] ?? 0);
                if ($value > 0) {
                    $selected[$slot] = $value;
                } else {
                    unset($selected[$slot]);
                }
            }

            Response::json(['ok' => true] + $service->selectionSummary($selected));
        } catch (\Throwable) {
            Response::json(['ok' => false, 'error' => 'configurator_unavailable'], 503);
        }
    }

    public function blog(): void
    {
        $posts = $this->content->getBlogPosts();
        $this->view('public/blog', compact('posts'));
    }

    public function blogPost(string $slug): void
    {
        $post = $this->content->getBlogPostBySlug($slug);

        if (!$post) {
            http_response_code(404);
            $this->view('public/not-found');
            return;
        }

        $this->view('public/blog-post', compact('post'));
    }

    public function agents(): void
    {
        $agents = $this->content->getAgents();
        $this->view('public/agents', compact('agents'));
    }

    public function team(): void
    {
        $team = $this->content->getTeamMembers();
        $this->view('public/team', compact('team'));
    }

    public function partners(): void
    {
        $partners = $this->content->getPartners();
        $this->view('public/partners', compact('partners'));
    }

    public function clients(): void
    {
        $caseStudies = $this->content->getCaseStudies();
        $this->view('public/clients', compact('caseStudies'));
    }

    public function commands(): void
    {
        $commands = $this->content->getCommands();
        $this->view('public/commands', compact('commands'));
    }

    public function socialProof(): void
    {
        $items = $this->content->getSocialProofItems();
        $this->view('public/social-proof', compact('items'));
    }

    public function roadmap(): void
    {
        $phases = $this->content->getRoadmapPhases();
        $tracks = $this->content->getAlwaysOnTracks();
        $settings = $this->content->getSettings();

        $this->view('public/roadmap', [
            'phases' => $phases,
            'tracks' => $tracks,
            'vision' => $settings['roadmap_vision'] ?? '',
        ]);
    }

    public function tokenomics(): void
    {
        $this->view('public/tokenomics');
    }

    public function transparency(): void
    {
        $settings = $this->content->getSettings();
        $wallets = $this->content->getTransparencyWallets();
        $reports = $this->content->getTransparencyReports();

        $this->view('public/transparency', [
            'settings' => $settings,
            'wallets' => $wallets,
            'reports' => $reports,
        ]);
    }

    public function apiPlugins(): void
    {
        $this->view('public/api-plugins');
    }

    public function press(): void
    {
        $assets = $this->content->getPressAssets();
        $this->view('public/press', compact('assets'));
    }

    public function legal(): void
    {
        $settings = $this->content->getSettings();
        $sections = $this->content->getLegalSections();

        $this->view('public/legal', [
            'settings' => $settings,
            'sections' => $sections,
        ]);
    }

    public function withdrawal(): void
    {
        $settings = $this->content->getSettings();
        $csrfToken = Csrf::token();
        $success = Flash::pull('withdrawal_success');
        $error = Flash::pull('withdrawal_error');

        $this->view('public/recesso', compact('settings', 'csrfToken', 'success', 'error'));
    }

    public function faq(): void
    {
        $faqs = $this->content->getFaqItems();
        $this->view('public/faq', compact('faqs'));
    }

    public function contact(): void
    {
        $settings = $this->content->getSettings();
        $csrfToken = Csrf::token();
        $success = Flash::pull('contact_success');
        $error = Flash::pull('contact_error');

        $this->view('public/contact', compact('settings', 'csrfToken', 'success', 'error'));
    }

    public function customerArea(): void
    {
        Session::ensureStarted();

        if (empty($_SESSION['user_email'])) {
            Flash::set('auth_notice', 'Accedi per consultare la tua area riservata.');
            $this->redirect('/login');
        }

        $this->view('public/customer-area', [
            'name' => (string)($_SESSION['user_name'] ?? 'Cliente Bisped'),
            'email' => (string)($_SESSION['user_email'] ?? ''),
            'role' => (string)($_SESSION['user_role'] ?? 'cliente'),
        ]);
    }

    public function teleassistenza(): void
    {
        $settings = $this->content->getSettings();
        $this->view('public/teleassistenza', compact('settings'));
    }

    public function sitemap(): void
    {
        $pdo = \App\Core\Database::connection();

        $products = $pdo->query("SELECT slug, updated_at FROM products ORDER BY featured_order ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $posts    = $pdo->query("SELECT slug, updated_at FROM blog_posts WHERE is_published = 1 ORDER BY published_at DESC")->fetchAll(\PDO::FETCH_ASSOC);
        $brandLandings = $this->content->getBrandLandingIndex();

        $baseUrl = 'https://bisped.net';

        header('Content-Type: application/xml; charset=utf-8');
        header('X-Robots-Tag: noindex');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $staticPages = [
            ['loc' => '/',              'priority' => '1.0',  'freq' => 'weekly'],
            ['loc' => '/products',      'priority' => '0.9',  'freq' => 'weekly'],
            ['loc' => '/negozio',       'priority' => '0.9',  'freq' => 'weekly'],
            ['loc' => '/blog',          'priority' => '0.8',  'freq' => 'weekly'],
            ['loc' => '/servizi',       'priority' => '0.8',  'freq' => 'monthly'],
            ['loc' => '/teleassistenza','priority' => '0.8',  'freq' => 'monthly'],
            ['loc' => '/azienda',       'priority' => '0.6',  'freq' => 'monthly'],
            ['loc' => '/dove',          'priority' => '0.7',  'freq' => 'monthly'],
            ['loc' => '/contatti',      'priority' => '0.7',  'freq' => 'monthly'],
            ['loc' => '/faq',           'priority' => '0.6',  'freq' => 'monthly'],
            ['loc' => '/legal',         'priority' => '0.3',  'freq' => 'yearly'],
            ['loc' => '/recesso',       'priority' => '0.3',  'freq' => 'yearly'],
        ];

        foreach ($staticPages as $p) {
            echo "  <url>\n";
            echo "    <loc>{$baseUrl}" . htmlspecialchars($p['loc'], ENT_XML1) . "</loc>\n";
            echo "    <changefreq>{$p['freq']}</changefreq>\n";
            echo "    <priority>{$p['priority']}</priority>\n";
            echo "  </url>\n";
        }

        foreach ($products as $p) {
            $loc     = $baseUrl . '/products/' . htmlspecialchars($p['slug'], ENT_XML1);
            $lastmod = substr((string)($p['updated_at'] ?? date('Y-m-d')), 0, 10);
            echo "  <url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><priority>0.7</priority></url>\n";
        }

        foreach ($brandLandings as $landing) {
            $loc = $baseUrl . '/negozio/' . htmlspecialchars((string)$landing['slug'], ENT_XML1);
            echo "  <url><loc>{$loc}</loc><changefreq>weekly</changefreq><priority>0.72</priority></url>\n";
        }

        foreach ($posts as $p) {
            $loc     = $baseUrl . '/blog/' . htmlspecialchars($p['slug'], ENT_XML1);
            $lastmod = substr((string)($p['updated_at'] ?? date('Y-m-d')), 0, 10);
            echo "  <url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><priority>0.65</priority></url>\n";
        }

        echo '</urlset>';
        exit;
    }
}
