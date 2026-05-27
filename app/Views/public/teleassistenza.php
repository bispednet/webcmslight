<?php
use App\Core\View;

View::renderPartial('layouts/main', [
    'title'           => 'Teleassistenza remota — bisp&d Piombino',
    'contentTemplate' => 'public/teleassistenza-content',
    'contentData'     => ['settings' => $settings ?? []],
]);
