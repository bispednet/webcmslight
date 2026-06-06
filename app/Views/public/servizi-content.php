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
        'label' => 'Connettività e fonia',
        'title' => 'La connessione giusta per casa, negozio e ufficio.',
        'text'  => 'Fibra, FWA, SIM dati, router e copertura reale: confrontiamo le opzioni disponibili e ti aiutiamo a scegliere una soluzione stabile, proporzionata e installabile senza sorprese.',
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z"/>',
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
    [
        'id'    => 'ai',
        'label' => 'AI & Software',
        'title' => 'Applicativi su misura e intelligenza artificiale per le aziende.',
        'text'  => 'Sviluppiamo software gestionale, chatbot, AI Agent e automazioni che lavorano per te: dalla gestione clienti alla presenza online, fino agli assistenti virtuali che rispondono al posto tuo.',
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>',
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

    <!-- AI & Software dedicata -->
    <section id="ai-software" class="scroll-mt-28 rounded-lg border p-10 md:p-16 tech-grid" style="border-color:var(--c-border);background:var(--c-surface)" data-animate>
        <div class="flex flex-col gap-4 mb-10 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="section-label mb-4">Intelligenza artificiale</p>
                <h2 class="max-w-3xl font-display text-3xl font-black md:text-4xl" style="color:var(--c-acc)">
                    Non solo tecnologia da scaffale:<br class="hidden md:block"> software e AI che lavorano per la tua azienda.
                </h2>
                <p class="mt-5 max-w-2xl text-base leading-7" style="color:var(--c-muted)">Progettiamo applicativi su misura e soluzioni di intelligenza artificiale pensate per i processi reali delle imprese: automatizzare attività ripetitive, rispondere ai clienti, presidiare i canali online e liberare tempo alle persone. Dall'idea al rilascio, seguiamo tutto noi.</p>
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">

            <article class="service-card">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded" style="background:rgba(209,25,32,.12)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="color:var(--bisped-red)">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5"/>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-widest" style="color:var(--bisped-red)">Applicativi su misura</span>
                </div>
                <h3 class="font-display text-lg font-black leading-snug mb-3" style="color:var(--c-acc)">Software costruito intorno al tuo lavoro.</h3>
                <p class="text-sm leading-6" style="color:var(--c-muted)">Gestionali, portali, integrazioni e automazioni progettati sulle tue procedure, non il contrario. Veloci, sicuri e amministrabili da te.</p>
            </article>

            <article class="service-card">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded" style="background:rgba(209,25,32,.12)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="color:var(--bisped-red)">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-widest" style="color:var(--bisped-red)">Chatbot</span>
                </div>
                <h3 class="font-display text-lg font-black leading-snug mb-3" style="color:var(--c-acc)">Risposte ai clienti, giorno e notte.</h3>
                <p class="text-sm leading-6" style="color:var(--c-muted)">Assistenti conversazionali addestrati sui tuoi prodotti e servizi, integrati su sito, WhatsApp e social. Qualificano le richieste e passano al team i contatti pronti.</p>
            </article>

            <article class="service-card">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded" style="background:rgba(209,25,32,.12)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="color:var(--bisped-red)">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-widest" style="color:var(--bisped-red)">AI Agent</span>
                </div>
                <h3 class="font-display text-lg font-black leading-snug mb-3" style="color:var(--c-acc)">Agenti che eseguono, non solo rispondono.</h3>
                <p class="text-sm leading-6" style="color:var(--c-muted)">Agenti autonomi che svolgono attività concrete: smistano richieste, aggiornano dati, generano documenti e coordinano più passaggi al posto tuo.</p>
            </article>

            <article class="service-card">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded" style="background:rgba(209,25,32,.12)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="color:var(--bisped-red)">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-widest" style="color:var(--bisped-red)">Social influencer AI</span>
                </div>
                <h3 class="font-display text-lg font-black leading-snug mb-3" style="color:var(--c-acc)">Una presenza sui social sempre attiva.</h3>
                <p class="text-sm leading-6" style="color:var(--c-muted)">Personaggi digitali coerenti col tuo brand che producono contenuti e animano i canali con continuità, mantenendo tono e identità sotto il tuo controllo.</p>
            </article>

            <article class="service-card">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded" style="background:rgba(209,25,32,.12)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="color:var(--bisped-red)">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-widest" style="color:var(--bisped-red)">Avatar 3D</span>
                </div>
                <h3 class="font-display text-lg font-black leading-snug mb-3" style="color:var(--c-acc)">Un volto digitale per la tua azienda.</h3>
                <p class="text-sm leading-6" style="color:var(--c-muted)">Avatar tridimensionali realistici per accoglienza, formazione, video e assistenza: parlano, presentano e interagiscono con i tuoi clienti.</p>
            </article>

            <article class="service-card">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded" style="background:rgba(209,25,32,.12)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="color:var(--bisped-red)">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-widest" style="color:var(--bisped-red)">AI per le aziende</span>
                </div>
                <h3 class="font-display text-lg font-black leading-snug mb-3" style="color:var(--c-acc)">Consulenza e integrazione, passo dopo passo.</h3>
                <p class="text-sm leading-6" style="color:var(--c-muted)">Ti aiutiamo a capire dove l'AI conviene davvero e a integrarla nei tuoi strumenti, con un percorso concreto e misurabile, senza inseguire le mode.</p>
            </article>

        </div>

        <div class="mt-10 flex flex-col items-start gap-4 sm:flex-row sm:items-center">
            <a href="/contatti?topic=AI%20Agent" class="btn-primary">Parliamo del tuo progetto AI</a>
            <p class="text-sm" style="color:var(--c-muted)">Una prima call gratuita per capire cosa ha senso fare, e cosa no.</p>
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
            <a href="tel:+39056531136" class="btn-outline">Chiama</a>
        </div>
    </div>

</div>
