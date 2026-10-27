<?php

declare(strict_types=1);

require_once __DIR__ . '/../Dto/Commands/CreateGastoCommand.php';
require_once __DIR__ . '/../Dto/Commands/UpdateGastoCommand.php';
require_once __DIR__ . '/../Dto/Commands/DeleteGastoCommand.php';
require_once __DIR__ . '/../Dto/Queries/GetGastoByIdQuery.php';
require_once __DIR__ . '/../Dto/Responses/GastoApplicationResponse.php';

require_once __DIR__ . '/../../../Domain/Models/GastoModel.php';
require_once __DIR__ . '/../../../Domain/ValueObjects/GastoId.php';
require_once __DIR__ . '/../../../Domain/ValueObjects/GastoFecha.php';
require_once __DIR__ . '/../../../Domain/ValueObjects/GastoMonto.php';
require_once __DIR__ . '/../../../Domain/ValueObjects/GastoLugar.php';
require_once __DIR__ . '/../../../Domain/ValueObjects/GastoDescripcion.php';
require_once __DIR__ . '/../../../Domain/ValueObjects/UserName.php';

final class GastoApplicationMapper
{
    public static function fromCreateCommandToModel(CreateGastoCommand $command): GastoModel
    {
        return GastoModel::create(
            new GastoId($command->getId()),
            new GastoFecha($command->getFecha()),
            GastoMonto::fromInput($command->getValorTotalSinIVA(), 'valorTotalSinIVA', false),
            GastoMonto::fromInput($command->getIvaTotal(), 'ivaTotal', true),
            GastoMonto::fromInput($command->getValorTotalConIVA(), 'valorTotalConIVA', false),
            new UserName($command->getNombreUsuario()),
            new GastoLugar($command->getLugar()),
            new GastoDescripcion($command->getDescripcion())
        );
    }

    public static function fromUpdateCommandToModel(UpdateGastoCommand $command): GastoModel
    {
        return new GastoModel(
            new GastoId($command->getId()),
            new GastoFecha($command->getFecha()),
            GastoMonto::fromInput($command->getValorTotalSinIVA(), 'valorTotalSinIVA', false),
            GastoMonto::fromInput($command->getIvaTotal(), 'ivaTotal', true),
            GastoMonto::fromInput($command->getValorTotalConIVA(), 'valorTotalConIVA', false),
            new UserName($command->getNombreUsuario()),
            new GastoLugar($command->getLugar()),
            new GastoDescripcion($command->getDescripcion())
        );
    }

    public static function fromGetByIdQueryToGastoId(GetGastoByIdQuery $query): GastoId
    {
        return new GastoId($query->getId());
    }

    public static function fromDeleteCommandToGastoId(DeleteGastoCommand $command): GastoId
    {
        return new GastoId($command->getId());
    }

    public static function fromModelToResponse(GastoModel $gasto): GastoApplicationResponse
    {
        return new GastoApplicationResponse(
            $gasto->id()->value(),
            $gasto->fecha()->value(),
            $gasto->valorTotalSinIVA()->toFloat(),
            $gasto->ivaTotal()->toFloat(),
            $gasto->valorTotalConIVA()->toFloat(),
            $gasto->nombreUsuario()->value(),
            $gasto->lugar()->value(),
            $gasto->descripcion()->value()
        );
    }

    /**
     * @return array<string, float|string>
     */
    public static function fromModelToArray(GastoModel $gasto): array
    {
        return $gasto->toArray();
    }

    /**
     * @param GastoModel[] $gastos
     * @return array<int, array<string, float|string>>
     */
    public static function fromModelsToArray(array $gastos): array
    {
        $items = array();
        foreach ($gastos as $gasto) {
            $items[] = self::fromModelToArray($gasto);
        }

        return $items;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return GastoModel[]
     */
    public static function fromArraysToModels(array $rows): array
    {
        $models = array();
        foreach ($rows as $row) {
            if (!isset($row['id'], $row['fecha'], $row['valorTotalSinIVA'], $row['ivaTotal'], $row['valorTotalConIVA'], $row['nombreUsuario'], $row['lugar'], $row['descripcion'])) {
                continue;
            }

            $models[] = new GastoModel(
                new GastoId((string) $row['id']),
                new GastoFecha((string) $row['fecha']),
                GastoMonto::fromInput((string) $row['valorTotalSinIVA'], 'valorTotalSinIVA', false),
                GastoMonto::fromInput((string) $row['ivaTotal'], 'ivaTotal', true),
                GastoMonto::fromInput((string) $row['valorTotalConIVA'], 'valorTotalConIVA', false),
                new UserName((string) $row['nombreUsuario']),
                new GastoLugar((string) $row['lugar']),
                new GastoDescripcion((string) $row['descripcion'])
            );
        }

        return $models;
    }
}