<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — SERVICIO DE APLICACIÓN COMMAND: DeleteUserService
 * ============================================================================
 * CAPA: Aplicación (`Application/Services`)
 * PATRÓN: Command Handler (CQRS)
 * PRINCIPIO SOLID: SRP + DIP
 *
 * ¿QUÉ HACE?
 *   Implementa DeleteUserUseCase para eliminar un usuario del sistema.
 *
 * ¿CÓMO LO HACE?
 *   Verifica existencia previa con GetUserByIdPort y ejecuta DeleteUserPort.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Asegura que no se puedan eliminar usuarios inexistentes y permite registrar auditoría del borrado.
 * ============================================================================
 */

require_once __DIR__ . '/../Ports/In/DeleteUserUseCase.php';
require_once __DIR__ . '/../Ports/Out/DeleteUserPort.php';
require_once __DIR__ . '/../Ports/Out/GetUserByIdPort.php';
require_once __DIR__ . '/Mappers/UserApplicationMapper.php';

require_once __DIR__ . '/../../Domain/Exceptions/UserNotFoundException.php';

final class DeleteUserService implements DeleteUserUseCase
{
    private DeleteUserPort $deleteUserPort;
    private GetUserByIdPort $getUserByIdPort;

    public function __construct(
        DeleteUserPort $deleteUserPort,
        GetUserByIdPort $getUserByIdPort
    ) {
        $this->deleteUserPort = $deleteUserPort;
        $this->getUserByIdPort = $getUserByIdPort;
    }

    public function execute(DeleteUserCommand $command): void
    {
        $userId = UserApplicationMapper::fromDeleteCommandToUserId($command);
        $existingUser = $this->getUserByIdPort->getById($userId);

        if ($existingUser === null) {
            throw UserNotFoundException::becauseIdWasNotFound($userId->value());
        }

        $this->deleteUserPort->delete($userId);
    }
}