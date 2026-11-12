<?php

declare(strict_types=1);

require_once __DIR__ . '/../Dto/ApiRequest.php';
require_once __DIR__ . '/../../../../Application/Services/Dto/Commands/CreateUserCommand.php';
require_once __DIR__ . '/../../../../Application/Services/Dto/Commands/UpdateUserCommand.php';
require_once __DIR__ . '/../../../../Application/Services/Dto/Commands/LoginCommand.php';
require_once __DIR__ . '/../../../../Application/Services/Dto/Commands/CreateGastoCommand.php';
require_once __DIR__ . '/../../../../Application/Services/Dto/Commands/UpdateGastoCommand.php';
require_once __DIR__ . '/../../../../Application/Services/Dto/Responses/UserApplicationResponse.php';
require_once __DIR__ . '/../../../../Application/Services/Dto/Responses/GastoApplicationResponse.php';
require_once __DIR__ . '/../../../../Domain/Models/UserModel.php';
require_once __DIR__ . '/../../../../Domain/Models/GastoModel.php';
require_once __DIR__ . '/../../../../Domain/Enums/UserRoleEnum.php';
require_once __DIR__ . '/../../../../Domain/Enums/UserStatusEnum.php';

final class RestApiMapper
{
    public static function toCreateUserCommand(ApiRequest $request, string $generatedId): CreateUserCommand
    {
        $id = $request->stringField('id') !== '' ? $request->stringField('id') : $generatedId;

        return new CreateUserCommand(
            $id,
            $request->stringField('name'),
            $request->stringField('email'),
            $request->stringField('password'),
            $request->stringField('role', UserRoleEnum::MEMBER)
        );
    }

    public static function toUpdateUserCommand(ApiRequest $request, string $id): UpdateUserCommand
    {
        return new UpdateUserCommand(
            $id,
            $request->stringField('name'),
            $request->stringField('email'),
            $request->stringField('password', ''),
            $request->stringField('role', UserRoleEnum::MEMBER),
            $request->stringField('status', UserStatusEnum::ACTIVE)
        );
    }

    public static function toLoginCommand(ApiRequest $request): LoginCommand
    {
        return new LoginCommand(
            $request->stringField('email'),
            $request->stringField('password')
        );
    }

    public static function toCreateGastoCommand(ApiRequest $request, string $generatedId): CreateGastoCommand
    {
        $id = $request->stringField('id') !== '' ? $request->stringField('id') : $generatedId;

        return new CreateGastoCommand(
            $id,
            $request->stringField('fecha'),
            $request->stringField('valorTotalSinIVA'),
            $request->stringField('ivaTotal'),
            $request->stringField('valorTotalConIVA'),
            $request->stringField('nombreUsuario'),
            $request->stringField('lugar'),
            $request->stringField('descripcion')
        );
    }

    public static function toUpdateGastoCommand(ApiRequest $request, string $id): UpdateGastoCommand
    {
        return new UpdateGastoCommand(
            $id,
            $request->stringField('fecha'),
            $request->stringField('valorTotalSinIVA'),
            $request->stringField('ivaTotal'),
            $request->stringField('valorTotalConIVA'),
            $request->stringField('nombreUsuario'),
            $request->stringField('lugar'),
            $request->stringField('descripcion')
        );
    }

    /**
     * @return array<string, string>
     */
    public static function userModelToArray(UserModel $user): array
    {
        return (new UserApplicationResponse(
            $user->id()->value(),
            $user->name()->value(),
            $user->email()->value(),
            $user->role(),
            $user->status()
        ))->toArray();
    }

    /**
     * @return array<string, float|string>
     */
    public static function gastoModelToArray(GastoModel $gasto): array
    {
        return (new GastoApplicationResponse(
            $gasto->id()->value(),
            $gasto->fecha()->value(),
            $gasto->valorTotalSinIVA()->toFloat(),
            $gasto->ivaTotal()->toFloat(),
            $gasto->valorTotalConIVA()->toFloat(),
            $gasto->nombreUsuario()->value(),
            $gasto->lugar()->value(),
            $gasto->descripcion()->value()
        ))->toArray();
    }
}