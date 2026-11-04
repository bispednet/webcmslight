<?php
/** @var array $stats */
/** @var array $recentSessions */

$statConfig = [
    'products' => 'Products',
    'agents' => 'Agents',
    'partners' => 'Partners',
    'teamMembers' => 'Team Members',
    'blogPosts' => 'Published Posts',
    'contactMessages' => 'Contact Messages',
];
?>
<?php if (isset($catalogHealth)): ?>
<section class="card mb-6" style="border:1px solid <?= !empty($catalogHealth['warning']) ? '#d88a25' : '#368b65' ?>">
    <h2 class="text-lg font-semibold">Sincronizzazione catalogo</h2>
    <p class="mt-2"><?= !empty($catalogHealth['warning']) ? 'Attenzione: il catalogo richiede una verifica. Gli articoli senza prezzo e stock verificati non sono acquistabili.' : 'Prezzi e disponibilità verificati dal fornitore.' ?></p>
    <p class="text-sm mt-2">Ultimo aggiornamento riuscito (UTC): <?= htmlspecialchars((string)($catalogHealth['last_success_utc'] ?? 'nessuno'), ENT_QUOTES, 'UTF-8') ?> · Dati scaduti: <?= (int)$catalogHealth['expired'] ?> · Ritirati: <?= (int)$catalogHealth['retired'] ?></p>
    <?php if (!empty($catalogHealth['last']['error_message'])): ?><p class="text-sm mt-2"><?= htmlspecialchars((string)$catalogHealth['last']['error_message'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
</section>
<?php endif; ?>
<section class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <?php foreach ($statConfig as $key => $label): ?>
        <div class="card">
            <p class="text-sm text-muted mb-2"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-3xl font-semibold text-acc">
                <?= isset($stats[$key]) ? (int)$stats[$key] : 0 ?>
            </p>
        </div>
    <?php endforeach; ?>
</section>

<section class="mt-8 card">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-acc">Recent Admin Sessions</h2>
    </div>
    <?php if (empty($recentSessions)): ?>
        <p class="text-sm text-muted">No recent admin sessions recorded.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-muted text-xs uppercase tracking-wide">
                    <tr>
                        <th class="text-left py-2">Admin</th>
                        <th class="text-left py-2">Session</th>
                        <th class="text-left py-2">IP</th>
                        <th class="text-left py-2">Created</th>
                        <th class="text-left py-2">Expires</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/60">
                    <?php foreach ($recentSessions as $session): ?>
                        <tr>
                            <td class="py-3">
                                <?= htmlspecialchars($session['display_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-3 text-muted">
                                <?= htmlspecialchars($session['session_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-3 text-muted">
                                <?= htmlspecialchars($session['ip_address'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-3 text-muted">
                                <?= htmlspecialchars($session['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-3 text-muted">
                                <?= htmlspecialchars($session['expires_at'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
