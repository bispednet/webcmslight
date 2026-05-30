<?php
use App\Core\View;
View::renderPartial('layouts/admin', [
    'title' => $title,
    'contentTemplate' => 'admin/ai-concierge/show-content',
    'contentData' => compact('conversation', 'messages', 'quotes'),
]);
