<?php
declare(strict_types=1);

define('BISPED_SKIP_DB_BOOTSTRAP', true);

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

$openAiVisible = $extract->invoke($client, [
    'choices' => [[
        'message' => ['content' => '{"reply":"gemrouter ok"}'],
    ]],
]);
if ($openAiVisible !== '{"reply":"gemrouter ok"}') {
    fwrite(STDERR, 'FAIL OpenAI-compatible response extraction' . PHP_EOL);
    exit(1);
}

$config = [
    'ai' => ['provider' => 'gemini'],
    'gemrouter' => [
        'enabled' => true,
        'api_key' => 'router-key',
        'default_surface' => 'gemrouter',
        'enabled_surfaces' => ['gemrouter'],
        'surfaces' => [
            'gemrouter' => [
                'enabled' => true,
                'chat_url' => 'https://gemr.airewardrop.xyz/chat/completions',
            ],
        ],
    ],
    'gemini' => [
        'api_key' => 'gemini-key',
        'concierge' => [
            'model' => 'gemini-3.1-flash-lite',
        ],
    ],
];

$providerProp = $reflection->getProperty('provider');
$backendProp = $reflection->getProperty('backend');
$chatUrlProp = $reflection->getProperty('chatUrl');

$geminiClient = GeminiClient::fromConfig($config, 'concierge');
if (!$geminiClient instanceof GeminiClient || $providerProp->getValue($geminiClient) !== 'gemini' || $backendProp->getValue($geminiClient) !== 'gemini') {
    fwrite(STDERR, 'FAIL provider selector must keep Gemini when selected' . PHP_EOL);
    exit(1);
}

$config['ai']['provider'] = 'gemrouter';
$routerClient = GeminiClient::fromConfig($config, 'concierge');
if (!$routerClient instanceof GeminiClient
    || $providerProp->getValue($routerClient) !== 'gemrouter'
    || $backendProp->getValue($routerClient) !== 'openai_compatible'
    || !str_contains((string)$chatUrlProp->getValue($routerClient), '/chat/completions')) {
    fwrite(STDERR, 'FAIL provider selector must route Gemrouter only when selected' . PHP_EOL);
    exit(1);
}

echo "Gemini normalizer tests passed\n";
