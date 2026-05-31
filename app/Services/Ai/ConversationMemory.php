<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class ConversationMemory
{
    public string $activeAgent = 'router';
    public ?string $mainSector = null;
    public array $secondarySectors = [];
    public ?string $customerType = null;
    public ?string $phone = null;
    public ?string $email = null;
    public ?string $name = null;
    public ?string $needSummary = null;
    public ?string $urgency = null;
    public array $facts = [];
    public array $painPoints = [];
    public array $missing = [];
    public array $objections = [];
    public array $opportunities = [];
    public array $corrections = [];
    public array $conversationSignals = [];
    public bool $handoffReady = false;
    public ?string $handoffReason = null;
    public int $usefulTurnCount = 0;
    public bool $callbackRequested = false;
    public bool $handoffExplicitlyRequested = false;
    public ?string $customerEmotion = null;
    public ?string $commercialReport = null;
    public ?array $analytics = null;

    public static function fromConversation(array $conversation): self
    {
        $self = new self();
        $data = json_decode((string)($conversation['structured_data'] ?? '{}'), true) ?: [];

        $self->activeAgent = (string)($data['active_agent'] ?? 'router');
        $self->mainSector = ($conversation['main_sector'] ?? null) ?: ($data['main_sector'] ?? null);
        $self->customerType = ($conversation['customer_type'] ?? null) === 'non_definito' ? null : ($conversation['customer_type'] ?? null);
        $self->phone = ($conversation['customer_phone'] ?? null) ?: ($data['phone'] ?? null);
        $self->email = ($conversation['customer_email'] ?? null) ?: ($data['email'] ?? null);
        $self->name = ($conversation['customer_name'] ?? null) ?: null;
        $self->needSummary = $data['need_summary'] ?? null;
        $self->urgency = ($conversation['urgency'] ?? null) ?: ($data['urgency'] ?? null);
        $self->facts = (array)($data['facts'] ?? []);
        $self->painPoints = (array)($data['pain_points'] ?? []);
        $self->missing = (array)($data['missing'] ?? []);
        $self->objections = (array)($data['objections'] ?? []);
        $self->opportunities = (array)($data['opportunities'] ?? []);
        $self->corrections = (array)($data['corrections'] ?? []);
        $self->conversationSignals = (array)($data['conversation_signals'] ?? []);
        $self->handoffReady = (bool)($data['handoff_ready'] ?? false);
        $self->handoffReason = $data['handoff_reason'] ?? null;
        $self->usefulTurnCount = (int)($data['useful_turn_count'] ?? 0);
        $self->callbackRequested = (bool)($data['callback_requested'] ?? false);
        $self->handoffExplicitlyRequested = (bool)($data['handoff_explicitly_requested'] ?? false);
        $self->customerEmotion = $data['customer_emotion'] ?? null;

        // Carry forward facts from legacy structured_data format
        foreach (['operator', 'access_type', 'service_kind', 'request_type', 'usage_context', 'symptoms', 'trigger', 'commodity'] as $key) {
            if (isset($data[$key])) {
                $self->facts[$key] = $data[$key];
            }
        }

        return $self;
    }

    public function mergeExtracted(array $extracted): void
    {
        // Phone: explicit always wins
        if (!empty($extracted['phone'])) {
            $this->phone = $extracted['phone'];
            $this->facts['phone'] = $extracted['phone'];
        }
        if (!empty($extracted['email'])) {
            $this->email = $extracted['email'];
        }
        if (!empty($extracted['name'])) {
            $this->name = $extracted['name'];
        }
        if (!empty($extracted['detected_sector']) && $extracted['detected_sector'] !== 'guidance') {
            $this->mainSector = $extracted['detected_sector'];
        }
        if (!empty($extracted['customer_type'])) {
            $this->customerType = $extracted['customer_type'];
        }
        if (!empty($extracted['urgency'])) {
            $this->urgency = $extracted['urgency'];
        }
        if (!empty($extracted['need_summary'])) {
            $this->needSummary = $extracted['need_summary'];
        }
        if (!empty($extracted['callback_requested'])) {
            $this->callbackRequested = true;
        }
        if (!empty($extracted['handoff_requested'])) {
            $this->handoffExplicitlyRequested = true;
        }
        if (!empty($extracted['pain_points'])) {
            $this->painPoints = array_merge($this->painPoints, (array)$extracted['pain_points']);
        }
        foreach ([
            'operator', 'access_type', 'service_kind', 'request_type', 'trigger', 'commodity',
            'usage_context', 'symptoms', 'device_type', 'device_brand', 'pc_brand', 'gpu_request',
            'use_case', 'budget_eur', 'purchase_method', 'trade_in', 'it_request', 'hardware_signals',
            'connectivity_signals', 'topic_correction',
        ] as $key) {
            if (isset($extracted[$key])) {
                $this->facts[$key] = $extracted[$key];
            }
        }
        if (isset($extracted['customer_emotion'])) {
            $this->customerEmotion = $extracted['customer_emotion'];
        }
    }

    public function applyCorrection(string $field): void
    {
        $this->corrections[] = $field;
        switch ($field) {
            case 'gaming':
                unset($this->facts['usage_context']['gaming'], $this->painPoints['stabilita_ping']);
                if (isset($this->facts['usage_context']) && empty($this->facts['usage_context'])) {
                    unset($this->facts['usage_context']);
                }
                // Clean gaming from need_summary
                if ($this->needSummary !== null) {
                    $this->needSummary = preg_replace('/,?\s*gaming online/i', '', $this->needSummary) ?? $this->needSummary;
                    $this->needSummary = preg_replace('/,?\s*stabilità\/ping/i', '', $this->needSummary) ?? $this->needSummary;
                    $this->needSummary = trim(rtrim(trim($this->needSummary), ','));
                }
                break;
            case 'topic_correction':
                // Reset the need_summary so the next message builds a fresh one
                $this->needSummary = null;
                break;
        }
    }

    public function has(string $slot): bool
    {
        return match ($slot) {
            'phone' => !empty($this->phone),
            'sector' => $this->mainSector !== null && $this->mainSector !== 'guidance',
            'need' => !empty($this->needSummary),
            'operator' => !empty($this->facts['operator']),
            'access_type' => !empty($this->facts['access_type']),
            'urgency' => $this->urgency !== null,
            'customer_type' => $this->customerType !== null,
            'email' => !empty($this->email),
            default => false,
        };
    }

    public function missingCriticalSlots(): array
    {
        $missing = [];
        if (!$this->has('phone') && !$this->callbackRequested && !$this->handoffExplicitlyRequested) {
            $missing[] = 'phone';
        }

        return $missing;
    }

    public function toStructuredData(): array
    {
        return [
            'active_agent' => $this->activeAgent,
            'main_sector' => $this->mainSector,
            'detected_sector' => $this->mainSector, // persisted so LeadExtractor keeps context on next turn
            'secondary_sectors' => $this->secondarySectors,
            'customer_type' => $this->customerType,
            'phone' => $this->phone,
            'email' => $this->email,
            'name' => $this->name,
            'need_summary' => $this->needSummary,
            'urgency' => $this->urgency,
            'facts' => $this->facts,
            'pain_points' => $this->painPoints,
            'missing' => $this->missing,
            'objections' => $this->objections,
            'opportunities' => $this->opportunities,
            'corrections' => $this->corrections,
            'conversation_signals' => $this->conversationSignals,
            'handoff_ready' => $this->handoffReady,
            'handoff_reason' => $this->handoffReason,
            'useful_turn_count' => $this->usefulTurnCount,
            'callback_requested' => $this->callbackRequested,
            'handoff_explicitly_requested' => $this->handoffExplicitlyRequested,
            'customer_emotion' => $this->customerEmotion,
            // Legacy fields for backward compat
            'operator' => $this->facts['operator'] ?? null,
            'access_type' => $this->facts['access_type'] ?? null,
            'service_kind' => $this->facts['service_kind'] ?? null,
            'request_type' => $this->facts['request_type'] ?? null,
            'usage_context' => $this->facts['usage_context'] ?? null,
            'pain_points_legacy' => $this->painPoints ?: null,
            'trigger' => $this->facts['trigger'] ?? null,
            'commodity' => $this->facts['commodity'] ?? null,
        ];
    }

    public function getGamingContext(): bool
    {
        return !empty($this->facts['usage_context']['gaming']);
    }

    public function isUrgent(): bool
    {
        return $this->urgency === 'alta';
    }

    public function isIrritated(): bool
    {
        // 'urgent' is NOT irritation — it's a separate urgency signal
        return in_array($this->customerEmotion, ['frustrated', 'irritated'], true);
    }

    public function hasSectorAndNeed(): bool
    {
        return $this->has('sector') && !empty($this->needSummary);
    }
}
