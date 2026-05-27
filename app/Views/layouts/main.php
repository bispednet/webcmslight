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
$bodyClass = 'min-h-screen bg-bg text-txt font-sans relative';
if ($isAdmin) {
    $bodyClass .= ' admin-toolbar-present';
}
?>
<!DOCTYPE html>
<html lang="it">
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
    <link rel="stylesheet" href="/assets/css/app.css">
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
        <?php View::renderPartial($contentTemplate, $contentData ?? []); ?>
    </main>
    <?php View::renderPartial('partials/footer', [
        'siteLogo' => $siteLogoPath,
        'navigation' => $footerNavigation,
    ]); ?>

    <!-- WhatsApp FAB -->
    <a href="https://wa.me/393346582116?text=Ciao+bisp%26d%2C+vorrei+informazioni"
       target="_blank" rel="noopener" class="whatsapp-fab" aria-label="Contattaci su WhatsApp">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-7 h-7">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
        </svg>
    </a>

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
