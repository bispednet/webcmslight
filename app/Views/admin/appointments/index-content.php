<?php
/** @var array $appointments */
/** @var bool $calendarReady */
/** @var string|null $notice */
/** @var string|null $error */
/** @var string $csrfToken */
?>

<section class="space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-cy">Google Calendar</p>
            <h1 class="text-2xl font-semibold text-acc">Appuntamenti</h1>
        </div>
        <span class="rounded-full border px-3 py-1 text-xs <?= $calendarReady ? 'border-emerald-500/30 text-emerald-200 bg-emerald-500/10' : 'border-yellow-500/30 text-yellow-200 bg-yellow-500/10' ?>">
            <?= $calendarReady ? 'Calendar sync attiva' : 'Manca refresh token Google' ?>
        </span>
    </div>

    <?php if ($notice): ?><div class="card border-emerald-500/40 bg-emerald-500/10 text-emerald-100 text-sm"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($error): ?><div class="card border-red-500/40 bg-red-500/10 text-red-100 text-sm"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <?php if (empty($appointments)): ?>
        <div class="card text-sm text-muted">Nessuna richiesta appuntamento ricevuta.</div>
    <?php else: ?>
        <div class="grid gap-4">
            <?php foreach ($appointments as $appointment): ?>
                <article class="card space-y-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="text-lg font-semibold text-acc"><?= htmlspecialchars($appointment['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <span class="rounded-full border border-white/10 px-3 py-1 text-xs text-muted"><?= htmlspecialchars($appointment['status'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="text-sm text-muted">
                                <?= htmlspecialchars($appointment['service_type'], ENT_QUOTES, 'UTF-8') ?> ·
                                <?= htmlspecialchars($appointment['meeting_type'], ENT_QUOTES, 'UTF-8') ?> ·
                                <?= htmlspecialchars($appointment['starts_at'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="text-sm text-cy"><?= htmlspecialchars($appointment['email'], ENT_QUOTES, 'UTF-8') ?> <?= $appointment['phone'] ? '· ' . htmlspecialchars($appointment['phone'], ENT_QUOTES, 'UTF-8') : '' ?></p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <?php if ($appointment['status'] !== 'confirmed'): ?>
                                <form method="post" action="/admin/appointments/accept/<?= urlencode((string)$appointment['id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="rounded-md border border-emerald-500/30 px-3 py-2 text-sm text-emerald-200 hover:bg-emerald-500/10" type="submit">Accetta</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($appointment['status'] !== 'cancelled'): ?>
                                <form method="post" action="/admin/appointments/reject/<?= urlencode((string)$appointment['id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="rounded-md border border-red-500/30 px-3 py-2 text-sm text-red-200 hover:bg-red-500/10" type="submit">Annulla</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($appointment['notes']): ?>
                        <p class="whitespace-pre-wrap text-sm leading-6 text-muted"><?= htmlspecialchars($appointment['notes'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php if ($appointment['meet_url']): ?>
                        <a class="text-sm text-cy hover:underline" href="<?= htmlspecialchars($appointment['meet_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Apri Meet</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
