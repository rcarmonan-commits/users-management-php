<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE ENTRADA (USE CASE): GetUserByIdUseCase
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/In`)
 * PATRÓN: Driving Port + Query Handler
 * PRINCIPIO SOLID: ISP + DIP
 *
 * ¿QUÉ HACE?
 *   Contrato para recuperar un usuario específico a partir de su identificador único.
 *
 * ¿CÓMO LO HACE?
 *   Declara execute(GetUserByIdQuery $query): UserModel.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Aísla la búsqueda por id en una interfaz concisa y fácilmente mockeable en pruebas.
 * ============================================================================
 */

require_once __DIR__ . '/../../Services/Dto/Queries/GetUserByIdQuery.php';
require_once __DIR__ . '/../../../Domain/Models/UserModel.php';

interface GetUserByIdUseCase
{
    public function execute(GetUserByIdQuery $query): UserModel;
}