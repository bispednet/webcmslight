<?php
declare(strict_types=1);

namespace App\Services\Ai;

use App\Core\Logger;

final class GeminiClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'gemma-4-31b-it',
        private readonly int $requestsPerMinute = 10,
        private readonly int $requestsPerDay = 5000,
        private readonly int $cooldownSeconds = 7,
    ) {
    }

    public static function fromConfig(array $config): ?self
    {
        $settings = $config['gemini'] ?? [];
        $apiKey = trim((string)($settings['api_key'] ?? ''));
        if ($apiKey === '') {
            return null;
        }

        return new self(
            $apiKey,
            (string)($settings['model'] ?? 'gemma-4-31b-it'),
            max(1, (int)($settings['requests_per_minute'] ?? 10)),
            max(1, (int)($settings['requests_per_day'] ?? 5000)),
            max(1, (int)($settings['cooldown_seconds'] ?? 7)),
        );
    }

    public function generate(string $prompt, int $maxOutputTokens = 8192): ?string
    {
        $this->waitForCooldown();
        if (!$this->reserveRequest()) {
            Logger::error('Gemini request skipped: local rate limit reached', ['model' => $this->model]);
            return null;
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($this->model) . ':generateContent';
        $payload = json_encode([
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'temperature' => 0.35,
                'maxOutputTokens' => $maxOutputTokens,
            ],
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
            CURLOPT_TIMEOUT => 150,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if (!is_string($raw) || $status < 200 || $status >= 300) {
            Logger::error('Gemini generation failed', ['model' => $this->model, 'status' => $status]);
            return null;
        }

        $response = json_decode($raw, true);
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;
        return is_string($text) && trim($text) !== '' ? trim($text) : null;
    }

    private function waitForCooldown(): void
    {
        $path = BASE_PATH . '/storage/gemini-rate-limit.json';
        if (!is_file($path)) {
            return;
        }
        $state = json_decode((string)file_get_contents($path), true);
        $last = is_array($state) ? (int)($state['last'] ?? 0) : 0;
        $remaining = $this->cooldownSeconds - (time() - $last);
        if ($remaining > 0) {
            sleep($remaining);
        }
    }

    private function reserveRequest(): bool
    {
        $path = BASE_PATH . '/storage/gemini-rate-limit.json';
        $handle = fopen($path, 'c+');
        if ($handle === false) {
            return false;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }
            $raw = stream_get_contents($handle);
            $state = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
            $state = is_array($state) ? $state : [];
            $now = time();
            $day = gmdate('Y-m-d', $now);
            $minute = array_values(array_filter(
                (array)($state['minute'] ?? []),
                static fn(mixed $timestamp): bool => is_int($timestamp) && ($now - $timestamp) < 60
            ));
            $last = (int)($state['last'] ?? 0);
            $daily = ($state['day'] ?? '') === $day ? (int)($state['daily'] ?? 0) : 0;

            if (($now - $last) < $this->cooldownSeconds
                || count($minute) >= $this->requestsPerMinute
                || $daily >= $this->requestsPerDay) {
                return false;
            }

            $minute[] = $now;
            $state = ['day' => $day, 'daily' => $daily + 1, 'minute' => $minute, 'last' => $now];
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($state, JSON_THROW_ON_ERROR));
            fflush($handle);
            return true;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
