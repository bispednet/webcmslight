<?php
use App\Core\View;
use App\Support\AdminMode;
use App\Support\Session;

Session::ensureStarted();

$settings   = $settings   ?? [];
$siteName   = $settings['site_name'] ?? 'Bisped';
$siteLogo   = $siteLogo   ?? '';
$navigation = $navigation ?? [];

$navMap = [];
foreach ($navigation as $group) {
    if (!isset($group['group_key'])) continue;
    $navMap[$group['group_key']] = $group['items'] ?? [];
}

$defaultPrimary = [
    ['label' => 'Home',        'url' => '/',               'is_external' => false],
    ['label' => 'Shop',        'url' => '/products',       'is_external' => false],
    ['label' => 'Servizi',     'url' => '/servizi',        'is_external' => false],
    ['label' => 'Chi siamo',   'url' => '/azienda',        'is_external' => false],
    ['label' => 'Dove siamo',  'url' => '/dove',           'is_external' => false],
    ['label' => 'Blog',        'url' => '/blog',           'is_external' => false],
];

$defaultMore = [
    ['label' => 'Teleassistenza','url' => '/teleassistenza', 'is_external' => false],
    ['label' => 'FAQ',           'url' => '/faq',            'is_external' => false],
    ['label' => 'Contatti',      'url' => '/contatti',       'is_external' => false],
    ['label' => 'Sostenibilità', 'url' => '/sostenibilita',  'is_external' => false],
];

$primaryItems = $navMap['header_primary'] ?? $defaultPrimary;
if (empty($primaryItems)) $primaryItems = $defaultPrimary;

$moreItems  = $navMap['header_more'] ?? $defaultMore;
if (empty($moreItems)) $moreItems = $defaultMore;

$mobileItems = array_merge($primaryItems, $moreItems);

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$userName  = $_SESSION['user_name'] ?? null;
$userRole  = $_SESSION['user_role'] ?? null;
$isEnglish = str_starts_with((string)$currentPath, '/en');
$accountUrl = ($userRole === 'admin' || $userRole === 'commesso') ? '/admin/dashboard' : ($isEnglish ? '/en/customer-area' : '/area-clienti');
$loginUrl = $isEnglish ? '/en/login' : '/login';
$accountLabel = $userName
    ? ($userRole === 'admin' ? '⚙ Admin' : ($userRole === 'commesso' ? 'Banco' : 'Area clienti'))
    : 'Accedi';

$isActive = static fn(string $url): bool =>
    $url !== '/' ? str_starts_with($currentPath, $url) : $currentPath === '/';

$localized = static function (string $locale) use ($currentPath): string {
    $toEn = [
        '/' => '/en/',
        '/it' => '/en/',
        '/it/' => '/en/',
        '/azienda' => '/en/company',
        '/chi-siamo' => '/en/company',
        '/servizi' => '/en/services',
        '/products' => '/en/products',
        '/prodotti' => '/en/products',
        '/blog' => '/en/blog',
        '/contatti' => '/en/contact',
        '/contact' => '/en/contact',
        '/appuntamenti' => '/en/appointments',
        '/appointments' => '/en/appointments',
        '/dove' => '/en/find-us',
        '/dove-siamo' => '/en/find-us',
        '/sostenibilita' => '/en/sustainability',
        '/legal' => '/en/legal',
        '/faq' => '/en/faq',
        '/teleassistenza' => '/en/remote-support',
        '/area-clienti' => '/en/customer-area',
        '/register' => '/en/register',
        '/login' => '/en/login',
    ];
    $toIt = [
        '/en' => '/',
        '/en/' => '/',
        '/en/company' => '/azienda',
        '/en/services' => '/servizi',
        '/en/products' => '/products',
        '/en/blog' => '/blog',
        '/en/contact' => '/contatti',
        '/en/appointments' => '/appuntamenti',
        '/en/find-us' => '/dove',
        '/en/sustainability' => '/sostenibilita',
        '/en/legal' => '/legal',
        '/en/faq' => '/faq',
        '/en/remote-support' => '/teleassistenza',
        '/en/customer-area' => '/area-clienti',
        '/en/register' => '/register',
        '/en/login' => '/login',
    ];

    if ($locale === 'en') {
        uksort($toEn, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
        foreach ($toEn as $it => $en) {
            if ($currentPath === $it || str_starts_with($currentPath, $it . '/')) {
                return $en . substr($currentPath, strlen($it));
            }
        }
        return '/en/';
    }

    uksort($toIt, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
    foreach ($toIt as $en => $it) {
        if ($currentPath === $en || str_starts_with($currentPath, $en . '/')) {
            return $it . substr($currentPath, strlen($en));
        }
    }
    return $currentPath ?: '/';
};
$italianUrl = $localized('it');
$italianUrl .= str_contains($italianUrl, '?') ? '&lang=it' : '?lang=it';

$linkAttrs = static function (array $item): string {
    $url = $item['url'] ?? '#';
    $ext = !empty($item['is_external']);
    $a = ' href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"';
    if ($ext) $a .= ' target="_blank" rel="noopener"';
    return $a;
};
?>

<header class="site-header fixed top-0 left-0 right-0 z-50 transition-all">
    <div class="container mx-auto max-w-7xl px-4 lg:px-6">
        <div class="flex h-16 items-center justify-between gap-4">

            <!-- Logo -->
            <a href="/" class="flex items-center flex-shrink-0"
               <?= AdminMode::dataAttrs('settings', 'site_logo', null, 'image') ?>>
                <?php if ($siteLogo): ?>
                    <img src="<?= htmlspecialchars($siteLogo, ENT_QUOTES, 'UTF-8') ?>"
                         alt="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>"
                         class="h-8 w-auto">
                <?php else: ?>
                    <img src="/media/bisped/bisped_logo.png"
                         alt="bisp&amp;d"
                         class="h-8 w-auto">
                <?php endif; ?>
            </a>

            <!-- Desktop Nav -->
            <nav class="hidden lg:flex items-center gap-1 text-sm font-semibold" aria-label="Navigazione principale">
                <?php foreach ($primaryItems as $item):
                    $active = $isActive($item['url'] ?? '/'); ?>
                    <a<?= $linkAttrs($item) ?>
                       class="px-3 py-2 rounded transition-colors <?= $active ? 'text-pri font-bold' : 'text-muted hover:text-txt' ?>">
                        <?= htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>

                <?php if (!empty($moreItems)): ?>
                <div class="relative" data-dropdown>
                    <button type="button" data-dropdown-toggle
                            class="flex items-center gap-1 px-3 py-2 rounded text-muted hover:text-txt transition-colors">
                        Altro
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                    <div class="hidden absolute left-0 mt-1 w-48 border rounded-md shadow-lg z-50"
                         style="background:var(--c-surface);border-color:var(--c-border)"
                         data-dropdown-panel>
                        <?php foreach ($moreItems as $item): ?>
                            <a<?= $linkAttrs($item) ?>
                               class="block px-4 py-2.5 text-sm text-muted hover:text-pri hover:bg-white/5 transition-colors">
                                <?= htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </nav>

            <!-- Desktop utilities -->
            <div class="hidden lg:flex items-center gap-2">
                <!-- Language -->
                <div class="flex gap-1">
                    <a href="<?= htmlspecialchars($italianUrl, ENT_QUOTES, 'UTF-8') ?>" class="header-util-btn <?= str_starts_with($currentPath, '/en') ? '' : 'active' ?>">IT</a>
                    <a href="<?= htmlspecialchars($localized('en'), ENT_QUOTES, 'UTF-8') ?>" class="header-util-btn <?= str_starts_with($currentPath, '/en') ? 'active' : '' ?>">EN</a>
                </div>

                <!-- Theme toggle -->
                <button type="button" data-theme-toggle class="header-util-btn" title="Cambia tema" aria-label="Cambia tema">
                    <svg id="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 hidden">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                    </svg>
                    <svg id="icon-moon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
                    </svg>
                    <span class="theme-toggle-label"></span>
                </button>

                <!-- Account -->
                <a href="<?= htmlspecialchars($userName ? $accountUrl : $loginUrl, ENT_QUOTES, 'UTF-8') ?>"
                   class="header-util-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                    <?= htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8') ?>
                </a>

                <!-- CTA -->
                <a href="/contatti" class="btn-primary btn-sm">Contattaci</a>
            </div>

            <!-- Mobile utilities -->
            <div class="lg:hidden flex items-center gap-1">
                <a href="<?= htmlspecialchars($isEnglish ? $italianUrl : $localized('en'), ENT_QUOTES, 'UTF-8') ?>"
                   class="header-util-btn px-2" aria-label="<?= $isEnglish ? 'Italiano' : 'English' ?>">
                    <?= $isEnglish ? 'IT' : 'EN' ?>
                </a>
                <button type="button" data-theme-toggle class="header-util-btn px-2" title="Cambia tema" aria-label="Cambia tema">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
                    </svg>
                </button>
                <a href="<?= htmlspecialchars($userName ? $accountUrl : $loginUrl, ENT_QUOTES, 'UTF-8') ?>"
                   class="header-util-btn px-2" aria-label="<?= htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8') ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                </a>
                <button type="button" class="text-muted hover:text-txt p-2" data-toggle-mobile-nav aria-label="Menu">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Nav -->
    <div class="lg:hidden hidden border-t" style="background:var(--c-surface);border-color:var(--c-border)" data-mobile-nav>
        <nav class="flex flex-col px-4 py-5 gap-1">
            <?php foreach ($mobileItems as $item):
                $active = $isActive($item['url'] ?? '/'); ?>
                <a<?= $linkAttrs($item) ?>
                   class="px-3 py-2.5 text-sm font-semibold rounded transition-colors <?= $active ? 'text-pri bg-red/5' : 'text-txt hover:bg-white/5' ?>">
                    <?= htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
            <div class="border-t my-3" style="border-color:var(--c-border)"></div>
            <div class="flex items-center gap-2 flex-wrap px-3 pb-2">
                <a href="<?= htmlspecialchars($italianUrl, ENT_QUOTES, 'UTF-8') ?>" class="header-util-btn <?= str_starts_with($currentPath, '/en') ? '' : 'active' ?>">IT</a>
                <a href="<?= htmlspecialchars($localized('en'), ENT_QUOTES, 'UTF-8') ?>" class="header-util-btn <?= str_starts_with($currentPath, '/en') ? 'active' : '' ?>">EN</a>
                <button type="button" data-theme-toggle class="header-util-btn">
                    <span class="theme-toggle-label"></span>
                </button>
                <a href="<?= htmlspecialchars($userName ? $accountUrl : $loginUrl, ENT_QUOTES, 'UTF-8') ?>"
                   class="header-util-btn">
                    <?= htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8') ?>
                </a>
                <a href="/contatti" class="btn-primary btn-sm">Contattaci</a>
            </div>
        </nav>
    </div>
</header>

<script>
(function() {
    var theme = localStorage.getItem('bisped-theme') ||
        (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
    document.documentElement.dataset.theme = theme;
    var sun  = document.getElementById('icon-sun');
    var moon = document.getElementById('icon-moon');
    function syncIcons() {
        var t = document.documentElement.dataset.theme;
        if (sun)  sun.classList.toggle('hidden',  t !== 'dark');
        if (moon) moon.classList.toggle('hidden', t === 'dark');
    }
    syncIcons();
    document.addEventListener('DOMContentLoaded', syncIcons);
})();
</script>
