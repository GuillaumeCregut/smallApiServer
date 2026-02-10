<?php

declare(strict_types=1);

namespace App\Services\Mailer;

use App\Interfaces\MailerInterface;
use App\Kernel\GetEnvDatas;
use App\Kernel\Logger;
use Exception;


/**
 * Classe d'envoi d'emails moderne respectant les standards actuels
 */
class Mailer implements MailerInterface
{
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUser;
    private string $smtpPass;
    private string $fromEmail;
    private string $fromName;
    private bool $useTLS;
    private int $timeout = 30;
    private mixed $socket = null;
    private int $maxAttachement;

    public function __construct(
        MailConfig $config,
        ?bool $useTLS = true,
        ?int $maxFiles = 10
    ) {
        $this->smtpHost = $config->smtpHost;
        $this->smtpPort = (int)$config->smtpPort;
        $this->smtpUser = $config->smtpUser;
        $this->smtpPass = $config->smtpPass;
        $this->fromEmail = $config->fromEmail;
        $this->fromName = $config->fromName;
        $this->useTLS = $useTLS;
        $maxAttachement = $maxFiles;
        $this->maxAttachement =  (int)$maxAttachement * 1024 * 1024;
    }

    /**
     * Envoie un email
     */
    public function sendEmail(
        string|array $to,
        string $subject,
        string $body,
        bool $isHtml = true,
        array $attachments = [],
        array $headers = [],
        string|array $cc = [],
        string|array $bcc = [],
        string $replyTo = ''
    ): bool {
        $newValues = $this->sanitizeValues($to, $subject, $attachments, $cc, $bcc, $replyTo, $headers);
        $to = $newValues['to'];
        $subject = $newValues['subject'];
        $attachments = $newValues['attachments'];
        $cc = $newValues['cc'];
        $bcc = $newValues['bcc'];
        $replyTo = $newValues['replyTo'];
        $headers = $newValues['headers'];
        try {
            $this->connect();
            $this->authenticate();

            $recipients = is_array($to) ? $to : [$to];
            $ccList = is_array($cc) ? $cc : (empty($cc) ? [] : [$cc]);
            $bccList = is_array($bcc) ? $bcc : (empty($bcc) ? [] : [$bcc]);

            // MAIL FROM
            $this->sendCommand("MAIL FROM:<{$this->fromEmail}>", 250);

            // RCPT TO - inclut TO, CC et BCC
            $allRecipients = array_merge($recipients, $ccList, $bccList);
            foreach ($allRecipients as $recipient) {
                $this->sendCommand("RCPT TO:<{$recipient}>", 250);
            }

            // DATA
            $this->sendCommand("DATA", 354);

            // Construction du message
            $message = $this->buildMessage($recipients, $subject, $body, $isHtml, $attachments, $headers, $ccList, $replyTo);

            // Envoi du message
            $this->sendCommand($message . "\r\n.", 250);

            // QUIT
            $this->sendCommand("QUIT", 221);

            $this->disconnect();

            return true;
        } catch (Exception $e) {
            $this->disconnect();
            Logger::error($this, "Erreur d'envoi d'email: " . $e->getMessage(), false, false);
            return false;
        }
    }

    /**
     * Établit la connexion SMTP
     */
    private function connect(): void
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            ]
        ]);

        $protocol = $this->useTLS ? 'tls://' : 'tcp://';
        $this->socket = @stream_socket_client(
            "{$protocol}{$this->smtpHost}:{$this->smtpPort}",
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->socket) {
            throw new Exception("Impossible de se connecter au serveur SMTP: $errstr ($errno)");
        }

        stream_set_timeout($this->socket, $this->timeout);
        $this->getResponse(220);

        // EHLO avec le nom de domaine
        $domain = parse_url($this->fromEmail, PHP_URL_HOST) ?? gethostname();
        $domain = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $domain);
        $this->sendCommand("EHLO {$domain}", 250);
        if ($this->useTLS) {
            $this->sendCommand("STARTTLS", 220);
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \Exception("Impossible d'activer TLS");
            }
            // Re-EHLO après STARTTLS (obligatoire selon la RFC)
            $this->sendCommand("EHLO {$domain}", 250);
        }
    }

    /**
     * Authentification SMTP
     */
    private function authenticate(): void
    {
        $this->sendCommand("AUTH LOGIN", 334);
        $this->sendCommand(base64_encode($this->smtpUser), 334);
        $this->sendCommand(base64_encode($this->smtpPass), 235);
    }

    /**
     * Construction du message email
     */
    private function buildMessage(
        array $recipients,
        string $subject,
        string $body,
        bool $isHtml,
        array $attachments,
        array $customHeaders,
        array $ccList = [],
        string $replyTo = ''
    ): string {
        $boundary = "----=_Part_" . md5(uniqid((string)time()));
        $boundaryAlt = "----=_Part_Alt_" . md5(uniqid((string)time()));
        // En-têtes obligatoires
        $headers = [
            "From: " . $this->formatEmail($this->fromEmail, $this->fromName),
            "To: " . implode(", ", array_map(fn($e) => $this->formatEmail($e), $recipients)),
            "Subject: " . $this->encodeHeader($subject),
            "Date: " . date('r'),
            "Message-ID: <" . md5(uniqid((string)time())) . "@" . $this->getDomain() . ">",
            "MIME-Version: 1.0",
            "X-Mailer: CustomPHPMailer/1.0",
            "X-Priority: 3"
        ];

        // Reply-To si défini
        if (!empty($replyTo)) {
            $headers[] = "Reply-To: " . $this->formatEmail($replyTo);
        }

        // CC si défini
        if (!empty($ccList)) {
            $headers[] = "Cc: " . implode(", ", array_map(fn($e) => $this->formatEmail($e), $ccList));
        }

        // Note: BCC n'est PAS inclus dans les headers (c'est le principe du BCC)

        // Headers personnalisés
        foreach ($customHeaders as $key => $value) {
            $headers[] = "$key: $value";
        }

        // Type de contenu selon attachments
        if (!empty($attachments)) {
            $headers[] = "Content-Type: multipart/mixed; boundary=\"$boundary\"";
        } elseif ($isHtml) {
            $headers[] = "Content-Type: multipart/alternative; boundary=\"$boundaryAlt\"";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
            $headers[] = "Content-Transfer-Encoding: quoted-printable";
        }

        $message = implode("\r\n", $headers) . "\r\n\r\n";

        // Corps du message
        if (!empty($attachments)) {
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: multipart/alternative; boundary=\"$boundaryAlt\"\r\n\r\n";
        }

        if ($isHtml) {
            // Version texte
            $message .= "--$boundaryAlt\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
            $message .= quoted_printable_encode(strip_tags($body)) . "\r\n\r\n";

            // Version HTML
            $message .= "--$boundaryAlt\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
            $message .= quoted_printable_encode($body) . "\r\n\r\n";
            $message .= "--$boundaryAlt--\r\n";
        } else {
            $message .= quoted_printable_encode($body) . "\r\n";
        }

        // Pièces jointes
        foreach ($attachments as $file) {
            if (is_file($file)) {
                $message .= "\r\n--$boundary\r\n";
                $message .= $this->attachFile($file);
            }
        }

        if (!empty($attachments)) {
            $message .= "\r\n--$boundary--";
        }

        return $message;
    }

    /**
     * Attache un fichier
     */
    private function attachFile(string $filePath): string
    {
        $filename = basename($filePath);
        $content = file_get_contents($filePath);
        $encoded = chunk_split(base64_encode($content));
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        return "Content-Type: $mimeType; name=\"$filename\"\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n" .
            $encoded;
    }

    /**
     * Formate une adresse email avec nom
     */
    private function formatEmail(string $email, string $name = ''): string
    {
        if (empty($name)) {
            return $email;
        }
        return $this->encodeHeader($name) . " <$email>";
    }

    /**
     * Encode un header en UTF-8
     */
    private function encodeHeader(string $text): string
    {
        if (mb_check_encoding($text, 'ASCII')) {
            return $text;
        }
        return "=?UTF-8?B?" . base64_encode($text) . "?=";
    }

    /**
     * Récupère le domaine de l'email
     */
    private function getDomain(): string
    {
        return explode('@', $this->fromEmail)[1] ?? 'localhost';
    }

    /**
     * Envoie une commande SMTP
     */
    private function sendCommand(string $command, int $expectedCode): void
    {
        fwrite($this->socket, $command . "\r\n");
        $this->getResponse($expectedCode);
    }

    /**
     * Lit la réponse du serveur
     */
    private function getResponse(int $expectedCode): string
    {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int)substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new Exception("Erreur SMTP: $response (attendu: $expectedCode)");
        }

        return $response;
    }

    /**
     * Ferme la connexion
     */
    private function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }

    private function sanitizeValues(string |array $to, string $subject, array $attachments, string|array $cc, string|array $bcc, string $replyTo, array $headers): array
    {
        $newBcc = [];
        $newCc = [];
        $newAttachments = [];
        $this->fromEmail = $this->sanitizeEmail($this->fromEmail);
        $subject = $this->sanitizeHeader($subject);
        $newHeader = [];
        foreach ($headers as $key => $value) {
            $key = $this->sanitizeHeader($key);
            $value = $this->sanitizeHeader($value);
            if (!is_null($key)  && !is_null($value)) {
                $newHeader[$key] = $value;
            }
        }
        $newTo = [];
        if (!empty($to)) {
            if (is_array($to)) {
                foreach ($to as $mail) {
                    $newTo[] = $this->sanitizeEmail($mail);
                }
            } else {
                $newTo[] = $this->sanitizeEmail($to);
            }
        }
        if (!empty($cc)) {
            if (is_array($cc)) {
                foreach ($cc as $mail) {
                    $newCc[] = $this->sanitizeEmail($mail);
                }
            } else {
                $newCc[] = $this->sanitizeEmail($cc);
            }
        }
        if (!empty($bcc)) {
            if (is_array($bcc)) {
                foreach ($bcc as $mail) {
                    $newBcc[] = $this->sanitizeEmail($mail);
                }
            } else {
                $newBcc[] = $this->sanitizeEmail($cc);
            }
        }
        if (!empty($replyTo)) {
            $replyTo = $this->sanitizeEmail($replyTo);
        }
        $newAttachments = $this->sanitizeAttachements($attachments);
        $attachSize = $this->validateAttachments($attachments);
        if($attachSize>$this->maxAttachement) {
            throw new Exception('Total files size exceed maximum mail limit');
        }
        return [
            'to' => $newTo,
            'subject' => $subject,
            'attachments' => $newAttachments,
            'cc' => $newCc,
            'bcc' => $newBcc,
            'replyTo' => $replyTo,
            'headers' => $newHeader,
        ];
    }

    private function validateAttachments(array $attachments): int
    {
       $totalSize = 0;
        
        foreach ($attachments as $file) {
            // Vérifier que le fichier est dans le répertoire autorisé
            $realPath = realpath($file);
            $totalSize += filesize($realPath);
        }
        return $totalSize;
    }
    
    private function sanitizeAttachements(array $attachments): array
    {
        if (empty($attachments)) {
            return [];
            }
            $returnArray = [];
            foreach ($attachments as $file) {
                $realPath = realpath($file);
            if ($realPath === false) {
                throw new Exception('Fichier introuvable');
            }
            if (!is_file($realPath) || !is_readable($realPath)) {
                throw new Exception('Fichier invalide ou non lisible');
            }
            //Supprimer les caractères interdits
            $file = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file);
            $returnArray[] = $file;
        }
        return $returnArray;
    }

    private function sanitizeEmail(string $email): string
    {
        // Validation de la longueur
        if (strlen($email) > 254) {
            throw new Exception('Email trop long');
        }
        // Supprimer TOUS les caractères de contrôle
        $email = preg_replace('/[\r\n\t\0]/', '', $email);

        if (!$email = filter_var($email, FILTER_SANITIZE_EMAIL, FILTER_FLAG_EMPTY_STRING_NULL)) {
            throw new Exception('Email invalide: ');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email invalide: ' . $email);
        }
        return $email;
    }
    
    private function sanitizeHeader(string $header): ?string
    {
        $forbiddenHeaders = ['bcc', 'x-confirm-reading-to', 'disposition-notification-to'];
        if (in_array(strtolower($header), $forbiddenHeaders)) {
            return null;
        }
        return str_replace(["\r", "\n", "\0"], '', $header);
    }
}
