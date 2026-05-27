<?php
/** @var array $settings */
/** @var array $sections */

$contactEmail = $settings['contact_email'] ?? 'negozio@bisped.net';
$sections = $sections ?? [];
?>

<div class="space-y-12">

    <div data-animate>
        <p class="section-label mb-5">Trasparenza</p>
        <h1 class="font-display text-4xl font-black md:text-5xl" style="color:var(--c-acc)">Area legale</h1>
        <p class="mt-4 max-w-2xl text-lg" style="color:var(--c-muted)">Privacy, cookie e condizioni di utilizzo: informazioni chiare per usare il sito e contattare bisp&amp;d con consapevolezza.</p>
    </div>

    <section class="space-y-6 max-w-4xl mx-auto">
        <?php if (empty($sections)): ?>
            <article class="info-card" data-animate>
                <h2 class="font-display text-2xl font-black mb-4" style="color:var(--c-acc)">Informazioni legali</h2>
                <p class="text-sm leading-6" style="color:var(--c-muted)">Per richieste su privacy, dati personali, condizioni di vendita o utilizzo dei servizi puoi scrivere a
                    <a href="mailto:<?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8'); ?>"
                       class="transition-colors" style="color:var(--bisped-red)">
                        <?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8'); ?>
                    </a>.
                </p>
            </article>
        <?php else: ?>
            <?php foreach ($sections as $index => $section): ?>
                <?php
                $content = (string)($section['content_html'] ?? '');
                $content = str_replace('{{contact_email}}', $contactEmail, $content);
                ?>
                <article class="info-card" data-animate data-animate-delay="<?= $index * 80; ?>">
                    <h2 class="font-display text-2xl font-black mb-4" style="color:var(--c-acc)">
                        <?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8'); ?>
                    </h2>
                    <div class="text-sm leading-6 space-y-3" style="color:var(--c-muted)">
                        <?= $content; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

</div>
