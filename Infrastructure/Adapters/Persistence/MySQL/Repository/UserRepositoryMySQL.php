<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — ADAPTADOR SECUNDARIO (REPOSITORIO MYSQL): UserRepositoryMySQL
 * ============================================================================
 * CAPA: Infraestructura (`Infrastructure/Adapters/Persistence/MySQL/Repository`)
 * PATRÓN: Repository Pattern (Fowler) + Data Mapper + Secondary Adapter (Hexagonal)
 * PRINCIPIO SOLID: DIP (Implementa puertos definidos por la Aplicación) + SRP (Persistencia SQL)
 *
 * ¿QUÉ HACE?
 *   Implementa los contratos SaveUserPort, UpdateUserPort, GetUserByIdPort, GetUserByEmailPort, GetAllUsersPort y DeleteUserPort utilizando PDO y sentencias SQL parametrizadas.
 *
 * ¿CÓMO LO HACE?
 *   1. Utiliza consultas preparadas para proteger contra inyección SQL.
 *   2. Convierte entre filas relacionales (Entity/DTO) y modelos de dominio con UserPersistenceMapper.
 *   3. Soporta entornos MySQL locales y cloud (Alwaysdata) de forma resiliente.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Mantiene la base de datos como un detalle técnico reemplazable. El dominio y la aplicación nunca conocen la sintaxis SQL.
 * ============================================================================
 */

require_once __DIR__ . '/../../../../../Application/Ports/Out/SaveUserPort.php';
require_once __DIR__ . '/../../../../../Application/Ports/Out/UpdateUserPort.php';
require_once __DIR__ . '/../../../../../Application/Ports/Out/GetUserByIdPort.php';
require_once __DIR__ . '/../../../../../Application/Ports/Out/GetUserByEmailPort.php';
require_once __DIR__ . '/../../../../../Application/Ports/Out/GetAllUsersPort.php';
require_once __DIR__ . '/../../../../../Application/Ports/Out/DeleteUserPort.php';

require_once __DIR__ . '/../Mapper/UserPersistenceMapper.php';
require_once __DIR__ . '/../../../../../Domain/Models/UserModel.php';
require_once __DIR__ . '/../../../../../Domain/ValueObjects/UserId.php';
require_once __DIR__ . '/../../../../../Domain/ValueObjects/UserEmail.php';

final class UserRepositoryMySQL implements
    SaveUserPort,
    UpdateUserPort,
    GetUserByIdPort,
    GetUserByEmailPort,
    GetAllUsersPort,
    DeleteUserPort
{
    private PDO $pdo;
    private UserPersistenceMapper $mapper;

    public function __construct(PDO $pdo, UserPersistenceMapper $mapper)
    {
        $this->pdo = $pdo;
        $this->mapper = $mapper;
    }

    public function save(UserModel $user): UserModel
    {
        $dto = $this->mapper->fromModelToDto($user);

        $sql = '
            INSERT INTO users (
                id,
                name,
                email,
                password,
                role,
                status,
                created_at,
                updated_at
            ) VALUES (
                :id,
                :name,
                :email,
                :password,
                :role,
                :status,
                NOW(),
                NOW()
            )
        ';

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(
            ':id' => $dto->id(),
            ':name' => $dto->name(),
            ':email' => $dto->email(),
            ':password' => $dto->password(),
            ':role' => $dto->role(),
            ':status' => $dto->status(),
        ));

        $savedUser = $this->getById(new UserId($dto->id()));

        if ($savedUser === null) {
            throw new RuntimeException('The user could not be recovered after save.');
        }

        return $savedUser;
    }

    public function update(UserModel $user): UserModel
    {
        $dto = $this->mapper->fromModelToDto($user);

        $sql = '
            UPDATE users
               SET name = :name,
                   email = :email,
                   password = :password,
                   role = :role,
                   status = :status,
                   updated_at = NOW()
             WHERE id = :id
        ';

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(
            ':id' => $dto->id(),
            ':name' => $dto->name(),
            ':email' => $dto->email(),
            ':password' => $dto->password(),
            ':role' => $dto->role(),
            ':status' => $dto->status(),
        ));

        $updatedUser = $this->getById(new UserId($dto->id()));

        if ($updatedUser === null) {
            throw new RuntimeException('The user could not be recovered after update.');
        }

        return $updatedUser;
    }

    public function getById(UserId $userId): ?UserModel
    {
        $sql = '
            SELECT
                id,
                name,
                email,
                password,
                role,
                status,
                created_at,
                updated_at
            FROM users
            WHERE id = :id
            LIMIT 1
        ';

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(
            ':id' => $userId->value(),
        ));

        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->mapper->fromRowToModel($row);
    }

    public function getByEmail(UserEmail $email): ?UserModel
    {
        $sql = '
            SELECT
                id,
                name,
                email,
                password,
                role,
                status,
                created_at,
                updated_at
            FROM users
            WHERE email = :email
            LIMIT 1
        ';

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(
            ':email' => $email->value(),
        ));

        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->mapper->fromRowToModel($row);
    }

    /**
     * @return UserModel[]
     */
    public function getAll(): array
    {
        $sql = '
            SELECT
                id,
                name,
                email,
                password,
                role,
                status,
                created_at,
                updated_at
            FROM users
            ORDER BY name ASC
        ';

        $statement = $this->pdo->query($sql);
        $rows = $statement->fetchAll();

        return $this->mapper->fromRowsToModels($rows);
    }

    public function delete(UserId $userId): void
    {
        $sql = 'DELETE FROM users WHERE id = :id';

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(
            ':id' => $userId->value(),
        ));
    }
}