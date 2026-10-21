<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE ENTRADA (USE CASE): CreateUserUseCase
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/In`)
 * PATRÓN: Driving Port (Arquitectura Hexagonal) + Command Handler
 * PRINCIPIO SOLID: ISP (Interface Segregation) + DIP (Dependency Inversion)
 *
 * ¿QUÉ HACE?
 *   Define el contrato formal para registrar un nuevo Usuario a través del comando CreateUserCommand.
 *
 * ¿CÓMO LO HACE?
 *   Declara el método execute(CreateUserCommand $command): UserModel.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Permite que controladores Web, endpoints REST o comandos CLI interactúen con el caso de uso sin conocer su implementación concreta.
 * ============================================================================
 */

require_once __DIR__ . '/../../Services/Dto/Commands/CreateUserCommand.php';
require_once __DIR__ . '/../../../Domain/Models/UserModel.php';

interface CreateUserUseCase
{
    public function execute(CreateUserCommand $command): UserModel;
}









