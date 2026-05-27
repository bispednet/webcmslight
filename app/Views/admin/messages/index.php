<?php
use App\Core\View;

/** @var string $title */
/** @var array $messages */
/** @var string|null $notice */
/** @var string|null $error */
/** @var string $csrfToken */

View::renderPartial('layouts/admin', [
    'title' => $title,
    'contentTemplate' => 'admin/messages/index-content',
    'contentData' => [
        'messages' => $messages,
        'notice' => $notice,
        'error' => $error,
        'csrfToken' => $csrfToken,
    ],
]);
