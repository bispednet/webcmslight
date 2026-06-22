<?php
/** @var string $title */
/** @var string $contentTemplate */
/** @var array $contentData */

use App\Core\View;
use App\Support\AdminMode;
use App\Support\Media;
use App\Services\Cms\ContentRepository;
use App\Services\Security\Csrf;
use App\Core\Container;

$contentRepository = new ContentRepository();
$layoutSettings = $contentRepository->getSettings();
$headerNavigation = $contentRepository->getNavigation('header');
$footerNavigation = $contentRepository->getNavigation('footer');

$config = Container::get('config', []);
$baseUrl = rtrim((string)($config['app']['url'] ?? ''), '/');

$siteName = $layoutSettings['site_name'] ?? 'Bisped';
$seoBaseTitle = $layoutSettings['seo_meta_title'] ?? $siteName;
$pageTitle = isset($title) && $title !== '' ? $title . ' | ' . $seoBaseTitle : $seoBaseTitle;
$metaDescription = $layoutSettings['seo_meta_description'] ?? ($layoutSettings['site_tagline'] ?? '');
$seoSocialTitle = $layoutSettings['seo_social_title'] ?? $seoBaseTitle;
$seoSocialDescription = $layoutSettings['seo_social_description'] ?? $metaDescription;
$seoTwitterDescription = $layoutSettings['seo_twitter_description'] ?? $seoSocialDescription;
$seoTelegramDescription = $layoutSettings['seo_telegram_description'] ?? $seoSocialDescription;
$seoDiscordDescription = $layoutSettings['seo_discord_description'] ?? $seoSocialDescription;

$assetUrl = static function (string $path) use ($baseUrl): string {
    if ($path === '') {
        return '';
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    return ($baseUrl ? $baseUrl : '') . '/' . ltrim($path, '/');
};

$publicPath = static function (string $path): string {
    if ($path === '') {
        return '';
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    return '/' . ltrim($path, '/');
};

$shareImage = $layoutSettings['seo_share_image'] ?? $layoutSettings['og_image'] ?? '';
if ($shareImage === '') {
    $shareImage = Media::assetSvg('products/product1.svg');
}
$shareImageUrl = $shareImage ? $assetUrl($shareImage) : '';
$faviconPath = $publicPath($layoutSettings['favicon_path'] ?? '/favicon.ico');
$currentUrl = $assetUrl($_SERVER['REQUEST_URI'] ?? '/');
$siteLogoPath = Media::siteLogoUrl($layoutSettings['site_logo'] ?? '');

$isAdmin = AdminMode::isAdmin();
$adminModeEnabled = AdminMode::isEnabled();
$adminCsrf = $isAdmin ? Csrf::token() : null;
$isEnglish = str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/en');
$bodyClass = 'min-h-screen bg-bg text-txt font-sans relative';
if ($isAdmin) {
    $bodyClass .= ' admin-toolbar-present';
}

// ── SEO: canonical + hreflang (bilingue) ────────────────────────────────────
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$canonicalUrl = $assetUrl($reqPath); // senza query string
$pathIt = preg_replace('#^/en(/|$)#', '/', $reqPath) ?: '/';
$pathEn = str_starts_with($reqPath, '/en') ? $reqPath : ('/en' . ($reqPath === '/' ? '' : $reqPath));
$hrefIt = $assetUrl($pathIt);
$hrefEn = $assetUrl($pathEn);

// ── JSON-LD: negozio locale (solo home, SEO locale) ─────────────────────────
$company = $config['company'] ?? [];
$jsonLd = null;
if ($reqPath === '/' || $reqPath === '/en' || $reqPath === '/en/') {
    $jsonLd = json_encode([
        '@context'    => 'https://schema.org',
        '@type'       => 'ElectronicsStore',
        'name'        => $company['legal_name'] ?? 'Bisped',
        'image'       => $shareImageUrl,
        'url'         => $assetUrl('/'),
        'telephone'   => '+39 0565 31136',
        'address'     => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => 'Piazza della Costituzione, 68',
            'addressLocality' => 'Piombino',
            'addressRegion'   => 'LI',
            'postalCode'      => '57025',
            'addressCountry'  => 'IT',
        ],
        'vatID'       => $company['vat_id'] ?? null,
        'priceRange'  => '€€',
        'areaServed'  => 'Piombino e Val di Cornia',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
?>
<!DOCTYPE html>
<html lang="<?= $isEnglish ? 'en' : 'it' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        (function(){
            var t=localStorage.getItem('bisped-theme');
            if(!t) t=window.matchMedia&&window.matchMedia('(prefers-color-scheme:light)').matches?'light':'dark';
            document.documentElement.setAttribute('data-theme',t);
        })();
    </script>
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <?php if ($metaDescription !== ''): ?>
        <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($seoSocialTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($seoSocialDescription !== ''): ?>
        <meta property="og:description" content="<?= htmlspecialchars($seoSocialDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <meta property="og:url" content="<?= htmlspecialchars($currentUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($shareImageUrl !== ''): ?>
        <meta property="og:image" content="<?= htmlspecialchars($shareImageUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($seoSocialTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($seoTwitterDescription !== ''): ?>
        <meta name="twitter:description" content="<?= htmlspecialchars($seoTwitterDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if ($shareImageUrl !== ''): ?>
        <meta name="twitter:image" content="<?= htmlspecialchars($shareImageUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if ($seoTelegramDescription !== ''): ?>
        <meta name="telegram:description" content="<?= htmlspecialchars($seoTelegramDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if ($seoDiscordDescription !== ''): ?>
        <meta name="discord:description" content="<?= htmlspecialchars($seoDiscordDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="alternate" hreflang="it" href="<?= htmlspecialchars($hrefIt, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="alternate" hreflang="en" href="<?= htmlspecialchars($hrefEn, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($hrefIt, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:locale" content="<?= $isEnglish ? 'en_US' : 'it_IT'; ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($jsonLd): ?>
        <script type="application/ld+json"><?= $jsonLd ?></script>
    <?php endif; ?>
    <link rel="icon" href="<?= htmlspecialchars($faviconPath, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($faviconPath, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700;800;900&family=Montserrat:wght@600;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Barlow', 'sans-serif'],
                        display: ['Montserrat', 'Barlow', 'sans-serif'],
                    },
                    colors: {
                        bg:      'var(--c-bg)',
                        bg2:     'var(--c-bg2)',
                        surface: 'var(--c-surface)',
                        txt:     'var(--c-txt)',
                        muted:   'var(--c-muted)',
                        acc:     'var(--c-acc)',
                        pri:     'var(--bisped-red)',
                        border:  'var(--c-border)',
                    },
                }
            }
        };
    </script>
    <link rel="stylesheet" href="/assets/css/app.css?v=20260622-pc-config-map">
    <link rel="stylesheet" href="/assets/css/ai-concierge.css?v=20260604-mobile-fix">
    <script type="module" src="/assets/js/animate.js" defer></script>
    <?php if ($isAdmin && $adminCsrf): ?>
        <meta name="csrf-token" content="<?= htmlspecialchars($adminCsrf, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if ($isAdmin): ?>
        <link rel="stylesheet" href="/assets/admin/admin.css">
    <?php endif; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="site-atmosphere absolute inset-0 -z-10 h-full w-full"></div>
    <?php if ($isAdmin): ?>
        <?php View::renderPartial('partials/admin-toolbar', [
            'enabled' => $adminModeEnabled,
            'logoutCsrf' => $adminCsrf,
        ]); ?>
    <?php endif; ?>
    <?php View::renderPartial('partials/header', [
        'settings' => $layoutSettings,
        'siteLogo' => $siteLogoPath,
        'navigation' => $headerNavigation,
    ]); ?>
    <main class="container mx-auto max-w-7xl px-4 lg:px-6 pt-20 pb-16 space-y-16">
        <?php View::renderPartial($contentTemplate, ($contentData ?? []) + ['isEnglish' => $isEnglish]); ?>
    </main>
    <?php View::renderPartial('partials/footer', [
        'siteLogo' => $siteLogoPath,
        'navigation' => $footerNavigation,
    ]); ?>

    <?php View::renderPartial('partials/ai-concierge-widget'); ?>
    <script src="/assets/js/ai-concierge.js?v=20260604-mobile-fix" defer></script>

    <!-- Back nav: trasforma il primo eyebrow di pagina in una freccia "indietro" cliccabile -->
    <script>
    (function(){
        var p = location.pathname;
        if (p === '/' || p === '/en' || p === '/en/') return;
        document.addEventListener('DOMContentLoaded', function(){
            var label = document.querySelector('main .section-label');
            if (!label || label.querySelector('.section-label__back')) return;
            label.classList.add('section-label--back');
            var ref = document.referrer;
            var sameOrigin = ref && ref.indexOf(location.origin) === 0 && ref !== location.href;
            var a = document.createElement('a');
            a.className = 'section-label__back';
            a.href = sameOrigin ? ref : '/';
            a.setAttribute('aria-label', 'Torna alla pagina precedente');
            a.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 15.75 3 12m0 0 3.75-3.75M3 12h18"/></svg>';
            a.addEventListener('click', function(e){
                if (window.history.length > 1) { e.preventDefault(); window.history.back(); }
            });
            label.insertBefore(a, label.firstChild);
        });
    })();
    </script>

    <!-- Cookie Banner -->
    <div id="cookie-banner" class="cookie-banner" role="dialog" aria-label="Cookie policy">
        <p class="text-sm leading-6" style="color:var(--c-muted)">Usiamo cookie tecnici necessari al funzionamento. Nessun tracciamento marketing senza il tuo consenso. <a href="/legal" class="font-bold" style="color:var(--bisped-red)">Cookie policy</a></p>
        <div class="flex gap-3 flex-shrink-0">
            <button onclick="bisped_cookieAccept()" class="btn-primary btn-sm">Accetta</button>
            <button onclick="bisped_cookieReject()" class="btn-outline btn-sm">Solo necessari</button>
        </div>
    </div>
    <script>
    (function(){if(localStorage.getItem('bisped-cookie-consent')){document.getElementById('cookie-banner').style.display='none';}})();
    function bisped_cookieAccept(){localStorage.setItem('bisped-cookie-consent','all');document.getElementById('cookie-banner').style.display='none';}
    function bisped_cookieReject(){localStorage.setItem('bisped-cookie-consent','essential');document.getElementById('cookie-banner').style.display='none';}
    </script>

    <?php if ($isAdmin): ?>
        <script>
            window.ADMIN_CONTEXT = {
                enabled: <?= $adminModeEnabled ? 'true' : 'false'; ?>,
                csrf: <?= json_encode($adminCsrf, JSON_THROW_ON_ERROR); ?>,
                endpoints: {
                    toggle: '/admin/api/toggle-mode',
                    update: '/admin/api/update-field',
                    upload: '/admin/api/upload-image'
                }
            };
        </script>
        <script src="/assets/js/admin.js?v=legacy-safe" defer></script>
    <?php endif; ?>
</body>
</html>
