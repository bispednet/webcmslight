<?php
/** @var array $settings */
/** @var string $csrfToken */
/** @var ?string $success */
/** @var ?string $error */

$contactEmail = $settings['contact_email'] ?? 'negozio@bisped.net';

$prefill = [];
if (!empty($_GET['prodotto'])) $prefill['prodotto'] = htmlspecialchars((string)$_GET['prodotto'], ENT_QUOTES, 'UTF-8');
if (!empty($_GET['sku']))      $prefill['sku']      = htmlspecialchars((string)$_GET['sku'], ENT_QUOTES, 'UTF-8');
$defaultMessage = '';
if (!empty($prefill['prodotto'])) {
    $defaultMessage = 'Vorrei informazioni sul prodotto: ' . $prefill['prodotto'];
    if (!empty($prefill['sku'])) $defaultMessage .= ' (SKU: ' . $prefill['sku'] . ')';
}
?>

<div class="space-y-10">

    <section class="max-w-3xl" data-animate>
        <p class="section-label mb-5">Contatti</p>
        <h1 class="font-display text-4xl font-black md:text-5xl" style="color:var(--c-acc)">Raccontaci cosa ti serve.</h1>
        <p class="mt-5 text-lg leading-8" style="color:var(--c-muted)">Assistenza tecnica, disponibilità prodotto, telefonia, connettività, energia o supporto aziendale: scrivici cosa ti serve e ti richiamiamo con un riscontro concreto.</p>
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

</div>

<div class="grid grid-cols-1 gap-8 md:grid-cols-[1.15fr_.85fr] md:items-start mt-10">

    <!-- Form -->
    <div class="info-card" data-animate>
        <h2 class="font-display text-2xl font-black mb-6" style="color:var(--c-acc)">Invia una richiesta</h2>
        <form method="post" action="/contatti" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="form-label" for="name">Nome e cognome</label>
                    <input class="form-input" type="text" name="name" id="name" required autocomplete="name" style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
                </div>
                <div>
                    <label class="form-label" for="email">Email</label>
                    <input class="form-input" type="email" name="email" id="email" required autocomplete="email" style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="form-label" for="phone">Telefono</label>
                    <input class="form-input" type="tel" name="phone" id="phone" autocomplete="tel" style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
                </div>
                <div>
                    <label class="form-label" for="topic">Tipo richiesta</label>
                    <?php
                        $topicOptions = [
                            'Preventivo prodotto'  => 'Preventivo prodotto',
                            'Assistenza tecnica'   => 'Assistenza tecnica',
                            'Telefonia'            => 'Telefonia',
                            'Connettività'         => 'Connettività',
                            'Energia'              => 'Energia',
                            'Soluzioni aziendali'  => 'Soluzioni aziendali',
                            'AI Agent'             => 'AI Agent, chatbot e applicativi su misura',
                            'Altro'                => 'Altro',
                        ];
                        $selectedTopic = !empty($prefill['prodotto'])
                            ? 'Preventivo prodotto'
                            : (isset($_GET['topic']) ? trim((string)$_GET['topic']) : '');
                    ?>
                    <select class="form-select" name="topic" id="topic" style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)">
                        <?php foreach ($topicOptions as $optValue => $optLabel): ?>
                            <option value="<?= htmlspecialchars($optValue, ENT_QUOTES, 'UTF-8') ?>"<?= $selectedTopic === $optValue ? ' selected' : '' ?>><?= htmlspecialchars($optLabel, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="form-label" for="message">Messaggio</label>
                <textarea class="form-textarea" name="message" id="message" rows="6" required
                          style="background:var(--c-bg);color:var(--c-txt);border-color:var(--c-border)"><?= htmlspecialchars($defaultMessage, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <button type="submit" class="btn-primary w-full" style="justify-content:center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                </svg>
                Invia richiesta
            </button>
        </form>
    </div>

    <!-- Sidebar -->
    <aside class="space-y-4" data-animate data-animate-delay="120">
        <div class="info-card">
            <h3 class="font-display text-base font-black mb-3" style="color:var(--c-acc)">Email</h3>
            <a href="mailto:<?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8'); ?>"
               class="text-sm transition-colors hover:text-red-400" style="color:var(--c-muted)">
                <?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
        <div class="info-card">
            <h3 class="font-display text-base font-black mb-3" style="color:var(--c-acc)">Telefono</h3>
            <a href="tel:+39056531136" class="text-sm transition-colors hover:text-red-400" style="color:var(--c-muted)">+39 0565 31136</a>
        </div>
        <div class="info-card">
            <h3 class="font-display text-base font-black mb-3" style="color:var(--c-acc)">Cosa succede dopo</h3>
            <p class="text-sm leading-6" style="color:var(--c-muted)">Riceviamo la richiesta, la leggiamo e ti ricontattiamo per capire priorità, tempi e soluzione più adatta.</p>
        </div>
        <div class="info-card info-card--accent">
            <h3 class="font-display text-base font-black mb-3" style="color:var(--c-acc)">Meglio una domanda in più che un acquisto sbagliato</h3>
            <p class="text-sm leading-6" style="color:var(--c-muted)">Se hai dubbi su compatibilità, disponibilità o prezzo aggiornato, chiedi prima: ti aiutiamo a scegliere con calma.</p>
        </div>
    </aside>

</div>
