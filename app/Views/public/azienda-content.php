<?php
/** @var array $settings */

$_storeRaw  = $settings['storefront_image'] ?? '/media/bisped/fronte_negozio_bisped.png';
$_storeFile = dirname(__DIR__, 4) . '/public' . parse_url($_storeRaw, PHP_URL_PATH);
$storeImage = $_storeRaw . '?v=' . (@filemtime($_storeFile) ?: time());

$phone = '+39 0565 31136';
$whatsapp = '+39 334 658 2116';
$email = 'negozio@bisped.net';
$address = 'Piazza della Costituzione, 68';
$cityLine = '57025 Piombino (LI)';
$mapsEmbedSrc = 'https://maps.google.com/maps?q=Piazza+della+Costituzione+68+Piombino+LI+57025+Italia&output=embed&z=16&hl=it';
$mapsLink = 'https://maps.app.goo.gl/kBoBgbyUWXtgaDW18';
$mapsDirections = 'https://www.google.com/maps/dir/?api=1&destination=Piazza+della+Costituzione+68,+57025+Piombino+LI';
?>

<div class="space-y-20">

    <!-- Hero with store photo -->
    <section class="grid gap-10 lg:grid-cols-[1.1fr_.9fr] lg:items-center" data-animate>
        <div>
            <p class="section-label mb-5">Dal territorio al digitale</p>
            <h1 class="font-display text-4xl font-black tracking-tight md:text-5xl" style="color:var(--c-acc)">
                bisp&amp;d unisce negozio,<br class="hidden md:block"> laboratorio e consulenza IT.
            </h1>
            <p class="mt-6 max-w-2xl text-lg leading-8" style="color:var(--c-muted)">
                Siamo un punto di riferimento per informatica, telefonia, assistenza tecnica e soluzioni digitali.
                Qui trovi prodotti selezionati, consulenza comprensibile e un laboratorio capace di seguire il dopo-vendita.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="/servizi" class="btn-primary">Scopri i servizi</a>
                <a href="/contatti" class="btn-outline">Parla con noi</a>
            </div>
        </div>
        <div class="rounded-lg overflow-hidden border" style="border-color:var(--c-border);aspect-ratio:4/3">
            <img src="<?= htmlspecialchars($storeImage, ENT_QUOTES, 'UTF-8'); ?>"
                 alt="Esterno del punto vendita bisp&d"
                 class="h-full w-full object-cover"
                 loading="lazy">
        </div>
    </section>

    <!-- Pillars -->
    <section>
        <p class="section-label mb-8">Come lavoriamo</p>
        <div class="grid gap-5 md:grid-cols-3">
            <article class="service-card" data-animate data-animate-delay="0">
                <span class="font-display text-5xl font-black mb-4 block" style="color:var(--bisped-red)">01</span>
                <h2 class="font-display text-xl font-black mb-3" style="color:var(--c-acc)">Competenza concreta</h2>
                <p class="text-sm leading-6" style="color:var(--c-muted)">Supporto su PC, notebook, reti, periferiche, telefonia e configurazioni per privati, professionisti e aziende.</p>
            </article>
            <article class="service-card" data-animate data-animate-delay="80">
                <span class="font-display text-5xl font-black mb-4 block" style="color:var(--bisped-red)">02</span>
                <h2 class="font-display text-xl font-black mb-3" style="color:var(--c-acc)">Vendita assistita</h2>
                <p class="text-sm leading-6" style="color:var(--c-muted)">Il catalogo non deve essere un elenco freddo: ogni prodotto va scelto in base a utilizzo, budget e disponibilità reale.</p>
            </article>
            <article class="service-card" data-animate data-animate-delay="160">
                <span class="font-display text-5xl font-black mb-4 block" style="color:var(--bisped-red)">03</span>
                <h2 class="font-display text-xl font-black mb-3" style="color:var(--c-acc)">Relazione locale</h2>
                <p class="text-sm leading-6" style="color:var(--c-muted)">La tecnologia funziona meglio quando c'è continuità: diagnosi, preventivo, consegna, assistenza e post vendita.</p>
            </article>
        </div>
    </section>

    <!-- Store map -->
    <section class="grid gap-8 lg:grid-cols-[1.15fr_.85fr] lg:items-stretch" data-animate>
        <div class="overflow-hidden rounded-lg border" style="border-color:var(--c-border);background:var(--c-surface)">
            <div class="relative" style="aspect-ratio:16/10">
                <iframe
                    title="bisp&amp;d — Piazza della Costituzione 68, Piombino"
                    src="<?= htmlspecialchars($mapsEmbedSrc, ENT_QUOTES, 'UTF-8') ?>"
                    width="100%"
                    height="100%"
                    style="border:0;display:block;position:absolute;inset:0"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <div class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between" style="border-color:var(--c-border)">
                <div>
                    <div class="text-xs font-black uppercase tracking-widest" style="color:var(--c-muted)">Punto vendita e laboratorio</div>
                    <div class="mt-1 font-bold" style="color:var(--c-acc)">bisp&amp;d, Piombino</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="<?= htmlspecialchars($mapsLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn-primary btn-sm">Apri Google Maps</a>
                    <a href="<?= htmlspecialchars($mapsDirections, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn-outline btn-sm">Indicazioni</a>
                </div>
            </div>
        </div>

        <aside class="info-card flex flex-col justify-between">
            <div>
                <p class="section-label mb-4">Vieni in negozio</p>
                <h2 class="font-display text-2xl font-black leading-tight" style="color:var(--c-acc)">
                    Hardware, assistenza e consulenza senza rimbalzi.
                </h2>
                <p class="mt-4 text-sm leading-6" style="color:var(--c-muted)">
                    Porta il dispositivo, raccontaci l'obiettivo o passa per scegliere una configurazione: trovi banco vendita, laboratorio tecnico e supporto per aziende nello stesso posto.
                </p>

                <div class="mt-6 space-y-4 text-sm" style="color:var(--c-muted)">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-md" style="background:rgba(209,25,32,.12);color:var(--bisped-red)">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                        </span>
                        <div>
                            <div class="font-bold" style="color:var(--c-txt)"><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></div>
                            <div><?= htmlspecialchars($cityLine, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <a href="tel:+39056531136" class="rounded-md border p-3 transition-colors hover:border-red-500" style="border-color:var(--c-border);text-decoration:none;color:var(--c-txt)">
                            <span class="block text-xs font-black uppercase tracking-widest" style="color:var(--c-muted)">Telefono</span>
                            <span class="mt-1 block font-bold"><?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                        <a href="https://wa.me/393346582116" target="_blank" rel="noopener" class="rounded-md border p-3 transition-colors hover:border-red-500" style="border-color:var(--c-border);text-decoration:none;color:var(--c-txt)">
                            <span class="block text-xs font-black uppercase tracking-widest" style="color:var(--c-muted)">WhatsApp</span>
                            <span class="mt-1 block font-bold"><?= htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    </div>

                    <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" class="block rounded-md border p-3 transition-colors hover:border-red-500" style="border-color:var(--c-border);text-decoration:none;color:var(--c-txt)">
                        <span class="block text-xs font-black uppercase tracking-widest" style="color:var(--c-muted)">Email negozio</span>
                        <span class="mt-1 block font-bold"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></span>
                    </a>

                    <div class="rounded-md border p-3" style="border-color:var(--c-border);background:var(--c-bg)">
                        <div class="flex justify-between gap-4">
                            <span class="font-bold" style="color:var(--c-txt)">Lun - Ven</span>
                            <span>9:00 - 13:00 / 15:30 - 19:30</span>
                        </div>
                        <div class="mt-2 flex justify-between gap-4">
                            <span class="font-bold" style="color:var(--c-txt)">Sabato</span>
                            <span>9:00 - 13:00</span>
                        </div>
                        <div class="mt-2 flex justify-between gap-4">
                            <span class="font-bold" style="color:var(--c-txt)">Domenica</span>
                            <span>Chiuso</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </section>

    <!-- CTA -->
    <div class="cta-strip flex flex-col items-start gap-6 md:flex-row md:items-center md:justify-between" data-animate>
        <div>
            <h2 class="font-display text-2xl font-black" style="color:var(--c-acc)">Vieni a trovarci a Piombino</h2>
            <p class="mt-2 text-sm" style="color:var(--c-muted)">In negozio o in laboratorio: siamo qui per ascoltare e proporre la soluzione giusta.</p>
        </div>
        <div class="flex gap-3 flex-shrink-0">
            <a href="/contatti" class="btn-primary">Contattaci</a>
            <a href="tel:+39056531136" class="btn-outline">Chiama</a>
        </div>
    </div>

</div>
