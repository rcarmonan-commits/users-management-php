<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../Common/SecurityGuard.php';

/**
 * ============================================================================
 * GUÍA DE SEGURIDAD Y PRESENTACIÓN — MOTOR DE VISTAS: View
 * ============================================================================
 * CAPA: Infraestructura — Presentación Web (`Infrastructure/Entrypoints/Web/Presentation`)
 *
 * ¿QUÉ HACE?
 *   Renderiza plantillas PHP aisladas bajo `Views/` y gestiona redirecciones PRG
 *   (*Post-Redirect-Get*) aplicando defensas automáticas OWASP:
 *     1. Bloquea secuencias `..` en nombres de plantilla (Anti-Path Traversal).
 *     2. Emite cabeceras defensivas (`CSP`, `X-Frame-Options`, `nosniff`).
 *     3. Captura el HTML en búfer e inyecta automáticamente el token `_csrf_token`
 *        en cualquier `<form method="POST">` mediante `SecurityGuard`.
 *     4. Limpia secuencias CRLF (`\r`, `\n`, `\0`) en redirecciones HTTP.
 * ============================================================================
 */
final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = array()): void
    {
        if (str_contains($template, '..') || str_contains($template, "\0")) {
            throw new InvalidArgumentException('Nombre de vista no permitido.');
        }

        $file = __DIR__ . '/Views/' . $template . '.php';

        if (!file_exists($file)) {
            throw new RuntimeException('Vista no encontrada: ' . $template);
        }

        SecurityGuard::applyHttpSecurityHeaders();

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            require $file;
            $renderedHtml = (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        echo SecurityGuard::injectCsrfIntoPostForms($renderedHtml);
    }

    public static function redirect(string $route): void
    {
        $safeRoute = SecurityGuard::sanitizeHeaderValue($route);
        header('Location: ?route=' . urlencode($safeRoute));
        exit;
    }
}