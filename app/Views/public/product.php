<?php
use App\Core\View;

/** @var array $product */
/** @var array|null $pcConfigurator */

View::renderPartial('layouts/main', [
    'title' => $product['name'] ?? 'Product',
    'contentTemplate' => 'public/product-content',
    'contentData' => compact('product', 'pcConfigurator'),
]);
