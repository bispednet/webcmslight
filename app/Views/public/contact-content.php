<?php
/** @var array $settings */
/** @var string $csrfToken */
/** @var ?string $success */
/** @var ?string $error */

$contactEmail = $settings['contact_email'] ?? 'info@bisped.net';
?>

<div class="space-y-10" data-animate>
    <section class="max-w-3xl">
        <p class="text-sm font-black uppercase tracking-[0.35em] text-pri">Contatti</p>
        <h1 class="mt-4 text-4xl font-black text-acc md:text-6xl">Raccontaci cosa ti serve.</h1>
        <p class="mt-5 text-lg leading-8 text-muted">Assistenza tecnica, disponibilita prodotto, telefonia, connettivita, energia o supporto aziendale: scrivici cosa ti serve e ti richiamiamo con un riscontro concreto.</p>
    </section>

    <?php if ($success): ?>
        <div class="rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php elseif ($error): ?>
        <div class="rounded-2xl border border-pri/40 bg-pri/10 px-4 py-3 text-sm text-pri">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 gap-10 md:grid-cols-[1.15fr_.85fr] md:items-start">
    <div class="rounded-[2rem] border border-stroke bg-glass p-6 md:p-8" data-animate>
        <h2 class="mb-6 text-2xl font-black text-acc">Invia una richiesta</h2>
        <form method="post" action="/contatti" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-bold text-muted" for="name">Nome e cognome</label>
                    <input class="w-full rounded-2xl border border-stroke bg-bg2 p-3 outline-none transition focus:border-pri" type="text" name="name" id="name" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-muted" for="email">Email</label>
                    <input class="w-full rounded-2xl border border-stroke bg-bg2 p-3 outline-none transition focus:border-pri" type="email" name="email" id="email" required>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-bold text-muted" for="phone">Telefono</label>
                    <input class="w-full rounded-2xl border border-stroke bg-bg2 p-3 outline-none transition focus:border-pri" type="text" name="phone" id="phone">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-muted" for="topic">Tipo richiesta</label>
                    <select class="w-full rounded-2xl border border-stroke bg-bg2 p-3 outline-none transition focus:border-pri" name="topic" id="topic">
                        <option value="Assistenza tecnica">Assistenza tecnica</option>
                        <option value="Preventivo prodotto">Preventivo prodotto</option>
                        <option value="Telefonia">Telefonia</option>
                        <option value="Connettivita">Connettivita</option>
                        <option value="Energia">Energia</option>
                        <option value="Soluzioni aziendali">Soluzioni aziendali</option>
                        <option value="Altro">Altro</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-bold text-muted" for="message">Messaggio</label>
                <textarea class="w-full rounded-2xl border border-stroke bg-bg2 p-3 outline-none transition focus:border-pri" name="message" id="message" rows="6" required></textarea>
            </div>
            <button type="submit" class="w-full rounded-full bg-pri py-4 font-black text-white transition hover:bg-pri-700">
                Invia richiesta
            </button>
        </form>
    </div>

    <aside class="space-y-5" data-animate data-animate-delay="120">
        <div class="rounded-3xl border border-stroke bg-glass p-6">
            <h3 class="text-lg font-black text-acc">Email</h3>
            <a href="mailto:<?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 block text-muted transition hover:text-pri">
                <?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
        <div class="rounded-3xl border border-stroke bg-glass p-6">
            <h3 class="text-lg font-black text-acc">Cosa succede dopo</h3>
            <p class="mt-2 text-sm leading-6 text-muted">Riceviamo la richiesta, la leggiamo e ti ricontattiamo per capire priorita, tempi e soluzione piu adatta.</p>
        </div>
        <div class="rounded-3xl border border-pri/40 bg-pri/10 p-6">
            <h3 class="text-lg font-black text-acc">Meglio una domanda in piu che un acquisto sbagliato</h3>
            <p class="mt-2 text-sm leading-6 text-muted">Se hai dubbi su compatibilita, disponibilita o prezzo aggiornato, chiedi prima: ti aiutiamo a scegliere con calma.</p>
        </div>
    </aside>
</div>
