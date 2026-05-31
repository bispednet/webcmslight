<section class="space-y-6">
    <div>
        <p class="text-sm uppercase tracking-[.2em] text-cy">Agent Swarm Concierge</p>
        <h1 class="text-2xl font-semibold">Conversazioni AI</h1>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <?php foreach (['total' => 'Conversazioni', 'qualified' => 'Qualificate', 'handed_off' => 'WhatsApp', 'average_score' => 'Score medio'] as $key => $label): ?>
            <div class="card">
                <p class="text-sm text-muted"><?= htmlspecialchars($label) ?></p>
                <p class="mt-2 text-3xl font-semibold"><?= (int)($stats[$key] ?? 0) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-muted">
                <tr>
                    <th class="py-2">Data</th>
                    <th>Cliente</th>
                    <th>Settore</th>
                    <th>Agente</th>
                    <th>Temp.</th>
                    <th>Urgenza</th>
                    <th>Stato</th>
                    <th>Score</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border/60">
            <?php foreach ($conversations as $item):
                $itemData = json_decode((string)($item['structured_data'] ?? '{}'), true) ?: [];
                $analytics = $itemData['analytics'] ?? null;
                $temp = $analytics['lead_temperature'] ?? null;
                $agentKey = $itemData['active_agent'] ?? ($item['assigned_to'] ?? 'router');
                $agentName = match ($agentKey) { 'serenai' => 'SerenAI', 'andreai' => 'AndreAI', 'sarai' => 'SarAI', default => $agentKey };
                $tempClass = match ($temp) { 'hot' => 'text-red-500 font-bold', 'warm' => 'text-orange-400', default => 'text-muted' };
            ?>
                <tr>
                    <td class="py-3 text-muted"><?= htmlspecialchars((string)$item['created_at']) ?></td>
                    <td>
                        <?= htmlspecialchars((string)($item['customer_name'] ?: 'Anonimo')) ?>
                        <?php if (!empty($item['customer_phone'])): ?>
                            <span class="ml-1 text-xs text-muted"><?= htmlspecialchars((string)$item['customer_phone']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string)($item['main_sector'] ?: '-')) ?></td>
                    <td><?= htmlspecialchars($agentName) ?></td>
                    <td class="<?= $tempClass ?>"><?= htmlspecialchars((string)($temp ?: '-')) ?></td>
                    <td><?= htmlspecialchars((string)($item['urgency'] ?: '-')) ?></td>
                    <td><?= htmlspecialchars((string)$item['status']) ?></td>
                    <td><?= (int)$item['lead_score'] ?></td>
                    <td><a class="text-cy hover:underline" href="/admin/ai-concierge/conversations/<?= (int)$item['id'] ?>">Apri</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
