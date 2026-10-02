<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../Application/Ports/Out/SaveGastoPort.php';
require_once __DIR__ . '/../../../../Application/Ports/Out/UpdateGastoPort.php';
require_once __DIR__ . '/../../../../Application/Ports/Out/GetGastoByIdPort.php';
require_once __DIR__ . '/../../../../Application/Ports/Out/GetAllGastosPort.php';
require_once __DIR__ . '/../../../../Application/Ports/Out/DeleteGastoPort.php';

require_once __DIR__ . '/../../../../Domain/Models/GastoModel.php';
require_once __DIR__ . '/../../../../Domain/ValueObjects/GastoId.php';
require_once __DIR__ . '/../../../../Domain/ValueObjects/GastoFecha.php';
require_once __DIR__ . '/../../../../Domain/ValueObjects/GastoMonto.php';
require_once __DIR__ . '/../../../../Domain/ValueObjects/GastoLugar.php';
require_once __DIR__ . '/../../../../Domain/ValueObjects/GastoDescripcion.php';
require_once __DIR__ . '/../../../../Domain/ValueObjects/UserName.php';

final class InMemoryGastoRepository implements
    SaveGastoPort,
    UpdateGastoPort,
    GetGastoByIdPort,
    GetAllGastosPort,
    DeleteGastoPort
{
    /** @var array<string, GastoModel> */
    private array $items = array();

    private ?string $persistenceFile;

    public function __construct(bool $seedExamples = false, ?string $persistenceFile = null)
    {
        $this->persistenceFile = $persistenceFile;

        if ($this->persistenceFile !== null && is_file($this->persistenceFile)) {
            $this->loadFromDisk();
        }

        if ($seedExamples && empty($this->items)) {
            $sample1 = GastoModel::create(
                new GastoId('11111111-1111-4111-8111-111111111101'),
                new GastoFecha('2026-08-20'),
                GastoMonto::fromInput('100000.00', 'valorTotalSinIVA', false),
                GastoMonto::fromInput('19000.00', 'ivaTotal', true),
                GastoMonto::fromInput('119000.00', 'valorTotalConIVA', false),
                new UserName('Administrador'),
                new GastoLugar('Papelería Panamericana Cartagena'),
                new GastoDescripcion('Compra de resmas de papel e insumos de oficina para el departamento de sistemas')
            );
            $sample2 = GastoModel::create(
                new GastoId('11111111-1111-4111-8111-111111111102'),
                new GastoFecha('2026-09-05'),
                GastoMonto::fromInput('250000.00', 'valorTotalSinIVA', false),
                GastoMonto::fromInput('47500.00', 'ivaTotal', true),
                GastoMonto::fromInput('297500.00', 'valorTotalConIVA', false),
                new UserName('Administrador'),
                new GastoLugar('Centro de Convenciones Cartagena'),
                new GastoDescripcion('Servicio de conectividad y soporte logístico para sustentación académica')
            );

            $this->items[$sample1->id()->value()] = $sample1;
            $this->items[$sample2->id()->value()] = $sample2;
            $this->flushToDisk();
        }
    }

    public function save(GastoModel $gasto): GastoModel
    {
        $this->items[$gasto->id()->value()] = $gasto;
        $this->flushToDisk();

        return $gasto;
    }

    public function update(GastoModel $gasto): GastoModel
    {
        $this->items[$gasto->id()->value()] = $gasto;
        $this->flushToDisk();

        return $gasto;
    }

    public function getById(GastoId $gastoId): ?GastoModel
    {
        return $this->items[$gastoId->value()] ?? null;
    }

    /**
     * @return GastoModel[]
     */
    public function getAll(
        ?string $nombreUsuario = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null
    ): array {
        $filtered = array();

        foreach ($this->items as $gasto) {
            if ($nombreUsuario !== null && trim($nombreUsuario) !== '') {
                if (mb_stripos($gasto->nombreUsuario()->value(), trim($nombreUsuario)) === false) {
                    continue;
                }
            }

            if ($fechaDesde !== null && trim($fechaDesde) !== '') {
                if ($gasto->fecha()->value() < trim($fechaDesde)) {
                    continue;
                }
            }

            if ($fechaHasta !== null && trim($fechaHasta) !== '') {
                if ($gasto->fecha()->value() > trim($fechaHasta)) {
                    continue;
                }
            }

            $filtered[] = $gasto;
        }

        usort(
            $filtered,
            static fn(GastoModel $a, GastoModel $b): int => strcmp($b->fecha()->value(), $a->fecha()->value())
        );

        return $filtered;
    }

    public function delete(GastoId $gastoId): void
    {
        unset($this->items[$gastoId->value()]);
        $this->flushToDisk();
    }

    private function loadFromDisk(): void
    {
        if ($this->persistenceFile === null || !is_file($this->persistenceFile)) {
            return;
        }

        $raw = (string) file_get_contents($this->persistenceFile);
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return;
        }

        foreach ($decoded as $row) {
            if (!is_array($row) || !isset($row['id'], $row['fecha'], $row['valorTotalSinIVA'], $row['ivaTotal'], $row['valorTotalConIVA'], $row['nombreUsuario'], $row['lugar'], $row['descripcion'])) {
                continue;
            }

            $model = GastoModel::create(
                new GastoId((string) $row['id']),
                new GastoFecha((string) $row['fecha']),
                GastoMonto::fromInput((string) $row['valorTotalSinIVA'], 'valorTotalSinIVA', false),
                GastoMonto::fromInput((string) $row['ivaTotal'], 'ivaTotal', true),
                GastoMonto::fromInput((string) $row['valorTotalConIVA'], 'valorTotalConIVA', false),
                new UserName((string) $row['nombreUsuario']),
                new GastoLugar((string) $row['lugar']),
                new GastoDescripcion((string) $row['descripcion'])
            );
            $this->items[$model->id()->value()] = $model;
        }
    }

    private function flushToDisk(): void
    {
        if ($this->persistenceFile === null) {
            return;
        }

        $dir = dirname($this->persistenceFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $payload = array();
        foreach ($this->items as $item) {
            $payload[] = $item->toArray();
        }

        @file_put_contents($this->persistenceFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}