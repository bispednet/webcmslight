<?php
/** @var array $product */
/** @var array $categoryOptions */
/** @var string $featureText */
/** @var array $errors */
/** @var string $formAction */
/** @var string $submitLabel */
/** @var string $mode */
/** @var string $csrfToken */
?>

<section class="max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-acc">
                <?= htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <p class="text-sm text-muted">Manage hero data, copy, and feature bullets for this product.</p>
        </div>
        <a href="/admin/products" class="text-sm text-muted hover:text-acc transition">Back to list</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="card border-red-500/40 bg-red-500/10 text-red-100 text-sm space-y-2">
            <p class="font-semibold">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1 marker:text-red-300">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>" class="space-y-6" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Name</span>
                <input type="text" name="name" required
                       value="<?= htmlspecialchars($product['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Slug</span>
                <input type="text" name="slug" required
                       value="<?= htmlspecialchars($product['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none"
                       placeholder="example-product">
            </label>
        </div>

        <label class="text-sm text-muted flex flex-col gap-2">
            <span>Description</span>
            <textarea name="description" rows="3" required
                      class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none"><?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Icon Key</span>
                <input type="text" name="icon_key"
                       value="<?= htmlspecialchars($product['icon_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none"
                       placeholder="bolt">
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>External Link</span>
                <input type="url" name="external_link"
                       value="<?= htmlspecialchars($product['external_link'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none"
                       placeholder="https://example.com">
            </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-[1fr_220px] gap-4">
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Product Image URL</span>
                <input type="text" name="image_url"
                       value="<?= htmlspecialchars($product['image_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none"
                       placeholder="/media/2026/07/prodotto.jpg">
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Upload Photo</span>
                <input type="file" name="image_upload" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/x-icon"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
            </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Hero Title</span>
                <input type="text" name="hero_title"
                       value="<?= htmlspecialchars($product['hero_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Hero Subtitle</span>
                <input type="text" name="hero_subtitle"
                       value="<?= htmlspecialchars($product['hero_subtitle'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
            </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>CTA Text</span>
                <input type="text" name="cta_text"
                       value="<?= htmlspecialchars($product['cta_text'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>CTA Link</span>
                <input type="url" name="cta_link"
                       value="<?= htmlspecialchars($product['cta_link'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
            </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Category</span>
                <select name="category"
                        class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
                    <option value="">Select category</option>
                    <?php foreach (($categoryOptions ?? []) as $category): ?>
                        <option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>"
                            <?= (string)($product['category'] ?? '') === (string)$category ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Tags</span>
                <input type="text" name="tags"
                       value="<?= htmlspecialchars($product['tags'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none"
                       placeholder="gaming, periferiche, promo">
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>SKU</span>
                <input type="text" name="sku"
                       value="<?= htmlspecialchars($product['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
            </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Price</span>
                <input type="text" name="price"
                       value="<?= htmlspecialchars((string)($product['price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Sale Price</span>
                <input type="text" name="sale_price"
                       value="<?= htmlspecialchars((string)($product['sale_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Campaign</span>
                <input type="text" name="campaign_label"
                       value="<?= htmlspecialchars($product['campaign_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none"
                       placeholder="Promo reparto">
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Stock Status</span>
                <input type="text" name="stock_status"
                       value="<?= htmlspecialchars($product['stock_status'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none"
                       placeholder="Disponibile">
            </label>
        </div>

        <label class="text-sm text-muted flex flex-col gap-2 w-full md:w-40">
            <span>Featured Order</span>
            <input type="number" name="featured_order" min="0"
                   value="<?= htmlspecialchars((string)($product['featured_order'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
                   class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
        </label>

        <div class="card space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-acc">Pagamento e consegna</h2>
                <p class="text-xs text-muted mt-1">Lascia “Solo richiesta” per i prodotti normali. Se scegli PayPal o crypto, il prodotto diventa acquistabile dalla scheda pubblica.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label class="text-sm text-muted flex flex-col gap-2">
                    <span>Payment Method</span>
                    <select name="payment_method"
                            class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
                        <?php $paymentMethod = (string)($product['payment_method'] ?? 'none'); ?>
                        <option value="none" <?= $paymentMethod === 'none' ? 'selected' : '' ?>>Solo richiesta / preventivo</option>
                        <option value="paypal" <?= $paymentMethod === 'paypal' ? 'selected' : '' ?>>PayPal</option>
                        <option value="crypto" <?= $paymentMethod === 'crypto' ? 'selected' : '' ?>>Crypto</option>
                    </select>
                </label>
                <label class="text-sm text-muted flex flex-col gap-2">
                    <span>Delivery Method</span>
                    <?php $deliveryMethod = (string)($product['delivery_method'] ?? 'pickup'); ?>
                    <select name="delivery_method"
                            class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
                        <option value="pickup" <?= $deliveryMethod === 'pickup' ? 'selected' : '' ?>>Ritiro in negozio</option>
                        <option value="shipping" <?= $deliveryMethod === 'shipping' ? 'selected' : '' ?>>Spedizione</option>
                        <option value="both" <?= $deliveryMethod === 'both' ? 'selected' : '' ?>>Spedizione o ritiro</option>
                    </select>
                </label>
                <label class="text-sm text-muted flex items-center gap-2 pt-7">
                    <input type="checkbox" name="paypal_pay_later_enabled" value="1"
                           <?= !empty($product['paypal_pay_later_enabled']) ? 'checked' : '' ?>
                           class="rounded border-stroke bg-bg2 text-pri focus:ring-pri">
                    <span>Mostra PayPal 3 rate / Pay Later</span>
                </label>
            </div>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Payment Notes</span>
                <input type="text" name="payment_notes" maxlength="255"
                       value="<?= htmlspecialchars($product['payment_notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none"
                       placeholder="Es. Spedizione inclusa, ritiro entro 48h, istruzioni crypto">
            </label>
        </div>

        <label class="text-sm text-muted flex flex-col gap-2">
            <span>Feature Bullets (one per line)</span>
            <textarea name="features" rows="5"
                      class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none"
                      placeholder="Fast integrations&#10;Enterprise support&#10;Security-first"><?= htmlspecialchars($featureText, ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>

        <label class="text-sm text-muted flex flex-col gap-2">
            <span>Detailed Content (HTML allowed)</span>
            <textarea name="content_html" rows="10"
                      class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none monospace"><?= htmlspecialchars($product['content_html'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>

        <div class="flex items-center gap-3 pt-4">
            <button type="submit" class="bg-pri text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-red-500/80 transition">
                <?= htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8') ?>
            </button>
            <a href="/admin/products" class="text-sm text-muted hover:text-acc transition">Cancel</a>
        </div>
    </form>
</section>
