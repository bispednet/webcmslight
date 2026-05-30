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
        $agent = $this->agents->byKey('sarai');

        return $this->reply(
            'opening',
            $locale === 'en'
                ? 'Hi, I am SarAI, Bisped digital assistant. Tell me what you need: I will pick up the useful details and ask only what is missing.'
                : 'Ciao, sono SarAI, l’assistente digitale Bisped configurata sul metodo di lavoro di Sara. Raccontami pure di cosa hai bisogno: raccolgo io le informazioni utili e poi ti chiedo solo quello che manca.',
            [],
            false,
            $agent
        );
    }

    public function advance(array $conversation, string $input, ?string $choice = null): array
    {
        $locale = ($conversation['locale'] ?? 'it') === 'en' ? 'en' : 'it';
        $step = (string)($conversation['current_step'] ?? 'opening');
        $data = json_decode((string)($conversation['structured_data'] ?? '{}'), true) ?: [];
        $value = $choice ?: $input;
        $previousAgent = (string)($data['active_agent'] ?? 'sarai');

        $data = $this->extractor->extract($input !== '' ? $input : $value, $data);
        $this->captureContextualAnswer($step, $value, $data);
        $sector = (string)($data['detected_sector'] ?? $conversation['main_sector'] ?? 'guidance');
        $agent = $this->agents->forSector($sector);
        $data['active_agent'] = $agent['key'];
        $transition = $previousAgent !== $agent['key']
            ? $this->agentTransition($previousAgent, $agent['key'], $locale)
            : null;

        if ($step === 'privacy_notice') {
            if ($value !== 'privacy_accept') {
                return $this->with([], $data, $this->reply('privacy_notice', $locale === 'en'
                    ? 'Before preparing the handoff, may I use your answers to organize the request for the shop?'
                    : 'Prima di preparare il passaggio, posso usare le tue risposte per ordinare la richiesta per il negozio?',
                    [['value' => 'privacy_accept', 'label' => $locale === 'en' ? 'Yes, continue' : 'Sì, continuiamo']], false, $agent));
            }

            return $this->with(['consent_privacy' => 1, 'current_step' => 'ready', 'status' => 'qualified'], $data, $this->reply(
                'ready',
                $locale === 'en' ? 'Perfect. Your summary is ready: continue on WhatsApp without starting again.' : 'Perfetto. Il riepilogo è pronto: passa su WhatsApp senza ricominciare da zero.',
                [], true, $agent
            ));
        }

        $reply = $sector === 'energia_amministrativo'
            ? $this->saraiNext($data, $locale, $agent)
            : $this->specialistNext($sector, $data, $locale, $agent);
        if ($transition !== null) {
            $reply['transition'] = $transition;
        }

        return $this->with([
            'main_sector' => $sector === 'guidance' ? null : $sector,
            'current_step' => $reply['step'],
            'lead_score' => $this->score($data, $reply['ready']),
            'urgency' => $data['urgency'] ?? null,
            'status' => $reply['step'] === 'ready' ? 'qualified' : 'open',
        ], $data, $reply);
    }

    private function saraiNext(array $data, string $locale, array $agent): array
    {
        if (empty($data['trigger'])) {
            return $this->reply('sarai_situation_intake', 'Fammi capire meglio: è successo qualcosa sulla bolletta o stai valutando perché ti hanno fatto una proposta?', $this->saraiOpeningChoices(), false, $agent);
        }
        if (empty($data['commodity'])) {
            return $this->reply('sarai_situation_intake', 'Facciamo ordine: parliamo di luce, gas, entrambi oppure di una pratica come voltura o subentro?', [
                ['value' => 'luce', 'label' => 'Luce'], ['value' => 'gas', 'label' => 'Gas'], ['value' => 'luce_gas', 'label' => 'Luce e gas'], ['value' => 'pratica', 'label' => 'Voltura o subentro'],
            ], false, $agent);
        }
        if (empty($data['home_type'])) {
            return $this->reply('sarai_home_profile', 'Prima capisco come vivi, poi ti consiglio. Raccontami meglio la tua abitazione: appartamento o villetta?', [], false, $agent);
        }
        if (!isset($data['family_size'])) {
            return $this->reply('sarai_family_profile', 'Quanti siete in famiglia?', [], false, $agent);
        }
        if (empty($data['devices_profile_declared'])) {
            return $this->reply('sarai_usage_profile', 'Avete climatizzatori, pompa di calore, piano a induzione, auto elettrica o altri consumi importanti?', [], false, $agent);
        }
        if (!isset($data['current_cost_amount']) && empty($data['current_cost_unknown_declared'])) {
            return $this->reply('sarai_current_cost', 'Quanto stai pagando adesso, anche a spanne? Va bene mese, bimestre o l’ultima bolletta che ricordi.', [], false, $agent);
        }
        if (!array_key_exists('has_competing_offer', $data)) {
            return $this->reply('sarai_existing_offer_check', 'Hai ricevuto una proposta scritta, solo una chiamata oppure non hai ancora nessuna proposta?', [
                ['value' => 'proposta_scritta', 'label' => 'Ho una proposta scritta'], ['value' => 'chiamata', 'label' => 'Solo una chiamata'], ['value' => 'nessuna_proposta', 'label' => 'Nessuna proposta'],
            ], false, $agent);
        }
        if (empty($data['primary_goal'])) {
            $warning = !empty($data['risk_flags']) ? 'Attenzione: prima va controllato se ti stanno confrontando il costo totale o solo una parte della bolletta. ' : '';
            return $this->reply('sarai_goal_definition', $warning . 'Tu cosa vuoi ottenere davvero?', [
                ['value' => 'risparmio', 'label' => 'Pagare meno'], ['value' => 'stabilita', 'label' => 'Stabilizzare la spesa'], ['value' => 'verifica_proposta', 'label' => 'Capire se la proposta è seria'], ['value' => 'pratica', 'label' => 'Sistemare una pratica'],
            ], false, $agent);
        }

        return $this->reply('privacy_notice', 'Ora ha senso ragionare su tre strade concrete. Prima di preparare il passaggio al negozio, posso usare le tue risposte per creare il riepilogo?', [
            ['value' => 'privacy_accept', 'label' => 'Sì, prepara il riepilogo'],
        ], false, $agent, true);
    }

    private function specialistNext(string $sector, array $data, string $locale, array $agent): array
    {
        if (empty($data['need']) || mb_strlen(trim((string)$data['need']), 'UTF-8') < 8) {
            return $this->reply('specialist_need', $locale === 'en' ? 'Tell me what is happening in practical terms.' : 'Raccontami cosa succede in concreto, così evitiamo domande inutili.', [], false, $agent);
        }
        if (empty($data['specialist_detail_declared'])) {
            $message = $sector === 'informatica'
                ? 'Ti passo ad AndreAI. Per capire come intervenire senza farti perdere tempo: cosa succede esattamente quando provi a usare il dispositivo?'
                : 'Ti passo a SerenAI. Per inquadrare bene la verifica: che operatore hai adesso e qual è il problema concreto?';
            return $this->reply('specialist_detail', $message, [], false, $agent);
        }
        if (empty($data['urgency'])) {
            $message = $sector === 'informatica'
                ? 'Ok, ho il quadro iniziale. Ti sta bloccando adesso oppure possiamo ragionarci con calma?'
                : 'Ok, ho capito il punto. È una cosa urgente oppure stai valutando con calma?';
            return $this->reply('urgency', $message, [
                ['value' => 'bassa', 'label' => 'Valuto con calma'], ['value' => 'media', 'label' => 'Questa settimana'], ['value' => 'alta', 'label' => 'Urgente'], ['value' => 'immediata', 'label' => 'Mi sta bloccando'],
            ], false, $agent);
        }

        return $this->reply('privacy_notice', 'Ho abbastanza elementi per prepararti tre strade sensate. Posso usare queste risposte per creare il riepilogo da passare al negozio?', [
            ['value' => 'privacy_accept', 'label' => 'Sì, prepara il riepilogo'],
        ], false, $agent, true);
    }

    private function captureContextualAnswer(string $step, string $value, array &$data): void
    {
        if ($step === 'sarai_situation_intake' && in_array($value, ['luce', 'gas', 'luce_gas', 'pratica'], true)) {
            $data['commodity'] = $value;
        }
        if ($step === 'sarai_usage_profile') {
            $data['devices_profile_declared'] = true;
        }
        if ($step === 'sarai_existing_offer_check') {
            $data['has_competing_offer'] = $value !== 'nessuna_proposta';
            $data['offer_type'] = $value === 'proposta_scritta' ? 'scritta' : ($value === 'chiamata' ? 'telefonica' : 'nessuna');
            if ($value === 'chiamata') {
                $data['risk_flags'] = array_values(array_unique(array_merge((array)($data['risk_flags'] ?? []), ['solo_chiamata', 'nessuna_proposta_scritta'])));
            }
        }
        if ($step === 'sarai_goal_definition' && in_array($value, ['risparmio', 'stabilita', 'verifica_proposta', 'pratica'], true)) {
            $data['primary_goal'] = $value;
        }
        if ($step === 'urgency' && in_array($value, ['bassa', 'media', 'alta', 'immediata'], true)) {
            $data['urgency'] = $value;
        }
        if ($step === 'specialist_detail') {
            $data['specialist_detail_declared'] = true;
        }
    }

    private function saraiOpeningChoices(): array
    {
        return [
            ['value' => 'bolletta aumentata', 'label' => 'Bolletta aumentata'], ['value' => 'mi hanno chiamato', 'label' => 'Mi hanno chiamato'],
            ['value' => 'proposta scritta', 'label' => 'Ho una proposta scritta'], ['value' => 'voltura subentro', 'label' => 'Devo fare voltura/subentro'],
            ['value' => 'pago troppo', 'label' => 'Voglio capire se pago troppo'],
        ];
    }

    private function agentTransition(string $from, string $to, string $locale): string
    {
        $name = $this->agents->byKey($to)['name'];

        return $locale === 'en'
            ? "This is {$name}'s area. I am handing the conversation over without making you repeat anything."
            : "Questa è materia da {$name}. Ti passo subito a {$name}, senza farti ripetere quello che hai già scritto.";
    }

    private function score(array $data, bool $ready): int
    {
        $fields = ['trigger', 'commodity', 'home_type', 'family_size', 'devices_profile_declared', 'current_cost_amount', 'current_cost_unknown_declared', 'has_competing_offer', 'primary_goal', 'urgency'];
        $score = 10;
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $score += 7;
            }
        }

        return min(100, $ready ? max(70, $score) : $score);
    }

    private function reply(string $step, string $message, array $choices, bool $ready, array $agent, bool $quoteReady = false): array
    {
        return compact('step', 'message', 'choices', 'ready', 'quoteReady', 'agent');
    }

    private function with(array $updates, array $data, array $reply): array
    {
        $reply['updates'] = $updates + ['structured_data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];

        return $reply;
    }
}
