<?php
/** @var array $settings */
/** @var array $sections */

$contactEmail = $settings['contact_email'] ?? 'info@bisped.net';
$sections = $sections ?? [];
?>

<div class="space-y-16">
    <div data-animate>
        <?php \App\Core\View::renderPartial('partials/section-title', [
            'title' => 'Area legale',
            'subtitle' => 'Privacy, cookie e condizioni di utilizzo: informazioni chiare per usare il sito e contattare Bisped con consapevolezza.',
        ]); ?>
    </div>

    <section class="space-y-12 max-w-4xl mx-auto text-sm leading-relaxed text-muted">
        <?php if (empty($sections)): ?>
            <article class="bg-glass border border-stroke rounded-lg p-8 shadow-deep" data-animate>
                <h2 class="text-2xl font-bold text-acc mb-4">Informazioni legali</h2>
                <p class="mb-0">Per richieste su privacy, dati personali, condizioni di vendita o utilizzo dei servizi puoi scrivere a <a href="mailto:<?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8'); ?>" class="text-cy hover:underline"><?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8'); ?></a>.</p>
            </article>
        <?php else: ?>
            <?php foreach ($sections as $index => $section): ?>
                <?php
                $content = (string)($section['content_html'] ?? '');
                $content = str_replace('{{contact_email}}', $contactEmail, $content);
                ?>
                <article class="bg-glass border border-stroke rounded-lg p-8 shadow-deep" data-animate data-animate-delay="<?= $index * 120; ?>">
                    <h2 class="text-2xl font-bold text-acc mb-4"><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <div class="space-y-4 legal-section-content">
                        <?= $content; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>
