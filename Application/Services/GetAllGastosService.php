<?php

declare(strict_types=1);

require_once __DIR__ . '/../Ports/In/GetAllGastosUseCase.php';
require_once __DIR__ . '/../Ports/Out/GetAllGastosPort.php';
require_once __DIR__ . '/../Ports/Out/CachePort.php';
require_once __DIR__ . '/Mappers/GastoApplicationMapper.php';

final class GetAllGastosService implements GetAllGastosUseCase
{
    private GetAllGastosPort $getAllGastosPort;
    private ?CachePort $cachePort;
    private int $cacheTtlSeconds;

    public function __construct(
        GetAllGastosPort $getAllGastosPort,
        ?CachePort $cachePort = null,
        int $cacheTtlSeconds = 120
    ) {
        $this->getAllGastosPort = $getAllGastosPort;
        $this->cachePort = $cachePort;
        $this->cacheTtlSeconds = $cacheTtlSeconds;
    }

    /**
     * @return GastoModel[]
     */
    public function execute(GetAllGastosQuery $query): array
    {
        $cacheKey = 'gastos:list:' . sha1((string) json_encode(array(
            'u' => $query->getNombreUsuario(),
            'd' => $query->getFechaDesde(),
            'h' => $query->getFechaHasta(),
        )));

        if ($this->cachePort !== null) {
            $cached = $this->cachePort->get($cacheKey);
            if (is_array($cached)) {
                return GastoApplicationMapper::fromArraysToModels($cached);
            }
        }

        $models = $this->getAllGastosPort->getAll(
            $query->getNombreUsuario(),
            $query->getFechaDesde(),
            $query->getFechaHasta()
        );

        if ($this->cachePort !== null) {
            $this->cachePort->set(
                $cacheKey,
                GastoApplicationMapper::fromModelsToArray($models),
                $this->cacheTtlSeconds
            );
        }

        return $models;
    }
}