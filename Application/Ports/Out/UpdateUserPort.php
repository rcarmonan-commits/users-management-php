<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE SALIDA: UpdateUserPort
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/Out`)
 * PATRÓN: Driven Port / Secondary SPI
 * PRINCIPIO SOLID: DIP + ISP
 *
 * ¿QUÉ HACE?
 *   Contrato para guardar modificaciones de un usuario existente en la persistencia.
 *
 * ¿CÓMO LO HACE?
 *   Declara update(UserModel $user): UserModel.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Mantiene las operaciones de actualización estrictamente tipadas con el agregado UserModel.
 * ============================================================================
 */

require_once __DIR__ . '/../../../Domain/Models/UserModel.php';

interface UpdateUserPort
{
    public function update(UserModel $user): UserModel;
}
