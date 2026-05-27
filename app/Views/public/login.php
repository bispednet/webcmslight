<?php
use App\Core\View;

/** @var string|null $notice */
/** @var string $projectId */
/** @var string $rpcUrl */
/** @var string|null $error */
/** @var bool $googleConfigured */
/** @var string $csrfToken */

View::renderPartial('layouts/main', [
    'title' => 'Reserved Area',
    'contentTemplate' => 'public/login-content',
    'contentData' => [
        'notice' => $notice ?? null,
        'error' => $error ?? null,
        'projectId' => $projectId ?? '',
        'rpcUrl' => $rpcUrl ?? '',
        'googleConfigured' => $googleConfigured ?? false,
        'csrfToken' => $csrfToken ?? '',
    ],
]);
