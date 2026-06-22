<?php
/** @var array $settings */
/** @var string $csrfToken */
/** @var ?string $success */
/** @var ?string $error */

$email = $settings['contact_email'] ?? 'negozio@bisped.net';
?>

<div class="space-y-10">
    <section class="max-w-3xl" data-animate>
        <p class="section-label mb-5">Diritto di recesso</p>
        <h1 class="font-display text-4xl font-black md:text-5xl" style="color:var(--c-acc)">Recedere dal contratto qui.</h1>
        <p class="mt-5 text-lg leading-8" style="color:var(--c-muted)">
            Usa questa funzione online per comunicare il recesso da un contratto concluso a distanza con bisp&amp;d.
            Riceverai una ricevuta via email con contenuto, data e ora della trasmissione.
        </p>
    </section>

    <?php if ($success): ?>
        <div class="rounded border px-5 py-4 text-sm" style="background:rgba(34,197,94,.08);border-color:rgba(34,197,94,.30);color:#4ade80">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php elseif ($error): ?>
        <div class="rounded border px-5 py-4 text-sm" style="background:rgba(209,25,32,.08);border-color:rgba(209,25,32,.30);color:var(--bisped-red)">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="grid gap-8 lg:grid-cols-[1.1fr_.9fr] lg:items-start">
        <section class="info-card" data-animate>
            <h2 class="font-display text-2xl font-black mb-6" style="color:var(--c-acc)">Dichiarazione di recesso online</h2>
            <form method="post" action="/recesso" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="form-label" for="withdrawal-name">Nome e cognome</label>
                        <input class="form-input" type="text" name="name" id="withdrawal-name" required autocomplete="name" style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
                    </div>
                    <div>
                        <label class="form-label" for="withdrawal-email">Email per la conferma</label>
                        <input class="form-input" type="email" name="email" id="withdrawal-email" required autocomplete="email" style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
                    </div>
                </div>

                <div>
                    <label class="form-label" for="withdrawal-contract">Ordine, contratto, preventivo o prodotto</label>
                    <input class="form-input" type="text" name="contract_ref" id="withdrawal-contract" required placeholder="Es. numero ordine, SKU, data acquisto o prodotto" style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
                </div>

                <div>
                    <label class="form-label" for="withdrawal-message">Note facoltative</label>
                    <textarea class="form-textarea" name="message" id="withdrawal-message" rows="5" placeholder="Puoi aggiungere dettagli utili per identificare il contratto o il prodotto." style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)"></textarea>
                </div>

                <button type="submit" class="btn-primary w-full" style="justify-content:center">
                    Conferma recesso
                </button>
            </form>
        </section>

        <aside class="space-y-4" data-animate data-animate-delay="120">
            <div class="info-card">
                <h2 class="font-display text-xl font-black mb-4" style="color:var(--c-acc)">Quando si applica</h2>
                <p class="text-sm leading-6" style="color:var(--c-muted)">
                    Il consumatore può esercitare il recesso nei termini previsti dal Codice del Consumo per i contratti a distanza, salvo esclusioni di legge o prodotti personalizzati/attivati.
                </p>
            </div>
            <div class="info-card">
                <h3 class="font-display text-base font-black mb-3" style="color:var(--c-acc)">Contatti alternativi</h3>
                <div class="space-y-2 text-sm" style="color:var(--c-muted)">
                    <div>Email: <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" style="color:var(--bisped-red)"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></a></div>
                    <div>PEC: <a href="mailto:bisped@pec.it" style="color:var(--bisped-red)">bisped@pec.it</a></div>
                    <div>Telefono: <a href="tel:+39056531136" style="color:var(--bisped-red)">0565 31136</a></div>
                </div>
            </div>
            <div class="info-card info-card--accent">
                <h3 class="font-display text-base font-black mb-3" style="color:var(--c-acc)">Ricevuta su supporto durevole</h3>
                <p class="text-sm leading-6" style="color:var(--c-muted)">
                    Dopo l'invio, il sistema trasmette una ricevuta all'indirizzo email indicato, includendo contenuto, data e ora della dichiarazione.
                </p>
            </div>
        </aside>
    </div>
</div>
