<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Domain/Models/GastoModel.php';

interface GetAllGastosPort
{
    /**
     * @return GastoModel[]
     */
    public function getAll(
        ?string $nombreUsuario = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null
    ): array;
}