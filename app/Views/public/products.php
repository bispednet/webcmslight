<?php
use App\Core\View;

/** @var array $counts */
/** @var array $subcats */

View::renderPartial('layouts/main', [
    'title' => 'Catalogo',
    'contentTemplate' => 'public/products-content',
    'contentData' => compact('counts', 'subcats'),
]);
