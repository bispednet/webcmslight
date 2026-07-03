<?php
/** @var array $orders */
/** @var string|null $error */
?>

<section class="space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-cy">Ordini prodotto</p>
            <h1 class="text-2xl font-semibold text-acc">Pagamenti</h1>
        </div>
        <p class="text-sm text-muted">Ultimi 200 ordini PayPal e richieste crypto.</p>
    </div>

    <?php if ($error): ?>
        <div class="card border-red-500/40 bg-red-500/10 text-red-100 text-sm">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (empty($orders) && !$error): ?>
        <div class="card text-sm text-muted">Nessun pagamento registrato al momento.</div>
    <?php elseif (!empty($orders)): ?>
        <div class="card overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs uppercase text-muted tracking-wide">
                    <tr>
                        <th class="text-left py-2">Data</th>
                        <th class="text-left py-2">Prodotto</th>
                        <th class="text-left py-2">Metodo</th>
                        <th class="text-left py-2">Consegna</th>
                        <th class="text-left py-2">Stato</th>
                        <th class="text-right py-2">Importo</th>
                        <th class="text-left py-2 hidden lg:table-cell">Cliente</th>
                        <th class="text-left py-2 hidden xl:table-cell">PayPal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/60">
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $status = (string)($order['status'] ?? '');
                        $statusClass = match ($status) {
                            'completed' => 'bg-emerald-500/10 text-emerald-200',
                            'failed', 'cancelled' => 'bg-red-500/10 text-red-200',
                            'pending_crypto', 'created', 'approved' => 'bg-cy/10 text-cy',
                            default => 'bg-white/5 text-muted',
                        };
                        ?>
                        <tr>
                            <td class="py-3 text-muted whitespace-nowrap">
                                <?= htmlspecialchars((string)($order['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-3">
                                <a class="font-medium text-acc hover:underline" href="/products/<?= urlencode((string)$order['product_slug']) ?>" target="_blank" rel="noopener">
                                    <?= htmlspecialchars((string)$order['product_name'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <div class="text-xs text-muted font-mono"><?= htmlspecialchars((string)$order['public_id'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if ($status === 'failed' && !empty($order['raw_response_json'])): ?>
                                    <?php
                                    $raw = json_decode((string)$order['raw_response_json'], true);
                                    $errorText = is_array($raw) ? (string)($raw['error'] ?? '') : '';
                                    ?>
                                    <?php if ($errorText !== ''): ?>
                                        <div class="mt-1 text-xs text-red-200">
                                            <?= htmlspecialchars($errorText, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 text-muted">
                                <?= htmlspecialchars((string)$order['payment_method'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-3 text-muted">
                                <?= (string)$order['delivery_method'] === 'shipping' ? 'Spedizione' : 'Ritiro' ?>
                            </td>
                            <td class="py-3">
                                <span class="inline-flex rounded px-2 py-1 text-xs font-semibold <?= $statusClass ?>">
                                    <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="py-3 text-right font-semibold text-acc whitespace-nowrap">
                                <?= htmlspecialchars((string)$order['currency'], ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars(number_format((float)$order['amount'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-3 text-muted hidden lg:table-cell">
                                <?php if (!empty($order['payer_email'])): ?>
                                    <a class="text-cy hover:underline" href="mailto:<?= htmlspecialchars((string)$order['payer_email'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars((string)$order['payer_email'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <?php if (!empty($order['payer_name'])): ?>
                                        <div class="text-xs"><?= htmlspecialchars((string)$order['payer_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-xs">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 text-muted hidden xl:table-cell font-mono text-xs">
                                <?= htmlspecialchars((string)($order['paypal_order_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($order['paypal_capture_id'])): ?>
                                    <div><?= htmlspecialchars((string)$order['paypal_capture_id'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
