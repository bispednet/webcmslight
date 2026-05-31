<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class ConversationRepair
{
    /** @var array<string,list<string>> field → trigger patterns */
    private const CORRECTION_PATTERNS = [
        'gaming' => [
            'chi ti ha detto che gioco',
            'non ho detto che gioco',
            'non gioco',
            'niente gaming',
            'non ho parlato di gaming',
        ],
        'operator' => [
            'non sono con',
            'non ho detto che sono con',
            'non uso',
        ],
        'urgency' => [
            'non è urgente',
            'non ho fretta',
            'posso aspettare',
        ],
    ];

    public function detect(string $message, ConversationMemory $memory): ?string
    {
        $lower = mb_strtolower(trim($message), 'UTF-8');

        foreach (self::CORRECTION_PATTERNS as $field => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($lower, $pattern)) {
                    // Only correct if the field was actually set
                    if ($this->fieldIsSet($field, $memory)) {
                        return $field;
                    }
                }
            }
        }

        // Topic correction: "non ho detto X", "non ho parlato di X", "vi ho chiesto Y"
        if (preg_match('/non ho (?:detto|parlato di|menzionato|chiesto)|non ho mai|vi ho chiesto|ti ho chiesto/ui', $message)) {
            return 'topic_correction';
        }

        // Generic irritation: "te l'ho già detto", "me l'hai già chiesto"
        if (preg_match("/te l.ho già detto|me l.hai già chiesto|l.ho già detto|già detto/ui", $message)) {
            return 'repetition';
        }

        return null;
    }

    private function fieldIsSet(string $field, ConversationMemory $memory): bool
    {
        return match ($field) {
            'gaming' => $memory->getGamingContext(),
            'urgency' => $memory->urgency !== null,
            'operator' => $memory->has('operator'),
            default => false,
        };
    }

    public function repairMessage(string $field, ConversationMemory $memory): string
    {
        if ($field === 'repetition') {
            return 'Hai ragione, me l\'avevi già dato. Vado al punto.';
        }
        if ($field === 'topic_correction') {
            return 'Ok, capito. Dimmi tu cosa ti serve esattamente e riparto da quello.';
        }

        $fieldNames = [
            'gaming'   => 'il gaming',
            'operator' => 'l\'operatore',
            'urgency'  => 'l\'urgenza',
        ];
        $name = $fieldNames[$field] ?? $field;

        return "Hai ragione, tolgo {$name} da quello che ho raccolto. Ripartiamo.";
    }
}
