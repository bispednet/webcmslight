<?php
use App\Core\View;

/** @var array $products */

$categories = [
    'smartphone' => [
        'label'    => 'Smartphone',
        'subtitle' => 'Gli ultimi modelli 2025-2026 con consulenza inclusa.',
        'matches'  => ['smartphone', 'telefonia', 'mobile'],
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>',
    ],
    'informatica' => [
        'label'    => 'Notebook & PC',
        'subtitle' => 'Notebook, monitor, componenti e hardware per lavoro e casa.',
        'matches'  => ['informatica', 'monitor', 'notebook', 'catalogo', 'pc', 'laptop'],
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3"/></svg>',
    ],
    'gaming' => [
        'label'    => 'Gaming',
        'subtitle' => 'Rig, periferiche e setup per prestazioni vere.',
        'matches'  => ['gaming', 'gamingpc', 'cuffie-gaming', 'periferiche'],
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 0 1-.657.643 48.39 48.39 0 0 1-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 0 1-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 0 0-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 0 1-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 0 0 .657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 0 1-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 0 0 5.427-.63 48.05 48.05 0 0 0 .582-4.717.532.532 0 0 0-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.959.401v0a.656.656 0 0 0 .658-.663 48.422 48.422 0 0 0-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 0 1-.61-.58v0Z"/></svg>',
    ],
    'audio' => [
        'label'    => 'Audio & Cuffie',
        'subtitle' => 'Cuffie wireless, auricolari e speaker di qualità.',
        'matches'  => ['audio', 'cuffie', 'auricolari', 'airpods', 'headphones'],
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z"/></svg>',
    ],
    'wearable' => [
        'label'    => 'Wearable & Tablet',
        'subtitle' => 'Smartwatch, fitness tracker e tablet.',
        'matches'  => ['wearable', 'smartwatch', 'tablet', 'watch', 'tracker'],
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>',
    ],
    'connettivita' => [
        'label'    => 'Connettività',
        'subtitle' => 'Router Wi-Fi 6/6E, modem e reti domestiche.',
        'matches'  => ['connettivita', 'router', 'wifi', 'modem', 'rete', 'fritz'],
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z"/></svg>',
    ],
];

// Sort products: by featured_order then name
usort($products, fn($a, $b) => ($a['featured_order'] ?? 99) <=> ($b['featured_order'] ?? 99));

$groupedProducts = array_fill_keys(array_keys($categories), []);

foreach ($products as $product) {
    $cat   = strtolower(trim((string)($product['category_slug'] ?? $product['category'] ?? 'informatica')));
    $tags  = strtolower((string)($product['tags'] ?? ''));
    $placed = false;
    foreach ($categories as $key => $group) {
        foreach ($group['matches'] as $m) {
            if (str_contains($cat, $m) || str_contains($tags, $m)) {
                $groupedProducts[$key][] = $product;
                $placed = true;
                break 2;
            }
        }
    }
    if (!$placed) {
        $groupedProducts['informatica'][] = $product;
    }
}

$totalCount = count($products);

// Products with campaign = candidates for bundle promo strip
$onSaleProducts = array_filter($products, fn($p) => !empty($p['sale_price']) && !empty($p['price']) && (float)$p['sale_price'] < (float)$p['price']);
$topDeal = array_slice(array_values($onSaleProducts), 0, 3);
?>

<div class="space-y-16">

    <!-- Header + search -->
    <section class="py-8 tech-grid rounded-lg border px-8" style="border-color:var(--c-border);background:var(--c-surface)" data-animate>
        <div class="section-label mb-5">Shop Bisped</div>
        <h1 class="font-display text-4xl font-black md:text-5xl lg:text-6xl leading-none" style="color:var(--c-acc)">
            Prodotti 2025–2026,<br>
            <span style="color:var(--bisped-red)">persone a disposizione.</span>
        </h1>
        <p class="mt-5 max-w-2xl text-muted text-lg">
            Sfoglia per reparto o cerca direttamente. Prezzi ufficiali con sconto vs listino.
        </p>

        <!-- Live search -->
        <div class="mt-6 relative max-w-md">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                 class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" style="color:var(--c-muted)">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input id="product-search"
                   type="search"
                   placeholder="Cerca prodotto, marca, categoria…"
                   class="form-input pl-9 w-full text-sm"
                   autocomplete="off">
        </div>

        <!-- Category pills -->
        <div class="mt-5 flex flex-wrap gap-2" id="cat-pills">
            <button type="button" class="cat-pill active" data-filter="all">
                Tutti
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background:rgba(209,25,32,.15);color:var(--bisped-red)"><?= $totalCount ?></span>
            </button>
            <?php foreach ($categories as $key => $cat): if (empty($groupedProducts[$key])) continue; ?>
                <button type="button" class="cat-pill" data-filter="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                    <?= $cat['icon'] ?>
                    <?= htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8') ?>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background:rgba(255,255,255,.07);color:var(--c-muted)">
                        <?= count($groupedProducts[$key]) ?>
                    </span>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- No results message (hidden by default) -->
        <p id="no-results" class="hidden mt-5 text-sm" style="color:var(--c-muted)">
            Nessun prodotto trovato. <a href="/contatti" style="color:var(--bisped-red)">Richiedicelo →</a>
        </p>
    </section>

    <!-- Promo bundle strip -->
    <?php if (!empty($topDeal)): ?>
    <section class="info-card" style="border-color:rgba(209,25,32,.3);border-left:3px solid var(--bisped-red)" data-animate>
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <div>
                <span class="campaign-badge">Sconti attivi</span>
                <h2 class="font-display text-xl font-black mt-2" style="color:var(--c-acc)">Le migliori offerte del momento</h2>
                <p class="text-sm mt-1" style="color:var(--c-muted)">Prezzi Amazon confrontati con il listino ufficiale del produttore. Fino ad esaurimento scorte.</p>
            </div>
            <a href="/contatti" class="btn-primary btn-sm">Prenota ora</a>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <?php foreach ($topDeal as $deal):
                $pct = $deal['price'] > 0 ? (int)round((1 - $deal['sale_price'] / $deal['price']) * 100) : 0;
            ?>
            <a href="/products/<?= htmlspecialchars($deal['slug'], ENT_QUOTES, 'UTF-8') ?>" class="service-card group flex-1">
                <div class="flex items-center justify-between mb-2">
                    <span class="campaign-badge">-<?= $pct ?>%</span>
                </div>
                <h3 class="font-display font-black text-sm group-hover:text-red-400 transition-colors mb-2" style="color:var(--c-acc)">
                    <?= htmlspecialchars($deal['name'], ENT_QUOTES, 'UTF-8') ?>
                </h3>
                <div class="flex items-baseline gap-2">
                    <span class="font-bold text-lg" style="color:var(--bisped-red)"><?= number_format((float)$deal['sale_price'], 2, ',', '.') ?> €</span>
                    <span class="text-xs line-through" style="color:var(--c-muted)"><?= number_format((float)$deal['price'], 2, ',', '.') ?> €</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Product sections -->
    <div id="products-container">
    <?php foreach ($categories as $key => $catData):
        $items = $groupedProducts[$key];
        if (empty($items)) continue;
    ?>
        <section id="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                 class="product-section scroll-mt-20 mb-12"
                 data-category="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                 data-animate>
            <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <div class="section-label mb-3">
                        <?= $catData['icon'] ?>
                        <?= htmlspecialchars($catData['label'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <h2 class="font-display text-2xl font-black md:text-3xl" style="color:var(--c-acc)">
                        <?= htmlspecialchars($catData['subtitle'], ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                </div>
                <a href="/contatti?topic=<?= rawurlencode($catData['label']) ?>" class="text-sm font-black uppercase tracking-widest transition-colors" style="color:var(--bisped-red)">
                    Consulenza reparto →
                </a>
            </div>

            <div class="product-grid grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                <?php foreach ($items as $index => $product): ?>
                    <a href="/products/<?= htmlspecialchars($product['slug'], ENT_QUOTES, 'UTF-8') ?>"
                       class="product-item"
                       data-name="<?= htmlspecialchars(strtolower($product['name']), ENT_QUOTES, 'UTF-8') ?>"
                       data-tags="<?= htmlspecialchars(strtolower($product['tags'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                       data-category="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                       style="text-decoration:none"
                       data-animate
                       data-animate-delay="<?= min($index * 50, 400) ?>">
                        <?php View::renderPartial('public/partials/product-card', ['product' => $product]); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
    </div>

    <!-- CTA bottom -->
    <section class="cta-strip" data-animate>
        <div class="grid md:grid-cols-[1fr_auto] gap-6 items-center">
            <div>
                <h2 class="font-display text-2xl font-black" style="color:var(--c-acc)">Non trovi quello che cerchi?</h2>
                <p class="mt-2 text-muted">Bisped può ordinare qualsiasi prodotto tech. Contattaci con marca, modello o utilizzo previsto.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="/contatti" class="btn-primary">Richiedi un prodotto</a>
                <a href="/teleassistenza" class="btn-outline">Teleassistenza</a>
            </div>
        </div>
    </section>

</div>

<script>
(function () {
    const search   = document.getElementById('product-search');
    const pills    = document.querySelectorAll('#cat-pills [data-filter]');
    const sections = document.querySelectorAll('.product-section');
    const noRes    = document.getElementById('no-results');
    let activeCat  = 'all';

    function update() {
        const q = search.value.toLowerCase().trim();
        let totalVisible = 0;

        sections.forEach(sec => {
            const catKey = sec.dataset.category;
            const items  = sec.querySelectorAll('.product-item');
            let secVisible = 0;

            items.forEach(item => {
                const nameMatch = item.dataset.name.includes(q);
                const tagsMatch = item.dataset.tags.includes(q);
                const catMatch  = activeCat === 'all' || item.dataset.category === activeCat;
                const show      = catMatch && (q === '' || nameMatch || tagsMatch);
                item.style.display = show ? '' : 'none';
                if (show) { secVisible++; totalVisible++; }
            });

            sec.style.display = secVisible > 0 ? '' : 'none';
        });

        noRes.classList.toggle('hidden', totalVisible > 0);
    }

    search.addEventListener('input', update);

    pills.forEach(pill => {
        pill.addEventListener('click', () => {
            pills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            activeCat = pill.dataset.filter;
            update();
            if (activeCat !== 'all') {
                const sec = document.getElementById(activeCat);
                if (sec) sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
})();
</script>
