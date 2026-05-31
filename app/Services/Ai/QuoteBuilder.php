<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class QuoteBuilder
{
    public function build(string $sector, ?string $condition, array $data = []): array
    {
        if ($sector === 'energia_amministrativo') {
            $business = ($data['customer_type'] ?? '') === 'business';
            return [
                ['level' => 'base', 'title' => 'Controllo minimo', 'summary' => $business ? 'Verifica della spesa attuale e dei dati base della fornitura.' : 'Verifica della bolletta o della proposta ricevuta, senza cambiare nulla alla cieca.', 'items' => ['controllo situazione attuale'], 'condition' => null],
                ['level' => 'smart', 'title' => 'Verifica consigliata', 'summary' => $business ? 'Controllo contratto, consumi, potenza, aumenti e proposte ricevute.' : 'Analisi di consumi, casa e condizioni attuali per capire se ha davvero senso cambiare.', 'items' => ['lettura consumi', 'verifica condizioni'], 'condition' => $condition],
                ['level' => 'premium', 'title' => 'Gestione completa', 'summary' => $business ? 'Ricontatto prioritario e analisi con documento su canale diretto o appuntamento.' : 'Controllo della situazione, valutazione del contratto e passaggio ordinato al negozio.', 'items' => ['controllo completo', 'passaggio assistito'], 'condition' => $condition],
            ];
        }
        if ($sector === 'tlc') {
            $gaming = !empty($data['usage_context']['gaming']);
            $fwa = ($data['access_type'] ?? '') === 'FWA';
            return [
                ['level' => 'base', 'title' => 'Verifica minima', 'summary' => $fwa ? 'Controllo copertura alternativa alla FWA e primo inquadramento del problema di velocità o stabilità.' : 'Controllo copertura e primo inquadramento del problema di connessione.', 'items' => ['controllo copertura'], 'condition' => null],
                ['level' => 'smart', 'title' => 'Verifica consigliata', 'summary' => $gaming ? 'Controllo fibra o alternativa disponibile, ping, stabilità percepita, modem/router e uso gaming.' : 'Controllo tecnologia disponibile, stabilità e modem/router.', 'items' => ['verifica linea', 'verifica modem/router'], 'condition' => $condition],
                ['level' => 'premium', 'title' => 'Gestione completa', 'summary' => $gaming ? 'Verifica linea più rete interna per costruire una soluzione più stabile per gaming e uso casa.' : 'Verifica linea e rete interna per una connessione più stabile.', 'items' => ['verifica linea', 'rete interna'], 'condition' => $condition],
            ];
        }
        $items = match ($sector) {
            'informatica' => ['inquadramento del dispositivo e del blocco', 'verifica tecnica mirata', 'controllo dati e continuità operativa'],
            'business' => ['mappatura priorita', 'soluzione coordinata', 'piano operativo completo'],
            default => ['analisi richiesta', 'scelta guidata', 'verifica finale con il negozio'],
        };

        return [
            ['level' => 'base', 'title' => 'Verifica minima', 'summary' => 'Inquadramento del dispositivo e del problema segnalato.', 'items' => [$items[0]], 'condition' => null],
            ['level' => 'smart', 'title' => 'Verifica consigliata', 'summary' => 'Controllo tecnico mirato per distinguere problema software, hardware o configurazione.', 'items' => array_slice($items, 0, 2), 'condition' => $condition],
            ['level' => 'premium', 'title' => 'Gestione completa', 'summary' => 'Verifica del blocco, controllo dei dati importanti e percorso operativo più adatto.', 'items' => $items, 'condition' => $condition],
        ];
    }
}
