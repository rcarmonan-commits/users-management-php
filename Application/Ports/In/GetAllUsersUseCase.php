<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE ENTRADA (USE CASE): GetAllUsersUseCase
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/In`)
 * PATRÓN: Driving Port (Arquitectura Hexagonal) + Query Handler (CQRS)
 * PRINCIPIO SOLID: ISP + DIP
 *
 * ¿QUÉ HACE?
 *   Define el contrato de lectura para consultar y listar todos los usuarios del sistema.
 *
 * ¿CÓMO LO HACE?
 *   Declara execute(GetAllUsersQuery $query): array.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Segrega la responsabilidad de lectura de los comandos de escritura conforme al patrón CQRS.
 * ============================================================================
 */

require_once __DIR__ . '/../../Services/Dto/Queries/GetAllUsersQuery.php';
require_once __DIR__ . '/../../../Domain/Models/UserModel.php';

interface GetAllUsersUseCase
{
    /**
     * @return UserModel[]
     */
    public function execute(GetAllUsersQuery $query): array;
}

