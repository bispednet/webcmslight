<?php
use App\Core\View;

/** @var array $product */
/** @var array|null $pcConfigurator */

$productName = (string)($product['name'] ?? 'Prodotto');
$category = (string)($product['category'] ?? 'catalogo');
$metaDescription = mb_substr(
    $productName . ' a Piombino: prezzo, disponibilita, consulenza e assistenza da bisp&d. Categoria ' . $category . '.',
    0,
    155
);

View::renderPartial('layouts/main', [
    'title' => $productName,
    'metaDescription' => $metaDescription,
    'seoSocialTitle' => $productName . ' | bisp&d',
    'seoSocialDescription' => $metaDescription,
    'contentTemplate' => 'public/product-content',
    'contentData' => compact('product', 'pcConfigurator'),
]);
