<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class ConversationalAnalyzer
{
    public function __construct(private ?GeminiClient $client, private PromptBuilder $prompts)
    {
    }

    public function enrich(string $message, array $data): array
    {
        if (!$this->client || trim($message) === '') {
            return $data;
        }
        $raw = $this->client->generate($this->prompts->conversationAnalysis($message, $data), 260, 'json');
        $analysis = $raw ? json_decode($raw, true) : null;
        if (!is_array($analysis)) {
            return $data;
        }

        $sector = (string)($analysis['sector'] ?? 'guidance');
        $confidence = (float)($analysis['confidence'] ?? 0);
        if (in_array($sector, ['tlc', 'informatica', 'energia_amministrativo'], true) && $confidence >= 0.78) {
            $data['detected_sector'] = $sector;
            $data['routing_confidence'] = max((int)($data['routing_confidence'] ?? 0), (int)round($confidence * 100));
        }
        $summary = trim((string)($analysis['need_summary'] ?? ''));
        if ($summary !== '') {
            $data['need_summary'] = mb_substr($summary, 0, 180, 'UTF-8');
        }
        if (($analysis['handoff_requested'] ?? false) === true) {
            $data['handoff_requested'] = true;
        }
        if (($analysis['remove_gaming_assumption'] ?? false) === true) {
            unset($data['usage_context']['gaming'], $data['pain_points']['stabilita_ping']);
        }
        $data['semantic_route'] = [
            'sector' => $sector,
            'confidence' => $confidence,
            'need_summary' => $summary,
        ];

        return $data;
    }
}
