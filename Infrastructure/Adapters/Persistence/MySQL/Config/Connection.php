<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — FÁBRICA DE CONEXIÓN PDO SEGURA: Connection
 * ============================================================================
 * CAPA: Infraestructura — Adaptadores (`Infrastructure/Adapters/Persistence/MySQL/Config`)
 * PATRÓN: Proveedor de infraestructura PDO endurecido contra Inyección SQL
 *
 * ¿QUÉ HACE?
 *   Construye instancias `PDO` hacia MySQL local o motores Cloud (Alwaysdata MariaDB 11.4,
 *   Aiven, Railway, TiDB) aplicando parámetros estrictos de seguridad y codificación UTF-8.
 *
 * ¿POR QUÉ ES CRÍTICO EN SEGURIDAD (OWASP A03 — SQL INJECTION)?
 *   1. `PDO::ATTR_EMULATE_PREPARES => false`: obliga al motor MySQL/MariaDB a compilar
 *      las sentencias preparadas de forma nativa en el servidor separando el árbol
 *      sintáctico SQL de los datos enviados por el usuario.
 *   2. `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`: evita fallos silenciosos.
 *   3. Soporta certificados CA (`PDO::MYSQL_ATTR_SSL_CA`) para canales TLS cifrados.
 * ============================================================================
 */
final class Connection
{
    private string $host;
    private int $port;
    private string $database;
    private string $username;
    private string $password;
    private string $charset;
    private string $sslCa;

    public function __construct(
        string $host,
        int $port,
        string $database,
        string $username,
        string $password,
        string $charset = 'utf8mb4',
        string $sslCa = ''
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->charset = $charset;
        $this->sslCa = trim($sslCa);
    }

    public function createPdo(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->host,
            $this->port,
            $this->database,
            $this->charset
        );

        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 4,
        );

        if ($this->sslCa !== '' && defined('PDO::MYSQL_ATTR_SSL_CA')) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $this->sslCa;
        }

        return new PDO(
            $dsn,
            $this->username,
            $this->password,
            $options
        );
    }
}