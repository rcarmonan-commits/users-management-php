<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE SALIDA: SaveUserPort
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/Out`)
 * PATRÓN: Driven Port (Arquitectura Hexagonal) / SPI (Service Provider Interface)
 * PRINCIPIO SOLID: DIP (La aplicación define la interfaz; la infraestructura la implementa)
 *
 * ¿QUÉ HACE?
 *   Contrato que la capa de aplicación exige a cualquier adaptador de base de datos para persistir un nuevo usuario.
 *
 * ¿CÓMO LO HACE?
 *   Declara save(UserModel $user): UserModel.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Cumple la Inversión de Dependencias: la aplicación nunca depende de MySQL, PDO ni SQL.
 * ============================================================================
 */

require_once __DIR__ . '/../../../Domain/Models/UserModel.php';

interface SaveUserPort
{
    public function save(UserModel $user): UserModel;
}
