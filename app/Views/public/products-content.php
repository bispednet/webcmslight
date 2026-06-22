<?php
/** @var array $counts  macro => conteggio */
/** @var array $subcats macro => [[slug,label,n]] */

// Definizione macro: label, icona, sottotitolo
$macros = [
    'smartphone'    => ['label' => 'Smartphone',    'sub' => 'Cellulari, smartwatch, telefonia',          'icon' => 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3'],
    'notebook'      => ['label' => 'Notebook',       'sub' => 'Portatili per lavoro, studio e mobilità',   'icon' => 'M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3'],
    'pc-assemblati' => ['label' => 'PC',             'sub' => 'Desktop, all-in-one e mini PC',             'icon' => 'M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Z'],
    'pc-custom'     => ['label' => 'PC-Custom',      'sub' => 'PC assemblati e configurabili da Bisped',    'icon' => 'M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19 14.5m-4.75-11.396c.251.023.501.05.75.082M19 14.5l-2.621 4.716A2.25 2.25 0 0 1 14.412 20.5H9.588a2.25 2.25 0 0 1-1.967-1.284L5 14.5m14 0H5'],
    'componenti'    => ['label' => 'Componenti',     'sub' => 'CPU, RAM, SSD, schede madri, case',         'icon' => 'M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0 1 21 12v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6c0-.98.626-1.813 1.5-2.122'],
    'monitor'       => ['label' => 'Monitor',        'sub' => 'Display per ufficio, design e casa',        'icon' => 'M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3'],
    'gaming'        => ['label' => 'Gaming',          'sub' => 'Schede video, rig e periferiche',           'icon' => 'M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 0 1-.657.643 48.39 48.39 0 0 1-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 0 1-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 0 0-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 0 1-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 0 0 .657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 0 1-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 0 0 5.427-.63 48.05 48.05 0 0 0 .582-4.717.532.532 0 0 0-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.959.401v0a.656.656 0 0 0 .658-.663 48.422 48.422 0 0 0-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 0 1-.61-.58v0Z'],
    'connettivita'  => ['label' => 'Connettività',   'sub' => 'Router, switch, access point, sicurezza',   'icon' => 'M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z'],
    'server'        => ['label' => 'Server',         'sub' => 'Server, NAS, rack e storage enterprise',    'icon' => 'M21.75 17.25v-.228a4.5 4.5 0 0 0-.12-1.03l-2.268-9.64a3.375 3.375 0 0 0-3.285-2.602H7.923a3.375 3.375 0 0 0-3.285 2.602l-2.268 9.64a4.5 4.5 0 0 0-.12 1.03v.228m19.5 0a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3m19.5 0a3 3 0 0 0-3-3H5.25a3 3 0 0 0-3 3m16.5 0h.008v.008h-.008v-.008Zm-3 0h.008v.008h-.008v-.008Z'],
    'stampa'        => ['label' => 'Stampa',          'sub' => 'Stampanti, multifunzione, scanner',         'icon' => 'M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z'],
    'audio-video'   => ['label' => 'Audio & TV',     'sub' => 'Cuffie, speaker, TV e proiettori',          'icon' => 'M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z'],
    'accessori'     => ['label' => 'Accessori',      'sub' => 'Tablet, cavi, mouse, webcam, UPS',          'icon' => 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z'],
];

$totalCount = array_sum($counts);

function macroIcon(string $path): string
{
    return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="' . $path . '"/></svg>';
}
?>

<!-- Header -->
<section data-animate>
    <div class="mb-2 section-label">Shop Bisped</div>
    <h1 class="font-display text-4xl font-black md:text-5xl lg:text-6xl leading-none" style="color:var(--c-acc)">
        Catalogo<br>
        <span style="color:var(--bisped-red)"><?= number_format($totalCount, 0, ',', '.') ?> prodotti disponibili.</span>
    </h1>
    <p class="mt-5 max-w-2xl text-muted text-lg">
        Scegli un reparto, affina per sotto-categoria o cerca direttamente. Disponibilità reale, spedizione rapida.
    </p>

    <!-- Search / sort -->
    <div class="mt-6 grid max-w-3xl gap-3 sm:grid-cols-[1fr_220px]">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input id="product-search" type="search" placeholder="Cerca prodotto, marca, categoria…"
                   class="form-input pl-9 w-full text-sm" autocomplete="off">
        </div>
        <select id="product-sort" class="form-input text-sm" aria-label="Ordina prodotti">
            <option value="featured">Ordina: rilevanza</option>
            <option value="price_asc">Prezzo crescente</option>
            <option value="price_desc">Prezzo decrescente</option>
            <option value="name_asc">Prodotto A-Z</option>
            <option value="newest">Ultimi inseriti</option>
        </select>
    </div>

    <!-- Macro pills -->
    <div class="mt-5 flex flex-wrap gap-2" id="cat-pills">
        <button type="button" class="cat-pill active" data-filter="all">
            Tutti
            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background:rgba(209,25,32,.15);color:var(--bisped-red)"><?= $totalCount ?></span>
        </button>
        <?php foreach ($macros as $key => $m): if (empty($counts[$key])) continue; ?>
            <button type="button" class="cat-pill" data-filter="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                <?= macroIcon($m['icon']) ?>
                <?= htmlspecialchars($m['label'], ENT_QUOTES, 'UTF-8') ?>
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background:rgba(255,255,255,.07);color:var(--c-muted)"><?= (int)$counts[$key] ?></span>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Sub-category pills (popolati via JS) -->
    <div class="mt-3 flex flex-wrap gap-2" id="sub-pills" hidden></div>
</section>

<!-- Active reparto heading -->
<section data-animate>
    <div id="reparto-head" class="mb-6" hidden>
        <div class="section-label mb-2" id="reparto-label"></div>
        <p class="text-muted text-sm" id="reparto-sub"></p>
    </div>

    <!-- Product grid (lazy loaded) -->
    <div id="product-grid" class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"></div>

    <!-- Loading / load more / empty -->
    <div class="mt-8 text-center">
        <div id="grid-loading" class="text-sm text-muted" hidden>Caricamento…</div>
        <button id="load-more" type="button" class="btn-outline" hidden>Carica altri prodotti</button>
        <p id="no-results" class="text-sm" style="color:var(--c-muted)" hidden>
            Nessun prodotto trovato. <a href="/contatti" style="color:var(--bisped-red)">Richiedicelo →</a>
        </p>
    </div>
</section>

<!-- CTA -->
<section class="cta-strip" data-animate>
    <div class="grid md:grid-cols-[1fr_auto] gap-6 items-center">
        <div>
            <h2 class="font-display text-2xl font-black" style="color:var(--c-acc)">Non trovi quello che cerchi?</h2>
            <p class="mt-2 text-muted">Bisped può ordinare qualsiasi prodotto tech. Contattaci con marca, modello o utilizzo previsto.</p>
        </div>
        <a href="/contatti" class="btn-primary">Richiedi un prodotto</a>
    </div>
</section>

<script>
(function () {
    const SUBCATS = <?= json_encode($subcats, JSON_UNESCAPED_UNICODE) ?>;
    const macroLabels = <?= json_encode(array_map(fn($m) => $m, $macros), JSON_UNESCAPED_UNICODE) ?>;

    const search   = document.getElementById('product-search');
    const sortSel  = document.getElementById('product-sort');
    const catPills = document.querySelectorAll('#cat-pills [data-filter]');
    const subWrap  = document.getElementById('sub-pills');
    const grid     = document.getElementById('product-grid');
    const loading  = document.getElementById('grid-loading');
    const loadMore = document.getElementById('load-more');
    const noRes    = document.getElementById('no-results');
    const head     = document.getElementById('reparto-head');
    const headLbl  = document.getElementById('reparto-label');
    const headSub  = document.getElementById('reparto-sub');

    let activeCat = 'all', activeSub = 'all', page = 1, busy = false, hasMore = false, q = '', sort = 'featured';
    let searchTimer = null;

    function fetchPage(reset) {
        if (busy) return;
        busy = true;
        if (reset) { page = 1; grid.innerHTML = ''; }
        loading.hidden = false;
        loadMore.hidden = true;
        noRes.hidden = true;

        const url = `/products/load?cat=${encodeURIComponent(activeCat)}&sub=${encodeURIComponent(activeSub)}&q=${encodeURIComponent(q)}&sort=${encodeURIComponent(sort)}&page=${page}`;
        fetch(url)
            .then(r => r.json())
            .then(data => {
                grid.insertAdjacentHTML('beforeend', data.html);
                hasMore = data.hasMore;
                loadMore.hidden = !hasMore;
                noRes.hidden = !(data.total === 0);
                if (window.bispedAnimate) window.bispedAnimate();
            })
            .catch(() => { noRes.hidden = false; noRes.textContent = 'Errore di caricamento. Riprova.'; })
            .finally(() => { loading.hidden = true; busy = false; });
    }

    function buildSubPills() {
        subWrap.innerHTML = '';
        const subs = SUBCATS[activeCat] || [];
        if (activeCat === 'all' || subs.length <= 1) { subWrap.hidden = true; return; }
        subWrap.appendChild(makeSubPill('all', 'Tutti', true));
        subs.forEach(s => subWrap.appendChild(makeSubPill(s.slug, s.label + ' (' + s.n + ')', false)));
        subWrap.hidden = false;
    }

    function makeSubPill(slug, label, active) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'cat-pill cat-pill--sub' + (active ? ' active' : '');
        b.textContent = label;
        b.addEventListener('click', () => {
            subWrap.querySelectorAll('button').forEach(p => p.classList.remove('active'));
            b.classList.add('active');
            activeSub = slug;
            fetchPage(true);
        });
        return b;
    }

    function updateHead() {
        if (activeCat === 'all') { head.hidden = true; return; }
        const m = macroLabels[activeCat];
        if (!m) { head.hidden = true; return; }
        headLbl.textContent = m.label;
        headSub.textContent = m.sub;
        head.hidden = false;
    }

    catPills.forEach(pill => {
        pill.addEventListener('click', () => {
            catPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            activeCat = pill.dataset.filter;
            activeSub = 'all';
            buildSubPills();
            updateHead();
            fetchPage(true);
            window.scrollTo({ top: head.offsetTop - 90, behavior: 'smooth' });
        });
    });

    search.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { q = search.value.trim(); fetchPage(true); }, 300);
    });

    sortSel.addEventListener('change', () => {
        sort = sortSel.value;
        fetchPage(true);
    });

    loadMore.addEventListener('click', () => { page++; fetchPage(false); });

    // Deep-link: ?cat=gaming&sub=schede-video pre-seleziona reparto/sotto-categoria
    const params  = new URLSearchParams(window.location.search);
    const initCat = params.get('cat');
    const initSub = params.get('sub');
    const initSort = params.get('sort');
    if (initSort && sortSel.querySelector(`[value="${CSS.escape(initSort)}"]`)) {
        sort = initSort;
        sortSel.value = initSort;
    }
    if (initCat && macroLabels[initCat]) {
        activeCat = initCat;
        catPills.forEach(p => p.classList.toggle('active', p.dataset.filter === initCat));
        buildSubPills();
        updateHead();
        if (initSub) {
            activeSub = initSub;
            subWrap.querySelectorAll('button').forEach(b => {
                const match = (b.textContent.trim().toLowerCase().startsWith(
                    (SUBCATS[initCat] || []).find(s => s.slug === initSub)?.label.toLowerCase() || '\0'));
                b.classList.toggle('active', match);
            });
        }
    }

    // Caricamento iniziale
    fetchPage(true);
})();
</script>
