<?php

declare(strict_types=1);

require_once __DIR__ . '/Mapper/GastoWebMapper.php';

require_once __DIR__ . '/../../../../Application/Ports/In/CreateGastoUseCase.php';
require_once __DIR__ . '/../../../../Application/Ports/In/UpdateGastoUseCase.php';
require_once __DIR__ . '/../../../../Application/Ports/In/GetGastoByIdUseCase.php';
require_once __DIR__ . '/../../../../Application/Ports/In/GetAllGastosUseCase.php';
require_once __DIR__ . '/../../../../Application/Ports/In/DeleteGastoUseCase.php';

final class GastoController
{
    private CreateGastoUseCase $createGastoUseCase;
    private UpdateGastoUseCase $updateGastoUseCase;
    private GetGastoByIdUseCase $getGastoByIdUseCase;
    private GetAllGastosUseCase $getAllGastosUseCase;
    private DeleteGastoUseCase $deleteGastoUseCase;
    private GastoWebMapper $mapper;

    public function __construct(
        CreateGastoUseCase $createGastoUseCase,
        UpdateGastoUseCase $updateGastoUseCase,
        GetGastoByIdUseCase $getGastoByIdUseCase,
        GetAllGastosUseCase $getAllGastosUseCase,
        DeleteGastoUseCase $deleteGastoUseCase,
        GastoWebMapper $mapper
    ) {
        $this->createGastoUseCase = $createGastoUseCase;
        $this->updateGastoUseCase = $updateGastoUseCase;
        $this->getGastoByIdUseCase = $getGastoByIdUseCase;
        $this->getAllGastosUseCase = $getAllGastosUseCase;
        $this->deleteGastoUseCase = $deleteGastoUseCase;
        $this->mapper = $mapper;
    }

    /**
     * @return GastoResponse[]
     */
    public function index(
        ?string $nombreUsuario = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null
    ): array {
        $query = $this->mapper->fromFiltersToGetAllQuery($nombreUsuario, $fechaDesde, $fechaHasta);
        $gastos = $this->getAllGastosUseCase->execute($query);

        return $this->mapper->fromModelsToResponses($gastos);
    }

    public function show(string $id): GastoResponse
    {
        $query = $this->mapper->fromIdToGetByIdQuery($id);
        $gasto = $this->getGastoByIdUseCase->execute($query);

        return $this->mapper->fromModelToResponse($gasto);
    }

    public function store(CreateGastoWebRequest $request): GastoResponse
    {
        $command = $this->mapper->fromCreateRequestToCommand($request);
        $gasto = $this->createGastoUseCase->execute($command);

        return $this->mapper->fromModelToResponse($gasto);
    }

    public function update(UpdateGastoWebRequest $request): GastoResponse
    {
        $command = $this->mapper->fromUpdateRequestToCommand($request);
        $gasto = $this->updateGastoUseCase->execute($command);

        return $this->mapper->fromModelToResponse($gasto);
    }

    public function delete(string $id): void
    {
        $command = $this->mapper->fromIdToDeleteCommand($id);
        $this->deleteGastoUseCase->execute($command);
    }
}