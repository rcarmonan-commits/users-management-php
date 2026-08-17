<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE SALIDA: GetUserByIdPort
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/Out`)
 * PATRÓN: Driven Port / Secondary SPI
 * PRINCIPIO SOLID: DIP + ISP
 *
 * ¿QUÉ HACE?
 *   Contrato para consultar un usuario por su UserId en la infraestructura de persistencia.
 *
 * ¿CÓMO LO HACE?
 *   Declara getById(UserId $id): ?UserModel.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Garantiza que la consulta use el Value Object UserId en lugar de primitivas desacopladas.
 * ============================================================================
 */

require_once __DIR__ . '/../../../Domain/Models/UserModel.php';
require_once __DIR__ . '/../../../Domain/ValueObjects/UserId.php';

interface GetUserByIdPort
{
    public function getById(UserId $userId): ?UserModel;
}