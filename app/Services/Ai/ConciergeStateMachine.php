<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class ConciergeStateMachine
{
    private LeadExtractor $extractor;

    public function __construct(
        private AgentPersonaRegistry $agents,
        private NeedClassifier $classifier
    ) {
        $this->extractor = new LeadExtractor($classifier);
    }

    public function greeting(string $locale): array
    {
        return $this->reply(
            'opening',
            $locale === 'en'
                ? 'Hi, tell me what you need. I will ask only what is missing, then prepare the WhatsApp message for the shop.'
                : 'Ciao, dimmi pure cosa ti serve. Ti faccio solo le domande che mancano e poi preparo il messaggio WhatsApp per il negozio.',
            $this->agents->byKey('sarai')
        );
    }

    public function advance(array $conversation, string $input, ?string $choice = null): array
    {
        $locale = ($conversation['locale'] ?? 'it') === 'en' ? 'en' : 'it';
        $step = (string)($conversation['current_step'] ?? 'opening');
        $data = json_decode((string)($conversation['structured_data'] ?? '{}'), true) ?: [];
        $previousAgent = (string)($data['active_agent'] ?? 'sarai');
        $data = $this->extractor->extract($input !== '' ? $input : (string)$choice, $data);
        $this->captureContext($step, $data);

        $sector = (string)($data['detected_sector'] ?? $conversation['main_sector'] ?? 'guidance');
        $agent = $this->agents->forSector($sector);
        $data['active_agent'] = $agent['key'];
        $reply = match ($sector) {
            'tlc' => $this->planTlc($data, $agent),
            'energia_amministrativo' => $this->planEnergy($data, $agent),
            'informatica' => $this->planTech($data, $agent),
            default => $this->reply('clarify_need', 'Dimmi qual è il problema concreto: linea internet, tecnologia, bolletta o una pratica?', $agent),
        };
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

    private function planTlc(array $data, array $agent): array
    {
        if (empty($data['operator']) && empty($data['access_type'])) {
            $context = !empty($data['usage_context']['gaming'])
                ? 'Se giochi non guardiamo solo la velocità: contano soprattutto stabilità e ping.'
                : 'Per capire se il limite è la linea o la rete di casa mi serve un dato.';

            return $this->reply('tlc_operator', $context . ' Che operatore hai adesso?', $agent, ['operator_or_access_type']);
        }
        if (empty($data['scope_declared'])) {
            $prefix = trim(implode(' ', array_filter([$data['operator'] ?? null, $data['access_type'] ?? null])));
            return $this->reply('tlc_scope', 'Ok, ' . $prefix . '. Ti succede solo durante il gioco o la connessione è lenta anche con streaming e navigazione?', $agent, ['scope']);
        }
        if (empty($data['urgency'])) {
            return $this->reply('urgency', 'Chiaro. Quanto ti sta creando problemi: è una verifica con calma o ti sta bloccando?', $agent, ['urgency']);
        }

        return $this->phoneOrReady($data, $agent, 'Ho già segnato il problema di connessione' . (!empty($data['operator']) ? ' con ' . $data['operator'] : '') . '.');
    }

    private function planEnergy(array $data, array $agent): array
    {
        if (empty($data['trigger'])) {
            return $this->reply('energy_reason', 'Dimmi il punto principale: spesa troppo alta, bolletta aumentata, proposta ricevuta oppure una pratica da sistemare?', $agent, ['reason']);
        }
        if (empty($data['urgency'])) {
            $context = ($data['customer_type'] ?? '') === 'business' ? 'Ho segnato che riguarda la tua attività.' : 'Ok, ho segnato il motivo.';
            return $this->reply('urgency', $context . ' Quanto è urgente?', $agent, ['urgency']);
        }

        return $this->phoneOrReady($data, $agent, 'Ho già il quadro essenziale della richiesta.');
    }

    private function planTech(array $data, array $agent): array
    {
        if (empty($data['tech_detail_declared'])) {
            return $this->reply('tech_detail', 'Per capire come intervenire senza farti perdere tempo: cosa succede esattamente al dispositivo?', $agent, ['device_detail']);
        }
        if (empty($data['urgency'])) {
            return $this->reply('urgency', 'Ok, chiaro. Ti sta bloccando adesso oppure possiamo ragionarci con calma?', $agent, ['urgency']);
        }

        return $this->phoneOrReady($data, $agent, 'Ho segnato il problema tecnico e la priorità.');
    }

    private function phoneOrReady(array $data, array $agent, string $prefix): array
    {
        if (empty($data['phone'])) {
            return $this->reply('phone', $prefix . ' Mi manca solo un numero WhatsApp per passarti al negozio con il riepilogo pronto.', $agent, ['phone']);
        }

        return $this->reply('ready', 'Perfetto, ho abbastanza per non farti ripartire da zero. Ti apro WhatsApp con il riepilogo già pronto.', $agent, [], true);
    }

    private function captureContext(string $step, array &$data): void
    {
        if ($step === 'tlc_scope') {
            $data['scope_declared'] = true;
        }
        if ($step === 'tech_detail') {
            $data['tech_detail_declared'] = true;
        }
    }

    private function score(array $data, bool $ready): int
    {
        $score = 15;
        foreach (['detected_sector', 'need_summary', 'operator', 'access_type', 'urgency', 'phone', 'customer_type', 'trigger'] as $field) {
            if (!empty($data[$field])) {
                $score += 10;
            }
        }

        return min(100, $ready ? max(80, $score) : $score);
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
