<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — COMANDO CQRS: CreateUserCommand
 * ============================================================================
 * CAPA: Aplicación (`Application/Services/Dto/Commands`)
 * PATRÓN: Command DTO (CQRS) + Inmutabilidad
 * PRINCIPIO SOLID: SRP (Transportar intención de creación de usuario)
 *
 * ¿QUÉ HACE?
 *   Objeto de transferencia inmutable que transporta los datos requeridos para registrar un nuevo usuario.
 *
 * ¿CÓMO LO HACE?
 *   Expone propiedades de solo lectura y un constructor tipado.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Desacopla la estructura de las peticiones Web/API de la lógica interna del caso de uso.
 * ============================================================================
 */

final class CreateUserCommand
{
    private string $id;
    private string $name;
    private string $email;
    private string $password;
    private string $role;

    public function __construct(
        string $id,
        string $name,
        string $email,
        string $password,
        string $role
    ) {
        $this->id = trim($id);
        $this->name = trim($name);
        $this->email = trim($email);
        $this->password = trim($password);
        $this->role = trim($role);
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
}
















