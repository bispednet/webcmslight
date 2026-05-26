<?php
/** @var ?string $notice */
/** @var ?string $error */

$notice = $notice ?? null;
$error = $error ?? null;
$projectId = $projectId ?? '';
$rpcUrl = $rpcUrl ?? '';
?>

<section class="mx-auto max-w-5xl space-y-8" data-animate>
    <div class="rounded-[2rem] border border-stroke bg-glass p-8 shadow-deep backdrop-blur-lg md:p-10">
        <p class="text-sm font-black uppercase tracking-[0.35em] text-pri">Area riservata</p>
        <h1 class="mt-4 text-4xl font-black tracking-tight text-acc md:text-6xl">Accesso per clienti, negozio e amministrazione.</h1>
        <p class="mt-5 max-w-3xl text-lg leading-8 text-muted">
            Un unico ingresso per seguire richieste, gestire catalogo, lavorare al banco e preparare il collegamento con anagrafiche,
            ordini e storico commerciale.
        </p>
    </div>

    <?php if ($notice): ?>
        <div class="rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
            <?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="rounded-2xl border border-pri/40 bg-pri/10 px-4 py-3 text-sm text-pri">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
        <form method="post" action="/login" class="rounded-[2rem] border border-stroke bg-bg2 p-6 shadow-deep md:p-8">
            <h2 class="text-2xl font-black text-acc">Entra nella tua area</h2>
            <p class="mt-2 text-sm leading-6 text-muted">Accesso operativo per amministratori, commessi di negozio e clienti.</p>

            <div class="mt-6 grid gap-3">
                <a href="/auth/google" class="inline-flex w-full items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-black text-black transition hover:bg-white/90">
                    Continua con Google
                </a>
                <button type="button" id="evm-wallet-login" class="inline-flex w-full items-center justify-center rounded-full border border-stroke px-5 py-3 text-sm font-black text-acc transition hover:border-pri hover:text-pri">
                    Connetti wallet EVM
                </button>
                <button type="button" id="solana-wallet-login" class="inline-flex w-full items-center justify-center rounded-full border border-stroke px-5 py-3 text-sm font-black text-acc transition hover:border-pri hover:text-pri">
                    Connetti wallet Solana
                </button>
            </div>

            <div class="mt-8 border-t border-stroke pt-6 space-y-4">
                <p class="text-xs font-black uppercase tracking-[0.28em] text-muted">Accesso locale di emergenza</p>
                <label class="block text-sm font-bold text-muted" for="email">Email</label>
                <input class="w-full rounded-2xl border border-stroke bg-bg p-3 text-txt outline-none transition focus:border-pri" type="email" name="email" id="email" autocomplete="email" required>

                <label class="block text-sm font-bold text-muted" for="password">Password</label>
                <input class="w-full rounded-2xl border border-stroke bg-bg p-3 text-txt outline-none transition focus:border-pri" type="password" name="password" id="password" autocomplete="current-password" required>
            </div>

            <button type="submit" class="mt-6 w-full rounded-full bg-pri py-4 font-black text-white transition hover:bg-pri-700">
                Accedi
            </button>
        </form>

        <aside class="space-y-5">
            <article class="rounded-3xl border border-stroke bg-glass p-6">
                <h3 class="text-lg font-black text-acc">Ruoli previsti</h3>
                <p class="mt-3 text-sm leading-6 text-muted">
                    Admin per configurazione e contenuti, commesso per catalogo e richieste al banco, cliente per preventivi,
                    assistenze e documenti personali.
                </p>
            </article>
            <article class="rounded-3xl border border-stroke bg-glass p-6">
                <h3 class="text-lg font-black text-acc">Policy admin</h3>
                <p class="mt-3 text-sm leading-6 text-muted">Google assegna il ruolo admin solo agli indirizzi allowlistati lato server. Wallet EVM e Solana entrano in admin solo se l'indirizzo e autorizzato in configurazione.</p>
                <button
                    type="button"
                    id="wallet-connect-button"
                    data-project-id="<?= htmlspecialchars($projectId, ENT_QUOTES, 'UTF-8'); ?>"
                    data-rpc-url="<?= htmlspecialchars($rpcUrl, ENT_QUOTES, 'UTF-8'); ?>"
                    class="mt-5 w-full rounded-full border border-stroke px-5 py-3 text-sm font-black text-acc transition hover:border-pri hover:text-pri"
                >
                    Collega wallet admin
                </button>
                <div id="wallet-error" class="mt-4 hidden rounded-md border border-pri/40 bg-pri/10 px-4 py-3 text-sm text-pri"></div>
            </article>
        </aside>
    </div>
</section>
<script type="module" src="/assets/js/login.js"></script>
