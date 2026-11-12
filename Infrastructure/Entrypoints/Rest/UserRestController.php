<?php

declare(strict_types=1);

require_once __DIR__ . '/Dto/ApiRequest.php';
require_once __DIR__ . '/Dto/ApiResponse.php';
require_once __DIR__ . '/Mapper/RestApiMapper.php';

require_once __DIR__ . '/../../../Application/Ports/In/CreateUserUseCase.php';
require_once __DIR__ . '/../../../Application/Ports/In/UpdateUserUseCase.php';
require_once __DIR__ . '/../../../Application/Ports/In/GetUserByIdUseCase.php';
require_once __DIR__ . '/../../../Application/Ports/In/GetAllUsersUseCase.php';
require_once __DIR__ . '/../../../Application/Ports/In/DeleteUserUseCase.php';
require_once __DIR__ . '/../../../Application/Ports/In/LoginUseCase.php';
require_once __DIR__ . '/../../../Application/Services/Dto/Commands/DeleteUserCommand.php';
require_once __DIR__ . '/../../../Application/Services/Dto/Queries/GetAllUsersQuery.php';
require_once __DIR__ . '/../../../Application/Services/Dto/Queries/GetUserByIdQuery.php';

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
        $user = $this->loginUseCase->execute(RestApiMapper::toLoginCommand($request));

        return ApiResponse::success(
            array('user' => RestApiMapper::userModelToArray($user)),
            200,
            'Autenticación exitosa.'
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