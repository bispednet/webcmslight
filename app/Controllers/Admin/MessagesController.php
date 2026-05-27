<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Services\Security\Csrf;
use App\Support\Flash;
use PDO;

final class MessagesController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function index(): void
    {
        $stmt = $this->db->query('SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC LIMIT 200');

        $this->view('admin/messages/index', [
            'title' => 'Messaggi contatto',
            'messages' => $stmt->fetchAll() ?: [],
            'notice' => Flash::pull('admin.messages.notice'),
            'error' => Flash::pull('admin.messages.error'),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function markRead(string $id): void
    {
        $this->assertValidCsrf($_POST['csrf_token'] ?? null);
        $this->updateStatus($id, 'read', 'Messaggio segnato come letto.');
    }

    public function archive(string $id): void
    {
        $this->assertValidCsrf($_POST['csrf_token'] ?? null);
        $this->updateStatus($id, 'archived', 'Messaggio archiviato.');
    }

    private function updateStatus(string $id, string $status, string $notice): void
    {
        $stmt = $this->db->prepare('UPDATE contact_messages SET status = :status WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'id' => (int)$id,
        ]);

        Flash::set('admin.messages.notice', $notice);
        $this->redirect('/admin/messages');
    }

    private function assertValidCsrf(?string $token): void
    {
        if (Csrf::verify($token)) {
            return;
        }

        Flash::set('admin.messages.error', 'Sessione scaduta, riprova.');
        $this->redirect('/admin/messages');
    }
}
