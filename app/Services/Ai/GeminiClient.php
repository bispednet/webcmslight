<?php
declare(strict_types=1);

namespace App\Services\Ai;

use App\Core\Logger;

final class GeminiClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $profile,
        private readonly string $model,
        private readonly int $requestsPerMinute,
        private readonly int $tokensPerMinute,
        private readonly int $requestsPerDay,
        private readonly int $cooldownSeconds,
        private readonly int $timeoutSeconds,
        private readonly float $temperature = 0.35,
        private readonly bool $includeThoughts = false,
        private readonly bool $stripReasoningOutput = true,
        private readonly string $thinkingLevel = 'minimal',
        private readonly int $thinkingBudget = 0,
    ) {
    }

    private bool $useThinkingFallback = false;
    /** @var array<string,bool> */
    private static array $thinkingFallbackModels = [];

    public static function fromConfig(array $config, string $profile = 'editorial'): ?self
    {
        $gemini = $config['gemini'] ?? [];
        $settings = $gemini[$profile] ?? ($profile === 'editorial' ? $gemini : []);
        $apiKey = trim((string)($gemini['api_key'] ?? $settings['api_key'] ?? ''));
        if ($apiKey === '' || empty($settings['model'])) {
            return null;
        }

        return new self(
            $apiKey,
            preg_replace('/[^a-z0-9_-]/i', '', $profile) ?: 'default',
            (string)$settings['model'],
            max(1, (int)($settings['requests_per_minute'] ?? 10)),
            max(1, (int)($settings['tokens_per_minute'] ?? 5000)),
            max(1, (int)($settings['requests_per_day'] ?? 1000)),
            max(0, (int)($settings['cooldown_seconds'] ?? 0)),
            max(5, (int)($settings['timeout_seconds'] ?? 150)),
            (float)($settings['temperature'] ?? 0.35),
            (bool)($settings['include_thoughts'] ?? false),
            (bool)($settings['strip_reasoning'] ?? true),
            (string)($settings['thinking_level'] ?? ($settings['thinking_config']['thinkingLevel'] ?? 'minimal')),
            (int)($settings['thinking_budget'] ?? 0),
        );
    }

    public function generate(string $prompt, int $maxOutputTokens = 8192, string $mode = 'text'): ?string
    {
        $mode = in_array($mode, ['text', 'html', 'json'], true) ? $mode : 'text';
        $reservation = $this->estimateTokens($prompt, $maxOutputTokens);
        if (!$this->reserveRequest($reservation)) {
            Logger::error('Gemini request skipped: local rate limit reached', ['profile' => $this->profile, 'model' => $this->model]);
            return null;
        }

        $this->useThinkingFallback = self::$thinkingFallbackModels[$this->model] ?? $this->useThinkingFallback;
        [$status, $response] = $this->request($prompt, $maxOutputTokens, $mode, $this->useThinkingFallback);
        $error = strtolower((string)($response['error']['message'] ?? ''));
        if ($status === 400 && !$this->useThinkingFallback && str_contains($error, 'thinking')) {
            $this->useThinkingFallback = true;
            self::$thinkingFallbackModels[$this->model] = true;
            Logger::info('Gemini thinkingConfig fallback enabled', ['profile' => $this->profile, 'model' => $this->model]);
            [$status, $response] = $this->request($prompt, $maxOutputTokens, $mode, true);
        }
        if ($status < 200 || $status >= 300 || !$response) {
            Logger::error('Gemini generation failed', ['profile' => $this->profile, 'model' => $this->model, 'status' => $status]);
            return null;
        }

        $actualTokens = (int)($response['usageMetadata']['totalTokenCount'] ?? $reservation);
        $this->replaceReservation($reservation, max(1, $actualTokens));
        $text = $this->extractVisibleText($response);
        return $text === null ? null : $this->normalizeModelOutput($text, $mode);
    }

    /**
     * @return array{0:int,1:?array}
     */
    private function request(string $prompt, int $maxOutputTokens, string $mode, bool $fallback): array
    {
        $thinkingConfig = $fallback
            ? ['includeThoughts' => $this->includeThoughts]
            : [
                'includeThoughts' => $this->includeThoughts,
                'thinkingLevel' => $this->thinkingLevel,
                'thinkingBudget' => $this->thinkingBudget,
            ];
        $payload = json_encode([
            'systemInstruction' => ['parts' => [['text' => $this->systemInstructionForMode($mode)]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => $this->temperature,
                'maxOutputTokens' => $maxOutputTokens,
                'thinkingConfig' => $thinkingConfig,
            ],
        ], JSON_THROW_ON_ERROR);
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($this->model) . ':generateContent';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . $this->apiKey],
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $response = is_string($raw) ? json_decode($raw, true) : null;
        return [$status, is_array($response) ? $response : null];
    }

    private function systemInstructionForMode(string $mode): string
    {
        $suffix = $mode === 'json'
            ? 'If JSON mode is active, return only valid JSON.'
            : ($mode === 'html' ? 'If HTML mode is active, return only the final HTML fragment.' : '');
        return trim('Return only the final payload requested by the caller.
Do not expose reasoning, chain-of-thought, thoughts, analysis, scratchpad, hidden reasoning, draft notes, self-checks, markdown fences or explanations.
' . $suffix);
    }

    private function extractVisibleText(array $response): ?string
    {
        $visible = [];
        $removedThoughts = 0;
        foreach ((array)($response['candidates'][0]['content']['parts'] ?? []) as $part) {
            if (!is_array($part)) {
                continue;
            }
            if (($part['thought'] ?? false) === true) {
                $removedThoughts++;
                continue;
            }
            $text = trim((string)($part['text'] ?? ''));
            if ($text !== '') {
                $visible[] = $text;
            }
        }
        if ($removedThoughts > 0) {
            Logger::info('Gemini thought parts removed', ['profile' => $this->profile, 'model' => $this->model, 'parts' => $removedThoughts]);
        }
        $text = trim(implode("\n", $visible));
        return $text === '' ? null : $text;
    }

    private function normalizeModelOutput(string $text, string $mode = 'text'): ?string
    {
        $text = $this->stripReasoningOutput ? $this->stripReasoning($text) : trim($text);
        $text = $this->unwrapMarkdownFence($text);
        return match ($mode) {
            'json' => $this->normalizeJsonOutput($text),
            'html' => $this->normalizeHtmlOutput($text),
            default => trim($text) !== '' ? trim($text) : null,
        };
    }

    private function stripReasoning(string $text): string
    {
        $original = $text;
        $text = preg_replace('#<(think|thinking|reasoning)\b[^>]*>.*?</\1>#is', '', $text) ?? $text;
        $text = preg_replace('/<\|channel\|>\s*(?:thought|analysis).*?(?=<\|channel\|>\s*(?:final|answer)|$)/is', '', $text) ?? $text;
        if (preg_match_all('/(?:^|\n)\s*(?:Final|Answer|Response|assistant)\s*:\s*/i', $text, $matches, PREG_OFFSET_CAPTURE) && $matches[0]) {
            $last = end($matches[0]);
            $text = substr($text, (int)$last[1] + strlen((string)$last[0]));
        }
        if ($text !== $original) {
            Logger::info('Gemini textual reasoning removed', ['profile' => $this->profile, 'model' => $this->model, 'before' => strlen($original), 'after' => strlen($text)]);
        }
        return trim($text);
    }

    private function unwrapMarkdownFence(string $text): string
    {
        return trim(preg_replace('/^```(?:html|json|text)?\s*|\s*```$/i', '', trim($text)) ?? $text);
    }

    private function extractBalancedJson(string $text): ?string
    {
        $length = strlen($text);
        for ($start = 0; $start < $length; $start++) {
            if (!in_array($text[$start], ['{', '['], true)) continue;
            $stack = [];
            $quoted = false;
            $escaped = false;
            for ($index = $start; $index < $length; $index++) {
                $char = $text[$index];
                if ($quoted) {
                    if ($escaped) $escaped = false;
                    elseif ($char === '\\') $escaped = true;
                    elseif ($char === '"') $quoted = false;
                    continue;
                }
                if ($char === '"') $quoted = true;
                elseif ($char === '{' || $char === '[') $stack[] = $char;
                elseif ($char === '}' || $char === ']') {
                    $open = array_pop($stack);
                    if (($open === '{' && $char !== '}') || ($open === '[' && $char !== ']')) break;
                    if (!$stack) return substr($text, $start, $index - $start + 1);
                }
            }
        }
        return null;
    }

    private function normalizeJsonOutput(string $text): ?string
    {
        $json = $this->extractBalancedJson($text);
        $decoded = $json === null ? null : json_decode($json, true);
        if ($json === null || json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('Gemini JSON normalization failed', ['profile' => $this->profile, 'model' => $this->model, 'length' => strlen($text)]);
            return null;
        }
        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function normalizeHtmlOutput(string $text): ?string
    {
        if (!preg_match('/<(?:h1|h2|p|section|article|ul|ol)\b/i', $text, $match, PREG_OFFSET_CAPTURE)) {
            Logger::error('Gemini HTML normalization failed: no useful tag', ['profile' => $this->profile, 'model' => $this->model, 'length' => strlen($text)]);
            return null;
        }
        $html = substr($text, (int)$match[0][1]);
        return trim($html) !== '' ? trim($html) : null;
    }

    private function estimateTokens(string $prompt, int $maxOutputTokens): int
    {
        return max(1, (int)ceil(mb_strlen($prompt, 'UTF-8') / 4) + $maxOutputTokens);
    }

    private function reserveRequest(int $tokens): bool
    {
        if ($tokens > $this->tokensPerMinute) {
            return false;
        }
        $deadline = time() + 65;
        do {
            if ($this->writeReservation($tokens)) {
                return true;
            }
            sleep(2);
        } while (time() < $deadline);
        return false;
    }

    private function writeReservation(int $tokens): bool
    {
        return $this->updateState(function (array $profile) use ($tokens): array {
            $now = time();
            $profile = $this->pruneProfile($profile, $now);
            $minute = $profile['minute'];
            $daily = ($profile['day'] ?? '') === gmdate('Y-m-d', $now) ? (int)($profile['daily'] ?? 0) : 0;
            $tokenTotal = array_sum(array_column($minute, 'tokens'));
            if (($now - (int)($profile['last'] ?? 0)) < $this->cooldownSeconds
                || count($minute) >= $this->requestsPerMinute
                || $tokenTotal + $tokens > $this->tokensPerMinute
                || $daily >= $this->requestsPerDay) {
                return [$profile, false];
            }
            $minute[] = ['at' => $now, 'tokens' => $tokens];
            return [[
                'day' => gmdate('Y-m-d', $now),
                'daily' => $daily + 1,
                'minute' => $minute,
                'last' => $now,
            ], true];
        });
    }

    private function replaceReservation(int $reservedTokens, int $actualTokens): void
    {
        $this->updateState(function (array $profile) use ($reservedTokens, $actualTokens): array {
            $profile = $this->pruneProfile($profile, time());
            for ($index = count($profile['minute']) - 1; $index >= 0; $index--) {
                if ((int)$profile['minute'][$index]['tokens'] === $reservedTokens) {
                    $profile['minute'][$index]['tokens'] = $actualTokens;
                    break;
                }
            }
            return [$profile, true];
        });
    }

    private function pruneProfile(array $profile, int $now): array
    {
        $profile['minute'] = array_values(array_filter(
            (array)($profile['minute'] ?? []),
            static fn(mixed $request): bool => is_array($request) && ($now - (int)($request['at'] ?? 0)) < 60
        ));
        return $profile;
    }

    private function updateState(callable $callback): bool
    {
        $path = BASE_PATH . '/storage/gemini-rate-limit.json';
        $handle = fopen($path, 'c+');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            return false;
        }
        try {
            $raw = stream_get_contents($handle);
            $state = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
            $state = is_array($state) ? $state : [];
            $profiles = is_array($state['profiles'] ?? null) ? $state['profiles'] : [];
            [$profiles[$this->profile], $result] = $callback((array)($profiles[$this->profile] ?? []));
            $state = ['profiles' => $profiles];
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($state, JSON_THROW_ON_ERROR));
            fflush($handle);
            return (bool)$result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
