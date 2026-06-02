<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\AgentAuth;
use App\Core\Database;
use PDO;

/**
 * Agent REST API — usata da Custom GPT, Gemini Function Calling, o qualsiasi AI agent.
 * Auth: Authorization: Bearer {agent.api_key da .env.php}
 */
final class AgentApiController
{
    private PDO $db;

    public function __construct()
    {
        AgentAuth::requireAuth();
        $this->db = Database::connection();
        header('Content-Type: application/json; charset=utf-8');
        header('X-Robots-Tag: noindex');
        // CORS permissivo per OpenAI/Gemini (il token protegge l'accesso)
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
    }

    // ── Ping ─────────────────────────────────────────────────────────────────
    public function ping(): void
    {
        echo json_encode(['ok' => true, 'site' => 'Bisped', 'version' => '1.0']);
    }

    // ── Stats ─────────────────────────────────────────────────────────────────
    public function stats(): void
    {
        $data = [
            'products'     => (int)$this->db->query('SELECT COUNT(*) FROM products')->fetchColumn(),
            'blog_posts'   => (int)$this->db->query('SELECT COUNT(*) FROM blog_posts WHERE is_published=1')->fetchColumn(),
            'leads_today'  => (int)$this->db->query("SELECT COUNT(*) FROM ai_leads WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
            'leads_total'  => (int)$this->db->query('SELECT COUNT(*) FROM ai_leads')->fetchColumn(),
            'new_messages' => (int)$this->db->query("SELECT COUNT(*) FROM contact_messages WHERE status='new'")->fetchColumn(),
            'appointments' => (int)$this->db->query("SELECT COUNT(*) FROM appointment_requests WHERE status='pending'")->fetchColumn(),
        ];
        echo json_encode($data);
    }

    // ── Products ──────────────────────────────────────────────────────────────
    public function listProducts(): void
    {
        $search = $_GET['search'] ?? '';
        $limit  = min((int)($_GET['limit'] ?? 20), 100);
        if ($search !== '') {
            $stmt = $this->db->prepare(
                'SELECT id,name,slug,category,price,sale_price,stock_status,sku,featured_order,updated_at
                 FROM products WHERE name LIKE :s OR category LIKE :s OR sku LIKE :s ORDER BY featured_order,name LIMIT :l'
            );
            $stmt->bindValue(':s', '%' . $search . '%');
            $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        } else {
            $stmt = $this->db->prepare(
                'SELECT id,name,slug,category,price,sale_price,stock_status,sku,featured_order,updated_at
                 FROM products ORDER BY featured_order,name LIMIT :l'
            );
            $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        echo json_encode(['products' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function getProduct(int $id): void
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id=:id');
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Prodotto non trovato.']);

            return;
        }
        // Features
        $fstmt = $this->db->prepare('SELECT feature_text FROM product_features WHERE product_id=:id ORDER BY sort_order');
        $fstmt->execute(['id' => $id]);
        $product['features'] = $fstmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode($product);
    }

    public function createProduct(): void
    {
        $body = $this->body();
        $errors = $this->validateProduct($body);
        if ($errors) {
            http_response_code(422);
            echo json_encode(['error' => implode('; ', $errors)]);

            return;
        }
        $slug = $this->uniqueSlug($body['slug'] ?? $this->slugify($body['name']), 'products');
        $stmt = $this->db->prepare(
            'INSERT INTO products (name,slug,description,icon_key,external_link,hero_title,hero_subtitle,
             cta_text,cta_link,category,tags,sku,price,sale_price,campaign_label,stock_status,content_html,featured_order)
             VALUES (:name,:slug,:description,:icon_key,:external_link,:hero_title,:hero_subtitle,
             :cta_text,:cta_link,:category,:tags,:sku,:price,:sale_price,:campaign_label,:stock_status,:content_html,:featured_order)'
        );
        $stmt->execute([
            'name'           => mb_substr((string)($body['name'] ?? ''), 0, 150),
            'slug'           => $slug,
            'description'    => (string)($body['description'] ?? ''),
            'icon_key'       => mb_substr((string)($body['icon_key'] ?? 'default'), 0, 50),
            'external_link'  => mb_substr((string)($body['external_link'] ?? ''), 0, 255) ?: null,
            'hero_title'     => mb_substr((string)($body['hero_title'] ?? ''), 0, 200) ?: null,
            'hero_subtitle'  => (string)($body['hero_subtitle'] ?? '') ?: null,
            'cta_text'       => mb_substr((string)($body['cta_text'] ?? ''), 0, 100) ?: null,
            'cta_link'       => mb_substr((string)($body['cta_link'] ?? ''), 0, 255) ?: null,
            'category'       => mb_substr((string)($body['category'] ?? ''), 0, 120) ?: null,
            'tags'           => mb_substr((string)($body['tags'] ?? ''), 0, 255) ?: null,
            'sku'            => mb_substr((string)($body['sku'] ?? ''), 0, 120) ?: null,
            'price'          => isset($body['price']) ? (float)$body['price'] : null,
            'sale_price'     => isset($body['sale_price']) ? (float)$body['sale_price'] : null,
            'campaign_label' => mb_substr((string)($body['campaign_label'] ?? ''), 0, 120) ?: null,
            'stock_status'   => mb_substr((string)($body['stock_status'] ?? 'disponibile'), 0, 80),
            'content_html'   => (string)($body['content_html'] ?? '') ?: null,
            'featured_order' => (int)($body['featured_order'] ?? 0),
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->syncFeatures($id, (array)($body['features'] ?? []));
        http_response_code(201);
        echo json_encode(['id' => $id, 'slug' => $slug, 'message' => 'Prodotto creato.']);
    }

    public function updateProduct(int $id): void
    {
        $stmt = $this->db->prepare('SELECT id FROM products WHERE id=:id');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['error' => 'Prodotto non trovato.']);

            return;
        }
        $body = $this->body();
        $allowed = ['name', 'description', 'icon_key', 'external_link', 'hero_title', 'hero_subtitle',
            'cta_text', 'cta_link', 'category', 'tags', 'sku', 'price', 'sale_price',
            'campaign_label', 'stock_status', 'content_html', 'featured_order'];
        $sets = [];
        $params = ['id' => $id];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $body)) {
                $sets[]        = "{$field}=:{$field}";
                $params[$field] = in_array($field, ['price', 'sale_price'], true)
                    ? ($body[$field] === null ? null : (float)$body[$field])
                    : ($field === 'featured_order' ? (int)$body[$field] : mb_substr((string)$body[$field], 0, 255));
            }
        }
        if ($sets) {
            $this->db->prepare('UPDATE products SET ' . implode(',', $sets) . ' WHERE id=:id')->execute($params);
        }
        if (isset($body['features'])) {
            $this->syncFeatures($id, (array)$body['features']);
        }
        echo json_encode(['id' => $id, 'message' => 'Prodotto aggiornato.']);
    }

    public function deleteProduct(int $id): void
    {
        $this->db->prepare('DELETE FROM products WHERE id=:id')->execute(['id' => $id]);
        echo json_encode(['message' => 'Prodotto eliminato.']);
    }

    // ── Blog ──────────────────────────────────────────────────────────────────
    public function listBlog(): void
    {
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        $stmt  = $this->db->prepare(
            'SELECT id,slug,title,published_at,is_published,auto_generated,updated_at
             FROM blog_posts ORDER BY published_at DESC LIMIT :l'
        );
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['posts' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function getPost(int $id): void
    {
        $stmt = $this->db->prepare('SELECT * FROM blog_posts WHERE id=:id');
        $stmt->execute(['id' => $id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$post) {
            http_response_code(404);
            echo json_encode(['error' => 'Post non trovato.']);

            return;
        }
        echo json_encode($post);
    }

    public function createPost(): void
    {
        $body = $this->body();
        if (empty($body['title']) || empty($body['content_html'])) {
            http_response_code(422);
            echo json_encode(['error' => 'title e content_html sono obbligatori.']);

            return;
        }
        $slug = $this->uniqueSlug($body['slug'] ?? $this->slugify($body['title']), 'blog_posts');
        $stmt = $this->db->prepare(
            'INSERT INTO blog_posts (slug,title,title_en,published_at,image_url,snippet,snippet_en,
             content_html,content_html_en,is_published,related_product_tags)
             VALUES (:slug,:title,:title_en,:published_at,:image_url,:snippet,:snippet_en,
             :content_html,:content_html_en,:is_published,:related_product_tags)'
        );
        $stmt->execute([
            'slug'                 => $slug,
            'title'                => mb_substr((string)$body['title'], 0, 200),
            'title_en'             => mb_substr((string)($body['title_en'] ?? ''), 0, 200),
            'published_at'         => $body['published_at'] ?? date('Y-m-d'),
            'image_url'            => mb_substr((string)($body['image_url'] ?? ''), 0, 255),
            'snippet'              => mb_substr((string)($body['snippet'] ?? ''), 0, 500),
            'snippet_en'           => mb_substr((string)($body['snippet_en'] ?? ''), 0, 500) ?: null,
            'content_html'         => (string)$body['content_html'],
            'content_html_en'      => (string)($body['content_html_en'] ?? '') ?: null,
            'is_published'         => (int)(bool)($body['is_published'] ?? true),
            'related_product_tags' => mb_substr((string)($body['related_product_tags'] ?? ''), 0, 255) ?: null,
        ]);
        $id = (int)$this->db->lastInsertId();
        http_response_code(201);
        echo json_encode(['id' => $id, 'slug' => $slug, 'message' => 'Post creato.']);
    }

    public function updatePost(int $id): void
    {
        $stmt = $this->db->prepare('SELECT id FROM blog_posts WHERE id=:id');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['error' => 'Post non trovato.']);

            return;
        }
        $body    = $this->body();
        $allowed = ['title', 'title_en', 'published_at', 'image_url', 'snippet', 'snippet_en',
            'content_html', 'content_html_en', 'is_published', 'related_product_tags'];
        $sets = [];
        $params = ['id' => $id];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $body)) {
                $sets[]     = "{$f}=:{$f}";
                $params[$f] = $f === 'is_published' ? (int)(bool)$body[$f] : mb_substr((string)$body[$f], 0, 500);
            }
        }
        if ($sets) {
            $this->db->prepare('UPDATE blog_posts SET ' . implode(',', $sets) . ' WHERE id=:id')->execute($params);
        }
        echo json_encode(['id' => $id, 'message' => 'Post aggiornato.']);
    }

    // ── Leads ─────────────────────────────────────────────────────────────────
    public function listLeads(): void
    {
        $limit  = min((int)($_GET['limit'] ?? 20), 100);
        $sector = $_GET['sector'] ?? '';
        $stmt = $sector !== ''
            ? $this->db->prepare(
                'SELECT id,name,phone,email,sector,urgency,lead_score,status,need_summary,created_at
                 FROM ai_leads WHERE sector=:s ORDER BY created_at DESC LIMIT :l'
            )
            : $this->db->prepare(
                'SELECT id,name,phone,email,sector,urgency,lead_score,status,need_summary,created_at
                 FROM ai_leads ORDER BY created_at DESC LIMIT :l'
            );
        if ($sector !== '') {
            $stmt->bindValue(':s', $sector);
        }
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['leads' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function getLead(int $id): void
    {
        $stmt = $this->db->prepare(
            'SELECT l.*, c.summary, c.structured_data
             FROM ai_leads l LEFT JOIN ai_conversations c ON c.id=l.conversation_id
             WHERE l.id=:id'
        );
        $stmt->execute(['id' => $id]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$lead) {
            http_response_code(404);
            echo json_encode(['error' => 'Lead non trovato.']);

            return;
        }
        // Parse structured_data for commercial report
        if (!empty($lead['structured_data'])) {
            $data = json_decode($lead['structured_data'], true) ?: [];
            $lead['commercial_report'] = $data['commercial_report'] ?? null;
            $lead['analytics']         = $data['analytics'] ?? null;
            unset($lead['structured_data']);
        }
        echo json_encode($lead);
    }

    // ── Messages ──────────────────────────────────────────────────────────────
    public function listMessages(): void
    {
        $status = $_GET['status'] ?? 'new';
        $limit  = min((int)($_GET['limit'] ?? 20), 100);
        $stmt   = $this->db->prepare(
            'SELECT id,name,email,message,status,created_at
             FROM contact_messages WHERE status=:s ORDER BY created_at DESC LIMIT :l'
        );
        $stmt->bindValue(':s', $status);
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // ── Appointments ──────────────────────────────────────────────────────────
    public function listAppointments(): void
    {
        $status = $_GET['status'] ?? 'pending';
        $limit  = min((int)($_GET['limit'] ?? 20), 100);
        $stmt   = $this->db->prepare(
            'SELECT id,name,email,phone,service,preferred_date,preferred_time,notes,status,created_at
             FROM appointment_requests WHERE status=:s ORDER BY preferred_date ASC LIMIT :l'
        );
        $stmt->bindValue(':s', $status);
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['appointments' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    private function body(): array
    {
        $raw = (string)file_get_contents('php://input');
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[àáâãäå]/u', 'a', $text) ?? $text;
        $text = preg_replace('/[èéêë]/u', 'e', $text) ?? $text;
        $text = preg_replace('/[ìíîï]/u', 'i', $text) ?? $text;
        $text = preg_replace('/[òóôõö]/u', 'o', $text) ?? $text;
        $text = preg_replace('/[ùúûü]/u', 'u', $text) ?? $text;
        $text = preg_replace('/[^a-z0-9\s-]/u', '', $text) ?? $text;
        $text = preg_replace('/[\s-]+/', '-', $text) ?? $text;

        return trim($text, '-');
    }

    private function uniqueSlug(string $base, string $table): string
    {
        $base  = $this->slugify($base) ?: 'item';
        $slug  = $base;
        $count = 1;
        while (true) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE slug=:s");
            $stmt->execute(['s' => $slug]);
            if ((int)$stmt->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $base . '-' . (++$count);
        }
    }

    private function validateProduct(array $body): array
    {
        $errors = [];
        if (empty($body['name'])) {
            $errors[] = 'name è obbligatorio';
        }
        if (empty($body['description'])) {
            $errors[] = 'description è obbligatoria';
        }

        return $errors;
    }

    private function syncFeatures(int $productId, array $features): void
    {
        $this->db->prepare('DELETE FROM product_features WHERE product_id=:id')->execute(['id' => $productId]);
        $stmt = $this->db->prepare(
            'INSERT INTO product_features (product_id, feature_text, sort_order) VALUES (:id, :text, :order)'
        );
        foreach (array_values($features) as $i => $text) {
            $stmt->execute(['id' => $productId, 'text' => mb_substr((string)$text, 0, 255), 'order' => $i]);
        }
    }
}
