<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — REPOSITORIO DINÁMICO SMTP: SmtpConfigRepositoryMySQL
 * ============================================================================
 * CAPA: Infraestructura — Adaptadores (`Infrastructure/Adapters/Email`)
 * PATRÓN: Configuración Dinámica en Caliente compatible con Alwaysdata / Spring Boot
 *
 * ¿QUÉ HACE?
 *   Lee y actualiza de forma no destructiva la tabla `configuracion_smtp` en MySQL,
 *   permitiendo administrar servidor, puerto, cuenta autenticada y remitente desde
 *   la interfaz administrativa (`?route=smtp.config`) sin reiniciar contenedores Cloud.
 *
 * ¿CÓMO RESPETA LA BASE DE DATOS COMPARTIDA?
 *   - En lectura (`getActiveConfig`), consulta únicamente `ORDER BY id ASC LIMIT 1`.
 *   - En escritura (`saveConfig`), actualiza el registro existente con `PDO::prepare`
 *     y conserva la contraseña actual si el administrador deja el campo clave vacío.
 * ============================================================================
 */
final class SmtpConfigRepositoryMySQL
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array{id: int, host: string, port: int, username: string, password: string, remitente: string, from_address: string, from_name: string}|null
     */
    public function getActiveConfig(): ?array
    {
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'configuracion_smtp'");
            if ($check === false || $check->fetchColumn() === false) {
                return null;
            }

            $stmt = $this->pdo->query('SELECT id, host, puerto, usuario, clave, remitente FROM configuracion_smtp ORDER BY id ASC LIMIT 1');
            if ($stmt === false) {
                return null;
            }

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return null;
            }

            return self::normalizeRow($row);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Normaliza una fila de `configuracion_smtp` distinguiendo si `remitente` contiene un correo o un nombre visible.
     *
     * @param array<string, mixed> $row
     * @return array{id: int, host: string, port: int, username: string, password: string, remitente: string, from_address: string, from_name: string}|null
     */
    public static function normalizeRow(array $row): ?array
    {
        $host = trim((string) ($row['host'] ?? ''));
        $username = trim((string) ($row['usuario'] ?? ''));
        $password = (string) ($row['clave'] ?? '');
        $port = (int) ($row['puerto'] ?? 587);
        if ($port <= 0) {
            $port = 587;
        }

        if ($host === '' || $username === '' || $password === '') {
            return null;
        }

        $remitente = trim((string) ($row['remitente'] ?? ''));
        if ($remitente !== '' && filter_var($remitente, FILTER_VALIDATE_EMAIL) !== false) {
            $fromAddress = $remitente;
            $fromName = 'Sistema de Control de Usuarios y Gastos';
        } else {
            $fromAddress = $username;
            $fromName = $remitente !== '' ? $remitente : 'Administración del Sistema';
        }

        return array(
            'id' => (int) ($row['id'] ?? 1),
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'remitente' => $remitente,
            'from_address' => $fromAddress,
            'from_name' => $fromName,
        );
    }

    /**
     * Crea la tabla si no existe y actualiza de forma no destructiva el primer registro de `configuracion_smtp`.
     */
    public function saveConfig(
        string $host,
        int $port,
        string $username,
        string $password,
        string $remitente
    ): void {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS configuracion_smtp (
                id INT AUTO_INCREMENT PRIMARY KEY,
                host VARCHAR(100) NOT NULL,
                puerto INT DEFAULT 587,
                usuario VARCHAR(100) NOT NULL,
                clave VARCHAR(255) NOT NULL,
                remitente VARCHAR(255) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $existingStmt = $this->pdo->query('SELECT id, clave FROM configuracion_smtp ORDER BY id ASC LIMIT 1');
        $existing = $existingStmt !== false ? $existingStmt->fetch(PDO::FETCH_ASSOC) : false;

        $cleanRemitente = trim($remitente);
        $remitenteParam = $cleanRemitente !== '' ? $cleanRemitente : null;

        if (is_array($existing) && isset($existing['id'])) {
            $effectivePassword = $password !== '' ? $password : (string) ($existing['clave'] ?? '');
            $upd = $this->pdo->prepare('
                UPDATE configuracion_smtp
                   SET host = :host,
                       puerto = :puerto,
                       usuario = :usuario,
                       clave = :clave,
                       remitente = :remitente
                 WHERE id = :id
            ');
            $upd->execute(array(
                ':host' => trim($host),
                ':puerto' => $port > 0 ? $port : 587,
                ':usuario' => trim($username),
                ':clave' => $effectivePassword,
                ':remitente' => $remitenteParam,
                ':id' => (int) $existing['id'],
            ));
            return;
        }

        $ins = $this->pdo->prepare('
            INSERT INTO configuracion_smtp (id, host, puerto, usuario, clave, remitente)
            VALUES (1, :host, :puerto, :usuario, :clave, :remitente)
        ');
        $ins->execute(array(
            ':host' => trim($host),
            ':puerto' => $port > 0 ? $port : 587,
            ':usuario' => trim($username),
            ':clave' => $password,
            ':remitente' => $remitenteParam,
        ));
    }

    /**
     * Lee la configuración opcional de pasarela HTTPS 443 (tabla independiente `hex_cloud_email_relay`,
     * 100% no intrusiva respecto a `configuracion_smtp` para preservar compatibilidad con otros sitios).
     *
     * @return array{webhook_url: string, webhook_token: string, brevo_api_key: string, resend_api_key: string, https_from_address: string}
     */
    public function getCloudRelayConfig(): array
    {
        $defaults = array(
            'webhook_url' => '',
            'webhook_token' => '',
            'brevo_api_key' => '',
            'resend_api_key' => '',
            'https_from_address' => '',
        );

        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'hex_cloud_email_relay'");
            if ($check === false || $check->fetchColumn() === false) {
                return $defaults;
            }

            $stmt = $this->pdo->query(
                'SELECT webhook_url, webhook_token, brevo_api_key, resend_api_key, https_from_address
                   FROM hex_cloud_email_relay
                  ORDER BY id ASC
                  LIMIT 1'
            );
            $row = $stmt !== false ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
            if (!is_array($row)) {
                return $defaults;
            }

            return array(
                'webhook_url' => trim((string) ($row['webhook_url'] ?? '')),
                'webhook_token' => trim((string) ($row['webhook_token'] ?? '')),
                'brevo_api_key' => trim((string) ($row['brevo_api_key'] ?? '')),
                'resend_api_key' => trim((string) ($row['resend_api_key'] ?? '')),
                'https_from_address' => trim((string) ($row['https_from_address'] ?? '')),
            );
        } catch (Throwable) {
            return $defaults;
        }
    }

    /**
     * Crea `hex_cloud_email_relay` si no existe y persiste credenciales HTTPS 443 sin sobrescribir
     * secretos previos cuando los campos tipo password/key se envían en blanco (salvo `$clearSecrets = true`).
     */
    public function saveCloudRelayConfig(
        string $webhookUrl,
        string $webhookToken,
        string $brevoApiKey,
        string $resendApiKey,
        string $httpsFromAddress,
        bool $clearSecrets = false
    ): void {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS hex_cloud_email_relay (
                id INT PRIMARY KEY,
                webhook_url VARCHAR(500) NULL,
                webhook_token VARCHAR(190) NULL,
                brevo_api_key VARCHAR(255) NULL,
                resend_api_key VARCHAR(255) NULL,
                https_from_address VARCHAR(190) NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $current = $this->getCloudRelayConfig();
        $finalToken = $clearSecrets ? '' : ($webhookToken !== '' ? trim($webhookToken) : $current['webhook_token']);
        $finalBrevo = $clearSecrets ? '' : ($brevoApiKey !== '' ? trim($brevoApiKey) : $current['brevo_api_key']);
        $finalResend = $clearSecrets ? '' : ($resendApiKey !== '' ? trim($resendApiKey) : $current['resend_api_key']);

        $stmt = $this->pdo->prepare('
            INSERT INTO hex_cloud_email_relay (id, webhook_url, webhook_token, brevo_api_key, resend_api_key, https_from_address)
            VALUES (1, :webhook_url, :webhook_token, :brevo_api_key, :resend_api_key, :https_from_address)
            ON DUPLICATE KEY UPDATE
                webhook_url = VALUES(webhook_url),
                webhook_token = VALUES(webhook_token),
                brevo_api_key = VALUES(brevo_api_key),
                resend_api_key = VALUES(resend_api_key),
                https_from_address = VALUES(https_from_address)
        ');
        $stmt->execute(array(
            ':webhook_url' => trim($webhookUrl) !== '' ? trim($webhookUrl) : null,
            ':webhook_token' => $finalToken !== '' ? $finalToken : null,
            ':brevo_api_key' => $finalBrevo !== '' ? $finalBrevo : null,
            ':resend_api_key' => $finalResend !== '' ? $finalResend : null,
            ':https_from_address' => trim($httpsFromAddress) !== '' ? trim($httpsFromAddress) : null,
        ));
    }
}