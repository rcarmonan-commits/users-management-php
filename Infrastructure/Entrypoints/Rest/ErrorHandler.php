<?php

declare(strict_types=1);

require_once __DIR__ . '/Dto/ApiResponse.php';
require_once __DIR__ . '/../../../Domain/Exceptions/UserNotFoundException.php';
require_once __DIR__ . '/../../../Domain/Exceptions/GastoNotFoundException.php';
require_once __DIR__ . '/../../../Domain/Exceptions/UserAlreadyExistsException.php';
require_once __DIR__ . '/../../../Domain/Exceptions/InvalidCredentialsException.php';
require_once __DIR__ . '/../../../Domain/Exceptions/InvalidGastoIvaConsistencyException.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA Y DE SEGURIDAD — MANEJADOR GLOBAL REST: ErrorHandler
 * ============================================================================
 * CAPA: Infraestructura — Entrypoint REST (`Infrastructure/Entrypoints/Rest`)
 *
 * ¿QUÉ HACE?
 *   Traduce excepciones semánticas del Dominio y guardias de seguridad hacia HTTP exactos:
 *     - `401` Credenciales inválidas o Token Bearer JWT ausente/inválido/expirado/revocado
 *     - `403` Permisos RBAC insuficientes o violación Anti-BOLA/IDOR
 *     - `404` Agregado inexistente (`UserNotFoundException`, `GastoNotFoundException`)
 *     - `409` Conflicto de unicidad (`UserAlreadyExistsException`)
 *     - `413` Cuerpo JSON demasiado grande (`OverflowException`)
 *     - `422` Violación de invariante de negocio (`DomainException` / IVA descuadrado)
 *     - `429` Límite de peticiones excedido (*Rate Limit*)
 *     - `500` Falla inesperada: oculta detalles internos de infraestructura salvo
 *       cuando `APP_DEBUG=true` (prevención OWASP A05 Information Disclosure).
 * ============================================================================
 */
final class ErrorHandler
{
    public static function handle(Throwable $exception): ApiResponse
    {
        $class = get_class($exception);

        if ($exception instanceof OverflowException) {
            return ApiResponse::error('PayloadTooLarge', $exception->getMessage(), 413);
        }

        if ($exception instanceof RuntimeException && $exception->getCode() === 401) {
            return ApiResponse::error('UnauthorizedToken', $exception->getMessage(), 401);
        }

        if ($exception instanceof RuntimeException && $exception->getCode() === 403) {
            return ApiResponse::error('ForbiddenAccess', $exception->getMessage(), 403);
        }

        if ($exception instanceof RuntimeException && $exception->getCode() === 429) {
            return ApiResponse::error('TooManyRequests', $exception->getMessage(), 429);
        }

        if ($exception instanceof UserNotFoundException || $exception instanceof GastoNotFoundException) {
            return ApiResponse::error($class, $exception->getMessage(), 404);
        }

        if ($exception instanceof UserAlreadyExistsException) {
            return ApiResponse::error($class, $exception->getMessage(), 409);
        }

        if ($exception instanceof InvalidCredentialsException) {
            return ApiResponse::error($class, $exception->getMessage(), 401);
        }

        if ($exception instanceof InvalidGastoIvaConsistencyException || $exception instanceof InvalidArgumentException || $exception instanceof DomainException) {
            return ApiResponse::error($class, $exception->getMessage(), 422);
        }

        $debugEnabled = strtolower((string) (getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? 'false'))) === 'true';
        $safeMessage = $debugEnabled
            ? $exception->getMessage()
            : 'Error interno procesando la solicitud.';

        return ApiResponse::error('InternalServerError', $safeMessage, 500);
    }
}