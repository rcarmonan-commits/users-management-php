<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — DTO WEB REQUEST: UpdateUserRequest
 * ============================================================================
 * CAPA: Infraestructura (`Infrastructure/Entrypoints/Web/Controllers/Dto`)
 * PATRÓN: Request DTO
 * PRINCIPIO SOLID: SRP (Encapsular datos de formulario de edición)
 *
 * ¿QUÉ HACE?
 *   Encapsula los datos enviados al modificar un usuario existente en la interfaz Web.
 *
 * ¿CÓMO LO HACE?
 *   Maneja campos opcionales como la contraseña.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Evita el acceso disperso a $_POST en los métodos del controlador.
 * ============================================================================
 */

final class UpdateUserWebRequest
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