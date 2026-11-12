<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../Common/SecurityGuard.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA Y DE SEGURIDAD — SOBRE ESTÁNDAR REST: ApiResponse
 * ============================================================================
 * CAPA: Infraestructura — Entrypoint REST (`Infrastructure/Entrypoints/Rest/Dto`)
 *
 * ¿QUÉ HACE?
 *   Estandariza la salida JSON (`success`, `message`, `data` / `error`) y emite
 *   cabeceras defensivas OWASP (`X-Content-Type-Options: nosniff`, `CSP`, etc.)
 *   antes de serializar el payload.
 * ============================================================================
 */
final class ApiResponse
{
    private int $statusCode;
    /** @var array<string, mixed> */
    private array $payload;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(int $statusCode, array $payload)
    {
        $this->statusCode = $statusCode;
        $this->payload = $payload;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function success(array $data, int $statusCode = 200, string $message = 'OK'): self
    {
        return new self($statusCode, array(
            'success' => true,
            'message' => $message,
            'data' => $data,
        ));
    }

    public static function error(string $errorCode, string $message, int $statusCode): self
    {
        return new self($statusCode, array(
            'success' => false,
            'error' => array(
                'code' => $errorCode,
                'message' => $message,
            ),
        ));
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            SecurityGuard::applyHttpSecurityHeaders();
            http_response_code($this->statusCode);
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');

            $allowedOrigin = SecurityGuard::sanitizeHeaderValue(
                trim((string) (getenv('CORS_ALLOWED_ORIGIN') ?: ($_ENV['CORS_ALLOWED_ORIGIN'] ?? '*')))
            );
            if ($allowedOrigin !== '') {
                header('Access-Control-Allow-Origin: ' . $allowedOrigin);
            }
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');

            if ($this->statusCode === 401) {
                header('WWW-Authenticate: Bearer realm="Hexagonal-API", charset="UTF-8"');
            }
        }

        echo json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}