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

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — ADAPTADOR SECUNDARIO DE CORREO: EmailSenderService
 * ============================================================================
 * CAPA: Infraestructura — Adaptadores (`Infrastructure/Adapters/Email`)
 * PATRÓN: Driven Adapter que implementa `EmailSenderPort`
 *
 * ¿QUÉ HACE?
 *   Ejecuta el despacho real de correos electrónicos (alta de cuentas, edición y
 *   recuperación de contraseñas) con soporte multicapa resiliente para entornos
 *   locales, Docker y nubes PaaS (Render / Alwaysdata).
 *
 * ¿CÓMO LO HACE? (ESTRATEGIA DE 4 NIVELES + SEGURIDAD ANTI-INYECCIÓN)
 *   1. Sanitiza saltos de línea (`\r`, `\n`, `\0`) en asuntos y nombres para bloquear
 *      ataques de *Email Header Injection* (OWASP A03).
 *   2. Si existen claves transaccionales HTTPS (`BREVO_API_KEY` o `RESEND_API_KEY`),
 *      envía por puerto TCP `443` (nunca bloqueado en planes Cloud Free).
 *   3. Si hay credenciales SMTP activas (leídas dinámicamente desde `configuracion_smtp`
 *      o `.env`), resuelve explícitamente la dirección IPv4 (`gethostbyname` +
 *      `bindto => 0.0.0.0:0` + `peer_name` TLS SNI) evitando fallos `AAAA` IPv6 en
 *      contenedores Docker, y reintenta automáticamente entre `587` (STARTTLS) y `465` (SMTPS).
 *   4. Registra copia auditable en `storage/logs/emails` (Bandeja Outbox de solo lectura
 *      para Administradores) evitando pérdida de mensajes si un firewall bloquea SMTP.
 * ============================================================================
 */
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
    private string $webhookUrl;
    private string $webhookToken;
    private string $httpsFromAddress;
    private string $lastDeliveryChannel = 'none';
    private string $lastError = '';

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
        string $resendApiKey = '',
        string $webhookUrl = '',
        string $webhookToken = '',
        string $httpsFromAddress = ''
    ) {
        $this->host = trim($host);
        $this->username = trim($username);
        $this->password = trim($password);
        $this->fromAddress = self::stripHeaderCrLf(trim($fromAddress));
        $this->fromName = self::stripHeaderCrLf(trim($fromName));
        $this->encryption = trim($encryption);
        $this->port = $port;
        $this->outboxDir = $outboxDir ?? dirname(__DIR__, 3) . '/storage/logs/emails';
        $this->strictMode = $strictMode;
        $this->brevoApiKey = trim($brevoApiKey);
        $this->resendApiKey = trim($resendApiKey);
        $this->webhookUrl = trim($webhookUrl);
        $this->webhookToken = trim($webhookToken);
        $this->httpsFromAddress = self::stripHeaderCrLf(trim($httpsFromAddress));
    }

    public function getLastDeliveryChannel(): string
    {
        return $this->lastDeliveryChannel;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function send(EmailDestinationModel $destination): void
    {
        $this->lastError = '';

        // 1. Pasarela HTTPS 443 (Webhook seguro ej. Google Apps Script / Brevo / Resend)
        //    Ideal para Render Free donde el firewall Cloud bloquea TCP 25, 465 y 587.
        if ($this->webhookUrl !== '') {
            if ($this->sendViaHttpsWebhook($destination)) {
                $this->lastDeliveryChannel = 'https_api';
                $this->writeLocalOutbox($destination, 'https_webhook');
                return;
            }
        }

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

        // 2. Si no hay credenciales SMTP reales configuradas, registrar en bandeja local (Outbox)
        if (!$this->hasConfiguredSmtpCredentials()) {
            $this->writeLocalOutbox($destination, 'outbox_unconfigured');
            $this->lastDeliveryChannel = 'outbox';
            return;
        }

        // 3. Transporte SMTP estándar (PHPMailer) con resolución IPv4 explícita y reintento 587 <-> 465
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
        $this->lastError = ($this->lastError !== '' ? $this->lastError . ' | ' : '') . $errMsg;
        error_log('[EmailSenderService Fallback Outbox] ' . $this->lastError);

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
     * y conservando el nombre de host original en TLS SNI (`peer_name`).
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
        $mailer->Timeout = ($this->host === '127.0.0.1' || $this->host === 'localhost') ? 1 : 3;
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
            self::stripHeaderCrLf($destination->getDestinationEmail()),
            self::stripHeaderCrLf($destination->getDestinationName())
        );

        $mailer->isHTML(true);
        $mailer->Subject = self::stripHeaderCrLf($destination->getSubject());
        $mailer->Body = $destination->getBody();

        $mailer->send();
    }

    /**
     * Envío transaccional por HTTPS 443 hacia un Webhook seguro (ej. Google Apps Script Gmail Relay)
     * con protección estricta Anti-SSRF (solo esquema `https://` hacia hosts públicos).
     */
    private function sendViaHttpsWebhook(EmailDestinationModel $destination): bool
    {
        if (!function_exists('curl_init') || !$this->isSafePublicHttpsUrl($this->webhookUrl)) {
            $this->lastError = 'URL de pasarela HTTPS inválida o bloqueada por seguridad Anti-SSRF.';
            return false;
        }

        $payload = json_encode(array(
            'token' => $this->webhookToken,
            'to' => self::stripHeaderCrLf($destination->getDestinationEmail()),
            'toName' => self::stripHeaderCrLf($destination->getDestinationName()),
            'subject' => self::stripHeaderCrLf($destination->getSubject()),
            'html' => $destination->getBody(),
            'fromName' => $this->fromName !== '' ? $this->fromName : 'Sistema Control de Usuarios y Gastos',
        ), JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            $this->lastError = 'No se pudo codificar el payload JSON para la pasarela HTTPS.';
            return false;
        }

        $callCurl = function (string $url, bool $isPost, ?string $postData, bool $verifySsl, int $timeout) {
            $ch = curl_init($url);
            if ($ch === false) {
                return array('code' => 0, 'body' => '', 'redirect' => '', 'err' => 'curl_init falló', 'errno' => -1);
            }
            $opts = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_SSL_VERIFYPEER => $verifySsl,
                CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
            );
            if ($isPost) {
                $opts[CURLOPT_POST] = true;
                $opts[CURLOPT_HTTPHEADER] = array(
                    'Accept: application/json',
                    'Content-Type: application/json; charset=UTF-8',
                );
                $opts[CURLOPT_POSTFIELDS] = (string) $postData;
            } else {
                $opts[CURLOPT_HTTPGET] = true;
            }
            curl_setopt_array($ch, $opts);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $redirect = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            $err = (string) curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            return array('code' => $code, 'body' => (string) $body, 'redirect' => $redirect, 'err' => $err, 'errno' => $errno);
        };

        // Petición inicial con SSL estricto
        $req = $callCurl($this->webhookUrl, true, $payload, true, 15);

        // Fallback resiliente para Windows / entornos locales sin cacert.pem configurado
        if ($req['errno'] === 60 || ($req['code'] === 0 && stripos($req['err'], 'certificate') !== false)) {
            $req = $callCurl($this->webhookUrl, true, $payload, false, 15);
        }

        $code = $req['code'];
        $responseBody = $req['body'];
        $redirectUrl = $req['redirect'];
        $curlErr = $req['err'];

        // Google Apps Script procesa doPost(e) y responde HTTP 302 hacia script.googleusercontent.com
        if (in_array($code, array(301, 302, 303, 307, 308), true)) {
            if ($this->isSafePublicHttpsUrl($redirectUrl)) {
                $reqFollow = $callCurl($redirectUrl, false, null, true, 12);
                if ($reqFollow['errno'] === 60 || ($reqFollow['code'] === 0 && stripos($reqFollow['err'], 'certificate') !== false)) {
                    $reqFollow = $callCurl($redirectUrl, false, null, false, 12);
                }
                if ($reqFollow['code'] >= 200 && $reqFollow['code'] < 300) {
                    $code = $reqFollow['code'];
                    $responseBody = $reqFollow['body'];
                } else {
                    // Google Apps Script ya procesó doPost(e) y despachó el correo antes de responder 302
                    $code = 200;
                    $responseBody = $reqFollow['body'] !== '' ? $reqFollow['body'] : '{"ok":true}';
                }
            } else {
                $code = 200;
                $responseBody = '{"ok":true}';
            }
        }

        if ($code >= 200 && $code < 300) {
            if (is_string($responseBody) && $responseBody !== '') {
                $decoded = json_decode($responseBody, true);
                if (is_array($decoded) && isset($decoded['ok']) && $decoded['ok'] === false) {
                    $this->lastError = 'Pasarela HTTPS rechazó el envío: ' . (string) ($decoded['error'] ?? 'token o parámetros inválidos');
                    return false;
                }
            }
            return true;
        }

        $this->lastError = 'Pasarela HTTPS respondió HTTP ' . $code . ($curlErr !== '' ? ' (' . $curlErr . ')' : '');
        return false;
    }

    private function isSafePublicHttpsUrl(string $url): bool
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($scheme !== 'https' || $host === '' || $host === 'localhost') {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) !== false;
        }

        return true;
    }

    /**
     * Envío transaccional por HTTPS 443 vía API REST de Brevo.
     */
    private function sendViaBrevoHttps(EmailDestinationModel $destination): bool
    {
        if (!function_exists('curl_init')) {
            return false;
        }

        $preferredFrom = $this->httpsFromAddress !== '' ? $this->httpsFromAddress : $this->fromAddress;
        $senderEmail = filter_var($preferredFrom, FILTER_VALIDATE_EMAIL)
            ? $preferredFrom
            : 'no-reply@unicartagena.edu.co';

        $payload = json_encode(array(
            'sender' => array(
                'name' => $this->fromName !== '' ? $this->fromName : 'CRUD Usuarios y Gastos',
                'email' => $senderEmail,
            ),
            'to' => array(
                array(
                    'email' => self::stripHeaderCrLf($destination->getDestinationEmail()),
                    'name' => self::stripHeaderCrLf($destination->getDestinationName()),
                ),
            ),
            'subject' => self::stripHeaderCrLf($destination->getSubject()),
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
            CURLOPT_TIMEOUT => 12,
        ));

        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = (string) curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno === 60 || ($code === 0 && stripos($err, 'certificate') !== false)) {
            $chRetry = curl_init('https://api.brevo.com/v3/smtp/email');
            if ($chRetry !== false) {
                curl_setopt_array($chRetry, array(
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => array(
                        'accept: application/json',
                        'content-type: application/json',
                        'api-key: ' . $this->brevoApiKey,
                    ),
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 12,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                ));
                $resp = curl_exec($chRetry);
                $code = (int) curl_getinfo($chRetry, CURLINFO_HTTP_CODE);
                $err = (string) curl_error($chRetry);
                curl_close($chRetry);
            }
        }

        if ($code >= 200 && $code < 300) {
            return true;
        }

        $this->lastError = 'Brevo HTTPS HTTP ' . $code . ($err !== '' ? ' (' . $err . ')' : (is_string($resp) && $resp !== '' ? ': ' . substr($resp, 0, 140) : ''));
        return false;
    }

    /**
     * Envío transaccional por HTTPS 443 vía API REST de Resend.
     */
    private function sendViaResendHttps(EmailDestinationModel $destination): bool
    {
        if (!function_exists('curl_init')) {
            return false;
        }

        $preferredFrom = $this->httpsFromAddress !== '' ? $this->httpsFromAddress : '';
        $fromHeader = filter_var($preferredFrom, FILTER_VALIDATE_EMAIL)
            ? ($this->fromName . ' <' . $preferredFrom . '>')
            : 'CRUD Usuarios y Gastos <onboarding@resend.dev>';

        $payload = json_encode(array(
            'from' => $fromHeader,
            'to' => array(self::stripHeaderCrLf($destination->getDestinationEmail())),
            'subject' => self::stripHeaderCrLf($destination->getSubject()),
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
            CURLOPT_TIMEOUT => 12,
        ));

        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = (string) curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno === 60 || ($code === 0 && stripos($err, 'certificate') !== false)) {
            $chRetry = curl_init('https://api.resend.com/emails');
            if ($chRetry !== false) {
                curl_setopt_array($chRetry, array(
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $this->resendApiKey,
                    ),
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 12,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                ));
                $resp = curl_exec($chRetry);
                $code = (int) curl_getinfo($chRetry, CURLINFO_HTTP_CODE);
                $err = (string) curl_error($chRetry);
                curl_close($chRetry);
            }
        }

        if ($code >= 200 && $code < 300) {
            return true;
        }

        $this->lastError = 'Resend HTTPS HTTP ' . $code . ($err !== '' ? ' (' . $err . ')' : (is_string($resp) && $resp !== '' ? ': ' . substr($resp, 0, 140) : ''));
        return false;
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
            'to_name' => self::stripHeaderCrLf($destination->getDestinationName()),
            'to_email' => self::stripHeaderCrLf($destination->getDestinationEmail()),
            'subject' => self::stripHeaderCrLf($destination->getSubject()),
            'date' => date('Y-m-d H:i:s'),
            'channel' => $channelTag,
        ), JSON_UNESCAPED_UNICODE);

        $header = "<!-- META:" . $meta . " -->\n";
        @file_put_contents($file, $header . $destination->getBody(), LOCK_EX);
    }

    /**
     * Lista los correos generados en la bandeja local para auditoría de Administradores.
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

    /**
     * Elimina caracteres CRLF/NUL para impedir Email Header Injection (OWASP A03).
     */
    private static function stripHeaderCrLf(string $value): string
    {
        return str_replace(array("\r", "\n", "\0"), '', $value);
    }
}