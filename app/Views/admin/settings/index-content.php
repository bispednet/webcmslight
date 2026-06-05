<?php
/** @var array $settings */
/** @var string|null $notice */
/** @var string|null $error */
/** @var string $csrfToken */

use App\Support\Media;

$normalizeAsset = static function (?string $value): string {
    $value = $value ?? '';
    $trimmed = trim($value);
    if ($trimmed === '') {
        return '';
    }
    if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
        return $trimmed;
    }
    if (preg_match('#^(?:/)?(?:media/|assets/svg-default/)#', $trimmed) === 1) {
        return Media::normalizeMediaPath($trimmed);
    }
    return '/' . ltrim($trimmed, '/');
};

$siteLogo = $normalizeAsset($settings['site_logo'] ?? '');
$siteLogoPreview = Media::siteLogoUrl($settings['site_logo'] ?? '');
$favicon = $normalizeAsset($settings['favicon_path'] ?? '/favicon.svg');
$shareImage = $normalizeAsset($settings['seo_share_image'] ?? ($settings['og_image'] ?? ''));
if ($shareImage === '') {
    $shareImage = Media::assetSvg('products/product1.svg');
}
$baseSiteName = $settings['site_name'] ?? 'Bisped';
?>

<section class="space-y-8 max-w-4xl">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-acc">Site Settings</h1>
            <p class="text-sm text-muted">Manage branding, SEO preview, and global metadata.</p>
        </div>
        <a href="/" target="_blank" class="text-sm text-cy hover:underline">Open site</a>
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

    <form method="post" action="/admin/settings" class="card space-y-4" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="general">
        <h2 class="text-sm font-semibold text-acc uppercase tracking-wide">General</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Site title</span>
                <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Tagline</span>
                <input type="text" name="site_tagline" value="<?= htmlspecialchars($settings['site_tagline'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Contact email</span>
                <input type="email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Telegram (business)</span>
                <input type="url" name="business_telegram" value="<?= htmlspecialchars($settings['business_telegram'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none" placeholder="https://t.me/...">
            </label>
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="bg-pri text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-red-500/80 transition">Save General Settings</button>
        </div>
    </form>

    <form method="post" action="/admin/settings" class="card space-y-4" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="brand">
        <h2 class="text-sm font-semibold text-acc uppercase tracking-wide">Brand Assets</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-3">
                <p class="text-sm font-semibold text-acc">Site logo</p>
                <div class="bg-bg2 border border-stroke rounded-lg p-4 flex items-center justify-center min-h-[120px]">
                    <img src="<?= htmlspecialchars($siteLogoPreview, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($baseSiteName, ENT_QUOTES, 'UTF-8'); ?>" class="max-h-20 max-w-full">
                    <?php if ($siteLogo === ''): ?>
                        <span class="sr-only">Using default site logo</span>
                    <?php endif; ?>
                </div>
                <input type="file" name="site_logo" accept=".png,.jpg,.jpeg,.webp,.svg" class="text-sm text-muted">
                <p class="text-xs text-muted">PNG/SVG/WebP, up to 5 MB.</p>
            </div>
            <div class="space-y-3">
                <p class="text-sm font-semibold text-acc">Favicon</p>
                <div class="bg-bg2 border border-stroke rounded-lg p-4 flex items-center justify-center min-h-[120px]">
                    <img src="<?= htmlspecialchars($favicon, ENT_QUOTES, 'UTF-8'); ?>" alt="Favicon" class="h-12 w-12">
                </div>
                <input type="file" name="favicon" accept=".png,.jpg,.jpeg,.webp,.svg,.ico" class="text-sm text-muted">
                <p class="text-xs text-muted">Square image or .ico, up to 5 MB.</p>
            </div>
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="bg-pri text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-red-500/80 transition">Save Brand Assets</button>
        </div>
    </form>

    <form method="post" action="/admin/settings" class="card space-y-4" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="seo">
        <h2 class="text-sm font-semibold text-acc uppercase tracking-wide">SEO & Social Preview</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Meta title</span>
                <input type="text" name="seo_meta_title" value="<?= htmlspecialchars($settings['seo_meta_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Social title</span>
                <input type="text" name="seo_social_title" value="<?= htmlspecialchars($settings['seo_social_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none">
            </label>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Meta description</span>
                <textarea name="seo_meta_description" rows="3" class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none"><?= htmlspecialchars($settings['seo_meta_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Social description</span>
                <textarea name="seo_social_description" rows="3" class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none"><?= htmlspecialchars($settings['seo_social_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>X (Twitter) preview</span>
                <textarea name="seo_twitter_description" rows="3" class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none"><?= htmlspecialchars($settings['seo_twitter_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Telegram preview</span>
                <textarea name="seo_telegram_description" rows="3" class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none"><?= htmlspecialchars($settings['seo_telegram_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                <span>Discord preview</span>
                <textarea name="seo_discord_description" rows="3" class="bg-bg2 border border-stroke rounded-md px-3 py-2 text-acc focus:border-cy focus:outline-none"><?= htmlspecialchars($settings['seo_discord_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
        </div>
        <div class="space-y-3">
            <p class="text-sm font-semibold text-acc">Share preview image</p>
            <div class="bg-bg2 border border-stroke rounded-lg p-4 flex items-center justify-center min-h-[160px]">
                <?php if ($shareImage): ?>
                    <img src="<?= htmlspecialchars($shareImage, ENT_QUOTES, 'UTF-8'); ?>" alt="Share preview" class="max-h-40 max-w-full">
                <?php else: ?>
                    <span class="text-xs text-muted">No preview image uploaded</span>
                <?php endif; ?>
            </div>
            <input type="file" name="seo_share_image" accept=".png,.jpg,.jpeg,.webp" class="text-sm text-muted">
            <p class="text-xs text-muted">Recommended 1200x630, PNG/JPEG/WebP, max 5 MB.</p>
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="bg-pri text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-red-500/80 transition">Save SEO Settings</button>
        </div>
    </form>

    <?php
    // Valori pricing correnti (settings DB con fallback ai default)
    $pMarkup = isset($settings['catalog_markup_default']) ? round((float)$settings['catalog_markup_default'] * 100, 2) : 10;
    $pFixed  = isset($settings['catalog_markup_fixed'])   ? round((float)$settings['catalog_markup_fixed'], 2)      : 5;
    $pVat    = isset($settings['catalog_vat'])            ? round((float)$settings['catalog_vat'] * 100, 2)         : 22;
    $pDisc   = isset($settings['catalog_max_discount'])   ? round((float)$settings['catalog_max_discount'] * 100, 2) : 5;
    ?>
    <form method="post" action="/admin/settings" class="card space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="pricing">
        <h2 class="text-sm font-semibold text-acc uppercase tracking-wide">Catalogo — Ricarico prezzi (Runner)</h2>
        <p class="text-xs text-muted">
            Formula prezzo di vendita: <code>(costo × (1 + ricarico%) + fisso€) × (1 + IVA%)</code>, arrotondato a ,90.
            Il ricarico fisso scoraggia la vendita di minuteria a basso margine.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="text-sm text-muted flex flex-col gap-2">
                Ricarico percentuale (%)
                <input type="number" step="0.1" min="0" max="200" name="markup_default" value="<?= htmlspecialchars((string)$pMarkup, ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg border border-border rounded-md px-3 py-2 text-txt">
                <span class="text-xs text-muted">Default su tutte le categorie (es. 10). Le override per categoria restano in .env.php</span>
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                Ricarico fisso (€)
                <input type="number" step="0.5" min="0" max="1000" name="markup_fixed" value="<?= htmlspecialchars((string)$pFixed, ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg border border-border rounded-md px-3 py-2 text-txt">
                <span class="text-xs text-muted">Somma fissa su ogni prodotto (es. 5€)</span>
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                IVA (%)
                <input type="number" step="0.1" min="0" max="100" name="vat" value="<?= htmlspecialchars((string)$pVat, ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg border border-border rounded-md px-3 py-2 text-txt">
            </label>
            <label class="text-sm text-muted flex flex-col gap-2">
                Sconto massimo in trattativa (%)
                <input type="number" step="0.5" min="0" max="100" name="max_discount" value="<?= htmlspecialchars((string)$pDisc, ENT_QUOTES, 'UTF-8') ?>"
                       class="bg-bg border border-border rounded-md px-3 py-2 text-txt">
                <span class="text-xs text-muted">Limite indicativo per il personale (es. 5%)</span>
            </label>
        </div>
        <div class="card bg-bg/50 border-border/60 text-xs text-muted">
            Esempio: costo €100 con ricarico <?= htmlspecialchars((string)$pMarkup, ENT_QUOTES, 'UTF-8') ?>% + €<?= htmlspecialchars((string)$pFixed, ENT_QUOTES, 'UTF-8') ?> fisso →
            (100 × <?= number_format(1 + $pMarkup/100, 2) ?> + <?= htmlspecialchars((string)$pFixed, ENT_QUOTES, 'UTF-8') ?>) × <?= number_format(1 + $pVat/100, 2) ?> =
            <strong class="text-acc">€<?= number_format((100 * (1 + $pMarkup/100) + $pFixed) * (1 + $pVat/100), 2) ?></strong> circa
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="bg-pri text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-red-500/80 transition">Salva ricarico</button>
        </div>
    </form>
</section>
