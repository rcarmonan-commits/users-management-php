<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — TABLA BLANCA DE ENRUTAMIENTO WEB: WebRoutes
 * ============================================================================
 * CAPA: Infraestructura — Entrypoint Web (`Infrastructure/Entrypoints/Web/Controllers/Config`)
 * SEGURIDAD: Lista blanca estricta (*Allowlist*) de rutas y verbos HTTP (`GET` / `POST`).
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   1. Cualquier parámetro `?route=` ausente en esta tabla recibe `HTTP 404`, impidiendo
 *      vulnerabilidades de *Local File Inclusion (LFI)* o *Path Traversal*.
 *   2. Todas las operaciones mutadoras (`store`, `update`, `delete`, `authenticate`,
 *      `forgot.send`, `smtp.save`) exigen estrictamente `POST` + validación CSRF.
 * ============================================================================
 */
final class WebRoutes
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function routes(): array
    {
        return array(
            'home' => array(
                'method' => 'GET',
                'action' => 'home',
            ),
                        'api.tester' => array(
                'method' => 'GET',
                'action' => 'api.tester',
            ),
'about' => array(
                'method' => 'GET',
                'action' => 'about',
            ),
            'reports' => array(
                'method' => 'GET',
                'action' => 'reports',
            ),
            // Entidad común: Usuario (CRUDL)
            'users.create' => array(
                'method' => 'GET',
                'action' => 'create',
            ),
            'users.store' => array(
                'method' => 'POST',
                'action' => 'store',
            ),
            'users.index' => array(
                'method' => 'GET',
                'action' => 'index',
            ),
            'users.show' => array(
                'method' => 'GET',
                'action' => 'show',
            ),
            'users.edit' => array(
                'method' => 'GET',
                'action' => 'edit',
            ),
            'users.update' => array(
                'method' => 'POST',
                'action' => 'update',
            ),
            'users.delete' => array(
                'method' => 'POST',
                'action' => 'delete',
            ),
            // Entidad asignada #6: Gasto (CRUDL)
            'gastos.create' => array(
                'method' => 'GET',
                'action' => 'gastos.create',
            ),
            'gastos.store' => array(
                'method' => 'POST',
                'action' => 'gastos.store',
            ),
            'gastos.index' => array(
                'method' => 'GET',
                'action' => 'gastos.index',
            ),
            'gastos.show' => array(
                'method' => 'GET',
                'action' => 'gastos.show',
            ),
            'gastos.edit' => array(
                'method' => 'GET',
                'action' => 'gastos.edit',
            ),
            'gastos.update' => array(
                'method' => 'POST',
                'action' => 'gastos.update',
            ),
            'gastos.delete' => array(
                'method' => 'POST',
                'action' => 'gastos.delete',
            ),
            // Autenticación, Recuperación y Administración SMTP
            'auth.login' => array(
                'method' => 'GET',
                'action' => 'login',
            ),
            'auth.authenticate' => array(
                'method' => 'POST',
                'action' => 'authenticate',
            ),
            'auth.logout' => array(
                'method' => 'GET',
                'action' => 'logout',
            ),
            'auth.forgot' => array(
                'method' => 'GET',
                'action' => 'forgot',
            ),
            'auth.forgot.send' => array(
                'method' => 'POST',
                'action' => 'forgot.send',
            ),
            'auth.change-password' => array(
                'method' => 'GET',
                'action' => 'change_password',
            ),
            'auth.change-password.save' => array(
                'method' => 'POST',
                'action' => 'change_password.save',
            ),
            'emails.outbox' => array(
                'method' => 'GET',
                'action' => 'emails.outbox',
            ),
            'smtp.config' => array(
                'method' => 'GET',
                'action' => 'smtp.config',
            ),
            'smtp.save' => array(
                'method' => 'POST',
                'action' => 'smtp.save',
            ),
            'smtp.relay.save' => array(
                'method' => 'POST',
                'action' => 'smtp.relay.save',
            ),
            'smtp.test' => array(
                'method' => 'POST',
                'action' => 'smtp.test',
            ),
            // Documentación interactiva OpenAPI / Swagger
            'swagger' => array(
                'method' => 'GET',
                'action' => 'swagger',
            ),
            'openapi' => array(
                'method' => 'GET',
                'action' => 'openapi',
            ),
        );
    }
}