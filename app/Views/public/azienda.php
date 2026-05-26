<?php
use App\Core\View;

/** @var array $settings */

View::renderPartial('layouts/main', [
    'title' => 'Azienda',
    'contentTemplate' => 'public/azienda-content',
    'contentData' => compact('settings'),
]);
