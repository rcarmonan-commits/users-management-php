<?php

declare(strict_types=1);

require_once __DIR__ . '/../../Services/Dto/Queries/GetGastoByIdQuery.php';
require_once __DIR__ . '/../../../Domain/Models/GastoModel.php';

interface GetGastoByIdUseCase
{
    public function execute(GetGastoByIdQuery $query): GastoModel;
}