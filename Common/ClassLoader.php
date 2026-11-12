<?php

declare(strict_types=1);

final class ClassLoader
{
    /**
     * @var array<string, string>
     */
    private static array $classMap = array(
        // Excepciones de Dominio — Usuario
        'InvalidUserEmailException' => 'Domain/Exceptions/InvalidUserEmailException.php',
        'InvalidUserIdException' => 'Domain/Exceptions/InvalidUserIdException.php',
        'InvalidUserNameException' => 'Domain/Exceptions/InvalidUserNameException.php',
        'InvalidUserPasswordException' => 'Domain/Exceptions/InvalidUserPasswordException.php',
        'InvalidUserRoleException' => 'Domain/Exceptions/InvalidUserRoleException.php',
        'InvalidUserStatusException' => 'Domain/Exceptions/InvalidUserStatusException.php',
        'UserAlreadyExistsException' => 'Domain/Exceptions/UserAlreadyExistsException.php',
        'UserNotFoundException' => 'Domain/Exceptions/UserNotFoundException.php',
        'InvalidCredentialsException' => 'Domain/Exceptions/InvalidCredentialsException.php',
        'EmailSenderException' => 'Domain/Exceptions/EmailSenderException.php',

        // Excepciones de Dominio — Gasto (#6)
        'InvalidGastoIdException' => 'Domain/Exceptions/InvalidGastoIdException.php',
        'InvalidGastoFechaException' => 'Domain/Exceptions/InvalidGastoFechaException.php',
        'InvalidGastoMontoException' => 'Domain/Exceptions/InvalidGastoMontoException.php',
        'InvalidGastoIvaConsistencyException' => 'Domain/Exceptions/InvalidGastoIvaConsistencyException.php',
        'InvalidGastoTextoException' => 'Domain/Exceptions/InvalidGastoTextoException.php',
        'GastoNotFoundException' => 'Domain/Exceptions/GastoNotFoundException.php',

        // Enums de Dominio
        'UserRoleEnum' => 'Domain/Enums/UserRoleEnum.php',
        'UserStatusEnum' => 'Domain/Enums/UserStatusEnum.php',
        'GastoIvaRateEnum' => 'Domain/Enums/GastoIvaRateEnum.php',

        // Value Objects de Dominio
        'UserId' => 'Domain/ValueObjects/UserId.php',
        'UserName' => 'Domain/ValueObjects/UserName.php',
        'UserEmail' => 'Domain/ValueObjects/UserEmail.php',
        'UserPassword' => 'Domain/ValueObjects/UserPassword.php',
        'RoleId' => 'Domain/ValueObjects/RoleId.php',
        'GastoId' => 'Domain/ValueObjects/GastoId.php',
        'GastoFecha' => 'Domain/ValueObjects/GastoFecha.php',
        'GastoMonto' => 'Domain/ValueObjects/GastoMonto.php',
        'GastoLugar' => 'Domain/ValueObjects/GastoLugar.php',
        'GastoDescripcion' => 'Domain/ValueObjects/GastoDescripcion.php',

        // Modelos y Eventos de Dominio
        'UserModel' => 'Domain/Models/UserModel.php',
        'EmailDestinationModel' => 'Domain/Models/EmailDestinationModel.php',
        'GastoModel' => 'Domain/Models/GastoModel.php',
        'DomainEvent' => 'Domain/Events/EventDomain.php',
        'UserCreatedDomainEvent' => 'Domain/Events/UserCreatedDomainEvent.php',
        'UserUpdatedDomainEvent' => 'Domain/Events/UserUpdatedDomainEvent.php',
        'UserDeletedDomainEvent' => 'Domain/Events/UserDeletedDomainEvent.php',
        'GastoCreatedDomainEvent' => 'Domain/Events/GastoCreatedDomainEvent.php',
        'GastoUpdatedDomainEvent' => 'Domain/Events/GastoUpdatedDomainEvent.php',
        'GastoDeletedDomainEvent' => 'Domain/Events/GastoDeletedDomainEvent.php',

        // Puertos de Entrada (Use Cases)
        'CreateUserUseCase' => 'Application/Ports/In/CreateUserUseCase.php',
        'UpdateUserUseCase' => 'Application/Ports/In/UpdateUserUseCase.php',
        'GetUserByIdUseCase' => 'Application/Ports/In/GetUserByIdUseCase.php',
        'GetAllUsersUseCase' => 'Application/Ports/In/GetAllUsersUseCase.php',
        'DeleteUserUseCase' => 'Application/Ports/In/DeleteUserUseCase.php',
        'LoginUseCase' => 'Application/Ports/In/LoginUseCase.php',
        'CreateGastoUseCase' => 'Application/Ports/In/CreateGastoUseCase.php',
        'UpdateGastoUseCase' => 'Application/Ports/In/UpdateGastoUseCase.php',
        'GetGastoByIdUseCase' => 'Application/Ports/In/GetGastoByIdUseCase.php',
        'GetAllGastosUseCase' => 'Application/Ports/In/GetAllGastosUseCase.php',
        'DeleteGastoUseCase' => 'Application/Ports/In/DeleteGastoUseCase.php',

        // Puertos de Salida
        'SaveUserPort' => 'Application/Ports/Out/SaveUserPort.php',
        'UpdateUserPort' => 'Application/Ports/Out/UpdateUserPort.php',
        'GetUserByIdPort' => 'Application/Ports/Out/GetUserByIdPort.php',
        'GetUserByEmailPort' => 'Application/Ports/Out/GetUserByEmailPort.php',
        'GetAllUsersPort' => 'Application/Ports/Out/GetAllUsersPort.php',
        'DeleteUserPort' => 'Application/Ports/Out/DeleteUserPort.php',
        'EmailSenderPort' => 'Application/Ports/Out/EmailSenderPort.php',
        'SaveGastoPort' => 'Application/Ports/Out/SaveGastoPort.php',
        'UpdateGastoPort' => 'Application/Ports/Out/UpdateGastoPort.php',
        'GetGastoByIdPort' => 'Application/Ports/Out/GetGastoByIdPort.php',
        'GetAllGastosPort' => 'Application/Ports/Out/GetAllGastosPort.php',
        'DeleteGastoPort' => 'Application/Ports/Out/DeleteGastoPort.php',
        'CachePort' => 'Application/Ports/Out/CachePort.php',
        'DomainEventPublisherPort' => 'Application/Ports/Out/DomainEventPublisherPort.php',
        'TaxIndicatorRestPort' => 'Application/Ports/Out/TaxIndicatorRestPort.php',

        // Commands, Queries y Responses de Aplicación
        'CreateUserCommand' => 'Application/Services/Dto/Commands/CreateUserCommand.php',
        'UpdateUserCommand' => 'Application/Services/Dto/Commands/UpdateUserCommand.php',
        'DeleteUserCommand' => 'Application/Services/Dto/Commands/DeleteUserCommand.php',
        'LoginCommand' => 'Application/Services/Dto/Commands/LoginCommand.php',
        'GetUserByIdQuery' => 'Application/Services/Dto/Queries/GetUserByIdQuery.php',
        'GetAllUsersQuery' => 'Application/Services/Dto/Queries/GetAllUsersQuery.php',
        'CreateGastoCommand' => 'Application/Services/Dto/Commands/CreateGastoCommand.php',
        'UpdateGastoCommand' => 'Application/Services/Dto/Commands/UpdateGastoCommand.php',
        'DeleteGastoCommand' => 'Application/Services/Dto/Commands/DeleteGastoCommand.php',
        'GetGastoByIdQuery' => 'Application/Services/Dto/Queries/GetGastoByIdQuery.php',
        'GetAllGastosQuery' => 'Application/Services/Dto/Queries/GetAllGastosQuery.php',
        'GastoApplicationResponse' => 'Application/Services/Dto/Responses/GastoApplicationResponse.php',
        'UserApplicationResponse' => 'Application/Services/Dto/Responses/UserApplicationResponse.php',

        // Servicios y Mappers de Aplicación
        'CreateUserService' => 'Application/Services/CreateUserService.php',
        'UpdateUserService' => 'Application/Services/UpdateUserService.php',
        'GetUserByIdService' => 'Application/Services/GetUserByIdService.php',
        'GetAllUsersService' => 'Application/Services/GetAllUsersService.php',
        'DeleteUserService' => 'Application/Services/DeleteUserService.php',
        'LoginService' => 'Application/Services/LoginService.php',
        'EmailNotificationService' => 'Application/Services/EmailNotificationService.php',
        'UserApplicationMapper' => 'Application/Services/Mappers/UserApplicationMapper.php',
        'CreateGastoService' => 'Application/Services/CreateGastoService.php',
        'UpdateGastoService' => 'Application/Services/UpdateGastoService.php',
        'GetGastoByIdService' => 'Application/Services/GetGastoByIdService.php',
        'GetAllGastosService' => 'Application/Services/GetAllGastosService.php',
        'DeleteGastoService' => 'Application/Services/DeleteGastoService.php',
        'GastoApplicationMapper' => 'Application/Services/Mappers/GastoApplicationMapper.php',

        // Adaptadores de Infraestructura
        'Connection' => 'Infrastructure/Adapters/Persistence/MySQL/Config/Connection.php',
        'UserPersistenceDto' => 'Infrastructure/Adapters/Persistence/MySQL/Dto/UserPersistenceDto.php',
        'UserEntity' => 'Infrastructure/Adapters/Persistence/MySQL/Entity/UserEntity.php',
        'UserPersistenceMapper' => 'Infrastructure/Adapters/Persistence/MySQL/Mapper/UserPersistenceMapper.php',
        'UserRepositoryMySQL' => 'Infrastructure/Adapters/Persistence/MySQL/Repository/UserRepositoryMySQL.php',
        'GastoPersistenceDto' => 'Infrastructure/Adapters/Persistence/MySQL/Dto/GastoPersistenceDto.php',
        'GastoEntity' => 'Infrastructure/Adapters/Persistence/MySQL/Entity/GastoEntity.php',
        'GastoPersistenceMapper' => 'Infrastructure/Adapters/Persistence/MySQL/Mapper/GastoPersistenceMapper.php',
        'GastoRepositoryMySQL' => 'Infrastructure/Adapters/Persistence/MySQL/Repository/GastoRepositoryMySQL.php',
        'InMemoryUserRepository' => 'Infrastructure/Adapters/Persistence/InMemory/InMemoryUserRepository.php',
        'InMemoryGastoRepository' => 'Infrastructure/Adapters/Persistence/InMemory/InMemoryGastoRepository.php',
        'EmailSenderService' => 'Infrastructure/Adapters/Email/EmailSenderService.php',
        'SmtpConfigRepositoryMySQL' => 'Infrastructure/Adapters/Email/SmtpConfigRepositoryMySQL.php',
        'FileCacheAdapter' => 'Infrastructure/Adapters/Cache/FileCacheAdapter.php',
        'FileQueueEventPublisherAdapter' => 'Infrastructure/Adapters/Queue/FileQueueEventPublisherAdapter.php',
        'TaxIndicatorRestAdapter' => 'Infrastructure/Adapters/Rest/TaxIndicatorRestAdapter.php',

        // Entrypoints Web — Usuario + Gasto (#6)
        'CreateUserWebRequest' => 'Infrastructure/Entrypoints/Web/Controllers/Dto/CreateUserRequest.php',
        'UpdateUserWebRequest' => 'Infrastructure/Entrypoints/Web/Controllers/Dto/UpdateUserRequest.php',
        'LoginWebRequest' => 'Infrastructure/Entrypoints/Web/Controllers/Dto/LoginWebRequest.php',
        'UserResponse' => 'Infrastructure/Entrypoints/Web/Controllers/Dto/UserResponse.php',
        'UserWebMapper' => 'Infrastructure/Entrypoints/Web/Controllers/Mapper/UserWebMapper.php',
        'UserController' => 'Infrastructure/Entrypoints/Web/Controllers/UserController.php',
        'CreateGastoWebRequest' => 'Infrastructure/Entrypoints/Web/Controllers/Dto/CreateGastoRequest.php',
        'UpdateGastoWebRequest' => 'Infrastructure/Entrypoints/Web/Controllers/Dto/UpdateGastoRequest.php',
        'GastoResponse' => 'Infrastructure/Entrypoints/Web/Controllers/Dto/GastoResponse.php',
        'GastoWebMapper' => 'Infrastructure/Entrypoints/Web/Controllers/Mapper/GastoWebMapper.php',
        'GastoController' => 'Infrastructure/Entrypoints/Web/Controllers/GastoController.php',
        'WebRoutes' => 'Infrastructure/Entrypoints/Web/Controllers/Config/WebRoutes.php',
        'View' => 'Infrastructure/Entrypoints/Web/Presentation/View.php',
        'Flash' => 'Infrastructure/Entrypoints/Web/Presentation/Flash.php',

        // Entrypoints REST + ErrorHandler
        'ApiRequest' => 'Infrastructure/Entrypoints/Rest/Dto/ApiRequest.php',
        'ApiResponse' => 'Infrastructure/Entrypoints/Rest/Dto/ApiResponse.php',
        'RestApiMapper' => 'Infrastructure/Entrypoints/Rest/Mapper/RestApiMapper.php',
        'ErrorHandler' => 'Infrastructure/Entrypoints/Rest/ErrorHandler.php',
        'UserRestController' => 'Infrastructure/Entrypoints/Rest/UserRestController.php',
        'GastoRestController' => 'Infrastructure/Entrypoints/Rest/GastoRestController.php',

        'DependencyInjection' => 'Common/DependencyInjection.php',
    );

    public static function register(): void
    {
        spl_autoload_register(array(self::class, 'loadClass'));
    }

    public static function loadClass(string $className): void
    {
        if (!isset(self::$classMap[$className])) {
            return;
        }

        $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $filePath = $baseDir . self::$classMap[$className];

        if (!file_exists($filePath)) {
            throw new RuntimeException(
                sprintf('No se encontró el archivo para la clase %s en %s', $className, $filePath)
            );
        }

        require_once $filePath;
    }
}