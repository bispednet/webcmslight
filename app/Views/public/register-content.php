<?php
use App\Core\View;

/** @var ?string $notice */
/** @var ?string $error */
/** @var bool $googleConfigured */
/** @var string $googleRedirectUri */
/** @var string $csrfToken */
?>

<div class="max-w-md mx-auto space-y-8">

    <div data-animate>
        <p class="section-label mb-5">Nuovo account</p>
        <h1 class="font-display text-4xl font-black" style="color:var(--c-acc)">Crea il tuo account.</h1>
        <p class="mt-4 text-sm leading-6" style="color:var(--c-muted)">Registrati per seguire richieste, appuntamenti e comunicazioni con bisp&amp;d.</p>
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

    <div class="info-card space-y-5" data-animate data-animate-delay="80">
        <?php if ($googleConfigured): ?>
            <?php View::renderPartial('public/partials/google-button', ['label' => 'Registrati con Google']); ?>
        <?php else: ?>
            <div class="rounded border px-4 py-3 text-xs" style="background:rgba(234,179,8,.08);border-color:rgba(234,179,8,.30);color:#fde68a">
                Registrazione Google non configurata.
            </div>
        <?php endif; ?>

        <div class="relative py-1 text-center">
            <span class="text-xs uppercase tracking-[0.2em]" style="color:var(--c-muted)">oppure</span>
        </div>

        <form method="post" action="/register" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div>
                <label class="form-label" for="register-name">Nome</label>
                <input class="form-input" type="text" name="name" id="register-name" required autocomplete="name"
                       style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
            </div>
            <div>
                <label class="form-label" for="register-email">Email</label>
                <input class="form-input" type="email" name="email" id="register-email" required autocomplete="email"
                       style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
            </div>
            <div>
                <label class="form-label" for="register-password">Password</label>
                <input class="form-input" type="password" name="password" id="register-password" required minlength="8" autocomplete="new-password"
                       style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
            </div>
            <button type="submit" class="btn-primary w-full" style="justify-content:center">Crea account</button>
        </form>

        <div class="pt-5 border-t text-center" style="border-color:var(--c-border)">
            <p class="text-xs" style="color:var(--c-muted)">Hai gia un account? <a href="/login" class="font-bold" style="color:var(--bisped-red)">Accedi</a></p>
        </div>
    </div>

</div>
