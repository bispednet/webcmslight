<?php
/** @var ?string $notice */
/** @var ?string $error */

$notice = $notice ?? null;
$error  = $error  ?? null;
?>

<div class="max-w-md mx-auto space-y-8">

    <div data-animate>
        <p class="section-label mb-5">Area riservata</p>
        <h1 class="font-display text-4xl font-black" style="color:var(--c-acc)">Accedi al tuo account</h1>
        <p class="mt-4 text-sm leading-6" style="color:var(--c-muted)">Clienti, commessi e amministratori: un unico accesso per seguire richieste, gestire catalogo e storico ordini.</p>
    </div>

    <?php if ($notice): ?>
        <div class="rounded border px-4 py-3 text-sm" style="background:rgba(34,197,94,.08);border-color:rgba(34,197,94,.30);color:#4ade80">
            <?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="rounded border px-4 py-3 text-sm" style="background:rgba(209,25,32,.08);border-color:rgba(209,25,32,.30);color:var(--bisped-red)">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="info-card" data-animate data-animate-delay="80">
        <form method="post" action="/login" class="space-y-5">
            <div>
                <label class="form-label" for="email">Email</label>
                <input class="form-input" type="email" name="email" id="email" required autocomplete="email"
                       style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
            </div>
            <div>
                <label class="form-label" for="password">Password</label>
                <input class="form-input" type="password" name="password" id="password" required autocomplete="current-password"
                       style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
            </div>
            <button type="submit" class="btn-primary w-full" style="justify-content:center">Accedi</button>
        </form>
        <div class="mt-5 pt-5 border-t text-center" style="border-color:var(--c-border)">
            <p class="text-xs" style="color:var(--c-muted)">Non hai un account? <a href="/contatti" class="font-bold" style="color:var(--bisped-red)">Contattaci</a> per la registrazione.</p>
        </div>
    </div>

</div>
