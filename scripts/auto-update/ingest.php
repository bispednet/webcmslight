<?php
declare(strict_types=1);
/**
 * bisped.net Auto-Update Ingestion Engine
 *
 * Fetches RSS / scrape / API sources from sources.json,
 * enriches content via the Gemini API when configured,
 * and upserts products + blog posts in the CMS database.
 *
 * Usage:
 *   php scripts/auto-update/ingest.php [--source=smartphone] [--dry-run] [--verbose] [--limit=3]
 *   php scripts/auto-update/ingest.php --source=energia
 *   php scripts/auto-update/ingest.php --all
 *
 * Cron (daily at 06:00):
 *   0 6 * * * /path/to/frankenphp php-cli /path/to/bisped.net/scripts/auto-update/ingest.php --all >> /var/log/bisped-ingest.log 2>&1
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\Ai\GeminiClient;
use App\Support\HtmlSanitizer;

// ── CLI options ──────────────────────────────────────────────────────────────
$opts     = getopt('', ['source:', 'all', 'dry-run', 'verbose', 'limit:']);
$dryRun   = isset($opts['dry-run']);
$verbose  = isset($opts['verbose']);
$limit    = isset($opts['limit']) ? max(1, (int)$opts['limit']) : 5;
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

// ── Gemini editorial bridge ──────────────────────────────────────────────────
function llm_generate(string $prompt, int $maxTokens = 800, string $mode = 'text'): ?string
{
    static $available = true;
    static $client = null;
    if (!$available) {
        return null;
    }
    if (!$client instanceof GeminiClient) {
        $client = GeminiClient::fromConfig($GLOBALS['config'] ?? []);
    }
    if (!$client instanceof GeminiClient) {
        $available = false;
        return null;
    }
    return $client->generate($prompt, $maxTokens, $mode);
}

// ── RSS fetch helper ─────────────────────────────────────────────────────────
function clean_text(string $value, int $maxLength): string
{
    $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('/\s+/', ' ', trim($value));
    return mb_substr($value, 0, $maxLength, 'UTF-8');
}

function canonical_source_url(string $url): string
{
    $parts = parse_url(trim($url));
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
        return trim($url);
    }
    $query = [];
    parse_str((string)($parts['query'] ?? ''), $query);
    foreach (array_keys($query) as $key) {
        if (str_starts_with(strtolower((string)$key), 'utm_') || in_array(strtolower((string)$key), ['fbclid', 'gclid'], true)) {
            unset($query[$key]);
        }
    }
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $path = $parts['path'] ?? '/';
    return strtolower($parts['scheme']) . '://' . strtolower($parts['host']) . $port . $path
        . ($query ? '?' . http_build_query($query) : '');
}

function source_fingerprint(array $item): string
{
    return hash('sha256', canonical_source_url((string)$item['link']) . "\n"
        . clean_text((string)$item['title'], 500) . "\n"
        . clean_text((string)$item['summary'], 2000));
}

function is_safe_public_url(string $url): bool
{
    $parts = parse_url($url);
    if (!$parts || !in_array(strtolower((string)($parts['scheme'] ?? '')), ['http', 'https'], true) || empty($parts['host'])) {
        return false;
    }
    $ip = gethostbyname((string)$parts['host']);
    return (bool)filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
}

function fetch_article_context(string $url): string
{
    if (!is_safe_public_url($url)) {
        return '';
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'bisped.net-bot/1.0 (+https://bisped.net)',
    ]);
    $body = curl_exec($ch);
    $mime = strtolower((string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
    if (!is_string($body) || $body === '' || strlen($body) > 3 * 1024 * 1024 || !str_contains($mime, 'text/html')) {
        return '';
    }

    $previousLibxml = libxml_use_internal_errors(true);
    libxml_clear_errors();
    try {
        $document = new DOMDocument();
        if (!$document->loadHTML($body, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return '';
        }
        foreach (['script', 'style', 'nav', 'footer', 'noscript'] as $tag) {
            $nodes = $document->getElementsByTagName($tag);
            while ($nodes->length > 0) {
                $node = $nodes->item(0);
                $node?->parentNode?->removeChild($node);
            }
        }
        $article = $document->getElementsByTagName('article')->item(0);
        return clean_text((string)($article?->textContent ?: $document->textContent), 6000);
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxml);
    }
}

function localize_image(string $url, string $slug): string
{
    if ($url === '' || str_starts_with($url, '/')) {
        return $url;
    }
    if (!is_safe_public_url($url)) {
        return '';
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'bisped.net-bot/1.0 (+https://bisped.net)',
    ]);
    $body = curl_exec($ch);
    $mime = strtolower((string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
    if (!is_string($body) || $body === '' || strlen($body) > 5 * 1024 * 1024) {
        return '';
    }
    $extension = match (strtok($mime, ';')) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        default => '',
    };
    if ($extension === '') {
        return '';
    }
    $subdir = '/media/blog/' . date('Y/m');
    $directory = dirname(__DIR__, 2) . '/public' . $subdir;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return '';
    }
    $filename = $slug . '-' . substr(hash('sha256', $url), 0, 10) . '.' . $extension;
    if (file_put_contents($directory . '/' . $filename, $body, LOCK_EX) === false) {
        return '';
    }
    return $subdir . '/' . $filename;
}

function absolute_url(string $url, string $baseUrl): string
{
    $url = trim($url);
    if ($url === '' || preg_match('#^https?://#i', $url)) {
        return $url;
    }
    $parts = parse_url($baseUrl);
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
        return $url;
    }
    if (str_starts_with($url, '//')) {
        return $parts['scheme'] . ':' . $url;
    }
    if (str_starts_with($url, '/')) {
        return $parts['scheme'] . '://' . $parts['host'] . $url;
    }
    $path = isset($parts['path']) ? rtrim(dirname($parts['path']), '/') : '';
    return $parts['scheme'] . '://' . $parts['host'] . $path . '/' . $url;
}

function fallback_image_for_category(string $category, string $brand = ''): string
{
    $brandKey = strtolower($brand);
    if (str_contains($brandKey, 'samsung')) {
        return '/media/products/samsung-galaxy-s25-256gb.jpg';
    }
    if (str_contains($brandKey, 'apple')) {
        return '/media/products/apple-iphone-16-128gb.jpg';
    }
    if (str_contains($brandKey, 'google')) {
        return '/media/products/google-pixel-9-128gb.jpg';
    }
    if (str_contains($brandKey, 'xiaomi')) {
        return '/media/products/xiaomi-14t-pro-512gb.jpg';
    }
    if (str_contains($brandKey, 'nvidia') || str_contains($brandKey, 'asus')) {
        return '/media/banners/banner-gaming-rig.jpg';
    }

    return match ($category) {
        'gaming' => '/media/banners/banner-gaming-rig.jpg',
        'connettivita' => '/media/brands/tim.png',
        'energia' => '/media/brands/enel.png',
        'informatica' => '/media/banners/pc-gaming-1.png',
        'smartphone' => '/media/banners/banner-xiaomi.jpg',
        default => '/media/bisped/fronte_negozio_bisped.png',
    };
}

function rss_entry_link(SimpleXMLElement $entry): string
{
    $link = (string)($entry->link ?? '');
    if ($link !== '') {
        return $link;
    }
    foreach ($entry->link as $node) {
        $attrs = $node->attributes();
        if (isset($attrs['href'])) {
            return (string)$attrs['href'];
        }
    }
    return (string)($entry->id ?? '');
}

function rss_entry_image(SimpleXMLElement $entry): string
{
    $namespaces = $entry->getNamespaces(true);
    foreach (['media', 'itunes'] as $nsKey) {
        if (!isset($namespaces[$nsKey])) {
            continue;
        }
        $ns = $entry->children($namespaces[$nsKey]);
        foreach (['content', 'thumbnail', 'image'] as $tag) {
            if (!isset($ns->{$tag})) {
                continue;
            }
            foreach ($ns->{$tag} as $node) {
                $attrs = $node->attributes();
                if (isset($attrs['url'])) {
                    return (string)$attrs['url'];
                }
                if (isset($attrs['href'])) {
                    return (string)$attrs['href'];
                }
            }
        }
    }

    foreach ($entry->enclosure ?? [] as $enclosure) {
        $attrs = $enclosure->attributes();
        $type = (string)($attrs['type'] ?? '');
        if (isset($attrs['url']) && str_starts_with($type, 'image/')) {
            return (string)$attrs['url'];
        }
    }

    $html = (string)($entry->description ?? $entry->summary ?? $entry->content ?? '');
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    return '';
}

function fetch_rss(string $url, array $keywords, string $category, string $brand, int $limit): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => 'bisped.net-bot/1.0 (+https://bisped.net)',
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);

    if (!$body) return [];

    $items = [];
    $previousLibxml = libxml_use_internal_errors(true);
    libxml_clear_errors();
    try {
        $xml = new SimpleXMLElement($body);
        $entries = $xml->channel->item ?? $xml->entry ?? [];
        foreach ($entries as $entry) {
            $title   = clean_text((string)($entry->title ?? ''), 200);
            $summary = clean_text((string)($entry->description ?? $entry->summary ?? ''), 500);
            $link    = rss_entry_link($entry);
            $pubDate = (string)($entry->pubDate ?? $entry->updated ?? date('Y-m-d'));
            $image   = absolute_url(rss_entry_image($entry), $url) ?: fallback_image_for_category($category, $brand);

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
                'image'   => $image,
                'kind'    => 'news',
            ];
        }
    } catch (Exception $e) {
        libxml_clear_errors();
        // Malformed XML — skip silently
    } finally {
        libxml_use_internal_errors($previousLibxml);
    }

    return array_slice($items, 0, $limit);
}

function fetch_scrape_summary(string $url, array $keywords, string $brand, string $category): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => 'bisped.net-bot/1.0 (+https://bisped.net)',
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);

    if (!$body) {
        return [];
    }

    $title = '';
    $summary = '';
    $image = '';
    if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i', $body, $m)) {
        $title = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i', $body, $m)
        || preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\']/i', $body, $m)) {
        $summary = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $body, $m)) {
        $image = absolute_url(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), $url);
    }

    $title = clean_text($title, 200);
    $summary = clean_text($summary, 500);
    if ($title === '' || mb_strlen($summary, 'UTF-8') < 100) {
        return [];
    }
    $text = strtolower($title . ' ' . $summary);
    $match = empty($keywords);
    foreach ($keywords as $kw) {
        if (str_contains($text, strtolower($kw))) {
            $match = true;
            break;
        }
    }
    if (!$match) {
        return [];
    }

    return [[
        'title' => $title,
        'summary' => $summary,
        'link' => $url,
        'date' => date('Y-m-d'),
        'image' => $image ?: fallback_image_for_category($category, $brand),
        'kind' => 'offer',
    ]];
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

function editorial_html_prompt(array $item, string $brand, string $category): string
{
    return <<<PROMPT
Sei la redazione di bisp&d, negozio e laboratorio tecnologico di Piombino. Trasforma la fonte in un articolo originale, utile e autosufficiente per il lettore. Non copiare frasi estese e non inventare prezzi, date, disponibilita o specifiche assenti dalla fonte.

Scrivi una versione completa in italiano, da 400-600 parole. Includi un punto di vista competente bisp&d, contesto pratico, cosa cambia davvero, a chi interessa, cosa verificare prima di acquistare e una conclusione utile. Usa solo HTML semantico: h2, h3, p, ul, ol, li, strong, em, blockquote. Non inserire link: la fonte viene citata separatamente dal sito.
Non ripetere, riassumere o commentare queste istruzioni. Il primo carattere della risposta deve essere `<` e il primo elemento deve essere un titolo `<h2>`.

TITOLO FONTE: {$item['title']}
BRAND: {$brand}
CATEGORIA: {$category}
SINTESI FONTE: {$item['summary']}
URL FONTE: {$item['link']}

Rispondi esclusivamente con l'HTML dell'articolo, senza markdown e senza spiegazioni.
PROMPT;
}

function editorial_translation_prompt(string $htmlIt): string
{
    return <<<PROMPT
Traduci integralmente in inglese l'articolo italiano seguente. Mantieni struttura, significato e tag HTML. Preserva esattamente brand, aziende, prodotti e nomi propri: per esempio `Altroconsumo` non deve essere tradotto. Non aggiungere commenti, scalette, markdown, link o informazioni nuove. Il primo carattere della risposta deve essere `<` e il primo elemento deve essere un titolo `<h2>`.

ARTICOLO ITALIANO:
{$htmlIt}

Rispondi esclusivamente con la traduzione HTML.
PROMPT;
}

function normalize_editorial_html(?string $raw): ?string
{
    if (!$raw) {
        return null;
    }
    $html = HtmlSanitizer::sanitize(trim($raw));
    $plainText = clean_text($html, 10000);
    $length = mb_strlen($plainText, 'UTF-8');
    $startsWithTitle = (bool)preg_match('/^\s*<h2(?:\s[^>]*)?>.+?<\/h2>/is', $html);
    $paragraphs = substr_count(strtolower($html), '<p');
    $echoesPrompt = (bool)preg_match('/(?:editorial team|semantic html|no inventing|rispondi esclusivamente|format:|main title|introduction:|target audience|pre-purchase|no links|check constraints|check length|html only|refining the|what changes:|who is it for\\?|first character|aiming for|headline:|key news:|key features|check html|wait, the source)/i', $plainText);
    if ($length >= 1200 && $startsWithTitle && $paragraphs >= 4 && !$echoesPrompt) {
        return $html;
    }
    if (!empty($GLOBALS['verbose'])) {
        echo "  → Editorial quality rejected: chars={$length}, h2=" . ($startsWithTitle ? 'yes' : 'no')
            . ", paragraphs={$paragraphs}, prompt_echo=" . ($echoesPrompt ? 'yes' : 'no') . "\n";
    }
    return null;
}

function editorial_title(string $html, string $fallback): string
{
    if (preg_match('/<h[12][^>]*>(.*?)<\/h[12]>/is', $html, $match)) {
        return clean_text($match[1], 200);
    }
    return clean_text($fallback, 200);
}

function editorial_snippet(string $html): string
{
    if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $html, $match)) {
        return clean_text($match[1], 260);
    }
    return clean_text($html, 260);
}

function generate_editorial(array $item, string $brand, string $category): ?array
{
    $htmlIt = normalize_editorial_html(llm_generate(editorial_html_prompt($item, $brand, $category), 2800, 'html'));
    if (!$htmlIt) {
        return null;
    }
    $htmlEn = normalize_editorial_html(llm_generate(editorial_translation_prompt($htmlIt), 2800, 'html'));
    if (!$htmlEn) {
        return null;
    }
    return [
        'title_it' => editorial_title($htmlIt, (string)$item['title']),
        'title_en' => editorial_title($htmlEn, (string)$item['title']),
        'snippet_it' => editorial_snippet($htmlIt),
        'snippet_en' => editorial_snippet($htmlEn),
        'html_it' => $htmlIt,
        'html_en' => $htmlEn,
    ];
}

function fallback_editorial(array $item, string $brand, string $category): array
{
    $title = clean_text((string)$item['title'], 200);
    $summary = clean_text((string)$item['summary'], 900);
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeSummary = htmlspecialchars($summary, ENT_QUOTES, 'UTF-8');
    $safeBrand = htmlspecialchars($brand, ENT_QUOTES, 'UTF-8');
    $safeCategory = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');

    $it = <<<HTML
<h2>{$safeTitle}: cosa significa davvero per chi acquista</h2>
<p>{$safeSummary}</p>
<p>Una novita tecnologica diventa utile solo quando si capisce quale problema risolve. Per questo la lettura di <strong>bisp&amp;d</strong> parte dai bisogni concreti: durata nel tempo, compatibilita con i dispositivi gia in uso, qualita dell'assistenza e rapporto tra spesa e vantaggi reali.</p>
<h3>Il punto della notizia</h3>
<p>La fonte segnala un aggiornamento legato a <strong>{$safeBrand}</strong> nell'area {$safeCategory}. Prima di decidere conviene distinguere il messaggio promozionale dagli elementi verificabili: caratteristiche incluse, eventuali vincoli, disponibilita effettiva e costi complessivi. Sono questi dettagli a determinare se la proposta e adatta a una famiglia, a un professionista o a un'attivita locale.</p>
<h3>Cosa controllare prima di scegliere</h3>
<ul><li>Confrontare il beneficio concreto con il prodotto o servizio gia utilizzato.</li><li>Verificare compatibilita, copertura, vincoli e costi accessori quando pertinenti.</li><li>Valutare assistenza post-vendita e possibilita di configurazione corretta.</li><li>Chiedere conferma delle condizioni aggiornate prima dell'acquisto.</li></ul>
<h3>Il punto di vista bisp&amp;d</h3>
<p>A Piombino vediamo ogni giorno che la scelta migliore non coincide automaticamente con la novita piu rumorosa. Il nostro lavoro e mettere la tecnologia nella situazione reale del cliente, chiarire cosa serve davvero e scartare cio che aggiunge costo senza valore. Se l'aggiornamento e pertinente al tuo caso, possiamo confrontarlo con alternative equivalenti e aiutarti a configurarlo bene fin dal primo giorno.</p>
<h3>Come orientarsi</h3>
<p>Porta con te dubbi, dispositivi gia presenti o l'ultima fattura se il tema riguarda servizi e tariffe. In negozio possiamo trasformare una notizia generica in una scelta consapevole, con una verifica puntuale delle condizioni disponibili al momento della richiesta.</p>
HTML;
    $en = <<<HTML
<h2>{$safeTitle}: what it really means before you buy</h2>
<p>{$safeSummary}</p>
<p>A technology update becomes useful only when it solves a real problem. At <strong>bisp&amp;d</strong>, we start from practical needs: long-term value, compatibility with the devices you already use, support quality and the balance between cost and meaningful benefits.</p>
<h3>The relevant part of the news</h3>
<p>The source highlights an update from <strong>{$safeBrand}</strong> in the {$safeCategory} area. Before making a decision, separate the promotional message from verifiable details: included features, possible constraints, actual availability and overall cost. These points determine whether an option fits a family, a professional or a local business.</p>
<h3>What to check first</h3>
<ul><li>Compare the practical benefit with your current product or service.</li><li>Check compatibility, coverage, constraints and additional costs where relevant.</li><li>Consider after-sales support and correct setup.</li><li>Confirm the latest conditions before purchasing.</li></ul>
<h3>The bisp&amp;d perspective</h3>
<p>In Piombino, we see every day that the best choice is not automatically the loudest new release. Our job is to place technology in the customer's real situation, clarify what is useful and remove unnecessary cost. If this update is relevant to you, we can compare equivalent alternatives and help configure the right solution from day one.</p>
<h3>How to make a clear decision</h3>
<p>Bring your questions, your existing devices or your latest bill when services and tariffs are involved. In store, we can turn a general news item into an informed choice and verify the conditions available when you ask.</p>
HTML;
    return [
        'title_it' => $title,
        'title_en' => "{$brand}: a practical guide to the latest update",
        'snippet_it' => mb_substr($summary, 0, 240, 'UTF-8'),
        'snippet_en' => "A practical bisp&d guide to the latest {$brand} update: what changed, what to check and how to make an informed choice.",
        'html_it' => HtmlSanitizer::sanitize($it),
        'html_en' => HtmlSanitizer::sanitize($en),
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// MAIN INGESTION LOOP
// ─────────────────────────────────────────────────────────────────────────────
$totalProcessed = 0;
$totalInserted  = 0;
$totalGenerationAttempts = 0;

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
            $items = fetch_rss($url, $keywords, $category, $brand, $limit);
        } elseif ($srcType === 'scrape') {
            $items = fetch_scrape_summary($url, $keywords, $brand, $category);
        } elseif ($srcType === 'api' && ($source['arera_source'] ?? false)) {
            if ($verbose) echo "  → Structured API adapter not configured: skipped\n";
            continue;
        }

        if (empty($items)) {
            if ($verbose) echo "  → No matching items\n";
            continue;
        }

        foreach ($items as $item) {
            $totalProcessed++;

            $sourceUrl = canonical_source_url((string)$item['link']);
            $fingerprint = source_fingerprint($item);
            $postSlug = auto_slug(substr($item['title'], 0, 80)) . '-' . $item['date'];

            // A static offers page is a source, not a daily news item. Republish
            // only when its extracted content changes.
            $stmt = $pdo->prepare("SELECT id FROM blog_posts WHERE slug = ? OR source_url = ? OR source_fingerprint = ? LIMIT 1");
            $stmt->execute([$postSlug, $sourceUrl, $fingerprint]);
            $row = $stmt->fetch();
            if ($row) {
                if ($verbose) echo "  → Already imported source: {$postSlug}\n";
                continue;
            }

            if (($item['kind'] ?? '') === 'offer'
                && !preg_match('/(?:\\d+[,.]?\\d*\\s*(?:€|euro|gb|giga|mbps|mese)|promo|sconto|offerta|fibra|5g)/iu', (string)$item['title'] . ' ' . (string)$item['summary'])) {
                if ($verbose) echo "  → Offer page without a measurable update: skipped\n";
                continue;
            }

            $sourceContext = fetch_article_context($sourceUrl);
            if ($sourceContext !== '') {
                $item['summary'] = clean_text((string)$item['summary'] . ' ' . $sourceContext, 6000);
            }

            if (!$dryRun) {
                $totalGenerationAttempts++;
            }
            $article = $dryRun ? fallback_editorial($item, $brand, $category) : generate_editorial($item, $brand, $category);
            if (!$article) {
                echo "  → Editorial LLM unavailable or below quality threshold: skipped\n";
                if ($totalGenerationAttempts >= $limit) {
                    break 3;
                }
                continue;
            }

            if (!$dryRun) {
                $imageUrl = localize_image((string)($item['image'] ?? ''), $postSlug)
                    ?: fallback_image_for_category($category, $brand);
                $pdo->prepare("
                    INSERT INTO blog_posts
                        (slug, title, title_en, published_at, image_url, snippet, snippet_en,
                         content_html, content_html_en, is_published, related_product_tags,
                         source_url, source_fingerprint, auto_generated)
                    VALUES (?,?,?,?,?,?,?,?,?,1,?,?,?,1)
                ")->execute([
                    $postSlug,
                    $article['title_it'],
                    $article['title_en'],
                    $item['date'],
                    $imageUrl,
                    clean_text((string)$article['snippet_it'], 260),
                    clean_text((string)$article['snippet_en'], 260),
                    $article['html_it'],
                    $article['html_en'],
                    implode(',', $keywords),
                    $sourceUrl,
                    $fingerprint,
                ]);

                db_log($pdo, 'blog_post_created', 'blog_post', $postSlug,
                    "Auto-generated from {$brand} RSS: {$item['title']}");
                $totalInserted++;
            }

            echo "  + " . ($dryRun ? '[DRY] ' : '') . "Post: {$postSlug}\n";
            if ($totalInserted >= $limit) {
                break 3;
            }
        }
    }
}

echo "\n─────────────────────────────────────────────────\n";
echo "Processed: {$totalProcessed} | Inserted: {$totalInserted}" . ($dryRun ? ' [DRY RUN]' : '') . "\n";
echo "Editorial attempts: {$totalGenerationAttempts}\n";
echo "Done at " . date('Y-m-d H:i:s') . "\n";
