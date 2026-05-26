<?php
/** @var array $settings */

$storeImage = $settings['storefront_image'] ?? '/media/bisped/fronte_negozio_bisped.png';
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

    <!-- CTA -->
    <div class="cta-strip flex flex-col items-start gap-6 md:flex-row md:items-center md:justify-between" data-animate>
        <div>
            <h2 class="font-display text-2xl font-black" style="color:var(--c-acc)">Vieni a trovarci a Piombino</h2>
            <p class="mt-2 text-sm" style="color:var(--c-muted)">In negozio o in laboratorio: siamo qui per ascoltare e proporre la soluzione giusta.</p>
        </div>
        <div class="flex gap-3 flex-shrink-0">
            <a href="/contatti" class="btn-primary">Contattaci</a>
            <a href="tel:+390565200000" class="btn-outline">Chiama</a>
        </div>
    </div>

</div>
