<?php

declare(strict_types=1);

require_once __DIR__ . '/../Dto/GastoPersistenceDto.php';
require_once __DIR__ . '/../Entity/GastoEntity.php';

require_once __DIR__ . '/../../../../../Domain/Models/GastoModel.php';
require_once __DIR__ . '/../../../../../Domain/ValueObjects/GastoId.php';
require_once __DIR__ . '/../../../../../Domain/ValueObjects/GastoFecha.php';
require_once __DIR__ . '/../../../../../Domain/ValueObjects/GastoMonto.php';
require_once __DIR__ . '/../../../../../Domain/ValueObjects/GastoLugar.php';
require_once __DIR__ . '/../../../../../Domain/ValueObjects/GastoDescripcion.php';
require_once __DIR__ . '/../../../../../Domain/ValueObjects/UserName.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — DATA MAPPER RELACIONAL: GastoPersistenceMapper
 * ============================================================================
 * CAPA: Infraestructura — Adaptadores (`Infrastructure/Adapters/Persistence/MySQL/Mapper`)
 * PATRÓN: Data Mapper Bidireccional + Capa Anticorrupción (ACL) para esquemas heredados
 *
 * ¿QUÉ HACE?
 *   Convierte sin pérdida entre `GastoModel` <-> `GastoPersistenceDto` <-> `GastoEntity` <-> fila SQL.
 *
 * ¿CÓMO PROTEGE DATOS PREEXISTENTES EN LA NUBE (ACL)?
 *   En `fromRowToEntity()` normaliza en memoria filas heredadas de bases compartidas
 *   (por ejemplo, `id_gasto` numérico o descripciones históricas vacías en Alwaysdata)
 *   para hidratar `GastoModel` limpiamente sin modificar ni borrar los datos previos.
 * ============================================================================
 */
final class GastoPersistenceMapper
{
    public function fromModelToDto(GastoModel $gasto): GastoPersistenceDto
    {
        return new GastoPersistenceDto(
            $gasto->id()->value(),
            $gasto->fecha()->value(),
            $gasto->valorTotalSinIVA()->formatted(),
            $gasto->ivaTotal()->formatted(),
            $gasto->valorTotalConIVA()->formatted(),
            $gasto->nombreUsuario()->value(),
            $gasto->lugar()->value(),
            $gasto->descripcion()->value()
        );
    }

    public function fromDtoToEntity(GastoPersistenceDto $dto): GastoEntity
    {
        return new GastoEntity(
            $dto->id(),
            $dto->fecha(),
            $dto->valorTotalSinIVA(),
            $dto->ivaTotal(),
            $dto->valorTotalConIVA(),
            $dto->nombreUsuario(),
            $dto->lugar(),
            $dto->descripcion()
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    public function fromRowToEntity(array $row): GastoEntity
    {
        $resolvedId = isset($row['id']) && (string) $row['id'] !== ''
            ? (string) $row['id']
            : (string) ($row['id_gasto'] ?? '');

        $rawUser = isset($row['nombre_usuario']) ? trim((string) $row['nombre_usuario']) : '';
        if ($rawUser === '') {
            $rawUser = 'usuario';
        } elseif (mb_strlen($rawUser) < 3) {
            $rawUser = 'usr_' . $rawUser;
        }

        $rawLugar = isset($row['lugar']) ? trim((string) $row['lugar']) : '';
        if ($rawLugar === '') {
            $rawLugar = 'Sin lugar';
        } elseif (mb_strlen($rawLugar) < 3) {
            $rawLugar = $rawLugar . ' - sede';
        }

        $rawDesc = isset($row['descripcion']) ? trim((string) $row['descripcion']) : '';
        if ($rawDesc === '') {
            $rawDesc = 'Sin descripción registrada';
        } elseif (mb_strlen($rawDesc) < 5) {
            $rawDesc = $rawDesc . ' (registro)';
        }

        return new GastoEntity(
            $resolvedId,
            (string) $row['fecha'],
            (string) $row['valor_total_sin_iva'],
            (string) $row['iva_total'],
            (string) $row['valor_total_con_iva'],
            $rawUser,
            $rawLugar,
            $rawDesc,
            isset($row['created_at']) ? (string) $row['created_at'] : null,
            isset($row['updated_at']) ? (string) $row['updated_at'] : null
        );
    }

    public function fromEntityToModel(GastoEntity $entity): GastoModel
    {
        return new GastoModel(
            new GastoId($entity->id()),
            new GastoFecha($entity->fecha()),
            GastoMonto::fromInput($entity->valorTotalSinIva(), 'valorTotalSinIVA', false),
            GastoMonto::fromInput($entity->ivaTotal(), 'ivaTotal', true),
            GastoMonto::fromInput($entity->valorTotalConIva(), 'valorTotalConIVA', false),
            new UserName($entity->nombreUsuario()),
            new GastoLugar($entity->lugar()),
            new GastoDescripcion($entity->descripcion())
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    public function fromRowToModel(array $row): GastoModel
    {
        return $this->fromEntityToModel(
            $this->fromRowToEntity($row)
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return GastoModel[]
     */
    public function fromRowsToModels(array $rows): array
    {
        $models = array();

        foreach ($rows as $row) {
            $models[] = $this->fromRowToModel($row);
        }

        return $models;
    }
}