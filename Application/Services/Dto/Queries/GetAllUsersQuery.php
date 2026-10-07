<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — CONSULTA CQRS: GetAllUsersQuery
 * ============================================================================
 * CAPA: Aplicación (`Application/Services/Dto/Queries`)
 * PATRÓN: Query DTO (CQRS)
 * PRINCIPIO SOLID: SRP (Representar la intención de lectura de usuarios)
 *
 * ¿QUÉ HACE?
 *   Objeto que representa la intención de consultar la lista de todos los usuarios.
 *
 * ¿CÓMO LO HACE?
 *   Actúa como marcador de consulta para el manejador GetAllUsersService.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Mantiene la simetría arquitectónica de CQRS entre Comandos y Consultas.
 * ============================================================================
 */

final class GetAllUsersQuery
{
}