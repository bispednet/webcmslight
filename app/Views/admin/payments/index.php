<?php
use App\Core\View;

/** @var string $title */
/** @var array $orders */
/** @var string|null $error */

View::renderPartial('layouts/admin', [
    'title' => $title,
    'contentTemplate' => 'admin/payments/index-content',
    'contentData' => [
        'orders' => $orders,
        'error' => $error,
    ],
]);
