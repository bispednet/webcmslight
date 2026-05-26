<?php
use App\Core\View;

/** @var array $products */

$groups = [
    'gaming' => ['title' => 'Gaming', 'subtitle' => 'Rig, periferiche e setup per chi vuole prestazioni vere.', 'matches' => ['GamingPC', 'CUFFIE E MICROFONI', 'TASTIERE']],
    'smartphone' => ['title' => 'Smartphone', 'subtitle' => 'Dispositivi, accessori e telefonia con configurazione assistita.', 'matches' => ['SMARTPHONE']],
    'informatica' => ['title' => 'Informatica', 'subtitle' => 'Notebook, monitor, componenti e hardware per lavoro e casa.', 'matches' => ['Monitor', 'NOTEBOOK', 'Catalogo']],
];

$extractCategory = static function (array $product): string {
    if (!empty($product['category'])) {
        return trim((string)$product['category']);
    }
    $content = (string)($product['content_html'] ?? '');
    if (preg_match('/Categoria:\\s*([^<]+)/i', $content, $match)) {
        return trim($match[1]);
    }
    return 'Catalogo';
};

$grouped = ['gaming' => [], 'smartphone' => [], 'informatica' => []];
foreach ($products as $product) {
    $category = $extractCategory($product);
    $placed = false;
    foreach ($groups as $key => $group) {
        if (in_array($category, $group['matches'], true)) {
            $grouped[$key][] = $product;
            $placed = true;
            break;
        }
    }
    if (!$placed) {
        $grouped['informatica'][] = $product;
    }
}
?>

<div class="space-y-14" data-animate>
    <section class="rounded-[2rem] border border-stroke bg-glass p-8 text-center md:p-12">
        <p class="text-sm font-black uppercase tracking-[0.35em] text-pri">Shop Bisped</p>
        <h1 class="mx-auto mt-4 max-w-4xl text-4xl font-black text-acc md:text-6xl">Prodotti selezionati, persone a disposizione.</h1>
        <p class="mx-auto mt-5 max-w-3xl text-muted">Sfoglia il catalogo per reparto, chiedi disponibilita e fatti consigliare. Un prezzo e utile; una scelta corretta vale di piu.</p>
        <div class="mt-7 flex flex-wrap justify-center gap-3">
            <a href="#informatica" class="rounded-full border border-stroke px-5 py-2 text-sm font-black text-txt hover:border-pri hover:text-pri">Informatica</a>
            <a href="#smartphone" class="rounded-full border border-stroke px-5 py-2 text-sm font-black text-txt hover:border-pri hover:text-pri">Smartphone</a>
            <a href="#gaming" class="rounded-full border border-stroke px-5 py-2 text-sm font-black text-txt hover:border-pri hover:text-pri">Gaming</a>
        </div>
    </section>

    <?php foreach ($groups as $key => $group): ?>
        <section id="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" class="scroll-mt-28">
            <div class="mb-7 flex flex-col justify-between gap-3 md:flex-row md:items-end">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.35em] text-pri"><?= htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <h2 class="mt-2 text-3xl font-black text-acc"><?= htmlspecialchars($group['subtitle'], ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>
                <a href="/contatti" class="text-sm font-black text-pri hover:underline">Richiedi consulenza reparto</a>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($grouped[$key] as $index => $product): ?>
                    <div data-animate data-animate-delay="<?= $index * 70 ?>" class="h-full">
                        <a href="/products/<?= htmlspecialchars($product['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="block h-full">
                            <?php View::renderPartial('public/partials/product-card', ['product' => $product]); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
