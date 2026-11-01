<?php

declare(strict_types=1);

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
            // Autenticación y recuperación
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
            // Documentación OpenAPI / Swagger
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