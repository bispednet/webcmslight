<?php
/** @var string $name */
/** @var string $email */
/** @var string $role */

$title = 'Area clienti';
$contentTemplate = 'public/customer-area-content';
$contentData = [
    'name' => $name,
    'email' => $email,
    'role' => $role,
];

require dirname(__DIR__) . '/layouts/main.php';
