<section class="space-y-6">
    <div><p class="text-sm uppercase tracking-[.2em] text-cy">Team AI Bisped</p><h1 class="text-2xl font-semibold">Conversazioni concierge</h1></div>
    <div class="grid gap-4 md:grid-cols-4">
        <?php foreach (['total'=>'Conversazioni','qualified'=>'Qualificate','handed_off'=>'WhatsApp','average_score'=>'Score medio'] as $key=>$label): ?>
            <div class="card"><p class="text-sm text-muted"><?= htmlspecialchars($label) ?></p><p class="mt-2 text-3xl font-semibold"><?= (int)($stats[$key] ?? 0) ?></p></div>
        <?php endforeach; ?>
    </div>
    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-muted"><tr><th class="py-2">Data</th><th>Cliente</th><th>Settore</th><th>Agente</th><th>Stato</th><th>Score</th><th></th></tr></thead>
            <tbody class="divide-y divide-border/60">
            <?php foreach ($conversations as $item): ?>
                <tr><td class="py-3 text-muted"><?= htmlspecialchars((string)$item['created_at']) ?></td><td><?= htmlspecialchars((string)($item['customer_name'] ?: 'Anonimo')) ?></td><td><?= htmlspecialchars((string)($item['main_sector'] ?: '-')) ?></td><td><?= htmlspecialchars((string)($item['assigned_to'] ?: 'router')) ?></td><td><?= htmlspecialchars((string)$item['status']) ?></td><td><?= (int)$item['lead_score'] ?></td><td><a class="text-cy hover:underline" href="/admin/ai-concierge/conversations/<?= (int)$item['id'] ?>">Apri</a></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
