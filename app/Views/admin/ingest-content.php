<?php
/** @var array $log */
/** @var array $sources */
use App\Services\Security\Csrf;
$csrf = Csrf::token();
?>
<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-black font-display" style="color:var(--c-acc)">Auto-Update Catalogo</h1>
        <p class="mt-1 text-sm" style="color:var(--c-muted)">Scarica notizie/offerte dai brand partner e aggiorna automaticamente prodotti e blog post.</p>
    </div>

    <?php foreach (['ingest_ok' => 'success', 'ingest_error' => 'danger'] as $key => $type): ?>
        <?php $msg = \App\Support\Flash::get($key); if ($msg): ?>
            <div class="info-card <?= $type === 'success' ? 'info-card--accent' : '' ?>" style="<?= $type === 'danger' ? 'border-color:var(--bisped-red)' : '' ?>">
                <p class="text-sm"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Trigger form -->
    <div class="info-card">
        <h2 class="font-bold mb-3" style="color:var(--c-acc)">Avvia ingestion</h2>
        <form method="post" action="/admin/ingest/run" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div>
                <label class="form-label text-xs">Categoria</label>
                <select name="category" class="form-select">
                    <option value="all">Tutte</option>
                    <option value="smartphone">Smartphone</option>
                    <option value="informatica">Informatica</option>
                    <option value="gaming">Gaming</option>
                    <option value="connettivita">Connettività</option>
                    <option value="energia">Energia</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Avvia ora</button>
        </form>
        <p class="mt-3 text-xs" style="color:var(--c-muted)">Il processo gira in background. Usa il log qui sotto per verificare i risultati.</p>
    </div>

    <!-- Sources table -->
    <div class="info-card">
        <h2 class="font-bold mb-3" style="color:var(--c-acc)">Sorgenti configurate</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-xs" style="border-collapse:collapse">
                <thead>
                    <tr style="border-bottom:1px solid var(--c-border)">
                        <th class="text-left py-1 pr-4" style="color:var(--c-muted)">Brand</th>
                        <th class="text-left py-1 pr-4" style="color:var(--c-muted)">Categoria</th>
                        <th class="text-left py-1 pr-4" style="color:var(--c-muted)">Tipo</th>
                        <th class="text-left py-1 pr-4" style="color:var(--c-muted)">URL</th>
                        <th class="text-left py-1" style="color:var(--c-muted)">Stato</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sources as $s): ?>
                    <tr style="border-bottom:1px solid rgba(255,255,255,.06)">
                        <td class="py-1 pr-4 font-bold" style="color:var(--c-txt)"><?= htmlspecialchars($s['brand'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="py-1 pr-4" style="color:var(--c-muted)"><?= htmlspecialchars($s['_category'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="py-1 pr-4" style="color:var(--c-muted)"><?= htmlspecialchars($s['type'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="py-1 pr-4 max-w-xs truncate" style="color:var(--bisped-red)">
                            <a href="<?= htmlspecialchars($s['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($s['url'], ENT_QUOTES, 'UTF-8') ?></a>
                        </td>
                        <td class="py-1">
                            <?php if ($s['enabled'] ?? false): ?>
                                <span class="text-green-400">● attivo</span>
                            <?php else: ?>
                                <span style="color:var(--c-muted)">○ disabilitato</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs" style="color:var(--c-muted)">
            Modifica <code>scripts/auto-update/sources.json</code> per aggiungere/disabilitare sorgenti.
        </p>
    </div>

    <!-- Log -->
    <div class="info-card">
        <h2 class="font-bold mb-3" style="color:var(--c-acc)">Log recente</h2>
        <?php if (empty($log)): ?>
            <p class="text-sm" style="color:var(--c-muted)">Nessun log disponibile. Avvia la prima ingestion.</p>
        <?php else: ?>
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="w-full text-xs" style="border-collapse:collapse">
                    <thead>
                        <tr style="border-bottom:1px solid var(--c-border)">
                            <th class="text-left py-1 pr-3" style="color:var(--c-muted)">Data</th>
                            <th class="text-left py-1 pr-3" style="color:var(--c-muted)">Azione</th>
                            <th class="text-left py-1 pr-3" style="color:var(--c-muted)">Slug</th>
                            <th class="text-left py-1" style="color:var(--c-muted)">Messaggio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($log as $entry): ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,.04)">
                            <td class="py-1 pr-3" style="color:var(--c-muted)"><?= substr($entry['created_at'], 0, 16) ?></td>
                            <td class="py-1 pr-3">
                                <span style="color:<?= $entry['action'] === 'error' ? 'var(--bisped-red)' : ($entry['action'] === 'blog_post_created' ? '#4ade80' : 'var(--c-acc)') ?>">
                                    <?= htmlspecialchars($entry['action'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="py-1 pr-3" style="color:var(--c-muted)"><?= htmlspecialchars((string)$entry['entity_slug'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-1 max-w-sm truncate" style="color:var(--c-txt)"><?= htmlspecialchars((string)$entry['message'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
