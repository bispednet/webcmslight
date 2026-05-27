<?php
/** @var array $messages */
/** @var string|null $notice */
/** @var string|null $error */
/** @var string $csrfToken */
?>

<section class="space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-cy">Lead dal sito</p>
            <h1 class="text-2xl font-semibold text-acc">Messaggi contatto</h1>
        </div>
        <p class="text-sm text-muted">Ultimi 200 messaggi ricevuti dai form pubblici.</p>
    </div>

    <?php if ($notice): ?>
        <div class="card border-emerald-500/40 bg-emerald-500/10 text-emerald-100 text-sm">
            <?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="card border-red-500/40 bg-red-500/10 text-red-100 text-sm">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (empty($messages)): ?>
        <div class="card text-sm text-muted">
            Nessun messaggio ricevuto al momento.
        </div>
    <?php else: ?>
        <div class="grid gap-4">
            <?php foreach ($messages as $message): ?>
                <?php
                $status = (string)($message['status'] ?? 'new');
                $badgeClass = match ($status) {
                    'new' => 'bg-cy/15 text-cy border-cy/30',
                    'read' => 'bg-emerald-500/15 text-emerald-200 border-emerald-500/30',
                    default => 'bg-white/5 text-muted border-white/10',
                };
                ?>
                <article class="card space-y-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="text-lg font-semibold text-acc">
                                    <?= htmlspecialchars((string)$message['name'], ENT_QUOTES, 'UTF-8') ?>
                                </h2>
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold <?= $badgeClass ?>">
                                    <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <a class="text-sm text-cy hover:underline" href="mailto:<?= htmlspecialchars((string)$message['email'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string)$message['email'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <p class="mt-1 text-xs text-muted">
                                <?= htmlspecialchars((string)$message['created_at'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <?php if ($status !== 'read'): ?>
                                <form method="post" action="/admin/messages/read/<?= urlencode((string)$message['id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="rounded-md border border-cy/30 px-3 py-2 text-sm text-cy hover:bg-cy/10" type="submit">Letto</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($status !== 'archived'): ?>
                                <form method="post" action="/admin/messages/archive/<?= urlencode((string)$message['id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="rounded-md border border-white/10 px-3 py-2 text-sm text-muted hover:bg-white/5" type="submit">Archivia</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="whitespace-pre-wrap text-sm leading-6 text-muted"><?= htmlspecialchars((string)$message['message'], ENT_QUOTES, 'UTF-8') ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
