<?php
/** @var ?string $notice */
/** @var ?string $error */
/** @var bool $googleConfigured */
/** @var string $csrfToken */

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

    <div class="info-card space-y-5" data-animate data-animate-delay="80">
        <?php if ($googleConfigured): ?>
            <a href="/auth/google" class="btn-outline w-full" style="justify-content:center">
                Accedi con Google
            </a>
        <?php else: ?>
            <div class="rounded border px-4 py-3 text-xs" style="background:rgba(234,179,8,.08);border-color:rgba(234,179,8,.30);color:#fde68a">
                Login Google configurato solo dopo credenziali OAuth valide.
            </div>
        <?php endif; ?>

        <div class="grid gap-3 sm:grid-cols-2">
            <button type="button" id="evm-wallet-login" class="btn-outline btn-sm" style="justify-content:center">Wallet EVM</button>
            <button type="button" id="solana-wallet-login" class="btn-outline btn-sm" style="justify-content:center">Wallet Solana</button>
        </div>
        <div id="wallet-error" class="hidden rounded border px-4 py-3 text-sm" style="background:rgba(209,25,32,.08);border-color:rgba(209,25,32,.30);color:var(--bisped-red)"></div>

        <div class="relative py-1 text-center">
            <span class="text-xs uppercase tracking-[0.2em]" style="color:var(--c-muted)">oppure</span>
        </div>

        <form method="post" action="/login" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
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
        <div class="pt-5 border-t" style="border-color:var(--c-border)">
            <h2 class="font-display text-xl font-black mb-3" style="color:var(--c-acc)">Registrati</h2>
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
        </div>
    </div>

</div>
<script type="module" src="/assets/js/login.js"></script>
