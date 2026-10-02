<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../Application/Ports/Out/SaveUserPort.php';
require_once __DIR__ . '/../../../../Application/Ports/Out/UpdateUserPort.php';
require_once __DIR__ . '/../../../../Application/Ports/Out/GetUserByIdPort.php';
require_once __DIR__ . '/../../../../Application/Ports/Out/GetUserByEmailPort.php';
require_once __DIR__ . '/../../../../Application/Ports/Out/GetAllUsersPort.php';
require_once __DIR__ . '/../../../../Application/Ports/Out/DeleteUserPort.php';

require_once __DIR__ . '/../../../../Domain/Models/UserModel.php';
require_once __DIR__ . '/../../../../Domain/ValueObjects/UserId.php';
require_once __DIR__ . '/../../../../Domain/ValueObjects/UserName.php';
require_once __DIR__ . '/../../../../Domain/ValueObjects/UserEmail.php';
require_once __DIR__ . '/../../../../Domain/ValueObjects/UserPassword.php';
require_once __DIR__ . '/../../../../Domain/Enums/UserRoleEnum.php';
require_once __DIR__ . '/../../../../Domain/Enums/UserStatusEnum.php';

final class InMemoryUserRepository implements
    SaveUserPort,
    UpdateUserPort,
    GetUserByIdPort,
    GetUserByEmailPort,
    GetAllUsersPort,
    DeleteUserPort
{
    /** @var array<string, UserModel> */
    private array $items = array();

    private ?string $persistenceFile;

    public function __construct(bool $seedDefaultAdmin = true, ?string $persistenceFile = null)
    {
        $this->persistenceFile = $persistenceFile;

        if ($this->persistenceFile !== null && is_file($this->persistenceFile)) {
            $this->loadFromDisk();
        }

        if ($seedDefaultAdmin && empty($this->items)) {
            $admin = new UserModel(
                new UserId('00000000-0000-4000-8000-000000000001'),
                new UserName('Administrador'),
                new UserEmail('admin@crud.com'),
                UserPassword::fromPlainText('Admin12345'),
                UserRoleEnum::ADMIN,
                UserStatusEnum::ACTIVE
            );
            $this->items[$admin->id()->value()] = $admin;
            $this->flushToDisk();
        }
    }

    public function save(UserModel $user): UserModel
    {
        $this->items[$user->id()->value()] = $user;
        $this->flushToDisk();

        return $user;
    }

    public function update(UserModel $user): UserModel
    {
        $this->items[$user->id()->value()] = $user;
        $this->flushToDisk();

        return $user;
    }

    public function getById(UserId $userId): ?UserModel
    {
        return $this->items[$userId->value()] ?? null;
    }

    public function getByEmail(UserEmail $email): ?UserModel
    {
        foreach ($this->items as $user) {
            if ($user->email()->equals($email)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @return UserModel[]
     */
    public function getAll(): array
    {
        $list = array_values($this->items);
        usort(
            $list,
            static fn(UserModel $a, UserModel $b): int => strcmp($a->name()->value(), $b->name()->value())
        );

        return $list;
    }

    public function delete(UserId $userId): void
    {
        unset($this->items[$userId->value()]);
        $this->flushToDisk();
    }

    private function loadFromDisk(): void
    {
        if ($this->persistenceFile === null || !is_file($this->persistenceFile)) {
            return;
        }

        $raw = (string) file_get_contents($this->persistenceFile);
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return;
        }

        foreach ($decoded as $row) {
            if (!is_array($row) || !isset($row['id'], $row['name'], $row['email'], $row['password'], $row['role'], $row['status'])) {
                continue;
            }

            $model = new UserModel(
                new UserId((string) $row['id']),
                new UserName((string) $row['name']),
                new UserEmail((string) $row['email']),
                UserPassword::fromHash((string) $row['password']),
                (string) $row['role'],
                (string) $row['status']
            );
            $this->items[$model->id()->value()] = $model;
        }
    }

    private function flushToDisk(): void
    {
        if ($this->persistenceFile === null) {
            return;
        }

        $dir = dirname($this->persistenceFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $payload = array();
        foreach ($this->items as $item) {
            $payload[] = $item->toArray();
        }

        @file_put_contents($this->persistenceFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}