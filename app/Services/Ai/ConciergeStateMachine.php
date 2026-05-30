<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class ConciergeStateMachine
{
    public function __construct(
        private AgentPersonaRegistry $agents,
        private NeedClassifier $classifier
    ) {
    }

    public function greeting(string $locale): array
    {
        return $this->reply(
            'privacy_notice',
            $locale === 'en'
                ? 'Hi, I am Team AI Bisped, Bisped\'s authorized digital assistant. I will ask a few focused questions and prepare three sensible paths before the human handoff. May I use your answers to prepare the request?'
                : 'Ciao, sono Team AI Bisped, l’assistente digitale autorizzato Bisped. Ti faccio poche domande mirate e preparo tre strade sensate prima del passaggio umano. Posso usare le tue risposte per preparare la richiesta?',
            [['value' => 'privacy_accept', 'label' => $locale === 'en' ? 'Yes, continue' : 'Sì, continuiamo']]
        );
    }

    public function advance(array $conversation, string $input, ?string $choice = null): array
    {
        $locale = ($conversation['locale'] ?? 'it') === 'en' ? 'en' : 'it';
        $step = (string)($conversation['current_step'] ?? 'privacy_notice');
        $data = json_decode((string)($conversation['structured_data'] ?? '{}'), true) ?: [];
        $value = $choice ?: $input;

        if ($step === 'privacy_notice') {
            if ($value !== 'privacy_accept') {
                return $this->greeting($locale);
            }
            return $this->with(['consent_privacy' => 1, 'current_step' => 'sector_selection'], $data, $this->reply(
                'sector_selection',
                $locale === 'en' ? 'Where should we start?' : 'Da dove partiamo?',
                $this->sectors($locale)
            ));
        }

        if ($step === 'sector_selection') {
            $sector = in_array($value, ['informatica', 'tlc', 'energia_amministrativo', 'business'], true)
                ? $value
                : $this->classifier->classify($input);
            if ($sector === 'guidance') {
                return $this->reply('sector_selection', $locale === 'en'
                    ? 'Let us start from the problem: is it technology, connectivity, energy or a business need?'
                    : 'Partiamo dal problema: riguarda tecnologia, connettività, energia o un’esigenza aziendale?', $this->sectors($locale));
            }
            $agent = $this->agents->forSector($sector);
            return $this->with(['main_sector' => $sector, 'current_step' => 'customer_type', 'lead_score' => 15], $data, $this->reply(
                'customer_type',
                ($locale === 'en' ? "{$agent['name']} will guide this request. Is it for a private customer or a business?" : "{$agent['name']} prende in carico la richiesta. Parliamo di un privato o di un’attività?"),
                [['value' => 'privato', 'label' => $locale === 'en' ? 'Private customer' : 'Privato'], ['value' => 'business', 'label' => 'Business']]
            ));
        }

        if ($step === 'customer_type') {
            $type = $value === 'business' ? 'business' : 'privato';
            return $this->with(['customer_type' => $type, 'current_step' => 'need_discovery', 'lead_score' => 25], $data, $this->reply(
                'need_discovery',
                $locale === 'en' ? 'Tell me the concrete problem or goal in one message.' : 'Raccontami in un messaggio il problema concreto o l’obiettivo.'
            ));
        }

        if ($step === 'need_discovery') {
            $data['need'] = $input;
            return $this->with(['current_step' => 'urgency', 'lead_score' => 45], $data, $this->reply(
                'urgency',
                $locale === 'en' ? 'How urgent is it?' : 'Quanto è urgente?',
                [['value' => 'bassa', 'label' => $locale === 'en' ? 'No rush' : 'Senza fretta'], ['value' => 'media', 'label' => $locale === 'en' ? 'This week' : 'Questa settimana'], ['value' => 'alta', 'label' => $locale === 'en' ? 'Urgent' : 'Urgente'], ['value' => 'immediata', 'label' => $locale === 'en' ? 'Blocking issue' : 'Mi sta bloccando']]
            ));
        }

        if ($step === 'urgency') {
            $urgency = in_array($value, ['bassa', 'media', 'alta', 'immediata'], true) ? $value : 'media';
            return $this->with(['urgency' => $urgency, 'current_step' => 'contact_name', 'lead_score' => $urgency === 'immediata' ? 70 : 60], $data, $this->reply(
                'contact_name',
                $locale === 'en' ? 'I can now prepare the handoff. What name should the shop use? You can also skip this.' : 'Posso preparare il passaggio. Che nome deve usare il negozio? Puoi anche saltare.',
                [['value' => 'skip', 'label' => $locale === 'en' ? 'Skip' : 'Salta']]
            ));
        }

        if ($step === 'contact_name') {
            $name = $value === 'skip' ? null : mb_substr($input, 0, 150, 'UTF-8');
            return $this->with(['customer_name' => $name, 'current_step' => 'contact_phone'], $data, $this->reply(
                'contact_phone',
                $locale === 'en' ? 'If useful, leave a phone number for the shop. Otherwise continue without it.' : 'Se ti è utile, lascia un telefono per il negozio. Altrimenti continua senza.',
                [['value' => 'skip', 'label' => $locale === 'en' ? 'Continue without phone' : 'Continua senza telefono']]
            ));
        }

        if ($step === 'contact_phone') {
            $phone = $value === 'skip' ? null : preg_replace('/[^\d+ ]/', '', $input);
            return $this->with(['customer_phone' => $phone ?: null, 'current_step' => 'ready', 'lead_score' => $phone ? 85 : 70, 'status' => 'qualified'], $data, $this->reply(
                'ready',
                $locale === 'en' ? 'The request is ready. I prepared three paths: essential, smart and complete. Continue on WhatsApp: the shop will receive the summary without making you start again.' : 'La richiesta è pronta. Ho preparato tre strade: essenziale, intelligente e completa. Continua su WhatsApp: il negozio riceverà il riepilogo senza farti ripartire da zero.',
                [],
                true
            ));
        }

        return $this->reply('ready', $locale === 'en' ? 'Your summary is ready for WhatsApp.' : 'Il riepilogo e pronto per WhatsApp.', [], true);
    }

    private function sectors(string $locale): array
    {
        return [
            ['value' => 'informatica', 'label' => $locale === 'en' ? 'Technology and support' : 'Tecnologia e assistenza'],
            ['value' => 'tlc', 'label' => $locale === 'en' ? 'Internet and mobile' : 'Fibra, internet e mobile'],
            ['value' => 'energia_amministrativo', 'label' => $locale === 'en' ? 'Energy and bills' : 'Energia e bollette'],
            ['value' => 'business', 'label' => $locale === 'en' ? 'Business needs' : 'Esigenze aziendali'],
        ];
    }

    private function reply(string $step, string $message, array $choices = [], bool $ready = false): array
    {
        return compact('step', 'message', 'choices', 'ready');
    }

    private function with(array $updates, array $data, array $reply): array
    {
        $reply['updates'] = $updates + ['structured_data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];

        return $reply;
    }
}
