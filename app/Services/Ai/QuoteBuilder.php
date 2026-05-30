<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class QuoteBuilder
{
    public function build(string $sector, ?string $condition, array $data = []): array
    {
        if ($sector === 'energia_amministrativo') {
            $required = ['trigger', 'commodity', 'home_type', 'has_competing_offer', 'primary_goal'];
            foreach ($required as $field) {
                if (!array_key_exists($field, $data) || $data[$field] === '') {
                    throw new \InvalidArgumentException('SarAI: dati insufficienti per preparare le tre strade.');
                }
            }
            if (!isset($data['current_cost_amount']) && empty($data['current_cost_unknown_declared'])) {
                throw new \InvalidArgumentException('SarAI: manca il costo attuale o la dichiarazione che non è disponibile.');
            }
            return [
                ['level' => 'base', 'title' => 'Controllo minimo', 'summary' => 'Verifica della bolletta o della proposta ricevuta, senza cambiare nulla alla cieca.', 'items' => ['controllo situazione attuale'], 'condition' => null],
                ['level' => 'smart', 'title' => 'Verifica intelligente', 'summary' => 'Analisi di consumi, casa, dispositivi e condizioni attuali per capire se ha davvero senso cambiare.', 'items' => ['lettura consumi', 'verifica condizioni'], 'condition' => $condition],
                ['level' => 'premium', 'title' => 'Gestione completa', 'summary' => 'Controllo della situazione, valutazione del contratto e passaggio ordinato al negozio senza ricominciare da zero.', 'items' => ['controllo completo', 'passaggio assistito'], 'condition' => $condition],
            ];
        }
        $items = match ($sector) {
            'informatica' => ['verifica del problema', 'intervento mirato', 'protezione e continuità operativa'],
            'tlc' => ['verifica copertura e vincoli', 'scelta della linea adatta', 'controllo rete e servizi collegati'],
            'business' => ['mappatura priorita', 'soluzione coordinata', 'piano operativo completo'],
            default => ['analisi richiesta', 'scelta guidata', 'verifica finale con il negozio'],
        };

        return [
            ['level' => 'base', 'title' => 'Essenziale', 'summary' => 'Risolvere il punto principale senza aggiungere complessità.', 'items' => [$items[0]], 'condition' => null],
            ['level' => 'smart', 'title' => 'Intelligente', 'summary' => 'Affrontare la richiesta con una verifica più completa prima di decidere.', 'items' => array_slice($items, 0, 2), 'condition' => $condition],
            ['level' => 'premium', 'title' => 'Completa', 'summary' => 'Valutare anche cio che puo evitare problemi o costi successivi.', 'items' => $items, 'condition' => $condition],
        ];
    }
}
