<?php
/** @var array $product */
use App\Support\AdminMode;

$slug       = (string)($product['slug'] ?? '');
$name       = (string)($product['name'] ?? '');
$category   = trim((string)($product['category'] ?? '')) ?: 'Catalogo';
$imgUrl     = trim((string)($product['image_url'] ?? ''));
$sku        = trim((string)($product['sku'] ?? ''));
$contentHtml = (string)($product['content_html'] ?? '');
$features   = $product['features'] ?? [];
$tags       = array_filter(array_map('trim', explode(',', (string)($product['tags'] ?? ''))));

// Clean imported WC content
$contentHtml = str_replace(
    ['Prodotto ', ' importato dal WooCommerce Bisped', 'Scheda importata dal catalogo WooCommerce Bisped.',
     'Prezzo WooCommerce:', 'Stato importato:', 'SKU:', 'Categoria:'],
    ['', '', '', 'Prezzo indicativo:', 'Disponibilità:', 'Codice:', 'Reparto:'],
    $contentHtml
);

// Price
$regularPrice = $product['price']      ?? null;
$salePrice    = $product['sale_price'] ?? null;
$displayPrice = $salePrice ?: $regularPrice;
$isSale = $salePrice && $regularPrice && (float)$salePrice < (float)$regularPrice;

$priceFormatted = $displayPrice ? number_format((float)$displayPrice, 2, ',', '.') : null;
$oldFormatted   = $isSale ? number_format((float)$regularPrice, 2, ',', '.') : null;

// Stock
$rawStock = strtolower(trim((string)($product['stock_status'] ?? '')));
if (in_array($rawStock, ['instock', 'in-stock', 'disponibile', '1'], true)) {
    $stockClass = 'badge-stock--in'; $stockLabel = 'Disponibile';
} elseif ($rawStock === '') {
    $stockClass = 'badge-stock--ask'; $stockLabel = 'Su richiesta';
} else {
    $stockClass = 'badge-stock--out'; $stockLabel = 'Non disponibile';
}

// Share
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'bisped.net';
$shareUrl = $scheme . '://' . $host . '/products/' . $slug;
?>

<!-- Breadcrumb -->
<nav class="flex items-center gap-2 text-sm text-muted mb-8" aria-label="Breadcrumb">
    <a href="/" class="hover:text-pri transition-colors">Home</a>
    <span>/</span>
    <a href="/products" class="hover:text-pri transition-colors">Shop</a>
    <span>/</span>
    <a href="/products#<?= strtolower(htmlspecialchars($category, ENT_QUOTES, 'UTF-8')) ?>" class="hover:text-pri transition-colors"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></a>
    <span>/</span>
    <span style="color:var(--c-acc)" class="truncate max-w-xs"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></span>
</nav>

<div class="grid gap-10 lg:grid-cols-[420px_1fr] lg:items-start" data-animate>

    <!-- Image panel -->
    <div class="sticky top-24">
        <div class="rounded-lg border overflow-hidden bg-white" style="border-color:var(--c-border);aspect-ratio:1/1">
            <?php if ($imgUrl !== ''): ?>
                <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                     class="w-full h-full object-contain p-8"
                     loading="eager" width="420" height="420">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-20 h-20 opacity-20">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                    </svg>
                </div>
            <?php endif; ?>
        </div>

        <!-- Social share -->
        <div class="mt-4 flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-widest text-muted">Condividi:</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>"
               target="_blank" rel="noopener"
               class="flex items-center gap-1.5 header-util-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12Z"/></svg>
                FB
            </a>
            <a href="https://wa.me/?text=<?= urlencode($name . ' - ' . $shareUrl) ?>"
               target="_blank" rel="noopener"
               class="flex items-center gap-1.5 header-util-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                WA
            </a>
        </div>
    </div>

    <!-- Info panel -->
    <div>
        <!-- Category + stock -->
        <div class="flex items-center gap-3 flex-wrap mb-4">
            <span class="product-card__cat"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="badge-stock <?= $stockClass ?>"><?= htmlspecialchars($stockLabel, ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($sku): ?>
                <span class="text-xs text-muted font-mono">SKU: <?= htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <h1 class="font-display text-2xl font-black leading-tight md:text-3xl"
            style="color:var(--c-acc)"
            <?= AdminMode::dataAttrs('products', 'name', $slug) ?>>
            <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
        </h1>

        <!-- Price -->
        <div class="mt-6 p-5 rounded-lg border" style="border-color:var(--c-border);background:var(--c-surface)">
            <?php if ($priceFormatted): ?>
                <?php if ($oldFormatted): ?>
                    <div class="text-sm text-muted line-through mb-1">€ <?= htmlspecialchars($oldFormatted, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <div class="font-display text-4xl font-black" style="color:<?= $isSale ? 'var(--bisped-red)' : 'var(--c-acc)' ?>">
                    <sup class="text-lg align-super">€</sup><?= htmlspecialchars($priceFormatted, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="text-xs text-muted mt-1">IVA inclusa — prezzo indicativo, conferma in negozio</div>
            <?php else: ?>
                <div class="font-display text-2xl font-black" style="color:var(--c-muted)">Richiedi disponibilità e prezzo</div>
            <?php endif; ?>
        </div>

        <!-- CTA buttons -->
        <div class="mt-5 flex flex-col sm:flex-row gap-3">
            <a href="/contatti?prodotto=<?= urlencode($name) ?>&sku=<?= urlencode($sku) ?>"
               class="btn-primary" style="justify-content:center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                </svg>
                Richiedi disponibilità / preventivo
            </a>
            <a href="tel:+393346582116" class="btn-outline" style="justify-content:center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 6Z"/>
                </svg>
                Chiama il negozio
            </a>
        </div>

        <?php if ($contentHtml && strip_tags($contentHtml) !== ''): ?>
        <!-- Description -->
        <div class="mt-8 border-t pt-6" style="border-color:var(--c-border)"
             <?= AdminMode::dataAttrs('products', 'content_html', $slug, 'html') ?>>
            <h2 class="text-sm font-black uppercase tracking-widest mb-4" style="color:var(--c-muted)">Dettagli prodotto</h2>
            <div class="text-sm leading-6" style="color:var(--c-txt)">
                <?= $contentHtml ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($features)): ?>
        <!-- Features -->
        <div class="mt-6 border-t pt-6" style="border-color:var(--c-border)">
            <h2 class="text-sm font-black uppercase tracking-widest mb-4" style="color:var(--c-muted)">Punti chiave</h2>
            <ul class="space-y-2.5">
                <?php foreach ($features as $feat): ?>
                    <li class="flex items-start gap-2.5 text-sm" style="color:var(--c-txt)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0 mt-0.5" style="color:var(--c-stock-ok)">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                        <?= htmlspecialchars($feat, ENT_QUOTES, 'UTF-8') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($tags)): ?>
        <div class="mt-6 flex flex-wrap gap-2">
            <?php foreach ($tags as $tag): ?>
                <span class="text-xs px-3 py-1 rounded-full font-bold text-muted"
                      style="background:var(--c-surface);border:1px solid var(--c-border)">
                    #<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Back to shop -->
<div class="mt-12 pt-8 border-t" style="border-color:var(--c-border)">
    <a href="/products" class="btn-outline">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        Torna al catalogo
    </a>
</div>
