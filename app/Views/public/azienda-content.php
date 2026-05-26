<?php
/** @var array $settings */

$storeImage = $settings['storefront_image'] ?? '/media/bisped/fronte_negozio_bisped.png';
?>

<div class="space-y-16" data-animate>
    <section class="grid gap-10 lg:grid-cols-[1.05fr_.95fr] lg:items-center">
        <div>
            <p class="text-sm font-black uppercase tracking-[0.35em] text-pri">Dal territorio al digitale</p>
            <h1 class="mt-5 text-4xl font-black tracking-tight text-acc md:text-6xl">Bisped unisce negozio, laboratorio e consulenza IT.</h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-muted">
                Siamo un punto di riferimento per informatica, telefonia, assistenza tecnica e soluzioni digitali.
                Qui trovi prodotti selezionati, consulenza comprensibile e un laboratorio capace di seguire il dopo-vendita.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="/servizi" class="rounded-full bg-pri px-6 py-3 text-sm font-black text-white transition hover:bg-pri-700">Scopri i servizi</a>
                <a href="/contatti" class="rounded-full border border-stroke px-6 py-3 text-sm font-black text-acc transition hover:border-pri hover:text-pri">Parla con noi</a>
            </div>
        </div>
        <div class="overflow-hidden rounded-[2rem] border border-stroke bg-glass shadow-deep">
            <img src="<?= htmlspecialchars($storeImage, ENT_QUOTES, 'UTF-8'); ?>" alt="Esterno del punto vendita Bisped" class="h-full w-full object-cover" loading="lazy">
        </div>
    </section>

    <section class="grid gap-5 md:grid-cols-3">
        <article class="rounded-3xl border border-stroke bg-glass p-6">
            <span class="text-4xl font-black text-pri">01</span>
            <h2 class="mt-4 text-xl font-black text-acc">Competenza concreta</h2>
            <p class="mt-3 text-sm leading-6 text-muted">Supporto su PC, notebook, reti, periferiche, telefonia e configurazioni per privati, professionisti e aziende.</p>
        </article>
        <article class="rounded-3xl border border-stroke bg-glass p-6">
            <span class="text-4xl font-black text-pri">02</span>
            <h2 class="mt-4 text-xl font-black text-acc">Vendita assistita</h2>
            <p class="mt-3 text-sm leading-6 text-muted">Il catalogo non deve essere un elenco freddo: ogni prodotto va scelto in base a utilizzo, budget e disponibilita reale.</p>
        </article>
        <article class="rounded-3xl border border-stroke bg-glass p-6">
            <span class="text-4xl font-black text-pri">03</span>
            <h2 class="mt-4 text-xl font-black text-acc">Relazione locale</h2>
            <p class="mt-3 text-sm leading-6 text-muted">La tecnologia funziona meglio quando c'e continuita: diagnosi, preventivo, consegna, assistenza e post vendita.</p>
        </article>
    </section>
</div>
