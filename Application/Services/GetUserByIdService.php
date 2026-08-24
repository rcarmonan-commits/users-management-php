<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — SERVICIO DE APLICACIÓN QUERY: GetUserByIdService
 * ============================================================================
 * CAPA: Aplicación (`Application/Services`)
 * PATRÓN: Query Handler (CQRS)
 * PRINCIPIO SOLID: SRP + DIP
 *
 * ¿QUÉ HACE?
 *   Implementa GetUserByIdUseCase, buscando un usuario por su identificador único.
 *
 * ¿CÓMO LO HACE?
 *   Ejecuta GetUserByIdPort y, si el usuario no existe, lanza UserNotFoundException.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Garantiza que las consultas manejen fallos mediante excepciones de dominio semánticas.
 * ============================================================================
 */

require_once __DIR__ . '/../Ports/In/GetUserByIdUseCase.php';
require_once __DIR__ . '/../Ports/Out/GetUserByIdPort.php';
require_once __DIR__ . '/Mappers/UserApplicationMapper.php';
require_once __DIR__ . '/../../Domain/Exceptions/UserNotFoundException.php';

final class GetUserByIdService implements GetUserByIdUseCase
{
    private GetUserByIdPort $getUserByIdPort;

    public function __construct(GetUserByIdPort $getUserByIdPort)
    {
        $this->getUserByIdPort = $getUserByIdPort;
    }

    public function execute(GetUserByIdQuery $query): UserModel
    {
        $userId = UserApplicationMapper::fromGetUserByIdQueryToUserId($query);
        $user = $this->getUserByIdPort->getById($userId);

        if ($user === null) {
            throw UserNotFoundException::becauseIdWasNotFound($userId->value());
        }

        return $user;
    }
}