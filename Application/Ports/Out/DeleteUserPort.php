<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE SALIDA: DeleteUserPort
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/Out`)
 * PATRÓN: Driven Port / Secondary SPI
 * PRINCIPIO SOLID: DIP + ISP
 *
 * ¿QUÉ HACE?
 *   Contrato para eliminar físicamente o dar de baja lógica a un usuario en el almacenamiento.
 *
 * ¿CÓMO LO HACE?
 *   Declara delete(UserId $id): void.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Permite delegar el borrado a la infraestructura respetando el contrato de la aplicación.
 * ============================================================================
 */

require_once __DIR__ . '/../../../Domain/ValueObjects/UserId.php';

interface DeleteUserPort
{
    public function delete(UserId $userId): void;
}

