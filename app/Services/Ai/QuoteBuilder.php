<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class QuoteBuilder
{
    public function build(string $sector, ?string $condition): array
    {
        $items = match ($sector) {
            'informatica' => ['verifica del problema', 'intervento mirato', 'protezione e continuità operativa'],
            'tlc' => ['verifica copertura e vincoli', 'scelta della linea adatta', 'controllo rete e servizi collegati'],
            'energia_amministrativo' => ['lettura della situazione attuale', 'verifica condizioni', 'gestione pratica assistita'],
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
