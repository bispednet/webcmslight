<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class HandoffDecisionEngine
{
    public function decide(ConversationMemory $memory): bool
    {
        // Immediate: customer requested human/WhatsApp explicitly
        if ($memory->handoffExplicitlyRequested) {
            return true;
        }

        // Immediate: customer is very irritated and we have minimal context
        if ($memory->isIrritated() && $memory->has('sector')) {
            return true;
        }

        // Phone given + sector + need → ready
        if ($memory->has('phone') && $memory->hasSectorAndNeed()) {
            return true;
        }

        // Callback requested + phone present
        if ($memory->callbackRequested && $memory->has('phone')) {
            return true;
        }

        // After max 4 turns with sector + need, stop collecting
        if ($memory->usefulTurnCount >= 4 && $memory->hasSectorAndNeed()) {
            return true;
        }

        // Urgent + sector + need + phone present → handoff
        if ($memory->isUrgent() && $memory->hasSectorAndNeed() && $memory->has('phone')) {
            return true;
        }

        // Urgent after 4+ turns even without phone (don't block forever)
        if ($memory->isUrgent() && $memory->hasSectorAndNeed() && $memory->usefulTurnCount >= 4) {
            return true;
        }

        return false;
    }

    public function reason(ConversationMemory $memory): string
    {
        if ($memory->handoffExplicitlyRequested) {
            return 'customer_requested_human';
        }
        if ($memory->isIrritated()) {
            return 'customer_irritated_handoff';
        }
        if ($memory->has('phone') && $memory->hasSectorAndNeed()) {
            return 'phone_and_context_complete';
        }
        if ($memory->callbackRequested && $memory->has('phone')) {
            return 'callback_requested_with_phone';
        }
        if ($memory->usefulTurnCount >= 4) {
            return 'max_turns_reached';
        }
        if ($memory->isUrgent()) {
            return 'urgent_with_sufficient_context';
        }

        return 'context_sufficient';
    }

    public function score(ConversationMemory $memory): int
    {
        $score = 15;
        if ($memory->has('sector')) {
            $score += 15;
        }
        if (!empty($memory->needSummary)) {
            $score += 10;
        }
        if ($memory->has('phone')) {
            $score += 15;
        }
        if ($memory->has('operator')) {
            $score += 8;
        }
        if ($memory->has('access_type')) {
            $score += 5;
        }
        if ($memory->isUrgent()) {
            $score += 10;
        }
        if ($memory->customerType !== null) {
            $score += 5;
        }
        if (!empty($memory->painPoints)) {
            $score += 7;
        }
        if ($memory->callbackRequested) {
            $score += 5;
        }
        if ($memory->has('email')) {
            $score += 5;
        }

        return min(100, $this->decide($memory) ? max(65, $score) : $score);
    }
}
