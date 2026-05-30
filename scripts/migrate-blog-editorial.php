<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$statements = [
    'ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS title_en VARCHAR(200) NOT NULL DEFAULT "" AFTER title',
    'ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS snippet_en TEXT NULL AFTER snippet',
    'ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS content_html_en LONGTEXT NULL AFTER content_html',
    'ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS related_product_tags VARCHAR(255) NULL AFTER is_published',
    'ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS source_url VARCHAR(500) NULL AFTER related_product_tags',
    'ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS source_fingerprint CHAR(64) NULL AFTER source_url',
    'ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS auto_generated TINYINT(1) NOT NULL DEFAULT 0 AFTER source_fingerprint',
];

foreach ($statements as $statement) {
    $pdo->exec($statement);
}

$indexes = [];
foreach ($pdo->query('SHOW INDEX FROM blog_posts') as $index) {
    $indexes[(string)$index['Key_name']] = true;
}
if (empty($indexes['idx_blog_source_url'])) {
    $pdo->exec('ALTER TABLE blog_posts ADD INDEX idx_blog_source_url (source_url(191))');
}
if (empty($indexes['idx_blog_source_fingerprint'])) {
    $pdo->exec('ALTER TABLE blog_posts ADD INDEX idx_blog_source_fingerprint (source_fingerprint)');
}

echo "Blog editorial migration complete.\n";
