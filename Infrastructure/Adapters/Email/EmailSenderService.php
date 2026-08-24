<?php

declare(strict_types=1);

require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

require_once __DIR__ . '/../../../Application/Ports/Out/EmailSenderPort.php';
require_once __DIR__ . '/../../../Domain/Models/EmailDestinationModel.php';
require_once __DIR__ . '/../../../Domain/Exceptions/EmailSenderException.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

final class EmailSenderService implements EmailSenderPort
{
    private string $host;
    private string $username;
    private string $password;
    private string $fromAddress;
    private string $fromName;
    private string $encryption;
    private int $port;
    private string $outboxDir;
    private bool $strictMode;
    private string $brevoApiKey;
    private string $resendApiKey;
    private string $lastDeliveryChannel = 'none';

    public function __construct(
        string $host,
        string $username,
        string $password,
        string $fromAddress,
        string $fromName,
        string $encryption = 'tls',
        int $port = 587,
        ?string $outboxDir = null,
        bool $strictMode = false,
        string $brevoApiKey = '',
        string $resendApiKey = ''
    ) {
        $this->host = trim($host);
        $this->username = trim($username);
        $this->password = trim($password);
        $this->fromAddress = trim($fromAddress);
        $this->fromName = trim($fromName);
        $this->encryption = trim($encryption);
        $this->port = $port;
        $this->outboxDir = $outboxDir ?? dirname(__DIR__, 3) . '/storage/logs/emails';
        $this->strictMode = $strictMode;
        $this->brevoApiKey = trim($brevoApiKey);
        $this->resendApiKey = trim($resendApiKey);
    }

    public function getLastDeliveryChannel(): string
    {
        return $this->lastDeliveryChannel;
    }

    public function send(EmailDestinationModel $destination): void
    {
        // 1. Transporte HTTPS (Puerto 443) ideal para Render Free / Cloud donde TCP 587/465 está bloqueado
        if ($this->brevoApiKey !== '') {
            if ($this->sendViaBrevoHttps($destination)) {
                $this->lastDeliveryChannel = 'https_api';
                $this->writeLocalOutbox($destination, 'https_brevo');
                return;
            }
        }

        if ($this->resendApiKey !== '') {
            if ($this->sendViaResendHttps($destination)) {
                $this->lastDeliveryChannel = 'https_api';
                $this->writeLocalOutbox($destination, 'https_resend');
                return;
            }
        }

        // 2. Si no hay credenciales SMTP reales, registrar en bandeja local (Outbox)
        if (!$this->hasConfiguredSmtpCredentials()) {
            $this->writeLocalOutbox($destination, 'outbox_unconfigured');
            $this->lastDeliveryChannel = 'outbox';
            return;
        }

        // 3. Transporte SMTP estándar (PHPMailer) con resolución IPv4 explícita (evita fallos AAAA IPv6 en Docker/Render),
        //    opciones TLS compatibles con JavaMailSender y reintento automático 587 <-> 465.
        $attempts = array(
            array('port' => $this->port, 'encryption' => $this->encryption),
        );

        if ($this->host !== '127.0.0.1' && $this->host !== 'localhost') {
            if ($this->port !== 465) {
                $attempts[] = array('port' => 465, 'encryption' => PHPMailer::ENCRYPTION_SMTPS);
            } else {
                $attempts[] = array('port' => 587, 'encryption' => PHPMailer::ENCRYPTION_STARTTLS);
            }
        }

        $lastException = null;
        foreach ($attempts as $attempt) {
            try {
                $this->sendViaSmtpAttempt($destination, (int) $attempt['port'], (string) $attempt['encryption']);
                $this->lastDeliveryChannel = 'smtp';
                $this->writeLocalOutbox($destination, 'smtp_sent');
                return;
            } catch (PHPMailerException $e) {
                $lastException = $e;
            }
        }

        $this->writeLocalOutbox($destination, 'outbox_fallback');
        $this->lastDeliveryChannel = 'outbox';
        $errMsg = $lastException !== null ? $lastException->getMessage() : 'Fallo desconocido de conexión SMTP';
        error_log('[EmailSenderService Fallback Outbox] ' . $errMsg);

        if ($this->strictMode && $lastException !== null) {
            throw new EmailSenderException(
                'No se pudo enviar el correo a ' . $destination->getDestinationEmail()
                . '. Error SMTP: ' . $errMsg,
                0,
                $lastException
            );
        }
    }

    /**
     * Ejecuta un intento puntual de despacho SMTP forzando pila IPv4 para contenedores Cloud (Render/Docker)
     * y conservando el nombre de host original en TLS SNI (peer_name).
     *
     * @throws PHPMailerException
     */
    private function sendViaSmtpAttempt(EmailDestinationModel $destination, int $port, string $encryption): void
    {
        $mailer = new PHPMailer(true);

        $connectHost = $this->host;
        if ($this->host !== '127.0.0.1' && $this->host !== 'localhost' && filter_var($this->host, FILTER_VALIDATE_IP) === false) {
            $resolvedIpv4 = gethostbyname($this->host);
            if ($resolvedIpv4 !== $this->host && filter_var($resolvedIpv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                $connectHost = $resolvedIpv4;
            }
        }

        $mailer->isSMTP();
        $mailer->SMTPDebug = 0;
        $mailer->SMTPAuth = true;
        $mailer->Timeout = ($this->host === '127.0.0.1' || $this->host === 'localhost') ? 3 : 7;
        $mailer->Host = $connectHost;
        $mailer->Hostname = 'localhost';
        $mailer->Username = $this->username;
        $mailer->Password = $this->password;
        $mailer->SMTPSecure = ($port === 465 || strtolower($encryption) === 'ssl')
            ? PHPMailer::ENCRYPTION_SMTPS
            : $encryption;
        $mailer->Port = $port;
        $mailer->CharSet = 'UTF-8';
        $mailer->SMTPOptions = array(
            'socket' => array(
                'bindto' => '0.0.0.0:0',
            ),
            'ssl' => array(
                'peer_name' => $this->host,
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ),
        );

        $mailer->setFrom($this->fromAddress, $this->fromName);
        $mailer->addAddress(
            $destination->getDestinationEmail(),
            $destination->getDestinationName()
        );

        $mailer->isHTML(true);
        $mailer->Subject = $destination->getSubject();
        $mailer->Body = $destination->getBody();

        $mailer->send();
    }

    /**
     * Envío transaccional por HTTPS 443 vía API REST de Brevo (compatible con Render Free).
     */
    private function sendViaBrevoHttps(EmailDestinationModel $destination): bool
    {
        if (!function_exists('curl_init')) {
            return false;
        }

        $senderEmail = filter_var($this->fromAddress, FILTER_VALIDATE_EMAIL)
            ? $this->fromAddress
            : 'no-reply@unicartagena.edu.co';

        $payload = json_encode(array(
            'sender' => array(
                'name' => $this->fromName !== '' ? $this->fromName : 'CRUD Usuarios y Gastos',
                'email' => $senderEmail,
            ),
            'to' => array(
                array(
                    'email' => $destination->getDestinationEmail(),
                    'name' => $destination->getDestinationName(),
                ),
            ),
            'subject' => $destination->getSubject(),
            'htmlContent' => $destination->getBody(),
        ));

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        if ($ch === false || $payload === false) {
            return false;
        }

        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array(
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $this->brevoApiKey,
            ),
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
        ));

        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code >= 200 && $code < 300;
    }

    /**
     * Envío transaccional por HTTPS 443 vía API REST de Resend (compatible con Render Free).
     */
    private function sendViaResendHttps(EmailDestinationModel $destination): bool
    {
        if (!function_exists('curl_init')) {
            return false;
        }

        $fromHeader = filter_var($this->fromAddress, FILTER_VALIDATE_EMAIL)
            ? ($this->fromName . ' <' . $this->fromAddress . '>')
            : 'CRUD Usuarios y Gastos <onboarding@resend.dev>';

        $payload = json_encode(array(
            'from' => $fromHeader,
            'to' => array($destination->getDestinationEmail()),
            'subject' => $destination->getSubject(),
            'html' => $destination->getBody(),
        ));

        $ch = curl_init('https://api.resend.com/emails');
        if ($ch === false || $payload === false) {
            return false;
        }

        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->resendApiKey,
            ),
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
        ));

        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code >= 200 && $code < 300;
    }

    private function hasConfiguredSmtpCredentials(): bool
    {
        if ($this->host === '' || $this->username === '' || $this->password === '' || $this->fromAddress === '') {
            return false;
        }

        if ($this->username === 'email_que_envia@proveedor.com' || $this->password === 'xxxx xxxx xxxx xxxx') {
            return false;
        }

        return filter_var($this->fromAddress, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function writeLocalOutbox(EmailDestinationModel $destination, string $channelTag = 'outbox'): void
    {
        if (!is_dir($this->outboxDir)) {
            @mkdir($this->outboxDir, 0775, true);
        }

        $safeEmail = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $destination->getDestinationEmail());
        $file = $this->outboxDir . '/' . date('Ymd_His') . '_' . uniqid('', true) . '_' . $safeEmail . '.html';
        $meta = json_encode(array(
            'to_name' => $destination->getDestinationName(),
            'to_email' => $destination->getDestinationEmail(),
            'subject' => $destination->getSubject(),
            'date' => date('Y-m-d H:i:s'),
            'channel' => $channelTag,
        ), JSON_UNESCAPED_UNICODE);

        $header = "<!-- META:" . $meta . " -->\n";
        @file_put_contents($file, $header . $destination->getBody());
    }

    /**
     * Lista los correos generados en la bandeja local para inspección administrativa en entornos Cloud Free.
     *
     * @return array<int, array{filename: string, to_name: string, to_email: string, subject: string, date: string, channel: string, html: string}>
     */
    public function listOutboxMessages(int $limit = 20): array
    {
        if (!is_dir($this->outboxDir)) {
            return array();
        }

        $files = glob($this->outboxDir . '/*.html');
        if ($files === false || empty($files)) {
            return array();
        }

        rsort($files);
        $files = array_slice($files, 0, $limit);
        $messages = array();

        foreach ($files as $filePath) {
            $raw = (string) @file_get_contents($filePath);
            $meta = array(
                'to_name' => '',
                'to_email' => '',
                'subject' => basename($filePath),
                'date' => date('Y-m-d H:i:s', (int) @filemtime($filePath)),
                'channel' => 'outbox',
            );
            $htmlBody = $raw;

            if (preg_match('/^<!-- META:(\{.*?\}) -->\s*/s', $raw, $m)) {
                $decoded = json_decode($m[1], true);
                if (is_array($decoded)) {
                    $meta = array_merge($meta, $decoded);
                }
                $htmlBody = (string) substr($raw, strlen($m[0]));
            }

            $messages[] = array(
                'filename' => basename($filePath),
                'to_name' => (string) $meta['to_name'],
                'to_email' => (string) $meta['to_email'],
                'subject' => (string) $meta['subject'],
                'date' => (string) $meta['date'],
                'channel' => (string) $meta['channel'],
                'html' => $htmlBody,
            );
        }

        return $messages;
    }
}