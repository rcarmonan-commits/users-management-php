<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — DTO WEB REQUEST: LoginWebRequest
 * ============================================================================
 * CAPA: Infraestructura (`Infrastructure/Entrypoints/Web/Controllers/Dto`)
 * PATRÓN: Request DTO
 * PRINCIPIO SOLID: SRP (Encapsular datos del formulario de login)
 *
 * ¿QUÉ HACE?
 *   Encapsula las credenciales ingresadas en la pantalla de inicio de sesión.
 *
 * ¿CÓMO LO HACE?
 *   Transporta email y password hacia el controlador de autenticación.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Mantiene la coherencia de tipos en el flujo de login Web.
 * ============================================================================
 */

final class LoginWebRequest
{
    private string $email;
    private string $password;

    public function __construct(string $email, string $password)
    {
        $this->email = trim(strtolower($email));
        $this->password = $password;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
