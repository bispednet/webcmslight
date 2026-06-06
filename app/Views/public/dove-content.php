<?php
/** @var array $settings */

$phone   = '+39 0565 31136';
$waPhone = '+39 334 658 2116';
$email   = 'negozio@bisped.net';
$address = 'Piazza della Costituzione, 68';
$city    = 'Piombino (LI)';
$cap     = '57025';

// Cache-bust the store photo using filemtime
$storePhotoPath = dirname(__DIR__, 4) . '/public/media/bisped/fronte_negozio_bisped.png';
$storePhotoV    = @filemtime($storePhotoPath) ?: time();
$storePhotoUrl  = '/media/bisped/fronte_negozio_bisped.png?v=' . $storePhotoV;

// Google Maps embed for Piazza della Costituzione 68, Piombino
$mapsEmbedSrc   = 'https://maps.google.com/maps?q=Piazza+della+Costituzione+68+Piombino+LI+57025+Italia&output=embed&z=16&hl=it';
$mapsDirections = 'https://www.google.com/maps/dir/?api=1&destination=Piazza+della+Costituzione+68,+57025+Piombino+LI';
$mapsLink       = 'https://maps.google.com/?q=bisp%26d+Piombino+Piazza+della+Costituzione+68';
?>

<div class="space-y-16">

    <!-- Header -->
    <section data-animate>
        <p class="section-label mb-5">Punto vendita</p>
        <h1 class="font-display text-4xl font-black md:text-5xl" style="color:var(--c-acc)">Dove siamo</h1>
        <p class="mt-4 max-w-2xl text-lg" style="color:var(--c-muted)">
            Vieni a trovarci in negozio a Piombino: vendiamo, ripariamo e consigliamo di persona.
            Nessun bot, nessun form obbligatorio.
        </p>
    </section>

    <!-- Grid: mappa + info -->
    <section class="grid gap-8 lg:grid-cols-[1fr_360px] lg:items-start">

        <!-- Mappa Google -->
        <div class="space-y-3">
            <div class="rounded-lg overflow-hidden border" style="border-color:var(--c-border);aspect-ratio:16/9">
                <iframe
                    title="bisp&amp;d — Piazza della Costituzione 68, Piombino"
                    src="<?= htmlspecialchars($mapsEmbedSrc, ENT_QUOTES, 'UTF-8') ?>"
                    width="100%" height="100%"
                    style="border:0;display:block"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <a href="<?= htmlspecialchars($mapsDirections, ENT_QUOTES, 'UTF-8') ?>"
               target="_blank" rel="noopener"
               class="btn-primary w-full flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z"/>
                </svg>
                Raggiungi il negozio con Google Maps
            </a>
        </div>

        <!-- Info contatto -->
        <aside class="space-y-4">
            <div class="info-card">
                <h2 class="font-display text-lg font-black mb-4" style="color:var(--c-acc)">bisp&amp;d s.r.l.</h2>
                <div class="space-y-3 text-sm" style="color:var(--c-muted)">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0 mt-0.5" style="color:var(--bisped-red)">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                        </svg>
                        <span>
                            <?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?><br>
                            <?= htmlspecialchars($cap . ' — ' . $city, ENT_QUOTES, 'UTF-8') ?>
                            <br>
                            <a href="<?= htmlspecialchars($mapsLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"
                               class="text-xs font-bold" style="color:var(--bisped-red)">Vedi su Google Maps →</a>
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0" style="color:var(--bisped-red)">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 6Z"/>
                        </svg>
                        <a href="tel:+39056531136" class="hover:underline"><?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?></a>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 flex-shrink-0" style="color:#25d366">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
                        </svg>
                        <a href="https://wa.me/393346582116" target="_blank" rel="noopener" class="hover:underline">
                            WhatsApp: <?= htmlspecialchars($waPhone, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0" style="color:var(--bisped-red)">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                        </svg>
                        <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" class="hover:underline"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></a>
                    </div>
                </div>
            </div>

            <!-- Orari -->
            <div class="info-card">
                <h3 class="font-display text-base font-black mb-4" style="color:var(--c-acc)">Orari di apertura</h3>
                <div class="space-y-2 text-sm" style="color:var(--c-muted)">
                    <div class="flex justify-between">
                        <span class="font-bold" style="color:var(--c-txt)">Lun – Ven</span>
                        <span>9:00 – 13:00 / 15:30 – 19:30</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold" style="color:var(--c-txt)">Sabato</span>
                        <span>9:00 – 13:00</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold" style="color:var(--c-txt)">Domenica</span>
                        <span>Chiuso</span>
                    </div>
                    <p class="text-xs pt-2 border-t" style="border-color:var(--c-border)">Teleassistenza disponibile anche fuori orario su appuntamento.</p>
                </div>
            </div>

            <!-- Google Reviews CTA -->
            <a href="https://www.google.com/maps/search/bisp%26d+Piombino+Piazza+della+Costituzione" target="_blank" rel="noopener"
               class="info-card flex items-center gap-4 hover:border-red-500 transition-colors cursor-pointer" style="text-decoration:none;display:flex">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-8 h-8 flex-shrink-0" style="fill:#4285f4">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" style="fill:#4285f4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" style="fill:#34a853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" style="fill:#fbbc05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" style="fill:#ea4335"/>
                </svg>
                <div>
                    <div class="flex items-center gap-1 mb-1">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                        <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="#FBBF24">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/>
                        </svg>
                        <?php endfor; ?>
                        <span class="text-xs font-bold ml-1" style="color:var(--c-acc)">Le nostre recensioni Google</span>
                    </div>
                    <p class="text-xs" style="color:var(--c-muted)">Leggi cosa dicono i nostri clienti →</p>
                </div>
            </a>
        </aside>
    </section>

    <!-- Google Reviews section -->
    <section data-animate>
        <p class="section-label mb-5">Cosa dicono di noi</p>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

            <div class="review-card">
                <div class="review-card__stars">
                    <?php for ($i = 0; $i < 5; $i++): ?><svg class="review-card__star" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/></svg><?php endfor; ?>
                </div>
                <p class="review-card__text">"Professionali, veloci e trasparenti. Ho portato il PC quasi spacciato, l'hanno riparato in 24 ore e mi hanno anche spiegato cosa era successo. Prezzi onesti."</p>
                <div class="review-card__author">Luca B.</div>
                <div class="review-card__source">⭐ Google — Piombino</div>
            </div>

            <div class="review-card">
                <div class="review-card__stars">
                    <?php for ($i = 0; $i < 5; $i++): ?><svg class="review-card__star" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/></svg><?php endfor; ?>
                </div>
                <p class="review-card__text">"Ho acquistato il mio MacBook qui: mi hanno spiegato tutto senza farmi sentire ignorante, configurato al momento e seguito dopo la vendita. Consiglio a tutti."</p>
                <div class="review-card__author">Federica R.</div>
                <div class="review-card__source">⭐ Google — Acquisto MacBook</div>
            </div>

            <div class="review-card">
                <div class="review-card__stars">
                    <?php for ($i = 0; $i < 5; $i++): ?><svg class="review-card__star" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/></svg><?php endfor; ?>
                </div>
                <p class="review-card__text">"Ho chiamato per un problema di rete aziendale a distanza di 30 minuti mi hanno risolto tutto in teleassistenza. Tempestivi e competenti, li userò sempre."</p>
                <div class="review-card__author">Marco V.</div>
                <div class="review-card__source">⭐ Google — Teleassistenza</div>
            </div>

            <div class="review-card">
                <div class="review-card__stars">
                    <?php for ($i = 0; $i < 5; $i++): ?><svg class="review-card__star" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/></svg><?php endfor; ?>
                </div>
                <p class="review-card__text">"Ottimo negozio, personale preparato e gentile. Sempre aggiornati sui nuovi prodotti e mai pressanti. Acquistato smartphone e notebook, entrambe esperienze positive."</p>
                <div class="review-card__author">Simona T.</div>
                <div class="review-card__source">⭐ Google — Cliente abituale</div>
            </div>

            <div class="review-card">
                <div class="review-card__stars">
                    <?php for ($i = 0; $i < 5; $i++): ?><svg class="review-card__star" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/></svg><?php endfor; ?>
                </div>
                <p class="review-card__text">"Finalmente un negozio dove ti ascoltano davvero. Non cercano di venderti il prodotto più costoso ma quello giusto per te. La fibra che hanno configurato funziona perfettamente."</p>
                <div class="review-card__author">Andrea M.</div>
                <div class="review-card__source">⭐ Google — Connettività</div>
            </div>

            <div class="review-card" style="display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center">
                <div class="text-4xl font-black mb-2" style="color:var(--bisped-red)">★ 4.9</div>
                <p class="text-sm font-bold mb-3" style="color:var(--c-acc)">su Google Reviews</p>
                <a href="https://www.google.com/maps/search/bisp%26d+Piombino+Piazza+della+Costituzione"
                   target="_blank" rel="noopener" class="btn-primary btn-sm">
                    Leggi tutte le recensioni
                </a>
                <p class="text-xs mt-3" style="color:var(--c-muted)">Anche tu sei stato da noi? <br>
                    <a href="https://www.google.com/maps/search/bisp%26d+Piombino+Piazza+della+Costituzione"
                       target="_blank" rel="noopener" style="color:var(--bisped-red)">Lascia la tua recensione →</a>
                </p>
            </div>
        </div>
    </section>

    <!-- Foto negozio + CTA -->
    <section class="grid gap-6 md:grid-cols-2 items-center" data-animate>
        <div>
            <img src="<?= htmlspecialchars($storePhotoUrl, ENT_QUOTES, 'UTF-8') ?>"
                 alt="Ingresso negozio bisp&d — Piazza della Costituzione 68, Piombino"
                 class="w-full rounded-lg border object-cover"
                 style="border-color:var(--c-border);aspect-ratio:16/10">
        </div>
        <div class="info-card">
            <h2 class="font-display text-xl font-black mb-3" style="color:var(--c-acc)">Vieni di persona, senza appuntamento</h2>
            <p class="text-sm leading-6 mb-5" style="color:var(--c-muted)">
                Hai un dispositivo da riparare, un acquisto da valutare o vuoi semplicemente capire cosa ti serve?
                Il banco è aperto: nessun ticket obbligatorio, nessuna attesa telefonica.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="tel:+39056531136" class="btn-primary btn-sm">Chiama: 0565 31136</a>
                <a href="/contatti" class="btn-outline btn-sm">Scrivi online</a>
            </div>
        </div>
    </section>

</div>
