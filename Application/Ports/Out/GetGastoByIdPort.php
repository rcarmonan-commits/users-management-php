<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Domain/Models/GastoModel.php';
require_once __DIR__ . '/../../../Domain/ValueObjects/GastoId.php';

interface GetGastoByIdPort
{
    public function getById(GastoId $gastoId): ?GastoModel;
}