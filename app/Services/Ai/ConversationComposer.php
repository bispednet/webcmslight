<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class ConversationComposer
{
    public function __construct(
        private ?GeminiClient $client,
        private PromptBuilder $prompts,
        private ResponseStyleGuard $guard
    ) {
    }

    public function compose(array $agent, string $userMessage, string $fallback, array $data): string
    {
        if (!$this->client || trim($userMessage) === '') {
            return $fallback;
        }
        $raw = $this->client->generate($this->prompts->conversationalRewrite($agent, $userMessage, $fallback, $data), 220, 'json');
        $decoded = $raw ? json_decode($raw, true) : null;
        $message = is_array($decoded) ? trim((string)($decoded['assistant_message'] ?? '')) : '';

        return $this->guard->validateAgentMessage((string)$agent['key'], $message, $fallback);
    }
}
