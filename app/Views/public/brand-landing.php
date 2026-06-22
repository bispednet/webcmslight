<?php
use App\Core\View;

/** @var array $landing */

$title = $landing['label'] . ' a Piombino';

View::renderPartial('layouts/main', [
    'title' => $title,
    'metaDescription' => $landing['meta_description'] ?? '',
    'seoSocialTitle' => $title . ' | bisp&d',
    'seoSocialDescription' => $landing['meta_description'] ?? '',
    'contentTemplate' => 'public/brand-landing-content',
    'contentData' => compact('landing'),
]);
