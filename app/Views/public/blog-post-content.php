<?php
/** @var array  $post */
/** @var array  $relatedProducts */

use App\Core\Database;
use App\Support\HtmlSanitizer;
use App\Support\I18n;

$locale = I18n::currentLocale();
$imgUrl  = trim((string)($post['image_url'] ?? ''));
$rawTitle = $locale === 'en' && trim((string)($post['title_en'] ?? '')) !== '' ? $post['title_en'] : $post['title'];
$rawSnippet = $locale === 'en' && trim((string)($post['snippet_en'] ?? '')) !== '' ? $post['snippet_en'] : ($post['snippet'] ?? '');
$rawContent = $locale === 'en' && trim((string)($post['content_html_en'] ?? '')) !== '' ? $post['content_html_en'] : $post['content_html'];
$decodeEntities = static function (string $value): string {
    for ($iteration = 0; $iteration < 3; $iteration++) {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($decoded === $value) break;
        $value = $decoded;
    }
    return $value;
};
$title   = htmlspecialchars($decodeEntities((string)$rawTitle), ENT_QUOTES, 'UTF-8');
$date    = htmlspecialchars(date('d F Y', strtotime($post['published_at'])), ENT_QUOTES, 'UTF-8');
$snippet = htmlspecialchars($decodeEntities((string)$rawSnippet), ENT_QUOTES, 'UTF-8');
$content = HtmlSanitizer::sanitize($decodeEntities((string)$rawContent));
$blogUrl = $locale === 'en' ? '/en/blog' : '/blog';

// Dynamic related products via tag overlap
$relatedProducts = $relatedProducts ?? [];
if (empty($relatedProducts) && !empty($post['related_product_tags'])) {
    $postTags = array_map('trim', explode(',', (string)$post['related_product_tags']));
    $pdo = Database::connection();
    $rows = $pdo->query("SELECT id, name, slug, sale_price, price, campaign_label, tags FROM products ORDER BY featured_order ASC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $productTags = array_map('trim', explode(',', (string)($row['tags'] ?? '')));
        $overlap = array_intersect($postTags, $productTags);
        if (count($overlap) >= 1) {
            $relatedProducts[] = $row;
            if (count($relatedProducts) >= 3) break;
        }
    }
}
?>

<article class="max-w-3xl mx-auto" data-animate>

    <!-- Back -->
    <a href="<?= $blogUrl ?>" class="inline-flex items-center gap-2 text-sm font-bold uppercase tracking-widest mb-8 transition-colors hover:text-red-400" style="color:var(--bisped-red)">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        Torna al blog
    </a>

    <!-- Header -->
    <header class="mb-8">
        <p class="text-xs font-bold uppercase tracking-widest mb-4" style="color:var(--bisped-red)"><?= $date ?></p>
        <h1 class="font-display text-3xl font-black leading-tight md:text-4xl md:leading-tight" style="color:var(--c-acc)"><?= $title ?></h1>
        <?php if ($snippet): ?>
            <p class="mt-4 text-lg leading-7" style="color:var(--c-muted)"><?= $snippet ?></p>
        <?php endif; ?>
    </header>

    <!-- Cover image -->
    <?php if ($imgUrl !== ''): ?>
        <div class="mb-8 rounded-lg overflow-hidden border" style="border-color:var(--c-border);aspect-ratio:16/7">
            <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= $title ?>"
                 class="w-full h-full object-cover" loading="lazy">
        </div>
    <?php endif; ?>

    <!-- Content -->
    <div class="info-card blog-body">
        <?= $content ?>
    </div>

    <?php if (!empty($post['source_url'])): ?>
        <p class="mt-5 text-xs" style="color:var(--c-muted)">
            <a href="<?= htmlspecialchars((string)$post['source_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="underline hover:text-red-400">
                <?= $locale === 'en' ? 'Original source' : 'Fonte originale' ?> ↗
            </a>
        </p>
    <?php endif; ?>

    <!-- Related products -->
    <?php if (!empty($relatedProducts)): ?>
    <div class="mt-12" data-animate>
        <p class="section-label mb-4">Prodotti correlati</p>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($relatedProducts as $rp):
                $rpName     = htmlspecialchars($rp['name'], ENT_QUOTES, 'UTF-8');
                $rpSlug     = htmlspecialchars($rp['slug'], ENT_QUOTES, 'UTF-8');
                $rpCampaign = htmlspecialchars($rp['campaign_label'] ?? '', ENT_QUOTES, 'UTF-8');
                $rpSale     = $rp['sale_price'] ? number_format((float)$rp['sale_price'], 2, ',', '.') . ' €' : null;
                $rpPrice    = $rp['price']      ? number_format((float)$rp['price'],      2, ',', '.') . ' €' : null;
            ?>
            <a href="/products/<?= $rpSlug ?>" class="service-card group block">
                <h3 class="font-display font-black text-base mb-2 group-hover:text-red-400 transition-colors" style="color:var(--c-acc)"><?= $rpName ?></h3>
                <?php if ($rpCampaign): ?>
                    <span class="campaign-badge mb-2 inline-block"><?= $rpCampaign ?></span>
                <?php endif; ?>
                <div class="flex items-baseline gap-2 mt-2">
                    <?php if ($rpSale): ?>
                        <span class="font-bold" style="color:var(--bisped-red)"><?= $rpSale ?></span>
                    <?php endif; ?>
                    <?php if ($rpPrice && $rpSale): ?>
                        <span class="text-xs line-through" style="color:var(--c-muted)"><?= $rpPrice ?></span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Back link bottom -->
    <div class="mt-10 pt-8 border-t" style="border-color:var(--c-border)">
        <a href="<?= $blogUrl ?>" class="btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Tutti gli articoli
        </a>
    </div>

</article>
