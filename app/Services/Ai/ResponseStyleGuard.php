<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class ResponseStyleGuard
{
    /** @var string[] */
    private const FORBIDDEN = [
        'Capisco perfettamente',
        'Gentile cliente',
        'La ringraziamo',
        'Siamo lieti',
        'Assistente digitale autorizzato',
        'Configurata sul metodo',
        'Tre strade sensate',
        'Essenziale',
        'Intelligente',
        'Completa',
        'Posso usare queste risposte',
        'Sì, prepara il riepilogo',
        'Quanto è urgente?',
        'Scrivi come parleresti al banco',
        'Al resto ci pensa',
        'Ti trasferisco',
        'Passo la parola',
        'Come posso aiutarti',
    ];

    /** @var array<string,string> */
    private const REPLACEMENTS = [
        'Capisco perfettamente' => 'Chiaro',
        'Gentile cliente' => '',
        'La ringraziamo' => '',
        'Siamo lieti' => '',
        'Come posso aiutarti' => 'Dimmi',
    ];

    public function violatesPublicTone(string $message): bool
    {
        $lower = mb_strtolower($message, 'UTF-8');
        foreach (self::FORBIDDEN as $phrase) {
            if (str_contains($lower, mb_strtolower($phrase, 'UTF-8'))) {
                return true;
            }
        }

        return false;
    }

    public function cleanCustomerMessage(string $message): string
    {
        foreach (self::REPLACEMENTS as $forbidden => $replacement) {
            $message = str_ireplace($forbidden, $replacement, $message);
        }
        // Remove leftover double spaces/commas from replacements
        $message = preg_replace('/\s{2,}/', ' ', $message) ?? $message;
        $message = preg_replace('/,\s*,/', ',', $message) ?? $message;

        return trim($message);
    }

    public function fallback(string $sector): string
    {
        return match ($sector) {
            'tlc' => 'Ok, ho capito il problema di connessione. Mi serve solo l\'ultimo dato utile e poi ti passo al negozio con il riepilogo.',
            'energia_amministrativo' => 'Ok, qui prima va capita la situazione reale. Ti chiedo solo il dato che manca e poi passo tutto al negozio.',
            'informatica' => 'Ok, qui serve capire se è problema software, hardware o configurazione. Ti chiedo solo il punto che manca e poi ti passo al negozio.',
            default => 'Ok. Ti chiedo solo una cosa e poi ti passo al negozio con il riepilogo.',
        };
    }
}
