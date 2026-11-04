<?php
declare(strict_types=1);

// Manufacturer-verified corrections for incomplete or mistyped supplier fields.
// Matching SKU and title are both required; no electrical property is guessed.
return [
    'PRO H610M-E' => [
        'title_pattern' => '/MSI.*PRO H610M-E.*DDR5/i',
        'metadata' => ['memory_slots' => 2],
        'source' => 'https://storage-asset.msi.com/datasheet/mb/global/PRO-H610M-E.pdf',
    ],
    'PRO A620AM-B EVO' => [
        'title_pattern' => '/MSI.*A620AM-B EVO/i',
        'specs' => ['form_factor' => 'Micro-ATX'],
        'metadata' => ['memory_slots' => 2, 'memory_max_gb' => 128],
        'source' => 'https://us-store.msi.com/Motherboards/PRO-A620AM-B-EVO',
    ],
];
