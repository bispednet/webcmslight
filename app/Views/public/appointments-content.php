<?php
/** @var string $csrfToken */
/** @var string|null $success */
/** @var string|null $error */

$tomorrow = (new DateTimeImmutable('tomorrow', new DateTimeZone('Europe/Rome')))->format('Y-m-d');
?>

<section class="grid gap-10 lg:grid-cols-[.9fr_1.1fr] lg:items-start">
    <div class="space-y-6" data-animate>
        <p class="section-label">Agenda bisp&amp;d</p>
        <h1 class="font-display text-4xl font-black md:text-5xl" style="color:var(--c-acc)">Prenota una visita o una call.</h1>
        <p class="text-lg leading-8" style="color:var(--c-muted)">Scegli una fascia indicativa: il team controlla l’agenda Google condivisa e conferma l’appuntamento in negozio, su Google Meet o via WhatsApp Business.</p>
        <div class="info-card info-card--accent">
            <h2 class="font-display text-xl font-black mb-3" style="color:var(--c-acc)">Come funziona</h2>
            <p class="text-sm leading-6" style="color:var(--c-muted)">L’agente del sito raccoglie la richiesta, la mette in coda per il negozio e, appena il calendario e configurato con accesso offline, puo creare direttamente l’evento nel Google Calendar usato dagli umani.</p>
        </div>
    </div>

    <div class="info-card" data-animate data-animate-delay="120">
        <h2 class="font-display text-2xl font-black mb-6" style="color:var(--c-acc)">Richiedi appuntamento</h2>

        <?php if ($success): ?>
            <div class="rounded border px-4 py-3 text-sm mb-5" style="background:rgba(34,197,94,.08);border-color:rgba(34,197,94,.30);color:#4ade80">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="rounded border px-4 py-3 text-sm mb-5" style="background:rgba(209,25,32,.08);border-color:rgba(209,25,32,.30);color:var(--bisped-red)">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/appuntamenti" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="form-label">Nome e cognome</span>
                    <input class="form-input" name="name" required autocomplete="name" style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
                </label>
                <label class="block">
                    <span class="form-label">Email</span>
                    <input class="form-input" type="email" name="email" required autocomplete="email" style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="form-label">Telefono / WhatsApp</span>
                    <input class="form-input" type="tel" name="phone" autocomplete="tel" style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
                </label>
                <label class="block">
                    <span class="form-label">Motivo</span>
                    <select class="form-select" name="service_type" style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
                        <option>Assistenza tecnica</option>
                        <option>Consulenza acquisto</option>
                        <option>Telefonia</option>
                        <option>Connettivita</option>
                        <option>Energia</option>
                        <option>Soluzioni business</option>
                    </select>
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <label class="block">
                    <span class="form-label">Modalita</span>
                    <select class="form-select" name="meeting_type" style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
                        <option value="negozio">In negozio</option>
                        <option value="meet">Google Meet</option>
                        <option value="whatsapp">WhatsApp</option>
                    </select>
                </label>
                <label class="block">
                    <span class="form-label">Data</span>
                    <input class="form-input" type="date" name="date" min="<?= htmlspecialchars($tomorrow, ENT_QUOTES, 'UTF-8') ?>" required style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
                </label>
                <label class="block">
                    <span class="form-label">Ora</span>
                    <input class="form-input" type="time" name="time" min="09:00" max="19:00" step="900" required style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
                </label>
            </div>

            <label class="block">
                <span class="form-label">Note utili</span>
                <textarea class="form-textarea" name="notes" rows="5" placeholder="Raccontaci modello dispositivo, urgenza, operatore o obiettivo della call." style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)"></textarea>
            </label>

            <button type="submit" class="btn-primary w-full" style="justify-content:center">Invia richiesta appuntamento</button>
        </form>
    </div>
</section>
