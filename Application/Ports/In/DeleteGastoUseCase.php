<?php

declare(strict_types=1);

require_once __DIR__ . '/../../Services/Dto/Commands/DeleteGastoCommand.php';

interface DeleteGastoUseCase
{
    public function execute(DeleteGastoCommand $command): void;
}