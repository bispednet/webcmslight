<?php
use App\Core\View;

/** @var string $title */
/** @var string $csrfToken */
/** @var string|null $success */
/** @var string|null $error */

View::renderPartial('layouts/main', [
    'title' => $title,
    'contentTemplate' => 'public/appointments-content',
    'contentData' => compact('csrfToken', 'success', 'error'),
]);
