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
        private readonly array $thinkingConfig = [],
    ) {
    }

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
            is_array($settings['thinking_config'] ?? null) ? $settings['thinking_config'] : [],
        );
    }

    public function generate(string $prompt, int $maxOutputTokens = 2048): ?string
    {
        $reservation = $this->estimateTokens($prompt, $maxOutputTokens);
        if (!$this->reserveRequest($reservation)) {
            Logger::error('Gemini request skipped: local rate limit reached', ['profile' => $this->profile, 'model' => $this->model]);
            return null;
        }

        $generationConfig = [
            'temperature' => 0.35,
            'maxOutputTokens' => $maxOutputTokens,
        ];
        if ($this->thinkingConfig) {
            $generationConfig['thinkingConfig'] = $this->thinkingConfig;
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($this->model) . ':generateContent';
        $payload = json_encode([
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => $generationConfig,
        ], JSON_THROW_ON_ERROR);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if (!is_string($raw) || $status < 200 || $status >= 300) {
            Logger::error('Gemini generation failed', ['profile' => $this->profile, 'model' => $this->model, 'status' => $status]);
            return null;
        }

        $response = json_decode($raw, true);
        $actualTokens = (int)($response['usageMetadata']['totalTokenCount'] ?? $reservation);
        $this->replaceReservation($reservation, max(1, $actualTokens));
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;
        return is_string($text) && trim($text) !== '' ? trim($text) : null;
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
