<?php

declare(strict_types=1);

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