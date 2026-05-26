<?php
use App\Core\View;

/** @var array $settings */
/** @var array $products */

View::renderPartial('layouts/main', [
    'title' => 'Servizi',
    'contentTemplate' => 'public/servizi-content',
    'contentData' => compact('settings', 'products'),
]);
