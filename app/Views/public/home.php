<?php
use App\Core\View;

/** @var array $settings */
/** @var array $products */

View::renderPartial('layouts/main', [
    'title' => 'Home',
    'contentTemplate' => 'public/home-content',
    'contentData' => compact('settings', 'products'),
]);
