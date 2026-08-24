<?php

declare(strict_types=1);

require_once __DIR__ . '/../Dto/Commands/CreateUserCommand.php';
require_once __DIR__ . '/../Dto/Commands/UpdateUserCommand.php';
require_once __DIR__ . '/../Dto/Commands/DeleteUserCommand.php';
require_once __DIR__ . '/../Dto/Queries/GetUserByIdQuery.php';

require_once __DIR__ . '/../../../Domain/Models/UserModel.php';
require_once __DIR__ . '/../../../Domain/ValueObjects/UserId.php';
require_once __DIR__ . '/../../../Domain/ValueObjects/UserName.php';
require_once __DIR__ . '/../../../Domain/ValueObjects/UserEmail.php';
require_once __DIR__ . '/../../../Domain/ValueObjects/UserPassword.php';
require_once __DIR__ . '/../../../Domain/Enums/UserStatusEnum.php';

final class UserApplicationMapper
{
    public static function fromCreateCommandToModel(
        CreateUserCommand $command,
        string $initialStatus = UserStatusEnum::ACTIVE
    ): UserModel {
        return new UserModel(
            new UserId($command->getId()),
            new UserName($command->getName()),
            new UserEmail($command->getEmail()),
            UserPassword::fromPlainText($command->getPassword()),
            $command->getRole(),
            $initialStatus
        );
    }

    public static function fromUpdateCommandToModel(
        UpdateUserCommand $command,
        ?UserPassword $fallbackPassword = null
    ): UserModel {
        $password = ($command->getPassword() !== '')
            ? UserPassword::fromPlainText($command->getPassword())
            : ($fallbackPassword ?? UserPassword::fromPlainText($command->getPassword()));

        return new UserModel(
            new UserId($command->getId()),
            new UserName($command->getName()),
            new UserEmail($command->getEmail()),
            $password,
            $command->getRole(),
            $command->getStatus()
        );
    }

    public static function fromGetUserByIdQueryToUserId(GetUserByIdQuery $query): UserId
    {
        return new UserId($query->getId());
    }

    public static function fromDeleteCommandToUserId(DeleteUserCommand $command): UserId
    {
        return new UserId($command->getId());
    }

    /**
     * @return array<string, string>
     */
    public static function fromModelToArray(UserModel $user): array
    {
        return array(
            'id' => $user->id()->value(),
            'name' => $user->name()->value(),
            'email' => $user->email()->value(),
            'password' => $user->password()->value(),
            'role' => $user->role(),
            'status' => $user->status(),
        );
    }

    /**
     * @param UserModel[] $users
     * @return array<int, array<string, string>>
     */
    public static function fromModelsToArray(array $users): array
    {
        $result = array();

        foreach ($users as $user) {
            $result[] = self::fromModelToArray($user);
        }

        return $result;
    }
}