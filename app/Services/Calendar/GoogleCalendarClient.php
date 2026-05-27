<?php
declare(strict_types=1);

namespace App\Services\Calendar;

final class GoogleCalendarClient
{
    public function __construct(private array $config)
    {
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['client_id'])
            && !empty($this->config['client_secret'])
            && !empty($this->config['refresh_token']);
    }

    /**
     * @param array{summary:string,description:string,start:string,end:string,attendee_email:string,location:string,meeting_type:string} $event
     */
    public function createEvent(array $event): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Google Calendar non configurato: manca il refresh token.');
        }

        $token = $this->accessToken();
        $calendarId = rawurlencode((string)($this->config['calendar_id'] ?? 'primary'));
        $payload = [
            'summary' => $event['summary'],
            'description' => $event['description'],
            'location' => $event['location'],
            'start' => [
                'dateTime' => $event['start'],
                'timeZone' => $this->config['timezone'] ?? 'Europe/Rome',
            ],
            'end' => [
                'dateTime' => $event['end'],
                'timeZone' => $this->config['timezone'] ?? 'Europe/Rome',
            ],
            'attendees' => [
                ['email' => $event['attendee_email']],
            ],
        ];

        if (($event['meeting_type'] ?? '') === 'meet' && !empty($this->config['meet_enabled'])) {
            $payload['conferenceData'] = [
                'createRequest' => [
                    'requestId' => bin2hex(random_bytes(12)),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ],
            ];
        }

        $url = "https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events?conferenceDataVersion=1&sendUpdates=all";
        return $this->jsonRequest('POST', $url, $payload, $token);
    }

    private function accessToken(): string
    {
        $payload = [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'refresh_token' => $this->config['refresh_token'],
            'grant_type' => 'refresh_token',
        ];

        $response = $this->formRequest('https://oauth2.googleapis.com/token', $payload);
        if (empty($response['access_token'])) {
            throw new \RuntimeException('Impossibile ottenere access token Google Calendar.');
        }

        return (string)$response['access_token'];
    }

    private function formRequest(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        $decoded = $raw ? json_decode((string)$raw, true) : null;
        if ($status < 200 || $status >= 300 || !is_array($decoded)) {
            throw new \RuntimeException('Errore API Google OAuth.');
        }

        return $decoded;
    }

    private function jsonRequest(string $method, string $url, array $payload, string $token): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        $decoded = $raw ? json_decode((string)$raw, true) : null;
        if ($status < 200 || $status >= 300 || !is_array($decoded)) {
            throw new \RuntimeException('Errore creazione evento Google Calendar.');
        }

        return $decoded;
    }
}
