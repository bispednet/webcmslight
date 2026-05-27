<?php
use App\Core\View;

View::renderPartial('layouts/admin', [
    'title'           => 'Auto-Update Catalogo',
    'contentTemplate' => 'admin/ingest-content',
    'contentData'     => [
        'log'     => $log     ?? [],
        'sources' => $sources ?? [],
    ],
]);
