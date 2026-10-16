<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — COMANDO CQRS: UpdateUserCommand
 * ============================================================================
 * CAPA: Aplicación (`Application/Services/Dto/Commands`)
 * PATRÓN: Command DTO (CQRS)
 * PRINCIPIO SOLID: SRP (Transportar intención de actualización)
 *
 * ¿QUÉ HACE?
 *   Transporta los datos para actualizar el perfil de un usuario existente, incluyendo contraseña opcional.
 *
 * ¿CÓMO LO HACE?
 *   Encapsula id, nombre, correo, clave y rol en un objeto inmutable.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Permite transportar la intención de cambio sin mutar directamente entidades de dominio.
 * ============================================================================
 */

final class UpdateUserCommand
{
    private string $id;
    private string $name;
    private string $email;
    private string $password;
    private string $role;
    private string $status;

    public function __construct(
        string $id,
        string $name,
        string $email,
        string $password,
        string $role,
        string $status
    ) {
        $this->id = trim($id);
        $this->name = trim($name);
        $this->email = trim($email);
        $this->password = trim($password);
        $this->role = trim($role);
        $this->status = trim($status);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}