<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Controller;
use App\Core\Database;
use App\Services\Security\Csrf;
use App\Support\Flash;
use App\Support\Session;
use PDO;

final class AppointmentController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function show(): void
    {
        Session::ensureStarted();
        $this->view('public/appointments', [
            'title' => 'Prenota appuntamento',
            'csrfToken' => Csrf::token(),
            'success' => Flash::pull('appointments.success'),
            'error' => Flash::pull('appointments.error'),
        ]);
    }

    public function submit(): void
    {
        Session::ensureStarted();
        if (!Csrf::verify(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
            Flash::set('appointments.error', 'Sessione scaduta. Riprova.');
            $this->redirect('/appuntamenti');
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $service = trim((string)($_POST['service_type'] ?? 'Consulenza'));
        $meeting = trim((string)($_POST['meeting_type'] ?? 'negozio'));
        $date = trim((string)($_POST['date'] ?? ''));
        $time = trim((string)($_POST['time'] ?? ''));
        $notes = trim((string)($_POST['notes'] ?? ''));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $date === '' || $time === '') {
            Flash::set('appointments.error', 'Compila nome, email, data e orario.');
            $this->redirect('/appuntamenti');
        }

        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i', "{$date} {$time}", new \DateTimeZone('Europe/Rome'));
        if (!$start || $start < new \DateTimeImmutable('now', new \DateTimeZone('Europe/Rome'))) {
            Flash::set('appointments.error', 'Scegli una data futura valida.');
            $this->redirect('/appuntamenti');
        }

        $config = Container::get('config', []);
        $duration = (int)($config['calendar']['default_duration_minutes'] ?? 30);
        $end = $start->modify('+' . max(15, $duration) . ' minutes');

        $stmt = $this->db->prepare(
            'INSERT INTO appointment_requests
                (name,email,phone,service_type,meeting_type,starts_at,ends_at,notes,status)
             VALUES (:name,:email,:phone,:service,:meeting,:starts,:ends,:notes,:status)'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'service' => $service,
            'meeting' => $meeting,
            'starts' => $start->format('Y-m-d H:i:s'),
            'ends' => $end->format('Y-m-d H:i:s'),
            'notes' => $notes,
            'status' => 'pending',
        ]);

        Flash::set('appointments.success', 'Richiesta ricevuta. Ti confermiamo l’appuntamento appena verificata la disponibilita in agenda.');
        $this->redirect('/appuntamenti');
    }
}
