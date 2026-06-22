<?php
use App\Core\View;

/** @var array $landing */

$label = (string)($landing['label'] ?? '');
$intro = (string)($landing['intro'] ?? '');
$products = $landing['products'] ?? [];
$posts = $landing['posts'] ?? [];
?>

<div class="space-y-14">
    <section class="tech-grid rounded-lg border p-8 md:p-12" style="border-color:var(--c-border);background:var(--c-surface)" data-animate>
        <nav class="mb-6 flex items-center gap-2 text-sm text-muted" aria-label="Breadcrumb">
            <a href="/" class="hover:text-pri transition-colors">Home</a>
            <span>/</span>
            <a href="/products" class="hover:text-pri transition-colors">Shop</a>
            <span>/</span>
            <span style="color:var(--c-acc)"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
        </nav>

        <p class="section-label mb-5">Negozio tech a Piombino</p>
        <h1 class="max-w-4xl font-display text-4xl font-black tracking-tight md:text-5xl" style="color:var(--c-acc)">
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?> a Piombino
        </h1>
        <p class="mt-5 max-w-3xl text-lg leading-8" style="color:var(--c-muted)">
            <?= htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') ?>
            Da bisp&amp;d trovi vendita, configurazione, assistenza e consulenza prima dell'acquisto.
        </p>
        <div class="mt-7 flex flex-wrap gap-3">
            <a href="/contatti?topic=<?= urlencode($label) ?>" class="btn-primary">Chiedi disponibilita</a>
            <a href="/products" class="btn-outline">Vai al catalogo</a>
        </div>
    </section>

    <?php if (!empty($products)): ?>
        <section data-animate>
            <div class="mb-8 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="section-label mb-3">Prodotti collegati</p>
                    <h2 class="font-display text-3xl font-black" style="color:var(--c-acc)">
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?> disponibili o ordinabili
                    </h2>
                </div>
                <a href="/products?q=<?= urlencode($label) ?>" class="btn-outline btn-sm">Cerca nello shop</a>
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <?php foreach (array_slice($products, 0, 8) as $product): ?>
                    <a href="/products/<?= htmlspecialchars((string)$product['slug'], ENT_QUOTES, 'UTF-8') ?>" style="text-decoration:none">
                        <?php View::renderPartial('public/partials/product-card', ['product' => $product]); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($posts)): ?>
        <section data-animate>
            <div class="mb-8">
                <p class="section-label mb-3">Guide e notizie</p>
                <h2 class="font-display text-3xl font-black" style="color:var(--c-acc)">
                    Articoli utili su <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </h2>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($posts as $post): ?>
                    <?php View::renderPartial('public/partials/blog-card', ['post' => $post]); ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="cta-strip" data-animate>
        <div class="grid gap-5 md:grid-cols-[1fr_auto] md:items-center">
            <div>
                <h2 class="font-display text-2xl font-black" style="color:var(--c-acc)">Cerchi un modello preciso?</h2>
                <p class="mt-2 text-sm" style="color:var(--c-muted)">Mandaci marca, modello o utilizzo: controlliamo disponibilita, alternative e tempi di arrivo.</p>
            </div>
            <a href="/contatti?topic=<?= urlencode($label) ?>" class="btn-primary">Contatta il negozio</a>
        </div>
    </section>
</div>
