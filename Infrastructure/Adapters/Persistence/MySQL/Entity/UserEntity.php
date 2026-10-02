<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — ENTIDAD DE PERSISTENCIA: UserEntity
 * ============================================================================
 * CAPA: Infraestructura (`Infrastructure/Adapters/Persistence/MySQL/Entity`)
 * PATRÓN: Persistence Entity / Active Record State Representation
 * PRINCIPIO SOLID: SRP (Representar la estructura física de la tabla users)
 *
 * ¿QUÉ HACE?
 *   Representa fielmente la estructura de columnas y tipos de datos de la tabla `users` en la base de datos relacional.
 *
 * ¿CÓMO LO HACE?
 *   Mapea directamente id, name, email, password, role y status.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Evita contaminar el modelo de dominio con anotaciones o tipos de datos exclusivos de la base de datos.
 * ============================================================================
 */

final class UserEntity
{
    private string $id;
    private string $name;
    private string $email;
    private string $password;
    private string $role;
    private string $status;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        string $id,
        string $name,
        string $email,
        string $password,
        string $role,
        string $status,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = trim($id);
        $this->name = trim($name);
        $this->email = trim($email);
        $this->password = trim($password);
        $this->role = trim($role);
        $this->status = trim($status);
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }
}