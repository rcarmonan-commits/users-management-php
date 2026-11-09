<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../Common/SecurityGuard.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA Y DE SEGURIDAD — DTO ENTRADA REST: ApiRequest
 * ============================================================================
 * CAPA: Infraestructura — Entrypoint REST (`Infrastructure/Entrypoints/Rest/Dto`)
 *
 * ¿QUÉ HACE Y QUÉ VULNERABILIDADES PREVIENE?
 *   1. Impone un tope estricto de `65536` bytes (64 KB) sobre `php://input` mediante
 *      `validatePayloadSize()` lanzando `OverflowException` (`HTTP 413`) para evitar
 *      agotamiento de memoria (*Denial of Service* / JSON Bomb).
 *   2. Exige JSON bien formado con profundidad acotada (`depth = 32`).
 *   3. Extrae de forma segura el token `Authorization: Bearer <JWT>` compatible con
 *      Apache (`mod_rewrite`/`CGI`), Nginx, PHP CLI Server y proxies Cloud.
 * ============================================================================
 */
final class ApiRequest
{
    public const MAX_PAYLOAD_BYTES = 65536;

    private string $method;
    private string $path;
    /** @var array<string, mixed> */
    private array $queryParams;
    /** @var array<string, mixed> */
    private array $body;
    private ?string $bearerToken;

    /**
     * @param array<string, mixed> $queryParams
     * @param array<string, mixed> $body
     */
    public function __construct(
        string $method,
        string $path,
        array $queryParams = array(),
        array $body = array(),
        ?string $bearerToken = null
    ) {
        $this->method = strtoupper(trim($method));
        $this->path = $path;
        $this->queryParams = $queryParams;
        $this->body = $body;
        $this->bearerToken = $bearerToken !== null ? trim($bearerToken) : null;
    }

    public static function validatePayloadSize(string $rawInput): void
    {
        if (strlen($rawInput) > self::MAX_PAYLOAD_BYTES) {
            throw new OverflowException('El cuerpo JSON excede el límite máximo permitido de 64 KB.');
        }
    }

    public static function fromGlobals(string $resolvedPath): self
    {
        $rawInput = (string) file_get_contents('php://input');
        self::validatePayloadSize($rawInput);
        $decoded = array();

        if ($rawInput !== '') {
            $parsed = json_decode($rawInput, true, 32);
            if (!is_array($parsed)) {
                throw new InvalidArgumentException('El cuerpo de la petición debe ser un objeto JSON válido.');
            }
            $decoded = $parsed;
        } elseif (!empty($_POST)) {
            $decoded = $_POST;
        }

        return new self(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $resolvedPath,
            $_GET,
            $decoded,
            SecurityGuard::extractBearerToken()
        );
    }

    /**
     * Retorna una nueva instancia inmutable sobrescribiendo campos del cuerpo JSON
     * (útil para blindar `role` y `status` cuando un no-ADMIN edita su propio perfil).
     *
     * @param array<string, mixed> $overrides
     */
    public function withBodyOverrides(array $overrides): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->queryParams,
            array_merge($this->body, $overrides),
            $this->bearerToken
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function bearerToken(): ?string
    {
        return $this->bearerToken;
    }

    public function query(string $key, ?string $default = null): ?string
    {
        if (!isset($this->queryParams[$key])) {
            return $default;
        }

        return trim((string) $this->queryParams[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    public function body(): array
    {
        return $this->body;
    }

    public function stringField(string $key, string $default = ''): string
    {
        return isset($this->body[$key]) ? trim((string) $this->body[$key]) : $default;
    }
}