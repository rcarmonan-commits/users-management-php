<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Domain/ValueObjects/GastoId.php';

interface DeleteGastoPort
{
    public function delete(GastoId $gastoId): void;
}