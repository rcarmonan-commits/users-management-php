<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — COMANDO CQRS: LoginCommand
 * ============================================================================
 * CAPA: Aplicación (`Application/Services/Dto/Commands`)
 * PATRÓN: Command DTO (CQRS)
 * PRINCIPIO SOLID: SRP (Transportar credenciales de inicio de sesión)
 *
 * ¿QUÉ HACE?
 *   Transporta el correo y la contraseña en texto plano para el proceso de autenticación.
 *
 * ¿CÓMO LO HACE?
 *   Expone getters inmutables para el servicio LoginService.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Evita acoplar la autenticación a superglobales $_POST o peticiones HTTP directas.
 * ============================================================================
 */

final class LoginCommand
{
    private string $email;
    private string $password;

    public function __construct(string $email, string $password)
    {
        $this->email = trim($email);
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
