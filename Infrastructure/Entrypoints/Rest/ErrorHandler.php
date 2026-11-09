<?php

declare(strict_types=1);

require_once __DIR__ . '/Dto/ApiResponse.php';
require_once __DIR__ . '/../../../Domain/Exceptions/UserNotFoundException.php';
require_once __DIR__ . '/../../../Domain/Exceptions/GastoNotFoundException.php';
require_once __DIR__ . '/../../../Domain/Exceptions/UserAlreadyExistsException.php';
require_once __DIR__ . '/../../../Domain/Exceptions/InvalidCredentialsException.php';
require_once __DIR__ . '/../../../Domain/Exceptions/InvalidGastoIvaConsistencyException.php';

final class ErrorHandler
{
    public static function handle(Throwable $exception): ApiResponse
    {
        $class = get_class($exception);

        if ($exception instanceof UserNotFoundException || $exception instanceof GastoNotFoundException) {
            return ApiResponse::error($class, $exception->getMessage(), 404);
        }

        if ($exception instanceof UserAlreadyExistsException) {
            return ApiResponse::error($class, $exception->getMessage(), 409);
        }

        if ($exception instanceof InvalidCredentialsException) {
            return ApiResponse::error($class, $exception->getMessage(), 401);
        }

        if ($exception instanceof InvalidGastoIvaConsistencyException || $exception instanceof InvalidArgumentException || $exception instanceof DomainException) {
            return ApiResponse::error($class, $exception->getMessage(), 422);
        }

        return ApiResponse::error('InternalServerError', $exception->getMessage(), 500);
    }
}