<?php
/**
 * Download product images from FTP and update DB.
 * Read-only access to FTP produzione.
 */

$dbHost = '127.0.0.1';
$dbPort = 3307;
$dbUser = 'bisped_user';
$dbPass = 'REDACTED_LOCAL_DB_PASSWORD';
$dbName = 'bisped_net';
$dbLegacy = 'bisped_wp_legacy';

$ftpBase = 'ftp://ftp.bisped.net/public_html/wp-content/uploads/';
$ftpUser = 'info@bisped.net';
$ftpPass = 'REDACTED_FTP_PASSWORD';
$mediaDir = __DIR__ . '/../public/media/products';

if (!is_dir($mediaDir)) {
    mkdir($mediaDir, 0755, true);
}

$pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
$pdoLegacy = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbLegacy;charset=utf8mb4", $dbUser, $dbPass);

// Get mapping: slug → image filename from legacy DB
$mapping = $pdoLegacy->query("
    SELECT p.post_name as slug, pa.meta_value as attached_file
    FROM wpb_posts p
    JOIN wpb_postmeta pm ON pm.post_id = p.ID AND pm.meta_key = '_thumbnail_id'
    JOIN wpb_postmeta pa ON pa.post_id = pm.meta_value AND pa.meta_key = '_wp_attached_file'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
")->fetchAll(PDO::FETCH_KEY_PAIR);

echo "Found " . count($mapping) . " image mappings in legacy DB\n";

$products = $pdo->query("SELECT id, slug FROM products")->fetchAll(PDO::FETCH_ASSOC);
$updated = 0;
$skipped = 0;
$downloaded = 0;
$failed = 0;

$stmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?");

foreach ($products as $product) {
    $slug = $product['slug'];
    $pid = $product['id'];

    if (!isset($mapping[$slug])) {
        $skipped++;
        continue;
    }

    $filename = $mapping[$slug]; // e.g. "SKILLER-SGH2-000-43308.png"
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $localName = $slug . '.' . $ext;
    $localPath = $mediaDir . '/' . $localName;
    $imageUrl = '/media/products/' . $localName;

    // Skip if already downloaded
    if (file_exists($localPath) && filesize($localPath) > 5000) {
        $stmt->execute([$imageUrl, $pid]);
        $updated++;
        continue;
    }

    // Try 600x600 first, then 460x460, then 300x300, then original
    $candidates = [
        $base . '-600x600.' . $ext,
        $base . '-460x460.' . $ext,
        $base . '-300x300.' . $ext,
        $filename,
    ];

    $downloaded_ok = false;
    foreach ($candidates as $candidate) {
        $ftpUrl = $ftpBase . rawurlencode($candidate);
        $cmd = sprintf(
            'curl -sf --ftp-pasv --connect-timeout 10 --max-time 30 --user %s %s -o %s 2>/dev/null',
            escapeshellarg("$ftpUser:$ftpPass"),
            escapeshellarg($ftpUrl),
            escapeshellarg($localPath)
        );
        exec($cmd, $out, $ret);
        if ($ret === 0 && file_exists($localPath) && filesize($localPath) > 1000) {
            $downloaded_ok = true;
            $downloaded++;
            echo "  OK  $slug → $candidate\n";
            break;
        }
        if (file_exists($localPath)) {
            unlink($localPath);
        }
    }

    if ($downloaded_ok) {
        $stmt->execute([$imageUrl, $pid]);
        $updated++;
    } else {
        echo "  FAIL $slug → not found on FTP\n";
        $failed++;
    }
}

echo "\nDone: updated=$updated downloaded=$downloaded skipped=$skipped failed=$failed\n";
