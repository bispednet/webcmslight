<?php
use App\Core\View;
use App\Support\AdminMode;

/** @var array $settings */
/** @var array $products */

$tagline = $settings['site_tagline'] ?? 'Computer, telefonia e assistenza a Piombino';
$heroTitle = $settings['hero_title_home'] ?? 'Tecnologia scelta bene. Installata meglio.';
$heroSubtitle = $settings['hero_subtitle_home'] ?? 'Bisped e il negozio dove entri con un dubbio e trovi una risposta concreta: PC, smartphone, gaming, connettivita, energia e assistenza tecnica seguita da persone vere.';
$heroImage = $settings['hero_image_home'] ?? '/media/bisped/fronte_negozio_bisped.png';
$heroBadge = $settings['hero_badge_home'] ?? 'Negozio, laboratorio e consulenza: un unico punto di riferimento tech.';
$productsList = array_values($products);

$departments = [
    ['id' => 'informatica', 'label' => 'Informatica', 'title' => 'PC, notebook, monitor e componenti', 'text' => 'Prodotti selezionati per lavoro, studio, casa e postazioni professionali.', 'href' => '/products#informatica'],
    ['id' => 'smartphone', 'label' => 'Smartphone', 'title' => 'Telefonia pronta all uso', 'text' => 'Dispositivi, SIM, trasferimento dati, accessori e configurazioni senza stress.', 'href' => '/products#smartphone'],
    ['id' => 'gaming', 'label' => 'Gaming', 'title' => 'Setup, rig e periferiche', 'text' => 'Dal PC assemblato al dettaglio che cambia la partita: prestazioni, estetica, affidabilita.', 'href' => '/products#gaming'],
    ['id' => 'assistenza', 'label' => 'Assistenza', 'title' => 'Laboratorio e supporto remoto', 'text' => 'Diagnosi, riparazione, recupero dati, upgrade e manutenzione programmata.', 'href' => '/servizi#assistenza'],
    ['id' => 'connettivita', 'label' => 'Connettivita', 'title' => 'Fibra, FWA, reti e Wi-Fi', 'text' => 'Verifica copertura, attivazioni, router, rete domestica e piccoli uffici.', 'href' => '/servizi#connettivita'],
    ['id' => 'energia', 'label' => 'Energia', 'title' => 'Luce, gas e risparmio', 'text' => 'Consulenza commerciale chiara per scegliere offerte adatte a casa e impresa.', 'href' => '/servizi#energia'],
];
?>

<div class="space-y-20 md:space-y-28">
    <section class="grid gap-10 pt-4 md:pt-10 lg:grid-cols-[1.02fr_.98fr] lg:items-center" data-animate>
        <div>
            <p class="text-sm font-black uppercase tracking-[0.35em] text-pri"<?= AdminMode::dataAttrs('settings', 'site_tagline'); ?>>
                <?= htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <h1 class="mt-5 font-display text-4xl font-black leading-[0.95] tracking-tight text-acc md:text-7xl"<?= AdminMode::dataAttrs('settings', 'hero_title_home'); ?>>
                <?= htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            <p class="mt-7 max-w-2xl text-lg leading-8 text-muted"<?= AdminMode::dataAttrs('settings', 'hero_subtitle_home'); ?>>
                <?= htmlspecialchars($heroSubtitle, ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                <a href="/products" class="rounded-full bg-pri px-7 py-4 text-center text-sm font-black text-white transition hover:bg-pri-700 hover:-translate-y-0.5">Acquista e richiedi disponibilita</a>
                <a href="/servizi#assistenza" class="rounded-full border border-stroke bg-glass px-7 py-4 text-center text-sm font-black text-acc transition hover:border-pri hover:text-pri">Ho bisogno di assistenza</a>
            </div>
            <div class="mt-8 grid max-w-xl grid-cols-3 gap-3 text-center">
                <div class="rounded-2xl border border-stroke bg-glass p-4">
                    <strong class="block text-2xl text-pri">Shop</strong>
                    <span class="text-xs font-bold uppercase tracking-widest text-muted">prodotti reali</span>
                </div>
                <div class="rounded-2xl border border-stroke bg-glass p-4">
                    <strong class="block text-2xl text-pri">Lab</strong>
                    <span class="text-xs font-bold uppercase tracking-widest text-muted">riparazioni</span>
                </div>
                <div class="rounded-2xl border border-stroke bg-glass p-4">
                    <strong class="block text-2xl text-pri">Store</strong>
                    <span class="text-xs font-bold uppercase tracking-widest text-muted">Piombino</span>
                </div>
            </div>
        </div>
        <div class="relative">
            <div class="absolute -inset-4 rounded-[2.5rem] bg-pri/20 blur-3xl"></div>
            <img src="<?= htmlspecialchars($heroImage, ENT_QUOTES, 'UTF-8'); ?>" alt="Punto vendita Bisped a Piombino" class="relative aspect-[16/10] w-full rounded-[2rem] border border-stroke object-cover shadow-deep" loading="lazy"<?= AdminMode::dataAttrs('settings', 'hero_image_home', null, 'image'); ?>>
        </div>
    </section>

    <section class="rounded-[2rem] border border-stroke bg-glass p-5 text-center" data-animate>
        <p class="text-sm text-muted">
            <span class="font-black text-yl"<?= AdminMode::dataAttrs('settings', 'hero_badge_home'); ?>>
                <?= htmlspecialchars($heroBadge, ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </p>
    </section>

    <section data-animate>
        <div class="mb-10 flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="text-sm font-black uppercase tracking-[0.35em] text-pri">Reparti</p>
                <h2 class="mt-4 text-3xl font-black text-acc md:text-5xl">Trova subito il banco giusto.</h2>
            </div>
            <p class="max-w-xl text-muted">Ogni reparto porta a una scelta utile: un prodotto da valutare, un servizio da richiedere, una persona con cui parlare.</p>
        </div>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($departments as $department): ?>
                <a id="<?= htmlspecialchars($department['id'], ENT_QUOTES, 'UTF-8'); ?>" href="<?= htmlspecialchars($department['href'], ENT_QUOTES, 'UTF-8'); ?>" class="group rounded-3xl border border-stroke bg-glass p-6 transition hover:-translate-y-1 hover:border-pri/70 hover:shadow-deep">
                    <span class="text-xs font-black uppercase tracking-[0.28em] text-pri"><?= htmlspecialchars($department['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3 class="mt-4 text-2xl font-black text-acc"><?= htmlspecialchars($department['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="mt-3 text-sm leading-6 text-muted"><?= htmlspecialchars($department['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <span class="mt-5 inline-flex text-sm font-black text-pri">Apri reparto &rarr;</span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section data-animate>
        <div class="mb-10 max-w-3xl">
            <p class="text-sm font-black uppercase tracking-[0.35em] text-pri">In evidenza</p>
            <h2 class="mt-4 text-3xl font-black text-acc md:text-5xl">Prodotti da negozio, con consulenza da laboratorio.</h2>
            <p class="mt-4 text-muted">Prezzi e disponibilita arrivano dal catalogo storico: prima di acquistare puoi chiedere conferma, alternativa o configurazione su misura.</p>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach (array_slice($productsList, 0, 6) as $index => $product): ?>
                <a href="/products/<?= htmlspecialchars($product['slug'], ENT_QUOTES, 'UTF-8'); ?>" data-animate data-animate-delay="<?= $index * 80; ?>">
                    <?php View::renderPartial('public/partials/product-card', ['product' => $product]); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="grid gap-6 rounded-[2rem] border border-pri/40 bg-pri/10 p-8 md:grid-cols-[1fr_auto] md:items-center" data-animate>
        <div>
            <h2 class="text-3xl font-black text-acc">Non sai cosa scegliere? Meglio cosi.</h2>
            <p class="mt-3 text-muted">Il valore di Bisped e aiutarti a comprare una volta sola, bene: prodotto giusto, configurazione pulita, assistenza quando serve.</p>
        </div>
        <a href="/contatti" class="rounded-full bg-pri px-7 py-4 text-center text-sm font-black text-white transition hover:bg-pri-700">Parla con Bisped</a>
    </section>
</div>
