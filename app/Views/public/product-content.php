<?php
/** @var array $product */

use App\Support\AdminMode;

$ctaLink = $product['cta_link'] ?? null;
$ctaText = $product['cta_text'] ?? null;
$features = $product['features'] ?? [];
$productId = $product['slug'] ?? (string)($product['id'] ?? '');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'solclawn.com';
$shareUrl = $scheme . '://' . $host . '/products/' . ($product['slug'] ?? '');
$contentHtml = (string)($product['content_html'] ?? '<p class="text-muted">Scheda in aggiornamento.</p>');
$contentHtml = str_replace(
    ['Prodotto ', ' importato dal WooCommerce Bisped', 'Scheda importata dal catalogo WooCommerce Bisped.', 'Prezzo WooCommerce:', 'Stato importato:'],
    ['', ' selezionato da Bisped', 'Scheda prodotto Bisped.', 'Prezzo indicativo:', 'Disponibilita:'],
    $contentHtml
);
$price = $product['sale_price'] ?? $product['price'] ?? null;
$tags = array_filter(array_map('trim', explode(',', (string)($product['tags'] ?? ''))));
?>

<div>
    <div class="text-center mb-12" data-animate>
        <a href="/products" class="text-pri font-semibold hover:underline mb-4 inline-block">&larr; Torna allo shop</a>
        <h1 class="text-4xl md:text-5xl font-extrabold text-acc tracking-tight"<?= AdminMode::dataAttrs('products', 'hero_title', $productId); ?><?= AdminMode::isAdmin() ? ' class="admin-editable-text"' : ''; ?>>
            <?= htmlspecialchars($product['hero_title'] ?: $product['name'], ENT_QUOTES, 'UTF-8'); ?>
        </h1>
        <?php if (!empty($product['hero_subtitle'])): ?>
            <p class="mt-4 max-w-3xl mx-auto text-lg text-muted"<?= AdminMode::dataAttrs('products', 'hero_subtitle', $productId); ?><?= AdminMode::isAdmin() ? ' class="admin-editable-text"' : ''; ?>>
                <?= htmlspecialchars($product['hero_subtitle'], ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="bg-glass border border-stroke rounded-lg p-8 md:p-12" data-animate>
        <div class="grid md:grid-cols-2 gap-12 items-start">
            <div class="space-y-4 prose prose-invert max-w-none"<?= AdminMode::dataAttrs('products', 'content_html', $productId, 'html'); ?>>
                <div class="not-prose mb-6 flex flex-wrap gap-3">
                    <?php if (!empty($product['category'])): ?>
                        <span class="rounded-full bg-pri/10 px-4 py-2 text-xs font-black uppercase tracking-widest text-pri"><?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                    <?php if ($price): ?>
                        <span class="rounded-full border border-stroke px-4 py-2 text-xs font-black uppercase tracking-widest text-acc">&euro; <?= htmlspecialchars(rtrim(rtrim((string)$price, '0'), '.'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($product['campaign_label'])): ?>
                        <span class="rounded-full border border-yl/40 bg-yl/10 px-4 py-2 text-xs font-black uppercase tracking-widest text-yl"><?= htmlspecialchars($product['campaign_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>
                <?= $contentHtml; ?>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl); ?>" target="_blank" rel="noopener" class="rounded-full border border-stroke px-4 py-2 text-sm font-bold text-txt hover:border-pri hover:text-pri">Condividi</a>
                    <a href="https://wa.me/?text=<?= urlencode(($product['name'] ?? 'Prodotto Bisped') . ' ' . $shareUrl); ?>" target="_blank" rel="noopener" class="rounded-full border border-stroke px-4 py-2 text-sm font-bold text-txt hover:border-pri hover:text-pri">WhatsApp</a>
                </div>
            </div>
            <?php if (!empty($features)): ?>
                <div>
                    <h3 class="text-2xl font-bold text-acc mb-4">Punti chiave</h3>
                    <ul class="space-y-3">
                        <?php foreach ($features as $feature): ?>
                            <li class="flex items-start gap-3">
                                <?= icon_svg('check', 'h-6 w-6 text-cy flex-shrink-0'); ?>
                                <span class="text-txt text-sm md:text-base"><?= htmlspecialchars($feature, ENT_QUOTES, 'UTF-8'); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($tags): ?>
                        <div class="mt-8 flex flex-wrap gap-2">
                            <?php foreach ($tags as $tag): ?>
                                <span class="rounded-full bg-bg2 px-3 py-1 text-xs font-bold text-muted">#<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($ctaLink && $ctaText): ?>
        <div class="mt-12 text-center" data-animate>
            <a href="<?= htmlspecialchars($ctaLink, ENT_QUOTES, 'UTF-8'); ?>?prodotto=<?= urlencode((string)($product['name'] ?? '')); ?>" class="bg-pri text-white font-bold py-3 px-8 rounded-full hover:bg-pri-700 transition-colors text-lg"<?= AdminMode::dataAttrs('products', 'cta_link', $productId, 'url'); ?>>
                <span<?= AdminMode::dataAttrs('products', 'cta_text', $productId); ?><?= AdminMode::isAdmin() ? ' class="admin-editable-text"' : ''; ?>>
                    <?= htmlspecialchars($ctaText, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </a>
        </div>
    <?php endif; ?>
</div>
