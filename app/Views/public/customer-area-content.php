<?php
/** @var string $name */
/** @var string $email */
/** @var string $role */
use App\Services\Security\Csrf;

$name  = htmlspecialchars($name  ?? 'Cliente', ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($email ?? '',         ENT_QUOTES, 'UTF-8');
$role  = $role ?? 'customer';
?>

<div class="space-y-10">

    <div data-animate>
        <p class="section-label mb-5">Area personale</p>
        <h1 class="font-display text-4xl font-black" style="color:var(--c-acc)">Benvenuto, <?= $name ?>.</h1>
        <p class="mt-3 text-sm" style="color:var(--c-muted)"><?= $email ?></p>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-animate>
        <a href="/contatti" class="service-card group">
            <h2 class="font-display text-lg font-black mb-2 group-hover:text-red-400 transition-colors" style="color:var(--c-acc)">Nuova richiesta</h2>
            <p class="text-sm" style="color:var(--c-muted)">Invia una nuova richiesta di assistenza o preventivo.</p>
        </a>
        <a href="/products" class="service-card group">
            <h2 class="font-display text-lg font-black mb-2 group-hover:text-red-400 transition-colors" style="color:var(--c-acc)">Catalogo prodotti</h2>
            <p class="text-sm" style="color:var(--c-muted)">Sfoglia i prodotti disponibili e richiedi disponibilità.</p>
        </a>
        <a href="/dove" class="service-card group">
            <h2 class="font-display text-lg font-black mb-2 group-hover:text-red-400 transition-colors" style="color:var(--c-acc)">Dove siamo</h2>
            <p class="text-sm" style="color:var(--c-muted)">Orari di apertura e come raggiungerci in negozio.</p>
        </a>
    </div>

    <div class="info-card info-card--accent" data-animate>
        <p class="text-sm font-bold mb-1" style="color:var(--c-acc)">Area in costruzione</p>
        <p class="text-sm" style="color:var(--c-muted)">Lo storico richieste, i documenti e la gestione ordini sono in arrivo. Per assistenza immediata scrivi a <a href="mailto:negozio@bisped.net" style="color:var(--bisped-red)">negozio@bisped.net</a> o chiama il <a href="tel:+39056531136" style="color:var(--bisped-red)">0565 31136</a>.</p>
    </div>

    <div data-animate>
        <form method="post" action="/auth/logout">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn-outline btn-sm">Esci dall'account</button>
        </form>
    </div>

</div>
