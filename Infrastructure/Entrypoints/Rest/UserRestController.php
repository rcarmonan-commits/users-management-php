<?php

declare(strict_types=1);

require_once __DIR__ . '/Dto/ApiRequest.php';
require_once __DIR__ . '/Dto/ApiResponse.php';
require_once __DIR__ . '/Mapper/RestApiMapper.php';
require_once __DIR__ . '/../../../Common/SecurityGuard.php';

require_once __DIR__ . '/../../../Application/Ports/In/CreateUserUseCase.php';
require_once __DIR__ . '/../../../Application/Ports/In/UpdateUserUseCase.php';
require_once __DIR__ . '/../../../Application/Ports/In/GetUserByIdUseCase.php';
require_once __DIR__ . '/../../../Application/Ports/In/GetAllUsersUseCase.php';
require_once __DIR__ . '/../../../Application/Ports/In/DeleteUserUseCase.php';
require_once __DIR__ . '/../../../Application/Ports/In/LoginUseCase.php';
require_once __DIR__ . '/../../../Application/Services/Dto/Commands/DeleteUserCommand.php';
require_once __DIR__ . '/../../../Application/Services/Dto/Queries/GetAllUsersQuery.php';
require_once __DIR__ . '/../../../Application/Services/Dto/Queries/GetUserByIdQuery.php';
require_once __DIR__ . '/../../../Domain/Exceptions/InvalidCredentialsException.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — CONTROLADOR REST ENTIDAD COMÚN: UserRestController
 * ============================================================================
 * CAPA: Infraestructura — Entrypoint REST (`Infrastructure/Entrypoints/Rest`)
 * SEGURIDAD: Incluye protección anti-fuerza bruta (*Rate Limiting*) y ecualización
 * de tiempos BCrypt en `POST /api/auth/login`.
 * ============================================================================
 */
final class UserRestController
{
    private CreateUserUseCase $createUserUseCase;
    private UpdateUserUseCase $updateUserUseCase;
    private GetUserByIdUseCase $getUserByIdUseCase;
    private GetAllUsersUseCase $getAllUsersUseCase;
    private DeleteUserUseCase $deleteUserUseCase;
    private LoginUseCase $loginUseCase;

    public function __construct(
        CreateUserUseCase $createUserUseCase,
        UpdateUserUseCase $updateUserUseCase,
        GetUserByIdUseCase $getUserByIdUseCase,
        GetAllUsersUseCase $getAllUsersUseCase,
        DeleteUserUseCase $deleteUserUseCase,
        LoginUseCase $loginUseCase
    ) {
        $this->createUserUseCase = $createUserUseCase;
        $this->updateUserUseCase = $updateUserUseCase;
        $this->getUserByIdUseCase = $getUserByIdUseCase;
        $this->getAllUsersUseCase = $getAllUsersUseCase;
        $this->deleteUserUseCase = $deleteUserUseCase;
        $this->loginUseCase = $loginUseCase;
    }

    public function login(ApiRequest $request): ApiResponse
    {
        $rateKey = SecurityGuard::clientIp() . '|' . strtolower($request->stringField('email'));
        if (!SecurityGuard::checkRateLimit('rest_login', $rateKey, 5, 300)) {
            throw new RuntimeException('Demasiados intentos de inicio de sesión. Intenta de nuevo en 5 minutos.', 429);
        }

        try {
            $user = $this->loginUseCase->execute(RestApiMapper::toLoginCommand($request));
            SecurityGuard::clearAttempts('rest_login', $rateKey);
        } catch (InvalidCredentialsException $exception) {
            SecurityGuard::recordFailedAttempt('rest_login', $rateKey, 300);
            SecurityGuard::mitigateTimingEnumeration($request->stringField('password'));
            throw $exception;
        }

        $jwtBundle = SecurityGuard::issueApiJwt(
            $user->id()->value(),
            $user->email()->value(),
            $user->name()->value(),
            $user->role()
        );

        return ApiResponse::success(
            array(
                'token_type' => 'Bearer',
                'access_token' => $jwtBundle['token'],
                'token' => $jwtBundle['token'],
                'expires_in' => $jwtBundle['expires_in'],
                'expires_at' => $jwtBundle['expires_at'],
                'user' => RestApiMapper::userModelToArray($user),
            ),
            200,
            'Autenticación exitosa. Envía access_token en el encabezado Authorization: Bearer <token>.'
        );
    }

    public function list(): ApiResponse
    {
        $users = $this->getAllUsersUseCase->execute(new GetAllUsersQuery());
        $items = array();

        foreach ($users as $user) {
            $items[] = RestApiMapper::userModelToArray($user);
        }

        return ApiResponse::success(array(
            'count' => count($items),
            'items' => $items,
        ));
    }

    public function getById(string $id): ApiResponse
    {
        $user = $this->getUserByIdUseCase->execute(new GetUserByIdQuery($id));

        return ApiResponse::success(RestApiMapper::userModelToArray($user));
    }

    public function create(ApiRequest $request, string $generatedId): ApiResponse
    {
        $command = RestApiMapper::toCreateUserCommand($request, $generatedId);
        $created = $this->createUserUseCase->execute($command);

        return ApiResponse::success(
            RestApiMapper::userModelToArray($created),
            201,
            'Usuario registrado exitosamente.'
        );
    }

    public function update(ApiRequest $request, string $id): ApiResponse
    {
        $command = RestApiMapper::toUpdateUserCommand($request, $id);
        $updated = $this->updateUserUseCase->execute($command);

        return ApiResponse::success(
            RestApiMapper::userModelToArray($updated),
            200,
            'Usuario actualizado exitosamente.'
        );
    }

    public function delete(string $id): ApiResponse
    {
        $this->deleteUserUseCase->execute(new DeleteUserCommand($id));

        return ApiResponse::success(
            array('id' => $id),
            200,
            'Usuario eliminado exitosamente.'
        );
    }
}