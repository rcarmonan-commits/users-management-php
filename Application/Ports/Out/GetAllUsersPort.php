<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE SALIDA: GetAllUsersPort
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/Out`)
 * PATRÓN: Driven Port / Secondary SPI
 * PRINCIPIO SOLID: DIP + ISP
 *
 * ¿QUÉ HACE?
 *   Contrato exigido para recuperar la lista de usuarios desde el almacenamiento de datos.
 *
 * ¿CÓMO LO HACE?
 *   Declara getAll(): array.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Permite sustituir MySQL por InMemory o MongoDB sin alterar la lógica de aplicación.
 * ============================================================================
 */

require_once __DIR__ . '/../../../Domain/Models/UserModel.php';

interface GetAllUsersPort
{
    /**
     * @return UserModel[]
     */
    public function getAll(): array;
}
