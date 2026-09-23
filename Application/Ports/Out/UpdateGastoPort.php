<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Domain/Models/GastoModel.php';

interface UpdateGastoPort
{
    public function update(GastoModel $gasto): GastoModel;
}