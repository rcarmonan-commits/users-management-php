<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — SERVICIO DE APLICACIÓN QUERY: GetAllUsersService
 * ============================================================================
 * CAPA: Aplicación (`Application/Services`)
 * PATRÓN: Query Handler (CQRS)
 * PRINCIPIO SOLID: SRP (Consultar usuarios sin modificar estado)
 *
 * ¿QUÉ HACE?
 *   Implementa GetAllUsersUseCase para recuperar todos los usuarios del sistema.
 *
 * ¿CÓMO LO HACE?
 *   Delega la consulta a GetAllUsersPort y retorna la lista de agregados UserModel.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Separa las consultas de lectura de los comandos de escritura conforme a CQRS.
 * ============================================================================
 */

require_once __DIR__ . '/../Ports/In/GetAllUsersUseCase.php';
require_once __DIR__ . '/../Ports/Out/GetAllUsersPort.php';

final class GetAllUsersService implements GetAllUsersUseCase
{
    private GetAllUsersPort $getAllUsersPort;

    public function __construct(GetAllUsersPort $getAllUsersPort)
    {
        $this->getAllUsersPort = $getAllUsersPort;
    }

    /**
     * @return UserModel[]
     */
    public function execute(GetAllUsersQuery $query): array
    {
        return $this->getAllUsersPort->getAll();
    }
}