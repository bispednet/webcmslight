<?php
/** @var array $product */

use App\Support\AdminMode;

$iconKey = $product['icon_key'] ?? 'chip';
$productIdentifier = $product['slug'] ?? (string)($product['id'] ?? '');
$content = (string)($product['content_html'] ?? '');
$category = trim((string)($product['category'] ?? '')) ?: 'Catalogo';
$price = $product['sale_price'] ?? $product['price'] ?? null;
$status = trim((string)($product['stock_status'] ?? '')) ?: null;
$campaign = trim((string)($product['campaign_label'] ?? '')) ?: 'Chiedi disponibilita';

if ($category === 'Catalogo' && preg_match('/Categoria:\\s*([^<]+)/i', $content, $match)) {
    $category = trim($match[1]);
}
if (!$price && preg_match('/Prezzo (?:WooCommerce|indicativo):\\s*EUR\\s*([^<]+)/i', $content, $match)) {
    $price = trim($match[1]);
}
if (!$status && preg_match('/(?:Stato importato|Disponibilita):\\s*([^<]+)/i', $content, $match)) {
    $status = trim($match[1]);
}

?>

<div class="h-full bg-glass border border-stroke rounded-3xl p-6 flex flex-col hover:border-pri/70 transition-all duration-300 transform hover:-translate-y-1 shadow-deep backdrop-blur-lg">
    <div class="mb-4 flex items-start justify-between gap-3">
        <span class="rounded-full bg-pri/10 px-3 py-1 text-[11px] font-black uppercase tracking-widest text-pri"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></span>
        <?= icon_svg($iconKey, 'h-7 w-7 text-pri'); ?>
    </div>
    <h3 class="font-black text-xl text-acc"<?= AdminMode::dataAttrs('products', 'name', $productIdentifier); ?><?= AdminMode::isAdmin() ? ' class="admin-editable-text"' : ''; ?>>
        <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>
    </h3>
    <p class="text-muted text-sm mt-2 flex-grow"<?= AdminMode::dataAttrs('products', 'description', $productIdentifier); ?><?= AdminMode::isAdmin() ? ' class="admin-editable-text"' : ''; ?>>
        <?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8'); ?>
    </p>
    <div class="mt-5 flex items-end justify-between gap-3">
        <div>
            <?php if ($price): ?>
                <div class="text-2xl font-black text-acc">&euro; <?= htmlspecialchars(rtrim(rtrim((string)$price, '0'), '.'), ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($status): ?>
                <div class="mt-1 text-xs font-bold uppercase tracking-widest <?= $status === 'Disponibile' ? 'text-cy' : 'text-muted'; ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <div class="mt-2 text-[11px] font-black uppercase tracking-widest text-yl">Promo: <?= htmlspecialchars($campaign, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <span class="text-pri font-black text-sm">
            Dettagli &rarr;
        </span>
    </div>
</div>
