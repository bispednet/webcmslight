<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

/**
 * Undo one or more accidental UTF-8-as-Windows-1252 conversions.
 */
function repairMojibake(string $value): string
{
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $before = mojibakeScore($value);
        if ($before === 0) {
            break;
        }

        // The output bytes are valid UTF-8 again after reversing the
        // accidental UTF-8 -> Windows-1252 interpretation.
        $candidate = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
        if (mojibakeScore($candidate) >= $before) {
            break;
        }

        $value = $candidate;
    }

    return $value;
}

function mojibakeScore(string $value): int
{
    preg_match_all('/[ÃÂâð]/u', $value, $matches);

    return count($matches[0]);
}

$db = Database::connection();
$columns = ['title', 'title_en', 'snippet', 'snippet_en', 'content_html', 'content_html_en'];
$rows = $db->query('SELECT id, ' . implode(', ', $columns) . ' FROM blog_posts')->fetchAll() ?: [];
$update = $db->prepare(
    'UPDATE blog_posts SET ' . implode(', ', array_map(static fn (string $column): string => "{$column} = :{$column}", $columns))
    . ' WHERE id = :id'
);

$updated = 0;
foreach ($rows as $row) {
    $params = ['id' => (int)$row['id']];
    $changed = false;
    foreach ($columns as $column) {
        $original = (string)($row[$column] ?? '');
        $params[$column] = repairMojibake($original);
        $changed = $changed || $params[$column] !== $original;
    }

    if ($changed) {
        $update->execute($params);
        $updated++;
    }
}

fwrite(STDOUT, sprintf("Repaired %d blog post(s).\n", $updated));
