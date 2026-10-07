<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — CONSULTA CQRS: GetUserByIdQuery
 * ============================================================================
 * CAPA: Aplicación (`Application/Services/Dto/Queries`)
 * PATRÓN: Query DTO (CQRS)
 * PRINCIPIO SOLID: SRP (Transportar parámetros de búsqueda de usuario)
 *
 * ¿QUÉ HACE?
 *   Transporta el identificador único para buscar un usuario específico.
 *
 * ¿CÓMO LO HACE?
 *   Encapsula el id en un objeto inmutable.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Estandariza los parámetros de consulta sin depender de cadenas primitivas sueltas.
 * ============================================================================
 */

final class GetUserByIdQuery
{
    private string $id;

    public function __construct(string $id)
    {
        $this->id = trim($id);
    }

    public function getId(): string
    {
        return $this->id;
    }
}