<?php
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();
$imgDir = dirname(__DIR__) . '/public/media/products';

$products = $pdo->query("SELECT id, slug, image_url FROM products ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
$skipped = 0;
$missing = 0;

foreach ($products as $p) {
    $slug = $p['slug'];
    $jpg  = $imgDir . '/' . $slug . '.jpg';
    $png  = $imgDir . '/' . $slug . '.png';

    if (file_exists($jpg) && filesize($jpg) > 4000) {
        $url = '/media/products/' . $slug . '.jpg';
    } elseif (file_exists($png) && filesize($png) > 4000) {
        $url = '/media/products/' . $slug . '.png';
    } else {
        echo "  [MISS] $slug\n";
        $missing++;
        continue;
    }

    if ($p['image_url'] === $url) {
        echo "  [SKIP] $slug\n";
        $skipped++;
        continue;
    }

    $stmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?");
    $stmt->execute([$url, $p['id']]);
    echo "  [ OK ] $slug → $url\n";
    $updated++;
}

echo "\nDone: $updated updated, $skipped skipped, $missing missing\n";
