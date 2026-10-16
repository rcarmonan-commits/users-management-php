<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — OBJETO COMMAND CQRS: DeleteGastoCommand
 * ============================================================================
 * CAPA: Aplicación (`Application/Services/Dto/Commands`)
 * PATRÓN: CQRS — Comando Inmutable de Eliminación
 *
 * ¿QUÉ HACE?
 *   Representa la orden explícita de eliminar el gasto identificado por `$id`.
 * ============================================================================
 */
final class DeleteGastoCommand
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