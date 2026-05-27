<?php
use App\Core\View;

/** @var array $settings */
/** @var array $products */

$services = [
    [
        'id'    => 'assistenza',
        'label' => 'Assistenza residenziale',
        'title' => 'Quando la tecnologia si ferma, non devi arrangiarti.',
        'text'  => 'PC lento, notebook che scalda, stampante ostinata, Wi-Fi instabile, backup da recuperare: partiamo dal problema e ti diciamo con chiarezza se conviene riparare, aggiornare o sostituire.',
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3"/>',
    ],
    [
        'id'    => 'connettivita',
        'label' => 'Connettività',
        'title' => 'La connessione giusta per casa, negozio e ufficio.',
        'text'  => 'Fibra, FWA, SIM dati, router e copertura reale: confrontiamo le opzioni disponibili e ti aiutiamo a scegliere una soluzione stabile, proporzionata e installabile senza sorprese.',
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z"/>',
    ],
    [
        'id'    => 'fonia',
        'label' => 'Fonia',
        'title' => 'Telefono, centralino e continuità di lavoro.',
        'text'  => 'Gestione linee, trasferimenti, configurazioni smartphone e soluzioni voce per chi lavora con il telefono ogni giorno e non può perdere chiamate importanti.',
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 6Z"/>',
    ],
    [
        'id'    => 'energia',
        'label' => 'Energia',
        'title' => 'Bollette più leggibili, scelte più consapevoli.',
        'text'  => 'Ti aiutiamo a orientarti tra offerte e consumi, con un approccio pratico: meno promesse generiche, più attenzione a cosa serve davvero alla tua casa o alla tua attività.',
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"/>',
    ],
    [
        'id'    => 'gaming',
        'label' => 'Gaming & Hardware',
        'title' => 'Prestazioni, componenti e postazioni fatte con criterio.',
        'text'  => 'Build gaming, upgrade, monitor, cuffie, tastiere e periferiche: scegliamo componenti coerenti tra loro, pensando a budget, giochi, raffreddamento e possibilità di crescita.',
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 0 1-.657.643 48.39 48.39 0 0 1-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 0 1-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 0 0-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 0 1-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 0 0 .657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 0 1-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.401.604-.401.959v0c0 .333.277.599.61.58a48.1 48.1 0 0 0 5.427-.63 48.05 48.05 0 0 0 .582-4.717.532.532 0 0 0-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.036 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.369 0 .713.128 1.003.349.283.215.604.401.959.401v0a.656.656 0 0 0 .658-.663 48.422 48.422 0 0 0-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 0 1-.61-.58v0Z"/>',
    ],
    [
        'id'    => 'aziende',
        'label' => 'Business',
        'title' => 'Tecnologia ordinata per chi lavora.',
        'text'  => 'Fornitura, configurazione, reti, sicurezza, backup e supporto continuativo per professionisti e aziende che vogliono ridurre fermi macchina e improvvisazioni.',
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>',
    ],
];
?>

<div class="space-y-20">

    <!-- Hero -->
    <section class="tech-grid rounded-lg border p-10 md:p-16" style="border-color:var(--c-border);background:var(--c-surface)" data-animate>
        <p class="section-label mb-5">Servizi bisp&amp;d</p>
        <h1 class="max-w-4xl font-display text-4xl font-black tracking-tight md:text-5xl" style="color:var(--c-acc)">
            Vendiamo tecnologia, ma soprattutto<br class="hidden md:block"> la facciamo funzionare.
        </h1>
        <p class="mt-6 max-w-3xl text-lg leading-8" style="color:var(--c-muted)">
            Il valore non è solo nel prodotto sullo scaffale. È nella persona che ti ascolta, nella diagnosi corretta,
            nella configurazione fatta bene e nel supporto quando qualcosa non va.
        </p>
    </section>

    <!-- Service cards -->
    <section>
        <p class="section-label mb-8">Cosa facciamo</p>
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($services as $index => $service): ?>
                <article id="<?= htmlspecialchars($service['id'], ENT_QUOTES, 'UTF-8'); ?>"
                         class="service-card scroll-mt-28"
                         data-animate data-animate-delay="<?= $index * 80 ?>">
                    <div class="mb-5 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded" style="background:rgba(209,25,32,.12)">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="color:var(--bisped-red)">
                                <?= $service['icon'] ?>
                            </svg>
                        </div>
                        <span class="text-xs font-black uppercase tracking-widest" style="color:var(--bisped-red)">
                            <?= htmlspecialchars($service['label'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                    <h2 class="font-display text-lg font-black leading-snug mb-3" style="color:var(--c-acc)">
                        <?= htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?>
                    </h2>
                    <p class="text-sm leading-6" style="color:var(--c-muted)">
                        <?= htmlspecialchars($service['text'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if (!empty($products)): ?>
    <!-- Products preview -->
    <section>
        <div class="flex flex-col gap-4 mb-8 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="section-label mb-4">Dal servizio al prodotto</p>
                <h2 class="font-display text-3xl font-black" style="color:var(--c-acc)">Se serve acquistare, si sceglie meglio.</h2>
                <p class="mt-3 max-w-2xl text-sm" style="color:var(--c-muted)">Prima capiamo l'uso, poi proponiamo una macchina, uno smartphone, una periferica o un piano di intervento coerente.</p>
            </div>
            <a href="/products" class="btn-outline btn-sm flex-shrink-0">Vai allo shop →</a>
        </div>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            <?php foreach (array_slice(array_values($products), 0, 6) as $product): ?>
                <?php View::renderPartial('public/partials/product-card', ['product' => $product]); ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA -->
    <div class="cta-strip flex flex-col items-start gap-6 md:flex-row md:items-center md:justify-between" data-animate>
        <div>
            <h2 class="font-display text-2xl font-black" style="color:var(--c-acc)">Hai un problema tecnico da risolvere?</h2>
            <p class="mt-2 text-sm" style="color:var(--c-muted)">Raccontaci la situazione: ti diciamo cosa fare senza giri di parole.</p>
        </div>
        <div class="flex gap-3 flex-shrink-0">
            <a href="/contatti" class="btn-primary">Contattaci</a>
            <a href="tel:+393346582116" class="btn-outline">Chiama</a>
        </div>
    </div>

</div>
