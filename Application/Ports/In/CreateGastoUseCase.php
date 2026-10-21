<?php

declare(strict_types=1);

require_once __DIR__ . '/../../Services/Dto/Commands/CreateGastoCommand.php';
require_once __DIR__ . '/../../../Domain/Models/GastoModel.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE ENTRADA (DRIVING PORT): CreateGastoUseCase
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/In`)
 * PATRÓN: Caso de Uso de Comando (C de CRUDL) — Arquitectura Hexagonal
 *
 * ¿QUÉ HACE?
 *   Define el puerto primario que invocan los controladores Web y REST para crear
 *   un nuevo `Gasto`. Los controladores dependen de esta interfaz, nunca de la clase
 *   concreta `CreateGastoService` (Principio DIP).
 * ============================================================================
 */
interface CreateGastoUseCase
{
    public function execute(CreateGastoCommand $command): GastoModel;
}