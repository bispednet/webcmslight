<?php
use App\Core\View;

/** @var string|null $notice */
/** @var string|null $error */
/** @var bool $googleConfigured */
/** @var string $googleRedirectUri */
/** @var string $csrfToken */

View::renderPartial('layouts/main', [
    'title' => 'Registrazione',
    'contentTemplate' => 'public/register-content',
    'contentData' => [
        'notice' => $notice ?? null,
        'error' => $error ?? null,
        'googleConfigured' => $googleConfigured ?? false,
        'googleRedirectUri' => $googleRedirectUri ?? '',
        'csrfToken' => $csrfToken ?? '',
    ],
]);
