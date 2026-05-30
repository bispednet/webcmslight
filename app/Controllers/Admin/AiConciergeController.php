<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use PDO;

final class AiConciergeController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function index(): void
    {
        $stats = $this->db->query(
            "SELECT COUNT(*) total,
             SUM(status='qualified') qualified,
             SUM(status='handed_to_whatsapp') handed_off,
             ROUND(AVG(lead_score)) average_score
             FROM ai_conversations"
        )->fetch() ?: [];
        $conversations = $this->db->query(
            'SELECT c.*, l.assigned_to FROM ai_conversations c
             LEFT JOIN ai_leads l ON l.conversation_id=c.id
             ORDER BY c.created_at DESC LIMIT 150'
        )->fetchAll() ?: [];

        $this->view('admin/ai-concierge/index', [
            'title' => 'AI Concierge',
            'stats' => $stats,
            'conversations' => $conversations,
        ]);
    }

    public function show(string $id): void
    {
        $stmt = $this->db->prepare('SELECT * FROM ai_conversations WHERE id=:id');
        $stmt->execute(['id' => (int)$id]);
        $conversation = $stmt->fetch();
        if (!$conversation) {
            http_response_code(404);
            echo 'Conversazione non trovata';
            return;
        }
        $stmt = $this->db->prepare('SELECT * FROM ai_messages WHERE conversation_id=:id ORDER BY id');
        $stmt->execute(['id' => (int)$id]);
        $messages = $stmt->fetchAll() ?: [];
        $stmt = $this->db->prepare('SELECT * FROM ai_quotes WHERE conversation_id=:id ORDER BY id');
        $stmt->execute(['id' => (int)$id]);
        $quotes = $stmt->fetchAll() ?: [];

        $this->view('admin/ai-concierge/show', compact('conversation', 'messages', 'quotes') + ['title' => 'Conversazione AI']);
    }
}
