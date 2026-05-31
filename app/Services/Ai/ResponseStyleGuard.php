<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class ResponseStyleGuard
{
    public function validateAgentMessage(string $agent, string $message, string $fallback): string
    {
        if ($message === '' || mb_strlen($message, 'UTF-8') > 420 || substr_count($message, '?') > 1) {
            return $fallback;
        }
        if (preg_match('/capisco perfettamente|comprendo la tua esigenza|sono qui per aiutarti|gentile cliente|siamo lieti|la ringraziamo|migliore offerta|risparmio garantito|offerta imperdibile|posso usare queste risposte|tre strade sensate|soluzione essenziale|soluzione intelligente|soluzione completa|scrivi come parleresti al banco|al resto ci pensa sarai|necessità di revisione|ti contatt|\b(?:sua|suo|sue|suoi|lei)\b|privacy_notice|tlc_scope|handoff_ready/ui', $message)) {
            return $fallback;
        }

        return $message;
    }
}
