<?php
use App\Core\View;

/** @var array $posts */
?>

<div class="space-y-12">

    <div data-animate>
        <p class="section-label mb-5">News & Approfondimenti</p>
        <h1 class="font-display text-4xl font-black md:text-5xl" style="color:var(--c-acc)">Blog bisp&amp;d</h1>
        <p class="mt-4 max-w-2xl text-lg" style="color:var(--c-muted)">Guide pratiche per comprare meglio, far durare i dispositivi e orientarsi tra informatica, smartphone, gaming, connettività ed energia.</p>
    </div>

    <?php if (empty($posts)): ?>
        <div class="info-card text-center py-16" data-animate>
            <p class="text-lg font-bold mb-2" style="color:var(--c-acc)">Nessun articolo pubblicato</p>
            <p class="text-sm" style="color:var(--c-muted)">Torna presto per nuovi contenuti.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($posts as $index => $post): ?>
                <div data-animate data-animate-delay="<?= $index * 80 ?>">
                    <?php View::renderPartial('public/partials/blog-card', ['post' => $post]); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
