<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — DTO WEB REQUEST: CreateUserRequest
 * ============================================================================
 * CAPA: Infraestructura (`Infrastructure/Entrypoints/Web/Controllers/Dto`)
 * PATRÓN: Request DTO
 * PRINCIPIO SOLID: SRP (Encapsular datos recibidos por formulario de creación)
 *
 * ¿QUÉ HACE?
 *   Encapsula los datos enviados por el usuario en el formulario Web de registro.
 *
 * ¿CÓMO LO HACE?
 *   Expone métodos para extraer y validar datos provenientes de $_POST.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Provee una interfaz fuertemente tipada para los datos del formulario.
 * ============================================================================
 */

final class CreateUserWebRequest
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