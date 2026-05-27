<?php
use App\Core\View;

/** @var array $settings */

View::renderPartial('layouts/main', [
    'title' => 'Dove siamo',
    'contentTemplate' => 'public/dove-content',
    'contentData' => compact('settings'),
]);
