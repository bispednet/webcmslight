<?php
/** @var array $settings */

$phone   = $settings['contact_phone']   ?? '+39 0565 200000';
$email   = $settings['contact_email']   ?? 'negozio@bisped.net';
$address = $settings['address_street']  ?? 'Via delle Canossiane 32';
$city    = $settings['address_city']    ?? 'Piombino (LI)';
$cap     = $settings['address_cap']     ?? '57025';
?>

<div class="space-y-16">

    <!-- Header -->
    <section data-animate>
        <p class="section-label mb-5">Punto vendita</p>
        <h1 class="font-display text-4xl font-black md:text-5xl" style="color:var(--c-acc)">Dove siamo</h1>
        <p class="mt-4 max-w-2xl text-lg" style="color:var(--c-muted)">Vieni a trovarci in negozio a Piombino: vendiamo, ripariamo e consigliamo di persona. Nessun bot, nessun form obbligatorio.</p>
    </section>

    <!-- Grid: mappa + info -->
    <section class="grid gap-8 lg:grid-cols-[1fr_360px] lg:items-start">

        <!-- Mappa Google -->
        <div class="rounded-lg overflow-hidden border" style="border-color:var(--c-border);aspect-ratio:16/9">
            <iframe
                title="bisp&d Piombino"
                src="https://maps.google.com/maps?q=Piombino+LI+Via+delle+Canossiane&output=embed&z=15"
                width="100%" height="100%"
                style="border:0;display:block"
                allowfullscreen
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>

        <!-- Info contatto -->
        <aside class="space-y-4">
            <div class="info-card">
                <h2 class="font-display text-lg font-black mb-4" style="color:var(--c-acc)">bisp&amp;d s.r.l.</h2>
                <div class="space-y-3 text-sm" style="color:var(--c-muted)">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0 mt-0.5" style="color:var(--bisped-red)"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                        <span><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?><br><?= htmlspecialchars($cap . ' — ' . $city, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0" style="color:var(--bisped-red)"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 6Z"/></svg>
                        <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $phone), ENT_QUOTES, 'UTF-8') ?>" class="hover:underline"><?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?></a>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0" style="color:var(--bisped-red)"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
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

            <!-- Social quick links -->
            <div class="info-card">
                <h3 class="font-display text-base font-black mb-3" style="color:var(--c-acc)">Contatti rapidi</h3>
                <div class="flex flex-col gap-2">
                    <a href="https://wa.me/393346582116" target="_blank" rel="noopener"
                       class="flex items-center gap-3 text-sm transition-colors hover:text-green-400" style="color:var(--c-muted)">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 flex-shrink-0" style="color:#25d366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                        WhatsApp: +39 334 658 2116
                    </a>
                    <a href="https://www.facebook.com/bispednet" target="_blank" rel="noopener"
                       class="flex items-center gap-3 text-sm transition-colors hover:text-blue-400" style="color:var(--c-muted)">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 flex-shrink-0" style="color:#1877f2"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12Z"/></svg>
                        Seguici su Facebook
                    </a>
                </div>
            </div>
        </aside>
    </section>

    <!-- Foto negozio + CTA -->
    <section class="grid gap-6 md:grid-cols-2 items-center" data-animate>
        <div>
            <img src="/media/bisped/fronte_negozio_bisped.png"
                 alt="Ingresso negozio bisp&d Piombino"
                 class="w-full rounded-lg border object-cover"
                 style="border-color:var(--c-border);aspect-ratio:16/10">
        </div>
        <div class="info-card">
            <h2 class="font-display text-xl font-black mb-3" style="color:var(--c-acc)">Vieni di persona, senza appuntamento</h2>
            <p class="text-sm leading-6 mb-5" style="color:var(--c-muted)">Hai un dispositivo da riparare, un acquisto da valutare o vuoi semplicemente capire cosa ti serve? Il banco è aperto: nessun ticket obbligatorio, nessuna attesa telefonica.</p>
            <div class="flex flex-wrap gap-3">
                <a href="tel:+393346582116" class="btn-primary btn-sm">Chiama</a>
                <a href="/contatti" class="btn-outline btn-sm">Scrivi online</a>
            </div>
        </div>
    </section>

</div>
