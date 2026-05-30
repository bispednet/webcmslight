<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class PromptInjectionGuard
{
    public function sanitize(string $message): string
    {
        $message = trim(strip_tags($message));
        $message = preg_replace('/\s+/u', ' ', $message) ?? '';

        return mb_substr($message, 0, 1500, 'UTF-8');
    }

    public function isSpam(string $message): bool
    {
        preg_match_all('#https?://#i', $message, $urls);

        return count($urls[0]) > 2 || preg_match('/(.)\1{14,}/u', $message) === 1;
    }
}
