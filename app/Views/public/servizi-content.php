<?php
use App\Core\View;

/** @var array $settings */
/** @var array $products */

$services = [
    [
        'id' => 'assistenza',
        'label' => 'Assistenza residenziale',
        'title' => 'Quando la tecnologia si ferma, non devi arrangiarti.',
        'text' => 'PC lento, notebook che scalda, stampante ostinata, Wi-Fi instabile, backup da recuperare: partiamo dal problema e ti diciamo con chiarezza se conviene riparare, aggiornare o sostituire.',
    ],
    [
        'id' => 'connettivita',
        'label' => 'Servizi connettivita',
        'title' => 'La connessione giusta per casa, negozio e ufficio.',
        'text' => 'Fibra, FWA, SIM dati, router e copertura reale: confrontiamo le opzioni disponibili e ti aiutiamo a scegliere una soluzione stabile, proporzionata e installabile senza sorprese.',
    ],
    [
        'id' => 'fonia',
        'label' => 'Servizi fonia',
        'title' => 'Telefono, centralino e continuita di lavoro.',
        'text' => 'Gestione linee, trasferimenti, configurazioni smartphone e soluzioni voce per chi lavora con il telefono ogni giorno e non puo perdere chiamate importanti.',
    ],
    [
        'id' => 'energia',
        'label' => 'Servizi energia',
        'title' => 'Bollette piu leggibili, scelte piu consapevoli.',
        'text' => 'Ti aiutiamo a orientarti tra offerte e consumi, con un approccio pratico: meno promesse generiche, piu attenzione a cosa serve davvero alla tua casa o alla tua attivita.',
    ],
    [
        'id' => 'gaming',
        'label' => 'Gaming e hardware',
        'title' => 'Prestazioni, componenti e postazioni fatte con criterio.',
        'text' => 'Build gaming, upgrade, monitor, cuffie, tastiere e periferiche: scegliamo componenti coerenti tra loro, pensando a budget, giochi, raffreddamento e possibilita di crescita.',
    ],
    [
        'id' => 'aziende',
        'label' => 'Soluzioni business',
        'title' => 'Tecnologia ordinata per chi lavora.',
        'text' => 'Fornitura, configurazione, reti, sicurezza, backup e supporto continuativo per professionisti e aziende che vogliono ridurre fermi macchina e improvvisazioni.',
    ],
];
?>

<div class="space-y-16" data-animate>
    <section class="rounded-[2rem] border border-stroke bg-[linear-gradient(135deg,rgba(226,31,38,.28),rgba(248,210,75,.08),rgba(255,255,255,.04))] p-8 md:p-12">
        <p class="text-sm font-black uppercase tracking-[0.35em] text-pri">Servizi Bisped</p>
        <h1 class="mt-5 max-w-4xl text-4xl font-black tracking-tight text-acc md:text-6xl">Vendiamo tecnologia, ma soprattutto la facciamo funzionare.</h1>
        <p class="mt-6 max-w-3xl text-lg leading-8 text-muted">
            Il valore non e solo nel prodotto sullo scaffale. E nella persona che ti ascolta, nella diagnosi corretta,
            nella configurazione fatta bene e nel supporto quando qualcosa non va.
        </p>
    </section>

    <section class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($services as $index => $service): ?>
            <article id="<?= htmlspecialchars($service['id'], ENT_QUOTES, 'UTF-8'); ?>" class="scroll-mt-28 rounded-3xl border border-stroke bg-glass p-6 transition hover:-translate-y-1 hover:border-pri/70">
                <div class="mb-5 inline-flex rounded-full bg-pri/15 px-4 py-2 text-xs font-black uppercase tracking-[0.22em] text-pri">
                    <?= htmlspecialchars($service['label'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <h2 class="text-xl font-black text-acc"><?= htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="mt-3 text-sm leading-6 text-muted"><?= htmlspecialchars($service['text'], ENT_QUOTES, 'UTF-8'); ?></p>
            </article>
        <?php endforeach; ?>
    </section>

    <section>
        <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="text-sm font-black uppercase tracking-[0.35em] text-pri">Dal servizio al prodotto</p>
                <h2 class="mt-3 text-3xl font-black text-acc">Se serve acquistare, si sceglie meglio.</h2>
                <p class="mt-3 max-w-2xl text-muted">Prima capiamo l'uso, poi proponiamo una macchina, uno smartphone, una periferica o un piano di intervento coerente.</p>
            </div>
            <a href="/products" class="text-sm font-black text-pri hover:underline">Vai allo shop</a>
        </div>
        <div class="grid gap-5 md:grid-cols-3">
            <?php foreach (array_slice(array_values($products), 0, 6) as $product): ?>
                <?php View::renderPartial('public/partials/product-card', ['product' => $product]); ?>
            <?php endforeach; ?>
        </div>
    </section>
</div>
