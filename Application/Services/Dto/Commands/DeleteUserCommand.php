<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — COMANDO CQRS: DeleteUserCommand
 * ============================================================================
 * CAPA: Aplicación (`Application/Services/Dto/Commands`)
 * PATRÓN: Command DTO (CQRS)
 * PRINCIPIO SOLID: SRP (Transportar intención de borrado)
 *
 * ¿QUÉ HACE?
 *   Transporta el identificador del usuario que se desea eliminar del sistema.
 *
 * ¿CÓMO LO HACE?
 *   Encapsula el identificador en una propiedad inmutable.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Estandariza todas las operaciones de escritura bajo el patrón CQRS.
 * ============================================================================
 */

final class DeleteUserCommand
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