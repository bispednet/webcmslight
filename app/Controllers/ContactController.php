<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\Security\Csrf;
use App\Services\Security\SpamGuard;
use App\Support\Flash;
use App\Support\Sanitizer;
use App\Support\Session;
use App\Support\Validator;
use PDO;

final class ContactController extends Controller
{
    public function submit(): void
    {
        Session::ensureStarted();

        $token = $_POST['csrf_token'] ?? null;
        if (!Csrf::verify(is_string($token) ? $token : null)) {
            Flash::set('contact_error', 'Sessione scaduta. Riprova.');
            $this->redirect('/contatti');
        }

        $spam = SpamGuard::check($_POST, $_SERVER['REMOTE_ADDR'] ?? null);
        if (!$spam['ok']) {
            SpamGuard::logBlocked('contact', $spam['reason'], $_SERVER['REMOTE_ADDR'] ?? null, $_POST);
            if ($spam['silent']) {
                // Non riveliamo la difesa al bot: finto successo.
                Flash::set('contact_success', 'Richiesta ricevuta. Ti risponderemo appena possibile.');
            } else {
                Flash::set('contact_error', 'Verifica antispam non superata. Riprova.');
            }
            $this->redirect('/contatti');
        }

        $lastSubmit = (int)($_SESSION['last_contact_submit'] ?? 0);
        if ($lastSubmit > 0 && time() - $lastSubmit < 45) {
            Flash::set('contact_error', 'Attendi qualche secondo prima di inviare una nuova richiesta.');
            $this->redirect('/contatti');
        }

        $sanitized = Sanitizer::clean($_POST, [
            'name' => 'string',
            'email' => 'email',
            'phone' => 'string',
            'topic' => 'string',
            'message' => 'text',
        ]);

        $errors = Validator::validate($sanitized, [
            'name' => ['required' => true, 'max' => 120],
            'email' => ['required' => true, 'email' => true, 'max' => 150],
            'message' => ['required' => true, 'max' => 2000],
        ]);

        if ($errors) {
            Flash::set('contact_error', reset($errors));
            $this->redirect('/contatti');
        }

        $details = [];
        if (!empty($sanitized['phone'])) {
            $details[] = 'Telefono: ' . $sanitized['phone'];
        }
        if (!empty($sanitized['topic'])) {
            $details[] = 'Tipo richiesta: ' . $sanitized['topic'];
        }
        $message = trim(implode("\n", $details) . "\n\n" . $sanitized['message']);

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO contact_messages (name, email, message, ip_address, user_agent, status)
             VALUES (:name, :email, :message, :ip, :agent, :status)'
        );

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ipBinary = $ip ? @inet_pton($ip) : null;

        $stmt->execute([
            'name' => $sanitized['name'],
            'email' => $sanitized['email'],
            'message' => $message,
            'ip' => $ipBinary,
            'agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
            'status' => 'new',
        ]);

        $_SESSION['last_contact_submit'] = time();

        // Email notification to store
        $this->sendNotification($sanitized['name'], $sanitized['email'], $message);

        Flash::set('contact_success', 'Richiesta ricevuta. Ti risponderemo appena possibile.');
        $this->redirect('/contatti');
    }

    public function withdrawal(): void
    {
        Session::ensureStarted();

        $token = $_POST['csrf_token'] ?? null;
        if (!Csrf::verify(is_string($token) ? $token : null)) {
            Flash::set('withdrawal_error', 'Sessione scaduta. Riprova.');
            $this->redirect('/recesso');
        }

        $spam = SpamGuard::check($_POST, $_SERVER['REMOTE_ADDR'] ?? null);
        if (!$spam['ok']) {
            SpamGuard::logBlocked('withdrawal', $spam['reason'], $_SERVER['REMOTE_ADDR'] ?? null, $_POST);
            if ($spam['silent']) {
                Flash::set('withdrawal_success', 'Richiesta di recesso ricevuta. Ti invieremo conferma appena possibile.');
            } else {
                Flash::set('withdrawal_error', 'Verifica antispam non superata. Riprova.');
            }
            $this->redirect('/recesso');
        }

        $lastSubmit = (int)($_SESSION['last_withdrawal_submit'] ?? 0);
        if ($lastSubmit > 0 && time() - $lastSubmit < 45) {
            Flash::set('withdrawal_error', 'Attendi qualche secondo prima di inviare una nuova richiesta.');
            $this->redirect('/recesso');
        }

        $sanitized = Sanitizer::clean($_POST, [
            'name' => 'string',
            'email' => 'email',
            'contract_ref' => 'string',
            'message' => 'text',
        ]);

        $errors = Validator::validate($sanitized, [
            'name' => ['required' => true, 'max' => 120],
            'email' => ['required' => true, 'email' => true, 'max' => 150],
            'contract_ref' => ['required' => true, 'max' => 180],
            'message' => ['max' => 1200],
        ]);

        if ($errors) {
            Flash::set('withdrawal_error', reset($errors));
            $this->redirect('/recesso');
        }

        $submittedAt = date('Y-m-d H:i:s');
        $message = trim(
            "DICHIARAZIONE DI RECESSO ONLINE\n"
            . "Data e ora trasmissione: {$submittedAt}\n"
            . "Nome consumatore: {$sanitized['name']}\n"
            . "Email per conferma: {$sanitized['email']}\n"
            . "Contratto/ordine/prodotto: {$sanitized['contract_ref']}\n\n"
            . "Dichiarazione: il consumatore comunica la decisione di recedere dal contratto indicato.\n\n"
            . "Note del consumatore:\n" . trim((string)($sanitized['message'] ?? ''))
        );

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO contact_messages (name, email, message, ip_address, user_agent, status)
             VALUES (:name, :email, :message, :ip, :agent, :status)'
        );

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt->execute([
            'name' => $sanitized['name'],
            'email' => $sanitized['email'],
            'message' => $message,
            'ip' => $ip ? @inet_pton($ip) : null,
            'agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
            'status' => 'new',
        ]);

        $_SESSION['last_withdrawal_submit'] = time();
        $this->sendWithdrawalReceipt($sanitized['name'], $sanitized['email'], $message, $submittedAt);

        Flash::set('withdrawal_success', 'Recesso trasmesso. Ti abbiamo inviato una ricevuta con contenuto, data e ora della richiesta.');
        $this->redirect('/recesso');
    }

    private function sendNotification(string $name, string $from, string $message): void
    {
        $name = $this->safeHeaderValue($name);
        $from = $this->safeHeaderValue($from);
        $config = require dirname(__DIR__, 2) . '/.env.php';
        $adminEmails = $config['admin_emails'] ?? ['negozio@bisped.net'];
        if (is_array($adminEmails) && isset($adminEmails[0])) {
            $to = $adminEmails[0];
        } else {
            $to = 'negozio@bisped.net';
        }

        $subject  = '[bisp&d] Nuova richiesta da ' . $name;
        $body     = "Hai ricevuto una nuova richiesta dal sito bisped.net\n\n";
        $body    .= "Nome: $name\n";
        $body    .= "Email: $from\n\n";
        $body    .= "Messaggio:\n$message\n";
        $body    .= "\n---\nbisped.net contact form";

        $headers  = "From: noreply@bisped.net\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "X-Mailer: bisp&d CMS\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8\r\n";

        @mail($to, $subject, $body, $headers);

        // Also send confirmation to sender
        $confirm  = "Ciao $name,\n\nAbbiamo ricevuto la tua richiesta e ti risponderemo al più presto.\n\n";
        $confirm .= "Il tuo messaggio:\n$message\n\n";
        $confirm .= "---\nbisp&d — Piombino (LI)\nTel: 0565 31136 — WhatsApp: 334 658 2116\nnegozio@bisped.net\nbisped.net";
        $hc  = "From: bisp&d <negozio@bisped.net>\r\n";
        $hc .= "Content-Type: text/plain; charset=utf-8\r\n";
        @mail($from, 'Conferma ricezione — bisp&d', $confirm, $hc);
    }

    private function sendWithdrawalReceipt(string $name, string $from, string $message, string $submittedAt): void
    {
        $name = $this->safeHeaderValue($name);
        $from = $this->safeHeaderValue($from);
        $config = require dirname(__DIR__, 2) . '/.env.php';
        $adminEmails = $config['admin_emails'] ?? ['negozio@bisped.net'];
        $to = is_array($adminEmails) && isset($adminEmails[0]) ? (string)$adminEmails[0] : 'negozio@bisped.net';

        $subject = '[bisp&d] Recesso online da ' . $name;
        $body = "Nuova dichiarazione di recesso ricevuta dal sito bisped.net\n\n{$message}\n\n---\nFunzione online /recesso";
        $headers  = "From: noreply@bisped.net\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "X-Mailer: bisp&d CMS\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
        @mail($to, $subject, $body, $headers);

        $confirm  = "Ciao $name,\n\n";
        $confirm .= "abbiamo ricevuto la tua dichiarazione di recesso online in data {$submittedAt}.\n";
        $confirm .= "Questa ricevuta riporta il contenuto trasmesso:\n\n{$message}\n\n";
        $confirm .= "La richiesta sara' verificata secondo le condizioni applicabili e il Codice del Consumo.\n\n";
        $confirm .= "---\nbisp&d s.r.l.\nPiazza della Costituzione, 68 - 57025 Piombino (LI)\n";
        $confirm .= "Tel: 0565 31136 - negozio@bisped.net - PEC: bisped@pec.it\n";
        $hc  = "From: bisp&d <negozio@bisped.net>\r\n";
        $hc .= "Content-Type: text/plain; charset=utf-8\r\n";
        @mail($from, 'Ricevuta recesso online — bisp&d', $confirm, $hc);
    }

    private function safeHeaderValue(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }
}
