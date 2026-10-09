<?php

declare(strict_types=1);

/**
 * Adaptador de infraestructura para lectura y actualización dinámica de credenciales SMTP
 * desde la tabla `configuracion_smtp` (100% compatible con esquemas Alwaysdata / Spring Boot),
 * evitando depender exclusivamente de variables estáticas de entorno en despliegues Cloud.
 */
final class SmtpConfigRepositoryMySQL
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene la primera configuración activa almacenada en `configuracion_smtp` sin alterar la tabla.
     *
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
}