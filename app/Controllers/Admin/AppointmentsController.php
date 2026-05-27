<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Container;
use App\Core\Controller;
use App\Core\Database;
use App\Services\Calendar\GoogleCalendarClient;
use App\Services\Security\Csrf;
use App\Support\Flash;
use PDO;

final class AppointmentsController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function index(): void
    {
        $rows = $this->db->query('SELECT * FROM appointment_requests ORDER BY starts_at DESC, id DESC LIMIT 200')->fetchAll() ?: [];
        $config = Container::get('config', []);
        $calendar = $this->calendarClient($config);

        $this->view('admin/appointments/index', [
            'title' => 'Appuntamenti',
            'appointments' => $rows,
            'calendarReady' => $calendar->isConfigured(),
            'notice' => Flash::pull('admin.appointments.notice'),
            'error' => Flash::pull('admin.appointments.error'),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function accept(string $id): void
    {
        $this->assertValidCsrf($_POST['csrf_token'] ?? null);
        $appointment = $this->find($id);
        if (!$appointment) {
            Flash::set('admin.appointments.error', 'Appuntamento non trovato.');
            $this->redirect('/admin/appointments');
        }

        $eventId = null;
        $meetUrl = null;
        try {
            $config = Container::get('config', []);
            $calendar = $this->calendarClient($config);
            if ($calendar->isConfigured()) {
                $event = $calendar->createEvent($this->eventPayload($appointment));
                $eventId = (string)($event['id'] ?? '');
                $meetUrl = (string)($event['hangoutLink'] ?? '');
            }
        } catch (\Throwable $e) {
            Flash::set('admin.appointments.error', 'Appuntamento salvato ma non sincronizzato con Google Calendar: ' . $e->getMessage());
        }

        $stmt = $this->db->prepare(
            "UPDATE appointment_requests
             SET status='confirmed', google_event_id=:event_id, meet_url=:meet_url
             WHERE id=:id"
        );
        $stmt->execute([
            'event_id' => $eventId,
            'meet_url' => $meetUrl,
            'id' => (int)$id,
        ]);

        Flash::set('admin.appointments.notice', $eventId ? 'Appuntamento confermato e inserito in Google Calendar.' : 'Appuntamento confermato. Configura il refresh token per sincronizzarlo su Google Calendar.');
        $this->redirect('/admin/appointments');
    }

    public function reject(string $id): void
    {
        $this->assertValidCsrf($_POST['csrf_token'] ?? null);
        $stmt = $this->db->prepare("UPDATE appointment_requests SET status='cancelled' WHERE id=:id");
        $stmt->execute(['id' => (int)$id]);
        Flash::set('admin.appointments.notice', 'Appuntamento annullato.');
        $this->redirect('/admin/appointments');
    }

    private function find(string $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM appointment_requests WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int)$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function calendarClient(array $config): GoogleCalendarClient
    {
        return new GoogleCalendarClient([
            'client_id' => $config['google']['client_id'] ?? '',
            'client_secret' => $config['google']['client_secret'] ?? '',
            'refresh_token' => $config['calendar']['google_refresh_token'] ?? '',
            'calendar_id' => $config['calendar']['calendar_id'] ?? 'primary',
            'timezone' => $config['calendar']['timezone'] ?? 'Europe/Rome',
            'meet_enabled' => $config['calendar']['meet_enabled'] ?? true,
        ]);
    }

    private function eventPayload(array $appointment): array
    {
        $name = (string)$appointment['name'];
        $meeting = (string)$appointment['meeting_type'];
        return [
            'summary' => 'bisp&d - ' . (string)$appointment['service_type'] . ' - ' . $name,
            'description' => "Richiesta dal sito bisped.net\n\nCliente: {$name}\nEmail: {$appointment['email']}\nTelefono: {$appointment['phone']}\nModalita: {$meeting}\n\nNote:\n{$appointment['notes']}",
            'start' => (new \DateTimeImmutable((string)$appointment['starts_at'], new \DateTimeZone('Europe/Rome')))->format(DATE_RFC3339),
            'end' => (new \DateTimeImmutable((string)$appointment['ends_at'], new \DateTimeZone('Europe/Rome')))->format(DATE_RFC3339),
            'attendee_email' => (string)$appointment['email'],
            'location' => $meeting === 'negozio' ? 'bisp&d, Piazza della Costituzione 68, Piombino' : ucfirst($meeting),
            'meeting_type' => $meeting,
        ];
    }

    private function assertValidCsrf(?string $token): void
    {
        if (Csrf::verify($token)) {
            return;
        }

        Flash::set('admin.appointments.error', 'Sessione scaduta, riprova.');
        $this->redirect('/admin/appointments');
    }
}
