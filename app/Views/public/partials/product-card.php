<?php
/** @var array $product */
use App\Support\AdminMode;

if (!function_exists('bisped_product_category_label')) {
    function bisped_product_category_label(string $category): string
    {
        return match ($category) {
            'pc-assemblati', 'pc-assmblati' => 'PC',
            'pc-custom' => 'PC-Custom',
            default => $category,
        };
    }
}

$slug     = (string)($product['slug'] ?? '');
$name     = (string)($product['name'] ?? '');
$category = trim((string)($product['category'] ?? '')) ?: 'Catalogo';
$categoryLabel = bisped_product_category_label($category);
$imgUrl   = trim((string)($product['image_url'] ?? ''));
$sku      = trim((string)($product['sku'] ?? ''));

// Price logic
$regularPrice = $product['price'] ?? null;
$salePrice    = $product['sale_price'] ?? null;
$displayPrice = $salePrice ?: $regularPrice;
$isSale       = $salePrice && $regularPrice && (float)$salePrice < (float)$regularPrice;

// Stock
$rawStock = strtolower(trim((string)($product['stock_status'] ?? '')));
$stockQty = (int)($product['stock_qty'] ?? 0);
if (in_array($rawStock, ['instock', 'in-stock', 'disponibile', '1', 'true'], true)) {
    $stockClass = 'badge-stock--in';
    $stockLabel = $stockQty > 0 ? 'Disponibile · ' . $stockQty . ' pz' : 'Disponibile';
} elseif ($rawStock === '' || in_array($rawStock, ['onrequest', 'su richiesta'], true)) {
    $stockClass = 'badge-stock--ask';
    $stockLabel = 'Su richiesta';
} else {
    $stockClass = 'badge-stock--out';
    $stockLabel = 'Non disp.';
}

$priceFormatted = $displayPrice ? '€ ' . number_format((float)$displayPrice, 2, ',', '.') : null;
$oldFormatted   = $isSale ? '€ ' . number_format((float)$regularPrice, 2, ',', '.') : null;

// Category slug for link
$catSlugs = [
    'PC-Custom'   => 'pc-custom',
    'Gaming'      => 'gaming',
    'Smartphone'  => 'smartphone',
    'Informatica' => 'informatica',
    'Connettivit' => 'connettivita',
    'Energia'     => 'energia',
    'Servizi'     => 'servizi',
];
$catSlug = 'catalogo';
foreach ($catSlugs as $k => $v) {
    if (stripos($category, $k) !== false) { $catSlug = $v; break; }
}
?>

<div class="product-card">
    <?php if ($isSale): ?>
        <div class="ribbon-sale">Promo</div>
    <?php endif; ?>

    <div class="product-card__img-wrap">
        <?php if ($imgUrl !== ''): ?>
            <img
                class="product-card__img"
                src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>"
                alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                loading="lazy"
                width="300" height="300"
            >
        <?php else: ?>
            <div class="product-card__no-img">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="64" height="64">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z"/>
                </svg>
            </div>
        <?php endif; ?>
    </div>

    <div class="product-card__body">
        <div class="product-card__cat"><?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?></div>

        <h3 class="product-card__name"
            <?= AdminMode::dataAttrs('products', 'name', $slug) ?>>
            <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
        </h3>

        <div class="product-card__footer">
            <div>
                <?php if ($oldFormatted): ?>
                    <div class="product-card__price-old"><?= htmlspecialchars($oldFormatted, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($priceFormatted): ?>
                    <div class="product-card__price"><?= htmlspecialchars($priceFormatted, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="price-iva">IVA incl.</div>
                <?php else: ?>
                    <div class="product-card__price" style="font-size:14px;color:var(--c-muted)">Richiedi prezzo</div>
                <?php endif; ?>
                <div class="badge-stock <?= $stockClass ?>" style="margin-top:6px"><?= htmlspecialchars($stockLabel, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <span class="product-card__cta">Dettagli →</span>
        </div>
    </div>
</div>
