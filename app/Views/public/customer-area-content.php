<?php
/** @var string $name */
/** @var string $email */
/** @var string $role */
?>

<div class="space-y-10" data-animate>
    <section class="rounded-[2rem] border border-stroke bg-glass p-8 md:p-12">
        <p class="text-sm font-black uppercase tracking-[0.35em] text-pri">Area riservata</p>
        <h1 class="mt-5 text-4xl font-black tracking-tight text-acc md:text-6xl">Ciao <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>.</h1>
        <p class="mt-5 max-w-3xl text-lg leading-8 text-muted">
            Questa area raccogliera richieste, preventivi, assistenze e documenti collegati al tuo profilo Bisped.
            La struttura e gia pronta per ricevere clienti, anagrafiche e storico commerciale da Danea Easyfatt.
        </p>
    </section>

    <section class="grid gap-5 md:grid-cols-3">
        <?php foreach ([
            ['title' => 'Richieste aperte', 'text' => 'Assistenze, preventivi e disponibilita prodotto in lavorazione.'],
            ['title' => 'Documenti e ordini', 'text' => 'Preventivi, conferme, ritiri in negozio e riepiloghi acquisto.'],
            ['title' => 'Profilo cliente', 'text' => $email . ' - ruolo: ' . $role],
        ] as $card): ?>
            <article class="rounded-3xl border border-stroke bg-bg2 p-6">
                <h2 class="text-xl font-black text-acc"><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="mt-3 text-sm leading-6 text-muted"><?= htmlspecialchars($card['text'], ENT_QUOTES, 'UTF-8'); ?></p>
            </article>
        <?php endforeach; ?>
    </section>
</div>
