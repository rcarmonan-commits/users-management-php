<?php

declare(strict_types=1);

require_once __DIR__ . '/../Ports/In/UpdateUserUseCase.php';
require_once __DIR__ . '/../Ports/Out/UpdateUserPort.php';
require_once __DIR__ . '/../Ports/Out/GetUserByIdPort.php';
require_once __DIR__ . '/../Ports/Out/GetUserByEmailPort.php';
require_once __DIR__ . '/Mappers/UserApplicationMapper.php';
require_once __DIR__ . '/EmailNotificationService.php';

require_once __DIR__ . '/../../Domain/Exceptions/UserAlreadyExistsException.php';
require_once __DIR__ . '/../../Domain/Exceptions/UserNotFoundException.php';
require_once __DIR__ . '/../../Domain/Models/UserModel.php';
require_once __DIR__ . '/../../Domain/ValueObjects/UserId.php';
require_once __DIR__ . '/../../Domain/ValueObjects/UserEmail.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — CASO DE USO COMMAND (CQRS): UpdateUserService
 * ============================================================================
 * CAPA: Aplicación (`Application/Services`)
 * PATRÓN: Caso de Uso de Escritura (Command Handler) + Inyección por Constructor
 *
 * ¿QUÉ HACE?
 *   Orquesta la actualización transaccional de un usuario existente verificando
 *   existencia previa, unicidad de correo electrónico y preservación opcional de clave.
 *
 * ¿CÓMO FUNCIONA EN EL FLUJO?
 *   1. Recupera el usuario actual por `UserId` mediante `GetUserByIdPort`.
 *   2. Verifica que el nuevo `UserEmail` no pertenezca a otro usuario distinto.
 *   3. Construye la nueva instancia inmutable `UserModel` pasando `$currentUser->password()`
 *      como respaldo seguro si no se suministró una nueva contraseña.
 *   4. Persiste a través de `UpdateUserPort` y dispara la notificación de auditoría.
 * ============================================================================
 */
final class UpdateUserService implements UpdateUserUseCase
{
    private UpdateUserPort $updateUserPort;
    private GetUserByIdPort $getUserByIdPort;
    private GetUserByEmailPort $getUserByEmailPort;
    private EmailNotificationService $emailNotification;

    public function __construct(
        UpdateUserPort $updateUserPort,
        GetUserByIdPort $getUserByIdPort,
        GetUserByEmailPort $getUserByEmailPort,
        EmailNotificationService $emailNotification
    ) {
        $this->updateUserPort = $updateUserPort;
        $this->getUserByIdPort = $getUserByIdPort;
        $this->getUserByEmailPort = $getUserByEmailPort;
        $this->emailNotification = $emailNotification;
    }

    public function execute(UpdateUserCommand $command): UserModel
    {
        $userId = new UserId($command->getId());
        $currentUser = $this->getUserByIdPort->getById($userId);

        if ($currentUser === null) {
            throw UserNotFoundException::becauseIdWasNotFound($userId->value());
        }

        $newEmail = new UserEmail($command->getEmail());
        $userWithSameEmail = $this->getUserByEmailPort->getByEmail($newEmail);

        if ($userWithSameEmail !== null && !$userWithSameEmail->id()->equals($userId)) {
            throw UserAlreadyExistsException::becauseEmailAlreadyExists($newEmail->value());
        }

        $userToUpdate = UserApplicationMapper::fromUpdateCommandToModel(
            $command,
            $currentUser->password()
        );

        $updatedUser = $this->updateUserPort->update($userToUpdate);
        $this->emailNotification->notifyUserUpdated($updatedUser);

        return $updatedUser;
    }
}