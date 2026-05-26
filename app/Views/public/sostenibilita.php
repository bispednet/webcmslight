<?php
use App\Core\View;

/** @var array $settings */

View::renderPartial('layouts/main', [
    'title' => 'Sostenibilita',
    'contentTemplate' => 'public/sostenibilita-content',
    'contentData' => compact('settings'),
]);
