<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\Ai\GeminiClient;

$client = new GeminiClient('test-key', 'test', 'test-model', 1, 1000, 1, 0, 5);
$reflection = new ReflectionClass(GeminiClient::class);
$normalize = $reflection->getMethod('normalizeModelOutput');
$extract = $reflection->getMethod('extractVisibleText');

$cases = [
    ['html', '<think>draft</think><h2>Titolo</h2><p>Testo utile.</p>', '<h2>Titolo</h2><p>Testo utile.</p>'],
    ['html', "Reasoning: draft\nFinal: <h2>Titolo</h2><p>Testo utile.</p>", '<h2>Titolo</h2><p>Testo utile.</p>'],
    ['html', "```html\n<h2>Titolo</h2><p>Testo utile.</p>\n```", '<h2>Titolo</h2><p>Testo utile.</p>'],
    ['json', "Reasoning: draft\nFinal: ```json\n{\"reply\":\"ok\",\"ready\":true}\n```", '{"reply":"ok","ready":true}'],
];

foreach ($cases as [$mode, $input, $expected]) {
    $actual = $normalize->invoke($client, $input, $mode);
    if ($actual !== $expected) {
        fwrite(STDERR, "FAIL {$mode}: " . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$visible = $extract->invoke($client, [
    'candidates' => [[
        'content' => ['parts' => [
            ['thought' => true, 'text' => 'private scratchpad'],
            ['text' => '{"reply":"ok"}'],
        ]],
    ]],
]);
if ($visible !== '{"reply":"ok"}') {
    fwrite(STDERR, 'FAIL thought-part filtering' . PHP_EOL);
    exit(1);
}

echo "Gemini normalizer tests passed\n";
