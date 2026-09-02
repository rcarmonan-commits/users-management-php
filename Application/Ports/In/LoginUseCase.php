<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE ENTRADA (USE CASE): LoginUseCase
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/In`)
 * PATRÓN: Driving Port + Authentication Boundary
 * PRINCIPIO SOLID: ISP + DIP
 *
 * ¿QUÉ HACE?
 *   Contrato para autenticar usuarios mediante credenciales (correo y contraseña).
 *
 * ¿CÓMO LO HACE?
 *   Declara execute(LoginCommand $command): UserModel.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Aísla el proceso de autenticación de los controladores HTTP y sesiones Web.
 * ============================================================================
 */

interface LoginUseCase
{
    public function execute(LoginCommand $command): UserModel;
}
