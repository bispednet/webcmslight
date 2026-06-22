<?php
declare(strict_types=1);

namespace App\Services\Cms;

use App\Core\Database;
use App\Support\I18n;
use PDO;

final class ContentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function getSettings(): array
    {
        $stmt = $this->db->query('SELECT setting_key, setting_value FROM settings');
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    }

    public function getProducts(): array
    {
        $stmt = $this->db->query('SELECT * FROM products ORDER BY featured_order ASC, name ASC');
        $products = $stmt->fetchAll() ?: [];

        $features = $this->getProductFeatures();
        foreach ($products as &$product) {
            $product['features'] = $features[$product['id']] ?? [];
        }

        return $products;
    }

    /**
     * Ricerca prodotti paginata per il caricamento lazy del catalogo.
     *
     * @return array{items: array<int,array<string,mixed>>, total: int}
     */
    public function searchProducts(string $cat = 'all', string $sub = 'all', string $q = '', int $limit = 30, int $offset = 0, string $sort = 'featured'): array
    {
        $where  = ['1=1'];
        $params = [];
        if ($cat !== 'all' && $cat !== '') {
            $where[]        = 'category = :cat';
            $params['cat']  = $cat;
        }
        if ($sub !== 'all' && $sub !== '') {
            $where[]        = 'subcategory = :sub';
            $params['sub']  = $sub;
        }
        if ($q !== '') {
            // Placeholder unici: native prepares non ammettono :q ripetuto (HY093).
            $like         = '%' . $q . '%';
            $where[]      = '(name LIKE :qn OR tags LIKE :qg OR subcategory_label LIKE :qs)';
            $params['qn'] = $like;
            $params['qg'] = $like;
            $params['qs'] = $like;
        }
        $whereSql = implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $orderBy = match ($sort) {
            'price_asc' => 'COALESCE(sale_price, price, 999999) ASC, name ASC',
            'price_desc' => 'COALESCE(sale_price, price, 0) DESC, name ASC',
            'name_asc' => 'name ASC',
            'newest' => 'id DESC',
            default => 'featured_order ASC, stock_qty DESC, name ASC',
        };

        $sql = "SELECT * FROM products WHERE {$whereSql} ORDER BY {$orderBy} LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll() ?: [], 'total' => $total];
    }

    /**
     * Macro presenti a catalogo con conteggio, per costruire i filtri.
     *
     * @return array<string,int>
     */
    public function productCategoryCounts(): array
    {
        $stmt = $this->db->query("SELECT category, COUNT(*) n FROM products WHERE category IS NOT NULL AND category <> '' GROUP BY category");

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    }

    /**
     * Sotto-categorie per macro, con label e conteggio.
     *
     * @return array<string, array<int, array{slug:string,label:string,n:int}>>
     */
    public function productSubcategories(): array
    {
        $stmt = $this->db->query(
            "SELECT category, subcategory, subcategory_label, COUNT(*) n
             FROM products
             WHERE category <> '' AND subcategory IS NOT NULL AND subcategory <> ''
             GROUP BY category, subcategory, subcategory_label
             ORDER BY subcategory_label"
        );
        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $out[$r['category']][] = [
                'slug'  => (string)$r['subcategory'],
                'label' => (string)($r['subcategory_label'] ?: $r['subcategory']),
                'n'     => (int)$r['n'],
            ];
        }

        return $out;
    }

    /**
     * Prodotti correlati a un insieme di tag/parole chiave (per il blog).
     * Match su nome, tag, categoria e sotto-categoria; priorità ai disponibili
     * con foto. Cerca su tutto il catalogo, non solo i primi N.
     *
     * @param array<int,string> $tags
     * @return array<int,array<string,mixed>>
     */
    public function relatedProductsByTags(array $tags, int $limit = 4): array
    {
        $tags = array_values(array_filter(array_map('trim', $tags), static fn ($t) => mb_strlen($t) >= 3));
        if ($tags === []) {
            return [];
        }
        $clauses = [];
        $params  = [];
        foreach (array_slice($tags, 0, 8) as $i => $t) {
            // Placeholder UNICI per ogni colonna: con i native prepares (PDO
            // EMULATE_PREPARES = false) un placeholder ripetuto lancia HY093.
            $like = '%' . $t . '%';
            $clauses[] = "(name LIKE :n{$i} OR tags LIKE :g{$i} OR category LIKE :c{$i} OR subcategory_label LIKE :s{$i})";
            $params["n{$i}"] = $like;
            $params["g{$i}"] = $like;
            $params["c{$i}"] = $like;
            $params["s{$i}"] = $like;
        }
        $sql = "SELECT id, name, slug, image_url, sale_price, price, campaign_label, stock_status, stock_qty, subcategory_label
                FROM products
                WHERE (" . implode(' OR ', $clauses) . ")
                  AND image_url IS NOT NULL AND image_url <> ''
                ORDER BY (stock_status = 'disponibile') DESC, stock_qty DESC, featured_order ASC
                LIMIT :limit";
        // I prodotti correlati sono un di più: se lo schema della tabella products
        // non è allineato (colonna mancante su un host con migrazioni parziali),
        // non deve far cadere l'intera pagina dell'articolo. Degrada a vuoto.
        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue(':' . $k, $v);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getProductBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $product = $stmt->fetch();

        if (!$product) {
            return null;
        }

        $features = $this->getProductFeatures();
        $product['features'] = $features[$product['id']] ?? [];

        return $product;
    }

    private function getProductFeatures(): array
    {
        $stmt = $this->db->query('SELECT product_id, feature_text FROM product_features ORDER BY sort_order ASC');
        $rows = $stmt->fetchAll() ?: [];

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int)$row['product_id']][] = $row['feature_text'];
        }

        return $grouped;
    }

    public function getAgents(): array
    {
        $stmt = $this->db->query('SELECT * FROM agents ORDER BY featured_order ASC, name ASC');
        return $stmt->fetchAll() ?: [];
    }

    public function getTeamMembers(): array
    {
        $stmt = $this->db->query('SELECT * FROM team_members ORDER BY sort_order ASC, name ASC');
        return $stmt->fetchAll() ?: [];
    }

    public function getPartners(): array
    {
        $stmt = $this->db->query('SELECT * FROM partners ORDER BY featured_order ASC, name ASC');
        return $stmt->fetchAll() ?: [];
    }

    public function getRoadmapPhases(): array
    {
        $stmt = $this->db->query('SELECT * FROM roadmap_phases ORDER BY sort_order ASC');
        $phases = $stmt->fetchAll() ?: [];

        $itemStmt = $this->db->prepare('SELECT title, description FROM roadmap_items WHERE roadmap_phase_id = :id ORDER BY sort_order ASC');

        foreach ($phases as &$phase) {
            $itemStmt->execute(['id' => $phase['id']]);
            $phase['items'] = $itemStmt->fetchAll() ?: [];
        }

        return $phases;
    }

    public function getAlwaysOnTracks(): array
    {
        $stmt = $this->db->query('SELECT title FROM always_on_tracks ORDER BY sort_order ASC');
        return array_column($stmt->fetchAll() ?: [], 'title');
    }

    public function getFaqItems(): array
    {
        $stmt = $this->db->query('SELECT * FROM faq_items ORDER BY sort_order ASC');
        return $stmt->fetchAll() ?: [];
    }

    public function getBlogPosts(): array
    {
        $where = I18n::currentLocale() === 'en'
            ? "is_published = 1 AND title_en != '' AND content_html_en IS NOT NULL AND content_html_en != ''"
            : 'is_published = 1';
        $stmt = $this->db->query("SELECT * FROM blog_posts WHERE {$where} ORDER BY published_at DESC");
        return $stmt->fetchAll() ?: [];
    }

    public function getBlogPostBySlug(string $slug): ?array
    {
        $translated = I18n::currentLocale() === 'en'
            ? " AND title_en != '' AND content_html_en IS NOT NULL AND content_html_en != ''"
            : '';
        $stmt = $this->db->prepare("SELECT * FROM blog_posts WHERE slug = :slug{$translated} LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $post = $stmt->fetch();

        return $post ?: null;
    }

    public function getCaseStudies(): array
    {
        $stmt = $this->db->query('SELECT * FROM case_studies ORDER BY sort_order ASC');
        return $stmt->fetchAll() ?: [];
    }

    public function getCommands(): array
    {
        $stmt = $this->db->query('SELECT * FROM commands ORDER BY sort_order ASC');
        return $stmt->fetchAll() ?: [];
    }

    public function getPressAssets(): array
    {
        $stmt = $this->db->query('SELECT * FROM press_assets ORDER BY sort_order ASC, asset_type ASC');
        return $stmt->fetchAll() ?: [];
    }

    public function getTransparencyWallets(): array
    {
        $stmt = $this->db->query('SELECT * FROM transparency_wallets ORDER BY sort_order ASC, label ASC');
        return $stmt->fetchAll() ?: [];
    }

    public function getTransparencyReports(): array
    {
        $stmt = $this->db->query('SELECT * FROM transparency_reports ORDER BY sort_order ASC, label ASC');
        return $stmt->fetchAll() ?: [];
    }

    public function getLegalSections(): array
    {
        $stmt = $this->db->query('SELECT * FROM legal_sections ORDER BY sort_order ASC, title ASC');
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int, array{group_key:string,title:string,items:array<int,array>}> 
     */
    public function getNavigation(string $menuKey): array
    {
        $menu = $menuKey === 'footer' ? 'footer' : 'header';

        $groupStmt = $this->db->prepare(
            'SELECT id, group_key, title
             FROM navigation_groups
             WHERE menu_key = :menu AND is_active = 1
             ORDER BY sort_order ASC, id ASC'
        );
        $groupStmt->execute(['menu' => $menu]);
        $groups = $groupStmt->fetchAll();

        if (!$groups) {
            return [];
        }

        $groupMap = [];
        $groupIds = [];
        foreach ($groups as $index => $group) {
            $groupIds[] = (int)$group['id'];
            $groupMap[(int)$group['id']] = [
                'group_key' => $group['group_key'],
                'title' => $group['title'],
                'items' => [],
            ];
        }

        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        $itemsStmt = $this->db->prepare(
            "SELECT group_id, label, url, icon_key, is_external
             FROM navigation_items
             WHERE group_id IN ($placeholders) AND is_active = 1
             ORDER BY sort_order ASC, id ASC"
        );
        $itemsStmt->execute($groupIds);
        $items = $itemsStmt->fetchAll() ?: [];

        foreach ($items as $item) {
            $gid = (int)$item['group_id'];
            if (!isset($groupMap[$gid])) {
                continue;
            }
            $groupMap[$gid]['items'][] = [
                'label' => $item['label'],
                'url' => $item['url'],
                'icon_key' => $item['icon_key'],
                'is_external' => (bool)$item['is_external'],
            ];
        }

        return array_values($groupMap);
    }

    public function getSocialProofItems(): array
    {
        $stmt = $this->db->query('SELECT * FROM social_proof_items ORDER BY sort_order ASC');
        return $stmt->fetchAll() ?: [];
    }
}
