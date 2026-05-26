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
<html lang="it" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <script>
        (function () {
            const saved = localStorage.getItem('bisped-theme');
            const prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
            document.documentElement.dataset.theme = saved || (prefersLight ? 'light' : 'dark');
        })();
    </script>
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
                        bg: 'rgb(var(--color-bg) / <alpha-value>)',
                        bg2: 'rgb(var(--color-bg2) / <alpha-value>)',
                        txt: 'rgb(var(--color-txt) / <alpha-value>)',
                        muted: 'rgb(var(--color-muted) / <alpha-value>)',
                        stroke: 'rgb(var(--color-stroke) / var(--alpha-stroke))',
                        glass: 'rgb(var(--color-glass) / var(--alpha-glass))',
                        pri: 'rgb(var(--color-pri) / <alpha-value>)',
                        'pri-700': 'rgb(var(--color-pri-700) / <alpha-value>)',
                        acc: 'rgb(var(--color-acc) / <alpha-value>)',
                        cy: 'rgb(var(--color-cy) / <alpha-value>)',
                        yl: 'rgb(var(--color-yl) / <alpha-value>)',
                    },
                    boxShadow: {
                        deep: '0 8px 28px rgba(0,0,0,.35)',
                    }
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
    <main class="container mx-auto max-w-6xl px-4 py-8 pt-28 space-y-16">
        <?php View::renderPartial($contentTemplate, $contentData ?? []); ?>
    </main>
    <?php View::renderPartial('partials/footer', [
        'siteLogo' => $siteLogoPath,
        'navigation' => $footerNavigation,
    ]); ?>
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
