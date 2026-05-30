<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class ResponseStyleGuard
{
    public function validateAgentMessage(string $agent, string $message, string $fallback): string
    {
        if ($message === '' || mb_strlen($message, 'UTF-8') > 600 || substr_count($message, '?') > 2) {
            return $fallback;
        }
        if ($agent === 'sarai' && preg_match('/gentile cliente|siamo lieti|la ringraziamo|migliore offerta|risparmio garantito|offerta imperdibile|\bLei\b/ui', $message)) {
            return $fallback;
        }

        return $message;
    }
}
