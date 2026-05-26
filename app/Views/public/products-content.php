<?php
use App\Core\View;

/** @var array $products */

$categories = [
    'informatica' => [
        'label'    => 'Informatica',
        'subtitle' => 'Notebook, monitor, componenti e hardware per lavoro e casa.',
        'matches'  => ['informatica', 'monitor', 'notebook', 'catalogo', 'pc'],
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3"/></svg>',
    ],
    'smartphone' => [
        'label'    => 'Smartphone',
        'subtitle' => 'Dispositivi, accessori e telefonia con configurazione assistita.',
        'matches'  => ['smartphone', 'telefonia', 'mobile'],
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>',
    ],
    'gaming' => [
        'label'    => 'Gaming',
        'subtitle' => 'Rig, periferiche e setup per chi vuole prestazioni vere.',
        'matches'  => ['gaming', 'gamingpc', 'cuffie', 'tastiere', 'periferiche'],
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 0 1-.657.643 48.39 48.39 0 0 1-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 0 1-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 0 0-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 0 1-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 0 0 .657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 0 1-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 0 0 5.427-.63 48.05 48.05 0 0 0 .582-4.717.532.532 0 0 0-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.959.401v0a.656.656 0 0 0 .658-.663 48.422 48.422 0 0 0-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 0 1-.61-.58v0Z"/></svg>',
    ],
];

$groupedProducts = ['informatica' => [], 'smartphone' => [], 'gaming' => []];

foreach ($products as $product) {
    $cat = strtolower(trim((string)($product['category'] ?? 'catalogo')));
    $placed = false;
    foreach ($categories as $key => $group) {
        foreach ($group['matches'] as $m) {
            if (str_contains($cat, $m)) {
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
?>

<div class="space-y-16">

    <!-- Page Header -->
    <section class="py-8 tech-grid rounded-lg border px-8" style="border-color:var(--c-border);background:var(--c-surface)" data-animate>
        <div class="section-label mb-5">Shop Bisped</div>
        <h1 class="font-display text-4xl font-black md:text-5xl lg:text-6xl leading-none" style="color:var(--c-acc)">
            Prodotti selezionati,<br>
            <span style="color:var(--bisped-red)">persone a disposizione.</span>
        </h1>
        <p class="mt-5 max-w-2xl text-muted text-lg">
            Sfoglia per reparto, chiedi disponibilità e fatti consigliare.
            Un prezzo è utile; una scelta corretta vale di più.
        </p>

        <!-- Category pills -->
        <div class="mt-7 flex flex-wrap gap-2">
            <a href="#tutti" class="cat-pill active">
                Tutti
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background:rgba(209,25,32,.15);color:var(--bisped-red)"><?= $totalCount ?></span>
            </a>
            <?php foreach ($categories as $key => $cat): ?>
                <a href="#<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" class="cat-pill">
                    <?= $cat['icon'] ?>
                    <?= htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8') ?>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background:rgba(255,255,255,.07);color:var(--c-muted)">
                        <?= count($groupedProducts[$key]) ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Product sections by category -->
    <?php foreach ($categories as $key => $catData):
        $items = $groupedProducts[$key];
        if (empty($items)) continue;
    ?>
        <section id="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" class="scroll-mt-20" data-animate>
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
                <a href="/contatti" class="text-sm font-black uppercase tracking-widest transition-colors" style="color:var(--bisped-red)">
                    Consulenza reparto →
                </a>
            </div>

            <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                <?php foreach ($items as $index => $product): ?>
                    <a href="/products/<?= htmlspecialchars($product['slug'], ENT_QUOTES, 'UTF-8') ?>"
                       style="text-decoration:none"
                       data-animate
                       data-animate-delay="<?= min($index * 50, 400) ?>">
                        <?php View::renderPartial('public/partials/product-card', ['product' => $product]); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <!-- CTA bottom -->
    <section class="cta-strip" data-animate>
        <div class="grid md:grid-cols-[1fr_auto] gap-6 items-center">
            <div>
                <h2 class="font-display text-2xl font-black" style="color:var(--c-acc)">Non trovi quello che cerchi?</h2>
                <p class="mt-2 text-muted">Bisped può ordinare qualsiasi prodotto tech. Contattaci con marca, modello o utilizzo previsto.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="/contatti" class="btn-primary">Richiedi un prodotto</a>
                <a href="/servizi" class="btn-outline">Servizi Bisped</a>
            </div>
        </div>
    </section>
</div>
