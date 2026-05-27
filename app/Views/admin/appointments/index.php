<?php
use App\Core\View;

View::renderPartial('layouts/admin', [
    'title' => $title,
    'contentTemplate' => 'admin/appointments/index-content',
    'contentData' => compact('appointments', 'calendarReady', 'notice', 'error', 'csrfToken'),
]);
