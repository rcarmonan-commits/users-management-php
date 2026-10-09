<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../../Application/Ports/Out/SaveGastoPort.php';
require_once __DIR__ . '/../../../../../Application/Ports/Out/UpdateGastoPort.php';
require_once __DIR__ . '/../../../../../Application/Ports/Out/GetGastoByIdPort.php';
require_once __DIR__ . '/../../../../../Application/Ports/Out/GetAllGastosPort.php';
require_once __DIR__ . '/../../../../../Application/Ports/Out/DeleteGastoPort.php';

require_once __DIR__ . '/../Mapper/GastoPersistenceMapper.php';
require_once __DIR__ . '/../../../../../Domain/Models/GastoModel.php';
require_once __DIR__ . '/../../../../../Domain/ValueObjects/GastoId.php';

final class GastoRepositoryMySQL implements
    SaveGastoPort,
    UpdateGastoPort,
    GetGastoByIdPort,
    GetAllGastosPort,
    DeleteGastoPort
{
    private PDO $pdo;
    private GastoPersistenceMapper $mapper;
    /** @var array{pk: string, auto_increment: bool, has_timestamps: bool, has_legacy_usuarios: bool}|null */
    private ?array $schemaMeta = null;

    public function __construct(PDO $pdo, GastoPersistenceMapper $mapper)
    {
        $this->pdo = $pdo;
        $this->mapper = $mapper;
    }

    /**
     * Detecta de forma no intrusiva la estructura real de la tabla gastos para coexistir
     * tanto con bases compartidas preexistentes (id_gasto AUTO_INCREMENT + FK usuarios)
     * como con instalaciones limpias basadas en UUID (id VARCHAR + timestamps).
     *
     * @return array{pk: string, auto_increment: bool, has_timestamps: bool, has_legacy_usuarios: bool}
     */
    private function getSchemaMeta(): array
    {
        if ($this->schemaMeta !== null) {
            return $this->schemaMeta;
        }

        $columns = $this->pdo->query('SHOW COLUMNS FROM gastos')->fetchAll(PDO::FETCH_ASSOC);
        $pk = 'id';
        $autoIncrement = false;
        $colNames = array();

        foreach ($columns as $col) {
            $field = (string) ($col['Field'] ?? '');
            $extra = strtolower((string) ($col['Extra'] ?? ''));
            $colNames[$field] = true;
            if ($field === 'id_gasto') {
                $pk = 'id_gasto';
                $autoIncrement = str_contains($extra, 'auto_increment');
            } elseif ($field === 'id' && $pk !== 'id_gasto') {
                $autoIncrement = str_contains($extra, 'auto_increment');
            }
        }

        $hasTimestamps = isset($colNames['created_at'], $colNames['updated_at']);
        $legacyCheck = $this->pdo->query("SHOW TABLES LIKE 'usuarios'")->fetchColumn();
        $hasLegacyUsuarios = ($legacyCheck !== false);

        $this->schemaMeta = array(
            'pk' => $pk,
            'auto_increment' => $autoIncrement,
            'has_timestamps' => $hasTimestamps,
            'has_legacy_usuarios' => $hasLegacyUsuarios,
        );

        return $this->schemaMeta;
    }

    /**
     * Si la BD tiene la tabla heredada `usuarios` referenciada por `fk_usuario`,
     * resuelve el identificador existente sin alterar ni eliminar registros previos.
     */
    private function resolveNombreUsuarioForWrite(string $nombreUsuario): string
    {
        $meta = $this->getSchemaMeta();
        if (!$meta['has_legacy_usuarios']) {
            return $nombreUsuario;
        }

        $candidate = trim($nombreUsuario);

        $byId = $this->pdo->prepare('SELECT id FROM usuarios WHERE id = :id LIMIT 1');
        $byId->execute(array(':id' => $candidate));
        $foundId = $byId->fetchColumn();
        if ($foundId !== false) {
            return (string) $foundId;
        }

        $byName = $this->pdo->prepare('SELECT id FROM usuarios WHERE LOWER(nombre) = LOWER(:nombre) LIMIT 1');
        $byName->execute(array(':nombre' => $candidate));
        $foundByName = $byName->fetchColumn();
        if ($foundByName !== false) {
            return (string) $foundByName;
        }

        $safeId = mb_substr($candidate, 0, 50);
        $insertUser = $this->pdo->prepare(
            'INSERT INTO usuarios (id, clave, nombre, rol, email)
             VALUES (:id, :clave, :nombre, :rol, NULL)
             ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)'
        );
        $insertUser->execute(array(
            ':id' => $safeId,
            ':clave' => password_hash('Temporal2026*', PASSWORD_BCRYPT),
            ':nombre' => mb_substr($candidate, 0, 100),
            ':rol' => 'Operador',
        ));

        return $safeId;
    }

    private function recordExists(string $idValue): bool
    {
        $pk = $this->getSchemaMeta()['pk'];
        $stmt = $this->pdo->prepare("SELECT 1 FROM gastos WHERE {$pk} = :id LIMIT 1");
        $stmt->execute(array(':id' => $idValue));

        return $stmt->fetchColumn() !== false;
    }

    public function save(GastoModel $gasto): GastoModel
    {
        $meta = $this->getSchemaMeta();
        $pk = $meta['pk'];
        $dto = $this->mapper->fromModelToDto($gasto);
        $resolvedUser = $this->resolveNombreUsuarioForWrite($dto->nombreUsuario());

        // Si el registro ya existe en BD, actualizar sin borrar lo existente
        if ((!$meta['auto_increment'] || ctype_digit($dto->id())) && $this->recordExists($dto->id())) {
            return $this->update($gasto);
        }

        if ($meta['auto_increment'] && !ctype_digit($dto->id())) {
            $cols = 'fecha, valor_total_sin_iva, iva_total, valor_total_con_iva, nombre_usuario, lugar, descripcion';
            $vals = ':fecha, :valor_total_sin_iva, :iva_total, :valor_total_con_iva, :nombre_usuario, :lugar, :descripcion';
            if ($meta['has_timestamps']) {
                $cols .= ', created_at, updated_at';
                $vals .= ', NOW(), NOW()';
            }

            $stmt = $this->pdo->prepare("INSERT INTO gastos ({$cols}) VALUES ({$vals})");
            $stmt->execute(array(
                ':fecha' => $dto->fecha(),
                ':valor_total_sin_iva' => $dto->valorTotalSinIVA(),
                ':iva_total' => $dto->ivaTotal(),
                ':valor_total_con_iva' => $dto->valorTotalConIVA(),
                ':nombre_usuario' => $resolvedUser,
                ':lugar' => $dto->lugar(),
                ':descripcion' => $dto->descripcion(),
            ));

            $targetId = (string) $this->pdo->lastInsertId();
        } else {
            $cols = "{$pk}, fecha, valor_total_sin_iva, iva_total, valor_total_con_iva, nombre_usuario, lugar, descripcion";
            $vals = ':id, :fecha, :valor_total_sin_iva, :iva_total, :valor_total_con_iva, :nombre_usuario, :lugar, :descripcion';
            if ($meta['has_timestamps']) {
                $cols .= ', created_at, updated_at';
                $vals .= ', NOW(), NOW()';
            }

            $stmt = $this->pdo->prepare("INSERT INTO gastos ({$cols}) VALUES ({$vals})");
            $stmt->execute(array(
                ':id' => $dto->id(),
                ':fecha' => $dto->fecha(),
                ':valor_total_sin_iva' => $dto->valorTotalSinIVA(),
                ':iva_total' => $dto->ivaTotal(),
                ':valor_total_con_iva' => $dto->valorTotalConIVA(),
                ':nombre_usuario' => $resolvedUser,
                ':lugar' => $dto->lugar(),
                ':descripcion' => $dto->descripcion(),
            ));

            $targetId = $dto->id();
        }

        $saved = $this->getById(new GastoId($targetId));

        if ($saved === null) {
            throw new RuntimeException('El gasto no pudo ser recuperado tras guardar en MySQL.');
        }

        return $saved;
    }

    public function update(GastoModel $gasto): GastoModel
    {
        $meta = $this->getSchemaMeta();
        $pk = $meta['pk'];
        $dto = $this->mapper->fromModelToDto($gasto);
        $resolvedUser = $this->resolveNombreUsuarioForWrite($dto->nombreUsuario());

        $timestampSet = $meta['has_timestamps'] ? ', updated_at = NOW()' : '';
        $sql = "
            UPDATE gastos
               SET fecha = :fecha,
                   valor_total_sin_iva = :valor_total_sin_iva,
                   iva_total = :iva_total,
                   valor_total_con_iva = :valor_total_con_iva,
                   nombre_usuario = :nombre_usuario,
                   lugar = :lugar,
                   descripcion = :descripcion{$timestampSet}
             WHERE {$pk} = :id
        ";

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(
            ':id' => $dto->id(),
            ':fecha' => $dto->fecha(),
            ':valor_total_sin_iva' => $dto->valorTotalSinIVA(),
            ':iva_total' => $dto->ivaTotal(),
            ':valor_total_con_iva' => $dto->valorTotalConIVA(),
            ':nombre_usuario' => $resolvedUser,
            ':lugar' => $dto->lugar(),
            ':descripcion' => $dto->descripcion(),
        ));

        $updated = $this->getById(new GastoId($dto->id()));

        if ($updated === null) {
            throw new RuntimeException('El gasto no pudo ser recuperado tras actualizar en MySQL.');
        }

        return $updated;
    }

    public function getById(GastoId $gastoId): ?GastoModel
    {
        $meta = $this->getSchemaMeta();
        $pk = $meta['pk'];
        $tsCols = $meta['has_timestamps'] ? ', created_at, updated_at' : '';

        $sql = "
            SELECT
                {$pk} AS id,
                fecha,
                valor_total_sin_iva,
                iva_total,
                valor_total_con_iva,
                nombre_usuario,
                lugar,
                descripcion{$tsCols}
            FROM gastos
            WHERE {$pk} = :id
            LIMIT 1
        ";

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(
            ':id' => $gastoId->value(),
        ));

        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->mapper->fromRowToModel($row);
    }

    /**
     * @return GastoModel[]
     */
    public function getAll(
        ?string $nombreUsuario = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null
    ): array {
        $meta = $this->getSchemaMeta();
        $pk = $meta['pk'];
        $tsCols = $meta['has_timestamps'] ? ', created_at, updated_at' : '';
        $secondaryOrder = $meta['has_timestamps'] ? 'created_at DESC' : "{$pk} DESC";

        $conditions = array();
        $params = array();

        if ($nombreUsuario !== null && trim($nombreUsuario) !== '') {
            $conditions[] = 'LOWER(nombre_usuario) LIKE :nombre_usuario';
            $params[':nombre_usuario'] = '%' . strtolower(trim($nombreUsuario)) . '%';
        }

        if ($fechaDesde !== null && trim($fechaDesde) !== '') {
            $conditions[] = 'fecha >= :fecha_desde';
            $params[':fecha_desde'] = trim($fechaDesde);
        }

        if ($fechaHasta !== null && trim($fechaHasta) !== '') {
            $conditions[] = 'fecha <= :fecha_hasta';
            $params[':fecha_hasta'] = trim($fechaHasta);
        }

        $where = empty($conditions) ? '' : ('WHERE ' . implode(' AND ', $conditions));

        $sql = "
            SELECT
                {$pk} AS id,
                fecha,
                valor_total_sin_iva,
                iva_total,
                valor_total_con_iva,
                nombre_usuario,
                lugar,
                descripcion{$tsCols}
            FROM gastos
            {$where}
            ORDER BY fecha DESC, {$secondaryOrder}
        ";

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll();

        return $this->mapper->fromRowsToModels($rows);
    }

    public function delete(GastoId $gastoId): void
    {
        $pk = $this->getSchemaMeta()['pk'];
        $sql = "DELETE FROM gastos WHERE {$pk} = :id";

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(
            ':id' => $gastoId->value(),
        ));
    }
}