<section class="space-y-6">
    <a class="text-sm text-cy hover:underline" href="/admin/ai-concierge">&larr; Conversazioni</a>

    <?php
    $data = json_decode((string)($conversation['structured_data'] ?? '{}'), true) ?: [];
    $analytics = $data['analytics'] ?? null;
    $commercialReport = $data['commercial_report'] ?? null;
    $agentLabel = match ($data['active_agent'] ?? '') { 'serenai' => 'SerenAI', 'andreai' => 'AndreAI', default => 'SarAI' };
    $tempLabel = $analytics['lead_temperature'] ?? '-';
    $tempClass = match ($tempLabel) { 'hot' => 'text-red-500', 'warm' => 'text-orange-400', default => 'text-muted' };
    ?>

    <div class="card">
        <h1 class="text-xl font-semibold">Conversazione <?= htmlspecialchars((string)$conversation['public_id']) ?></h1>
        <div class="mt-3 flex flex-wrap gap-4 text-sm">
            <span>Settore: <strong><?= htmlspecialchars((string)($conversation['main_sector'] ?: '-')) ?></strong></span>
            <span>Agente: <strong><?= htmlspecialchars($agentLabel) ?></strong></span>
            <span>Score: <strong><?= (int)$conversation['lead_score'] ?></strong></span>
            <span>Urgenza: <strong><?= htmlspecialchars((string)($conversation['urgency'] ?: '-')) ?></strong></span>
            <span class="<?= $tempClass ?>">Temperatura: <strong><?= htmlspecialchars($tempLabel) ?></strong></span>
            <?php if (!empty($conversation['customer_phone'])): ?>
                <span>Telefono: <strong><?= htmlspecialchars((string)$conversation['customer_phone']) ?></strong></span>
            <?php endif; ?>
            <?php if ($analytics): ?>
                <span>Intent: <strong><?= htmlspecialchars((string)($analytics['commercial_intent'] ?? '-')) ?></strong></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($analytics && !empty($analytics['recommended_next_action'])): ?>
        <div class="card bg-cy/5 border-l-4 border-cy">
            <p class="text-xs uppercase tracking-wide text-cy font-semibold mb-1">Prossima azione consigliata</p>
            <p class="text-sm"><?= htmlspecialchars((string)$analytics['recommended_next_action']) ?></p>
            <?php if (!empty($analytics['missing_for_human'])): ?>
                <p class="text-xs text-muted mt-2">Dati mancanti: <?= htmlspecialchars(implode(', ', $analytics['missing_for_human'])) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($commercialReport): ?>
        <div class="card">
            <h2 class="font-semibold mb-3">Report commerciale</h2>
            <pre class="text-sm whitespace-pre-wrap font-mono bg-surface rounded p-3"><?= htmlspecialchars($commercialReport) ?></pre>
        </div>
    <?php endif; ?>

    <?php if ($analytics): ?>
        <div class="card">
            <h2 class="font-semibold mb-3">Analytics</h2>
            <div class="grid gap-3 md:grid-cols-2 text-sm">
                <?php foreach ([
                    'Sales angle' => $analytics['sales_angle'] ?? null,
                    'Handoff reason' => $analytics['handoff_reason'] ?? null,
                    'Emozione cliente' => $analytics['customer_emotion'] ?? null,
                    'Gaming context' => !empty($analytics['gaming_context']) ? 'sì' : null,
                ] as $label => $value): ?>
                    <?php if ($value): ?>
                        <div><span class="text-muted"><?= htmlspecialchars($label) ?>:</span> <strong><?= htmlspecialchars((string)$value) ?></strong></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($analytics['cross_sell'])): ?>
                <p class="mt-3 text-xs text-muted">Cross-sell:
                    <?php foreach ($analytics['cross_sell'] as $cs): ?>
                        <span class="inline-block bg-surface rounded px-2 py-0.5 mr-1"><?= htmlspecialchars($cs) ?></span>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($conversation['summary'])): ?>
        <div class="card">
            <h2 class="font-semibold mb-2">Summary WhatsApp</h2>
            <pre class="text-sm whitespace-pre-wrap font-mono bg-surface rounded p-3"><?= htmlspecialchars((string)$conversation['summary']) ?></pre>
        </div>
    <?php endif; ?>

    <div class="card space-y-3">
        <h2 class="font-semibold">Transcript</h2>
        <?php foreach ($messages as $message): ?>
            <p class="text-sm">
                <strong class="<?= $message['role'] === 'user' ? 'text-cy' : '' ?>"><?= htmlspecialchars((string)$message['role']) ?>:</strong>
                <?= nl2br(htmlspecialchars((string)$message['content'])) ?>
            </p>
        <?php endforeach; ?>
    </div>
</section>
