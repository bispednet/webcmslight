<?php
/** @var array $settings */
use App\Services\Security\Csrf;
use App\Support\Flash;
use App\Support\Session;

Session::ensureStarted();
$csrfToken = Csrf::token();
$successMsg = Flash::pull('contact_success');
$errorMsg   = Flash::pull('contact_error');
?>

<div class="space-y-12">

    <!-- Hero -->
    <section class="py-6 tech-grid rounded-lg border px-8" style="border-color:var(--c-border);background:var(--c-surface)" data-animate>
        <div class="section-label mb-5">Supporto remoto</div>
        <h1 class="font-display text-4xl font-black md:text-5xl" style="color:var(--c-acc)">
            Teleassistenza<br>
            <span style="color:var(--bisped-red)">immediata.</span>
        </h1>
        <p class="mt-5 max-w-2xl text-lg" style="color:var(--c-muted)">
            Problemi con il PC, lo smartphone o la rete? I nostri tecnici si collegano da remoto e risolvono in tempo reale — senza che tu debba uscire di casa.
        </p>
        <div class="mt-6 flex flex-wrap gap-4">
            <a href="https://wa.me/393346582116?text=Ciao+bisp%26d%2C+ho+bisogno+di+teleassistenza" target="_blank" rel="noopener" class="btn-primary">
                WhatsApp immediato
            </a>
            <a href="tel:+390565311136" class="btn-outline">Chiama: 0565 31136</a>
        </div>
    </section>

    <!-- Come funziona -->
    <section data-animate>
        <p class="section-label mb-5">Come funziona</p>
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="service-card">
                <div class="text-3xl font-black mb-3" style="color:var(--bisped-red)">01</div>
                <h3 class="font-display font-black mb-2" style="color:var(--c-acc)">Contattaci</h3>
                <p class="text-sm" style="color:var(--c-muted)">Scrivi via WhatsApp, chiama o compila il form qui sotto. Descrivi il problema in 2 righe.</p>
            </div>
            <div class="service-card">
                <div class="text-3xl font-black mb-3" style="color:var(--bisped-red)">02</div>
                <h3 class="font-display font-black mb-2" style="color:var(--c-acc)">Ci colleghiamo</h3>
                <p class="text-sm" style="color:var(--c-muted)">Ti inviamo un link per avviare la sessione remota con AnyDesk o Quick Assist. Nessuna installazione permanente.</p>
            </div>
            <div class="service-card">
                <div class="text-3xl font-black mb-3" style="color:var(--bisped-red)">03</div>
                <h3 class="font-display font-black mb-2" style="color:var(--c-acc)">Problema risolto</h3>
                <p class="text-sm" style="color:var(--c-muted)">Il tecnico lavora sul tuo schermo in tempo reale. Vedi tutto quello che fa. Al termine ti spieghiamo cosa è successo.</p>
            </div>
        </div>
    </section>

    <!-- Servizi teleassistenza -->
    <section data-animate>
        <p class="section-label mb-5">Cosa risolviamo da remoto</p>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php
            $services = [
                ['Virus & Malware', 'Rimozione completa, scansione profonda, ripristino sistema.'],
                ['Windows lento', 'Ottimizzazione avvio, pulizia disco, aggiornamenti.'],
                ['Problemi stampante', 'Driver, condivisione rete, stampa da remoto.'],
                ['Configurazione email', 'Outlook, Thunderbird, Gmail su client — tutti i provider.'],
                ['Wi-Fi e rete', 'Problemi di connessione, router, VPN, accesso condiviso.'],
                ['Backup e recupero', 'Impostazione backup automatico, recupero file cancellati.'],
                ['Aggiornamenti software', 'Windows Update, Office, driver, antivirus.'],
                ['Smartphone Android/iOS', 'Configurazione app, trasferimento dati, reset.'],
                ['Office & PDF', 'Formule Excel, Word, conversioni, firme digitali.'],
            ];
            foreach ($services as [$title, $desc]):
            ?>
            <div class="service-card">
                <h3 class="font-display font-black text-sm mb-1" style="color:var(--c-acc)"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="text-sm" style="color:var(--c-muted)"><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Tariffe -->
    <section data-animate>
        <div class="info-card" style="border-color:rgba(209,25,32,.2)">
            <p class="section-label mb-4">Tariffe orientative</p>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="text-center py-4">
                    <div class="text-3xl font-black" style="color:var(--bisped-red)">Gratis</div>
                    <div class="font-bold mt-1" style="color:var(--c-acc)">Diagnosi</div>
                    <p class="text-xs mt-2" style="color:var(--c-muted)">Colloquio iniziale e analisi del problema. Nessun impegno.</p>
                </div>
                <div class="text-center py-4 border-x" style="border-color:var(--c-border)">
                    <div class="text-3xl font-black" style="color:var(--bisped-red)">€30</div>
                    <div class="font-bold mt-1" style="color:var(--c-acc)">Sessione 30 min</div>
                    <p class="text-xs mt-2" style="color:var(--c-muted)">Problemi semplici: virus, email, stampante, Wi-Fi.</p>
                </div>
                <div class="text-center py-4">
                    <div class="text-3xl font-black" style="color:var(--bisped-red)">€50</div>
                    <div class="font-bold mt-1" style="color:var(--c-acc)">Sessione 60 min</div>
                    <p class="text-xs mt-2" style="color:var(--c-muted)">Interventi complessi, configurazioni, ripristino sistema.</p>
                </div>
            </div>
            <p class="text-xs mt-4 text-center" style="color:var(--c-muted)">Pagamento al termine dell'intervento via Satispay, bonifico o contanti. Fattura disponibile.</p>
        </div>
    </section>

    <!-- Form ticket -->
    <section data-animate>
        <p class="section-label mb-5">Richiedi teleassistenza</p>

        <?php if ($successMsg): ?>
            <div class="info-card info-card--accent mb-6">
                <p class="text-sm font-bold"><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="info-card mb-6" style="border-color:var(--bisped-red)">
                <p class="text-sm font-bold" style="color:var(--bisped-red)"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endif; ?>

        <div class="grid gap-8 lg:grid-cols-[1fr_340px]">

            <form method="post" action="/contatti" class="info-card space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="topic" value="Teleassistenza">
                <!-- honeypot -->
                <div style="position:absolute;left:-9999px;opacity:0" aria-hidden="true">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label">Nome e cognome *</label>
                        <input type="text" name="name" required maxlength="120"
                               class="form-input w-full"
                               style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)"
                               placeholder="Mario Rossi">
                    </div>
                    <div>
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" required maxlength="150"
                               class="form-input w-full"
                               style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)"
                               placeholder="mario@email.it">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label">Telefono (per ricontattarti)</label>
                        <input type="tel" name="phone" maxlength="20"
                               class="form-input w-full"
                               style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)"
                               placeholder="+39 0565 31136">
                    </div>
                    <div>
                        <label class="form-label">Sistema operativo</label>
                        <select name="operating_system" class="form-select w-full"
                                style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
                            <option value="">— seleziona —</option>
                            <option>Windows 11</option>
                            <option>Windows 10</option>
                            <option>macOS</option>
                            <option>Android</option>
                            <option>iOS / iPhone</option>
                            <option>Linux</option>
                            <option>Altro</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="form-label">Descrivi il problema *</label>
                    <textarea name="message" required rows="5" maxlength="2000"
                              class="form-textarea w-full"
                              style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border);color-scheme:dark"
                              placeholder="Es: il PC si spegne da solo dopo 10 minuti, ho un virus che apre finestre pubblicitarie, non riesco a stampare dalla rete Wi-Fi…"></textarea>
                </div>

                <button type="submit" class="btn-primary w-full">Invia richiesta teleassistenza</button>
                <p class="text-xs text-center" style="color:var(--c-muted)">Ti ricontatteremo entro 2 ore negli orari di apertura (Lun-Sab 9-19).</p>
            </form>

            <!-- Side info -->
            <div class="space-y-4">
                <div class="service-card">
                    <h3 class="font-display font-black mb-2" style="color:var(--c-acc)">Orari disponibilità</h3>
                    <ul class="text-sm space-y-1" style="color:var(--c-muted)">
                        <li class="flex justify-between"><span>Lun – Ven</span><strong style="color:var(--c-txt)">9:00 – 19:00</strong></li>
                        <li class="flex justify-between"><span>Sabato</span><strong style="color:var(--c-txt)">9:00 – 13:00</strong></li>
                        <li class="flex justify-between"><span>Domenica</span><span>Chiuso</span></li>
                    </ul>
                </div>
                <div class="service-card">
                    <h3 class="font-display font-black mb-2" style="color:var(--c-acc)">Contatti diretti</h3>
                    <div class="space-y-2 text-sm">
                        <a href="https://wa.me/393346582116" target="_blank" rel="noopener"
                           class="flex items-center gap-2" style="color:var(--c-muted)">
                            <span style="color:#25d366">●</span> WhatsApp: 334 658 2116
                        </a>
                        <a href="tel:+390565311136" class="flex items-center gap-2" style="color:var(--c-muted)">
                            <span style="color:var(--bisped-red)">●</span> Tel: 0565 31136
                        </a>
                        <a href="mailto:negozio@bisped.net" class="flex items-center gap-2" style="color:var(--c-muted)">
                            <span style="color:var(--bisped-red)">●</span> negozio@bisped.net
                        </a>
                    </div>
                </div>
                <div class="service-card">
                    <h3 class="font-display font-black mb-2" style="color:var(--c-acc)">Cosa ci serve da te</h3>
                    <ul class="text-sm space-y-1" style="color:var(--c-muted)">
                        <li>✓ PC acceso e connesso a Internet</li>
                        <li>✓ AnyDesk o Quick Assist (ti guidiamo a scaricarlo)</li>
                        <li>✓ Codice a 9 cifre da comunicarci</li>
                        <li>✓ Nessun altro uso del PC durante la sessione</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

</div>
