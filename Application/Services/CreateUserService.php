<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — SERVICIO DE APLICACIÓN COMMAND: CreateUserService
 * ============================================================================
 * CAPA: Aplicación (`Application/Services`)
 * PATRÓN: Command Handler (CQRS) + Service Orchestrator
 * PRINCIPIO SOLID: SRP (Orquestar el registro de usuario) + DIP
 *
 * ¿QUÉ HACE?
 *   Implementa el caso de uso CreateUserUseCase, orquestando la validación de duplicados, persistencia y eventos.
 *
 * ¿CÓMO LO HACE?
 *   1. Verifica que el correo no esté registrado previamente con GetUserByEmailPort.
 *   2. Mapea el comando a UserModel con UserApplicationMapper.
 *   3. Persiste el agregado con SaveUserPort.
 *   4. Opcionalmente publica el evento de dominio UserCreatedDomainEvent.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Coordina la lógica de negocio sin mezclar detalles de transporte (HTTP) ni persistencia (SQL).
 * ============================================================================
 */

require_once __DIR__ . '/../Ports/In/CreateUserUseCase.php';
require_once __DIR__ . '/../Ports/Out/SaveUserPort.php';
require_once __DIR__ . '/../Ports/Out/GetUserByEmailPort.php';
require_once __DIR__ . '/Mappers/UserApplicationMapper.php';
require_once __DIR__ . '/EmailNotificationService.php';
require_once __DIR__ . '/../../Domain/Exceptions/UserAlreadyExistsException.php';
require_once __DIR__ . '/../../Domain/ValueObjects/UserEmail.php';

final class CreateUserService implements CreateUserUseCase
{
    private SaveUserPort $saveUserPort;
    private GetUserByEmailPort $getUserByEmailPort;
    private EmailNotificationService $emailNotification;

    public function __construct(
        SaveUserPort $saveUserPort,
        GetUserByEmailPort $getUserByEmailPort,
        EmailNotificationService $emailNotification
    ) {
        $this->saveUserPort = $saveUserPort;
        $this->getUserByEmailPort = $getUserByEmailPort;
        $this->emailNotification = $emailNotification;
    }

    public function execute(CreateUserCommand $command): UserModel
    {
        $email = new UserEmail($command->getEmail());
        $existingUser = $this->getUserByEmailPort->getByEmail($email);

        if ($existingUser !== null) {
            throw UserAlreadyExistsException::becauseEmailAlreadyExists($email->value());
        }

        $user = UserApplicationMapper::fromCreateCommandToModel($command);

        $savedUser = $this->saveUserPort->save($user);
        $this->emailNotification->notifyUserCreated($savedUser, $command->getPassword());

        return $savedUser;
    }
}