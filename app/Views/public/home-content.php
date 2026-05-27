<?php
use App\Core\View;
use App\Support\AdminMode;

/** @var array $settings */
/** @var array $products */

$tagline      = $settings['site_tagline']     ?? 'Informatica · Telefonia · Assistenza · Piombino';
$heroTitle    = $settings['hero_title_home']  ?? 'Tecnologia scelta bene.<br>Installata meglio.';
$heroSubtitle = $settings['hero_subtitle_home'] ?? 'Bisped è il punto di riferimento tech a Piombino: PC, smartphone, gaming, connettività, energia e assistenza tecnica con persone vere.';
$heroImage    = $settings['hero_image_home']  ?? '/media/bisped/fronte_negozio_bisped.png';
$productsList = array_values($products);

$departments = [
    [
        'id'    => 'informatica',
        'label' => 'Informatica',
        'title' => 'PC, notebook, monitor e componenti',
        'text'  => 'Soluzioni selezionate per lavoro, studio e postazioni professionali.',
        'href'  => '/products#informatica',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3"/></svg>',
    ],
    [
        'id'    => 'smartphone',
        'label' => 'Smartphone',
        'title' => 'Telefonia pronta all\'uso',
        'text'  => 'Dispositivi, SIM, trasferimento dati, accessori e configurazioni senza stress.',
        'href'  => '/products#smartphone',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>',
    ],
    [
        'id'    => 'gaming',
        'label' => 'Gaming',
        'title' => 'Setup, rig e periferiche',
        'text'  => 'Dal PC assemblato al dettaglio che cambia la partita: prestazioni, estetica, affidabilità.',
        'href'  => '/products#gaming',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 0 1-.657.643 48.39 48.39 0 0 1-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 0 1-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 0 0-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 0 1-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 0 0 .657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 0 1-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 0 0 5.427-.63 48.05 48.05 0 0 0 .582-4.717.532.532 0 0 0-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.959.401v0a.656.656 0 0 0 .658-.663 48.422 48.422 0 0 0-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 0 1-.61-.58v0Z"/></svg>',
    ],
    [
        'id'    => 'assistenza',
        'label' => 'Assistenza',
        'title' => 'Laboratorio e supporto remoto',
        'text'  => 'Diagnosi, riparazione, recupero dati, upgrade e manutenzione programmata.',
        'href'  => '/servizi#assistenza',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/></svg>',
    ],
    [
        'id'    => 'connettivita',
        'label' => 'Connettività',
        'title' => 'Fibra, FWA, reti e Wi-Fi',
        'text'  => 'Verifica copertura, attivazioni, router, rete domestica e piccoli uffici.',
        'href'  => '/servizi#connettivita',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z"/></svg>',
    ],
    [
        'id'    => 'energia',
        'label' => 'Energia',
        'title' => 'Luce, gas e risparmio',
        'text'  => 'Consulenza commerciale chiara per scegliere offerte adatte a casa e impresa.',
        'href'  => '/servizi#energia',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"/></svg>',
    ],
];

// Featured products: pick only those with images
$featured = array_filter($productsList, fn($p) => !empty($p['image_url']));
$featured = array_slice(array_values($featured), 0, 8);
if (empty($featured)) {
    $featured = array_slice($productsList, 0, 8);
}
?>

<!-- ─── HERO ─────────────────────────────────────────────────────────────── -->
<section class="relative pt-8 pb-16 md:pt-12 md:pb-20 tech-grid" data-animate>
    <div class="grid gap-10 lg:grid-cols-[1fr_420px] lg:items-center">
        <div>
            <div class="section-label mb-6"
                 <?= AdminMode::dataAttrs('settings', 'site_tagline') ?>>
                <?= htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <h1 class="font-display text-[2.6rem] sm:text-6xl lg:text-[4.5rem] font-black leading-[0.92] tracking-tight"
                style="color:var(--c-acc)"
                <?= AdminMode::dataAttrs('settings', 'hero_title_home') ?>>
                <?= $heroTitle ?>
            </h1>
            <p class="mt-6 max-w-xl text-base leading-7 text-muted lg:text-lg"
               <?= AdminMode::dataAttrs('settings', 'hero_subtitle_home') ?>>
                <?= htmlspecialchars($heroSubtitle, ENT_QUOTES, 'UTF-8') ?>
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="/products" class="btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                    </svg>
                    Vedi il catalogo
                </a>
                <a href="/servizi#assistenza" class="btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/>
                    </svg>
                    Apri un ticket
                </a>
            </div>

            <!-- Trust stats -->
            <div class="mt-10 grid grid-cols-3 gap-3 max-w-sm">
                <div class="text-center p-3 border rounded-md" style="border-color:var(--c-border);background:var(--c-surface)">
                    <div class="font-display text-2xl font-black" style="color:var(--bisped-red)">20+</div>
                    <div class="text-xs font-bold uppercase tracking-widest text-muted mt-1">Anni</div>
                </div>
                <div class="text-center p-3 border rounded-md" style="border-color:var(--c-border);background:var(--c-surface)">
                    <div class="font-display text-2xl font-black" style="color:var(--bisped-red)">Lab</div>
                    <div class="text-xs font-bold uppercase tracking-widest text-muted mt-1">Riparazioni</div>
                </div>
                <div class="text-center p-3 border rounded-md" style="border-color:var(--c-border);background:var(--c-surface)">
                    <div class="font-display text-2xl font-black" style="color:var(--bisped-red)">500+</div>
                    <div class="text-xs font-bold uppercase tracking-widest text-muted mt-1">Prodotti</div>
                </div>
            </div>
        </div>

        <!-- Store photo -->
        <div class="relative" <?= AdminMode::dataAttrs('settings', 'hero_image_home', null, 'image') ?>>
            <div class="absolute -inset-6 rounded-xl blur-3xl" style="background:var(--bisped-red-glow);opacity:.4"></div>
            <img src="<?= htmlspecialchars($heroImage, ENT_QUOTES, 'UTF-8') ?>"
                 alt="Negozio Bisped a Piombino"
                 class="relative w-full rounded-lg border shadow-lg object-cover"
                 style="border-color:var(--c-border);box-shadow:var(--shadow-hover);aspect-ratio:16/10"
                 loading="eager"
                 width="840" height="525">
        </div>
    </div>
</section>

<!-- ─── PROMO BANNERS ────────────────────────────────────────────────────────── -->
<section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 py-2" data-animate>
    <!-- Banner principale: Gaming RIG -->
    <a href="/products#gaming" class="promo-banner lg:col-span-2" style="aspect-ratio:16/7">
        <img src="/media/banners/banner-gaming-rig.jpg" alt="PC Gaming Build Bisped" loading="eager">
        <div class="promo-banner__overlay">
            <span class="promo-banner__tag">Promozione</span>
            <p class="promo-banner__title">Scegli le nostre<br>Build specifiche<br>per il Gaming</p>
            <span class="promo-banner__cta">Scopri →</span>
        </div>
    </a>
    <!-- Banner secondario: Smartphone -->
    <a href="/products#smartphone" class="promo-banner" style="aspect-ratio:16/7">
        <img src="/media/banners/banner-xiaomi.jpg" alt="Scopri Xiaomi" loading="lazy">
        <div class="promo-banner__overlay">
            <span class="promo-banner__tag">Ricarica ultra-rapida</span>
            <p class="promo-banner__title">Scopri<br>Xiaomi</p>
            <span class="promo-banner__cta">Vedi smartphone →</span>
        </div>
    </a>
</section>

<!-- ─── TRUST BAR ─────────────────────────────────────────────────────────── -->
<section class="trust-bar py-4 -mx-4 md:-mx-6 lg:-mx-0" data-animate>
    <div class="container mx-auto max-w-7xl px-4 lg:px-6">
        <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-3 text-xs font-bold uppercase tracking-wider text-muted">
            <span class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4" style="color:var(--bisped-red)"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/></svg>
                Garanzia ufficiale
            </span>
            <span class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4" style="color:var(--bisped-red)"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                Spedizione disponibile
            </span>
            <span class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4" style="color:var(--bisped-red)"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/></svg>
                Assistenza tecnica locale
            </span>
            <span class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4" style="color:var(--bisped-red)"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 6Z"/></svg>
                Consulenza gratuita
            </span>
            <span class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4" style="color:var(--bisped-red)"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/></svg>
                Teleassistenza remota
            </span>
        </div>
    </div>
</section>

<!-- ─── BRAND PARTNER BAR ────────────────────────────────────────────────────── -->
<section class="brand-bar -mx-4 md:-mx-6 lg:-mx-0" aria-label="Partner e brand">
    <div class="container mx-auto max-w-7xl px-4 lg:px-6">
        <div class="brand-bar__track">
            <span class="text-xs font-black uppercase tracking-widest text-muted opacity-50 flex-shrink-0">Partner ufficiali</span>
            <img class="brand-logo" src="/media/brands/tim.png"      alt="TIM"      loading="lazy">
            <img class="brand-logo" src="/media/brands/vodafone.png" alt="Vodafone" loading="lazy">
            <img class="brand-logo" src="/media/brands/wind.png"     alt="WindTre"  loading="lazy">
            <img class="brand-logo" src="/media/brands/tre.png"      alt="Tre"      loading="lazy">
            <img class="brand-logo" src="/media/brands/enel.png"     alt="Enel"     loading="lazy">
            <img class="brand-logo" src="/media/brands/eni.png"      alt="Eni"      loading="lazy">
            <img class="brand-logo" src="/media/brands/msi.png"      alt="MSI"      loading="lazy">
            <span class="brand-label">ASUS</span>
            <span class="brand-label">Intel</span>
            <span class="brand-label">AMD</span>
            <span class="brand-label">Samsung</span>
            <span class="brand-label">Huawei</span>
            <img class="brand-logo" src="/media/brands/cisco.png"    alt="Cisco"    loading="lazy">
        </div>
    </div>
</section>

<!-- ─── REPARTI ───────────────────────────────────────────────────────────── -->
<section class="py-4" data-animate>
    <div class="mb-8">
        <div class="section-label mb-4">Reparti</div>
        <h2 class="font-display text-3xl font-black md:text-4xl" style="color:var(--c-acc)">Trova subito il banco giusto.</h2>
        <p class="mt-3 text-muted max-w-2xl">Ogni reparto porta a una scelta utile: un prodotto da valutare, un servizio da richiedere, una persona con cui parlare.</p>
    </div>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($departments as $department): ?>
            <a id="<?= htmlspecialchars($department['id'], ENT_QUOTES, 'UTF-8') ?>"
               href="<?= htmlspecialchars($department['href'], ENT_QUOTES, 'UTF-8') ?>"
               class="dept-card">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded flex items-center justify-center"
                         style="background:rgba(209,25,32,.12);color:var(--bisped-red)">
                        <?= $department['icon'] ?>
                    </div>
                    <div>
                        <div class="text-xs font-black uppercase tracking-widest mb-2" style="color:var(--bisped-red)"><?= htmlspecialchars($department['label'], ENT_QUOTES, 'UTF-8') ?></div>
                        <h3 class="font-bold text-base" style="color:var(--c-acc)"><?= htmlspecialchars($department['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="mt-2 text-sm text-muted leading-5"><?= htmlspecialchars($department['text'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
                <span class="mt-4 inline-flex text-sm font-black" style="color:var(--bisped-red)">Apri reparto →</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ─── PRODOTTI IN EVIDENZA ──────────────────────────────────────────────── -->
<section class="py-4" data-animate>
    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <div class="section-label mb-4">In evidenza</div>
            <h2 class="font-display text-3xl font-black md:text-4xl" style="color:var(--c-acc)">Prodotti da negozio,<br>con consulenza da laboratorio.</h2>
        </div>
        <a href="/products" class="text-sm font-black uppercase tracking-widest" style="color:var(--bisped-red)">
            Tutto il catalogo →
        </a>
    </div>
    <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4">
        <?php foreach ($featured as $index => $product): ?>
            <a href="/products/<?= htmlspecialchars($product['slug'], ENT_QUOTES, 'UTF-8') ?>"
               style="text-decoration:none"
               data-animate
               data-animate-delay="<?= $index * 60 ?>">
                <?php View::renderPartial('public/partials/product-card', ['product' => $product]); ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ─── BANNER TELEASSISTENZA ─────────────────────────────────────────────── -->
<section data-animate>
    <a href="/servizi#assistenza" class="promo-banner block" style="aspect-ratio:21/5">
        <img src="/media/banners/banner-teleassistenza.jpg" alt="Teleassistenza Bisped" loading="lazy">
        <div class="promo-banner__overlay" style="background:linear-gradient(135deg,rgba(209,25,32,.6) 0%,rgba(0,0,0,.15) 60%)">
            <span class="promo-banner__tag">Servizio attivo</span>
            <p class="promo-banner__title">Teleassistenza remota —<br>ti aiutiamo anche da casa</p>
            <span class="promo-banner__cta">Richiedi supporto →</span>
        </div>
    </a>
</section>

<!-- ─── RECENSIONI ────────────────────────────────────────────────────────── -->
<section data-animate>
    <div class="mb-8">
        <div class="section-label mb-4">Clienti soddisfatti</div>
        <h2 class="font-display text-3xl font-black md:text-4xl" style="color:var(--c-acc)">I nostri clienti soddisfatti.</h2>
        <p class="mt-3 text-muted max-w-2xl">Quello che dicono le persone che sono passate dal banco, hanno chiamato o ci hanno scritto.</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="review-card" data-animate data-animate-delay="0">
            <div class="review-card__stars">
                <?php for ($i = 0; $i < 5; $i++): ?><svg class="review-card__star" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/></svg><?php endfor; ?>
            </div>
            <p class="review-card__text">"Precisione hardware, bella estetica. Il pc gaming che mi hanno assemblato funziona benissimo e la consulenza è stata eccellente."</p>
            <div class="review-card__author">Marco R.</div>
            <div class="review-card__source">Cliente negozio — Gaming build custom</div>
        </div>
        <div class="review-card" data-animate data-animate-delay="80">
            <div class="review-card__stars">
                <?php for ($i = 0; $i < 5; $i++): ?><svg class="review-card__star" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/></svg><?php endfor; ?>
            </div>
            <p class="review-card__text">"Avevo un hard disk con anni di foto di famiglia. Recupero dati completato in 48h. Non ci speravo più, sono contentissima."</p>
            <div class="review-card__author">Silvia M.</div>
            <div class="review-card__source">Recupero dati — Piombino</div>
        </div>
        <div class="review-card" data-animate data-animate-delay="160">
            <div class="review-card__stars">
                <?php for ($i = 0; $i < 5; $i++): ?><svg class="review-card__star" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/></svg><?php endfor; ?>
            </div>
            <p class="review-card__text">"Gestione completa della nostra rete aziendale e delle linee TIM Business. Professionali, puntuali e sempre disponibili."</p>
            <div class="review-card__author">Antonio B.</div>
            <div class="review-card__source">Soluzione business — Azienda locale</div>
        </div>
    </div>
</section>

<!-- ─── FAQ PREVIEW ────────────────────────────────────────────────────────── -->
<section data-animate>
    <div class="grid gap-10 lg:grid-cols-[1fr_440px] lg:items-start">
        <div>
            <div class="section-label mb-4">Domande frequenti</div>
            <h2 class="font-display text-3xl font-black md:text-4xl mb-2" style="color:var(--c-acc)">Lavoriamo con i migliori in campo.</h2>
            <p class="text-muted text-sm leading-6 mb-6">Le migliori tecnologie informatiche e di telecomunicazione a vostra disposizione: TIM, WindTre, Vodafone, Fastweb, Enel, Heracomm, MSI, ASUS, Intel, AMD, Samsung, Huawei, Philips, HP, Canon.</p>
            <a href="/faq" class="btn-outline btn-sm">Tutte le FAQ →</a>
        </div>
        <div class="space-y-3">
            <details class="faq-item">
                <summary>Quali pagamenti accettate?</summary>
                <p>Accettiamo contanti, bancomat, carte di credito e bonifico bancario. Per acquisti aziendali è possibile concordare pagamenti differiti con fattura.</p>
            </details>
            <details class="faq-item">
                <summary>Come funziona la garanzia?</summary>
                <p>Tutti i prodotti sono venduti con garanzia ufficiale del produttore (minimo 24 mesi per i privati). Per i dispositivi riparati in laboratorio offriamo garanzia sul lavoro eseguito.</p>
            </details>
            <details class="faq-item">
                <summary>Fate spedizioni?</summary>
                <p>Sì, spediamo su tutto il territorio nazionale. Contattateci per un preventivo o per verificare la disponibilità immediata prima di ordinare online.</p>
            </details>
            <details class="faq-item">
                <summary>Quanto costa un'assistenza tecnica?</summary>
                <p>La diagnosi è sempre gratuita. Il preventivo per la riparazione viene comunicato prima di procedere, senza sorprese. Ci rifiutiamo di riparare se non ne vale la pena.</p>
            </details>
        </div>
    </div>
</section>

<!-- ─── CTA STRIP ─────────────────────────────────────────────────────────── -->
<section class="cta-strip" data-animate>
    <div class="grid gap-6 md:grid-cols-[1fr_auto] md:items-center">
        <div>
            <h2 class="font-display text-2xl font-black md:text-3xl" style="color:var(--c-acc)">Non sai cosa scegliere? Meglio così.</h2>
            <p class="mt-2 text-muted max-w-xl">Aiutarti a comprare una volta sola, bene: prodotto giusto, configurazione pulita, assistenza quando serve. Questo è il valore di Bisped.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="/contatti" class="btn-primary">Parla con noi</a>
            <a href="/servizi" class="btn-outline">I nostri servizi</a>
        </div>
    </div>
</section>
