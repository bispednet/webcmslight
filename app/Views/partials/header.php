<?php
use App\Core\View;
use App\Support\AdminMode;

$settings = $settings ?? [];
$siteName = $settings['site_name'] ?? 'Bisped';
$siteLogo = $siteLogo ?? '';
$navigation = $navigation ?? [];

$navMap = [];
foreach ($navigation as $group) {
    if (!isset($group['group_key'])) {
        continue;
    }
    $navMap[$group['group_key']] = $group['items'] ?? [];
}

$defaultPrimary = [
    ['label' => 'Home', 'url' => '/', 'is_external' => false],
    ['label' => 'Shop', 'url' => '/products', 'is_external' => false],
    ['label' => 'Gaming', 'url' => '/products#gaming', 'is_external' => false],
    ['label' => 'Servizi', 'url' => '/servizi', 'is_external' => false],
    ['label' => 'Assistenza', 'url' => '/servizi#assistenza', 'is_external' => false],
];

$defaultMore = [
    ['label' => 'Azienda', 'url' => '/azienda', 'is_external' => false],
    ['label' => 'Sostenibilita', 'url' => '/sostenibilita', 'is_external' => false],
    ['label' => 'FAQ', 'url' => '/faq', 'is_external' => false],
    ['label' => 'Area riservata', 'url' => '/login', 'is_external' => false],
];

$defaultCta = ['label' => 'Contatti', 'url' => '/contatti', 'is_external' => false];

$primaryItems = $navMap['header_primary'] ?? $defaultPrimary;
if (empty($primaryItems)) {
    $primaryItems = $defaultPrimary;
}

$moreItems = $navMap['header_more'] ?? $defaultMore;
$ctaItems = $navMap['header_cta'] ?? [$defaultCta];
$ctaItem = $ctaItems[0] ?? $defaultCta;

$mobileItems = array_merge($primaryItems, $moreItems);

$linkAttributes = static function (array $item): string {
    $attrs = '';
    $url = $item['url'] ?? '#';
    $attrs .= ' href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"';
    if (!empty($item['is_external'])) {
        $attrs .= ' target="_blank" rel="noopener"';
    }
    return $attrs;
};
?>
<header class="site-header fixed top-0 left-0 right-0 z-50 bg-bg/80 backdrop-blur-lg border-b border-stroke transition-all">
    <div class="container mx-auto max-w-6xl px-4">
        <div class="flex h-20 items-center justify-between">
            <a href="/" class="flex items-center gap-2 text-xl font-bold text-acc">
                <?php if ($siteLogo): ?>
                    <img src="<?= htmlspecialchars($siteLogo, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?>" class="h-9 w-auto"<?= AdminMode::dataAttrs('settings', 'site_logo', null, 'image'); ?>>
                <?php else: ?>
                    <span class="inline-flex h-9 w-9 items-center justify-center text-pri" data-alt="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?>"<?= AdminMode::dataAttrs('settings', 'site_logo', null, 'image'); ?>>
                        <?php View::renderPartial('partials/logo', ['class' => 'h-8 w-8 text-pri']); ?>
                    </span>
                <?php endif; ?>
                <span<?= AdminMode::dataAttrs('settings', 'site_name'); ?><?= AdminMode::isAdmin() ? ' class="admin-editable-text"' : ''; ?>>
                    <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </a>
            <nav class="hidden md:flex items-center gap-6 text-sm font-semibold">
                <?php foreach ($primaryItems as $item): ?>
                    <a<?= $linkAttributes($item); ?> class="text-txt hover:text-pri transition-colors">
                        <?= htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
                <?php if (!empty($moreItems)): ?>
                    <div class="relative" data-dropdown>
                        <button type="button" data-dropdown-toggle class="flex items-center gap-1 text-txt hover:text-pri transition-colors">
                            Altro
                            <?= icon_svg('chevron-down', 'h-4 w-4'); ?>
                        </button>
                        <div class="hidden absolute right-0 mt-3 w-52 rounded-lg border border-stroke bg-bg2 shadow-deep" data-dropdown-panel>
                            <?php foreach ($moreItems as $item): ?>
                                <a<?= $linkAttributes($item); ?> class="block px-4 py-2 text-sm text-txt hover:bg-stroke hover:text-pri transition">
                                    <?= htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </nav>
            <div class="hidden md:flex items-center gap-4">
                <button type="button" data-theme-toggle class="rounded-full border border-stroke bg-glass px-3 py-2 text-xs font-black uppercase tracking-widest text-txt transition hover:border-pri hover:text-pri">
                    <span class="theme-toggle-label"></span>
                </button>
                <a<?= $linkAttributes($ctaItem); ?> class="bg-pri text-white font-black py-2 px-4 rounded-full hover:bg-pri-700 transition-transform ease-in-out duration-200 hover:-translate-y-0.5">
                    <?= htmlspecialchars($ctaItem['label'] ?? $defaultCta['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
            <div class="md:hidden">
                <button type="button" class="text-txt" data-toggle-mobile-nav>
                    <?= icon_svg('menu', 'h-6 w-6'); ?>
                </button>
            </div>
        </div>
    </div>
    <div class="md:hidden hidden border-t border-stroke bg-bg2" data-mobile-nav>
        <nav class="flex flex-col gap-4 px-4 py-6 text-sm font-semibold text-center">
            <?php foreach ($mobileItems as $item): ?>
                <a<?= $linkAttributes($item); ?> class="text-txt hover:text-pri transition-colors">
                    <?= htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
            <button type="button" data-theme-toggle class="mx-auto rounded-full border border-stroke px-5 py-2 text-xs font-black uppercase tracking-widest text-txt">
                <span class="theme-toggle-label"></span>
            </button>
            <a<?= $linkAttributes($ctaItem); ?> class="mt-4 bg-pri text-white font-black py-3 rounded-full hover:bg-pri-700 transition-colors">
                <?= htmlspecialchars($ctaItem['label'] ?? $defaultCta['label'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </nav>
    </div>
</header>
