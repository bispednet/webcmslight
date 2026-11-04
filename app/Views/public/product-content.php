<?php
/** @var array $product */
/** @var array|null $pcConfigurator */
/** @var string $paymentCsrf */
/** @var array $paypalPublicConfig */
use App\Support\AdminMode;

if (!function_exists('bisped_product_category_label')) {
    function bisped_product_category_label(string $category): string
    {
        return match ($category) {
            'pc-assemblati', 'pc-assmblati' => 'PC',
            'pc-custom' => 'PC-Custom',
            default => $category,
        };
    }
}

$slug       = (string)($product['slug'] ?? '');
$name       = (string)($product['name'] ?? '');
$category   = trim((string)($product['category'] ?? '')) ?: 'Catalogo';
$categoryLabel = bisped_product_category_label($category);
$imgUrl     = trim((string)($product['image_url'] ?? ''));
$sku        = trim((string)($product['sku'] ?? ''));
$contentHtml = (string)($product['content_html'] ?? '');
$features   = $product['features'] ?? [];
$tags       = array_filter(array_map('trim', explode(',', (string)($product['tags'] ?? ''))));

// Clean imported WC content
$contentHtml = str_replace(
    ['Prodotto ', ' importato dal WooCommerce Bisped', 'Scheda importata dal catalogo WooCommerce Bisped.',
     'Prezzo WooCommerce:', 'Stato importato:', 'SKU:', 'Categoria:'],
    ['', '', '', 'Prezzo indicativo:', 'Disponibilità:', 'Codice:', 'Reparto:'],
    $contentHtml
);

// Price
$regularPrice = $product['price']      ?? null;
$salePrice    = $product['sale_price'] ?? null;
$isConfigurablePc = !empty($pcConfigurator);
$displayPrice = $isConfigurablePc ? (($pcConfigurator['valid'] ?? false) ? ($pcConfigurator['total'] ?? null) : null) : ($salePrice ?: $regularPrice);
$isSale = !$isConfigurablePc && $salePrice && $regularPrice && (float)$salePrice < (float)$regularPrice;

$priceFormatted = $displayPrice ? number_format((float)$displayPrice, 2, ',', '.') : null;
$oldFormatted   = $isSale ? number_format((float)$regularPrice, 2, ',', '.') : null;
$paymentMethod = (string)($product['payment_method'] ?? 'none');
$isPaymentProduct = (int)($product['is_payment_product'] ?? 0) === 1;
$isCheckoutEnabled = $isPaymentProduct && in_array($paymentMethod, ['paypal', 'crypto'], true)
    && \App\Services\Catalog\CatalogSaleGuard::reason($product, (array)config('catalog', [])) === null;
$paymentAmount = $displayPrice ? number_format((float)$displayPrice, 2, '.', '') : null;
$deliveryMethod = (string)($product['delivery_method'] ?? 'pickup');
if (!in_array($deliveryMethod, ['shipping', 'pickup', 'both'], true)) {
    $deliveryMethod = 'pickup';
}
$paypalConfig = $paypalPublicConfig ?? ['configured' => false, 'client_id' => '', 'currency' => 'EUR'];
$paypalConfigured = !empty($paypalConfig['configured']) && (string)($paypalConfig['client_id'] ?? '') !== '';
$paypalCurrency = strtoupper((string)($paypalConfig['currency'] ?? 'EUR'));
$paypalSdkUrl = '';
if ($paymentMethod === 'paypal' && $paypalConfigured) {
    $paypalSdkUrl = 'https://www.paypal.com/sdk/js?' . http_build_query([
        'client-id' => (string)$paypalConfig['client_id'],
        'currency' => $paypalCurrency,
        'intent' => 'capture',
        'components' => 'buttons,messages',
        'enable-funding' => 'paylater',
        'locale' => 'it_IT',
    ], '', '&', PHP_QUERY_RFC3986);
}
$checkoutDomId = 'checkout-' . (int)($product['id'] ?? 0);

// Stock
$rawStock = strtolower(trim((string)($product['stock_status'] ?? '')));
$stockQty = (int)($product['stock_qty'] ?? 0);
if (in_array($rawStock, ['instock', 'in-stock', 'disponibile', '1'], true)) {
    $stockClass = 'badge-stock--in';
    $stockLabel = $stockQty > 0
        ? 'Disponibile · ' . $stockQty . ' pz online'
        : 'Disponibile';
} elseif ($rawStock === '') {
    $stockClass = 'badge-stock--ask'; $stockLabel = 'Su richiesta';
} else {
    $stockClass = 'badge-stock--out'; $stockLabel = 'Non disponibile';
}
if (!empty($pcConfigurator['stock']) && is_array($pcConfigurator['stock'])) {
    $configStock = $pcConfigurator['stock'];
    if (!empty($configStock['available']) && (int)($configStock['qty'] ?? 0) > 0) {
        $stockClass = 'badge-stock--in';
        $stockLabel = 'Disponibile · ' . (int)$configStock['qty'] . ' pz';
    } else {
        $stockClass = 'badge-stock--ask';
        $stockLabel = 'Su richiesta';
    }
}

// Share
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'bisped.net';
$shareUrl = $scheme . '://' . $host . '/products/' . $slug;
?>

<script>
window.BISPED_TRACKING_CONTEXT = window.BISPED_TRACKING_CONTEXT || {};
window.BISPED_TRACKING_CONTEXT.product = <?= json_encode([
    'slug' => $slug,
    'sku' => $sku,
    'name' => $name,
    'category' => $category,
    'price' => $displayPrice ? (float)$displayPrice : 0.0,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<!-- Breadcrumb -->
<nav class="flex items-center gap-2 text-sm text-muted mb-8" aria-label="Breadcrumb">
    <a href="/" class="hover:text-pri transition-colors">Home</a>
    <span>/</span>
    <a href="/products" class="hover:text-pri transition-colors">Shop</a>
    <span>/</span>
    <a href="/products#<?= strtolower(htmlspecialchars($category, ENT_QUOTES, 'UTF-8')) ?>" class="hover:text-pri transition-colors"><?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?></a>
    <span>/</span>
    <span style="color:var(--c-acc)" class="truncate max-w-xs"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></span>
</nav>

<div class="grid gap-10 lg:grid-cols-[420px_1fr] lg:items-start" data-animate>

    <!-- Image panel (sticky solo da desktop: su mobile deve scorrere col contenuto) -->
    <div class="lg:sticky lg:top-24">
        <div class="rounded-lg border overflow-hidden bg-white" style="border-color:var(--c-border);aspect-ratio:1/1">
            <?php if ($imgUrl !== ''): ?>
                <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                     class="w-full h-full object-contain p-8"
                     loading="eager" width="420" height="420">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-20 h-20 opacity-20">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                    </svg>
                </div>
            <?php endif; ?>
        </div>

        <!-- Social share -->
        <div class="mt-4 flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-widest text-muted">Condividi:</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>"
               target="_blank" rel="noopener"
               class="flex items-center gap-1.5 header-util-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12Z"/></svg>
                FB
            </a>
            <a href="https://wa.me/?text=<?= urlencode($name . ' - ' . $shareUrl) ?>"
               target="_blank" rel="noopener"
               class="flex items-center gap-1.5 header-util-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                WA
            </a>
        </div>
    </div>

    <!-- Info panel -->
    <div>
        <!-- Category + stock -->
        <div class="flex items-center gap-3 flex-wrap mb-4">
            <span class="product-card__cat"><?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="badge-stock <?= $stockClass ?>"<?= !empty($pcConfigurator) ? ' data-pc-stock-badge' : '' ?>><?= htmlspecialchars($stockLabel, ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($sku): ?>
                <span class="text-xs text-muted font-mono">SKU: <?= htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <h1 class="font-display text-2xl font-black leading-tight md:text-3xl"
            style="color:var(--c-acc)"
            <?= AdminMode::dataAttrs('products', 'name', $slug) ?>>
            <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
        </h1>

        <!-- Price -->
        <div class="mt-6 p-5 rounded-lg border" style="border-color:var(--c-border);background:var(--c-surface)">
            <?php if ($priceFormatted): ?>
                <?php if ($oldFormatted): ?>
                    <div class="text-sm text-muted line-through mb-1">€ <?= htmlspecialchars($oldFormatted, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if (!empty($pcConfigurator)): ?>
                    <div class="text-xs font-bold uppercase tracking-widest text-muted mb-1">A partire da</div>
                <?php endif; ?>
                <div class="font-display text-4xl font-black" style="color:<?= $isSale ? 'var(--bisped-red)' : 'var(--c-acc)' ?>">
                    <sup class="text-lg align-super">€</sup><span<?= $isConfigurablePc ? ' data-pc-current-price' : '' ?>><?= htmlspecialchars($priceFormatted, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="text-xs text-muted mt-1">IVA inclusa — prezzo indicativo, conferma in negozio</div>
            <?php else: ?>
                <div class="font-display text-2xl font-black" style="color:var(--c-muted)">Richiedi disponibilità e prezzo</div>
            <?php endif; ?>
        </div>

        <!-- CTA buttons -->
        <div class="mt-5 flex flex-col sm:flex-row gap-3">
            <a href="/contatti?prodotto=<?= urlencode($name) ?>&sku=<?= urlencode($sku) ?>"
               class="btn-primary" style="justify-content:center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                </svg>
                Richiedi disponibilità / preventivo
            </a>
            <a href="tel:+39056531136" class="btn-outline" style="justify-content:center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 6Z"/>
                </svg>
                Chiama il negozio
            </a>
        </div>

        <?php if ($isCheckoutEnabled && $paymentAmount !== null): ?>
            <section id="<?= htmlspecialchars($checkoutDomId, ENT_QUOTES, 'UTF-8') ?>"
                     class="mt-6 rounded-lg border p-5"
                     style="border-color:var(--c-border);background:var(--c-surface)"
                     data-checkout-root
                     data-slug="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-sm font-black uppercase tracking-widest" style="color:var(--c-muted)">Acquisto online</h2>
                        <div class="mt-1 font-display text-2xl font-black" style="color:var(--c-acc)">
                            € <?= htmlspecialchars(number_format((float)$paymentAmount, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="text-xs text-muted">IVA inclusa. Disponibilità e consegna secondo opzione selezionata.</div>
                    </div>
                    <?php if ($deliveryMethod === 'both'): ?>
                        <label class="text-sm text-muted flex flex-col gap-2 min-w-[190px]">
                            <span>Consegna</span>
                            <select class="form-input text-sm" data-delivery-select>
                                <option value="pickup">Ritiro in negozio</option>
                                <option value="shipping">Spedizione</option>
                            </select>
                        </label>
                    <?php else: ?>
                        <div class="rounded-md border px-3 py-2 text-sm" style="border-color:var(--c-border)">
                            <?= $deliveryMethod === 'shipping' ? 'Spedizione' : 'Ritiro in negozio' ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (($product['payment_notes'] ?? '') !== ''): ?>
                    <p class="mt-3 text-sm text-muted"><?= htmlspecialchars((string)$product['payment_notes'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>

                <?php if ($paymentMethod === 'paypal'): ?>
                    <?php if ($paypalConfigured): ?>
                        <?php if (!empty($product['paypal_pay_later_enabled'])): ?>
                            <div class="mt-4 rounded-md border px-3 py-3" style="border-color:var(--c-border);background:rgba(255,255,255,.03)">
                                <div data-pp-message
                                     data-pp-placement="product"
                                     data-pp-amount="<?= htmlspecialchars($paymentAmount, ENT_QUOTES, 'UTF-8') ?>"
                                     data-pp-style-layout="text"
                                     data-pp-style-logo-type="inline"
                                     data-pp-style-text-color="white"
                                     data-pp-style-text-size="13"></div>
                                <div class="mt-2 text-xs text-muted">Pagamento in 3 rate disponibile solo se PayPal lo rende eleggibile per cliente e importo.</div>
                            </div>
                        <?php endif; ?>
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <div>
                                <div class="mb-2 text-xs font-bold uppercase tracking-widest text-muted">PayPal</div>
                                <div id="<?= htmlspecialchars($checkoutDomId, ENT_QUOTES, 'UTF-8') ?>-paypal-buttons"></div>
                            </div>
                            <?php if (!empty($product['paypal_pay_later_enabled'])): ?>
                                <div>
                                    <div class="mb-2 text-xs font-bold uppercase tracking-widest text-muted">PayPal 3 rate</div>
                                    <div id="<?= htmlspecialchars($checkoutDomId, ENT_QUOTES, 'UTF-8') ?>-paylater-buttons"></div>
                                    <div class="hidden text-xs text-muted" data-paylater-unavailable>Pay Later non risulta disponibile per questa sessione.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-3 text-sm" style="color:var(--c-muted)" data-payment-status></div>
                        <script src="<?= htmlspecialchars($paypalSdkUrl, ENT_QUOTES, 'UTF-8') ?>"></script>
                        <script>
                        (function () {
                            const root = document.getElementById(<?= json_encode($checkoutDomId) ?>);
                            if (!root || !window.paypal) return;
                            const status = root.querySelector('[data-payment-status]');
                            const deliverySelect = root.querySelector('[data-delivery-select]');
                            let csrfToken = <?= json_encode($paymentCsrf ?? '') ?>;
                            const slug = <?= json_encode($slug) ?>;

                            function selectedDelivery() {
                                return deliverySelect ? deliverySelect.value : <?= json_encode($deliveryMethod === 'shipping' ? 'shipping' : 'pickup') ?>;
                            }

                            function setStatus(message, isError) {
                                if (!status) return;
                                status.textContent = message;
                                status.style.color = isError ? 'var(--bisped-red)' : 'var(--c-muted)';
                                status.dataset.paymentStatusKind = isError ? 'error' : 'info';
                            }

                            async function postJson(url, payload) {
                                const response = await fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-Token': csrfToken
                                    },
                                    body: JSON.stringify(payload || {})
                                });
                                const data = await response.json().catch(() => ({}));
                                if (data && typeof data.csrf === 'string' && data.csrf !== '') {
                                    csrfToken = data.csrf;
                                }
                                if (!response.ok || data.ok === false) {
                                    const error = new Error(data.message || data.error || 'payment_error');
                                    error.code = data.error || 'payment_error';
                                    error.payload = data;
                                    throw error;
                                }
                                return data;
                            }

                            function describeError(error, fallback) {
                                const code = error && error.code ? String(error.code) : '';
                                if (error && error.message && error.message !== code) {
                                    return error.message;
                                }
                                if (code === 'csrf') return 'Sessione scaduta: ricarica la pagina e riprova.';
                                if (code === 'paypal_not_configured') return 'PayPal non e configurato sul server.';
                                if (code === 'product_not_payable') return 'Prodotto non abilitato al pagamento online.';
                                if (code === 'order_not_found') return 'Ordine locale non trovato: contatta il negozio.';
                                if (code === 'paypal_capture_failed') return 'PayPal ha approvato il pagamento, ma la conferma server non e riuscita. Contatta il negozio prima di riprovare.';
                                return fallback;
                            }

                            function paypalButtonConfig(fundingSource) {
                                const config = {
                                    style: {
                                        layout: 'vertical',
                                        shape: 'rect',
                                        color: fundingSource ? 'gold' : 'blue',
                                        label: fundingSource ? 'paylater' : 'paypal'
                                    },
                                    createOrder: async function () {
                                        setStatus('Creo ordine sicuro PayPal...', false);
                                        try {
                                            const data = await postJson('/payments/paypal/orders/' + encodeURIComponent(slug), {
                                                delivery_method: selectedDelivery(),
                                                expected_amount: <?= json_encode($paymentAmount) ?>
                                            });
                                            return data.id;
                                        } catch (error) {
                                            setStatus(describeError(error, "PayPal non ha creato l'ordine. Riprova o contatta il negozio."), true);
                                            throw error;
                                        }
                                    },
                                    onApprove: async function (data) {
                                        setStatus('Pagamento approvato, confermo la cattura...', false);
                                        try {
                                            const orderId = data.orderID || data.orderId;
                                            const capture = await postJson('/payments/paypal/orders/' + encodeURIComponent(orderId) + '/capture', {});
                                            setStatus('Pagamento completato. Codice ordine: ' + capture.public_id, false);
                                        } catch (error) {
                                            setStatus(describeError(error, 'PayPal ha approvato il pagamento, ma la conferma server non e riuscita. Contatta il negozio prima di riprovare.'), true);
                                        }
                                    },
                                    onCancel: function () {
                                        setStatus('Pagamento annullato. Puoi riprovare quando vuoi.', true);
                                    },
                                    onError: function (error) {
                                        if (status && status.textContent && status.dataset.paymentStatusKind === 'error') return;
                                        setStatus(describeError(error, 'PayPal non ha completato il pagamento. Riprova o contatta il negozio.'), true);
                                    }
                                };
                                if (fundingSource) {
                                    config.fundingSource = fundingSource;
                                }
                                return config;
                            }

                            paypal.Buttons(paypalButtonConfig()).render('#' + <?= json_encode($checkoutDomId . '-paypal-buttons') ?>);

                            const payLaterTarget = document.getElementById(<?= json_encode($checkoutDomId . '-paylater-buttons') ?>);
                            if (payLaterTarget && paypal.FUNDING && paypal.FUNDING.PAYLATER) {
                                const payLaterButton = paypal.Buttons(paypalButtonConfig(paypal.FUNDING.PAYLATER));
                                if (payLaterButton.isEligible()) {
                                    payLaterButton.render(payLaterTarget);
                                } else {
                                    payLaterTarget.classList.add('hidden');
                                    const unavailable = root.querySelector('[data-paylater-unavailable]');
                                    if (unavailable) unavailable.classList.remove('hidden');
                                }
                            }
                        })();
                        </script>
                    <?php else: ?>
                        <div class="mt-4 rounded-md border p-4 text-sm text-muted" style="border-color:var(--c-border)">
                            Pagamento online momentaneamente non disponibile. Contattaci per completare l'acquisto di questo prodotto.
                        </div>
                    <?php endif; ?>
                <?php elseif ($paymentMethod === 'crypto'): ?>
                    <div class="mt-4 rounded-md border p-4" style="border-color:var(--c-border);background:rgba(255,255,255,.03)">
                        <p class="text-sm text-muted">Prodotto abilitato al pagamento crypto. Per sicurezza confermiamo wallet, rete e disponibilità prima dell'invio fondi.</p>
                        <button type="button" class="btn-primary mt-3" data-crypto-request>Richiedi pagamento crypto</button>
                        <div class="mt-3 text-sm text-muted" data-crypto-status></div>
                    </div>
                    <script>
                    (function () {
                        const root = document.getElementById(<?= json_encode($checkoutDomId) ?>);
                        if (!root) return;
                        const button = root.querySelector('[data-crypto-request]');
                        const status = root.querySelector('[data-crypto-status]');
                        const deliverySelect = root.querySelector('[data-delivery-select]');
                        let csrfToken = <?= json_encode($paymentCsrf ?? '') ?>;
                        const slug = <?= json_encode($slug) ?>;
                        if (!button || !status) return;
                        button.addEventListener('click', async function () {
                            button.disabled = true;
                            status.textContent = 'Registro la richiesta...';
                            try {
                                const response = await fetch('/payments/crypto/orders/' + encodeURIComponent(slug), {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
                                    body: JSON.stringify({delivery_method: deliverySelect ? deliverySelect.value : <?= json_encode($deliveryMethod === 'shipping' ? 'shipping' : 'pickup') ?>})
                                });
                                const data = await response.json();
                                if (data && typeof data.csrf === 'string' && data.csrf !== '') {
                                    csrfToken = data.csrf;
                                }
                                if (!response.ok || data.ok === false) throw new Error(data.error || 'crypto_error');
                                status.textContent = data.message + ' Codice richiesta: ' + data.public_id;
                            } catch (e) {
                                status.textContent = 'Non sono riuscito a registrare la richiesta. Contatta il negozio.';
                                button.disabled = false;
                            }
                        });
                    })();
                    </script>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if (!empty($pcConfigurator)): ?>
            <?php
            $slotLabels = $pcConfigurator['slot_labels'] ?? [];
            $selected = $pcConfigurator['selected'] ?? [];
            $options = $pcConfigurator['options'] ?? [];
            $configTotal = (float)($pcConfigurator['total'] ?? 0);
            $recommendedWattage = (int)($pcConfigurator['recommended_wattage'] ?? 0);
            $pcSlots = ['cpu', 'motherboard', 'ram', 'storage', 'gpu', 'psu', 'case', 'cooler', 'fan'];
            $peripheralSlots = ['monitor', 'keyboard', 'mouse', 'headset'];
            $optionalSlots = ['gpu', 'cooler', 'fan', 'monitor', 'keyboard', 'mouse', 'headset'];
            $visiblePcSlots = array_values(array_filter($pcSlots, static function (string $slot) use ($selected, $options): bool {
                return isset($selected[$slot]) || !empty($options[$slot]);
            }));
            $visiblePeripheralSlots = array_values(array_filter($peripheralSlots, static function (string $slot) use ($selected): bool {
                return isset($selected[$slot]);
            }));
            $hiddenPeripheralSlots = array_values(array_filter($peripheralSlots, static function (string $slot) use ($selected, $options): bool {
                return !isset($selected[$slot]) && !empty($options[$slot]);
            }));
            $renderConfigSlot = static function (string $slot, bool $isPeripheral, bool $isHidden = false) use ($slotLabels, $selected, $options, $optionalSlots, $pcConfigurator): void {
                $slotOptions = $options[$slot] ?? [];
                $currentId = (int)($selected[$slot] ?? 0);
                $isOptional = in_array($slot, $optionalSlots, true) || ($slot === 'psu' && empty($selected['psu']));
                ?>
                <div class="pc-configurator__row rounded-md border p-3<?= $isHidden ? ' hidden' : '' ?>"
                     style="border-color:var(--c-border);background:var(--c-surface)"
                     data-config-row="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>"
                     data-peripheral="<?= $isPeripheral ? '1' : '0' ?>">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <div>
                            <span class="block text-xs font-bold uppercase tracking-widest text-muted">
                                <?= htmlspecialchars((string)($slotLabels[$slot] ?? $slot), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if ($isOptional): ?>
                                <span class="text-[11px] font-bold" style="color:var(--c-muted)">Opzionale</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($isPeripheral): ?>
                            <button type="button"
                                    class="pc-configurator__remove text-xs font-bold"
                                    style="color:var(--bisped-red)"
                                    data-remove-slot="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>">
                                Rimuovi
                            </button>
                        <?php endif; ?>
                    </div>
                    <select class="pc-configurator__select hidden"
                            data-slot="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>"
                            data-optional="<?= $isOptional ? '1' : '0' ?>">
                        <?php if ($isOptional): ?>
                            <option value="">Nessuno</option>
                        <?php else: ?>
                            <option value="">Seleziona componente</option>
                            <?php if ($currentId > 0 && !in_array($currentId, array_column($slotOptions, 'id'), true)): ?>
                            <option value="<?= $currentId ?>" selected>Componente non disponibile: scegli una alternativa</option>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php foreach ($slotOptions as $option): ?>
                            <?php $optionId = (int)($option['id'] ?? 0); ?>
                            <option value="<?= $optionId ?>" <?= $optionId === $currentId ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$option['name'] . ' - ' . (string)$option['price_label'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pc-configurator__combo relative mt-2" data-combo-slot="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button"
                                class="pc-configurator__trigger form-input w-full text-left text-sm"
                                data-trigger-slot="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>">
                            Seleziona componente
                        </button>
                        <div class="pc-configurator__menu hidden absolute left-0 right-0 top-full z-30 mt-1 rounded-md border p-2 shadow-lg"
                             style="border-color:var(--c-border)"
                             data-menu-slot="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="search"
                                   class="pc-configurator__filter form-input w-full text-sm"
                                   data-filter-slot="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="Scrivi per filtrare..."
                                   autocomplete="off">
                            <div class="pc-configurator__options mt-2 max-h-64 overflow-auto"
                                 data-options-slot="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>"></div>
                        </div>
                    </div>
                    <?php if ($slot === 'ram'): ?>
                    <label class="block mt-3 text-xs font-bold">Banchi RAM installati
                        <select class="form-input w-full text-sm mt-1" data-ram-quantity>
                            <?php foreach (($pcConfigurator['memory']['choices'] ?? []) as $choice): ?>
                            <option value="<?= (int)$choice['quantity'] ?>" <?= (int)$choice['quantity'] === (int)($pcConfigurator['quantities']['ram'] ?? 1) ? 'selected' : '' ?>><?= htmlspecialchars($choice['label'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="mt-2 text-xs text-muted" data-ram-summary></div>
                    <?php endif; ?>
                    <div class="pc-configurator__meta mt-2 text-xs text-muted" data-slot-meta="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>"></div>
                </div>
                <?php
            };
            ?>
            <section class="mt-8 border-t pt-6 pc-configurator"
                     style="border-color:var(--c-border)"
                     data-endpoint="/products/<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>/configurator-options">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-sm font-black uppercase tracking-widest" style="color:var(--c-muted)">Configura il PC</h2>
                        <div class="mt-2 text-sm text-muted">Alternative filtrate per compatibilità tecnica.</div>
                    </div>
                    <div class="rounded-md border px-4 py-3 text-right" style="border-color:var(--c-border);background:var(--c-surface)">
                        <div class="text-xs font-bold uppercase tracking-widest text-muted">Totale configurazione</div>
                        <div class="pc-configurator__total font-display text-2xl font-black" style="color:var(--c-acc)">
                            € <?= htmlspecialchars(number_format($configTotal, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php if ($recommendedWattage > 0): ?>
                            <div class="pc-configurator__watt text-xs text-muted">PSU consigliato: <?= $recommendedWattage ?>W+</div>
                        <?php else: ?>
                            <div class="pc-configurator__watt text-xs text-muted"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-5">
                    <h3 class="text-xs font-black uppercase tracking-widest" style="color:var(--c-muted)">PC</h3>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        <?php foreach ($visiblePcSlots as $slot): ?>
                            <?php $renderConfigSlot($slot, false); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mt-6 border-t pt-5" style="border-color:var(--c-border)">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-widest" style="color:var(--c-muted)">Periferiche</h3>
                        </div>
                        <div class="flex gap-2">
                            <select class="pc-configurator__add-select form-input text-sm" aria-label="Categoria periferica">
                                <?php foreach ($hiddenPeripheralSlots as $slot): ?>
                                    <option value="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars((string)($slotLabels[$slot] ?? $slot), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="pc-configurator__add btn-outline text-sm">+ Aggiungi periferica</button>
                        </div>
                    </div>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        <?php foreach ($visiblePeripheralSlots as $slot): ?>
                            <?php $renderConfigSlot($slot, true); ?>
                        <?php endforeach; ?>
                        <?php foreach ($hiddenPeripheralSlots as $slot): ?>
                            <?php $renderConfigSlot($slot, true, true); ?>
                        <?php endforeach; ?>
                    </div>
                    <?php if (empty($visiblePeripheralSlots) && empty($hiddenPeripheralSlots)): ?>
                        <div class="mt-3 text-xs text-muted">Periferiche non disponibili per questa configurazione.</div>
                    <?php endif; ?>
                </div>
                <div class="pc-configurator__status mt-3 text-xs text-muted"></div>
            </section>

            <script>
            (function () {
                const root = document.currentScript.previousElementSibling;
                if (!root || !root.classList.contains('pc-configurator')) return;
                const endpoint = root.dataset.endpoint;
                const total = root.querySelector('.pc-configurator__total');
                const watt = root.querySelector('.pc-configurator__watt');
                const status = root.querySelector('.pc-configurator__status');
                const stockBadge = document.querySelector('[data-pc-stock-badge]');
                const addSelect = root.querySelector('.pc-configurator__add-select');
                const addButton = root.querySelector('.pc-configurator__add');
                const latestOptions = {};
                const baseOptions = {};
                const selectedState = {};
                let requestSequence = 0;
                const ramQuantity = root.querySelector('[data-ram-quantity]');
                let installedRam = <?= json_encode($pcConfigurator['memory'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
                if (ramQuantity && !ramQuantity.value) ramQuantity.value = String(installedRam.quantity || 1);

                function rows() {
                    return Array.from(root.querySelectorAll('.pc-configurator__row'));
                }

                function visibleRows() {
                    return rows().filter(row => !row.classList.contains('hidden'));
                }

                function selects() {
                    return Array.from(root.querySelectorAll('.pc-configurator__select'));
                }

                function collect() {
                    const params = new URLSearchParams();
                    rows().forEach(row => {
                        const select = row.querySelector('.pc-configurator__select');
                        if (!select) return;
                        const slot = select.dataset.slot;
                        const value = row.classList.contains('hidden')
                            ? ''
                            : (Object.prototype.hasOwnProperty.call(selectedState, slot) ? selectedState[slot] : select.value);
                        // Sending an empty value is intentional: it removes an optional
                        // component from the server-side selection.
                        params.set(slot, value || '');
                    });
                    if (ramQuantity) params.set('ram_quantity', ramQuantity.value || '1');
                    return params;
                }

                function setSelectedValue(slot, value) {
                    if (value) {
                        selectedState[slot] = String(value);
                    } else {
                        delete selectedState[slot];
                    }
                }

                function selectedOption(select, options) {
                    return (options || []).find(option => String(option.id) === String(select.value)) || null;
                }

                function formatPrice(value) {
                    return '€ ' + Number(value || 0).toLocaleString('it-IT', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }

                function formatDelta(option, current) {
                    if (!option || !current || option.price === null || current.price === null) return null;
                    const diff = Number(option.price) - Number(current.price);
                    if (Math.abs(diff) < 0.01) return {text: '±€ 0,00', color: 'var(--c-muted)'};
                    return {
                        text: (diff > 0 ? '+ ' : '- ') + formatPrice(Math.abs(diff)),
                        color: diff > 0 ? 'var(--c-stock-ok)' : 'var(--bisped-red)'
                    };
                }

                function specsLine(option) {
                    if (!option) return '';
                    const specs = option.specs || {};
                    const bits = [];
                    if (specs.socket) bits.push(specs.socket);
                    if (specs.memory_type) bits.push(specs.memory_type);
                    if (specs.form_factor) bits.push(specs.form_factor);
                    if (specs.interface_type) bits.push(specs.interface_type);
                    if (specs.wattage) bits.push(specs.wattage + 'W');
                    if (specs.capacity_gb) bits.push(specs.capacity_gb + 'GB' + (specs.modules_per_unit > 1 ? ' (kit ' + specs.modules_per_unit + ' x ' + specs.module_capacity_gb + 'GB)' : ''));
                    if (specs.memory_slots) bits.push(specs.memory_slots + ' slot RAM');
                    if (typeof specs.integrated_graphics === 'boolean') bits.push(specs.integrated_graphics ? 'iGPU' : 'no iGPU');
                    return bits.join(' · ');
                }

                function syncSelect(select, options, serverSelected) {
                    const previous = serverSelected === undefined ? select.value : String(serverSelected || '');
                    const isOptional = select.dataset.optional === '1';
                    select.innerHTML = '';
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = isOptional ? 'Nessuno' : 'Seleziona componente';
                    select.appendChild(placeholder);
                    (options || []).forEach(option => {
                        const node = document.createElement('option'); node.value = option.id;
                        node.textContent = option.name + ' - ' + option.price_label; select.appendChild(node);
                    });
                    if (previous && !(options || []).some(option => String(option.id) === previous)) {
                        const unavailable = document.createElement('option'); unavailable.value = previous;
                        unavailable.textContent = 'Componente non disponibile: scegli una alternativa'; select.appendChild(unavailable);
                    }
                    select.value = previous;
                }

                function optionLabel(option, current) {
                    const delta = formatDelta(option, current);
                    return option.name + ' - ' + option.price_label + (delta ? ' (' + delta.text.replace('€ ', 'EUR ') + ')' : '');
                }

                function syncPicker(slot, options) {
                    const select = root.querySelector('[data-slot="' + slot + '"]');
                    const trigger = root.querySelector('[data-trigger-slot="' + slot + '"]');
                    const input = root.querySelector('[data-filter-slot="' + slot + '"]');
                    const list = root.querySelector('[data-options-slot="' + slot + '"]');
                    const meta = root.querySelector('[data-slot-meta="' + slot + '"]');
                    if (!select || !trigger || !list) return;

                    const q = (input ? input.value : '').trim().toLocaleLowerCase('it-IT');
                    const current = selectedOption(select, options);
                    const filtered = (options || []).filter(option => {
                        if (!q) return true;
                        const text = String(option.search_text || option.name || '').toLocaleLowerCase('it-IT');
                        return text.includes(q);
                    });

                    trigger.textContent = current ? optionLabel(current, current) : (select.dataset.optional === '1' ? 'Nessuno' : 'Seleziona componente');
                    list.innerHTML = '';

                    if (select.dataset.optional === '1') {
                        const empty = document.createElement('button');
                        empty.type = 'button';
                        empty.className = 'pc-configurator__option block w-full rounded px-2 py-2 text-left text-sm';
                        empty.dataset.optionValue = '';
                        empty.textContent = 'Nessuno';
                        if (!select.value) empty.style.fontWeight = '700';
                        empty.addEventListener('click', () => chooseOption(slot, ''));
                        list.appendChild(empty);
                    }

                    filtered.forEach(option => {
                        const node = document.createElement('button');
                        node.type = 'button';
                        node.className = 'pc-configurator__option flex w-full items-start justify-between gap-3 rounded px-2 py-2 text-left text-sm';
                        node.dataset.optionValue = option.id;
                        const label = document.createElement('span');
                        label.className = 'min-w-0';
                        label.textContent = option.name + ' - ' + option.price_label;
                        node.appendChild(label);
                        const delta = formatDelta(option, current);
                        if (delta) {
                            const deltaNode = document.createElement('span');
                            deltaNode.className = 'shrink-0 font-bold';
                            deltaNode.textContent = delta.text;
                            deltaNode.style.color = delta.color;
                            node.appendChild(deltaNode);
                        }
                        if (String(option.id) === String(select.value)) node.style.fontWeight = '700';
                        node.addEventListener('click', () => chooseOption(slot, option.id));
                        list.appendChild(node);
                    });

                    if (filtered.length === 0 && !(select.dataset.optional === '1')) {
                        const empty = document.createElement('div');
                        empty.className = 'px-2 py-2 text-sm text-muted';
                        empty.textContent = 'Nessun componente trovato';
                        list.appendChild(empty);
                    }

                    if (meta) {
                        meta.innerHTML = '';
                        if (current) {
                            const line = document.createElement('span');
                            line.textContent = specsLine(current);
                            meta.appendChild(line);

                            if (current.price_label) {
                                const price = document.createElement('span');
                                price.textContent = (line.textContent ? ' · ' : '') + current.price_label;
                                meta.appendChild(price);
                            }
                            const reference = baseOptions[slot] || current;
                            const delta = formatDelta(current, reference);
                            if (delta) {
                                const deltaNode = document.createElement('span');
                                deltaNode.textContent = ' · ' + delta.text;
                                deltaNode.style.color = delta.color;
                                deltaNode.style.fontWeight = '700';
                                meta.appendChild(deltaNode);
                            }
                        } else {
                            meta.textContent = select.dataset.optional === '1' ? 'Non incluso' : '';
                        }
                    }
                }

                function updateAddControls() {
                    if (!addSelect || !addButton) return;
                    addSelect.innerHTML = '';
                    rows().forEach(row => {
                        if (row.dataset.peripheral !== '1' || !row.classList.contains('hidden')) return;
                        const select = row.querySelector('.pc-configurator__select');
                        const label = row.querySelector('.block.text-xs');
                        if (!select || select.options.length <= 1) return;
                        const option = document.createElement('option');
                        option.value = select.dataset.slot;
                        option.textContent = label ? label.textContent.trim() : select.dataset.slot;
                        addSelect.appendChild(option);
                    });
                    const hasOptions = addSelect.options.length > 0;
                    addSelect.disabled = !hasOptions;
                    addButton.disabled = !hasOptions;
                    addSelect.classList.toggle('hidden', !hasOptions);
                    addButton.classList.toggle('hidden', !hasOptions);
                }

                function selectedLabel(row) {
                    const label = row.querySelector('.block.text-xs');
                    return label ? label.textContent.trim() : '';
                }

                function appendItem(list, row) {
                    const select = row.querySelector('.pc-configurator__select');
                    if (!select || !select.value) return false;
                    const slot = select.dataset.slot;
                    const option = selectedOption(select, latestOptions[slot] || []);
                    if (!option) return false;

                    const item = document.createElement('li');
                    const name = document.createElement('strong');
                    name.textContent = selectedLabel(row) + ': ';
                    item.appendChild(name);
                    item.append(document.createTextNode((slot === 'ram' ? (installedRam.quantity || 1) + ' x ' : '') + (option.name || 'Componente')));
                    list.appendChild(item);
                    return true;
                }

                function updateDescription(data) {
                    const desc = document.querySelector('.pc-configurator__description');
                    if (!desc) return;

                    desc.innerHTML = '';

                    const intro = document.createElement('p');
                    intro.textContent = 'Configurazione aggiornata in tempo reale dal configuratore: componenti compatibili, prezzo dei componenti e periferiche selezionate.';
                    desc.appendChild(intro);

                    const pcTitle = document.createElement('h3');
                    pcTitle.textContent = 'Componenti nel PC';
                    desc.appendChild(pcTitle);
                    const pcList = document.createElement('ul');
                    visibleRows()
                        .filter(row => row.dataset.peripheral !== '1')
                        .forEach(row => appendItem(pcList, row));
                    desc.appendChild(pcList);

                    const peripheralList = document.createElement('ul');
                    visibleRows()
                        .filter(row => row.dataset.peripheral === '1')
                        .forEach(row => appendItem(peripheralList, row));
                    if (peripheralList.children.length > 0) {
                        const peripheralTitle = document.createElement('h3');
                        peripheralTitle.textContent = 'Periferiche';
                        desc.appendChild(peripheralTitle);
                        desc.appendChild(peripheralList);
                    }

                    const totalLine = document.createElement('p');
                    totalLine.append(document.createTextNode('Totale componenti attuale: '));
                    const totalStrong = document.createElement('strong');
                    totalStrong.textContent = formatPrice(data.total || 0);
                    totalLine.appendChild(totalStrong);
                    if (data.recommended_wattage) {
                        totalLine.append(document.createTextNode('. Alimentatore consigliato: '));
                        const wattStrong = document.createElement('strong');
                        wattStrong.textContent = data.recommended_wattage + 'W o superiore';
                        totalLine.appendChild(wattStrong);
                    }
                    totalLine.append(document.createTextNode('.'));
                    desc.appendChild(totalLine);
                }

                function render(data) {
                    installedRam = data.memory || {};
                    if (ramQuantity) {
                        ramQuantity.innerHTML = '';
                        (installedRam.choices || []).forEach(choice => {
                            const option = document.createElement('option'); option.value = choice.quantity;
                            option.textContent = choice.label + ' - ' + formatPrice(choice.price); ramQuantity.appendChild(option);
                        });
                        const quantity = String((data.quantities || {}).ram || 1);
                        if (!Array.from(ramQuantity.options).some(option => option.value === quantity)) {
                            const invalid = document.createElement('option'); invalid.value = quantity; invalid.textContent = 'Quantita RAM da verificare'; ramQuantity.appendChild(invalid);
                        }
                        ramQuantity.value = quantity;
                        ramQuantity.disabled = !(installedRam.choices || []).length;
                    }
                    const memorySummary = root.querySelector('[data-ram-summary]');
                    if (memorySummary) memorySummary.textContent = installedRam.total_gb
                        ? installedRam.total_gb + ' GB totali, ' + installedRam.modules + ' banchi installati. ' + (installedRam.slots ? 'Scheda madre: ' + installedRam.slots + ' slot.' : 'Numero di slot da verificare: espansione multipla disabilitata.')
                        : 'Seleziona una memoria compatibile.';
                    Object.keys(selectedState).forEach(slot => delete selectedState[slot]);
                    Object.entries(data.selected || {}).forEach(([slot, value]) => setSelectedValue(slot, value));
                    Object.entries(data.options || {}).forEach(([slot, options]) => {
                        const select = root.querySelector('[data-slot="' + slot + '"]');
                        if (!select) return;
                        latestOptions[slot] = options || [];
                        const serverSelected = data.selected && Object.prototype.hasOwnProperty.call(data.selected, slot)
                            ? data.selected[slot]
                            : undefined;
                        syncSelect(select, options || [], serverSelected);
                        if (!baseOptions[slot]) {
                            baseOptions[slot] = selectedOption(select, options || []);
                        }
                        setSelectedValue(slot, select.value);
                        syncPicker(slot, options || []);
                    });

                    updateAddControls();
                    if (total) total.textContent = data.valid ? formatPrice(data.total || 0) : 'Da verificare';
                    const headlinePrice = document.querySelector('[data-pc-current-price]');
                    if (headlinePrice) headlinePrice.textContent = data.valid ? Number(data.total || 0).toLocaleString('it-IT', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : 'Da verificare';
                    if (watt) watt.textContent = data.recommended_wattage ? 'PSU consigliato: ' + data.recommended_wattage + 'W+' : '';
                    if (stockBadge && data.stock) {
                        stockBadge.textContent = data.stock.label || (data.stock.available ? 'Disponibile' : 'Su richiesta');
                        stockBadge.classList.toggle('badge-stock--in', Boolean(data.stock.available));
                        stockBadge.classList.toggle('badge-stock--ask', !data.stock.available);
                        stockBadge.classList.remove('badge-stock--out');
                    }
                    if (status) status.textContent = data.valid ? '' : 'Configurazione da ricontrollare: prova una combinazione diversa.';
                    updateDescription(data);
                }

                function closeMenus(exceptSlot) {
                    root.querySelectorAll('.pc-configurator__menu').forEach(menu => {
                        if (exceptSlot && menu.dataset.menuSlot === exceptSlot) return;
                        menu.classList.add('hidden');
                    });
                }

                function refresh() {
                    const sequence = ++requestSequence;
                    status.textContent = 'Aggiorno compatibilita...';
                    fetch(endpoint + '?' + collect().toString(), {headers: {'Accept': 'application/json'}})
                        .then(response => response.json())
                        .then(data => { if (sequence !== requestSequence) return; if (!data.ok) throw new Error('configurator'); render(data); })
                        .catch(() => { if (sequence === requestSequence) status.textContent = 'Configuratore non disponibile in questo momento.'; });
                }

                function chooseOption(slot, value) {
                    const select = root.querySelector('[data-slot="' + slot + '"]');
                    if (!select) return;
                    if (ramQuantity && (slot === 'ram' || slot === 'motherboard')) ramQuantity.value = '1';
                    select.value = value || '';
                    setSelectedValue(slot, value || '');
                    closeMenus();
                    refresh();
                }

                root.querySelectorAll('.pc-configurator__trigger').forEach(button => {
                    button.addEventListener('click', () => {
                        const slot = button.dataset.triggerSlot;
                        const menu = root.querySelector('[data-menu-slot="' + slot + '"]');
                        const filter = root.querySelector('[data-filter-slot="' + slot + '"]');
                        if (!menu) return;
                        const willOpen = menu.classList.contains('hidden');
                        closeMenus(slot);
                        menu.classList.toggle('hidden', !willOpen);
                        if (willOpen && filter) {
                            filter.focus();
                            filter.select();
                        }
                    });
                });

                root.querySelectorAll('.pc-configurator__filter').forEach(input => {
                    input.addEventListener('input', () => {
                        const slot = input.dataset.filterSlot;
                        syncPicker(slot, latestOptions[slot] || []);
                    });
                });

                selects().forEach(select => select.addEventListener('change', () => {
                    setSelectedValue(select.dataset.slot, select.value);
                    refresh();
                }));

                document.addEventListener('click', event => {
                    if (!root.contains(event.target)) closeMenus();
                });

                document.addEventListener('keydown', event => {
                    if (event.key === 'Escape') closeMenus();
                });

                root.querySelectorAll('.pc-configurator__options').forEach(list => {
                    list.addEventListener('click', () => {
                        const slot = list.dataset.optionsSlot;
                        const select = root.querySelector('[data-slot="' + slot + '"]');
                        if (select) syncPicker(slot, latestOptions[slot] || []);
                    });
                });

                root.querySelectorAll('.pc-configurator__remove').forEach(button => {
                    button.addEventListener('click', () => {
                        const row = root.querySelector('[data-config-row="' + button.dataset.removeSlot + '"]');
                        const select = row ? row.querySelector('.pc-configurator__select') : null;
                        if (!row || !select) return;
                        select.value = '';
                        setSelectedValue(select.dataset.slot, '');
                        row.classList.add('hidden');
                        refresh();
                    });
                });

                if (addButton && addSelect) {
                    addButton.addEventListener('click', () => {
                        const row = root.querySelector('[data-config-row="' + addSelect.value + '"]');
                        const select = row ? row.querySelector('.pc-configurator__select') : null;
                        if (!row || !select) return;
                        row.classList.remove('hidden');
                        const firstOption = Array.from(select.options).find(option => option.value !== '');
                        select.value = firstOption ? firstOption.value : '';
                        setSelectedValue(select.dataset.slot, select.value);
                        refresh();
                    });
                }

                if (ramQuantity) ramQuantity.addEventListener('change', refresh);
                refresh();
            })();
            </script>
        <?php endif; ?>

        <?php
        // Mostra content_html curato se presente; altrimenti la descrizione del
        // fornitore (prodotti Runner). I tag in description sono già sanificati all'import.
        $descHtml = (string)($product['description'] ?? '');
        $detailHtml = ($contentHtml && strip_tags($contentHtml) !== '') ? $contentHtml : '';
        if ($detailHtml === '' && strip_tags($descHtml) !== '' && trim($descHtml) !== trim($name)) {
            $detailHtml = '<p>' . $descHtml . '</p>';
        }
        ?>
        <?php if ($detailHtml !== ''): ?>
        <!-- Description -->
        <div class="mt-8 border-t pt-6" style="border-color:var(--c-border)"
             <?= AdminMode::dataAttrs('products', 'content_html', $slug, 'html') ?>>
            <h2 class="text-sm font-black uppercase tracking-widest mb-4" style="color:var(--c-muted)">Dettagli prodotto</h2>
            <div class="pc-configurator__description text-sm leading-6" style="color:var(--c-txt)">
                <?= $detailHtml ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($features)): ?>
        <!-- Features -->
        <div class="mt-6 border-t pt-6" style="border-color:var(--c-border)">
            <h2 class="text-sm font-black uppercase tracking-widest mb-4" style="color:var(--c-muted)">Punti chiave</h2>
            <ul class="space-y-2.5">
                <?php foreach ($features as $feat): ?>
                    <li class="flex items-start gap-2.5 text-sm" style="color:var(--c-txt)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0 mt-0.5" style="color:var(--c-stock-ok)">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                        <?= htmlspecialchars($feat, ENT_QUOTES, 'UTF-8') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($tags)): ?>
        <div class="mt-6 flex flex-wrap gap-2">
            <?php foreach ($tags as $tag): ?>
                <span class="text-xs px-3 py-1 rounded-full font-bold text-muted"
                      style="background:var(--c-surface);border:1px solid var(--c-border)">
                    #<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Back to shop -->
<div class="mt-12 pt-8 border-t" style="border-color:var(--c-border)">
    <a href="/products" class="btn-outline">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        Torna al catalogo
    </a>
</div>
