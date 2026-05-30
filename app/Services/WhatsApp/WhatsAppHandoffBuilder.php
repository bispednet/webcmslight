<?php
declare(strict_types=1);

namespace App\Services\WhatsApp;

final class WhatsAppHandoffBuilder
{
    public function build(string $number, array $conversation, array $quotes): array
    {
        $data = json_decode((string)($conversation['structured_data'] ?? '{}'), true) ?: [];
        $lines = [
            'Ciao bisp&d, ho completato il percorso Team AI Bisped.',
            '',
            'Settore: ' . ($conversation['main_sector'] ?: 'da verificare'),
            'Cliente: ' . ($conversation['customer_name'] ?: 'non indicato'),
            'Urgenza: ' . ($conversation['urgency'] ?: 'non indicata'),
            'Esigenza: ' . ($data['need'] ?? 'da approfondire'),
            '',
            'Percorsi valutati:',
        ];
        foreach ($quotes as $quote) {
            $lines[] = '- ' . $quote['title'] . ': ' . $quote['summary'];
        }
        $lines[] = '';
        $lines[] = 'Vorrei continuare con una verifica umana.';
        $text = implode("\n", $lines);

        return ['summary' => $text, 'url' => 'https://wa.me/' . preg_replace('/\D+/', '', $number) . '?text=' . rawurlencode($text)];
    }
}
