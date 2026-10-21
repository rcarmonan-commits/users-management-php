<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE ENTRADA (USE CASE): UpdateUserUseCase
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/In`)
 * PATRÓN: Driving Port + Command Handler
 * PRINCIPIO SOLID: ISP + DIP
 *
 * ¿QUÉ HACE?
 *   Contrato formal para actualizar los datos de perfil de un usuario existente.
 *
 * ¿CÓMO LO HACE?
 *   Declara execute(UpdateUserCommand $command): UserModel.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Permite modificar usuarios garantizando que se ejecuten todas las validaciones de negocio.
 * ============================================================================
 */

require_once __DIR__ . '/../../Services/Dto/Commands/UpdateUserCommand.php';
require_once __DIR__ . '/../../../Domain/Models/UserModel.php';

interface UpdateUserUseCase
{
    public function execute(UpdateUserCommand $command): UserModel;
}