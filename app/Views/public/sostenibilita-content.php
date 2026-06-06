<?php
/** @var array $settings */
?>

<div class="space-y-20">

    <section class="grid gap-10 lg:grid-cols-[1fr_1fr] lg:items-center" data-animate>
        <div>
            <p class="section-label mb-5">Il nostro approccio</p>
            <h1 class="font-display text-4xl font-black md:text-5xl" style="color:var(--c-acc)">Riparare, riusare,<br>scegliere meglio.</h1>
            <p class="mt-6 text-lg leading-8" style="color:var(--c-muted)">
                Nel mondo IT la sostenibilità non è uno slogan: significa allungare la vita dei dispositivi,
                consigliare acquisti proporzionati, recuperare quando conviene e smaltire correttamente quando non conviene più.
                bisp&amp;d porta questa filosofia nel modo in cui consiglia, ripara e accompagna ogni scelta tecnologica.
            </p>
        </div>
        <div class="rounded-lg border overflow-hidden" style="border-color:var(--c-border);aspect-ratio:4/3">
            <img src="/media/pages/sostenibilita-hero.jpg"
                 alt="Laboratorio Bisped: diagnosi e riparazione prima della sostituzione"
                 class="w-full h-full object-cover" loading="eager" width="1000" height="750">
        </div>
    </section>

    <section class="grid gap-5 md:grid-cols-3">
        <article class="service-card" data-animate data-animate-delay="0">
            <h2 class="font-display text-xl font-black mb-3" style="color:var(--c-acc)">Diagnosi prima della sostituzione</h2>
            <p class="text-sm leading-6" style="color:var(--c-muted)">Prima di proporre un acquisto nuovo, valutiamo sempre se il dispositivo esistente può essere riparato o aggiornato per altri anni di vita utile.</p>
        </article>
        <article class="service-card" data-animate data-animate-delay="80">
            <h2 class="font-display text-xl font-black mb-3" style="color:var(--c-acc)">Consulenza sul consumo energetico</h2>
            <p class="text-sm leading-6" style="color:var(--c-muted)">Aiutiamo privati e aziende a scegliere dispositivi ed offerte energetiche che riducono i costi fissi senza rinunciare alle prestazioni.</p>
        </article>
        <article class="service-card" data-animate data-animate-delay="160">
            <h2 class="font-display text-xl font-black mb-3" style="color:var(--c-acc)">Smaltimento RAEE responsabile</h2>
            <p class="text-sm leading-6" style="color:var(--c-muted)">I dispositivi che non possono essere riparati vengono avviati verso filiere certificate di smaltimento RAEE, senza finire in discarica.</p>
        </article>
    </section>

    <div class="cta-strip flex flex-col items-start gap-6 md:flex-row md:items-center md:justify-between" data-animate>
        <div>
            <h2 class="font-display text-2xl font-black" style="color:var(--c-acc)">Il tuo dispositivo ha ancora vita utile?</h2>
            <p class="mt-2 text-sm" style="color:var(--c-muted)">Portalo in laboratorio: lo valutiamo gratuitamente e ti diciamo con chiarezza cosa conviene fare.</p>
        </div>
        <a href="/contatti" class="btn-primary flex-shrink-0">Richiedi valutazione</a>
    </div>

</div>
