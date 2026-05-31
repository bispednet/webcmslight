<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class ConciergeStateMachine
{
    private LeadExtractor $extractor;

    public function __construct(
        private AgentPersonaRegistry $agents,
        private NeedClassifier $classifier,
        private ?ConversationalAnalyzer $analyzer = null
    ) {
        $this->extractor = new LeadExtractor($classifier);
    }

    public function greeting(string $locale): array
    {
        return $this->reply(
            'opening',
            $locale === 'en'
                ? 'Hi, tell me what is happening or what you would like to get done. If useful, I will prepare a WhatsApp summary for the shop.'
                : 'Ciao, raccontami cosa succede o cosa vorresti fare. Se serve, ti preparo subito il messaggio WhatsApp per il negozio.',
            $this->agents->byKey('sarai')
        );
    }

    public function advance(array $conversation, string $input, ?string $choice = null): array
    {
        $data = json_decode((string)($conversation['structured_data'] ?? '{}'), true) ?: [];
        $locale = ($conversation['locale'] ?? 'it') === 'en' ? 'en' : 'it';
        $previousAgent = (string)($data['active_agent'] ?? 'sarai');
        $message = $input !== '' ? $input : (string)$choice;
        $data = $this->extractor->extract($message, $data);
        if ($this->analyzer && $this->classifier->classify($message) === 'guidance' && empty($data['handoff_requested'])) {
            $data = $this->analyzer->enrich($message, $data);
        }
        $sector = (string)($data['detected_sector'] ?? $conversation['main_sector'] ?? 'guidance');
        $agent = $this->agents->forSector($sector);
        $data['active_agent'] = $agent['key'];

        if (!empty($data['handoff_requested'])) {
            $reply = $this->ready($sector, $data, $agent, $locale);
        } elseif ($sector === 'guidance') {
            $data['clarification_count'] = (int)($data['clarification_count'] ?? 0) + 1;
            $reply = $data['clarification_count'] >= 2
                ? $this->ready($sector, $data, $agent, $locale)
                : $this->reply(
                    'understand_need',
                    $locale === 'en'
                        ? 'Tell me in your own words what is not working or what you would like to achieve. If you prefer not to go into detail here, I can open WhatsApp directly.'
                        : 'Raccontami con parole tue cosa non funziona o cosa vorresti ottenere. Se preferisci non approfondire qui, ti apro direttamente WhatsApp.',
                    $agent,
                    ['need']
                );
        } else {
            $reply = $this->ready($sector, $data, $agent, $locale);
        }

        if ($previousAgent !== $agent['key']) {
            $reply['transition'] = 'Ti segue ' . $agent['name'] . '.';
        }
        $data['missing_fields'] = $reply['missing_fields'];
        $data['handoff_ready'] = $reply['ready'];

        return $this->with([
            'main_sector' => $sector === 'guidance' ? null : $sector,
            'customer_phone' => $data['phone'] ?? null,
            'customer_email' => $data['email'] ?? null,
            'customer_type' => $data['customer_type'] ?? 'non_definito',
            'urgency' => $data['urgency'] ?? null,
            'current_step' => $reply['step'],
            'lead_score' => $this->score($data, $reply['ready']),
            'consent_privacy' => !empty($data['phone']) ? 1 : 0,
            'status' => $reply['ready'] ? 'qualified' : 'open',
        ], $data, $reply);
    }

    private function ready(string $sector, array $data, array $agent, string $locale): array
    {
        if ($locale === 'en') {
            $message = match ($sector) {
                'tlc' => $this->tlcReadyMessage($data, $locale),
                'informatica' => 'I understood the technical issue. I am opening WhatsApp with a short summary, so the shop can start from the right point.',
                'energia_amministrativo' => 'I noted the request. I am opening WhatsApp with a summary, so the shop can check the specific situation.',
                default => 'There is no need to classify the issue any further here. I am opening WhatsApp: you can write to the shop with a summary of what you told me.',
            };

            return $this->reply('ready', $message, $agent, [], true);
        }
        $message = match ($sector) {
            'tlc' => $this->tlcReadyMessage($data, $locale),
            'informatica' => 'Ho capito il problema tecnico. Ti apro WhatsApp con un riepilogo breve, così il negozio parte già dal punto giusto.',
            'energia_amministrativo' => 'Ho segnato la richiesta. Ti apro WhatsApp con il riepilogo, così il negozio può verificare la situazione concreta.',
            default => 'Non serve incasellare meglio il problema qui. Ti apro WhatsApp: scrivi pure al negozio con il riepilogo di quello che mi hai raccontato.',
        };

        return $this->reply('ready', $message, $agent, [], true);
    }

    private function tlcReadyMessage(array $data, string $locale): string
    {
        if ($locale === 'en') {
            if (($data['service_kind'] ?? '') === 'mobile_data') {
                return 'It could be your data allowance, the mobile line or the data settings. I am opening WhatsApp with the summary, so the shop can check the specific case without making you repeat everything.';
            }
            if (($data['request_type'] ?? '') === 'new_line') {
                return 'I noted that you want to activate a new internet line. I am opening WhatsApp with the summary, so the shop can check coverage and available alternatives.';
            }

            return 'I noted the connection issue. I am opening WhatsApp with the summary, so the shop can check the specific situation.';
        }
        if (($data['service_kind'] ?? '') === 'mobile_data') {
            return 'Potrebbero essere i giga terminati, la linea mobile oppure le impostazioni dati. Ti apro WhatsApp con il riepilogo: il negozio controlla il caso concreto senza farti ripetere tutto.';
        }
        if (($data['request_type'] ?? '') === 'new_line') {
            return 'Ho segnato che vuoi attivare una nuova linea internet. Ti apro WhatsApp con il riepilogo, così il negozio può verificare copertura e alternative disponibili.';
        }

        return 'Ho segnato il problema di connessione. Ti apro WhatsApp con il riepilogo, così il negozio può verificare la situazione concreta.';
    }

    private function score(array $data, bool $ready): int
    {
        $score = 15;
        foreach (['detected_sector', 'need_summary', 'operator', 'access_type', 'urgency', 'phone', 'customer_type', 'trigger', 'service_kind', 'request_type'] as $field) {
            if (!empty($data[$field])) {
                $score += 8;
            }
        }

        return min(100, $ready ? max(65, $score) : $score);
    }

    private function reply(string $step, string $message, array $agent, array $missingFields = [], bool $ready = false): array
    {
        return ['step' => $step, 'message' => $message, 'agent' => $agent, 'missing_fields' => $missingFields, 'ready' => $ready];
    }

    private function with(array $updates, array $data, array $reply): array
    {
        $reply['updates'] = $updates + ['structured_data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];

        return $reply;
    }
}
