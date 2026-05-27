<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\Cms\ContentRepository;
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
        $products = $this->content->getProducts();
        $this->view('public/products', compact('products'));
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
        $products = $this->content->getProducts();
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

        $this->view('public/product', compact('product'));
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

        $baseUrl = 'https://bisped.net';

        header('Content-Type: application/xml; charset=utf-8');
        header('X-Robots-Tag: noindex');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $staticPages = [
            ['loc' => '/',              'priority' => '1.0',  'freq' => 'weekly'],
            ['loc' => '/products',      'priority' => '0.9',  'freq' => 'weekly'],
            ['loc' => '/blog',          'priority' => '0.8',  'freq' => 'weekly'],
            ['loc' => '/servizi',       'priority' => '0.8',  'freq' => 'monthly'],
            ['loc' => '/teleassistenza','priority' => '0.8',  'freq' => 'monthly'],
            ['loc' => '/azienda',       'priority' => '0.6',  'freq' => 'monthly'],
            ['loc' => '/dove',          'priority' => '0.7',  'freq' => 'monthly'],
            ['loc' => '/contatti',      'priority' => '0.7',  'freq' => 'monthly'],
            ['loc' => '/faq',           'priority' => '0.6',  'freq' => 'monthly'],
            ['loc' => '/legal',         'priority' => '0.3',  'freq' => 'yearly'],
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

        foreach ($posts as $p) {
            $loc     = $baseUrl . '/blog/' . htmlspecialchars($p['slug'], ENT_XML1);
            $lastmod = substr((string)($p['updated_at'] ?? date('Y-m-d')), 0, 10);
            echo "  <url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><priority>0.65</priority></url>\n";
        }

        echo '</urlset>';
        exit;
    }
}
