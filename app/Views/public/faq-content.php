<?php
/** @var array $faqs */
?>

<div class="space-y-12">

    <div data-animate>
        <p class="section-label mb-5">Supporto</p>
        <h1 class="font-display text-4xl font-black md:text-5xl" style="color:var(--c-acc)">Domande frequenti</h1>
        <p class="mt-4 max-w-2xl text-lg" style="color:var(--c-muted)">Risposte rapide su acquisti, assistenza, disponibilità prodotti e richieste online.</p>
    </div>

    <div class="space-y-3 max-w-3xl" data-animate data-animate-delay="80">
        <?php foreach ($faqs as $index => $faq): ?>
            <details class="faq-item" <?= $index === 0 ? 'open' : ''; ?>>
                <summary><?= htmlspecialchars($faq['question'], ENT_QUOTES, 'UTF-8'); ?></summary>
                <p><?= htmlspecialchars($faq['answer'], ENT_QUOTES, 'UTF-8'); ?></p>
            </details>
        <?php endforeach; ?>
    </div>

    <div class="cta-strip flex flex-col items-start gap-6 md:flex-row md:items-center md:justify-between" data-animate>
        <div>
            <h2 class="font-display text-xl font-black" style="color:var(--c-acc)">Non trovi risposta?</h2>
            <p class="mt-2 text-sm" style="color:var(--c-muted)">Scrivici direttamente: ti risponderemo nel più breve tempo possibile.</p>
        </div>
        <a href="/contatti" class="btn-primary flex-shrink-0">Contattaci</a>
    </div>

</div>
