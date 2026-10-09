<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — SERVICIO DE APLICACIÓN: LoginService
 * ============================================================================
 * CAPA: Aplicación (`Application/Services`)
 * PATRÓN: Authentication Orchestrator + Security Boundary
 * PRINCIPIO SOLID: SRP (Validar credenciales de usuario)
 *
 * ¿QUÉ HACE?
 *   Implementa LoginUseCase, verificando las credenciales suministradas contra la base de datos.
 *
 * ¿CÓMO LO HACE?
 *   1. Busca el usuario por correo con GetUserByEmailPort.
 *   2. Si no existe, lanza InvalidCredentialsException.
 *   3. Comprueba la contraseña con password_verify().
 *   4. Si coincide, retorna el UserModel autenticado.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Protege el sistema contra enumeración de usuarios lanzando siempre la misma excepción ante credenciales inválidas.
 * ============================================================================
 */

require_once __DIR__ . '/../Ports/In/LoginUseCase.php';
require_once __DIR__ . '/../Ports/Out/GetUserByEmailPort.php';
require_once __DIR__ . '/Dto/Commands/LoginCommand.php';

require_once __DIR__ . '/../../Domain/Models/UserModel.php';
require_once __DIR__ . '/../../Domain/ValueObjects/UserEmail.php';
require_once __DIR__ . '/../../Domain/Enums/UserStatusEnum.php';
require_once __DIR__ . '/../../Domain/Exceptions/InvalidCredentialsException.php';

final class LoginService implements LoginUseCase
{
    private GetUserByEmailPort $getUserByEmailPort;

    public function __construct(GetUserByEmailPort $getUserByEmailPort)
    {
        $this->getUserByEmailPort = $getUserByEmailPort;
    }

    public function execute(LoginCommand $command): UserModel
    {
        $email = new UserEmail($command->getEmail());
        $user = $this->getUserByEmailPort->getByEmail($email);

        if ($user === null || !$user->password()->verifyPlain($command->getPassword())) {
            throw InvalidCredentialsException::becauseCredentialsAreInvalid();
        }

        if ($user->status() !== UserStatusEnum::ACTIVE) {
            throw InvalidCredentialsException::becauseUserIsNotActive();
        }

        return $user;
    }
}
