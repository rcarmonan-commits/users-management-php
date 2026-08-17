<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE SALIDA: GetUserByEmailPort
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/Out`)
 * PATRÓN: Driven Port / Secondary SPI
 * PRINCIPIO SOLID: DIP + ISP
 *
 * ¿QUÉ HACE?
 *   Contrato para buscar usuarios por su dirección de correo UserEmail.
 *
 * ¿CÓMO LO HACE?
 *   Declara getByEmail(UserEmail $email): ?UserModel.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Indispensable para autenticación (login) y validación de correos duplicados.
 * ============================================================================
 */

require_once __DIR__ . '/../../../Domain/Models/UserModel.php';
require_once __DIR__ . '/../../../Domain/ValueObjects/UserEmail.php';

interface GetUserByEmailPort
{
    public function getByEmail(UserEmail $email): ?UserModel;
}



