<?php
declare(strict_types=1);
/**
 * bisped.net Auto-Update Ingestion Engine
 *
 * Fetches RSS / scrape / API sources from sources.json,
 * enriches content via CopilotRM LLM (localhost:4010) when available,
 * and upserts products + blog posts in the CMS database.
 *
 * Usage:
 *   php scripts/auto-update/ingest.php [--source=smartphone] [--dry-run] [--verbose]
 *   php scripts/auto-update/ingest.php --source=energia
 *   php scripts/auto-update/ingest.php --all
 *
 * Cron (daily at 06:00):
 *   0 6 * * * /path/to/frankenphp php-cli /path/to/bisped.net/scripts/auto-update/ingest.php --all >> /var/log/bisped-ingest.log 2>&1
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Core\Database;

// ── CLI options ──────────────────────────────────────────────────────────────
$opts     = getopt('', ['source:', 'all', 'dry-run', 'verbose']);
$dryRun   = isset($opts['dry-run']);
$verbose  = isset($opts['verbose']);
$sources  = json_decode(file_get_contents(__DIR__ . '/sources.json'), true);
$pdo      = Database::connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ── Determine which categories to process ───────────────────────────────────
if (isset($opts['all'])) {
    $activeCategories = array_keys($sources);
} elseif (isset($opts['source'])) {
    $activeCategories = [(string)$opts['source']];
} else {
    $activeCategories = array_keys($sources);
}

// ── CopilotRM LLM bridge ─────────────────────────────────────────────────────
function llm_generate(string $prompt, int $maxTokens = 800): ?string
{
    $payload = json_encode([
        'model'      => 'llama3.2',
        'prompt'     => $prompt,
        'max_tokens' => $maxTokens,
        'stream'     => false,
    ]);
    $ch = curl_init('http://localhost:11434/api/generate');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err || !$raw) {
        return null;
    }
    // Ollama streams JSON lines; collect all response text
    $text = '';
    foreach (explode("\n", $raw) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $obj = json_decode($line, true);
        if (isset($obj['response'])) {
            $text .= $obj['response'];
        }
    }
    return $text ?: null;
}

// ── CopilotRM RSS ingest bridge (port 4010) ──────────────────────────────────
function copilotrm_rss_sync(int $maxItems = 30): ?array
{
    $ch = curl_init('http://localhost:4010/api/ingest/rss/sync');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['maxItems' => $maxItems]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer dev'],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    return $raw ? json_decode($raw, true) : null;
}

function copilotrm_get_news(string $category = '', int $limit = 20): array
{
    $qs = http_build_query(array_filter([
        'category' => $category,
        'limit'    => $limit,
    ]));
    $ch = curl_init("http://localhost:4010/api/news?{$qs}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer dev'],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    return ($raw ? json_decode($raw, true) : null) ?? [];
}

// ── RSS fetch helper ─────────────────────────────────────────────────────────
function fetch_rss(string $url, array $keywords): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => 'bisped.net-bot/1.0 (+https://bisped.net)',
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);

    if (!$body) return [];

    $items = [];
    try {
        $xml = new SimpleXMLElement($body);
        $entries = $xml->channel->item ?? $xml->entry ?? [];
        foreach ($entries as $entry) {
            $title   = html_entity_decode(strip_tags((string)($entry->title ?? '')));
            $summary = html_entity_decode(strip_tags((string)($entry->description ?? $entry->summary ?? '')));
            $link    = (string)($entry->link ?? $entry->id ?? '');
            $pubDate = (string)($entry->pubDate ?? $entry->updated ?? date('Y-m-d'));

            // Filter by keyword relevance
            $text    = strtolower($title . ' ' . $summary);
            $match   = false;
            foreach ($keywords as $kw) {
                if (str_contains($text, strtolower($kw))) { $match = true; break; }
            }
            if (!$match) continue;

            $items[] = [
                'title'   => substr($title, 0, 200),
                'summary' => substr($summary, 0, 500),
                'link'    => $link,
                'date'    => date('Y-m-d', strtotime($pubDate) ?: time()),
            ];
        }
    } catch (Exception $e) {
        // Malformed XML — skip silently
    }

    return array_slice($items, 0, 5);
}

// ── Slug helper ───────────────────────────────────────────────────────────────
function auto_slug(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace(['à','è','é','ì','ò','ù'], ['a','e','e','i','o','u'], $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

// ── DB log helper ─────────────────────────────────────────────────────────────
function db_log(PDO $pdo, string $action, string $entityType, string $entitySlug, string $msg): void
{
    $pdo->prepare("INSERT INTO ingest_log (action, entity_type, entity_slug, message) VALUES (?,?,?,?)")
        ->execute([$action, $entityType, $entitySlug, $msg]);
}

// ─────────────────────────────────────────────────────────────────────────────
// MAIN INGESTION LOOP
// ─────────────────────────────────────────────────────────────────────────────
$totalProcessed = 0;
$totalInserted  = 0;

foreach ($activeCategories as $category) {
    if (!isset($sources[$category])) {
        echo "[WARN] Unknown category: {$category}\n";
        continue;
    }

    foreach ($sources[$category] as $source) {
        if (empty($source['enabled'])) continue;

        $brand    = $source['brand'];
        $srcType  = $source['type'];
        $url      = $source['url'];
        $keywords = $source['keywords'] ?? [];

        echo "[{$brand}] Fetching {$srcType}: {$url}\n";

        $items = [];

        if ($srcType === 'rss') {
            $items = fetch_rss($url, $keywords);
        } elseif ($srcType === 'api' && ($source['arera_source'] ?? false)) {
            // For ARERA open data — delegate to CopilotRM if running
            $result = copilotrm_rss_sync(20);
            if ($result) {
                echo "  → CopilotRM RSS sync triggered: " . json_encode($result) . "\n";
            }
            continue;
        }

        if (empty($items)) {
            if ($verbose) echo "  → No matching items\n";
            continue;
        }

        foreach ($items as $item) {
            $totalProcessed++;

            // Generate a blog post from each news item via LLM
            $postSlug = auto_slug(substr($item['title'], 0, 80)) . '-' . $item['date'];

            // Check if already exists
            $exists = $pdo->prepare("SELECT id FROM blog_posts WHERE slug = ?")->execute([$postSlug]);
            $row    = $pdo->query("SELECT id FROM blog_posts WHERE slug = " . $pdo->quote($postSlug))->fetch();
            if ($row) {
                if ($verbose) echo "  → Already exists: {$postSlug}\n";
                continue;
            }

            // Build prompt for LLM
            $prompt = <<<PROMPT
Sei un copywriter SEO italiano che scrive per bisped.net, un negozio di informatica e telefonia a Piombino (LI).
Scrivi un articolo blog ottimizzato SEO in italiano, in HTML (senza tag html/head/body), di circa 500-700 parole su questa notizia:

TITOLO: {$item['title']}
BRAND: {$brand}
CATEGORIA: {$category}
SINTESI: {$item['summary']}
FONTE: {$item['link']}

L'articolo deve:
- Iniziare con un <h2> accattivante
- Avere 3-4 sezioni con <h3>
- Menzionare bisped.net di Piombino come punto di acquisto/consulenza
- Concludere con un invito a visitare il negozio o contattarci
- Usare tag <strong> per i termini importanti
- Evitare qualsiasi script o tag non semantico

Rispondi SOLO con l'HTML dell'articolo.
PROMPT;

            if (!$dryRun) {
                $html = llm_generate($prompt, 1200);
            } else {
                $html = null;
            }

            if (!$html) {
                // Fallback: minimal HTML article without LLM
                $html  = "<h2>" . htmlspecialchars($item['title'], ENT_QUOTES) . "</h2>\n";
                $html .= "<p>" . htmlspecialchars($item['summary'], ENT_QUOTES) . "</p>\n";
                $html .= "<p><a href=\"" . htmlspecialchars($item['link'], ENT_QUOTES) . "\">Leggi la fonte originale</a></p>\n";
                $html .= "<p>Per acquistare o ricevere una consulenza su questo prodotto, <strong>vieni da bisp&amp;d a Piombino</strong> o contattaci su WhatsApp al <strong>0565 200000</strong>.</p>";
            }

            $snippet = strip_tags($html);
            $snippet = mb_substr(preg_replace('/\s+/', ' ', $snippet), 0, 200, 'UTF-8');

            if (!$dryRun) {
                $pdo->prepare("
                    INSERT INTO blog_posts
                        (slug, title, published_at, image_url, snippet, content_html,
                         is_published, related_product_tags, source_url, auto_generated)
                    VALUES (?,?,?,?,?,?,1,?,?,1)
                ")->execute([
                    $postSlug,
                    $item['title'],
                    $item['date'],
                    '',
                    $snippet,
                    $html,
                    implode(',', $keywords),
                    $item['link'],
                ]);

                db_log($pdo, 'blog_post_created', 'blog_post', $postSlug,
                    "Auto-generated from {$brand} RSS: {$item['title']}");
                $totalInserted++;
            }

            echo "  + " . ($dryRun ? '[DRY] ' : '') . "Post: {$postSlug}\n";
        }
    }
}

// ── Try pulling fresh data from CopilotRM news API ──────────────────────────
echo "\n[CopilotRM] Polling /api/news for cross-reference...\n";
$news = copilotrm_get_news('', 30);
if ($news) {
    echo "  → " . count($news) . " news items from CopilotRM\n";
    // Future: cross-reference these with product catalog
} else {
    echo "  → CopilotRM not running (localhost:4010) — skipped\n";
}

echo "\n─────────────────────────────────────────────────\n";
echo "Processed: {$totalProcessed} | Inserted: {$totalInserted}" . ($dryRun ? ' [DRY RUN]' : '') . "\n";
echo "Done at " . date('Y-m-d H:i:s') . "\n";
