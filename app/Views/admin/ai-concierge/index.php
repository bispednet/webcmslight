<?php
use App\Core\View;
View::renderPartial('layouts/admin', [
    'title' => $title,
    'contentTemplate' => 'admin/ai-concierge/index-content',
    'contentData' => compact('stats', 'conversations'),
]);
