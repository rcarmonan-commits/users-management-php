<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Domain/Models/GastoModel.php';

interface SaveGastoPort
{
    public function save(GastoModel $gasto): GastoModel;
}