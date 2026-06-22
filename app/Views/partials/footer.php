<?php
use App\Support\Media;

$navigation = $navigation ?? [];
$siteLogoUrl = $siteLogo ?? '';
if ($siteLogoUrl === '') {
    $siteLogoUrl = '/media/bisped/bisped_logo.png';
}
?>

<footer style="background:var(--c-bg2);border-top:1px solid var(--c-border)">

    <!-- Main footer grid -->
    <div class="container mx-auto max-w-7xl px-4 lg:px-6 py-14">
        <div class="grid gap-10 md:grid-cols-[280px_1fr]">

            <!-- Brand column -->
            <div>
                <a href="/" class="flex items-center mb-5">
                    <img src="<?= htmlspecialchars($siteLogoUrl, ENT_QUOTES, 'UTF-8') ?>"
                         alt="bisp&amp;d" class="h-8 w-auto" loading="lazy">
                </a>
                <p class="text-sm text-muted leading-6 mb-5 max-w-xs">
                    Il punto di riferimento tech a Piombino: negozio, laboratorio e consulenza digitale per privati, professionisti e aziende.
                </p>

                <!-- Contact info -->
                <div class="space-y-2">
                    <a href="tel:+39056531136" class="flex items-center gap-2 text-sm text-muted hover:text-pri transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 6Z"/></svg>
                        +39 0565 31136
                    </a>
                    <a href="mailto:negozio@bisped.net" class="flex items-center gap-2 text-sm text-muted hover:text-pri transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                        negozio@bisped.net
                    </a>
                    <div class="flex items-start gap-2 text-sm text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0 mt-0.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                        Piazza della Costituzione, 68 — 57025 Piombino (LI)
                    </div>
                </div>
            </div>

            <!-- Links grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xs font-black uppercase tracking-widest mb-4" style="color:var(--c-acc)">Shop</h3>
                    <ul class="space-y-2.5">
                        <li><a href="/products#informatica" class="text-sm text-muted hover:text-pri transition-colors">Informatica</a></li>
                        <li><a href="/products#smartphone" class="text-sm text-muted hover:text-pri transition-colors">Smartphone</a></li>
                        <li><a href="/products#gaming" class="text-sm text-muted hover:text-pri transition-colors">Gaming</a></li>
                        <li><a href="/products" class="text-sm text-muted hover:text-pri transition-colors">Tutto il catalogo</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-black uppercase tracking-widest mb-4" style="color:var(--c-acc)">Servizi</h3>
                    <ul class="space-y-2.5">
                        <li><a href="/teleassistenza" class="text-sm text-muted hover:text-pri transition-colors">Teleassistenza</a></li>
                        <li><a href="/servizi#assistenza" class="text-sm text-muted hover:text-pri transition-colors">Assistenza PC</a></li>
                        <li><a href="/servizi#connettivita" class="text-sm text-muted hover:text-pri transition-colors">Connettività</a></li>
                        <li><a href="/servizi#fonia" class="text-sm text-muted hover:text-pri transition-colors">Fonia</a></li>
                        <li><a href="/servizi#energia" class="text-sm text-muted hover:text-pri transition-colors">Energia</a></li>
                        <li><a href="/servizi#aziende" class="text-sm text-muted hover:text-pri transition-colors">Business</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-black uppercase tracking-widest mb-4" style="color:var(--c-acc)">Azienda</h3>
                    <ul class="space-y-2.5">
                        <li><a href="/azienda" class="text-sm text-muted hover:text-pri transition-colors">Chi siamo</a></li>
                        <li><a href="/dove" class="text-sm text-muted hover:text-pri transition-colors">Dove siamo</a></li>
                        <li><a href="/blog" class="text-sm text-muted hover:text-pri transition-colors">News & Blog</a></li>
                        <li><a href="/faq" class="text-sm text-muted hover:text-pri transition-colors">FAQ</a></li>
                        <li><a href="/contatti" class="text-sm text-muted hover:text-pri transition-colors">Contatti</a></li>
                        <li><a href="/appuntamenti" class="text-sm text-muted hover:text-pri transition-colors">Appuntamenti</a></li>
                        <li><a href="/sostenibilita" class="text-sm text-muted hover:text-pri transition-colors">Sostenibilità</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-black uppercase tracking-widest mb-4" style="color:var(--c-acc)">Legal</h3>
                    <ul class="space-y-2.5">
                        <li><a href="/legal#privacy-policy" class="text-sm text-muted hover:text-pri transition-colors">Privacy Policy</a></li>
                        <li><a href="/legal#cookie-policy" class="text-sm text-muted hover:text-pri transition-colors">Cookie Policy</a></li>
                        <li><a href="/legal#condizioni-vendita" class="text-sm text-muted hover:text-pri transition-colors">Condizioni di vendita</a></li>
                        <li><a href="/recesso" class="text-sm text-muted hover:text-pri transition-colors">Richiedi recesso</a></li>
                        <li><a href="/login" class="text-sm text-muted hover:text-pri transition-colors">Area riservata</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom bar -->
    <div class="border-t" style="border-color:var(--c-border)">
        <div class="container mx-auto max-w-7xl px-4 lg:px-6 py-5 flex flex-col sm:flex-row justify-between items-center gap-3">
            <p class="text-xs text-muted text-center sm:text-left">
                &copy; <?= date('Y') ?> bisp&amp;d s.r.l. — P.IVA IT0156025048 — REA LI-138175 — Capitale sociale 100.000€ i.v. — Tutti i diritti riservati.<br class="sm:hidden">
                <span class="hidden sm:inline"> · </span>Computer, telefonia, connettività, energia e assistenza tecnica a Piombino (LI).
            </p>
            <div class="flex items-center gap-3">
                <!-- Email icon -->
                <a href="mailto:negozio@bisped.net" aria-label="Email"
                   class="w-8 h-8 rounded flex items-center justify-center border transition-colors text-muted hover:text-pri hover:border-pri"
                   style="border-color:var(--c-border)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                    </svg>
                </a>
                <!-- Phone icon -->
                <a href="tel:+39056531136" aria-label="Telefono"
                   class="w-8 h-8 rounded flex items-center justify-center border transition-colors text-muted hover:text-pri hover:border-pri"
                   style="border-color:var(--c-border)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 6Z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</footer>
