<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE ENTRADA (USE CASE): DeleteUserUseCase
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/In`)
 * PATRÓN: Driving Port + Command Handler
 * PRINCIPIO SOLID: ISP + DIP
 *
 * ¿QUÉ HACE?
 *   Contrato formal para dar de baja o eliminar un usuario del sistema.
 *
 * ¿CÓMO LO HACE?
 *   Declara execute(DeleteUserCommand $command): void.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Centraliza la regla de negocio de borrado en un puerto de aplicación.
 * ============================================================================
 */

require_once __DIR__ . '/../../Services/Dto/Commands/DeleteUserCommand.php';

interface DeleteUserUseCase
{
    public function execute(DeleteUserCommand $command): void;
}

