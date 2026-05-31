<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class ConversationSupervisor
{
    private LeadExtractor $extractor;
    private AgentSwarmRouter $router;
    private AgentTurnPlanner $planner;
    private ResponseComposer $composer;
    private HandoffDecisionEngine $handoff;
    private CommercialReportBuilder $reporter;
    private ConversationRepair $repair;
    private AgentPersonaRegistry $agents;
    private ?ConversationalAnalyzer $analyzer;

    public function __construct(?ConversationalAnalyzer $analyzer = null)
    {
        $classifier = new NeedClassifier();
        $this->extractor = new LeadExtractor($classifier);
        $this->router = new AgentSwarmRouter();
        $this->planner = new AgentTurnPlanner();
        $this->composer = new ResponseComposer();
        $this->handoff = new HandoffDecisionEngine();
        $this->reporter = new CommercialReportBuilder();
        $this->repair = new ConversationRepair();
        $this->agents = new AgentPersonaRegistry();
        $this->analyzer = $analyzer;
    }

    public function greeting(string $locale): array
    {
        $message = $locale === 'en'
            ? 'Hi, tell me what you need.'
            : 'Ciao, dimmi pure cosa ti serve.';

        return [
            'step' => 'opening',
            'message' => $message,
            'agent' => $this->agents->byKey('sarai'),
            'missing_fields' => [],
            'ready' => false,
            'updates' => [],
        ];
    }

    public function handleTurn(array $conversation, string $message): array
    {
        $memory = ConversationMemory::fromConversation($conversation);
        $locale = ($conversation['locale'] ?? 'it') === 'en' ? 'en' : 'it';

        // 1. Extract from current message
        $existingData = json_decode((string)($conversation['structured_data'] ?? '{}'), true) ?: [];
        $extracted = $this->extractor->extract($message, $existingData);

        // 2. Optionally enrich with LLM if available and sector unclear
        if ($this->analyzer && ($memory->mainSector === null || $memory->mainSector === 'guidance')) {
            $enriched = $this->analyzer->enrich($message, $extracted);
            $extracted = array_merge($extracted, $enriched);
        }

        // 3. Detect repair/correction (will be applied AFTER merge so it wins)
        $correctionField = $this->repair->detect($message, $memory);

        // 4. Merge extracted into memory
        $memory->mergeExtracted($extracted);

        // 4b. Apply correction after merge so it overrides any merged values
        if ($correctionField) {
            $memory->applyCorrection($correctionField);
        }

        // 5. Route to correct agent
        $routeResult = $this->router->route($memory, $extracted);
        $memory->activeAgent = $routeResult['active_agent'];
        $memory->mainSector = $routeResult['main_sector'] ?? $memory->mainSector;
        $memory->secondarySectors = $routeResult['secondary_agents'] ?? [];

        // 6. Increment useful turn count if message has substance
        if ($this->hasSubstance($message, $memory)) {
            $memory->usefulTurnCount++;
        }

        // 7. Plan next action
        $isRepair = ($correctionField !== null);
        $plan = $this->planner->plan($memory, $isRepair);

        // 8. Override plan if handoff engine says ready
        if ($this->handoff->decide($memory)) {
            $plan = ['action' => 'handoff', 'slot' => null, 'question_intent' => null, 'handoff_ready' => true, 'handoff_reason' => $this->handoff->reason($memory)];
        }

        // 9. Compose customer-visible reply
        // handleTurn is never called for the initial greeting (that's supervisor->greeting())
        if ($correctionField && $correctionField !== 'repetition') {
            $customerMessage = $this->repair->repairMessage($correctionField, $memory);
        } elseif ($plan['action'] === 'handoff') {
            $customerMessage = $this->composer->composeHandoff($memory, $plan);
        } else {
            // Try LLM-based response with Bisped business context first
            $customerMessage = $this->composeLlmResponse($memory, $plan, $message);
            if ($customerMessage === null) {
                $customerMessage = $this->composer->compose($memory, $plan);
            }
        }

        // Style guard: catch any forbidden phrases
        $guard = new ResponseStyleGuard();
        if ($guard->violatesPublicTone($customerMessage)) {
            $cleaned = $guard->cleanCustomerMessage($customerMessage);
            $customerMessage = $guard->violatesPublicTone($cleaned)
                ? $guard->fallback($memory->mainSector ?? 'guidance')
                : $cleaned;
        }

        // 10. Set handoff state
        $handoffReady = $plan['action'] === 'handoff';
        $memory->handoffReady = $handoffReady;
        $memory->handoffReason = $plan['handoff_reason'] ?? null;

        // 11. Build commercial report if handing off
        $commercialReport = null;
        $analytics = null;
        if ($handoffReady) {
            $transcript = $this->buildTranscript($conversation);
            $commercialReport = $this->reporter->buildReport($memory, $transcript);
            $analytics = $this->reporter->buildAnalytics($memory);
        }

        // 12. Compute lead score
        $score = $this->handoff->score($memory);

        // 13. Build structured data
        $structuredData = $memory->toStructuredData();
        if ($commercialReport) {
            $structuredData['commercial_report'] = $commercialReport;
        }
        if ($analytics) {
            $structuredData['analytics'] = $analytics;
        }

        // 14. Build agent for UI
        $agent = $this->agents->byKey($memory->activeAgent);

        // 15. Build DB updates
        $updates = array_filter([
            'main_sector' => $memory->mainSector,
            'customer_phone' => $memory->phone,
            'customer_email' => $memory->email,
            'customer_type' => $memory->customerType ?? 'non_definito',
            'urgency' => $memory->urgency,
            'current_step' => $handoffReady ? 'ready' : 'in_progress',
            'lead_score' => $score,
            'consent_privacy' => $memory->has('phone') ? 1 : 0,
            'status' => $handoffReady ? 'qualified' : 'open',
            'structured_data' => json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ], static fn ($v): bool => $v !== null);

        $previousAgent = $conversation['structured_data']
            ? (json_decode((string)$conversation['structured_data'], true)['active_agent'] ?? 'router')
            : 'router';
        $transition = null;
        if ($previousAgent !== $memory->activeAgent && $previousAgent !== 'router') {
            // Don't expose the agent name swap to customer; signal internally only
            $transition = null;
        }

        return [
            'step' => $handoffReady ? 'ready' : 'in_progress',
            'message' => $customerMessage,
            'agent' => $agent,
            'missing_fields' => $memory->missingCriticalSlots(),
            'ready' => $handoffReady,
            'updates' => $updates,
            'transition' => $transition,
            'commercial_report' => $commercialReport,
            'analytics' => $analytics,
        ];
    }

    private function composeLlmResponse(ConversationMemory $memory, array $plan, string $userMessage): ?string
    {
        if (!$this->analyzer) {
            return null;
        }

        try {
            $prompt = BispedBusinessContext::buildReplyPrompt(
                $memory->activeAgent,
                $memory,
                $plan,
                $userMessage
            );
            $raw = $this->analyzer->generateRaw($prompt, 180);
            if ($raw === null || trim($raw) === '') {
                return null;
            }
            // Validate: must be plain text, not JSON, not empty
            $text = trim($raw);
            if (str_starts_with($text, '{') || str_starts_with($text, '[')) {
                return null;
            }

            return $text;
        } catch (\Throwable) {
            return null;
        }
    }

    private function hasSubstance(string $message, ConversationMemory $memory): bool
    {
        $lower = mb_strtolower(trim($message), 'UTF-8');
        // Very short filler messages don't count as useful turns
        if (mb_strlen($lower, 'UTF-8') < 3) {
            return false;
        }
        // Pure greetings
        if (preg_match('/^(ciao|salve|buongiorno|buonasera|ok|si|no|boh)$/u', $lower)) {
            return false;
        }

        return true;
    }

    private function buildTranscript(array $conversation): array
    {
        // Transcript is loaded by orchestrator if needed; provide minimal structure
        return [];
    }
}
