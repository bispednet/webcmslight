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
     * Vetrina prodotti per la pagina servizi: pochi prodotti forti, non un
     * semplice slice del catalogo. Privilegia categorie diverse e articoli
     * disponibili, fotografati e commercialmente presentabili.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getServiceShowcaseProducts(int $limit = 8): array
    {
        $metricsJoin = $this->tableExists('product_metrics') ? 'LEFT JOIN product_metrics pm ON pm.product_id = products.id' : '';
        $metricsScore = $metricsJoin !== '' ? '+ LEAST(COALESCE(pm.views_30d, 0), 500) * 20' : '';
        $sql = "SELECT *,
                       COALESCE(sale_price, price, 0) AS effective_price,
                       CASE category
                           WHEN 'pc-custom' THEN 0
                           WHEN 'pc-assemblati' THEN 1
                           WHEN 'notebook' THEN 2
                           WHEN 'smartphone' THEN 3
                           WHEN 'gaming' THEN 4
                           WHEN 'monitor' THEN 5
                           WHEN 'connettivita' THEN 6
                           WHEN 'stampa' THEN 7
                           WHEN 'audio-video' THEN 8
                           WHEN 'server' THEN 9
                           WHEN 'componenti' THEN 10
                           WHEN 'accessori' THEN 11
                           ELSE 50
                       END AS service_category_rank,
                       (
                           CASE WHEN stock_status IN ('disponibile','instock','in-stock','1','true') THEN 10000 ELSE 0 END
                           + CASE WHEN image_url IS NOT NULL AND image_url <> '' THEN 3000 ELSE 0 END
                           + CASE WHEN campaign_label IS NOT NULL AND campaign_label <> '' THEN 900 ELSE 0 END
                           + CASE
                               WHEN COALESCE(sale_price, price, 0) >= 1000 THEN 900
                               WHEN COALESCE(sale_price, price, 0) >= 300 THEN 700
                               WHEN COALESCE(sale_price, price, 0) >= 100 THEN 500
                               ELSE 0
                             END
                           + CASE
                               WHEN UPPER(name) LIKE '%RTX 50%' OR UPPER(name) LIKE '%GEFORCE RTX%' OR UPPER(name) LIKE '%RADEON RX%' THEN 1300
                               WHEN UPPER(name) LIKE '%I9%' OR UPPER(name) LIKE '%I7%' OR UPPER(name) LIKE '%RYZEN 9%' OR UPPER(name) LIKE '%RYZEN 7%' THEN 900
                               WHEN UPPER(name) LIKE '%PRO MAX%' OR UPPER(name) LIKE '%ULTRA%' THEN 850
                               WHEN UPPER(name) LIKE '%OLED%' OR UPPER(name) LIKE '%4K%' OR UPPER(name) LIKE '%WQHD%' THEN 750
                               WHEN UPPER(name) LIKE '%144HZ%' OR UPPER(name) LIKE '%165HZ%' OR UPPER(name) LIKE '%240HZ%' THEN 550
                               WHEN UPPER(name) LIKE '%FIREWALL%' OR UPPER(name) LIKE '%FORTIGATE%' OR UPPER(name) LIKE '%LASER%' THEN 500
                               ELSE 0
                             END
                           + LEAST(COALESCE(sale_price, price, 0), 3000) / 2
                           - CASE
                               WHEN UPPER(name) LIKE '%MICROSD%' OR UPPER(name) LIKE '%MEMORY CARD%' OR UPPER(name) LIKE '%CAVO %' THEN 1200
                               ELSE 0
                             END
                           {$metricsScore}
                           + LEAST(COALESCE(stock_qty, 0), 50) * 12
                           + GREATEST(0, 300 - COALESCE(featured_order, 0))
                       ) AS showcase_score
                FROM products
                {$metricsJoin}
                WHERE category IS NOT NULL
                  AND category <> ''
                  AND COALESCE(sale_price, price, 0) >= 60
                  AND stock_status IN ('disponibile','instock','in-stock','1','true')
                  AND image_url IS NOT NULL
                  AND image_url <> ''
                ORDER BY service_category_rank ASC, showcase_score DESC, featured_order ASC, name ASC
                LIMIT 5000";

        $stmt = $this->db->query($sql);
        $candidates = $stmt->fetchAll() ?: [];

        $selected = [];
        $seenCategories = [];
        foreach ($candidates as $product) {
            $category = (string)($product['category'] ?? '');
            if ($category === '' || isset($seenCategories[$category])) {
                continue;
            }

            $seenCategories[$category] = true;
            $selected[] = $product;
            if (count($selected) >= $limit) {
                break;
            }
        }

        if (count($selected) < $limit) {
            $seenIds = array_fill_keys(array_map(static fn ($p) => (int)$p['id'], $selected), true);
            foreach ($candidates as $product) {
                $id = (int)($product['id'] ?? 0);
                if ($id <= 0 || isset($seenIds[$id])) {
                    continue;
                }
                $selected[] = $product;
                $seenIds[$id] = true;
                if (count($selected) >= $limit) {
                    break;
                }
            }
        }

        return array_slice($selected, 0, $limit);
    }

    /**
     * Landing SEO per vecchi URL WordPress tipo /negozio/samsung-galaxy.
     *
     * @return array<string,mixed>|null
     */
    public function getBrandLanding(string $slug): ?array
    {
        $definition = $this->brandDefinition($slug);
        if ($definition === null) {
            return null;
        }

        $products = $this->searchBrandProducts($definition['terms'], 24);
        $posts = $this->searchBrandPosts($definition['terms'], 6);

        if ($products === [] && $posts === []) {
            return null;
        }

        return $definition + [
            'products' => $products,
            'posts' => $posts,
            'meta_description' => sprintf(
                '%s a Piombino: prodotti disponibili, assistenza, configurazione e consulenza in negozio da bisp&d.',
                $definition['label']
            ),
        ];
    }

    /**
     * @return array<int,array{slug:string,label:string}>
     */
    public function getBrandLandingIndex(): array
    {
        $out = [];
        foreach ($this->brandDefinitions() as $slug => $definition) {
            if ($this->searchBrandProducts($definition['terms'], 1) !== [] || $this->searchBrandPosts($definition['terms'], 1) !== []) {
                $out[] = ['slug' => $slug, 'label' => $definition['label']];
            }
        }

        return $out;
    }

    /**
     * @param array<int,string> $terms
     * @return array<int,array<string,mixed>>
     */
    private function searchBrandProducts(array $terms, int $limit): array
    {
        $where = [];
        $params = [];
        foreach ($terms as $i => $term) {
            $like = '%' . $term . '%';
            $where[] = "(name LIKE :pn{$i} OR tags LIKE :pt{$i} OR subcategory_label LIKE :ps{$i})";
            $params["pn{$i}"] = $like;
            $params["pt{$i}"] = $like;
            $params["ps{$i}"] = $like;
        }

        $logic = count($terms) > 1 ? implode(' AND ', $where) : implode(' OR ', $where);
        $sql = "SELECT *
                FROM products
                WHERE ({$logic})
                  AND image_url IS NOT NULL AND image_url <> ''
                ORDER BY (stock_status IN ('disponibile','instock','in-stock','1','true')) DESC,
                         stock_qty DESC,
                         COALESCE(sale_price, price, 999999) DESC,
                         featured_order ASC,
                         name ASC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll() ?: [];

        if ($products === [] && count($terms) > 1) {
            return $this->searchBrandProducts([$terms[0]], $limit);
        }

        return $products;
    }

    /**
     * @param array<int,string> $terms
     * @return array<int,array<string,mixed>>
     */
    private function searchBrandPosts(array $terms, int $limit): array
    {
        $clauses = [];
        $params = [];
        foreach ($terms as $i => $term) {
            $like = '%' . $term . '%';
            $clauses[] = "(title LIKE :bt{$i} OR snippet LIKE :bs{$i} OR related_product_tags LIKE :br{$i})";
            $params["bt{$i}"] = $like;
            $params["bs{$i}"] = $like;
            $params["br{$i}"] = $like;
        }

        $sql = "SELECT *
                FROM blog_posts
                WHERE is_published = 1 AND (" . implode(' OR ', $clauses) . ")
                ORDER BY published_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array{slug:string,label:string,terms:array<int,string>,intro:string}|null
     */
    private function brandDefinition(string $slug): ?array
    {
        $slug = trim(strtolower($slug), " /\t\n\r\0\x0B");
        $definitions = $this->brandDefinitions();
        if (isset($definitions[$slug])) {
            return $definitions[$slug] + ['slug' => $slug];
        }

        if (!preg_match('/^[a-z0-9-]{2,80}$/', $slug)) {
            return null;
        }

        $label = ucwords(str_replace('-', ' ', $slug));
        return [
            'slug' => $slug,
            'label' => $label,
            'terms' => [str_replace('-', ' ', $slug)],
            'intro' => "Prodotti {$label}, disponibilita e assistenza locale a Piombino.",
        ];
    }

    /**
     * @return array<string,array{label:string,terms:array<int,string>,intro:string}>
     */
    private function brandDefinitions(): array
    {
        return [
            'samsung-galaxy' => ['label' => 'Samsung Galaxy', 'terms' => ['Samsung', 'Galaxy'], 'intro' => 'Smartphone Samsung Galaxy, accessori e assistenza con consulenza in negozio a Piombino.'],
            'samsung' => ['label' => 'Samsung', 'terms' => ['Samsung'], 'intro' => 'Prodotti Samsung, smartphone Galaxy, monitor e storage selezionati da bisp&d.'],
            'apple-iphone' => ['label' => 'Apple iPhone', 'terms' => ['iPhone'], 'intro' => 'iPhone disponibili, accessori e supporto per scegliere il modello giusto.'],
            'apple' => ['label' => 'Apple', 'terms' => ['Apple', 'iPhone'], 'intro' => 'Prodotti Apple e accessori con assistenza locale a Piombino.'],
            'xiaomi' => ['label' => 'Xiaomi', 'terms' => ['Xiaomi'], 'intro' => 'Smartphone e accessori Xiaomi per chi cerca rapporto qualita prezzo.'],
            'oppo' => ['label' => 'OPPO', 'terms' => ['OPPO'], 'intro' => 'Smartphone OPPO e soluzioni mobile disponibili o ordinabili.'],
            'motorola' => ['label' => 'Motorola', 'terms' => ['Motorola'], 'intro' => 'Smartphone Motorola e accessori con consulenza in negozio.'],
            'huawei' => ['label' => 'Huawei', 'terms' => ['Huawei'], 'intro' => 'Prodotti Huawei, connettivita e dispositivi mobile.'],
            'honor' => ['label' => 'Honor', 'terms' => ['Honor'], 'intro' => 'Smartphone Honor e accessori disponibili da bisp&d.'],
            'asus' => ['label' => 'ASUS', 'terms' => ['ASUS'], 'intro' => 'Notebook, monitor, componenti e gaming ASUS selezionati per lavoro e prestazioni.'],
            'msi' => ['label' => 'MSI', 'terms' => ['MSI'], 'intro' => 'Notebook gaming, schede video, monitor e PC MSI per postazioni ad alte prestazioni.'],
            'hp' => ['label' => 'HP', 'terms' => ['HP'], 'intro' => 'Notebook, PC, stampanti e monitor HP per casa e ufficio.'],
            'lenovo' => ['label' => 'Lenovo', 'terms' => ['Lenovo'], 'intro' => 'Notebook, workstation e desktop Lenovo per lavoro e produttivita.'],
            'dell' => ['label' => 'Dell', 'terms' => ['Dell'], 'intro' => 'Monitor, notebook e PC Dell per ufficio, grafica e produttivita.'],
            'acer' => ['label' => 'Acer', 'terms' => ['Acer'], 'intro' => 'Notebook, monitor e postazioni Acer per studio, lavoro e gaming.'],
            'intel' => ['label' => 'Intel', 'terms' => ['Intel'], 'intro' => 'CPU, notebook e PC con piattaforma Intel disponibili da bisp&d.'],
            'amd' => ['label' => 'AMD Ryzen', 'terms' => ['AMD', 'Ryzen'], 'intro' => 'CPU, schede video e PC con piattaforma AMD Ryzen.'],
            'nvidia' => ['label' => 'NVIDIA GeForce', 'terms' => ['RTX'], 'intro' => 'Schede video NVIDIA GeForce RTX e PC gaming configurati con criterio.'],
            'canon' => ['label' => 'Canon', 'terms' => ['Canon'], 'intro' => 'Stampanti, multifunzione e soluzioni Canon per casa e ufficio.'],
            'brother' => ['label' => 'Brother', 'terms' => ['Brother'], 'intro' => 'Stampanti e multifunzione Brother per ufficio e attivita.'],
            'epson' => ['label' => 'Epson', 'terms' => ['Epson'], 'intro' => 'Stampanti, multifunzione e consumabili Epson.'],
            'fortinet' => ['label' => 'Fortinet Fortigate', 'terms' => ['Fortigate'], 'intro' => 'Firewall Fortigate e sicurezza di rete per aziende e professionisti.'],
            'cisco' => ['label' => 'Cisco', 'terms' => ['Cisco'], 'intro' => 'Networking Cisco, switch, access point e soluzioni per reti aziendali.'],
            'tp-link' => ['label' => 'TP-Link', 'terms' => ['TP-Link', 'TPLINK'], 'intro' => 'Router, switch, access point e networking TP-Link.'],
            'fritzbox' => ['label' => 'FRITZ!Box', 'terms' => ['FRITZ', 'AVM'], 'intro' => 'Router FRITZ!Box e soluzioni Wi-Fi per casa e ufficio.'],
            'ubiquiti' => ['label' => 'Ubiquiti UniFi', 'terms' => ['Ubiquiti', 'UniFi'], 'intro' => 'Reti UniFi, access point e infrastrutture Wi-Fi gestite.'],
        ];
    }

    private function tableExists(string $table): bool
    {
        if (!preg_match('/^[a-z0-9_]+$/', $table)) {
            return false;
        }

        try {
            $stmt = $this->db->query("SHOW TABLES LIKE " . $this->db->quote($table));
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
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
